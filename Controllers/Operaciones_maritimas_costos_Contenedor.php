<?php
require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimas_costos_Contenedor extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        $this->opLog = new OperacionesLogModel();
    }

    /* ===== Helpers de auditoría ===== */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        if ($operacionId <= 0) return;
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) { error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}"); }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: ".$e->getMessage());
        }
    }
    private function makeDesc(string $base, array $info = []): string
    {
        if (empty($info)) return $base;
        $kv = [];
        foreach ($info as $k => $v) { $kv[] = "$k=$v"; }
        return $base.' ('.implode(', ', $kv).')';
    }

    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page    = (int)($_GET['page']    ?? 1);
        $perPage = (int)($_GET['perPage'] ?? 10);
        $buscar  = trim($_GET['buscar']   ?? '');
        $moneda  = trim($_GET['moneda']   ?? '');            // 'PESOS' | 'DLLS' | ''
        $tipoId  = (int)($_GET['tipo']    ?? 0);

        $filtros = [
            'buscar'             => $buscar,
            'moneda'             => $moneda,
            'tipo_movimiento_id' => $tipoId,
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
            if ($categoriaId <= 0 && $categoria !== '' && method_exists($this->model, 'getTipoOperacionIdPorNombre')) {
                $categoriaId = (int)($this->model->getTipoOperacionIdPorNombre($categoria) ?? 0);
            }

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

    /* ================== CRUD con LOG ================== */

    public function registrarCostoContenedor()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $contenedorOpId = (int)($_POST['costosContenedorContenedorId'] ?? 0); // contenedor_operacion_id
            $tipoMovId      = (int)($_POST['costosContenedoresTipoCosto'] ?? 0);  // tipo_movimiento_id
            $monto          = (float)($_POST['costosContenedoresMonto'] ?? 0);
            $comentario     = trim((string)($_POST['costosContenedoresComentarios'] ?? ''));

            if ($contenedorOpId <= 0) { echo json_encode(['status'=>'warning','msg'=>'Selecciona una operación y un contenedor válido']); return; }
            if ($tipoMovId <= 0)      { echo json_encode(['status'=>'warning','msg'=>'Selecciona un tipo de costo']); return; }
            if ($monto <= 0)          { echo json_encode(['status'=>'warning','msg'=>'El monto debe ser mayor a 0']); return; }

            // Validaciones/Reglas
            $co = $this->model->obtenerContenedorOperacion($contenedorOpId);
            if (!$co) { echo json_encode(['status'=>'warning','msg'=>'El contenedor en operación no existe']); return; }

            $tm = $this->model->obtenerTipoMovimiento($tipoMovId);
            if (!$tm || (int)($tm['estatus'] ?? 0) !== 1) { echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no existe o está inactivo']); return; }
            if (strtoupper($tm['tipo'] ?? '') !== 'GASTO') { echo json_encode(['status'=>'warning','msg'=>'El tipo seleccionado debe ser de tipo GASTO']); return; }

            $idTerrestre = $this->model->obtenerTipoOperacionIdPorNombre('Terrestre'); // puede ser null
            if ($idTerrestre && (int)($tm['tipo_operacion_id'] ?? 0) !== (int)$idTerrestre) {
                echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no pertenece a la categoría TERRESTRE']); return;
            }

            // Insert
            $newId = $this->model->insertarCostoContenedor($contenedorOpId, $tipoMovId, $monto, $comentario);
            if (!$newId) { echo json_encode(['status'=>'error','msg'=>'No se pudo insertar el costo']); return; }

            // Obtener fila “bonita”
            $row = $this->model->obtenerCostoPorId((int)$newId);

            // ===== LOG: Creado =====
            $opId = (int)($co['operacion_id'] ?? 0);
            $desc = $this->makeDesc('Costo de contenedor creado', [
                'costo_id'    => (int)$newId,
                'cont_op_id'  => $contenedorOpId,
                'tipo_id'     => $tipoMovId,
                'monto'       => $monto,
                'coment'      => ($comentario !== '' ? mb_substr($comentario,0,60).'…' : '')
            ]);
            $this->logOp($opId, 'creacion', $desc);

            echo json_encode([
                'status' => 'success',
                'msg'    => 'Costo registrado correctamente',
                'id'     => (int)$newId,
                'data'   => $row
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
            $id             = (int)($_POST['row_id'] ?? 0);
            $contenedorOpId = (int)($_POST['costosContenedorContenedorId'] ?? 0);
            $tipoMovId      = (int)($_POST['costosContenedoresTipoCosto'] ?? 0);
            $monto          = (float)($_POST['costosContenedoresMonto'] ?? 0);
            $comentario     = trim((string)($_POST['costosContenedoresComentarios'] ?? ''));

            if ($id <= 0)             { echo json_encode(['status'=>'warning','msg'=>'ID inválido']); return; }
            if ($contenedorOpId <= 0) { echo json_encode(['status'=>'warning','msg'=>'Selecciona una operación y contenedor válido']); return; }
            if ($tipoMovId <= 0)      { echo json_encode(['status'=>'warning','msg'=>'Selecciona un tipo de costo']); return; }
            if ($monto <= 0)          { echo json_encode(['status'=>'warning','msg'=>'El monto debe ser mayor a 0']); return; }

            // Snapshot y validaciones
            $prev = $this->model->obtenerCostoPorId($id);
            $co   = $this->model->obtenerContenedorOperacion($contenedorOpId);
            if (!$co) { echo json_encode(['status'=>'warning','msg'=>'El contenedor en operación no existe']); return; }

            $tm = $this->model->obtenerTipoMovimiento($tipoMovId);
            if (!$tm || (int)($tm['estatus'] ?? 0) !== 1) { echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no existe o está inactivo']); return; }
            if (strtoupper($tm['tipo'] ?? '') !== 'GASTO') { echo json_encode(['status'=>'warning','msg'=>'El tipo de costo debe ser GASTO']); return; }

            $idTerrestre = $this->model->obtenerTipoOperacionIdPorNombre('Terrestre');
            if ($idTerrestre && (int)($tm['tipo_operacion_id'] ?? 0) !== (int)$idTerrestre) {
                echo json_encode(['status'=>'warning','msg'=>'El tipo de costo no pertenece a TERRESTRE']); return;
            }

            // Actualizar
            $ok = $this->model->actualizarCostoContenedor($id, $contenedorOpId, $tipoMovId, $monto, $comentario);
            if (!$ok) { echo json_encode(['status'=>'error','msg'=>'No se pudo actualizar']); return; }

            $row = $this->model->obtenerCostoPorId($id);

            // ===== LOG: Actualizado =====
            $opId = (int)($co['operacion_id'] ?? ($prev['operacion_id'] ?? 0));
            $desc = $this->makeDesc('Costo de contenedor actualizado', [
                'costo_id'    => $id,
                'cont_op_id'  => $contenedorOpId,
                'tipo_id'     => $tipoMovId,
                'monto'       => $monto,
                'coment'      => ($comentario !== '' ? mb_substr($comentario,0,60).'…' : '')
            ]);
            $this->logOp($opId, 'actualizacion', $desc);

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

            // Verifica existencia (y úsalo para el log)
            $row = $this->model->obtenerCostoPorId($id);
            if (!$row) { echo json_encode(['status'=>'warning','msg'=>'Registro no encontrado']); return; }

            $ok = $this->model->eliminarCostoContenedor($id);
            if (!$ok) { echo json_encode(['status'=>'error','msg'=>'No se pudo eliminar']); return; }

            // ===== LOG: Eliminado =====
            // Saca operacion_id de la fila o, si no viene, de contenedor_operacion
            $opId = (int)($row['operacion_id'] ?? 0);
            if ($opId <= 0 && !empty($row['contenedor_operacion_id'])) {
                $co = $this->model->obtenerContenedorOperacion((int)$row['contenedor_operacion_id']);
                $opId = (int)($co['operacion_id'] ?? 0);
            }

            $desc = $this->makeDesc('Costo de contenedor eliminado', [
                'costo_id'    => $id,
                'cont_op_id'  => $row['contenedor_operacion_id'] ?? '-',
                'tipo_id'     => $row['tipo_movimiento_id'] ?? '-',
                'monto'       => $row['monto'] ?? '-'
            ]);
            $this->logOp($opId, 'cancelacion', $desc);

            echo json_encode(['status'=>'success','msg'=>'Costo eliminado correctamente']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','msg'=>'Error al eliminar: '.$e->getMessage()]);
        }
    }
}
