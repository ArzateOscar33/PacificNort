<?php
class Puestos extends Controller
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
        $data['title'] = 'Puestos';

        $this->views->getView('admin/puestos', "index", $data);
    }
 
    public function listar()
    {
        $data = $this->model->getPuestos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function registrar()
    {
        $nombre = trim($_POST['nombrePuesto']);

        if (empty($nombre)) {
            $res = ['status' => false, 'msg' => 'El nombre es obligatorio'];
        } else {
            $insertado = $this->model->registrarPuesto($nombre);
            if ($insertado > 0) {
                $res = ['status' => true, 'msg' => 'Puesto registrado'];
            } else {
                $res = ['status' => false, 'msg' => 'Error al registrar'];
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function eliminar($id)
    {
        $res = $this->model->eliminarPuesto($id);
        if($res){
            $res = ['status' => true, 'msg' => 'Puesto eliminado'];
        }else{
            $res = ['status' => false, 'msg' => 'No se pudo eliminar'];
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->getPuesto($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function actualizar()
    {
        $id = $_POST['id'];
        $nombre = $_POST['nombrePuesto'];

        if (empty($nombre)) {
            $res = ['status' => false, 'msg' => 'El nombre es obligatorio'];
        } else {
            $existe = $this->model->existePuesto($nombre);

            if ($existe && $existe['id_puesto'] != $id) {
                $res = ['status' => false, 'msg' => 'Ya existe un puesto con ese nombre'];
            } else {
                $modificado = $this->model->modificarPuesto($id, $nombre);
                if ($modificado) {
                    $res = ['status' => true, 'msg' => 'Puesto actualizado'];
                } else {
                    $res = ['status' => false, 'msg' => 'No se pudo actualizar'];
                }
            }
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function buscar()
    {
        $termino = $_GET['term'] ?? ''; // Asegura que no lance error si no viene nada
        $data = $this->model->buscarPuesto($termino);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}