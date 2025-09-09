<?php
require_once "Models/Operaciones_maritimas_contenedoresModel.php";
require_once "Models/Operaciones_maritimas_costos_operacionModel.php";
require_once "Models/Operaciones_maritimas_eventosModel.php";
require_once "Models/Operaciones_maritimas_resumenModel.php";
class Operaciones_maritimas extends Controller
{
    private $contenedoresModel; 
    private $costos_OperacionModel;
    private $eventosModel; 
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
        // Modelo especializado para el tab de contenedores
           $this->contenedoresModel = new Operaciones_maritimas_contenedoresModel();
           $this->costos_OperacionModel = new Operaciones_maritimas_costos_operacionModel();
           $this->eventosModel = new Operaciones_maritimas_eventosModel(); 
    }

    
    // =================== VISTAS ===================
    public function ver()
    {
        $data['title']      = 'Operaciones Marítimas';
        $data['subtipos']   = $this->model->subtiposMaritimos();
        $data['estatus']    = $this->model->catalogoEstatus();
        $data['puertos']    = $this->model->catalogoPuertos();   
        $data['navieras']   = $this->model->catalogoNavieras();
        $data['forwarders'] = $this->model->catalogoForwarders();


        // Catálogos del tab “Contenedores en Operación”
        $data['ops']       = $this->contenedoresModel->catalogoOperaciones();
        $data['fisicos']   = $this->contenedoresModel->catalogoContenedoresFisicos();
        $data['shippers']  = $this->contenedoresModel->catalogoShippers();


        // Catálogos del tab “Costos por Operación” 
        $data['tiposMovimiento'] = $this->costos_OperacionModel->obtenerTiposMovimientoActivos();

 

         

        $this->views->getView('admin/operaciones_maritimas/', "ver", $data);
    }

 

    // =================== API ===================

// LISTAR (tabla principal) con paginación server-side
public function listar()
{
    header('Content-Type: application/json; charset=UTF-8');

    // Filtros (misma lógica que ya usabas)
    $filters = [
        'subtipo_id' => (isset($_GET['subtipo_id']) && $_GET['subtipo_id'] !== '')
                        ? (int)$_GET['subtipo_id']
                        : ((isset($_GET['filtroSubtipo']) && $_GET['filtroSubtipo'] !== '')
                           ? (int)$_GET['filtroSubtipo'] : 0),
        'term'       => trim($_GET['term'] ?? '')
    ];

    // Parámetros de paginación
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['per_page'] ?? 10);

    // Solo permitir ciertos tamaños (evita abusos y consultas pesadas)
    $allowedPerPage = [10, 25, 50, 100];
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

    try {
        // Nuevo método del modelo con tu misma lógica + LIMIT/OFFSET
        $result = $this->model->listarPaginado($filters, $page, $perPage);

        echo json_encode([
            'status' => 'success',
            'data'   => $result['rows'],  // filas de la página solicitada
            'meta'   => [
                'total'       => $result['total'],
                'page'        => $result['page'],
                'per_page'    => $result['per_page'],
                'total_pages' => $result['total_pages'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        // Manejo simple de errores (útil en dev)
        echo json_encode([
            'status' => 'error',
            'msg'    => 'Error al listar operaciones',
            'error'  => $e->getMessage(), // en producción puedes omitir este detalle
        ], JSON_UNESCAPED_UNICODE);
    }
    die();
}


    // Autocomplete clientes
    public function buscar_clientes()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        echo json_encode($term === '' ? [] : $this->model->buscarClientes($term), JSON_UNESCAPED_UNICODE);
        die();
    }

    // Autocomplete contenedores
    public function buscar_contenedores()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        echo json_encode($term === '' ? [] : $this->model->buscarContenedores($term), JSON_UNESCAPED_UNICODE);
        die();
    }

    // REGISTRAR (sin movimientos_logisticos)
    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $op = [
        'numero_operacion'     => trim($_POST['numero_operacion'] ?? ''),
        'tipo_operacion_id'    => 1,
        'subtipo_operacion_id' => (int)($_POST['subtipo_operacion_id'] ?? 0),
        'etd'                  => $_POST['etd'] ?? null,
        'eta'                  => $_POST['eta'] ?? null,
        'numero_bl'            => trim($_POST['numero_bl'] ?? ''),
        'cliente_id'           => (int)($_POST['cliente_id'] ?? 0),
        'estatus_id'           => (int)($_POST['estatus_id'] ?? 9),
        'naviera_id'           => (int)($_POST['naviera_id'] ?? 0),
        'forwarder_id'         => (int)($_POST['forwarder_id'] ?? 0),
        'shipper_id'           => (int)($_POST['shipper_id'] ?? 0), // NUEVO
        'notas'                => trim($_POST['notas'] ?? ''),
        ];

    if (!empty($_POST['contenedores'])) {
        $tmp = json_decode($_POST['contenedores'], true);
        if (is_array($tmp)) $contenedores = $tmp;
    }

    // === VALIDACIONES REQUERIDAS ===
    if (empty($contenedores)) {
        echo json_encode(['status' => 'warning', 'msg' => 'Agrega al menos un contenedor a la operación.']);
        die();
    }

    if ($op['forwarder_id'] <= 0 ) {
        echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un forwarder válido.']);
        die();
    }

    if ($op['shipper_id'] <= 0 ) {
        echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un shipper válido.']);
        die();
    }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

        // 👇 Modelo ya NO recibe puerto_arribo_id
        $res = $this->model->insertarOperacion($op, $contenedores, $usuarioId);

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // EDITAR: datos para llenar modal
    public function editar($id)
    {
        $id = (int)$id;
        if ($id <= 0) { echo json_encode(['status'=>'error','msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE); die(); }

        $op = $this->model->getOperacionById($id);
        if (!$op) { echo json_encode(['status'=>'error','msg'=>'No encontrado'], JSON_UNESCAPED_UNICODE); die(); }

        $contenedores = $this->model->getContenedoresByOperacion($id);
        echo json_encode(['status'=>'success','op'=>$op,'contenedores'=>$contenedores], JSON_UNESCAPED_UNICODE);
        die();
    }

    // ELIMINAR: baja lógica
    public function eliminar($id)
    {
        $id = (int)$id;
        if ($id <= 0) { echo json_encode(['status'=>'error','msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE); die(); }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        $res = $this->model->desactivarOperacion($id, $usuarioId);

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function obtener()
{
    header('Content-Type: application/json; charset=UTF-8');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode([
            'status' => 'error',
            'msg'    => 'ID inválido'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    try {
        $op = $this->model->obtenerOperacion($id);
        if (!$op) {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'Operación no encontrada'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // Si implementaste el método en el modelo:
        $contenedores = [];
        if (method_exists($this->model, 'obtenerContenedoresOperacion')) {
            $contenedores = $this->model->obtenerContenedoresOperacion($id);
        }

        echo json_encode([
            'status'       => 'success',
            'operacion'    => $op,
            'contenedores' => $contenedores, // [] si no hay
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'msg'    => 'Error al obtener operación',
            'error'  => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    die();
}


// POST /Operaciones_maritimas/actualizar
public function actualizar()
{
    header('Content-Type: application/json; charset=UTF-8');

    // Soportar application/json además de form-urlencoded
    $input = file_get_contents('php://input');
    if ($input && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $json = json_decode($input, true);
        if (is_array($json)) {
            // Merge JSON en $_POST sin pisar claves existentes
            $_POST = array_merge($_POST, $json);
        }
    }
    // Sanitizado (máx 300 chars por tu schema)
    $notas = trim((string)($_POST['notas'] ?? ''));
    if ($notas !== '') $notas = mb_substr($notas, 0, 300, 'UTF-8'); else $notas = null;
    // Sanitizado básico
    $payload = [
        'id_operacion'         => (int)($_POST['id_operacion'] ?? 0),
        'subtipo_operacion_id' => (int)($_POST['subtipo_operacion_id'] ?? 0),
        'numero_operacion'     => trim((string)($_POST['numero_operacion'] ?? '')),
        'estatus_id'           => (int)($_POST['estatus_id'] ?? 0),
        'etd'                  => trim((string)($_POST['etd'] ?? '')),
        'eta'                  => trim((string)($_POST['eta'] ?? '')),
        'numero_bl'            => trim((string)($_POST['numero_bl'] ?? '')),
        'cliente_id'           => ($_POST['cliente_id']   ?? '') !== '' ? (int)$_POST['cliente_id']   : null,
        'naviera_id'           => ($_POST['naviera_id']   ?? '') !== '' ? (int)$_POST['naviera_id']   : null,
        'forwarder_id'         => ($_POST['forwarder_id'] ?? '') !== '' ? (int)$_POST['forwarder_id'] : null,
        'shipper_id'           => ($_POST['shipper_id']   ?? '') !== '' ? (int)$_POST['shipper_id']   : null,  
        'notas'                => $notas,
    ];

    // Validaciones mínimas
    if ($payload['id_operacion'] <= 0) {
        echo json_encode(['status'=>'error','msg'=>'ID de operación requerido'], JSON_UNESCAPED_UNICODE); die();
    }
    if ($payload['subtipo_operacion_id'] <= 0) {
        echo json_encode(['status'=>'error','msg'=>'Subtipo requerido'], JSON_UNESCAPED_UNICODE); die();
    }
    if ($payload['estatus_id'] <= 0) {
        echo json_encode(['status'=>'error','msg'=>'Estatus requerido'], JSON_UNESCAPED_UNICODE); die();
    }
    if ($payload['numero_operacion'] === '') {
        echo json_encode(['status'=>'error','msg'=>'Número de operación requerido'], JSON_UNESCAPED_UNICODE); die();
    }

    try {
        $ok = $this->model->actualizarOperacion($payload);

        if (!$ok) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo actualizar la operación'], JSON_UNESCAPED_UNICODE);
            die();
        }

        // Si quieres devolver la operación actualizada (para repintar modal sin reconsultar)
        $actualizada = $this->model->obtenerOperacion($payload['id_operacion']);

        echo json_encode([
            'status'     => 'success',
            'msg'        => 'Operación actualizada',
            'operacion'  => $actualizada
        ], JSON_UNESCAPED_UNICODE);

    } catch (\Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'msg'    => 'Error al actualizar operación',
            'error'  => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    die();
}
public function buscar_shippers() {
  $term = isset($_GET['term']) ? trim($_GET['term']) : '';
  echo json_encode($term === '' ? [] : $this->model->buscarShippers($term), JSON_UNESCAPED_UNICODE);
  die();
}
public function siguiente_codigo() {
  header('Content-Type: application/json; charset=UTF-8');
  $subtipoId = (int)($_GET['subtipo_id'] ?? 0);
  if ($subtipoId <= 0) { echo json_encode([]); die(); }

  $prev = $this->model->previewCodigoSubtipo($subtipoId);
  echo json_encode($prev ?: []);
  die();
}


}
