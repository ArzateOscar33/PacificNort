<?php
class Puertos extends Controller
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
        $data['title']    = 'Puertos';
        $data['ciudades'] = $this->model->listarCiudades();
        $this->views->getView('admin/puertos', "index", $data);
    }

    /**
     * Lista con paginación y filtros
     * GET ?page=1&per_page=25&q=&ciudad_id=
     * Respuesta:
     * { status:'success', data:[...], pagination:{page,per_page,total,total_pages} }
     */
    public function listar()
    {
        $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50], true)) $perPage = 25;

        $q        = trim($_GET['q'] ?? '');
        $ciudadId = isset($_GET['ciudad_id']) ? trim($_GET['ciudad_id']) : null;

        $offset   = ($page - 1) * $perPage;

        $result = $this->model->listarPaginado($offset, $perPage, $q === '' ? null : $q, $ciudadId);

        $total      = (int)($result['total'] ?? 0);
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        echo json_encode([
            'status' => 'success',
            'data'   => $result['rows'] ?? [],
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages
            ]
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Crear / Actualizar
     * POST:
     *  - Crear: nombre_puerto, ciudad_id
     *  - Editar: id_puerto, nombre_puerto, ciudad_id
     */
    public function registrar()
    {
        $id      = trim($_POST['id_puerto'] ?? '');
        $nombre  = trim($_POST['nombre_puerto'] ?? '');
        $ciudad  = trim($_POST['ciudad_id'] ?? '');

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio'], JSON_UNESCAPED_UNICODE); die();
        }
        if ($ciudad === '' || (int)$ciudad <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'La ciudad es obligatoria'], JSON_UNESCAPED_UNICODE); die();
        }

        // CREAR
        if ($id === '') {
            if ($this->model->existePorNombreCiudad($nombre, (int)$ciudad)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un puerto con ese nombre en esa ciudad'], JSON_UNESCAPED_UNICODE); die();
            }
            $ok = $this->model->registrar($nombre, (int)$ciudad);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'msg'    => $ok ? 'Puerto registrado' : 'Error al registrar'
            ], JSON_UNESCAPED_UNICODE); die();
        }

        // EDITAR
        $idInt = (int)$id;
        if ($idInt <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'ID inválido'], JSON_UNESCAPED_UNICODE); die();
        }

        if ($this->model->existeOtro($nombre, (int)$ciudad, $idInt)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro puerto con ese nombre en esa ciudad'], JSON_UNESCAPED_UNICODE); die();
        }

        $ok = $this->model->actualizar($idInt, $nombre, (int)$ciudad);
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Puerto actualizado' : 'Error al actualizar'
        ], JSON_UNESCAPED_UNICODE); die();
    }

    public function editar(int $id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function eliminar($id)
    {
        $ok = $this->model->eliminar((int)$id);
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Puerto eliminado' : 'Error al eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Sugerencias para el input (sin paginar)
     * GET ?term=texto[&ciudad_id=##]
     */
    public function buscar()
    {
        $term     = trim($_GET['term'] ?? '');
        $ciudadId = isset($_GET['ciudad_id']) ? trim($_GET['ciudad_id']) : null;
        $rows     = $term === '' ? [] : $this->model->buscar($term, $ciudadId);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Compatibilidad con código previo:
     * GET ?term=&ciudad_id=
     * (puedes migrar a /listar y eliminarlo más adelante)
     */
    public function filtrar()
    {
        $term     = isset($_GET['term']) ? trim($_GET['term']) : '';
        $ciudadId = isset($_GET['ciudad_id']) ? trim($_GET['ciudad_id']) : '';
        $data     = $this->model->filtrar($term, $ciudadId);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
