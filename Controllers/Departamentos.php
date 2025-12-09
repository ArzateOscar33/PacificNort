<?php
class Departamentos extends Controller
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
        $data['title'] = 'Departamentos';
        $this->views->getView('admin/departamentos', "index", $data);
    }

    public function listar()
    {
        $data = $this->model->getDepartamentos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

public function registrar()
{
    $nombre = trim($_POST['nombreDepartamento']);

    if (empty($nombre)) {
        $res = ['status' => false, 'msg' => 'El nombre es obligatorio'];
    } else {
        $existe = $this->model->existeDepartamento($nombre);
        if ($existe) {
            $res = ['status' => false, 'msg' => 'El departamento ya existe'];
        } else {
            $insertado = $this->model->registrarDepartamento($nombre);
            if ($insertado > 0) {
                $res = ['status' => true, 'msg' => 'Departamento registrado'];
            } else {
                $res = ['status' => false, 'msg' => 'Error al registrar'];
            }
        }
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

    public function eliminar($id)
    {
        $res = $this->model->eliminarDepartamento($id);
        if($res){
            $res = ['status' => true, 'msg' => 'Departamento eliminado'];
        }else{
            $res = ['status' => false, 'msg' => 'No se pudo eliminar'];
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->getDepartamento($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function actualizar()
    {
        $id = $_POST['id'];
        $nombre = $_POST['nombreDepartamento'];

        if (empty($nombre)) {
            $res = ['status' => false, 'msg' => 'El nombre es obligatorio'];
        } else {
            $existe = $this->model->existeDepartamento($nombre);

            if ($existe && $existe['id_departamento'] != $id) {
                $res = ['status' => false, 'msg' => 'Ya existe un departamento con ese nombre'];
            } else {
                $modificado = $this->model->modificarDepartamento($id, $nombre);
                if ($modificado) {
                    $res = ['status' => true, 'msg' => 'Departamento actualizado'];
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
    $termino = isset($_GET['term']) ? trim($_GET['term']) : '';
    if (empty($termino)) {
        $res = [];
    } else {
        $res = $this->model->buscarDepartamento($termino);
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}



}
