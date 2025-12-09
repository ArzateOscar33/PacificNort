<?php
class Roles extends Controller
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
        $data['title'] = 'Roles';

        $this->views->getView('admin/roles', "index", $data);
    }
    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    


    public function registrar()
    {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);

        if ($nombre === '' || $descripcion === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Todos los campos son obligatorios']);
            return;
        }

        // Si es registro nuevo, verifica duplicado
        if ($id === '') {
            $existe = $this->model->existeRol($nombre);
            if ($existe && $existe['total'] > 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un rol con ese nombre']);
                return;
            }

            $res = $this->model->registrar($nombre, $descripcion);
            echo json_encode(['status' => $res ? 'success' : 'error', 'msg' => $res ? 'Rol registrado' : 'Error al registrar']);
        } else {
            // Para actualizar, opcionalmente podrías validar que no se repita en otro id
            $res = $this->model->actualizar($id, $nombre, $descripcion);
            echo json_encode(['status' => $res ? 'success' : 'error', 'msg' => $res ? 'Rol actualizado' : 'Error al actualizar']);
        }
    }


    public function eliminar($id)
    {
        $relacion = $this->model->existeRelacionUsuarios($id);
        if ($relacion['total'] > 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'No se puede eliminar, hay usuarios con este rol']);
            return;
        }

        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg' => $res ? 'Rol eliminado correctamente' : 'Error al eliminar'
        ]);
    }


    public function obtener($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data);
    }

    public function buscar()
    {
        $termino = $_GET['term'] ?? '';
        $data = $this->model->buscarRol($termino);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }


}