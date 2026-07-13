<?php
class Shippers extends Controller
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
        $this->requireRoles([1, 11, 2]);
    }

    public function index()
    {
        $data['title'] = 'Shippers';
        $this->views->getView('admin/shippers', "index", $data);
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
        $nombre    = trim($_POST['nombre']    ?? '');
        $contacto  = trim($_POST['contacto']  ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($nombre === '' || $contacto === '' || $direccion === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']);
            die();
        }

        // Evitar duplicado por nombre
        $existe = $this->model->existeNombre($nombre);
        if ($existe) {
            $msg = ((int)$existe['estatus'] === 1)
                ? 'Ya existe un shipper con ese nombre'
                : 'Existe un shipper con ese nombre desactivado. Cambia el nombre o reactívalo.';
            echo json_encode(['status' => 'warning', 'msg' => $msg]);
            die();
        }

        $nuevoId = $this->model->registrar($nombre, $contacto, $direccion);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar el shipper']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Shipper registrado correctamente']);
        die();
    }

    /* ===== OBTENER UNO (para editar) ===== */
    public function editar($id)
    {
        $data = $this->model->obtenerShipper($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== ACTUALIZAR ===== */
    public function actualizar()
    {
        $id_shipper = (int)($_POST['id_shipper'] ?? 0);
        $nombre     = trim($_POST['nombre']     ?? '');
        $contacto   = trim($_POST['contacto']   ?? '');
        $direccion  = trim($_POST['direccion']  ?? '');

        if ($id_shipper <= 0 || $nombre === '' || $contacto === '' || $direccion === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']);
            die();
        }

        // Colisión con OTRO shipper del mismo nombre
        $dup = $this->model->existeNombreOtro($nombre, $id_shipper);
        if ($dup) {
            $msg = ((int)$dup['estatus'] === 1)
                ? 'Ya existe otro shipper con ese nombre'
                : 'Existe otro shipper con ese nombre desactivado.';
            echo json_encode(['status' => 'warning', 'msg' => $msg]);
            die();
        }

        $ok = $this->model->actualizar($id_shipper, $nombre, $contacto, $direccion);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar el shipper']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Shipper actualizado correctamente']);
        die();
    }

    /* ===== ELIMINAR (lógico) ===== */
    public function eliminar($id)
    {
        $ok = $this->model->eliminar($id);
        if ($ok) {
            echo json_encode(['status' => 'success', 'msg' => 'Shipper desactivado correctamente']);
            die();
        }
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo desactivar el shipper']);
        die();
    }

    /* ===== BÚSQUEDA ===== */
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
