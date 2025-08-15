<?php
class Contenedores_fisicos extends Controller
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
        $data['title'] = 'Contenedores_fisicos';

        $this->views->getView('admin/Contenedores_fisicos', "index", $data);
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

        // Validaciones básicas
        if ($nombre === '' ) {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }

        // ¿Existe ya una bodega con el mismo nombre en la misma ciudad?
        $existe = $this->model->existeNombre($nombre);
        if ($existe) { 
        echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un Ferro con ese nombre ']); 
        die();
        }

        // Registrar nueva
        $nuevoId = $this->model->registrar($nombre);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar el Contenedor Fisico']);
             die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Contenedor registrado correctamente']);
         die();
    }
        public function editar($id)
        {
            $data = $this->model->obtenerContenedorFisico($id);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            die();
        }


    public function actualizar()
    {
        $id         = (int)($_POST['id'] ?? 0);
        $nombre     = trim($_POST['numero_ferro_fisico'] ?? ''); 
        

        if ( $nombre === '' ) {
            echo json_encode(['status'=>'warning','msg'=>'Campos obligatorios faltantes']); die();
        }

 

        $ok = $this->model->actualizar($id, $nombre);
        if (!$ok) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo actualizar el contenedor']); die();
        }

        echo json_encode(['status'=>'success','msg'=>'contenedor actualizada correctamente']); die();
    }


    public function eliminar($id){
        $res =$this->model->eliminar($id);       
        
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Contenedor Fisico Eliminado' : 'Error al eliminar'
        ]);
        die();
    }
}