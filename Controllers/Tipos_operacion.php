<?php
class Tipos_operacion extends Controller
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
        $data['title'] = 'Tipos_operacion';

        $this->views->getView('admin/Tipos_operacion', "index", $data);
    }
    public function listar()
    {
        $data = $this->model->getTiposOperacion();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

       public function registrar()
    {
        $nombre = trim($_POST['nombreTipoOperacion']);

        if (empty($nombre)) {
            $res = ['status' => false, 'msg' => 'El nombre es obligatorio'];
        } else {
            $existe = $this->model->existeTipoOperacion($nombre);
            if ($existe && $existe['id_tipo_operacion']) {
                $res = ['status' => false, 'msg' => 'Ya existe un tipo de operación con ese nombre'];
            } else {
                $registrado = $this->model->registrarTipoOperacion($nombre);
                $res = $registrado
                    ? ['status' => true, 'msg' => 'Tipo de operación registrado']
                    : ['status' => false, 'msg' => 'Error al registrar'];
            }
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->getTipoOperacion($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function actualizar()
    {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombreTipoOperacion']);

        if (empty($nombre)) {
            $res = ['status' => false, 'msg' => 'El nombre es obligatorio'];
        } else {
            $existe = $this->model->existeTipoOperacion($nombre);
            if ($existe && $existe['id_tipo_operacion'] != $id) {
                $res = ['status' => false, 'msg' => 'Ya existe un tipo de operación con ese nombre'];
            } else {
                $actualizado = $this->model->actualizarTipoOperacion($id, $nombre);
                $res = $actualizado
                    ? ['status' => true, 'msg' => 'Tipo de operación actualizado']
                    : ['status' => false, 'msg' => 'No se pudo actualizar'];
            }
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }


        public function eliminar($id)
    {
        $res = $this->model->eliminarTipoOperacion($id);
        echo json_encode([
            'status' => $res ? true : false,
            'msg' => $res ? 'Tipo de operación eliminado' : 'No se pudo eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function buscar()
    {
        $termino = $_GET['term'] ?? '';
        $data = $this->model->buscarTipoOperacion($termino);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }



}