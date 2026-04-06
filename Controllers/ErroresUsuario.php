<?php
class ErroresUsuario extends Controller
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
        // $this->requireAdmin();
    }

    public function index()
    {
        $data['title'] = 'Reportar Errores';
        $data['tipos_error'] = $this->model->getTiposError();
        $data['modulos_error'] = $this->model->getModulosError();

        $this->views->getView('admin/Errores', "index", $data);
    }

    public function registrar()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => false,
                'msg' => 'Método no permitido'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tipoErrorId    = isset($_POST['tipo_error_id']) ? (int) $_POST['tipo_error_id'] : 0;
        $moduloId       = isset($_POST['modulo_id']) ? (int) $_POST['modulo_id'] : 0;
        $descripcion    = isset($_POST['description']) ? trim($_POST['description']) : '';
        $valorPropuesto = isset($_POST['proposed_value']) ? trim($_POST['proposed_value']) : '';
        $razonError     = isset($_POST['reason']) ? trim($_POST['reason']) : '';

        $reportadoPor = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;

        if ($tipoErrorId <= 0) {
            echo json_encode([
                'status' => false,
                'msg' => 'Seleccione un tipo de error'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($moduloId <= 0) {
            echo json_encode([
                'status' => false,
                'msg' => 'Seleccione un módulo'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($descripcion === '') {
            echo json_encode([
                'status' => false,
                'msg' => 'La descripción del error es obligatoria'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($reportadoPor <= 0) {
            echo json_encode([
                'status' => false,
                'msg' => 'No se pudo identificar al usuario que reporta'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $valorPropuesto = ($valorPropuesto !== '') ? $valorPropuesto : null;
        $razonError     = ($razonError !== '') ? $razonError : null;

        $idReporte = $this->model->registrarReporte(
            $tipoErrorId,
            $moduloId,
            $descripcion,
            $valorPropuesto,
            $razonError,
            $reportadoPor
        );

        if ($idReporte) {
            echo json_encode([
                'status' => true,
                'msg' => 'Reporte registrado correctamente',
                'id_reporte' => $idReporte
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => false,
                'msg' => 'No se pudo registrar el reporte'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
