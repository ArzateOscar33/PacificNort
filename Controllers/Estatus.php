<?php
class Estatus extends Controller
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
        $data['title'] = 'Estatus';

        $this->views->getView('admin/Estatus', "index", $data);
    }

    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function registrar()
    {
        $id = $_POST['id_estatus'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $color_hex = trim($_POST['color_hex'] ?? '#807A79');

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
            return;
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color_hex)) {
            $color_hex = '#807A79';
        }

        if ($id === '') {
            $existe = $this->model->existe($nombre);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un estatus con ese nombre']);
                return;
            }

            $res = $this->model->registrar($nombre, $color_hex);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg' => $res ? 'Estatus registrado' : 'Error al registrar'
            ]);
        } else {
            $res = $this->model->actualizar($id, $nombre, $color_hex);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg' => $res ? 'Estatus actualizado' : 'Error al actualizar'
            ]);
        }
    }
    public function editar(int $id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Estatus eliminado' : 'Error al eliminar'
        ]);
    }

    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
