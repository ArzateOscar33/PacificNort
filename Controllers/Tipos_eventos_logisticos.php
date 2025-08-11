<?php
class Tipos_eventos_logisticos extends Controller
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
        $data['title'] = 'Tipos de Eventos Logísticos';

        $this->views->getView('admin/tipos_eventos_logisticos', "index", $data);
    }

    public function listar(){
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
        // OBTENER (para editar)
    public function editar($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // REGISTRAR / ACTUALIZAR
    public function registrar()
    {
        $id   = $_POST['id_tipo_evento'] ?? '';
        $nombre = trim($_POST['nombre'] ?? ''); 

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
            return;
        }

        if ($id === '') {
            // validar duplicado
            $existe = $this->model->existe($nombre);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo de evento con ese nombre']);
                return;
            }
            $res = $this->model->registrar($nombre);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de evento registrado' : 'Error al registrar'
            ]);
        } else {
            $res = $this->model->actualizar($id, $nombre);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de evento actualizado' : 'Error al actualizar'
            ]);
        }
    }

    // ELIMINAR (soft delete)
    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Tipo de evento eliminado' : 'Error al eliminar'
        ]);
    }

    // (Opcional) BUSCAR por nombre (para sugerencias)
    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    


 

}