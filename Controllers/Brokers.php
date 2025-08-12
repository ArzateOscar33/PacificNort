<?php
class Brokers extends Controller
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
        $data['title'] = 'Brokers';

        $this->views->getView('admin/Brokers', "index", $data);
    }
    public function listar(){
        $data=$this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function registrar()
    {
        // Campos desde el form
        $nombre     = trim($_POST['nombre']     ?? '');
        $contacto  = trim($_POST['contacto']  ?? ''); 

        // Validaciones básicas
        if ($nombre === '' || $contacto === ''   ) {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }

        // ¿Existe ya una bodega con el mismo nombre en la misma ciudad?
        $existe = $this->model->existeNombre($nombre);
        if ($existe) { 
        echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un Broker con ese nombre ']); 
        die();
        }

        // Registrar nueva
        $nuevoId = $this->model->registrar($nombre, $contacto);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar el broker']);
             die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Broker registrado correctamente']);
         die();
    }

    public function editar($id)
    {
        $data = $this->model->obtenerBroker($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
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

    public function actualizar()
    {
        $id         = (int)($_POST['id'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');
        $contacto  = trim($_POST['contacto'] ?? ''); 

        if ($id <= 0 || $nombre === '' || $contacto === '') {
            echo json_encode(['status'=>'warning','msg'=>'Campos obligatorios faltantes']); die();
        }

 

        $ok = $this->model->actualizar($id, $nombre, $contacto);
        if (!$ok) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo actualizar el broker']); die();
        }

        echo json_encode(['status'=>'success','msg'=>'Bodega actualizada correctamente']); die();
    }

    public function eliminar($id){
        $res =$this->model->eliminar($id);       
        
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Broker Eliminado' : 'Error al eliminar'
        ]);
        die();
    }

    }

 