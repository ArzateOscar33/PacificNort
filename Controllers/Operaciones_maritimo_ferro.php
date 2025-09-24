<?php
include_once 'Models/Operaciones_maritimo_ferro_contenedoresModel.php';
class Operaciones_maritimo_ferro extends Controller
{
    private $contenedoresModel;
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
       $this->contenedoresModel               = new Operaciones_maritimo_ferro_contenedoresModel();
    }

    /* ================================
       ==========  VISTAS  ============
       ================================ */

    /** Vista principal: carga catálogos que tu vista usa (subtipos, estatus) */
    public function index()
    {
        $data['title']      = 'Operaciones Marítimo-Ferroviarias';  
        $this->views->getView($this, "Operaciones_maritimo_ferro", $data);
    }


    /* ==========================================
       ==========  LISTADO / PAGINACIÓN  =========
       ========================================== */

    /** GET /operaciones_maritimo_ferro/listar_operaciones */
    public function listar_operaciones()
    {
        // Filtros que manda tu vista (ids con prefijo maritimo_ferro_)
        $subtipoId   = isset($_GET['maritimo_ferro_filtroSubtipo']) ? (int)$_GET['maritimo_ferro_filtroSubtipo'] : 0;
        $term        = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['maritimo_ferro_buscarOperacion']) ? trim($_GET['maritimo_ferro_buscarOperacion']) : '');
        $fechaInicio = isset($_GET['maritimo_ferro_fechaInicio']) ? trim($_GET['maritimo_ferro_fechaInicio']) : '';
        $fechaFin    = isset($_GET['maritimo_ferro_fechaFin'])    ? trim($_GET['maritimo_ferro_fechaFin'])    : '';
        $page        = isset($_GET['page'])        ? (int)$_GET['page']        : 1;
        $perPage     = isset($_GET['perPage'])     ? (int)$_GET['perPage']     : (isset($_GET['maritimo_ferro_perPage']) ? (int)$_GET['maritimo_ferro_perPage'] : 10);

        if ($page < 1) $page = 1;
        $allowedPer = [10, 25, 50, 100, 200];
        if (!in_array($perPage, $allowedPer, true)) $perPage = 10;

        $filters = [
            'filtroSubtipo' => $subtipoId,
            'term'          => mb_strtolower($term, 'UTF-8'),
            'fecha_inicio'  => $fechaInicio,
            'fecha_fin'     => $fechaFin,
            // Si quisieras filtrar solo ferro desde UI: 'tipo' => 11
        ];

        $res = $this->model->listarPaginado($filters, $page, $perPage);
        $rows = $res['rows'] ?? [];
        $total = (int)($res['total'] ?? 0);
        $pp = (int)($res['per_page'] ?? $perPage);
        $pg = (int)($res['page'] ?? $page);
        $totalPages = (int)($res['total_pages'] ?? 1);
        $from = ($total > 0) ? (($pg - 1) * $pp + 1) : 0;
        $to   = ($total > 0) ? min($total, $pg * $pp) : 0;

        $paginationHtml = $this->buildPaginationHtml($totalPages, $pg);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data'            => $rows,           // la vista arma las columnas
            'from'            => $from,
            'to'              => $to,
            'total'           => $total,
            'page'            => $pg,
            'per_page'        => $pp,
            'total_pages'     => $totalPages,
            'pagination_html' => $paginationHtml,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function buildPaginationHtml(int $totalPages, int $currentPage): string
    {
        if ($totalPages <= 1) return '';
        $html = '<li class="page-item' . ($currentPage <= 1 ? ' disabled' : '') . '">
                   <a class="page-link" href="#" data-page="' . max(1, $currentPage - 1) . '">&laquo;</a>
                 </li>';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i === $currentPage) ? ' active' : '';
            $html .= '<li class="page-item' . $active . '">
                        <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
                      </li>';
        }
        $html .= '<li class="page-item' . ($currentPage >= $totalPages ? ' disabled' : '') . '">
                    <a class="page-link" href="#" data-page="' . min($totalPages, $currentPage + 1) . '">&raquo;</a>
                  </li>';
        return $html;
    }



    /** GET /operaciones_maritimo_ferro/autocomplete_clientes?q= */
    public function autocomplete_clientes()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $data = ($q === '') ? [] : $this->model->buscarClientes($q);
        $this->jsonOk($data);
    }

    /** GET /operaciones_maritimo_ferro/buscar_contenedores_mar?q= */
    public function buscar_contenedores_mar()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $data = ($q === '') ? [] : $this->model->buscarContenedoresMar($q);
        // Si quieres devolver bultos “sugeridos”, aquí podrías enriquecerlos
        $this->jsonOk($data);
    }

    /** GET /operaciones_maritimo_ferro/preview_folio?subtipo_id= */
    public function preview_folio()
    {
        $sid = isset($_GET['subtipo_id']) ? (int)$_GET['subtipo_id'] : 0;
        if ($sid <= 0) $this->jsonError('subtipo_id requerido');
        $prev = $this->model->previewCodigoSubtipo($sid);
        if (!$prev) $this->jsonError('No disponible');
        $this->jsonOk($prev);
    }

    /** GET /operaciones_maritimo_ferro/subtipo_info?id= */
    public function subtipo_info()
    {
        $sid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($sid <= 0) $this->jsonError('id requerido');
        $row = $this->model->getSubtipoFull($sid);
        if (!$row) $this->jsonError('No encontrado');
        // Devuelve también el puerto default para que la vista lo rellene
        $this->jsonOk([
            'id_subtipo'               => (int)$row['id_subtipo'],
            'tipo_operacion_id'        => (int)$row['tipo_operacion_id'],
            'nombre'                   => (string)$row['nombre'],
            'requiere_naviera'         => (int)$row['requiere_naviera'],
            'requiere_forwarder'       => (int)$row['requiere_forwarder'],
            'puerto_arribo_default_id' => $row['puerto_arribo_default_id'] ? (int)$row['puerto_arribo_default_id'] : null,
            'prefijo_codigo'           => (string)($row['prefijo_codigo'] ?? ''),
        ]);
    }

    /* =============================
       ========== ALTA Y EDICION ===
       ============================= */
       public function guardar()
{
    // Solo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $this->jsonError('Método no permitido', 405);
    }

    // ====== 1) Recibir y sanear ======
    $subtipoId = (int)($_POST['maritimo_ferro_subtipo'] ?? 0);
    if ($subtipoId <= 0) return $this->jsonError('Subtipo requerido', 400);

    // Si viene readonly el número, tu JS lo manda vacío para que el modelo lo genere
    $numeroOp  = trim((string)($_POST['maritimo_ferro_numeroOperacion'] ?? ''));

    $estatusId = (int)($_POST['maritimo_ferro_estatus'] ?? 9);
    $etd       = trim((string)($_POST['maritimo_ferro_etd'] ?? '')) ?: null;
    $eta       = trim((string)($_POST['maritimo_ferro_eta'] ?? '')) ?: null;

    // BL: deja solo alfanumérico como ya haces en el front
    $blRaw = (string)($_POST['maritimo_ferro_numeroBL'] ?? '');
    $bl    = preg_replace('/[^A-Za-z0-9]/', '', $blRaw) ?: null;

    $clienteId   = (int)($_POST['maritimo_ferro_clienteId'] ?? 0);
    $navieraId   = (int)($_POST['maritimo_ferro_navieraId'] ?? 0);
    $forwarderId = (int)($_POST['maritimo_ferro_forwarderId'] ?? 0);
    $shipperId   = (int)($_POST['maritimo_ferro_shipperId'] ?? 0);
    $notas       = trim((string)($_POST['maritimo_ferro_notas'] ?? '')) ?: null;

    // ====== 2) Armar payload para el modelo ======
    $op = [
        'numero_operacion'      => $numeroOp,          // '' => el modelo genera folio
        'subtipo_operacion_id'  => $subtipoId,
        'etd'                   => $etd,
        'eta'                   => $eta,
        'numero_bl'             => $bl,
        'cliente_id'            => $clienteId,
        'estatus_id'            => $estatusId,
        'naviera_id'            => $navieraId ?: null,
        'forwarder_id'          => $forwarderId ?: null,
        'shipper_id'            => $shipperId ?: null,
        'notas'                 => $notas,
    ];

    // ====== 3) Contenedores (ids / numeros / bultos) ======
    $ids   = $_POST['maritimo_ferro_contenedores_ids']     ?? [];
    $nums  = $_POST['maritimo_ferro_contenedores_numeros'] ?? [];
    $bults = $_POST['maritimo_ferro_contenedores_bultos']  ?? [];

    $contenedores = [];
    $n = max(count($ids), count($nums), count($bults));
    for ($i = 0; $i < $n; $i++) {
        $cid  = isset($ids[$i])   ? (int)$ids[$i]   : 0;
        $cnum = isset($nums[$i])  ? trim((string)$nums[$i]) : '';
        $cbul = isset($bults[$i]) && $bults[$i] !== '' ? (int)$bults[$i] : null;

        // ignora filas vacías
        if ($cid > 0 || $cnum !== '') {
            $contenedores[] = ['id' => $cid, 'numero' => $cnum, 'bultos' => $cbul];
        }
    }

    // ====== 4) Usuario (para bitácora) ======
    $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

    // ====== 5) Llamar modelo ======
    $res = $this->model->insertarOperacion($op, $contenedores, $usuarioId);

    // $res viene con ['status'=>..., 'msg'=>..., 'id_operacion'=>..., 'numero_operacion'=>...]
    if (!is_array($res) || ($res['status'] ?? 'error') !== 'success') {
        // Devuelve tal cual el mensaje del modelo (warning/error)
        $status = $res['status'] ?? 'error';
        $msg    = $res['msg']    ?? 'No se pudo guardar';
        // Si tienes jsonWarn/jsonError, usa el que aplique; aquí homogenizamos con jsonError
        return $this->jsonError($msg, 200); // 200 para que tu JS entre al branch y muestre Swal con msg
    }

    // ====== 6) Respuesta en el shape que espera tu JS ======
    // Tu JS lee res.data.numero_operacion o res.numero_operacion (soportemos el primero)
    return $this->jsonOk([
        'id_operacion'     => (int)$res['id_operacion'],
        'numero_operacion' => (string)$res['numero_operacion'],
        'msg'              => (string)($res['msg'] ?? 'Operación creada'),
    ]);
}

public function obtener_operacion()
{
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) return $this->jsonError('id requerido', 400);

    // Datos base de la operación (incluye info de subtipo/estatus/cliente, etc.)
    $op = $this->model->obtenerOperacion($id);
    if (!$op) return $this->jsonError('Operación no encontrada', 404);

    // (Opcional) Si quieres mandar contenedores para mostrarlos en edición (aunque estén readonly):
    if (method_exists($this->model, 'getContenedoresDeOperacion')) {
        $op['contenedores'] = $this->model->getContenedoresDeOperacion($id);
    } else {
        $op['contenedores'] = []; // si aún no implementas la lectura
    }

    return $this->jsonOk($op);
}
public function actualizar()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $this->jsonError('Método no permitido', 405);
    }

    $id = (int)($_POST['id_operacion_mf'] ?? 0);
    if ($id <= 0) return $this->jsonError('id_operacion requerido', 400);

    $actual = $this->model->getOperacionById($id);
    if (!$actual) return $this->jsonError('Operación no existe', 404);

    // --- payload base (sin tocar numero_operacion) ---
    $subtipoId = (int)($_POST['maritimo_ferro_subtipo'] ?? 0);
    if ($subtipoId <= 0) $subtipoId = (int)$actual['subtipo_operacion_id'];

    $blRaw = (string)($_POST['maritimo_ferro_numeroBL'] ?? $actual['numero_bl'] ?? '');
    $numeroBL = preg_replace('/[^A-Za-z0-9]/', '', $blRaw);

    $navieraId   = $_POST['maritimo_ferro_navieraId']   ?? $actual['naviera_id']   ?? '';
    $forwarderId = $_POST['maritimo_ferro_forwarderId'] ?? $actual['forwarder_id'] ?? '';
    $shipperId   = $_POST['maritimo_ferro_shipperId']   ?? $actual['shipper_id']   ?? '';

    $op = [
        'id_operacion'          => $id,
        'subtipo_operacion_id'  => $subtipoId,
        'etd'                   => trim((string)($_POST['maritimo_ferro_etd'] ?? $actual['etd'] ?? '')) ?: null,
        'eta'                   => trim((string)($_POST['maritimo_ferro_eta'] ?? $actual['eta'] ?? '')) ?: null,
        'numero_bl'             => $numeroBL ?: null,
        'cliente_id'            => (int)($_POST['maritimo_ferro_clienteId'] ?? $actual['cliente_id'] ?? 0),
        'estatus_id'            => (int)($_POST['maritimo_ferro_estatus']   ?? $actual['estatus_id'] ?? 0),
        'naviera_id'            => ($navieraId   !== '') ? (int)$navieraId   : null,
        'forwarder_id'          => ($forwarderId !== '') ? (int)$forwarderId : null,
        'shipper_id'            => ($shipperId   !== '') ? (int)$shipperId   : null,
        'notas'                 => trim((string)($_POST['maritimo_ferro_notas'] ?? $actual['notas'] ?? '')) ?: null,
    ];

    // ======= NUEVO: recoger arrays de IDs y BULTOS desde el form de edición =======
    $ids   = $_POST['maritimo_ferro_contenedores_ids']    ?? [];
    $bults = $_POST['maritimo_ferro_contenedores_bultos'] ?? [];

    // Actualizamos operación + bultos en una transacción
    try {
        $this->model->save("START TRANSACTION", []);

        // 1) actualizar operación
        if (!$this->model->actualizarOperacion($op)) {
            $this->model->save("ROLLBACK", []);
            return $this->jsonError('No se pudo actualizar la operación', 200);
        }

        // 2) actualizar bultos por cada contenedor (si vienen en el POST)
        if (is_array($ids) && is_array($bults) && count($ids) > 0) {
            $n = max(count($ids), count($bults));
            for ($i = 0; $i < $n; $i++) {
                $cid = isset($ids[$i]) ? (int)$ids[$i] : 0;
                if ($cid <= 0) continue;

                $bul = isset($bults[$i]) && $bults[$i] !== '' ? (int)$bults[$i] : null;
                if ($bul !== null && $bul < 0) $bul = 0; // clamp simple

                $okB = $this->model->actualizarBultos($id, $cid, $bul);
                if ($okB === false) {
                    $this->model->save("ROLLBACK", []);
                    return $this->jsonError("No se pudo actualizar bultos del contenedor $cid", 200);
                }
            }
        }

        // 3) log
        if (method_exists($this->model, 'crearLog')) {
            $uid = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
            $this->model->crearLog($id, $uid, 'edicion', 'Operación actualizada (incluye bultos)');
        }

        $this->model->save("COMMIT", []);

        return $this->jsonOk([
            'id_operacion'     => $id,
            'numero_operacion' => (string)$actual['numero_operacion'],
            'msg'              => 'Operación y bultos actualizados',
        ]);

    } catch (\Throwable $e) {
        $this->model->save("ROLLBACK", []);
        return $this->jsonError('Error inesperado al actualizar', 200);
    }
}




    /* =============================
       ========== HELPERS ==========
       ============================= */

    private function jsonOk($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError($msg, $code = 400)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

   /* ==========================================
       ==========  VISTAS  =========
       ========================================== */

    // Vista principal con tabs
    public function ver($id)
    {
        $data['id_operacion'] = 1;
        $data['title'] = 'Operaciones Maritimo-Ferroviarias';
        $data['subtipos']   = $this->model->subtiposMaritimoFerro();
        $data['estatus']    = $this->model->catalogoEstatus();
        $data['navieras']   = $this->model->catalogoNavieras();
        $data['forwarders'] = $this->model->catalogoForwarders();
        $data['shippers']   = $this->model->catalogoShippers();
        $data['puertos']    = $this->model->catalogoPuertos();
   


        $this->views->getView('admin/Operaciones_maritimo_ferro', "ver", $data);
    }
    // TAB : Crear Operación
    public function crear_operacion($id)
    {
        $data['title'] = 'Crear Operación';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/operaciones_terrestres', "crear_operacion", $data);
    }
    // TAB: Detalles Generales (operaciones + detalles_logisticos)
    public function detalles($id)
    {
        //$data = $this->model->getDetallesOperacion($id);
        $data['title'] = 'Detalles Operacion';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/detalles_generales', "detalles", $data);
    }

    // TAB: Contenedores
    public function contenedores($id)
    {
        //$data = $this->model->getContenedoresPorOperacion($id);
        $data['title'] = 'Contenedores';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/contenedores', "contenedores", $data);
    }

    // TAB: Costos por Contenedor
    public function costos($id)
    {
        //$data = $this->model->getCostosPorOperacion($id);
        $data['title'] = 'Costos por Contenedor';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/costos', "costos", $data);
    }

    // TAB: Trazabilidad / Movimientos
    public function trazabilidad($id)
    {
        //$data = $this->model->getTrazabilidadOperacion($id);
        $data['title'] = 'Trazabilidad';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/trazabilidad', "trazabilidad", $data);
    }

    // TAB: Documentos
    public function documentos($id)
    {
        //$data = $this->model->getDocumentosOperacion($id);
        $data['title'] = 'Documentos';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/documentos', "documentos", $data);
    }
    public function costos_operacion($id)
    {
        $data['title'] = 'Costos por Operación';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/costos_operacion', "costos", $data);
    }

    // TAB: Bitácora / Log
    public function log($id)
    {
        //$data = $this->model->getBitacoraOperacion($id);
        $data['title'] = 'Bitácora';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/log', "log", $data);
    }
    // TAB: Detalles Logísticos
    public function detalles_logisticos($id)
    {
        //$data = $this->model->getBitacoraOperacion($id);
        $data['title'] = 'Detalles Logísticos';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/detalles_logisticos', "detalles_logisticos", $data);
    }
}
