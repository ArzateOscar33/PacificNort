<?php
class Movimiento_logistico extends Controller
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
        $data['title'] = 'Tipo de Costo Logístico';
        $data['categorias'] = $this->model->listarCategorias(true);
        $this->views->getView('admin/movimiento_logistico', "index", $data);
    }

    // =========================
    // LISTAR / EDITAR
    // =========================
    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function editar($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)$id;

        $data = $this->model->obtener($id);
        if (empty($data)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Registro no encontrado']);
            die();
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // =========================
    // REGISTRAR / ACTUALIZAR
    // =========================
    public function registrar()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id          = trim($_POST['id_movimiento'] ?? '');
        $nombre      = trim($_POST['nombre_movimiento'] ?? '');
        $tipo        = trim($_POST['tipo'] ?? '');
        $moneda      = trim($_POST['moneda'] ?? '');
        $categoriaId = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;

        // Validaciones base
        if ($nombre === '' || $tipo === '' || $moneda === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Todos los campos son obligatorios']);
            die();
        }

        // categoria_id puede ser null (0 => null)
        $categoriaId = ($categoriaId > 0) ? $categoriaId : null;

        // (Opcional pero recomendado) validar que la categoría exista y esté activa
        if ($categoriaId !== null) {
            $cats = $this->model->getCategorias(true);
            $ok = false;
            foreach ($cats as $c) {
                if ((int)$c['id_categoria'] === (int)$categoriaId) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                echo json_encode(['status' => 'warning', 'msg' => 'La categoría seleccionada no es válida o está inactiva']);
                die();
            }
        }

        // Crear
        if ($id === '') {

            $existe = $this->model->existeMovimiento($nombre);
            if (!empty($existe)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo de movimiento con ese nombre']);
                die();
            }

            $res = $this->model->registrar($nombre, $tipo, $moneda, $categoriaId);

            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg'    => $res ? 'Tipo de movimiento registrado' : 'Error al registrar'
            ]);
            die();
        }

        // Actualizar
        $id = (int)$id;

        // Evitar duplicado al actualizar (excluye el mismo id)
        $existe = $this->model->existeMovimiento($nombre, $id);
        if (!empty($existe)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo de movimiento con ese nombre']);
            die();
        }

        $res = $this->model->actualizar($id, $nombre, $tipo, $moneda, $categoriaId);

        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Tipo de movimiento actualizado' : 'Error al actualizar'
        ]);
        die();
    }

    // =========================
    // ELIMINAR
    // =========================
    public function eliminar($id)
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)$id;
        $res = $this->model->eliminar($id);

        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Tipo de movimiento eliminado' : 'Error al eliminar'
        ]);
        die();
    }

    // =========================
    // BUSCAR / FILTRAR
    // =========================
    public function buscar()
    {
        header('Content-Type: application/json; charset=utf-8');

        $termino = $_GET['term'] ?? '';
        $data = $this->model->buscar($termino);

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function filtrar()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term       = isset($_GET['term']) ? trim($_GET['term']) : '';
        $tipo       = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
        $moneda     = isset($_GET['moneda']) ? trim($_GET['moneda']) : '';
        $categoria  = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

        $data = $this->model->filtrar($term, $tipo, $moneda, $categoria);

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // (Opcional) Endpoints antiguos: compatibilidad
    public function buscarFiltroTipo($tipo)
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = $this->model->buscarFiltroTipo($tipo);
        if (empty($data)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se encontraron resultados']);
            die();
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function buscarFiltroMoneda($moneda)
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = $this->model->buscarFiltroMoneda($moneda);
        if (empty($data)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se encontraron resultados']);
            die();
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // =========================
    // CATEGORÍAS (NUEVO)
    // =========================
    public function registrarCategoria()
    {
        header('Content-Type: application/json; charset=utf-8');

        $nombre = trim($_POST['nombre_categoria'] ?? '');

        if ($nombre === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'El nombre es obligatorio']);
            die();
        }

        // Validar duplicado
        $existe = $this->model->existeCategoria($nombre);
        if (!empty($existe)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe una categoría con ese nombre']);
            die();
        }

        $res = $this->model->registrarCategoria($nombre);

        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg'    => $res ? 'Categoría registrada' : 'Error al registrar categoría'
        ]);
        die();
    }

    public function listarCategorias()
    {
        header('Content-Type: application/json; charset=utf-8');

        $rows = $this->model->listarCategorias(true);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }
}
