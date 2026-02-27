<?php

class Operaciones_maritimo_ferro_costos_clientes extends Controller
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
    }

    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;

            $page = max(1, $page);
            $perPage = (int)$perPage;
            if ($perPage <= 0) $perPage = 25;

            // =========================
            // Normalización de params según tu VISTA (selects)
            // =========================

            // Cliente: 0 / '' => "Todos"
            $clienteId = 0;
            if (isset($_GET['cliente_id']) && $_GET['cliente_id'] !== '') {
                $clienteId = (int)$_GET['cliente_id'];
            }
            if ($clienteId <= 0 && isset($_GET['clienteId_cc']) && $_GET['clienteId_cc'] !== '') {
                $clienteId = (int)$_GET['clienteId_cc'];
            }

            // Broker
            $brokerId = 0;
            if (isset($_GET['broker_id']) && $_GET['broker_id'] !== '') {
                $brokerId = (int)$_GET['broker_id'];
            } elseif (isset($_GET['brokerId_cc']) && $_GET['brokerId_cc'] !== '') {
                $brokerId = (int)$_GET['brokerId_cc'];
            }

            // Transportista
            $transportistaId = 0;
            if (isset($_GET['transportista_id']) && $_GET['transportista_id'] !== '') {
                $transportistaId = (int)$_GET['transportista_id'];
            } elseif (isset($_GET['transportistaId_cc']) && $_GET['transportistaId_cc'] !== '') {
                $transportistaId = (int)$_GET['transportistaId_cc'];
            }

            // ✅ NUEVO: Categoría
            $categoriaId = 0;
            if (isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '') {
                $categoriaId = (int)$_GET['categoria_id'];
            } elseif (isset($_GET['categoriaId_cc']) && $_GET['categoriaId_cc'] !== '') {
                $categoriaId = (int)$_GET['categoriaId_cc'];
            } elseif (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
                // por si tu JS manda "categoria" directo
                $categoriaId = (int)$_GET['categoria'];
            }

            // Fechas
            $fechaInicio = $_GET['fecha_inicio'] ?? ($_GET['costosCliente_fechaInicio'] ?? '');
            $fechaFin    = $_GET['fecha_fin']    ?? ($_GET['costosCliente_fechaFin'] ?? '');

            // Estatus pago
            $pagado = $_GET['pagado'] ?? ($_GET['costosCliente_estatusPago'] ?? '');

            // Term
            $term = $_GET['term'] ?? ($_GET['costosCliente_term'] ?? '');

            $filters = [
                'cliente_id'       => $clienteId,
                'fecha_inicio'     => $fechaInicio,
                'fecha_fin'        => $fechaFin,
                'broker_id'        => $brokerId,
                'transportista_id' => $transportistaId,
                'pagado'           => $pagado,
                'term'             => $term,

                // ✅ NUEVO
                'categoria_id'     => $categoriaId,
            ];

            $res = $this->model->listarPaginado($filters, $page, $perPage);

            echo json_encode([
                'status' => 'success',
                'rows' => $res['rows'] ?? [],
                'meta' => $res['meta'] ?? [
                    'total_ops' => 0,
                    'total_conceptos' => 0,
                    'pendientes' => [],
                    'pagados' => [],
                ],
                'page' => $res['page'] ?? $page,
                'per_page' => $res['per_page'] ?? $perPage,
                'total' => $res['total'] ?? 0,
                'total_pages' => $res['total_pages'] ?? 1,
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Error al listar: ' . $e->getMessage(),
                'rows' => [],
                'meta' => [
                    'total_ops' => 0,
                    'total_conceptos' => 0,
                    'pendientes' => [],
                    'pagados' => [],
                ],
                'page' => 1,
                'per_page' => 25,
                'total' => 0,
                'total_pages' => 1,
            ]);
        }
    }
}
