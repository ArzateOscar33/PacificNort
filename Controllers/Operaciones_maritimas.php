<?php
require_once "Models/Operaciones_maritimas_contenedoresModel.php";
require_once "Models/Operaciones_maritimas_costos_operacionModel.php";
require_once "Models/Operaciones_maritimas_eventosModel.php";
require_once "Models/Operaciones_maritimas_resumenModel.php";
require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimas extends Controller
{
    private $contenedoresModel; 
    private $costos_OperacionModel;
    private $eventosModel; 
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
        // Modelos
        $this->contenedoresModel   = new Operaciones_maritimas_contenedoresModel();
        $this->costos_OperacionModel = new Operaciones_maritimas_costos_operacionModel();
        $this->eventosModel        = new Operaciones_maritimas_eventosModel(); 
        $this->opLog               = new OperacionesLogModel();
    }

    // Helper de auditoría
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

    // =================== VISTAS ===================
    public function ver()
    {
        $data['title']      = 'Operaciones Marítimas';
        $data['subtipos']   = $this->model->subtiposMaritimos();
        $data['estatus']    = $this->model->catalogoEstatus();
        $data['puertos']    = $this->model->catalogoPuertos();   
        $data['navieras']   = $this->model->catalogoNavieras();
        $data['forwarders'] = $this->model->catalogoForwarders();

        // Tab Contenedores en Operación
        $data['ops']       = $this->contenedoresModel->catalogoOperaciones();
        $data['fisicos']   = $this->contenedoresModel->catalogoContenedoresFisicos();
        $data['shippers']  = $this->contenedoresModel->catalogoShippers();

        // Tab Costos por Operación
        $data['tiposMovimiento'] = $this->costos_OperacionModel->obtenerTiposMovimientoActivos();

        $this->views->getView('admin/operaciones_maritimas/', "ver", $data);
    }

    // =================== API ===================

    // LISTAR (tabla principal)
public function listar()
{
    header('Content-Type: application/json; charset=UTF-8');

    // 1) Recolecta filtros de la vista (GET)
    $filters = [
        'subtipo_id'   => (isset($_GET['subtipo_id']) && $_GET['subtipo_id'] !== '')
                          ? (int)$_GET['subtipo_id']
                          : ((isset($_GET['filtroSubtipo']) && $_GET['filtroSubtipo'] !== '')
                             ? (int)$_GET['filtroSubtipo'] : 0),
        'term'         => trim($_GET['term'] ?? ''),

        // ⬇️ NUEVO: fechas desde tus inputs <input id="filtroFechaInicio"> y <input id="filtroFechaFin">
        'fecha_inicio' => isset($_GET['filtroFechaInicio']) ? trim($_GET['filtroFechaInicio']) : '',
        'fecha_fin'    => isset($_GET['filtroFechaFin'])    ? trim($_GET['filtroFechaFin'])    : '',
        // Nota: el modelo filtra por DATE(o.eta). Si luego quieres ETD, ajusta en el modelo.
    ];

    // 2) Paginación
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['per_page'] ?? 10);
    $allowedPerPage = [10, 25, 50, 100];
    if (!in_array($perPage, $allowedPerPage, true)) { $perPage = 10; }

    try {
        // 3) Llama al modelo con los filtros (ya incluye fechas)
        $result = $this->model->listarPaginado($filters, $page, $perPage);

        echo json_encode([
            'status' => 'success',
            'data'   => $result['rows'],
            'meta'   => [
                'total'       => $result['total'],
                'page'        => $result['page'],
                'per_page'    => $result['per_page'],
                'total_pages' => $result['total_pages'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'msg'    => 'Error al listar operaciones',
            'error'  => $e->getMessage(),
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
            'shipper_id'           => (int)($_POST['shipper_id'] ?? 0),
            'notas'                => trim($_POST['notas'] ?? ''),
        ];

        $contenedores = [];
        if (!empty($_POST['contenedores'])) {
            $tmp = json_decode($_POST['contenedores'], true);
            if (is_array($tmp)) $contenedores = $tmp;
        }

        // Validaciones
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
        $res = $this->model->insertarOperacion($op, $contenedores, $usuarioId);

        // LOG: operación creada
        if (is_array($res) && ($res['status'] ?? '') === 'success') {
            // Detectar id de operación según lo que devuelva tu modelo
            $opId = (int)($res['operacion_id'] ?? $res['id_operacion'] ?? $res['id'] ?? 0);
            if ($opId > 0) {
                $nCont = is_array($contenedores) ? count($contenedores) : 0;
                $desc  = "Operación creada (num='{$op['numero_operacion']}', subtipo_id={$op['subtipo_operacion_id']}, contenedores={$nCont})";
                $this->logOp($opId, 'creacion', $desc);
            }
        }

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

        // LOG: operación desactivada (cancelación)
        if (is_array($res) && ($res['status'] ?? '') === 'success') {
            $motivo = trim((string)($res['motivo'] ?? 'baja lógica'));
            $this->logOp($id, 'cancelacion', "Operación desactivada ({$motivo})");
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function obtener()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status'=>'error','msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $op = $this->model->obtenerOperacion($id);
            if (!$op) {
                echo json_encode(['status'=>'error','msg'=>'Operación no encontrada'], JSON_UNESCAPED_UNICODE);
                die();
            }

            $contenedores = [];
            if (method_exists($this->model, 'obtenerContenedoresOperacion')) {
                $contenedores = $this->model->obtenerContenedoresOperacion($id);
            }

            echo json_encode([
                'status'       => 'success',
                'operacion'    => $op,
                'contenedores' => $contenedores,
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

        // Soportar JSON
        $input = file_get_contents('php://input');
        if ($input && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $json = json_decode($input, true);
            if (is_array($json)) {
                $_POST = array_merge($_POST, $json);
            }
        }

        $notas = trim((string)($_POST['notas'] ?? ''));
        $notas = ($notas !== '') ? mb_substr($notas, 0, 300, 'UTF-8') : null;

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

            // LOG: operación actualizada
            $desc = "Operación actualizada (num='{$payload['numero_operacion']}', estatus_id={$payload['estatus_id']})";
            $this->logOp($payload['id_operacion'], 'actualizacion', $desc);
            // ¿Se finalizó sin entrega?
            $opId      = (int)$payload['id_operacion'];
            $nuevoEst  = (int)$payload['estatus_id'];
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $warning = null;
            if ($nuevoEst === 7 /* id Finalizada */) {
                $hayEntrega = $this->eventosModel->existeEventoEntregaOperacion($opId);
                if (!$hayEntrega) {
                    $warning = 'La operación fue marcada como FINALIZADA pero no existe evento de ENTREGA. Captúralo para cerrar correctamente.';
                }
            }
            // Devolver la operación actualizada
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
