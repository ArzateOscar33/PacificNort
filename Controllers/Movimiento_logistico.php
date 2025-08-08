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
    }
    public function index()
    {
        $data['title'] = 'Tipo de Movimiento Logístico';

        $this->views->getView('admin/movimiento_logistico', "index", $data);
    }

    //METODOS TIPOS DE MOVIMIENTO
    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function editar($id)
    {
        $data = $this->model->obtener($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function registrar()
    {
        $id = $_POST['id_movimiento'] ?? '';
        $nombre = trim($_POST['nombre_movimiento']);
        $tipo = $_POST['tipo'];
        $moneda = $_POST['moneda'];

        if ($nombre === '' || $tipo === '' || $moneda === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Todos los campos son obligatorios']);
            return;
        }

        if ($id === '') {
            // Registro nuevo, validar duplicado
            $existe = $this->model->existeMovimiento($nombre);
            if ($existe) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un tipo de movimiento con ese nombre']);
                return;
            }

            $res = $this->model->registrar($nombre, $tipo, $moneda);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg' => $res ? 'Tipo de movimiento registrado' : 'Error al registrar'
            ]);
        } else {
            // Actualizar
            $res = $this->model->actualizar($id, $nombre, $tipo, $moneda);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'msg' => $res ? 'Tipo de movimiento actualizado' : 'Error al actualizar'
            ]);
        }
    }
    public function eliminar($id)
    {
        $res = $this->model->eliminar($id);
        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'msg' => $res ? 'Tipo de movimiento eliminado' : 'Error al eliminar'
        ]);
    }

    // Movimiento_logistico.php
    public function buscar()
    {
        $termino = $_GET['term'] ?? '';
        $data = $this->model->buscar($termino);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function buscarFiltroTipo($tipo)
    {
        $data = $this->model->buscarFiltroTipo($tipo);
        if (empty($data)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se encontraron resultados']);
            return;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

 
    public function buscarFiltroMoneda($moneda)
    {
        $data = $this->model->buscarFiltroMoneda($moneda);
        if (empty($data)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se encontraron resultados']);
            return;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

}