<?php
class Puertos extends Controller
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
        $data['title'] = 'Puertos';
        $data['ciudades'] = $this->model->listarCiudades();

        $this->views->getView('admin/puertos', "index", $data);
    }

    public function listar(){
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

public function registrar()
{
    $id     = $_POST['id_puerto'] ?? '';
    $nombre = trim($_POST['nombre_puerto'] ?? '');
    $ciudad = $_POST['ciudad_id'] ?? '';

    if ($nombre === '') {
        echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
        return;
    }
    if ($ciudad === '') {
        echo json_encode(['status' => 'warning', 'msg' => 'La ciudad es obligatoria']);
        return;
    }

    if ($id === '') {
        // validar duplicado por (nombre, ciudad)
        $existe = $this->model->existePorNombreCiudad($nombre, $ciudad);
        if ($existe) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un puerto con ese nombre en esa ciudad']);
            return;
        }
        $res = $this->model->registrar($nombre, $ciudad);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Puerto registrado' : 'Error al registrar'
        ]);
    } else {
        // evitar duplicado al actualizar
        $existeOtro = $this->model->existeOtro($nombre, $ciudad, $id);
        if ($existeOtro) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro puerto con ese nombre en esa ciudad']);
            return;
        }
        $res = $this->model->actualizar($id, $nombre, $ciudad);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Puerto actualizado' : 'Error al actualizar'
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
            'msg'    => $res ? 'Puerto eliminado' : 'Error al eliminar'
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
    $term      = isset($_GET['term']) ? trim($_GET['term']) : '';
    $ciudadId  = isset($_GET['ciudad_id']) ? trim($_GET['ciudad_id']) : '';  
    $data = $this->model->filtrar($term, $ciudadId);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}


}