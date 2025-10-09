<?php
require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimo_ferro_eventos_mar extends Controller
{
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->opLog = new OperacionesLogModel();
    }
 public function listar()
{
    header('Content-Type: application/json; charset=UTF-8');

    // Parámetros de query (con defaults)
    $page    = isset($_GET['page'])     ? max(1, (int)$_GET['page'])       : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 10;
    
    // Filtros opcionales
    $opId    = (isset($_GET['op_id'])   && $_GET['op_id']   !== '') ? (int)$_GET['op_id']   : null;
    $contId  = (isset($_GET['cont_id']) && $_GET['cont_id'] !== '') ? (int)$_GET['cont_id'] : null; // cmo.id
    $q       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

    try {
        // SOLO MF (11) + estatus=1 lo resuelve el modelo
        $res = $this->model->listarEventosMFPaginado($page, $perPage, $opId, $contId, $q);

        echo json_encode([
            'data'     => $res['rows']     ?? [],
            'total'    => (int)($res['total']    ?? 0),
            'page'     => (int)($res['page']     ?? $page),
            'per_page' => (int)($res['per_page'] ?? $perPage)
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        error_log('listar MF eventos: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'data'     => [],
            'total'    => 0,
            'page'     => $page,
            'per_page' => $perPage,
            'error'    => 'No fue posible obtener el listado.'
        ], JSON_UNESCAPED_UNICODE);
    }
    die();
}

public function eventos_maritimos_columnas()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        // Del modelo: listarEventosMaritimosParaColumnas()
        $rows = $this->model->listarEventosMaritimosParaColumnas();

        // Normalizamos salida a [{id, nombre, key}]
        $out = array_map(function ($r) {
            return [
                'id'     => (int)($r['id'] ?? $r['id_tipo_evento'] ?? 0),
                'nombre' => (string)($r['nombre'] ?? ''),
                'key'    => (string)($r['key'] ?? '')
            ];
        }, is_array($rows) ? $rows : []);

        echo json_encode([
            'ok'       => true,
            'count'    => count($out),
            'columns'  => $out
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        error_log('eventos_maritimos_columnas: '.$e->getMessage());
        http_response_code(500);
        echo json_encode([
            'ok'    => false,
            'error' => 'No fue posible obtener las columnas de eventos marítimos'
        ], JSON_UNESCAPED_UNICODE);
    }
    die();
}
/* =============================================================
   AUTOCOMPLETE: OPERACIONES MF (id_tipo_operacion = 11)
   GET ?term=LBMF-01[&limit=10]
   Respuesta: [{id, label, meta}]
   ============================================================= */
public function buscar_operaciones()
{
    header('Content-Type: application/json; charset=UTF-8');

    $term  = isset($_GET['term']) ? trim($_GET['term']) : '';
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

    if ($term === '') { echo json_encode([], JSON_UNESCAPED_UNICODE); die(); }

    try {
        $rows = $this->model->buscarOperacionesMaritimoFerro($term, $limit);
        $out  = array_map(function ($r) {
            return [
                'id'    => (int)($r['id'] ?? 0),
                'label' => (string)($r['label'] ?? ''),
                'meta'  => isset($r['maritimos']) ? ($r['maritimos'] . ' contenedor(es)') : ''
            ];
        }, is_array($rows) ? $rows : []);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        error_log('buscar_operaciones MF: '.$e->getMessage());
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/* =============================================================
   AUTOCOMPLETE: CONTENEDORES MARÍTIMOS DE UNA OPERACIÓN MF
   GET ?operacion_id=123[&term=MGU…&limit=15]
   Respuesta: [{id,label,tipo}]  // id = cmo.id
   ============================================================= */
public function buscar_contenedores()
{
    header('Content-Type: application/json; charset=UTF-8');

    $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
    $term        = isset($_GET['term']) ? trim($_GET['term']) : '';
    $limit       = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;

    if ($operacionId <= 0) { echo json_encode([], JSON_UNESCAPED_UNICODE); die(); }

    try {
        $rows = $this->model->buscarContenedoresMarDeOperacion($operacionId, $term, $limit);

        $out = array_map(function ($r) {
            return [
                'id'    => (int)($r['id'] ?? 0),         // cmo.id
                'label' => (string)($r['label'] ?? ''),  // cm.numero_contenedor
                'tipo'  => (string)($r['tipo'] ?? 'MARITIMO')
            ];
        }, is_array($rows) ? $rows : []);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        error_log('buscar_contenedores MF: '.$e->getMessage());
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
    die();
}
public function contenedor_maritimo_de_operacion()
{
    header('Content-Type: application/json; charset=UTF-8');
    $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
    if ($opId <= 0) { echo json_encode(null, JSON_UNESCAPED_UNICODE); die(); }

    $row = $this->model->getContenedorMaritimoDeOperacion($opId);
    echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
    die();
}

/* =============================================================
   CATÁLOGO DE TIPOS DE EVENTO (MARÍTIMOS)
   GET (sin params) -> [{id, nombre}]
   ============================================================= */
public function tipos_evento()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $rows = $this->model->listarTiposEventoMaritimo();
        $out  = array_map(function ($r) {
            return [
                'id'     => (int)($r['id_tipo_evento'] ?? 0),
                'nombre' => (string)($r['nombre'] ?? '')
            ];
        }, is_array($rows) ? $rows : []);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        error_log('tipos_evento (marítimos): '.$e->getMessage());
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
    die();
}

 
// === Helpers de auditoría (si no los tienes en este archivo) ===
private function logOp(int $operacionId, string $accion, string $descripcion): void
{
    try {
        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
        if (!$id) { error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}"); }
    } catch (\Throwable $e) {
        error_log("operaciones_log error: ".$e->getMessage());
    }
}
private function makeDesc(string $base, array $info = []): string
{
    if (empty($info)) return $base;
    $kv = [];
    foreach ($info as $k => $v) { $kv[] = "$k=$v"; }
    return $base.' ('.implode(', ', $kv).')';
}

// ================== REGISTRAR EVENTO (MF=11 con eventos marítimos) ==================
public function registrar()
{
    header('Content-Type: application/json; charset=UTF-8');

    // Los nombres vienen del JS (FormData):
    // operacion_id, cont_maritimo_operacion_id, tipo_evento_id, fecha, comentario
    $evento = [
        'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
        'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) ? (int)$_POST['cont_maritimo_operacion_id'] : 0,
        'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
        'fecha'                      => trim($_POST['fecha'] ?? ''),
        'comentario'                 => trim($_POST['comentario'] ?? '')
    ];

    // Validaciones mínimas antes de ir al modelo (para dar mensajes útiles)
    if ($evento['operacion_id'] <= 0) {
        echo json_encode(['status' => 'warning', 'msg' => 'Selecciona una operación (MF).']); die();
    }
    if ($evento['cont_maritimo_operacion_id'] <= 0) {
        echo json_encode(['status' => 'warning', 'msg' => 'No hay contenedor marítimo ligado a la operación.']); die();
    }
    if ($evento['tipo_evento_id'] <= 0) {
        echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un tipo de evento marítimo.']); die();
    }
    if ($evento['fecha'] === '') {
        echo json_encode(['status' => 'warning', 'msg' => 'Indica la fecha del evento.']); die();
    }

    $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
    $id = $this->model->registrar($evento, $usuarioId);

    if ($id > 0) {
        // LOG de creación
        $desc = $this->makeDesc('Evento creado', [
            'id_evento'   => $id,
            'operacion'   => $evento['operacion_id'],
            'cmo'         => $evento['cont_maritimo_operacion_id'],
            'tipo_evt_id' => $evento['tipo_evento_id'],
            'fecha'       => $evento['fecha']
        ]);
        $this->logOp($evento['operacion_id'], 'creacion', $desc);

        echo json_encode(['status' => 'success', 'msg' => 'Evento registrado', 'id' => $id]);
    } else {
        // El modelo retorna 0 si: op no es MF=11, CMO no pertenece/activo, tipo evento no es marítimo/activo o duplicado
        echo json_encode([
            'status' => 'error',
            'msg'    => 'No fue posible registrar el evento. Verifica: operación MF, contenedor marítimo activo, tipo de evento marítimo y que no sea duplicado.'
        ]);
    }
    die();
}

public function obtener_por_clave()
{
    header('Content-Type: application/json; charset=UTF-8');

    $opId  = (int)($_GET['operacion_id'] ?? 0);
    $cmoId = (int)($_GET['cont_maritimo_operacion_id'] ?? 0);
    $evtId = (int)($_GET['tipo_evento_id'] ?? 0);

    if ($opId<=0 || $cmoId<=0 || $evtId<=0) {
        echo json_encode(null, JSON_UNESCAPED_UNICODE); die();
    }

    $row = $this->model->obtenerEventoPorClave($opId, $cmoId, $evtId);
    echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
    die();
}
public function actualizar()
{
    header('Content-Type: application/json; charset=UTF-8');

    $evento = [
        'id_evento'                  => (int)($_POST['id_evento'] ?? 0),
        'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
        'cont_maritimo_operacion_id' => (int)($_POST['cont_maritimo_operacion_id'] ?? 0),
        'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
        'fecha'                      => trim($_POST['fecha'] ?? ''),
        'comentario'                 => trim($_POST['comentario'] ?? '')
    ];

    if ($evento['id_evento'] <= 0) { echo json_encode(['status'=>'warning','msg'=>'Falta id_evento']); die(); }
    if ($evento['operacion_id'] <= 0) { echo json_encode(['status'=>'warning','msg'=>'Selecciona una operación (MF).']); die(); }
    if ($evento['cont_maritimo_operacion_id'] <= 0) { echo json_encode(['status'=>'warning','msg'=>'No hay contenedor marítimo ligado a la operación.']); die(); }
    if ($evento['tipo_evento_id'] <= 0) { echo json_encode(['status'=>'warning','msg'=>'Selecciona un tipo de evento marítimo.']); die(); }
    if ($evento['fecha'] === '') { echo json_encode(['status'=>'warning','msg'=>'Indica la fecha del evento.']); die(); }

    try {
        $ok = $this->model->actualizar($evento);
        if ($ok) {
            // LOG opcional
            $desc = $this->makeDesc('Evento actualizado', [
                'id_evento'   => $evento['id_evento'],
                'operacion'   => $evento['operacion_id'],
                'cmo'         => $evento['cont_maritimo_operacion_id'],
                'tipo_evt_id' => $evento['tipo_evento_id'],
                'fecha'       => $evento['fecha']
            ]);
            $this->logOp($evento['operacion_id'], 'actualizacion', $desc);

            echo json_encode(['status'=>'success','msg'=>'Evento actualizado']);
        } else {
            echo json_encode(['status'=>'error','msg'=>'No fue posible actualizar. Verifica MF, CMO activo/pertenencia, tipo marítimo y que no sea duplicado.']);
        }
    } catch (\Throwable $e) {
        error_log('actualizar evento MF: '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['status'=>'error','msg'=>'Error interno al actualizar.']);
    }
    die();
}

public function eliminar()
{
    header('Content-Type: application/json; charset=UTF-8');

    $id = (int)($_POST['id_evento'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'warning','msg'=>'Falta id_evento']); die(); }

    try {
        $ok = $this->model->eliminar($id);
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Evento eliminado' : 'No se pudo eliminar'
        ]);
    } catch (\Throwable $e) {
        error_log('eliminar evento MF: '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['status'=>'error','msg'=>'Error interno al eliminar.']);
    }
    die();
}

}
