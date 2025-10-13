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
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        if ($operacionId <= 0) return;
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            // Si tu OperacionesLogModel registra por operación marítima/ferro, aquí solo pasamos el ID.
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

    /**
     * GET /Operaciones_ferroviarias_costos_operacion/listarPaginado
     * o     /operaciones_maritimo_ferro_costos_contenedor/listarPaginado
     * Query:
     *  - page, perPage, buscar, moneda(PESOS|DLLS|''), tipo
     *  - operacion_ferro_id | operacion_id
     *  - solo_activos (1|0)
     *  - fuente = 'F' | 'MF'   (por defecto 'F' para compatibilidad)
     */
    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page        = (int)($_GET['page'] ?? 1);
        $perPage     = (int)($_GET['perPage'] ?? 10);
        $buscar      = trim((string)($_GET['buscar'] ?? ''));
        $monedaRaw   = trim((string)($_GET['moneda'] ?? ''));
        $tipoId      = (int)($_GET['tipo'] ?? ($_GET['tipo_movimiento_id'] ?? 0));
        $fuente      = strtoupper(trim((string)($_GET['fuente'] ?? 'F'))); // 'F' | 'MF'
        if ($fuente !== 'MF') $fuente = 'F';

        // Compat: operacion_ferro_id u operacion_id
        $operacionId = (int)($_GET['operacion_ferro_id'] ?? $_GET['operacion_id'] ?? 0);
        $soloActivos = isset($_GET['solo_activos']) ? ((int)$_GET['solo_activos'] === 1) : true;

        $m = strtoupper($monedaRaw);
        if ($m !== 'PESOS' && $m !== 'DLLS') { $m = ''; }

        // Normalizamos filtros para el modelo combinado
        $filtros = [
            'fuente'            => $fuente,
            // siempre mandamos ambos por compatibilidad; el modelo toma el que corresponda
            'operacion_ferro_id'=> $operacionId,
            'operacion_id'      => $operacionId,
            'buscar'            => $buscar,
            'moneda'            => $m,
            'tipo_movimiento_id'=> $tipoId,
            'solo_activos'      => $soloActivos,
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
                    'totalPages' => $totalPages,
                    'fuente'     => $fuente
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
                'message' => 'Error al listar costos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /Operaciones_ferroviarias_costos_operacion/buscarOperaciones?term=xxx
     * o   /operaciones_maritimo_ferro_costos_contenedor/buscarOperaciones
     * Autocompletar por número de operación (FO + LBMF).
     * Respuesta estándar: [{ id, numero_operacion, cliente, fuente }]
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
            $rows = $this->model->buscarOperacionesCombinadasPorTerm($term) ?: [];
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al buscar operaciones: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /operaciones_maritimo_ferro_costos_contenedor/tiposMovimiento?fuente=F|MF
     * Devuelve tipos de movimiento activos según la fuente.
     */
    public function tiposMovimiento()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $fuente = strtoupper(trim((string)($_GET['fuente'] ?? 'F')));
        if ($fuente !== 'MF') $fuente = 'F';

        try {
            $rows = $this->model->obtenerTiposMovimientoActivosPorFuente($fuente);
            echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /Operaciones_ferroviarias_costos_operacion/guardar
     * o   /operaciones_maritimo_ferro_costos_contenedor/guardar
     * Body:
     *  - fuente (F|MF)
     *  - row_id (0 crea / >0 actualiza)
     *  - operacion_ferro_id (cuando F) | operacion_id (cuando MF)
     *  - tipo_movimiento_id, monto, comentario
     */
    public function guardar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $fuente      = strtoupper(trim((string)($_POST['fuente'] ?? 'F')));
            if ($fuente !== 'MF') $fuente = 'F';

            $rowId       = (int)($_POST['row_id'] ?? 0);
            // Compat: permitimos que vengan ambos y el modelo tomará el correcto
            $operacionId = (int)($_POST['operacion_ferro_id'] ?? $_POST['operacion_id'] ?? 0);
            $tipoId      = (int)($_POST['tipo_movimiento_id'] ?? 0);
            $monto       = (float)($_POST['monto'] ?? 0);
            $comentario  = trim((string)($_POST['comentario'] ?? ''));

            if ($operacionId <= 0 && $rowId <= 0) { echo json_encode(['status'=>'warning','message'=>'Falta operación']); return; }
            if ($tipoId      <= 0) { echo json_encode(['status'=>'warning','message'=>'Selecciona un tipo de movimiento']); return; }
            if ($monto       <= 0) { echo json_encode(['status'=>'warning','message'=>'Monto inválido']); return; }

            // Validar catálogo (moneda/tipo) y opcionalmente pertenencia al tipo_operacion_id
            $tm = $this->model->obtenerTipoMovimiento($tipoId);
            if (!$tm) { echo json_encode(['status'=>'warning','message'=>'Tipo de movimiento inválido']); return; }

            $monedaCat  = strtoupper((string)($tm['moneda'] ?? ''));
            $tipoDinero = strtolower((string)($tm['tipo'] ?? '')); // 'gasto' | 'abono'
            if ($monedaCat !== 'PESOS' && $monedaCat !== 'DLLS') {
                echo json_encode(['status'=>'warning','message'=>'Moneda del tipo de movimiento inválida']); return;
            }

            // (Opcional) Validar que el tipo_movimiento_id pertenezca a la fuente:
            // $tiposPermitidos = $this->model->obtenerTiposMovimientoActivosPorFuente($fuente);
            // if (!in_array($tipoId, array_column($tiposPermitidos, 'id_tipo_movimiento'))) { ... }

            if ($rowId > 0) {
                // === ACTUALIZAR ===
                $prev = $this->model->obtenerCostoOperacionCombinado($rowId, $fuente);
                if (!$prev) { echo json_encode(['status'=>'warning','message'=>'Registro no encontrado']); return; }

                $ok = $this->model->actualizarCostoOperacionCombinado($rowId, [
                    'fuente'             => $fuente,
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'comentario'         => $comentario,
                ]);
                if (!$ok){ echo json_encode(['status'=>'error','message'=>'No se actualizó el registro']); return; }

                $opId4 = (int)($prev['operacion_id'] ?? $operacionId);
                $desc = $this->makeDesc('Movimiento de operación actualizado', [
                    'fuente'   => $fuente,
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
                if ($operacionId <= 0){ echo json_encode(['status'=>'warning','message'=>'Falta operación']); return; }

                $newId = $this->model->insertarCostoOperacionCombinado([
                    'fuente'             => $fuente,
                    'operacion_ferro_id' => $operacionId, // compat
                    'operacion_id'       => $operacionId, // compat
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'comentario'         => $comentario,
                ]);
                if ($newId <= 0){ echo json_encode(['status'=>'error','message'=>'No se creó el registro']); return; }

                $desc = $this->makeDesc('Movimiento de operación creado', [
                    'fuente'   => $fuente,
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
            echo json_encode(['status'=>'error','message'=>'Error al guardar: '.$e->getMessage()]);
        }
    }

    /**
     * GET /Operaciones_ferroviarias_costos_operacion/obtenerUno?id=XX&fuente=F|MF
     */
    public function obtenerUno()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id     = (int)($_GET['id'] ?? 0);
        $fuente = strtoupper(trim((string)($_GET['fuente'] ?? 'F')));
        if ($fuente !== 'MF') $fuente = 'F';

        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id, $fuente);
            if (!$row){ echo json_encode(['status'=>'warning','message'=>'No encontrado']); return; }
            echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Error: '.$e->getMessage()]);
        }
    }

    /**
     * POST /Operaciones_ferroviarias_costos_operacion/desactivarCostoOperacion
     * Body: id, fuente(F|MF)
     */
    public function desactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id     = (int)($_POST['id'] ?? 0);
        $fuente = strtoupper(trim((string)($_POST['fuente'] ?? 'F')));
        if ($fuente !== 'MF') $fuente = 'F';

        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id, $fuente);
            $ok  = $this->model->desactivarCostoOperacionCombinado($id, $fuente);

            if ($ok && $row) {
                $opId = (int)($row['operacion_id'] ?? 0);
                $desc = $this->makeDesc('Costo de operación desactivado', [
                    'fuente'   => $fuente,
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

    /**
     * POST /Operaciones_ferroviarias_costos_operacion/reactivarCostoOperacion
     * Body: id, fuente(F|MF)
     */
    public function reactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id     = (int)($_POST['id'] ?? 0);
        $fuente = strtoupper(trim((string)($_POST['fuente'] ?? 'F')));
        if ($fuente !== 'MF') $fuente = 'F';

        if ($id <= 0){ echo json_encode(['status'=>'warning','message'=>'ID inválido']); return; }
        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id, $fuente);
            $ok  = $this->model->reactivarCostoOperacionCombinado($id, $fuente);

            if ($ok && $row) {
                $opId = (int)($row['operacion_id'] ?? 0);
                $desc = $this->makeDesc('Costo de operación reactivado', [
                    'fuente'   => $fuente,
                    'costo_id' => $id,
                    'tipo_id'  => $row['tipo_movimiento_id'] ?? '-'
                ]);
                // Si no tienes 'reactivacion' en ENUM, usa 'actualizacion'
                $this->logOp($opId, 'actualizacion', $desc);
            }

            echo json_encode($ok ? ['status'=>'success'] : ['status'=>'error','message'=>'No se reactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }

    public function contenedorLigado()
{
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $fuente = strtoupper(trim((string)($_GET['fuente'] ?? 'F')));
        if ($fuente !== 'MF') $fuente = 'F';

        // aceptamos ambos nombres para compatibilidad:
        $opId = (int)($_GET['operacion_id'] ?? $_GET['operacion_ferro_id'] ?? 0);
        if ($opId <= 0) {
            echo json_encode(['status'=>'warning','message'=>'Falta operación'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $row = $this->model->obtenerContenedorLigado([
            'fuente'        => $fuente,
            'operacion_id'  => $opId,
            'operacion_ferro_id' => $opId
        ]);

        if (!$row) {
            echo json_encode(['status'=>'warning','message'=>'Sin contenedor ligado'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

}
