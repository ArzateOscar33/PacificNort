<?php
class Contenedores_maritimos extends Controller
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

    // Carga vista + catálogo de navieras para el <select>
    public function index()
    {
        $data['title']    = 'Contenedores marítimos';
        $this->views->getView('admin/Contenedores_maritimos', "index", $data);
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
        $numero = strtoupper(trim($_POST['numero_contenedor'] ?? ''));  // estándar en mayúsculas
        $tipo   = trim($_POST['tipo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($numero === '' || $tipo === '' ) {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }

        // Duplicado por número
        $existe = $this->model->existeNumero($numero);
        if ($existe) {
            if ((int)$existe['estatus'] === 1) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un contenedor con ese número']); die();
            }
            // Existe inactivo: reactivar con datos actuales
            $ok = $this->model->reactivar($existe['id_contenedor'], $numero, $tipo, $observaciones);
            if ($ok) { echo json_encode(['status' => 'success', 'msg' => 'Contenedor reactivado correctamente']); die(); }
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo reactivar el contenedor']); die();
        }

        // Alta normal
        $nuevoId = $this->model->registrar($numero, $tipo, $observaciones);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar el contenedor']); die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Contenedor registrado correctamente']); die();
    }

    /* ===== OBTENER UNO (para editar) ===== */
    public function editar($id)
    {
        // $id es id_contenedor_maritimo en DB; el modelo devuelve alias id_contenedor
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== ACTUALIZAR ===== */
    public function actualizar()
    {
        // En el form el hidden debe llamarse id_contenedor
        $id_contenedor = (int)($_POST['id_contenedor'] ?? 0);
        $numero        = strtoupper(trim($_POST['numero_contenedor'] ?? ''));
        $tipo          = trim($_POST['tipo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($id_contenedor <= 0 || $numero === '' || $tipo === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
        }

        // Evitar colisión de número con OTRO registro activo
        $dup = $this->model->existeNumeroOtro($numero, $id_contenedor);
        if ($dup && (int)$dup['estatus'] === 1) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro contenedor con ese número']); die();
        }

        $ok = $this->model->actualizar($id_contenedor, $numero, $tipo, $observaciones);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar el contenedor']); die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Contenedor actualizado correctamente']); die();
    }

    /* ===== ELIMINAR (lógico) ===== */
    public function eliminar($id)
    {
        $ok = $this->model->eliminar($id);
        if ($ok) { echo json_encode(['status' => 'success', 'msg' => 'Contenedor desactivado correctamente']); die(); }
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo desactivar el contenedor']); die();
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
