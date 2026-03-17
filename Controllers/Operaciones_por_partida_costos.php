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
     * GET /Operaciones_por_partida_costos/listarPaginado
     * Query:
     *  - page, perPage, buscar, moneda(PESOS|DLLS|'')
     *  - tipo / tipo_movimiento_id
     *  - factura_id
     *  - contenedor_fisico_id
     *  - solo_activos (1|0)
     */
    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page               = max(1, (int)($_GET['page'] ?? 1));
        $perPage            = max(1, min(200, (int)($_GET['perPage'] ?? 10)));
        $buscar             = trim((string)($_GET['buscar'] ?? ''));
        $monedaRaw          = trim((string)($_GET['moneda'] ?? ''));
        $tipoId             = (int)($_GET['tipo_movimiento_id'] ?? ($_GET['tipo'] ?? 0));
        $facturaId          = (int)($_GET['factura_id'] ?? 0);
        $contenedorFisicoId = (int)($_GET['contenedor_fisico_id'] ?? 0);
        $soloActivos        = isset($_GET['solo_activos']) ? ((int)$_GET['solo_activos'] === 1) : true;

        $moneda = strtoupper($monedaRaw);
        if (!in_array($moneda, ['PESOS', 'DLLS'], true)) {
            $moneda = '';
        }

        $filtros = [
            'factura_id'           => $facturaId,
            'contenedor_fisico_id' => $contenedorFisicoId,
            'buscar'               => $buscar,
            'moneda'               => $moneda,
            'tipo_movimiento_id'   => $tipoId,
            'solo_activos'         => $soloActivos,
        ];

        try {
            $abonosDetalle = $this->model->abonosCombinadosDetallado($filtros);
            $totales       = $this->model->totalesCostosCombinados($filtros);
            $totalesDet    = $this->model->totalesCostosCombinadosDetallado($filtros);
            $total         = (int)$this->model->contarCostosCombinados($filtros);

            $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;

            if ($totalPages > 0 && $page > $totalPages) {
                $page = $totalPages;
            }

            $rows = $this->model->listarCostosCombinadosPaginado($page, $perPage, $filtros);

            echo json_encode([
                'status' => 'success',
                'meta'   => [
                    'page'                 => $page,
                    'perPage'              => $perPage,
                    'total'                => $total,
                    'totalPages'           => $totalPages,
                    'fuente'               => 'PARTIDA',
                    'factura_id'           => $facturaId,
                    'contenedor_fisico_id' => $contenedorFisicoId,
                ],
                'totales' => is_array($totales) ? $totales : [
                    'total_operacion'           => 0.0,
                    'total_contenedores'        => 0.0,
                    'total_general'             => 0.0,
                    'total_abonos_operacion'    => 0.0,
                    'total_abonos_contenedores' => 0.0,
                ],
                'totales_detalle' => is_array($totalesDet) ? $totalesDet : [
                    'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                    'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
                ],
                'abonos_detalle' => is_array($abonosDetalle) ? $abonosDetalle : [
                    'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                    'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
                ],
                'data' => is_array($rows) ? $rows : [],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar costos: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /Operaciones_por_partida_costos/buscarOperaciones?term=xxx
     * Busca facturas de operaciones por partida.
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
                'message' => 'Error al buscar facturas: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /Operaciones_por_partida_costos/tiposMovimiento
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
                'message' => 'Error al obtener tipos de movimiento: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /Operaciones_por_partida_costos/obtenerFerrosPorFactura?factura_id=XX
     * Devuelve los ferros realmente vinculados a la factura a través de:
     * detalle -> envio -> contenedor_fisico
     */
    public function obtenerFerrosPorFactura()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $facturaId = (int)($_GET['factura_id'] ?? 0);

            if ($facturaId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Falta factura',
                    'data'    => [],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $factura = $this->model->obtenerFacturaPartida($facturaId);
            if (!$factura) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Factura no encontrada',
                    'data'    => [],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ((int)($factura['estatus'] ?? 0) !== 1) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'La factura no está activa',
                    'data'    => [],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $rows = $this->model->obtenerFerrosPorFactura($facturaId) ?: [];

            echo json_encode([
                'status'  => 'success',
                'message' => empty($rows) ? 'La factura no tiene ferros vinculados' : 'OK',
                'factura' => [
                    'id_factura'      => (int)($factura['id_factura'] ?? 0),
                    'numero_factura'  => (string)($factura['numero_factura'] ?? ''),
                    'cliente'         => (string)($factura['cliente'] ?? ''),
                    'proveedor'       => (string)($factura['proveedor'] ?? ''),
                    'bodega'          => (string)($factura['bodega'] ?? ''),
                ],
                'data' => $rows,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al obtener ferros de la factura: ' . $e->getMessage(),
                'data'    => [],
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /Operaciones_por_partida_costos/guardar
     * Body:
     *  - row_id (0 crea / >0 actualiza)
     *  - factura_id
     *  - contenedor_fisico_id
     *  - tipo_movimiento_id
     *  - monto
     *  - comentario
     *  - costosContenedoresPagado (0|1)
     */
    public function guardar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rowId              = (int)($_POST['row_id'] ?? 0);
            $facturaId          = (int)($_POST['factura_id'] ?? 0);
            $contenedorFisicoId = (int)($_POST['contenedor_fisico_id'] ?? 0);
            $tipoId             = (int)($_POST['tipo_movimiento_id'] ?? 0);
            $monto              = (float)($_POST['monto'] ?? 0);
            $comentario         = trim((string)($_POST['comentario'] ?? ''));

            $pagado = isset($_POST['costosContenedoresPagado']) ? (int)$_POST['costosContenedoresPagado'] : 0;
            $pagado = ($pagado === 1) ? 1 : 0;

            if ($rowId <= 0 && $facturaId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Falta factura',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($tipoId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Selecciona un tipo de movimiento',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($monto <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Monto inválido',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $tm = $this->model->obtenerTipoMovimiento($tipoId);
            if (!$tm) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Tipo de movimiento inválido',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $monedaCat = strtoupper((string)($tm['moneda'] ?? ''));
            if (!in_array($monedaCat, ['PESOS', 'DLLS'], true)) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Moneda del tipo de movimiento inválida',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($rowId > 0) {
                // =========================
                // ACTUALIZAR
                // =========================
                $prev = $this->model->obtenerCostoOperacionCombinado($rowId);
                if (!$prev) {
                    echo json_encode([
                        'status'  => 'warning',
                        'message' => 'Registro no encontrado',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $facturaIdEdit = (int)($prev['factura_id'] ?? 0);

                if ($facturaIdEdit <= 0) {
                    echo json_encode([
                        'status'  => 'warning',
                        'message' => 'El registro no tiene factura válida',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                if ($contenedorFisicoId <= 0) {
                    $contenedorFisicoId = (int)($prev['contenedor_fisico_id'] ?? 0);
                }

                if ($contenedorFisicoId <= 0) {
                    echo json_encode([
                        'status'  => 'warning',
                        'message' => 'Falta ferro/caja',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $factura = $this->model->obtenerFacturaPartida($facturaIdEdit);
                if (!$factura) {
                    echo json_encode([
                        'status'  => 'warning',
                        'message' => 'Factura no encontrada',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                if ((int)($factura['estatus'] ?? 0) !== 1) {
                    echo json_encode([
                        'status'  => 'warning',
                        'message' => 'La factura no está activa',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                if (!$this->model->existeFerroEnFactura($facturaIdEdit, $contenedorFisicoId)) {
                    echo json_encode([
                        'status'  => 'warning',
                        'message' => 'El ferro/caja no pertenece a la factura seleccionada',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $ok = $this->model->actualizarCostoOperacionCombinado($rowId, [
                    'contenedor_fisico_id' => $contenedorFisicoId,
                    'tipo_movimiento_id'   => $tipoId,
                    'monto'                => $monto,
                    'comentario'           => $comentario,
                    'pagado'               => $pagado,
                ]);

                if (!$ok) {
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'No se actualizó el registro',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Actualizado',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // =========================
            // CREAR
            // =========================
            if ($facturaId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Falta factura',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($contenedorFisicoId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Selecciona un ferro/caja',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $factura = $this->model->obtenerFacturaPartida($facturaId);
            if (!$factura) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Factura no encontrada',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ((int)($factura['estatus'] ?? 0) !== 1) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'La factura no está activa',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!$this->model->existeFerroEnFactura($facturaId, $contenedorFisicoId)) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'El ferro/caja no pertenece a la factura seleccionada',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $newId = $this->model->insertarCostoOperacionCombinado([
                'factura_id'           => $facturaId,
                'contenedor_fisico_id' => $contenedorFisicoId,
                'tipo_movimiento_id'   => $tipoId,
                'monto'                => $monto,
                'comentario'           => $comentario,
                'pagado'               => $pagado,
            ]);

            if ($newId <= 0) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'No se creó el registro',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Creado',
                'id'      => $newId,
            ], JSON_UNESCAPED_UNICODE);
            return;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /Operaciones_por_partida_costos/obtenerUno?id=XX
     */
    public function obtenerUno()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode([
                'status'  => 'warning',
                'message' => 'ID inválido',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id);
            if (!$row) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'No encontrado',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $row,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al obtener el registro: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /Operaciones_por_partida_costos/desactivarCostoOperacion
     * Body: id
     */
    public function desactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode([
                'status'  => 'warning',
                'message' => 'ID inválido',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id);
            if (!$row) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Registro no encontrado',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $ok = $this->model->desactivarCostoOperacionCombinado($id);

            echo json_encode(
                $ok
                    ? ['status' => 'success', 'message' => 'Desactivado']
                    : ['status' => 'error', 'message' => 'No se desactivó'],
                JSON_UNESCAPED_UNICODE
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al desactivar: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST /Operaciones_por_partida_costos/reactivarCostoOperacion
     * Body: id
     */
    public function reactivarCostoOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode([
                'status'  => 'warning',
                'message' => 'ID inválido',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $row = $this->model->obtenerCostoOperacionCombinado($id);
            if (!$row) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Registro no encontrado',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $ok = $this->model->reactivarCostoOperacionCombinado($id);

            echo json_encode(
                $ok
                    ? ['status' => 'success', 'message' => 'Reactivado']
                    : ['status' => 'error', 'message' => 'No se reactivó'],
                JSON_UNESCAPED_UNICODE
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al reactivar: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /Operaciones_por_partida_costos/contenedorLigado?factura_id=XX
     * Compatibilidad temporal para JS viejo.
     */
    public function contenedorLigado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $facturaId = (int)($_GET['factura_id'] ?? 0);
            if ($facturaId <= 0) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Falta factura',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $factura = $this->model->obtenerFacturaPartida($facturaId);
            if (!$factura) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Factura no encontrada',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $row = $this->model->obtenerContenedorLigado([
                'factura_id' => $facturaId,
            ]);

            if (!$row) {
                echo json_encode([
                    'status'  => 'warning',
                    'message' => 'Sin contenedor ligado',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $row,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al obtener contenedor ligado: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
