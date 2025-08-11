<?php
class Ciudades extends Controller
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
        $data['title'] = 'Ciudades';
        $data['estados'] = $this->model->listarEstados();

        $this->views->getView('admin/Ciudades', "index", $data);
    }

    public function listar(){
        $data=$this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function registrar()
    {
        $id   = $_POST['id_ciudad'] ?? '';
        $nombre = trim($_POST['nombre_ciudad'] ?? '');
        $estado = $_POST['estado_id'] ?? '';
        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
            return;
        }
        if ($estado === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El estado es obligatorio']);
            return;
        }
        if ($id === '') {
            // validar duplicado
            $existe = $this->model->existe($nombre);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe una ciudad con ese nombre']);
                return;
            }
            $res = $this->model->registrar($nombre,$estado);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Ciudad registrada' : 'Error al registrar'
            ]);
        } else {
            $res = $this->model->actualizar($id, $nombre,$estado);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Ciudad actualizada' : 'Error al actualizar'
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
            'msg'    => $res ? 'Ciudad eliminada' : 'Error al eliminar'
        ]);
    }

    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function filtrar()
{
    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    $estadoId = isset($_GET['estado_id']) ? trim($_GET['estado_id']) : '';
    $data = $this->model->filtrar($term, $estadoId);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}
    

}