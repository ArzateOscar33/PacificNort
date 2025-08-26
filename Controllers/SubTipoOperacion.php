<?php
class SubTipoOperacion extends Controller
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
        $data['title'] = 'Subtipos De Operacion';
        $data['tipos_operacion'] = $this->model->getTipoOperacion();
        $data['puertos'] = $this->model->getPuertos();

        $this->views->getView('admin/subtipos_operacion', "index", $data);
    }
        public function listar()
    {
        $data = $this->model->getSubTiposOperacion();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
public function registrar()
{
    $nombre = trim($_POST['nombreSubtipoOperacion'] ?? '');
    $id_tipo_operacion = trim($_POST['tipo_operacion_id'] ?? '');
    $clave = trim($_POST['claveSubtipoOperacion'] ?? '');
    $puerto_id = trim($_POST['puerto_id'] ?? '');

    if (empty($nombre) || empty($clave) || empty($id_tipo_operacion)) {
        $res = ['status' => "error", 'msg' => 'Nombre, clave y tipo de operación son obligatorios'];
    } else {
        // valida duplicados (clave y nombre)
        if ($this->model->existeSubtipoPorClave($clave)) {
            $res = ['status' => "error", 'msg' => 'Ya existe un subtipo con esa clave'];
        } elseif ($this->model->existeSubtipoPorNombre($nombre)) {
            $res = ['status' => "error", 'msg' => 'Ya existe un subtipo con ese nombre'];
        } else {
            $ok = $this->model->registrarSubTipoOperacion($id_tipo_operacion, $clave, $nombre, $puerto_id);
            $res = $ok
                ? ['status' => "success", 'msg' => 'Subtipo de operación registrado']
                : ['status' => "error", 'msg' => 'Error al registrar'];
        }
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

public function actualizar()
{
    $nombre = trim($_POST['nombreSubtipoOperacion'] ?? '');
    $id_tipo_operacion = trim($_POST['tipo_operacion_id'] ?? '');
    $clave = trim($_POST['claveSubtipoOperacion'] ?? '');
    $puerto_id = trim($_POST['puerto_id'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['status'=>false,'msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE); die();
    }

    if (empty($nombre) || empty($clave) || empty($id_tipo_operacion)) {
        $res = ['status' => false, 'msg' => 'Nombre, clave y tipo de operación son obligatorios'];
    } else {
        if ($this->model->existeSubtipoPorClave($clave, $id)) {
            $res = ['status' => false, 'msg' => 'Ya existe un subtipo con esa clave'];
        } elseif ($this->model->existeSubtipoPorNombre($nombre, $id)) {
            $res = ['status' => false, 'msg' => 'Ya existe un subtipo con ese nombre'];
        } else {
            $ok = $this->model->actualizarTipoOperacion($id_tipo_operacion, $clave, $nombre, $puerto_id, $id);
            $res = $ok
                ? ['status' => true, 'msg' => 'Subtipo de operación actualizado']
                : ['status' => false, 'msg' => 'No se pudo actualizar'];
        }
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

    public function editar($id)
    {
        $data = $this->model->getSubtipoOperacion($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
         public function eliminar($id)
    {
        $res = $this->model->eliminarSubtipoOperacion($id);
        echo json_encode([
            'status' => $res ? "success" : "error",
            'msg' => $res ? 'Tipo de operación eliminado' : 'No se pudo eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function buscar()
    {
        $termino = $_GET['term'] ?? '';
        $data = $this->model->buscarSubtipoOperacion($termino);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    /*







 


*/
}