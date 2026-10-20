<?php
class Bodegas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2, 15]);
    }

    // La nomenclatura es BD, YD o ES + dos letras.
    private function validarCodigo($codigo, $tipo_ubicacion)
    {
        $prefijos = [
            'BODEGA' => 'BD',
            'YARDA' => 'YD',
            'ESTACIONAMIENTO' => 'ES'
        ];
        return isset($prefijos[$tipo_ubicacion])
            && preg_match('/^[A-Z]{4}$/', $codigo)
            && substr($codigo, 0, 2) === $prefijos[$tipo_ubicacion];
    }

    public function index()
    {
        $data['title'] = 'Bodegas';
        $data['ciudades'] = $this->model->listarCiudades();
        $this->views->getView('admin/bodegas', "index", $data);
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
        $nombre         = trim($_POST['nombre'] ?? '');
        $direccion      = trim($_POST['direccion'] ?? '');
        $ciudad_id      = (int)($_POST['ciudad_id'] ?? 0);
        $codigo         = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo_ubicacion = strtoupper(trim($_POST['tipo_ubicacion'] ?? ''));

        if ($nombre === '' || $direccion === '' || $ciudad_id <= 0 || $codigo === '' || $tipo_ubicacion === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Completa nombre, dirección, ciudad, tipo y código']);
            die();
        }

        if (!$this->validarCodigo($codigo, $tipo_ubicacion)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Código inválido. Usa BD, YD o ES más dos letras según el tipo (ej. YDTE)']);
            die();
        }

        // Mantenemos la validación original de nombre + ciudad.
        $existe = $this->model->existeNombreEnCiudad($nombre, $ciudad_id);
        if ($existe) {
            if ((int)$existe['estatus'] === 1) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe una ubicación con ese nombre en la ciudad seleccionada']);
                die();
            }

            // Para reactivar, el código no debe estar usado por otra ubicación.
            if ($this->model->existeCodigo($codigo, (int)$existe['id_bodega'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ese código ya pertenece a otra ubicación']);
                die();
            }

            $ok = $this->model->reactivar($existe['id_bodega'], $nombre, $direccion, $ciudad_id, $codigo, $tipo_ubicacion);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'msg' => $ok ? 'Ubicación reactivada correctamente' : 'No se pudo reactivar la ubicación'
            ]);
            die();
        }

        if ($this->model->existeCodigo($codigo)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ese código ya existe. Utiliza otro código']);
            die();
        }

        $nuevoId = $this->model->registrar($nombre, $direccion, $ciudad_id, $codigo, $tipo_ubicacion);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar la ubicación']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Ubicación registrada correctamente']);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->obtener((int)$id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function actualizar()
    {
        $id             = (int)($_POST['id'] ?? 0);
        $nombre         = trim($_POST['nombre'] ?? '');
        $direccion      = trim($_POST['direccion'] ?? '');
        $ciudad_id      = (int)($_POST['ciudad_id'] ?? 0);
        $codigo         = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo_ubicacion = strtoupper(trim($_POST['tipo_ubicacion'] ?? ''));

        if ($id <= 0 || $nombre === '' || $direccion === '' || $ciudad_id <= 0 || $codigo === '' || $tipo_ubicacion === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Completa nombre, dirección, ciudad, tipo y código']);
            die();
        }

        if (!$this->validarCodigo($codigo, $tipo_ubicacion)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Código inválido. Usa BD, YD o ES más dos letras según el tipo (ej. BDHC)']);
            die();
        }

        $dup = $this->model->existeNombreEnCiudadOtro($nombre, $ciudad_id, $id);
        if ($dup && (int)$dup['estatus'] === 1) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otra ubicación con ese nombre en la ciudad seleccionada']);
            die();
        }

        if ($this->model->existeCodigo($codigo, $id)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ese código ya pertenece a otra ubicación']);
            die();
        }

        $ok = $this->model->actualizar($id, $nombre, $direccion, $ciudad_id, $codigo, $tipo_ubicacion);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar la ubicación']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Ubicación actualizada correctamente']);
        die();
    }

    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg' => $res ? 'Ubicación eliminada' : 'Error al eliminar'
        ]);
        die();
    }

    public function buscar()
    {
        $term = trim($_GET['term'] ?? '');
        if ($term === '') {
            echo json_encode([]);
            die();
        }
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
