<?php
class Estados extends Controller
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
        $data['title'] = 'Estados';
        $this->views->getView('admin/Estados', "index", $data);
    }

    /**
     * Lista con paginación + filtro (?q=)
     * Respuesta:
     * { status:'success', data:[...], pagination:{page,per_page,total,total_pages} }
     */
    public function listar()
    {
        $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50], true)) $perPage = 25;

        $q      = trim($_GET['q'] ?? '');
        $offset = ($page - 1) * $perPage;

        $result = $this->model->listarPaginado($offset, $perPage, $q === '' ? null : $q);

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
     * Crear o actualizar (según venga o no id_estado).
     * Para crear valida con existe(); para editar valida con existeEnOtro().
     */
    public function registrar()
    {
        $id     = trim($_POST['id_estado'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio'], JSON_UNESCAPED_UNICODE);
            die();
        }

        // CREAR
        if ($id === '') {
            if ($this->model->existe($nombre)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un estado con ese nombre'], JSON_UNESCAPED_UNICODE);
                die();
            }
            $ok = $this->model->registrar($nombre);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'msg'    => $ok ? 'Estado registrado' : 'Error al registrar'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // EDITAR
        $idInt = (int)$id;
        if ($idInt <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        if (method_exists($this->model, 'existeEnOtro') && $this->model->existeEnOtro($nombre, $idInt)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro estado con ese nombre'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $ok = $this->model->actualizar($idInt, $nombre);
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Estado actualizado' : 'Error al actualizar'
        ], JSON_UNESCAPED_UNICODE);
        die();
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
            'msg'    => $ok ? 'Estado eliminado' : 'Error al eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Sugerencias para el input (sin paginar)
     * GET ?term=
     */
    public function buscar()
    {
        $term = trim($_GET['term'] ?? '');
        $rows = ($term === '') ? [] : $this->model->buscar($term);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }
}
