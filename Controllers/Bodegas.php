<?php
class Bodegas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2, 15]);
    }
    public function index()
    {
        $data['title'] = 'Bodegas';
        $data['ciudades'] = $this->model->listarCiudades();

        $this->views->getView('admin/Bodegas', "index", $data);
    }

    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function registrar()
    {
        // Campos desde el form
        $nombre     = trim($_POST['nombre']     ?? '');
        $direccion  = trim($_POST['direccion']  ?? '');
        $ciudad_id  = (int)($_POST['ciudad_id'] ?? 0);

        // Validaciones básicas
        if ($nombre === '' || $direccion === '' || $ciudad_id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']);
            die();
        }

        // ¿Existe ya una bodega con el mismo nombre en la misma ciudad?
        $existe = $this->model->existeNombreEnCiudad($nombre, $ciudad_id);
        if ($existe) {
            // Si ya está activa -> warning
            if ((int)$existe['estatus'] === 1) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe una bodega con ese nombre en la ciudad seleccionada']);
                die();
            }
            // Si existe pero desactivada -> reactivar con los datos actuales
            $ok = $this->model->reactivar($existe['id_bodega'], $nombre, $direccion, $ciudad_id);
            if ($ok) {
                echo json_encode(['status' => 'success', 'msg' => 'Bodega reactivada correctamente']);
                die();
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo reactivar la bodega']);
                die();
            }
        }

        // Registrar nueva
        $nuevoId = $this->model->registrar($nombre, $direccion, $ciudad_id);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar la bodega']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Bodega registrada correctamente']);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Actualiza una bodega existente
    public function actualizar()
    {
        $id         = (int)($_POST['id'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');
        $direccion  = trim($_POST['direccion'] ?? '');
        $ciudad_id  = (int)($_POST['ciudad_id'] ?? 0);

        if ($id <= 0 || $nombre === '' || $direccion === '' || $ciudad_id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']);
            die();
        }

        // Evitar duplicado con otra bodega (nombre+ciudad)
        $dup = $this->model->existeNombreEnCiudadOtro($nombre, $ciudad_id, $id);
        if ($dup && (int)$dup['estatus'] === 1) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otra bodega con ese nombre en la ciudad seleccionada']);
            die();
        }

        $ok = $this->model->actualizar($id, $nombre, $direccion, $ciudad_id);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar la bodega']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Bodega actualizada correctamente']);
        die();
    }
    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);

        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Bodega Eliminada' : 'Error al eliminar'
        ]);
        die();
    }
    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        if ($term === '') {
            echo json_encode([]);
            die();
        }
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
