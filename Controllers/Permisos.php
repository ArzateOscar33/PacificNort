<?php
class Permisos extends Controller
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
        $data['title'] = 'Permisos';
        $this->views->getView('admin/permisos', "index", $data);
    }

    /* ===== LISTAR TABLA ===== */
    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== LLENADO DE SELECTS ===== */
    public function usuarios()
    {
        $data = $this->model->listarUsuariosActivos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE); die();
    }

    public function tipos_operacion()
    {
        $data = $this->model->listarTiposOperacionActivos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE); die();
    }

    /* ===== OBTENER UNO ===== */
    public function editar($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE); die();
    }

    /* ===== REGISTRAR / ACTUALIZAR ===== */
    public function registrar()
    {
        $id_permiso       = $_POST['id']              ?? '';
        $usuario_id       = (int)($_POST['usuario_id'] ?? 0);
        $tipo_operacion_id= (int)($_POST['tipo_operacion_id'] ?? 0);

        if ($usuario_id <= 0 || $tipo_operacion_id <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'Usuario y Tipo de Operación son requeridos']); die();
        }

        if ($id_permiso === '') {
            // alta / reactivación
            $res = $this->model->registrar($usuario_id, $tipo_operacion_id);
            if ($res['ok']) {
                $msg = !empty($res['reactivado']) ? 'Permiso reactivado correctamente' : 'Permiso asignado correctamente';
                echo json_encode(['status'=>'success','msg'=>$msg]); die();
            }
            echo json_encode(['status'=>'warning','msg'=>$res['msg'] ?? 'No se pudo asignar']); die();

        } else {
            // edición
            $res = $this->model->actualizar($id_permiso, $usuario_id, $tipo_operacion_id);
            if ($res['ok']) {
                echo json_encode(['status'=>'success','msg'=>'Permiso actualizado correctamente']); die();
            }
            echo json_encode(['status'=>'warning','msg'=>$res['msg'] ?? 'No se pudo actualizar']); die();
        }
    }

    /* ===== ELIMINAR (LÓGICO) ===== */
    public function eliminar($id)
    {
        $ok = $this->model->eliminar($id);
        if ($ok) {
            echo json_encode(['status'=>'success','msg'=>'Permiso desactivado']); die();
        }
        echo json_encode(['status'=>'error','msg'=>'No se pudo desactivar']); die();
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        if ($term === '') { echo json_encode([]); die(); }
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE); die();
    }
}
