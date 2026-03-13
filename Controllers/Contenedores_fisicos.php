<?php
class Contenedores_fisicos extends Controller
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
        $data['title'] = 'Contenedores_fisicos';
        $this->views->getView('admin/Contenedores_fisicos', "index", $data);
    }


    public function listar()
    {
        $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50], true)) $perPage = 25;

        $q        = trim($_GET['q'] ?? '');
        $offset   = ($page - 1) * $perPage;

        $result = $this->model->listarPaginado($offset, $perPage, $q === '' ? null : $q);

        $total      = (int)$result['total'];
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        echo json_encode([
            'status'     => 'success',
            'data'       => $result['rows'],
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages
            ]
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // NUEVO: endpoint para sugerencias / búsqueda rápida (sin paginar)
    public function buscar()
    {
        $term = trim($_GET['term'] ?? '');
        if ($term === '') {
            echo json_encode([]);
            die();
        }

        $rows = $this->model->buscar($term);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }



    public function registrar()
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El número de ferro es obligatorio']);
            die();
        }

        $existe = $this->model->existeNombre($nombre);
        if ($existe) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un Ferro con ese número']);
            die();
        }

        $nuevoId = $this->model->registrar($nombre);
        if (!$nuevoId) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar el Contenedor Físico']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Contenedor registrado correctamente']);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->obtenerContenedorFisico($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function actualizar()
    {
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['numero_ferro_fisico'] ?? '');

        if ($id <= 0 || $nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Datos incompletos']);
            die();
        }

        // Validación de duplicado en edición
        if ($this->model->existeNombreEnOtro($nombre, $id)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro Ferro con ese número']);
            die();
        }

        $ok = $this->model->actualizar($id, $nombre);
        if (!$ok) {
            echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar el contenedor']);
            die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Contenedor actualizado correctamente']);
        die();
    }

    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Contenedor Físico eliminado' : 'Error al eliminar'
        ]);
        die();
    }
}
