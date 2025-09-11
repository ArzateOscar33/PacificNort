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

    public function index()
    {
        $data['title'] = 'Contenedores Maritimos';
        $this->views->getView('admin/Contenedores_maritimos', "index", $data);
    }

    /**
     * Lista con paginación y filtro rápido (?q=)
     * Devuelve: { status, data:[], pagination:{page,per_page,total,total_pages} }
     */
    public function listar()
    {
        $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50], true)) $perPage = 25;

        $q      = trim($_GET['q'] ?? '');
        $offset = ($page - 1) * $perPage;

        // El modelo debe implementar listarPaginado($offset, $limit, ?$q)
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
     * Sugerencias rápidas para el input (sin paginar)
     * GET ?term=texto
     */
    public function buscar()
    {
        $term = trim($_GET['term'] ?? '');
        if ($term === '') { echo json_encode([]); die(); }

        // El modelo debe implementar buscar($term)
        $rows = $this->model->buscar($term);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE); die();
    }

    public function registrar()
    {
        $numero = strtoupper(trim($_POST['numero_contenedor'] ?? ''));
        $tipo   = trim($_POST['tipo'] ?? '');
        $obs    = trim($_POST['observaciones'] ?? '');

        if ($numero === '' ) {
            echo json_encode(['status'=>'warning','msg'=>'Número de contenedor y tipo son obligatorios']); die();
        }

        // El modelo debe implementar existeNumero($numero)
        if ($this->model->existeNumero($numero)) {
            echo json_encode(['status'=>'warning','msg'=>'Ya existe un contenedor con ese número']); die();
        }

        // El modelo debe implementar registrar($numero,$tipo,$obs)
        $nuevoId = $this->model->registrar($numero, $tipo, $obs);
        if (!$nuevoId) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo registrar el contenedor']); die();
        }

        echo json_encode(['status'=>'success','msg'=>'Contenedor registrado correctamente']); die();
    }

    public function editar($id)
    {
        // El modelo debe implementar obtenerContenedorMaritimo($id)
        $data = $this->model->obtenerContenedorMaritimo($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE); die();
    }

    public function actualizar()
    {
        $id     = (int)($_POST['id_contenedor'] ?? 0);
        $numero = strtoupper(trim($_POST['numero_contenedor'] ?? ''));
        $tipo   = trim($_POST['tipo'] ?? '');
        $obs    = trim($_POST['observaciones'] ?? '');

        if ($id <= 0 || $numero === '' ) {
            echo json_encode(['status'=>'warning','msg'=>'Datos incompletos']); die();
        }

        // El modelo debe implementar existeNumeroEnOtro($numero,$id)
        if ($this->model->existeNumeroEnOtro($numero, $id)) {
            echo json_encode(['status'=>'warning','msg'=>'Ya existe otro contenedor con ese número']); die();
        }

        // El modelo debe implementar actualizar($id,$numero,$tipo,$obs)
        $ok = $this->model->actualizar($id, $numero, $tipo, $obs);
        if (!$ok) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo actualizar el contenedor']); die();
        }

        echo json_encode(['status'=>'success','msg'=>'Contenedor actualizado correctamente']); die();
    }

    public function eliminar($id)
    {
        // El modelo debe implementar eliminar($id) => estatus=0
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Contenedor eliminado' : 'Error al eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }
}
