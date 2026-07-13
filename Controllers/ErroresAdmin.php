<?php
class ErroresAdmin extends Controller
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
        $data['title'] = 'Solucionar Errores';
        $data['tipos_error'] = $this->model->getTiposError();
        $data['modulos_error'] = $this->model->getModulosError();

        $this->views->getView('admin/errores', "admin", $data);
    }

    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $estatus  = isset($_GET['estatus']) ? trim($_GET['estatus']) : '';
            $modulo   = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
            $tipo     = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
            $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

            $data = $this->model->listar($estatus, $modulo, $tipo, $busqueda);

            echo json_encode([
                'status' => true,
                'data'   => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Error al listar los reportes',
                'error'  => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function getReporte()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idReporte = isset($_GET['id_reporte']) ? (int)$_GET['id_reporte'] : 0;

            if ($idReporte <= 0) {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'ID de reporte inválido'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $reporte = $this->model->getReporte($idReporte);

            if (empty($reporte)) {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'Reporte no encontrado'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'status' => true,
                'data'   => $reporte
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Error al obtener el reporte',
                'error'  => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function actualizarEstatus()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'Método no permitido'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $idReporte = isset($_POST['id_reporte']) ? (int)$_POST['id_reporte'] : 0;
            $estatus   = isset($_POST['estatus_nuevo']) ? (int)$_POST['estatus_nuevo'] : -1;
            $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

            if ($idReporte <= 0) {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'ID de reporte inválido'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!in_array($estatus, [1, 2], true)) {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'Estatus inválido'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($usuarioId <= 0) {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'No se pudo identificar al usuario actual'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $ok = $this->model->actualizarEstatus($idReporte, $estatus, $usuarioId);

            if ($ok == 1) {
                echo json_encode([
                    'status' => true,
                    'msg'    => $estatus == 1
                        ? 'Reporte marcado como resuelto correctamente'
                        : 'Reporte marcado como rechazado correctamente'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => false,
                    'msg'    => 'No se pudo actualizar el estatus del reporte'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Error al actualizar el estatus',
                'error'  => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
