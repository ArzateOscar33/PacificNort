<?php
class Forwarders extends Controller
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

    /* ===== VISTA ===== */
    public function index()
    {
        $data['title'] = 'Forwarders';
        $this->views->getView('admin/forwarders', "index", $data);
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
        // La vista envía "direccion", pero no existe en la tabla -> se ignora

        if ($nombre === '' || $contacto === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']);
            die();
        }

        // Duplicado por nombre (case-insensitive)
        $existe = $this->model->existeNombre($nombre);
        if ($existe) {
            if ((int)$existe['estatus'] === 1) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un forwarder con ese nombre']);
                die();
            }
            // Existe inactivo -> reactivar
            $ok = $this->model->reactivar($existe['id_forwarder'], $nombre, $contacto);
            if ($ok) {
                echo json_encode(['status' => 'success', 'msg' => 'Forwarder reactivado correctamente']);
                die();
            }
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo reactivar el forwarder']);
            die();
        }

        $nuevoId = $this->model->registrar($nombre, $contacto);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar el forwarder']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Forwarder registrado correctamente']);
        die();
    }

    /* ===== OBTENER UNO (EDITAR) ===== */
    public function editar($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== ACTUALIZAR ===== */
    public function actualizar()
    {
        // Tu vista usa "id_Forwarder" (con F mayúscula). Permitimos ambos por compatibilidad:
        $id_forwarder = (int)($_POST['id_forwarder'] ?? $_POST['id_Forwarder'] ?? 0);
        $nombre       = trim($_POST['nombre']   ?? '');
        $contacto     = trim($_POST['contacto'] ?? '');
        // "direccion" en el form se ignora si no existe en la tabla

        if ($id_forwarder <= 0 || $nombre === '' || $contacto === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']);
            die();
        }

        // Checar duplicado con OTRO registro
        $dup = $this->model->existeNombreOtro($nombre, $id_forwarder);
        if ($dup && (int)$dup['estatus'] === 1) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro forwarder con ese nombre']);
            die();
        }

        $ok = $this->model->actualizar($id_forwarder, $nombre, $contacto);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar el forwarder']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Forwarder actualizado correctamente']);
        die();
    }

    /* ===== ELIMINAR (lógico) ===== */
    public function eliminar($id)
    {
        $ok = $this->model->eliminar($id);
        if ($ok) {
            echo json_encode(['status' => 'success', 'msg' => 'Forwarder desactivado correctamente']);
            die();
        }
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo desactivar el forwarder']);
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
