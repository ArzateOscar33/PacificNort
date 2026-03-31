<?php
class BitacoraOpPartida extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        //$this->validarSesionInactividad();
        //$this->validarSesionUnica();
        $this->requireAdmin();
    }


    public function index()
    {
        $data['title'] = 'Bitácora de Operaciones';
        $this->views->getView('admin/bitacora', "bitacoraOpPartida", $data);
    }

    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // ===== Filtros =====
            $usuario     = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
            $entidad     = isset($_GET['entidad']) ? trim($_GET['entidad']) : '';
            $modulo      = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
            $accion      = isset($_GET['accion']) ? trim($_GET['accion']) : '';
            $fechaDesde  = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
            $fechaHasta  = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

            // ===== Paginación =====
            $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;

            if ($page < 1) $page = 1;
            if ($perPage < 1) $perPage = 10;

            $offset = ($page - 1) * $perPage;

            $filtrosBase = [
                'usuario'     => $usuario !== '' ? $usuario : null,
                'entidad'     => $entidad !== '' ? $entidad : null,
                'modulo'      => $modulo !== '' ? $modulo : null,
                'accion'      => $accion !== '' ? $accion : null,
                'fecha_desde' => $fechaDesde !== '' ? $fechaDesde : null,
                'fecha_hasta' => $fechaHasta !== '' ? $fechaHasta : null,
            ];

            $totalRegistros = (int)$this->model->contarLogs($filtrosBase);

            $filtrosLista = $filtrosBase;
            $filtrosLista['limit']  = $perPage;
            $filtrosLista['offset'] = $offset;

            $rows = $this->model->obtenerLogs($filtrosLista);

            if (!is_array($rows)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No fue posible obtener la bitácora.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $totalPages = $totalRegistros > 0 ? (int)ceil($totalRegistros / $perPage) : 1;
            $from = $totalRegistros > 0 ? ($offset + 1) : 0;
            $to   = $offset + count($rows);

            echo json_encode([
                'success'    => true,
                'rows'       => $rows,
                'total'      => $totalRegistros,
                'page'       => $page,
                'perPage'    => $perPage,
                'totalPages' => $totalPages,
                'from'       => $from,
                'to'         => $to
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Error en BitacoraOpPartida::listar -> ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Ocurrió un error al listar la bitácora.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
