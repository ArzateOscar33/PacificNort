<?php
class Operaciones_maritimo_ferro_costos_Contenedor extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        require_once "Models/OperacionesLogModel.php";
        $this->opLog = new OperacionesLogModel();
    }

    /* ===== Helpers de auditoría ===== */
    private function logOp(int $operacionFerroId, string $accion, string $descripcion): void
    {
        if ($operacionFerroId <= 0) return;
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            // Si tu OperacionesLogModel registra por operación marítima,
            // puedes crear un método alterno para ferro o guardar el ID “tal cual”.
            $id = $this->opLog->crear($operacionFerroId, $usuarioId, $accion, $descripcion);
            if (!$id) { error_log("operaciones_log: insert falló ({$accion}) opFerro={$operacionFerroId}"); }
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

    /**
     * GET /Operaciones_ferroviarias_costos_operacion/listarPaginado
     * Query:
     *  - page, perPage, buscar, moneda(PESOS|DLLS|''), tipo, operacion_ferro_id, solo_activos(1|0)
     */
    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page        = (int)($_GET['page'] ?? 1);
        $perPage     = (int)($_GET['perPage'] ?? 10);
        $buscar      = trim((string)($_GET['buscar'] ?? ''));
        $monedaRaw   = trim((string)($_GET['moneda'] ?? ''));
        $tipoId      = (int)($_GET['tipo'] ?? 0);
        $operacionId = (int)($_GET['operacion_ferro_id'] ?? $_GET['operacion_id'] ?? 0);
        $soloActivos = isset($_GET['solo_activos']) ? ((int)$_GET['solo_activos'] === 1) : true;

        $m = strtoupper($monedaRaw);
        if ($m !== 'PESOS' && $m !== 'DLLS') { $m = ''; }

        $filtros = [
            'operacion_ferro_id' => $operacionId,
            'buscar'             => $buscar,
            'moneda'             => $m,
            'tipo_movimiento_id' => $tipoId,
            'solo_activos'       => $soloActivos,
        ];

        try {
            $abonosDetalle = $this->model->abonosCombinadosDetallado($filtros);
            $totales       = $this->model->totalesCostosCombinados($filtros);
            $totalesDet    = $this->model->totalesCostosCombinadosDetallado($filtros);
            $total         = $this->model->contarCostosCombinados($filtros);

            $totalPages = (int)ceil($total / max(1, $perPage));
            if ($totalPages > 0 && $page > $totalPages) { $page = $totalPages; }
            if ($page < 1) $page = 1;

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
                    'total_operacion'           => 0,
                    'total_contenedores'        => 0,
                    'total_general'             => 0,
                    'total_abonos_operacion'    => 0,
                    'total_abonos_contenedores' => 0,
                ],
                'totales_detalle' => is_array($totalesDet) ? $totalesDet : [
                    'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                    'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
                ],
                'abonos_detalle' => is_array($abonosDetalle) ? $abonosDetalle : [
                    'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                    'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
                ],
                'data'    => is_array($rows) ? $rows : []
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar costos (ferro): ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /Operaciones_ferroviarias_costos_operacion/buscarOperaciones?term=xxx
     * Autocompletar por número de operación (ferro).
     */
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
                'message' => 'Error al buscar operaciones (ferro): ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /Operaciones_ferroviarias_costos_operacion/guardar
     * Body:
     *  - row_id(0 crea / >0 actualiza), operacion_ferro_id, tipo_movimiento_id, monto, comentario
     */
    public function guardar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rowId       = (int)($_POST['row_id'] ?? 0);
            $operacionId = (int)($_POST['operacion_ferro_id'] ?? $_POST['operacion_id'] ?? 0);
            $tipoId      = (int)($_POST['tipo_movimiento_id'] ?? 0);
            $monto       = (float)($_POST['monto'] ?? 0);
            $comentario  = trim((string)($_POST['comentario'] ?? ''));

            if ($operacionId <= 0 && $rowId <= 0) { echo json_encode(['status'=>'warning','message'=>'Falta operación ferro']); return; }
            if ($tipoId      <= 0) { echo json_encode(['status'=>'warning','message'=>'Selecciona un tipo de movimiento']); return; }
            if ($monto       <= 0) { echo json_encode(['status'=>'warning','message'=>'Monto inválido']); return; }

            // Catálogo para moneda y tipo (gasto|abono)
            $tm = $this->model->obtenerTipoMovimiento($tipoId);
            if (!$tm) { echo json_encode(['status'=>'warning','message'=>'Tipo de movimiento inválido']); return; }

            $monedaCat = strtoupper((string)($tm['moneda'] ?? ''));
            $tipoDinero = strtolower((string)($tm['tipo'] ?? '')); // 'gasto' | 'abono'
            if ($monedaCat !== 'PESOS' && $monedaCat !== 'DLLS') {
                echo json_encode(['status'=>'warning','message'=>'Moneda del tipo de movimiento inválida']); return;
            }

            if ($rowId > 0){
                // === ACTUALIZAR ===
                $prev  = $this->model->obtenerCostoOperacion($rowId);
                $opId4 = $operacionId > 0 ? $operacionId : (int)($prev['operacion_ferro_id'] ?? 0);

                $ok = $this->model->actualizarCostoOperacion($rowId, [
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'comentario'         => $comentario,
                ]);
                if (!$ok){ echo json_encode(['status'=>'error','message'=>'No se actualizó el registro']); return; }

                $desc = $this->makeDesc('Movimiento de operación FERRO actualizado', [
                    'costo_id' => $rowId,
                    'tipo_id'  => $tipoId,
                    'tipo'     => $tipoDinero,
                    'monto'    => $monto,
                    'moneda'   => $monedaCat,
                    'coment'   => ($comentario !== '' ? mb_substr($comentario,0,60).'…' : '')
                ]);
                $this->logOp($opId4, 'actualizacion', $desc);

                echo json_encode(['status'=>'success','message'=>'Actualizado']); return;

            } else {
                // === CREAR ===
                if ($operacionId <= 0){ echo json_encode(['status'=>'warning','message'=>'Falta operación ferro']); return; }

                $newId = $this->model->insertarCostoOperacion([
                    'operacion_ferro_id' => $operacionId,
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'comentario'         => $comentario,
                ]);
                if ($newId <= 0){ echo json_encode(['status'=>'error','message'=>'No se creó el registro']); return; }

                $desc = $this->makeDesc('Movimiento de operación FERRO creado', [
                    'costo_id' => $newId,
                    'tipo_id'  => $tipoId,
                    'tipo'     => $tipoDinero,
                    'monto'    => $monto,
                    'moneda'   => $monedaCat,
                    'coment'   => ($comentario !== '' ? mb_substr($comentario,0,60).'…' : '')
                ]);
                $this->logOp($operacionId, 'creacion', $desc);

                echo json_encode(['status'=>'success','message'=>'Creado','id'=>$newId]); return;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Error al guardar (ferro): '.$e->getMessage()]);
        }
    }

    /** GET /Operaciones_ferroviarias_costos_operacion/obtenerUno?id=XX */
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

    /** POST /Operaciones_ferroviarias_costos_operacion/desactivarCostoOperacion */
    public function desactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $row = $this->model->obtenerCostoOperacion($id);
            $ok  = $this->model->desactivarCostoOperacion($id);

            if ($ok) {
                $opId = (int)($row['operacion_ferro_id'] ?? 0);
                $desc = $this->makeDesc('Costo de operación FERRO desactivado', [
                    'costo_id' => $id,
                    'tipo_id'  => $row['tipo_movimiento_id'] ?? '-',
                    'monto'    => $row['monto'] ?? '-',
                    'moneda'   => $row['moneda'] ?? '-'
                ]);
                $this->logOp($opId, 'cancelacion', $desc);
            }

            echo json_encode($ok ? ['status'=>'success'] : ['status'=>'error','message'=>'No se desactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }

    /** POST /Operaciones_ferroviarias_costos_operacion/reactivarCostoOperacion */
    public function reactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $row = $this->model->obtenerCostoOperacion($id);
            $ok  = $this->model->reactivarCostoOperacion($id);

            if ($ok) {
                $opId = (int)($row['operacion_ferro_id'] ?? 0);
                $desc = $this->makeDesc('Costo de operación FERRO reactivado', [
                    'costo_id' => $id,
                    'tipo_id'  => $row['tipo_movimiento_id'] ?? '-'
                ]);
                // Si no tienes 'reactivacion' en ENUM, mantenemos 'actualizacion'
                $this->logOp($opId, 'actualizacion', $desc);
            }

            echo json_encode($ok ? ['status'=>'success'] : ['status'=>'error','message'=>'No se reactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }
}
