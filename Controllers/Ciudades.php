<?php
class Ciudades extends Controller
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
        $data['title']   = 'Ciudades';
        $data['estados'] = $this->model->listarEstados();
        $this->views->getView('admin/Ciudades', "index", $data);
    }

    /**
     * Lista con paginación + filtros
     * GET ?page=1&per_page=25&q=&estado_id=
     * Respuesta:
     * { status:'success', data:[...], pagination:{page,per_page,total,total_pages} }
     */
    public function listar()
    {
        $page      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage   = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50], true)) $perPage = 25;

        $q         = trim($_GET['q'] ?? '');
        $estadoId  = isset($_GET['estado_id']) ? trim($_GET['estado_id']) : null;

        $offset    = ($page - 1) * $perPage;

        // Modelo nuevo con paginación y filtros
        $result = $this->model->listarPaginado($offset, $perPage, $q === '' ? null : $q, $estadoId);

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
     *  - Crear: nombre_ciudad, estado_id
     *  - Editar: id_ciudad, nombre_ciudad, estado_id
     */
    public function registrar()
    {
        $id       = trim($_POST['id_ciudad'] ?? '');
        $nombre   = trim($_POST['nombre_ciudad'] ?? '');
        $estadoId = trim($_POST['estado_id'] ?? '');

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio'], JSON_UNESCAPED_UNICODE); die();
        }
        if ($estadoId === '' || (int)$estadoId <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'El estado es obligatorio'], JSON_UNESCAPED_UNICODE); die();
        }

        // CREAR
        if ($id === '') {
            if ($this->model->existe($nombre, $estadoId)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe una ciudad con ese nombre en el estado seleccionado'], JSON_UNESCAPED_UNICODE); die();
            }
            $ok = $this->model->registrar($nombre, (int)$estadoId);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'msg'    => $ok ? 'Ciudad registrada' : 'Error al registrar'
            ], JSON_UNESCAPED_UNICODE); die();
        }

        // EDITAR
        $idInt = (int)$id;
        if ($idInt <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'ID inválido'], JSON_UNESCAPED_UNICODE); die();
        }

        if ($this->model->existeEnOtro($nombre, (int)$estadoId, $idInt)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otra ciudad con ese nombre en el mismo estado'], JSON_UNESCAPED_UNICODE); die();
        }

        $ok = $this->model->actualizar($idInt, $nombre, (int)$estadoId);
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Ciudad actualizada' : 'Error al actualizar'
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
            'msg'    => $ok ? 'Ciudad eliminada' : 'Error al eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Sugerencias para el input (sin paginar)
     * GET ?term=texto[&estado_id=##]
     */
    public function buscar()
    {
        $term     = trim($_GET['term'] ?? '');
        $estadoId = isset($_GET['estado_id']) ? trim($_GET['estado_id']) : null;
        $rows     = $term === '' ? [] : $this->model->buscar($term, $estadoId);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Compatibilidad con código previo:
     * GET ?term=&estado_id=
     * (puedes migrar a /listar y eliminarlo más adelante)
     */
    public function filtrar()
    {
        $term     = isset($_GET['term']) ? trim($_GET['term']) : '';
        $estadoId = isset($_GET['estado_id']) ? trim($_GET['estado_id']) : '';
        $data     = $this->model->filtrar($term, $estadoId);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
