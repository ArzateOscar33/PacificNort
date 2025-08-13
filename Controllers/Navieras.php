<?php
class Navieras extends Controller
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
        $data['title'] = 'Navieras';
        $this->views->getView('admin/Navieras', "index", $data);
    }

    /* ===== LISTAR ===== */
    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== REGISTRAR ===== */
    public function registrar()
    {
        $nombre   = trim($_POST['nombre']   ?? '');
        $contacto = trim($_POST['contacto'] ?? '');

        if ($nombre === '' || $contacto === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }

        // Evitar duplicado por nombre
        $existe = $this->model->existeNombre($nombre);
        if ($existe) {
            // Como en Brokers: si existe (activo o inactivo) bloqueamos el alta
            $msg = ((int)$existe['estatus'] === 1)
                 ? 'Ya existe una naviera con ese nombre'
                 : 'Existe una naviera con ese nombre desactivada. Cambia el nombre o reactívala desde administración';
            echo json_encode(['status' => 'warning', 'msg' => $msg]); die();
        }

        $nuevoId = $this->model->registrar($nombre, $contacto);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar la naviera']); die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Naviera registrada correctamente']); die();
    }

    /* ===== OBTENER UNO (para editar) ===== */
    public function editar($id)
    {
        $data = $this->model->obtenerNaviera($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== ACTUALIZAR ===== */
    public function actualizar()
    {
        $id_naviera = (int)($_POST['id_naviera'] ?? 0);
        $nombre     = trim($_POST['nombre']     ?? '');
        $contacto   = trim($_POST['contacto']   ?? '');

        if ($id_naviera <= 0 || $nombre === '' || $contacto === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }

        // Evitar colisión con otra naviera de mismo nombre
        $dup = $this->model->existeNombreOtro($nombre, $id_naviera);
        if ($dup) {
            $msg = ((int)$dup['estatus'] === 1)
                 ? 'Ya existe otra naviera con ese nombre'
                 : 'Existe otra naviera con ese nombre desactivada. Cambia el nombre o reactívala';
            echo json_encode(['status' => 'warning', 'msg' => $msg]); die();
        }

        $ok = $this->model->actualizar($id_naviera, $nombre, $contacto);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar la naviera']); die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Naviera actualizada correctamente']); die();
    }

    /* ===== ELIMINAR (borrado lógico) ===== */
    public function eliminar($id)
    {
        $ok = $this->model->eliminar($id);
        if ($ok) {
            echo json_encode(['status' => 'success', 'msg' => 'Naviera desactivada correctamente']); die();
        }
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo desactivar la naviera']); die();
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        if ($term === '') { echo json_encode([]); die(); }
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
