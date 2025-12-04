<?php
class Bitacora extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Bitácora de Operaciones';
        $this->views->getView('admin/bitacora', "index", $data);
    }

    /**
     * Listado de logs con filtros y paginación.
     * Responde JSON para ser consumido por JS.
     *
     * GET /Bitacora/listar?usuario=...&operacion=...&accion=...&
     *     fecha_desde=YYYY-MM-DD&fecha_hasta=YYYY-MM-DD&page=1&perPage=10
     */
    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // === Filtros de texto ===
            $usuario     = isset($_GET['usuario'])   ? trim($_GET['usuario'])   : '';
            $operacion   = isset($_GET['operacion']) ? trim($_GET['operacion']) : '';
            $accion      = isset($_GET['accion'])    ? trim($_GET['accion'])    : '';

            // === Filtros de fecha ===
            $fechaDesde  = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
            $fechaHasta  = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

            // Normalizar a null si vienen vacíos
            $usuario    = ($usuario   !== '') ? $usuario   : null;
            $operacion  = ($operacion !== '') ? $operacion : null;
            $accion     = ($accion    !== '') ? $accion    : null;
            $fechaDesde = ($fechaDesde !== '') ? $fechaDesde : null;
            $fechaHasta = ($fechaHasta !== '') ? $fechaHasta : null;

            // === Paginación ===
            $page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['perPage']) ? (int) $_GET['perPage'] : 10;

            if ($page < 1) {
                $page = 1;
            }
            if ($perPage < 1) {
                $perPage = 10;
            }

            $offset = ($page - 1) * $perPage;

            // Filtros base
            $filtrosBase = [
                'usuario'     => $usuario,
                'operacion'   => $operacion,
                'accion'      => $accion,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ];

            // 1) Total de registros
            $totalRegistros = $this->model->contarLogs($filtrosBase);

            // 2) Registros de la página actual
            $filtrosLista = $filtrosBase;
            $filtrosLista['limit']  = $perPage;
            $filtrosLista['offset'] = $offset;

            $rows = $this->model->obtenerLogs($filtrosLista);

            if (!is_array($rows)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al obtener registros de bitácora.',
                ]);
                return;
            }

            $totalPaginas = ($totalRegistros > 0)
                ? (int) ceil($totalRegistros / $perPage)
                : 1;

            $desde = ($totalRegistros > 0) ? ($offset + 1) : 0;
            $hasta = ($offset + count($rows));

            $respuesta = [
                'success'    => true,
                'rows'       => $rows,
                'total'      => $totalRegistros,
                'page'       => $page,
                'perPage'    => $perPage,
                'totalPages' => $totalPaginas,
                'from'       => $desde,
                'to'         => $hasta,
            ];

            echo json_encode($respuesta);
        } catch (Throwable $e) {
            error_log('Error en Bitacora::listar -> ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al listar la bitácora.',
            ]);
        }
    }
}
