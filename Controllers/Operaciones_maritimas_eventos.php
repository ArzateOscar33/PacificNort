<?php
class Operaciones_maritimas_eventos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /** GET ?term=LI */
    public function buscar_operaciones()
    {
        $term  = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        if (mb_strlen($term) < 1) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        $ops = $this->model->buscarOperacionesMaritimas($term, $limit);

        // Estructura estándar: [{id, label, meta}]
        $out = array_map(function ($row) {
            $meta = '';
            if (isset($row['fisicos'], $row['maritimos'])) {
                $meta = "{$row['fisicos']} físico(s) · {$row['maritimos']} marítimo(s)";
            }
            return [
                'id'    => (int)$row['id'],
                'label' => $row['label'],
                'meta'  => $meta
            ];
        }, $ops);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }

    /** GET ?operacion_id=48[&term=FXEU] */
    public function buscar_contenedores()
    {
        $operacionId = isset($_GET['operacion_id']) ? intval($_GET['operacion_id']) : 0;
        $term        = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit       = isset($_GET['limit']) ? intval($_GET['limit']) : 15;

        if ($operacionId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        $rows = $this->model->buscarContenedoresDeOperacion($operacionId, $term, $limit);

        // Estructura estándar: [{id, label, tipo}]
        $out = array_map(function ($r) {
            return [
                'id'    => (int)$r['id'],     // id_contenedor (FISICO) o id (tabla cmo) MARITIMO
                'label' => $r['label'],       // numero_ferro o numero_contenedor
                'tipo'  => $r['tipo']         // 'FISICO' | 'MARITIMO'
            ];
        }, $rows);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function tipos_evento_maritimo()
    {
        $rows = $this->model->listarTiposEventoMaritimo();
        $out  = array_map(function ($r) {
            return [
                'id'     => (int)$r['id_tipo_evento'],
                'nombre' => $r['nombre']
            ];
        }, is_array($rows) ? $rows : []);
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function tipos_evento()
    {
        // Permite null => solo globales
        $tipo = isset($_GET['tipo_operacion_id']) && $_GET['tipo_operacion_id'] !== ''
            ? (int)$_GET['tipo_operacion_id']
            : null;

        $rows = $this->model->listarTiposEventoPorTipoOperacion($tipo);
        $out  = array_map(function ($r) {
            return [
                'id'     => (int)$r['id_tipo_evento'],
                'nombre' => $r['nombre']
            ];
        }, is_array($rows) ? $rows : []);
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function listarTiposEvento()
    {
        $data = $this->model->listarTiposEvento();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

 

public function registrar()
{
    header('Content-Type: application/json; charset=UTF-8');

    // Arma el payload tal como lo envía tu <form> (names esperados)
    $evento = [
        'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
        'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
        'fecha'                      => trim($_POST['fecha'] ?? ''),          // 'YYYY-MM-DD' o datetime-local
        'comentario'                 => trim($_POST['comentario'] ?? ''),

        // Solo uno de los dos (el modelo decidirá y validará)
        'contenedor_operacion_id'    => isset($_POST['contenedor_operacion_id']) && $_POST['contenedor_operacion_id'] !== ''
                                        ? (int)$_POST['contenedor_operacion_id'] : null,   // FÍSICO
        'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) && $_POST['cont_maritimo_operacion_id'] !== ''
                                        ? (int)$_POST['cont_maritimo_operacion_id'] : null // MARÍTIMO
    ];

    $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

    // Igual que en tu ejemplo: el modelo encapsula validaciones e inserción
    // y devuelve un array ['status' => ..., 'msg' => ..., 'id' => ...]
    $res = $this->model->registrar($evento, $usuarioId);
    if($res)
        $res = ['status'=>'success','msg'=>'Evento registrado','id'=>$res];
        else
        $res = ['status'=>'error','msg'=>'No se registró el evento'];
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

 

public function editar($id)
{
    $data = $this->model->obtenerEvento($id); // método que debes crear en tu modelo
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}

    /* ==== Helper para responder JSON consistente ==== */
    private function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
