<?php
class Operaciones_maritimas_costos_operacion extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /Operaciones_maritimas_contenedores_costos_Operacion/listarPaginado
     * Query params soportados:
     *   - page            (int)    default 1
     *   - perPage         (int)    default 10
     *   - buscar          (string) filtro libre: concepto/comentario/número de operación
     *   - moneda          (string) 'PESOS'|'DLLS'|''
     *   - tipo            (int)    id_tipo_movimiento
     *   - operacion_id    (int)    id de operación (opcional, pero recomendado para tu vista)
     *   - solo_activos    (int)    1|0 (default 1)
     *
     * Respuesta:
     * {
     *   "status":"success",
     *   "meta":{"page":1,"perPage":10,"total":123,"totalPages":13},
     *   "data":[{... filas ...}]
     * }
     */
    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // --- Query params ---
        $page        = (int)($_GET['page'] ?? 1);
        $perPage     = (int)($_GET['perPage'] ?? 10);
        $buscar      = trim((string)($_GET['buscar'] ?? ''));
        $monedaRaw   = trim((string)($_GET['moneda'] ?? '')); // 'PESOS'|'DLLS'|''
        $tipoId      = (int)($_GET['tipo'] ?? 0);
        $operacionId = (int)($_GET['operacion_id'] ?? 0);
        $origen      = strtoupper(trim((string)($_GET['origen'] ?? ''))); // ''|'OPERACION'|'CONTENEDOR'
        $soloActivos = isset($_GET['solo_activos']) ? ((int)$_GET['solo_activos'] === 1) : true;

        // Normaliza moneda
        $m = strtoupper($monedaRaw);
        if ($m !== 'PESOS' && $m !== 'DLLS') {
            $m = '';
        }

        // Filtros al modelo
        $filtros = [
            'operacion_id'       => $operacionId,
            'buscar'             => $buscar,
            'moneda'             => $m,
            'tipo_movimiento_id' => $tipoId,
            'origen'             => $origen,
            'solo_activos'       => $soloActivos,
        ];

        try {
            // Totales para cards
            $totales = $this->model->totalesCostosCombinados($filtros);
            $totalesDetalle = $this->model->totalesCostosCombinadosDetallado($filtros);

            // Conteo total para paginación
            $total = $this->model->contarCostosCombinados($filtros);

            // Asegura página válida si cambia perPage o hay menos datos
            $totalPages = (int)ceil($total / max(1, $perPage));
            if ($totalPages > 0 && $page > $totalPages) {
                $page = $totalPages;
            }
            if ($page < 1) $page = 1;

            // Datos
            $rows = $this->model->listarCostosCombinadosPaginado($page, $perPage, $filtros);

            echo json_encode([
                'status'  => 'success',
                'meta'    => [
                    'page'       => $page,
                    'perPage'    => $perPage,
                    'total'      => (int)$total,
                    'totalPages' => $totalPages
                ],
                'totales' => is_array($totales) ? $totales : [
                    'total_operacion'    => 0,
                    'total_contenedores' => 0,
                    'total_general'      => 0
                ],
                'totales_detalle' => is_array($totalesDetalle) ? $totalesDetalle : [ 
                    'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                    'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
                ],
                'data'    => is_array($rows) ? $rows : []
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar costos combinados: ' . $e->getMessage()
            ]);
        }
    }
    public function buscarOperaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term = trim((string)($_GET['term'] ?? ''));
        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $rows = $this->model->buscarOperacionesPorTerm($term);
            echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al buscar operaciones: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    public function guardar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rowId        = (int)($_POST['row_id'] ?? 0);                // si >0 => actualizar
            $operacionId  = (int)($_POST['operacion_id'] ?? 0);
            $tipoId       = (int)($_POST['tipo_movimiento_id'] ?? 0);
            $monto        = (float)($_POST['monto'] ?? 0);
            $moneda       = strtoupper(trim((string)($_POST['moneda'] ?? '')));
            $comentario   = trim((string)($_POST['comentario'] ?? ''));

            if ($operacionId <= 0) { echo json_encode(['status'=>'warning','message'=>'Falta operación']); return; }
            if ($tipoId      <= 0) { echo json_encode(['status'=>'warning','message'=>'Selecciona un tipo de costo']); return; }
            if ($monto       <= 0) { echo json_encode(['status'=>'warning','message'=>'Monto inválido']); return; }
            if ($moneda !== 'PESOS' && $moneda !== 'DLLS'){
                echo json_encode(['status'=>'warning','message'=>'Moneda inválida']); return;
            }

            if ($rowId > 0){
                // actualizar
                $ok = $this->model->actualizarCostoOperacion($rowId, [
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'moneda'             => $moneda,     // opcional si la derives del tipo en BD
                    'comentario'         => $comentario,
                ]);
                if (!$ok){ echo json_encode(['status'=>'error','message'=>'No se actualizó el registro']); return; }
                echo json_encode(['status'=>'success','message'=>'Actualizado']); return;
            } else {
                // crear
                $newId = $this->model->insertarCostoOperacion([
                    'operacion_id'       => $operacionId,
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'moneda'             => $moneda,
                    'comentario'         => $comentario,
                ]);
                if ($newId <= 0){ echo json_encode(['status'=>'error','message'=>'No se creó el registro']); return; }
                echo json_encode(['status'=>'success','message'=>'Creado','id'=>$newId]); return;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Error al guardar: '.$e->getMessage()]);
        }
    }

    // (Opcional) para prefilling exacto desde BD si no quieres usar dataset del <tr>
    public function obtenerUno()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $row = $this->model->obtenerCostoOperacion($id);
            if (!$row){ echo json_encode(['status'=>'warning','message'=>'No encontrado']); return; }
            echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Error: '.$e->getMessage()]);
        }
    }
    public function desactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $ok = $this->model->desactivarCostoOperacion($id);
            echo json_encode($ok ? ['status'=>'success'] : ['status'=>'error','message'=>'No se desactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }

    public function reactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $ok = $this->model->reactivarCostoOperacion($id);
            echo json_encode($ok ? ['status'=>'success'] : ['status'=>'error','message'=>'No se reactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }


    
}
