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

    $evento = [
        'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
        'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
        'fecha'                      => trim($_POST['fecha'] ?? ''),
        'comentario'                 => trim($_POST['comentario'] ?? ''),
        'contenedor_operacion_id'    => isset($_POST['contenedor_operacion_id']) && $_POST['contenedor_operacion_id'] !== '' ? (int)$_POST['contenedor_operacion_id'] : null,
        'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) && $_POST['cont_maritimo_operacion_id'] !== '' ? (int)$_POST['cont_maritimo_operacion_id'] : null,
    ];

    if ($evento['operacion_id'] <= 0 || $evento['tipo_evento_id'] <= 0 || $evento['fecha'] === '') {
        echo json_encode(['status' => 'warning', 'msg' => 'Faltan campos requeridos (operación, tipo de evento o fecha)']);
        die();
    }

    // === Validación duplicado muy simple ===
    if (!empty($evento['contenedor_operacion_id'])) {
        if ($this->model->existeEventoFisicoDuplicado($evento['contenedor_operacion_id'], $evento['tipo_evento_id'])) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor ya tiene ese tipo de evento (activo).']);
            die();
        }
    } elseif (!empty($evento['cont_maritimo_operacion_id'])) {
        if ($this->model->existeEventoMaritimoDuplicado($evento['cont_maritimo_operacion_id'], $evento['tipo_evento_id'])) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor marítimo ya tiene ese tipo de evento (activo).']);
            die();
        }
    }

    $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
    $id = $this->model->registrar($evento, $usuarioId);

    echo json_encode($id
        ? ['status' => 'success', 'msg' => 'Evento registrado', 'id' => $id]
        : ['status' => 'error',   'msg' => 'No se registró el evento']
    );
    die();
}


 

public function editar($id)
{
    $data = $this->model->obtenerEvento($id); // método que debes crear en tu modelo
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}
public function actualizar()
{
    header('Content-Type: application/json; charset=UTF-8');

    $data = [
        'id_evento'                  => (int)($_POST['id_evento'] ?? 0),
        'operacion_id'               => (int)($_POST['operacion_id'] ?? 0), // será reemplazado
        'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
        'fecha'                      => trim($_POST['fecha'] ?? ''),
        'comentario'                 => trim($_POST['comentario'] ?? ''),
        'contenedor_operacion_id'    => isset($_POST['contenedor_operacion_id']) && $_POST['contenedor_operacion_id'] !== '' ? (int)$_POST['contenedor_operacion_id'] : null, // será reemplazado
        'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) && $_POST['cont_maritimo_operacion_id'] !== '' ? (int)$_POST['cont_maritimo_operacion_id'] : null, // será reemplazado
    ];

    if ($data['id_evento'] <= 0 || $data['tipo_evento_id'] <= 0 || $data['fecha'] === '') {
        echo json_encode(['status' => 'warning', 'msg' => 'Datos incompletos']);
        die();
    }

    // === Forzar operación/contenedor desde el registro original (inmutables en edición) ===
    $orig = $this->model->obtenerEvento($data['id_evento']);
    if (!$orig) {
        echo json_encode(['status' => 'warning', 'msg' => 'Evento no encontrado']);
        die();
    }
    $data['operacion_id']               = (int)$orig['operacion_id'];
    $data['contenedor_operacion_id']    = $orig['contenedor_operacion_id'] !== null ? (int)$orig['contenedor_operacion_id'] : null;
    $data['cont_maritimo_operacion_id'] = $orig['cont_maritimo_operacion_id'] !== null ? (int)$orig['cont_maritimo_operacion_id'] : null;

    // === Validación de duplicado con los valores fijados
    if (!empty($data['contenedor_operacion_id'])) {
        if ($this->model->existeEventoFisicoDuplicado($data['contenedor_operacion_id'], $data['tipo_evento_id'], $data['id_evento'])) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor ya tiene ese tipo de evento (activo).']);
            die();
        }
    } elseif (!empty($data['cont_maritimo_operacion_id'])) {
        if ($this->model->existeEventoMaritimoDuplicado($data['cont_maritimo_operacion_id'], $data['tipo_evento_id'], $data['id_evento'])) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor marítimo ya tiene ese tipo de evento (activo).']);
            die();
        }
    }

    $ok = $this->model->actualizar($data);

    echo json_encode($ok
        ? ['status' => 'success', 'msg' => 'Evento actualizado']
        : ['status' => 'error',   'msg' => 'No se actualizó el evento']
    );
    die();
}
public function eliminar()
{
    header('Content-Type: application/json; charset=UTF-8');

    $id = (int)($_POST['id_evento'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'warning', 'msg' => 'ID inválido']);
        die();
    }

    // (Opcional) podrías validar existencia:
    // $evt = $this->model->obtenerEvento($id);
    // if (!$evt) { echo json_encode(['status'=>'warning','msg'=>'Evento no encontrado']); die(); }

    $ok = $this->model->desactivar($id);

    echo json_encode($ok
        ? ['status' => 'success', 'msg' => 'Evento eliminado']
        : ['status' => 'error',   'msg' => 'No se pudo eliminar']
    );
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
