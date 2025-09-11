<?php
class Clientes extends Controller
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
        $data['title'] = 'Clientes';

        $this->views->getView('admin/Clientes', "index", $data);
    }
 
    public function listar(){
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function registrar()
    {
        $id         = $_POST['id_cliente'] ?? '';
        $nombre     = trim($_POST['nombre'] ?? '');
        
        $correo     = trim($_POST['correo'] ?? '');
        $telefono   = trim($_POST['telefono'] ?? ''); 

        if ($nombre === ''  || $correo === '' || $telefono === ''  ) {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Correo no válido']); die();
        }

        if ($id === '') {
            // ALTA
            if ($this->model->existeCorreo($correo)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un cliente con ese correo']); die();
            }
            $nuevoId = $this->model->registrarCliente($nombre,  $telefono, $correo);
            if (!$nuevoId) { echo json_encode(['status' => 'error', 'msg' => 'Error al registrar cliente']); die(); }
            echo json_encode(['status' => 'success', 'msg' => 'Cliente registrado correctamente']); die();
        } else {
            // EDICIÓN
            if ($this->model->existeCorreoOtro($correo, $id)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Otro cliente ya usa ese correo']); die();
            }
            $ok = $this->model->actualizarCliente($nombre, $telefono, $correo, $id);
            if (!$ok) { echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar cliente']); die(); }
            echo json_encode(['status' => 'success', 'msg' => 'Cliente actualizado correctamente']); die();
        }
    }

        public function editar($id)
    {
        $data = $this->model->obtenerCliente($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function eliminar($id){
        $res =$this->model->eliminar($id);       
        
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Cliente eliminado' : 'Error al eliminar'
        ]);
        die();
    }

    public function buscar()
{
    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    if ($term === '') { 
        echo json_encode([]); 
        die(); 
    }
    $data = $this->model->buscar($term);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}

     

}