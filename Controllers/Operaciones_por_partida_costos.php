<?php

class Operaciones_por_partida_costos extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Solo usuarios internos, no cliente
        $this->requireRoles([1, 11, 2]);
    }

    /**
     * GET /operaciones_por_partida_costo_contenedor/listarPaginado
     * Query:
     *  - page, perPage, buscar, moneda(PESOS|DLLS|''), tipo / tipo_movimiento_id
     *  - factura_id
     *  - solo_activos (1|0)
     */
    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page        = (int)($_GET['page'] ?? 1);
        $perPage     = (int)($_GET['perPage'] ?? 10);
        $buscar      = trim((string)($_GET['buscar'] ?? ''));
        $monedaRaw   = trim((string)($_GET['moneda'] ?? ''));
        $tipoId      = (int)($_GET['tipo'] ?? ($_GET['tipo_movimiento_id'] ?? 0));
        $facturaId   = (int)($_GET['factura_id'] ?? 0);
        $soloActivos = isset($_GET['solo_activos']) ? ((int)$_GET['solo_activos'] === 1) : true;

        $m = strtoupper($monedaRaw);
        if ($m !== 'PESOS' && $m !== 'DLLS') {
            $m = '';
        }

        $filtros = [
            'factura_id'          => $facturaId,
            'buscar'              => $buscar,
            'moneda'              => $m,
            'tipo_movimiento_id'  => $tipoId,
            'solo_activos'        => $soloActivos,
        ];

        try {
            $abonosDetalle = $this->model->abonosCombinadosDetallado($filtros);
            $totales       = $this->model->totalesCostosCombinados($filtros);
            $totalesDet    = $this->model->totalesCostosCombinadosDetallado($filtros);
            $total         = $this->model->contarCostosCombinados($filtros);

            $totalPages = (int)ceil($total / max(1, $perPage));
            if ($totalPages > 0 && $page > $totalPages) {
                $page = $totalPages;
            }
            if ($page < 1) {
                $page = 1;
            }

            $rows = $this->model->listarCostosCombinadosPaginado($page, $perPage, $filtros);

            echo json_encode([
                'status' => 'success',
                'meta'   => [
                    'page'       => $page,
                    'perPage'    => $perPage,
                    'total'      => (int)$total,
                    'totalPages' => $totalPages,
                    'fuente'     => 'PARTIDA',
                    'factura_id' => $facturaId
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
                'data' => is_array($rows) ? $rows : []
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar costos: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /operaciones_por_partida_costo_contenedor/buscarOperaciones?term=xxx
     * NOTA: se conserva el nombre del endpoint por compatibilidad con el front,
     * pero realmente busca facturas de operaciones por partida.
     *
     * Respuesta:
     * [{ id, numero_operacion, cliente, proveedor, bodega, fuente }]
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
                'message' => 'Error al buscar facturas: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /operaciones_por_partida_costo_contenedor/tiposMovimiento
     * Devuelve tipos de movimiento activos del catálogo compartido.
     */
    public function tiposMovimiento()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->obtenerTiposMovimientoActivos();
            echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /operaciones_por_partida_costo_contenedor/guardar
     * Body:
     *  - row_id (0 crea / >0 actualiza)
     *  - factura_id (obligatorio al crear)
     *  - tipo_movimiento_id, monto, comentario
     *  - costosContenedoresPagado (0|1)
     */
    public function guardar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rowId      = (int)($_POST['row_id'] ?? 0);
            $facturaId  = (int)($_POST['factura_id'] ?? 0);
            $tipoId     = (int)($_POST['tipo_movimiento_id'] ?? 0);
            $monto      = (float)($_POST['monto'] ?? 0);
            $comentario = trim((string)($_POST['comentario'] ?? ''));

            $pagado = isset($_POST['costosContenedoresPagado']) ? (int)$_POST['costosContenedoresPagado'] : 0;
            $pagado = ($pagado === 1) ? 1 : 0;

            if ($facturaId <= 0 && $rowId <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Falta factura'], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($tipoId <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Selecciona un tipo de movimiento'], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($monto <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Monto inválido'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $tm = $this->model->obtenerTipoMovimiento($tipoId);
            if (!$tm) {
                echo json_encode(['status' => 'warning', 'message' => 'Tipo de movimiento inválido'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $monedaCat  = strtoupper((string)($tm['moneda'] ?? ''));
            if ($monedaCat !== 'PESOS' && $monedaCat !== 'DLLS') {
                echo json_encode(['status' => 'warning', 'message' => 'Moneda del tipo de movimiento inválida'], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($rowId > 0) {
                // ===== ACTUALIZAR =====
                $prev = $this->model->obtenerCostoOperacionCombinado($rowId);
                if (!$prev) {
                    echo json_encode(['status' => 'warning', 'message' => 'Registro no encontrado'], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $ok = $this->model->actualizarCostoOperacionCombinado($rowId, [
                    'tipo_movimiento_id' => $tipoId,
                    'monto'              => $monto,
                    'comentario'         => $comentario,
                    'pagado'             => $pagado,
                ]);

                if (!$ok) {
                    echo json_encode(['status' => 'error', 'message' => 'No se actualizó el registro'], JSON_UNESCAPED_UNICODE);
                    return;
                }

                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Actualizado'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // ===== CREAR =====
            if ($facturaId <= 0) {
                echo json_encode(['status' => 'warning', 'message' => 'Falta factura'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $factura = $this->model->obtenerFacturaPartida($facturaId);
            if (!$factura) {
                echo json_encode(['status' => 'warning', 'message' => 'Factura no encontrada'], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ((int)($factura['estatus'] ?? 0) !== 1) {
                echo json_encode(['status' => 'warning', 'message' => 'La factura no está activa'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $newId = $this->model->insertarCostoOperacionCombinado([
                'factura_id'          => $facturaId,
                'tipo_movimiento_id'  => $tipoId,
                'monto'               => $monto,
                'comentario'          => $comentario,
                'pagado'              => $pagado,
            ]);

            if ($newId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'No se creó el registro'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Creado',
                'id'      => $newId
            ], JSON_UNESCAPED_UNICODE);
            return;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /operaciones_por_partida_costo_contenedor/obtenerUno?id=XX
     */
    public function obtenerUno()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id);
            if (!$row) {
                echo json_encode(['status' => 'warning', 'message' => 'No encontrado'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $row
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /operaciones_por_partida_costo_contenedor/desactivarCostoOperacion
     * Body: id
     */
    public function desactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id);
            if (!$row) {
                echo json_encode(['status' => 'warning', 'message' => 'Registro no encontrado'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $ok = $this->model->desactivarCostoOperacionCombinado($id);

            echo json_encode(
                $ok
                    ? ['status' => 'success']
                    : ['status' => 'error', 'message' => 'No se desactivó'],
                JSON_UNESCAPED_UNICODE
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /operaciones_por_partida_costo_contenedor/reactivarCostoOperacion
     * Body: id
     */
    public function reactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id);
            if (!$row) {
                echo json_encode(['status' => 'warning', 'message' => 'Registro no encontrado'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $ok = $this->model->reactivarCostoOperacionCombinado($id);

            echo json_encode(
                $ok
                    ? ['status' => 'success']
                    : ['status' => 'error', 'message' => 'No se reactivó'],
                JSON_UNESCAPED_UNICODE
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /operaciones_por_partida_costo_contenedor/contenedorLigado?factura_id=XX
     */
    public function contenedorLigado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $facturaId = (int)($_GET['factura_id'] ?? 0);
            if ($facturaId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Falta factura'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $row = $this->model->obtenerContenedorLigado([
                'factura_id' => $facturaId
            ]);

            if (!$row) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Sin contenedor ligado'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $row
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
