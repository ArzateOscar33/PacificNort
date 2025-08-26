<?php
class Tipos_documentos extends Controller
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
        $data['title'] = 'Tipos de Documentos';

        $this->views->getView('admin/tipos_documentos', "index", $data);
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
        $id   = $_POST['idTipoDocumento'] ?? '';
        $nombre = trim($_POST['nombreDocumento'] ?? ''); 
        $clave = trim($_POST['clave'] ?? ''); 
        $descripcion = trim($_POST['descripcionDocumento'] ?? '');
        $aplicaSobre = trim($_POST['aplicaSobre'] ?? '');

         // Validaciones básicas

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
            return;
        }
        if ($clave === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'La clave es obligatoria']);
            return;
        }
        if ($aplicaSobre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Debe registrar donde aplica el documento']);
            return;
        }

        if ($id === '') {
            // validar duplicado
            $existe = $this->model->existe($nombre);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo de documento con ese nombre']);
                return;
            }
            $res = $this->model->registrar($clave, $nombre, $descripcion, $aplicaSobre);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de documento registrado' : 'Error al registrar'
            ]);
        } else {
            $res = $this->model->actualizar($id,$clave, $nombre, $descripcion, $aplicaSobre );
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de documento actualizado' : 'Error al actualizar'
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
/*


    
*/

 

}