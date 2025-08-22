<?php
class Operaciones_maritimas_costos_Contenedor extends Controller
{
    

    public function __construct()
    {
        parent::__construct();
    }

    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Query params
        $page    = (int)($_GET['page']    ?? 1);
        $perPage = (int)($_GET['perPage'] ?? 10);
        $buscar  = trim($_GET['buscar']   ?? '');
        $moneda  = trim($_GET['moneda']   ?? '');            // 'PESOS' | 'DLLS' | ''
        $tipoId  = (int)($_GET['tipo']    ?? 0);             // id_tipo_movimiento

        // Filtros
        $filtros = [
            'buscar'            => $buscar,
            'moneda'            => $moneda,
            'tipo_movimiento_id'=> $tipoId,
        ];

        try {
            $total = $this->model->contarCostos($filtros);
            $rows  = $this->model->listarCostosPaginado($page, $perPage, $filtros);

            $totalPages = (int)ceil($total / max(1, $perPage));
            echo json_encode([
                'status' => 'success',
                'meta' => [
                    'page'       => $page,
                    'perPage'    => $perPage,
                    'total'      => $total,
                    'totalPages' => $totalPages
                ],
                'data' => $rows
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar costos: ' . $e->getMessage()
            ]);
        }
    }
    public function catalogoTiposMovimiento()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $soloGastos   = isset($_GET['solo_gastos']) ? (int)$_GET['solo_gastos'] : 1; // 1 = solo GASTO
        $categoriaId  = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
        $categoria    = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';   // ej: "Terrestre"

        try {
            // Si mandan nombre, resuélvelo a id
            if ($categoriaId <= 0 && $categoria !== '' && method_exists($this->model, 'getTipoOperacionIdPorNombre')) {
                $categoriaId = (int)($this->model->getTipoOperacionIdPorNombre($categoria) ?? 0);
            }

            // Llama al modelo con filtros
            if (method_exists($this->model, 'catalogoTiposMovimiento')) {
                $rows = $this->model->catalogoTiposMovimiento($soloGastos === 1, $categoriaId > 0 ? $categoriaId : null);
                echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }




    /**
     * GET /Operaciones_maritimas_costos_Contenedor/buscarOperaciones?term=JL
     * Respuesta: JSON [{id_operacion, numero_operacion, cliente_id, cliente}, ...]
     */
    public function buscarOperaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $term  = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

        try {
            $rows = $this->model->buscarOperaciones($term, $limit);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /Operaciones_maritimas_costos_Contenedor/buscarContenedoresPorOperacion?operacion_id=7&term=FXE
     * Respuesta: JSON [{contenedor_operacion_id, id_fisico, numero_ferro}, ...]
     */
    public function buscarContenedoresPorOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        $term        = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit       = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if ($operacionId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'warning', 'message' => 'operacion_id es requerido']);
            return;
        }

        try {
            $rows = $this->model->buscarContenedoresPorOperacion($operacionId, $term, $limit);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    
    public function registrarCostoContenedor()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        // === 1) Entradas del formulario (names/ids de tu vista) ===
        $contenedorOpId = (int)($_POST['costosContenedorContenedorId'] ?? 0);      // hidden contenedor_operacion_id
        $tipoMovId      = (int)($_POST['costosContenedoresTipoCosto'] ?? 0);       // select tipo_movimiento_id
        $monto          = (float)($_POST['costosContenedoresMonto'] ?? 0);         // number
        $comentario     = trim((string)($_POST['costosContenedoresComentarios'] ?? ''));

        // (Opcional) campos de búsqueda no son obligatorios para insertar:
        // costosOperacionid, costosOperacionNombre, costosContenedorContenedorNombre

        // === 2) Validaciones mínimas ===
        if ($contenedorOpId <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'Selecciona una operación y un contenedor válido']);
            return;
        }
        if ($tipoMovId <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'Selecciona un tipo de costo']);
            return;
        }
        if ($monto <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'El monto debe ser mayor a 0']);
            return;
        }

        // === 3) Validaciones de existencia (SQL via modelo) ===
        $co = $this->model->obtenerContenedorOperacion($contenedorOpId);
        if (!$co) {
            echo json_encode(['status'=>'warning','msg'=>'El contenedor en operación no existe']);
            return;
        }

        $tm = $this->model->obtenerTipoMovimiento($tipoMovId);
        if (!$tm || (int)($tm['estatus'] ?? 0) !== 1) {
            echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no existe o está inactivo']);
            return;
        }

        // === 4) Reglas de negocio del módulo ===
        // 4.1 Debe ser GASTO
        if (strtoupper($tm['tipo'] ?? '') !== 'GASTO') {
            echo json_encode(['status'=>'warning','msg'=>'El tipo seleccionado debe ser de tipo GASTO']);
            return;
        }

        // 4.2 Solo TERRESTRE (porque así definiste este módulo)
        $idTerrestre = $this->model->obtenerTipoOperacionIdPorNombre('Terrestre'); // null si no existe
        if ($idTerrestre && (int)($tm['tipo_operacion_id'] ?? 0) !== (int)$idTerrestre) {
            echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no pertenece a la categoría TERRESTRE']);
            return;
        }

        // (Opcional) Evitar duplicados exactos (mismo contenedor_op + tipo_mov + monto + comentario)
        // Si lo necesitas, aquí podrías consultar y decidir.

        // === 5) Insertar (SQL puro en el modelo) ===
        $newId = $this->model->insertarCostoContenedor($contenedorOpId, $tipoMovId, $monto, $comentario);
        if (!$newId) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo insertar el costo']);
            return;
        }

        // === 6) Traer el registro “bonito” para refrescar la tabla (JOINs) ===
        $row = $this->model->obtenerCostoPorId((int)$newId);

        echo json_encode([
            'status' => 'success',
            'msg'    => 'Costo registrado correctamente',
            'id'     => (int)$newId,
            'data'   => $row   // opcional: el frontend puede re-renderizar con esto o volver a listar
        ], JSON_UNESCAPED_UNICODE);

    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status'=>'error',
            'msg'   =>'Error al guardar: '.$e->getMessage()
        ]);
    }
}
public function obtenerCosto($id)
{
    header('Content-Type: application/json; charset=UTF-8');
    $id = (int)$id;
    if ($id <= 0) { echo json_encode(['status'=>'warning','msg'=>'ID inválido']); return; }

    try {
        $row = $this->model->obtenerCostoPorId($id);
        if (!$row) { echo json_encode(['status'=>'warning','msg'=>'No encontrado']); return; }
        echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
    }
}

public function actualizarCostoContenedor()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $id              = (int)($_POST['row_id'] ?? 0);
        $contenedorOpId  = (int)($_POST['costosContenedorContenedorId'] ?? 0);
        $tipoMovId       = (int)($_POST['costosContenedoresTipoCosto'] ?? 0);
        $monto           = (float)($_POST['costosContenedoresMonto'] ?? 0);
        $comentario      = trim((string)($_POST['costosContenedoresComentarios'] ?? ''));

        if ($id <= 0)                { echo json_encode(['status'=>'warning','msg'=>'ID inválido']); return; }
        if ($contenedorOpId <= 0)    { echo json_encode(['status'=>'warning','msg'=>'Selecciona una operación y contenedor válido']); return; }
        if ($tipoMovId <= 0)         { echo json_encode(['status'=>'warning','msg'=>'Selecciona un tipo de costo']); return; }
        if ($monto <= 0)             { echo json_encode(['status'=>'warning','msg'=>'El monto debe ser mayor a 0']); return; }

        // Validaciones de existencia/reglas (solo en controlador):
        $co = $this->model->obtenerContenedorOperacion($contenedorOpId);
        if (!$co) { echo json_encode(['status'=>'warning','msg'=>'El contenedor en operación no existe']); return; }

        $tm = $this->model->obtenerTipoMovimiento($tipoMovId);
        if (!$tm || (int)($tm['estatus'] ?? 0) !== 1) { echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no existe o está inactivo']); return; }
        if (strtoupper($tm['tipo'] ?? '') !== 'GASTO') { echo json_encode(['status'=>'warning','msg'=>'El tipo de costo debe ser GASTO']); return; }

        $idTerrestre = $this->model->obtenerTipoOperacionIdPorNombre('Terrestre');
        if ($idTerrestre && (int)($tm['tipo_operacion_id'] ?? 0) !== (int)$idTerrestre) {
            echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no pertenece a TERRESTRE']); return;
        }

        $ok = $this->model->actualizarCostoContenedor($id, $contenedorOpId, $tipoMovId, $monto, $comentario);
        if (!$ok) { echo json_encode(['status'=>'error','msg'=>'No se pudo actualizar']); return; }

        $row = $this->model->obtenerCostoPorId($id);
        echo json_encode(['status'=>'success','msg'=>'Costo actualizado','data'=>$row], JSON_UNESCAPED_UNICODE);

    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['status'=>'error','msg'=>'Error al actualizar: '.$e->getMessage()]);
    }
}
public function eliminarCostoContenedor()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'warning','msg'=>'ID inválido']); return; }

        // Verifica existencia antes de eliminar
        $row = $this->model->obtenerCostoPorId($id);
        if (!$row) { echo json_encode(['status'=>'warning','msg'=>'Registro no encontrado']); return; }

        $ok = $this->model->eliminarCostoContenedor($id);
        if (!$ok) { echo json_encode(['status'=>'error','msg'=>'No se pudo eliminar']); return; }

        echo json_encode(['status'=>'success','msg'=>'Costo eliminado correctamente']);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['status'=>'error','msg'=>'Error al eliminar: '.$e->getMessage()]);
    }
}

}


