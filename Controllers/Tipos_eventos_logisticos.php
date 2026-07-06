<?php
class Tipos_eventos_logisticos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2]);
    }

    public function index()
    {
        $data['title'] = 'Tipos de Eventos Logísticos';
        // Para llenar el <select> del modal con PHP (estatus=1)
        $data['tipos_operacion'] = $this->model->listarTiposOperacionActivas();

        $this->views->getView('admin/tipos_eventos_logisticos', "index", $data);
    }

    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function registrar()
    {
        $id   = $_POST['id_tipo_evento'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        // puede venir vacío si quieres permitir "global"
        $tipo_operacion_id = $_POST['tipo_operacion_id'] ?? '';

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
            return;
        }

        // Si quieres forzar selección, descomenta:
        if ($tipo_operacion_id === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El tipo de operación es obligatorio']);
            return;
        }

        if ($id === '') {
            // validar duplicado por nombre + tipo
            $existe = $this->model->existe($nombre, $tipo_operacion_id);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo con ese nombre para ese tipo de operación']);
                return;
            }
            // normaliza vacío a NULL si tu columna lo permite
            $tipo_operacion_id = ($tipo_operacion_id === '') ? null : $tipo_operacion_id;

            $res = $this->model->registrar($nombre, $tipo_operacion_id);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de evento registrado' : 'Error al registrar'
            ]);
        } else {
            $existe = $this->model->existe($nombre, $tipo_operacion_id, $id);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo con ese nombre para ese tipo de operación']);
                return;
            }
            $tipo_operacion_id = ($tipo_operacion_id === '') ? null : $tipo_operacion_id;

            $res = $this->model->actualizar($id, $nombre, $tipo_operacion_id);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de evento actualizado' : 'Error al actualizar'
            ]);
        }
    }

    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Tipo de evento eliminado' : 'Error al eliminar'
        ]);
    }

    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // (Opcional) Si prefieres llenar el select por AJAX:
    public function tipos_operacion()
    {
        $data = $this->model->listarTiposOperacionActivas();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
