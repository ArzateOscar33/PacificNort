<?php
class Operaciones_maritimas_costos_operacion extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        require_once "Models/OperacionesLogModel.php";
        $this->opLog = new OperacionesLogModel();
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2]);
    }

    /* ===== Helpers de auditoría ===== */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        if ($operacionId <= 0) return;
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) {
                error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}");
            }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: " . $e->getMessage());
        }
    }
    private function makeDesc(string $base, array $info = []): string
    {
        if (empty($info)) return $base;
        $kv = [];
        foreach ($info as $k => $v) {
            $kv[] = "$k=$v";
        }
        return $base . ' (' . implode(', ', $kv) . ')';
    }

    /**
     * GET /Operaciones_maritimas_contenedores_costos_Operacion/listarPaginado
     * ...
     */
    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page        = (int)($_GET['page'] ?? 1);
        $perPage     = (int)($_GET['perPage'] ?? 10);
        $buscar      = trim((string)($_GET['buscar'] ?? ''));
        $monedaRaw   = trim((string)($_GET['moneda'] ?? ''));
        $tipoId      = (int)($_GET['tipo'] ?? 0);
        $operacionId = (int)($_GET['operacion_id'] ?? 0);
        $origen      = strtoupper(trim((string)($_GET['origen'] ?? ''))); // ''|'OPERACION'|'CONTENEDOR'
        $soloActivos = isset($_GET['solo_activos']) ? ((int)$_GET['solo_activos'] === 1) : true;

        $m = strtoupper($monedaRaw);
        if ($m !== 'PESOS' && $m !== 'DLLS') {
            $m = '';
        }

        $filtros = [
            'operacion_id'       => $operacionId,
            'buscar'             => $buscar,
            'moneda'             => $m,
            'tipo_movimiento_id' => $tipoId,
            'origen'             => $origen,
            'solo_activos'       => $soloActivos,

        ];
        $abonosDetalle = $this->model->abonosCombinadosDetallado($filtros);
        try {
            $totales        = $this->model->totalesCostosCombinados($filtros);
            $totalesDetalle = $this->model->totalesCostosCombinadosDetallado($filtros);
            $total          = $this->model->contarCostosCombinados($filtros);

            $totalPages = (int)ceil($total / max(1, $perPage));
            if ($totalPages > 0 && $page > $totalPages) {
                $page = $totalPages;
            }
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
                    'total_operacion'    => 0,
                    'total_contenedores' => 0,
                    'total_general'      => 0
                ],
                'totales_detalle' => is_array($totalesDetalle) ? $totalesDetalle : [
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
            $rowId        = (int)($_POST['row_id'] ?? 0);
            $operacionId  = (int)($_POST['operacion_id'] ?? 0);
            $tipoId       = (int)($_POST['tipo_movimiento_id'] ?? 0);
            $monto        = (float)($_POST['monto'] ?? 0);
            // $moneda ya NO lo tomamos del POST; lo derivamos del catálogo:
            $comentario   = trim((string)($_POST['comentario'] ?? ''));

            if ($operacionId <= 0 && $rowId <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Falta operación']);
                return;
            }
            if ($tipoId      <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Selecciona un tipo de movimiento']);
                return;
            }
            if ($monto       <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Monto inválido']);
                return;
            }

            // 🟢 Consulta el tipo de movimiento para obtener moneda y si es gasto|abono:
            $tm = $this->model->obtenerTipoMovimiento($tipoId);
            if (!$tm) {
                echo json_encode(['status' => 'warning', 'message' => 'Tipo de movimiento inválido']);
                return;
            }

            $monedaCat = strtoupper((string)($tm['moneda'] ?? ''));
            $tipoDinero = strtolower((string)($tm['tipo'] ?? '')); // 'gasto' | 'abono'
            if ($monedaCat !== 'PESOS' && $monedaCat !== 'DLLS') {
                echo json_encode(['status' => 'warning', 'message' => 'Moneda del tipo de movimiento inválida']);
                return;
            }

            if ($rowId > 0) {
                // === ACTUALIZAR ===
                $prev = $this->model->obtenerCostoOperacion($rowId);
                $opIdForLog = $operacionId > 0 ? $operacionId : (int)($prev['operacion_id'] ?? 0);

                $ok = $this->model->actualizarCostoOperacion($rowId, [
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    // OJO: en tu modelo no se actualiza 'moneda' porque se deriva del tipo; no se guarda aquí
                    'comentario'         => $comentario,
                ]);
                if (!$ok) {
                    echo json_encode(['status' => 'error', 'message' => 'No se actualizó el registro']);
                    return;
                }

                // LOG (usa “Movimiento” y agrega si fue gasto/abono)
                $desc = $this->makeDesc('Movimiento de operación actualizado', [
                    'costo_id'   => $rowId,
                    'tipo_id'    => $tipoId,
                    'tipo'       => $tipoDinero,     // gasto|abono
                    'monto'      => $monto,
                    'moneda'     => $monedaCat,
                    'coment'     => ($comentario !== '' ? mb_substr($comentario, 0, 60) . '…' : '')
                ]);
                $this->logOp($opIdForLog, 'actualizacion', $desc);

                echo json_encode(['status' => 'success', 'message' => 'Actualizado']);
                return;
            } else {
                // === CREAR ===
                if ($operacionId <= 0) {
                    echo json_encode(['status' => 'warning', 'message' => 'Falta operación']);
                    return;
                }

                $newId = $this->model->insertarCostoOperacion([
                    'operacion_id'       => $operacionId,
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    // moneda no se guarda en esa tabla; la infieres siempre de tipos_movimiento
                    'comentario'         => $comentario,
                ]);
                if ($newId <= 0) {
                    echo json_encode(['status' => 'error', 'message' => 'No se creó el registro']);
                    return;
                }

                // LOG (usa “Movimiento” y agrega si fue gasto/abono)
                $desc = $this->makeDesc('Movimiento de operación creado', [
                    'costo_id'   => $newId,
                    'tipo_id'    => $tipoId,
                    'tipo'       => $tipoDinero,
                    'monto'      => $monto,
                    'moneda'     => $monedaCat,
                    'coment'     => ($comentario !== '' ? mb_substr($comentario, 0, 60) . '…' : '')
                ]);
                $this->logOp($operacionId, 'creacion', $desc);

                echo json_encode(['status' => 'success', 'message' => 'Creado', 'id' => $newId]);
                return;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }


    // (Opcional) para prefilling exacto desde BD si no quieres usar dataset del <tr>
    public function obtenerUno()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ID inválido']);
            return;
        }
        try {
            $row = $this->model->obtenerCostoOperacion($id);
            if (!$row) {
                echo json_encode(['status' => 'warning', 'message' => 'No encontrado']);
                return;
            }
            echo json_encode(['status' => 'success', 'data' => $row], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function desactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ID inválido']);
            return;
        }
        try {
            // snapshot para log
            $row = $this->model->obtenerCostoOperacion($id);
            $ok  = $this->model->desactivarCostoOperacion($id);

            if ($ok) {
                $opId = (int)($row['operacion_id'] ?? 0);
                $desc = $this->makeDesc('Costo de operación desactivado', [
                    'costo_id' => $id,
                    'tipo_id'  => $row['tipo_movimiento_id'] ?? '-',
                    'monto'    => $row['monto'] ?? '-',
                    'moneda'   => $row['moneda'] ?? '-'
                ]);
                $this->logOp($opId, 'cancelacion', $desc);
            }

            echo json_encode($ok ? ['status' => 'success'] : ['status' => 'error', 'message' => 'No se desactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ID inválido']);
            return;
        }
        try {
            // snapshot para log (para conocer operacion_id)
            $row = $this->model->obtenerCostoOperacion($id);
            $ok  = $this->model->reactivarCostoOperacion($id);

            if ($ok) {
                $opId = (int)($row['operacion_id'] ?? 0);
                $desc = $this->makeDesc('Costo de operación reactivado', [
                    'costo_id' => $id,
                    'tipo_id'  => $row['tipo_movimiento_id'] ?? '-'
                ]);
                // No hay 'reactivacion' en tu ENUM; usamos 'actualizacion'
                $this->logOp($opId, 'actualizacion', $desc);
            }

            echo json_encode($ok ? ['status' => 'success'] : ['status' => 'error', 'message' => 'No se reactivó']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
