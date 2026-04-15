<?php
class Finanzas extends Controller
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

        // Solo usuarios internos
        $this->requireRoles([1, 11, 2]);
    }

    public function index($id = 0)
    {
        $data['title']            = 'Finanzas';
        $data['id_operacion']     = (int)$id; // compatibilidad, aunque ya no será clave
        $data['brokers']          = $this->model->getBrokers();
        $data['transportistas']   = $this->model->getTransportistas();
        $data['ciudades']         = $this->model->listarDestinos();
        $data['categoriasCostos'] = $this->model->listarCategoriasCostos();
        $data['clientes']         = $this->model->catalogoClientes();

        // Nuevo catálogo estático para filtro de origen
        $data['origenes'] = [
            ['id' => '',                   'nombre' => 'Todos'],
            ['id' => 'MARITIMO-FERRO',     'nombre' => 'Marítimo-Ferro'],
            ['id' => 'PARTIDA/DOMESTICO',  'nombre' => 'Partida / Doméstico'],
        ];

        $this->views->getView('admin/Finanzas', "ver", $data);
    }

    public function costos_logisticos()
    {
        $data['title'] = 'Costos de Operaciones';
        $this->views->getView('admin/finanzas', "costos_logisticos", $data);
    }

    public function costos_contenedor_operacion()
    {
        $data['title'] = 'Costos por Contenedor';
        $this->views->getView('admin/finanzas', "costos_contenedor_operacion", $data);
    }

    public function costos_operacion()
    {
        $data['title'] = 'Costos Operación';
        $this->views->getView('admin/finanzas', "costos_operacion", $data);
    }

    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;

            $page = max(1, $page);
            $perPage = (int)$perPage;
            if ($perPage <= 0) {
                $perPage = 25;
            }

            // =========================
            // Normalización de filtros
            // =========================

            // Cliente
            $clienteId = 0;
            if (isset($_GET['cliente_id']) && $_GET['cliente_id'] !== '') {
                $clienteId = (int)$_GET['cliente_id'];
            } elseif (isset($_GET['clienteId_cc']) && $_GET['clienteId_cc'] !== '') {
                $clienteId = (int)$_GET['clienteId_cc'];
            }

            // Broker
            $brokerId = 0;
            if (isset($_GET['broker_id']) && $_GET['broker_id'] !== '') {
                $brokerId = (int)$_GET['broker_id'];
            } elseif (isset($_GET['brokerId_cc']) && $_GET['brokerId_cc'] !== '') {
                $brokerId = (int)$_GET['brokerId_cc'];
            }

            // =====================================================
            // Transportista marítimo
            // Compatibilidad:
            // - transportista_maritimo_id
            // - transportista_id (legacy)
            // - transportistaId_cc (legacy)
            // =====================================================
            $transportistaMaritimoId = 0;
            if (isset($_GET['transportista_maritimo_id']) && $_GET['transportista_maritimo_id'] !== '') {
                $transportistaMaritimoId = (int)$_GET['transportista_maritimo_id'];
            } elseif (isset($_GET['transportistaMaritimoId_cc']) && $_GET['transportistaMaritimoId_cc'] !== '') {
                $transportistaMaritimoId = (int)$_GET['transportistaMaritimoId_cc'];
            } elseif (isset($_GET['transportista_id']) && $_GET['transportista_id'] !== '') {
                $transportistaMaritimoId = (int)$_GET['transportista_id'];
            } elseif (isset($_GET['transportistaId_cc']) && $_GET['transportistaId_cc'] !== '') {
                $transportistaMaritimoId = (int)$_GET['transportistaId_cc'];
            }

            // =====================================================
            // Transportista ferro/caja
            // Nuevos params:
            // - transportista_ferro_id
            // - transportistaFerroId_cc
            // =====================================================
            $transportistaFerroId = 0;
            if (isset($_GET['transportista_ferro_id']) && $_GET['transportista_ferro_id'] !== '') {
                $transportistaFerroId = (int)$_GET['transportista_ferro_id'];
            } elseif (isset($_GET['transportistaFerroId_cc']) && $_GET['transportistaFerroId_cc'] !== '') {
                $transportistaFerroId = (int)$_GET['transportistaFerroId_cc'];
            }

            // Categoría
            $categoriaId = 0;
            if (isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '') {
                $categoriaId = (int)$_GET['categoria_id'];
            } elseif (isset($_GET['categoriaId_cc']) && $_GET['categoriaId_cc'] !== '') {
                $categoriaId = (int)$_GET['categoriaId_cc'];
            } elseif (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
                $categoriaId = (int)$_GET['categoria'];
            }

            // Origen
            $origenTipo = '';
            if (isset($_GET['origen_tipo']) && $_GET['origen_tipo'] !== '') {
                $origenTipo = trim((string)$_GET['origen_tipo']);
            } elseif (isset($_GET['origenTipo_cc']) && $_GET['origenTipo_cc'] !== '') {
                $origenTipo = trim((string)$_GET['origenTipo_cc']);
            }

            // Fechas
            $fechaInicio = trim((string)($_GET['fecha_inicio'] ?? ($_GET['costosCliente_fechaInicio'] ?? '')));
            $fechaFin    = trim((string)($_GET['fecha_fin'] ?? ($_GET['costosCliente_fechaFin'] ?? '')));

            // Pagado
            $pagado = $_GET['pagado'] ?? ($_GET['costosCliente_estatusPago'] ?? '');

            // Búsqueda
            $term = trim((string)($_GET['term'] ?? ($_GET['costosCliente_term'] ?? '')));
            $factura = trim((string)($_GET['factura'] ?? ($_GET['factura_cc'] ?? '')));

            $filters = [
                'cliente_id'                => $clienteId,
                'fecha_inicio'              => $fechaInicio,
                'fecha_fin'                 => $fechaFin,
                'broker_id'                 => $brokerId,
                'factura'                   => $factura,

                // nuevos
                'transportista_maritimo_id' => $transportistaMaritimoId,
                'transportista_ferro_id'    => $transportistaFerroId,

                // legacy por compatibilidad
                'transportista_id'          => $transportistaMaritimoId,

                'categoria_id'              => $categoriaId,
                'origen_tipo'               => $origenTipo,
                'pagado'                    => $pagado,
                'term'                      => $term,
            ];

            $res = $this->model->listarPaginado($filters, $page, $perPage);

            echo json_encode([
                'status'      => 'success',
                'rows'        => $res['rows'] ?? [],
                'meta'        => $res['meta'] ?? [
                    'total_rows'      => 0,
                    'total_ops'       => 0,
                    'total_conceptos' => 0,
                    'pendientes'      => [],
                    'pagados'         => [],
                ],
                'page'        => $res['page'] ?? $page,
                'per_page'    => $res['per_page'] ?? $perPage,
                'total'       => $res['total'] ?? 0,
                'total_pages' => $res['total_pages'] ?? 1,
            ]);
            exit;
        } catch (Throwable $e) {
            echo json_encode([
                'status'      => 'error',
                'msg'         => 'Error al listar: ' . $e->getMessage(),
                'rows'        => [],
                'meta'        => [
                    'total_rows'      => 0,
                    'total_ops'       => 0,
                    'total_conceptos' => 0,
                    'pendientes'      => [],
                    'pagados'         => [],
                ],
                'page'        => 1,
                'per_page'    => 25,
                'total'       => 0,
                'total_pages' => 1,
            ]);
            exit;
        }
    }
    public function diagnostico()
    {
        header('Content-Type: application/json; charset=utf-8');

        $resultado = $this->model->diagnostico();

        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
