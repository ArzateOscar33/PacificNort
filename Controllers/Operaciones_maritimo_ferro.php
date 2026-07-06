<?php
include_once 'Models/Operaciones_maritimo_ferro_contenedoresModel.php';
include_once 'Models/Operaciones_maritimo_ferro_asignacion_ferroModel.php';

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
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2]);
        $this->contenedoresModel = new Operaciones_maritimo_ferro_contenedoresModel();
    }

    /* ================================
       ==========  VISTAS  ============
       ================================ */

    public function index()
    {
        $data['title']          = 'Operaciones Marítimas-Ferroviarias';
        // ✅ La vista que pegaste necesita estos catálogos:
        $data['subtipos']       = $this->model->subtiposMaritimoFerro();
        $data['estatus']        = $this->model->catalogoEstatus();
        $data['navieras']       = $this->model->catalogoNavieras();
        $data['forwarders']     = $this->model->catalogoForwarders();
        $data['shippers']       = $this->model->catalogoShippers();
        $data['puertos']        = $this->model->catalogoPuertos();
        $data['brokers']        = $this->model->getBrokers();
        $data['transportistas'] = $this->model->getTransportistas();
        $data['ciudades']       = $this->model->listarDestinos();


        $this->views->getView($this, "Operaciones_maritimo_ferro", $data);
    }

    /* ==========================================
       ==========  LISTADO / PAGINACIÓN  =========
       ========================================== */

    public function listar_operaciones()
    {
        $subtipoId   = isset($_GET['maritimo_ferro_filtroSubtipo']) ? (int)$_GET['maritimo_ferro_filtroSubtipo'] : 0;
        $term        = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['maritimo_ferro_buscarOperacion']) ? trim($_GET['maritimo_ferro_buscarOperacion']) : '');
        $fechaInicio = isset($_GET['maritimo_ferro_fechaInicio']) ? trim($_GET['maritimo_ferro_fechaInicio']) : '';
        $fechaFin    = isset($_GET['maritimo_ferro_fechaFin'])    ? trim($_GET['maritimo_ferro_fechaFin'])    : '';
        $page        = isset($_GET['page'])    ? (int)$_GET['page']    : 1;
        $perPage     = isset($_GET['perPage']) ? (int)$_GET['perPage'] : (isset($_GET['maritimo_ferro_perPage']) ? (int)$_GET['maritimo_ferro_perPage'] : 10);
        $estatusIds = [];

        if (isset($_GET['maritimo_ferro_filtroEstatus'])) {
            $rawEstatus = $_GET['maritimo_ferro_filtroEstatus'];

            if (!is_array($rawEstatus)) {
                $rawEstatus = explode(',', (string)$rawEstatus);
            }

            foreach ($rawEstatus as $idEst) {
                $idEst = (int)$idEst;
                if ($idEst > 0) {
                    $estatusIds[] = $idEst;
                }
            }

            $estatusIds = array_values(array_unique($estatusIds));
        }
        $navieraId       = isset($_GET['maritimo_ferro_filtroNaviera']) ? (int)$_GET['maritimo_ferro_filtroNaviera'] : 0;
        $forwarderId     = isset($_GET['maritimo_ferro_filtroForwarder']) ? (int)$_GET['maritimo_ferro_filtroForwarder'] : 0;
        $shipperId       = isset($_GET['maritimo_ferro_filtroShipper']) ? (int)$_GET['maritimo_ferro_filtroShipper'] : 0;
        $transportistaId = isset($_GET['maritimo_ferro_filtroTransportista']) ? (int)$_GET['maritimo_ferro_filtroTransportista'] : 0;
        $medida          = isset($_GET['maritimo_ferro_filtroMedidaContenedor']) ? trim($_GET['maritimo_ferro_filtroMedidaContenedor']) : '';
        if ($page < 1) $page = 1;
        $allowedPer = [10, 25, 50, 100, 200, 500, 1000, 10000000];
        if (!in_array($perPage, $allowedPer, true)) $perPage = 10;

        $filters = [
            'filtroSubtipo'         => $subtipoId,
            'filtroEstatus'         => $estatusIds,
            'filtroNaviera'         => $navieraId,
            'filtroForwarder'       => $forwarderId,
            'filtroShipper'         => $shipperId,
            'filtroTransportista'   => $transportistaId,
            'filtroMedidaContenedor' => $medida,

            'term'                  => mb_strtolower($term, 'UTF-8'),
            'fecha_inicio'          => $fechaInicio,
            'fecha_fin'             => $fechaFin,
        ];

        $res = $this->model->listarPaginado($filters, $page, $perPage);

        $rows       = $res['rows'] ?? [];
        $total      = (int)($res['total'] ?? 0);
        $pp         = (int)($res['per_page'] ?? $perPage);
        $pg         = (int)($res['page'] ?? $page);
        $totalPages = (int)($res['total_pages'] ?? 1);

        $from = ($total > 0) ? (($pg - 1) * $pp + 1) : 0;
        $to   = ($total > 0) ? min($total, $pg * $pp) : 0;

        $paginationHtml = $this->buildPaginationHtml($totalPages, $pg);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data'            => $rows,
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

    /* =============================
       ========== AUTOCOMPLETE =====
       ============================= */

    public function autocomplete_clientes()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $data = ($q === '') ? [] : $this->model->buscarClientes($q);
        return $this->jsonOk($data);
    }

    public function buscar_contenedores_mar()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $data = ($q === '') ? [] : $this->model->buscarContenedoresMar($q);
        return $this->jsonOk($data);
    }

    public function preview_folio()
    {
        $sid = isset($_GET['subtipo_id']) ? (int)$_GET['subtipo_id'] : 0;
        if ($sid <= 0) return $this->jsonError('subtipo_id requerido', 400);

        $prev = $this->model->previewCodigoSubtipo($sid);
        if (!$prev) return $this->jsonError('No disponible', 404);

        return $this->jsonOk($prev);
    }

    public function subtipo_info()
    {
        $sid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($sid <= 0) return $this->jsonError('id requerido', 400);

        $row = $this->model->getSubtipoFull($sid);
        if (!$row) return $this->jsonError('No encontrado', 404);

        return $this->jsonOk([
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
       ========== ALTA ==========
       ============================= */

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonError('Método no permitido', 405);
        }

        // ✅ Names REALES de tu vista
        $subtipoId = (int)($_POST['subtipo_operacion_id_mf'] ?? 0);
        if ($subtipoId <= 0) return $this->jsonError('Subtipo requerido', 400);

        $numeroOp  = trim((string)($_POST['numero_operacion_mf'] ?? '')); // '' => modelo genera
        $estatusId = (int)($_POST['estatus_id_mf'] ?? 9);

        $etd = trim((string)($_POST['etd_mf'] ?? '')) ?: null;
        $eta = trim((string)($_POST['eta_mf'] ?? '')) ?: null;
        $ubicacionActual = trim((string)($_POST['ubicacion_actual_mf'] ?? ''));
        $ubicacionActual = ($ubicacionActual !== '') ? mb_strtoupper($ubicacionActual, 'UTF-8') : null;

        if ($ubicacionActual !== null && mb_strlen($ubicacionActual, 'UTF-8') > 250) {
            return $this->jsonError('La ubicación actual no puede superar los 250 caracteres.', 200);
        }

        // BL
        $blRaw = (string)($_POST['numero_bl_mf'] ?? '');
        $bl    = preg_replace('/[^A-Za-z0-9]/', '', $blRaw) ?: null;

        // Cliente (hidden)
        $clienteIdRaw = trim((string)($_POST['cliente_id_mf'] ?? ''));
        $clienteId = ctype_digit($clienteIdRaw) ? (int)$clienteIdRaw : 0;
        $clienteId = ($clienteId > 0) ? $clienteId : null;
        if ($clienteId === null) {
            return $this->jsonWarning('Selecciona un cliente de la lista antes de guardar la operación.');
        }

        if (method_exists($this->model, 'clienteActivoExiste') && !$this->model->clienteActivoExiste((int)$clienteId)) {
            return $this->jsonWarning('El cliente seleccionado no existe o está inactivo.');
        }
        $navieraIdRaw       = (int)($_POST['naviera_id_mf'] ?? 0);
        $forwarderIdRaw     = (int)($_POST['forwarder_id_mf'] ?? 0);
        $shipperIdRaw       = (int)($_POST['shipper_id_mf'] ?? 0);
        $brokerIdRaw        = (int)($_POST['broker_id_mf'] ?? 0);
        $transportistaIdRaw = (int)($_POST['transportista_id_mf'] ?? 0);

        $navieraId       = $navieraIdRaw > 0 ? $navieraIdRaw : null;
        $forwarderId     = $forwarderIdRaw > 0 ? $forwarderIdRaw : null;
        $shipperId       = $shipperIdRaw > 0 ? $shipperIdRaw : null;
        $brokerId        = $brokerIdRaw > 0 ? $brokerIdRaw : null;
        $transportistaId = $transportistaIdRaw > 0 ? $transportistaIdRaw : null;

        $notas = trim((string)($_POST['notas_mf'] ?? '')) ?: null;

        // ISF checkbox: si no viene, es 0
        $isf = isset($_POST['isf']) ? 1 : 0;

        // cita_puerto (tu input es type="date" actualmente)
        $citaRaw = trim((string)($_POST['cita_puerto'] ?? ''));
        $cita = ($citaRaw !== '') ? $citaRaw : null;

        // ===== Contenedores (arrays de tu repeater)
        $ids   = $_POST['contenedores_id']     ?? [];
        $nums  = $_POST['contenedores_codigo'] ?? [];
        $bults = $_POST['contenedores_bultos'] ?? [];
        $tipos = $_POST['contenedores_tipo']   ?? [];
        $pesos = $_POST['contenedores_peso']   ?? [];

        $contenedores = [];
        $pesoTotal = 0.0;
        $tienePeso = false;

        $n = max(count($ids), count($nums), count($bults), count($tipos), count($pesos));
        for ($i = 0; $i < $n; $i++) {
            $cid  = isset($ids[$i]) ? (int)$ids[$i] : 0;
            $cnum = isset($nums[$i]) ? trim((string)$nums[$i]) : '';
            $cbul = (isset($bults[$i]) && $bults[$i] !== '') ? (int)$bults[$i] : null;
            $ctip = isset($tipos[$i]) ? trim((string)$tipos[$i]) : '';

            // Peso por fila (para sumar a peso_total)
            $cpeso = null;
            if (isset($pesos[$i]) && $pesos[$i] !== '') {
                $cpeso = (float)$pesos[$i];
                if ($cpeso < 0) $cpeso = 0;
                $pesoTotal += $cpeso;
                $tienePeso = true;
            }

            if ($cid > 0 || $cnum !== '') {
                $contenedores[] = [
                    'id'     => $cid,
                    'numero' => $cnum,
                    'bultos' => $cbul,
                    'tipo'   => $ctip, // ✅ esto permite actualizar contenedores_maritimos.tipo
                    // 'peso' => $cpeso, // si luego creas tabla para peso por contenedor, aquí ya lo tienes
                ];
            }
        }
        $mercancia = trim((string)($_POST['descripcion_mercancia_mf'] ?? ''));

        // Si no mandaron pesos, dejamos null (para no “inventar” 0)
        $pesoOperacion = $tienePeso ? $pesoTotal : null;

        $op = [
            'numero_operacion'      => $numeroOp,     // '' => modelo genera folio
            'subtipo_operacion_id'  => $subtipoId,
            'etd'                   => $etd,
            'eta'                   => $eta,
            'ubicacion_actual'      => $ubicacionActual,
            'numero_bl'             => $bl,
            'cliente_id'            => $clienteId,
            'estatus_id'            => $estatusId,
            'naviera_id'            => $navieraId,
            'forwarder_id'          => $forwarderId,
            'shipper_id'            => $shipperId,

            'notas'                 => $notas,
            'isf'                   => $isf,
            'cita_puerto'           => $cita,

            // ✅ NUEVOS
            'peso_total'            => $pesoOperacion,
            'broker_id'             => $brokerId,
            'transportista_id'      => $transportistaId,
            'descripcion_mercancia'             => $mercancia,
        ];

        $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

        try {
            $res = $this->model->insertarOperacion($op, $contenedores, $usuarioId);
        } catch (\Throwable $e) {
            $parsed = $this->parseModeloError($e);

            // warning (DUP_CONT_MES) -> lo devolvemos tal cual para el JS
            if (($parsed['status'] ?? '') === 'warning') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($parsed, JSON_UNESCAPED_UNICODE);
                exit;
            }

            // error normal
            return $this->jsonError($parsed['msg'] ?? 'No se pudo guardar', 200);
        }

        if (!is_array($res) || ($res['status'] ?? 'error') !== 'success') {
            $msg = $res['msg'] ?? 'No se pudo guardar';
            return $this->jsonError($msg, 200);
        }

        return $this->jsonOk([
            'id_operacion'     => $res['id_operacion'] ?? 0,
            'numero_operacion' => $res['numero_operacion'] ?? '',
            'msg'              => $res['msg'] ?? 'Operación creada',
        ]);
    }

    /* =============================
       ========== OBTENER (EDIT) ===
       ============================= */

    public function obtener_operacion()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) return $this->jsonError('id requerido', 400);

        $op = $this->model->obtenerOperacion($id);
        if (!$op) return $this->jsonError('Operación no encontrada', 404);

        // ✅ Para precargar el repeater (incluye tipo/bultos)
        if (method_exists($this->model, 'getContenedoresDeOperacion')) {
            $op['contenedores'] = $this->model->getContenedoresDeOperacion($id);
        } else {
            $op['contenedores'] = [];
        }

        return $this->jsonOk($op);
    }

    /* =============================
       ========== ACTUALIZAR =======
       ============================= */

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonError('Método no permitido', 405);
        }

        $id = (int)($_POST['id_operacion_mf'] ?? 0);
        if ($id <= 0) return $this->jsonError('id_operacion requerido', 400);

        $actual = $this->model->getOperacionById($id);
        if (!$actual) return $this->jsonError('Operación no existe', 404);

        // ✅ Names REALES de tu vista
        $subtipoId = (int)($_POST['subtipo_operacion_id_mf'] ?? 0);
        if ($subtipoId <= 0) $subtipoId = (int)($actual['subtipo_operacion_id'] ?? 0);

        $estatusId = (int)($_POST['estatus_id_mf'] ?? ($actual['estatus_id'] ?? 9));

        $etd = trim((string)($_POST['etd_mf'] ?? ($actual['etd'] ?? ''))) ?: null;
        $eta = trim((string)($_POST['eta_mf'] ?? ($actual['eta'] ?? ''))) ?: null;

        $ubicacionActual = trim((string)($_POST['ubicacion_actual_mf'] ?? ($actual['ubicacion_actual'] ?? '')));
        $ubicacionActual = ($ubicacionActual !== '') ? mb_strtoupper($ubicacionActual, 'UTF-8') : null;

        if ($ubicacionActual !== null && mb_strlen($ubicacionActual, 'UTF-8') > 250) {
            return $this->jsonError('La ubicación actual no puede superar los 250 caracteres.', 200);
        }

        $blRaw = (string)($_POST['numero_bl_mf'] ?? ($actual['numero_bl'] ?? ''));
        $bl    = preg_replace('/[^A-Za-z0-9]/', '', $blRaw) ?: null;

        $clienteIdRaw = trim((string)($_POST['cliente_id_mf'] ?? ($actual['cliente_id'] ?? '')));
        $clienteId = ctype_digit((string)$clienteIdRaw) ? (int)$clienteIdRaw : 0;
        $clienteId = ($clienteId > 0) ? $clienteId : null;

        $navieraIdRaw       = (int)($_POST['naviera_id_mf'] ?? ($actual['naviera_id'] ?? 0));
        $forwarderIdRaw     = (int)($_POST['forwarder_id_mf'] ?? ($actual['forwarder_id'] ?? 0));
        $shipperIdRaw       = (int)($_POST['shipper_id_mf'] ?? ($actual['shipper_id'] ?? 0));
        $brokerIdRaw        = (int)($_POST['broker_id_mf'] ?? ($actual['broker_id'] ?? 0));
        $transportistaIdRaw = (int)($_POST['transportista_id_mf'] ?? ($actual['transportista_id'] ?? 0));

        $navieraId       = $navieraIdRaw > 0 ? $navieraIdRaw : null;
        $forwarderId     = $forwarderIdRaw > 0 ? $forwarderIdRaw : null;
        $shipperId       = $shipperIdRaw > 0 ? $shipperIdRaw : null;
        $brokerId        = $brokerIdRaw > 0 ? $brokerIdRaw : null;
        $transportistaId = $transportistaIdRaw > 0 ? $transportistaIdRaw : null;

        $notas = trim((string)($_POST['notas_mf'] ?? ($actual['notas'] ?? ''))) ?: null;
        $mercancia = trim((string)($_POST['descripcion_mercancia_mf'] ?? ($actual['descripcion_mercancia'] ?? '')));
        $mercancia = ($mercancia !== '') ? $mercancia : null;

        $isf = (!empty($_POST['isf']) && (int)$_POST['isf'] === 1) ? 1 : 0;


        $citaRaw = trim((string)($_POST['cita_puerto'] ?? ($actual['cita_puerto'] ?? '')));
        $cita = ($citaRaw !== '') ? $citaRaw : null;

        // ===== Contenedores (arrays de tu repeater) — en EDIT también
        $ids   = $_POST['contenedores_id']     ?? [];
        $nums  = $_POST['contenedores_codigo'] ?? [];
        $bults = $_POST['contenedores_bultos'] ?? [];
        $tipos = $_POST['contenedores_tipo']   ?? [];
        $pesos = $_POST['contenedores_peso']   ?? [];

        $contenedores = [];
        $pesoTotal = 0.0;
        $tienePeso = false;

        $n = max(count($ids), count($nums), count($bults), count($tipos), count($pesos));
        for ($i = 0; $i < $n; $i++) {
            $cid  = isset($ids[$i]) ? (int)$ids[$i] : 0;
            $cnum = isset($nums[$i]) ? trim((string)$nums[$i]) : '';
            $cbul = (isset($bults[$i]) && $bults[$i] !== '') ? (int)$bults[$i] : null;
            $ctip = isset($tipos[$i]) ? trim((string)$tipos[$i]) : '';

            $cpeso = null;
            if (isset($pesos[$i]) && $pesos[$i] !== '') {
                $cpeso = (float)$pesos[$i];
                if ($cpeso < 0) $cpeso = 0;
                $pesoTotal += $cpeso;
                $tienePeso = true;
            }

            if ($cid > 0 || $cnum !== '') {
                $contenedores[] = [
                    'id'     => $cid,
                    'numero' => $cnum,
                    'bultos' => $cbul,
                    'tipo'   => $ctip,
                ];
            }
        }

        $pesoOperacion = $tienePeso ? $pesoTotal : null;

        $op = [
            'id_operacion'          => $id,
            'subtipo_operacion_id'  => $subtipoId,
            'estatus_id'            => $estatusId,
            'etd'                   => $etd,
            'eta'                   => $eta,
            'ubicacion_actual'      => $ubicacionActual,
            'numero_bl'             => $bl,
            'cliente_id'            => $clienteId,
            'naviera_id'            => $navieraId,
            'forwarder_id'          => $forwarderId,
            'shipper_id'            => $shipperId,

            'notas'                 => $notas,
            'isf'                   => $isf,
            'cita_puerto'           => $cita,

            'peso_total'            => $pesoOperacion,
            'broker_id'             => $brokerId,
            'transportista_id'      => $transportistaId,
            'descripcion_mercancia' => $mercancia,
        ];

        $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

        /**
         * ✅ Compatibilidad:
         * - Si tu modelo viejo tiene actualizarOperacion(array $d): bool
         * - y el nuevo tiene actualizarOperacion(array $d, array $contenedores = [], int $usuarioId = 0): array
         * este bloque decide cómo llamarlo sin romper.
         */
        try {
            $ref = new ReflectionMethod($this->model, 'actualizarOperacion');
            $argc = $ref->getNumberOfParameters();

            if ($argc >= 3) {
                // modelo NUEVO
                $res = $this->model->actualizarOperacion($op, $contenedores, $usuarioId);
                if (!is_array($res) || ($res['status'] ?? 'error') !== 'success') {
                    $msg = $res['msg'] ?? 'No se pudo actualizar';
                    return $this->jsonError($msg, 200);
                }
                return $this->jsonOk([
                    'id_operacion'     => $id,
                    'numero_operacion' => (string)($actual['numero_operacion'] ?? ''),
                    'msg'              => (string)($res['msg'] ?? 'Operación actualizada'),
                ]);
            }

            // modelo VIEJO (bool) — al menos guarda operación y broker_id; pero NO sincroniza contenedores
            $ok = $this->model->actualizarOperacion($op);
            if (!$ok) return $this->jsonError('No se pudo actualizar la operación', 200);

            // Si tu modelo viejo NO hace sync contenedores, aquí no podemos forzarlo.
            // (Por eso era necesario el modelo corregido.)

            return $this->jsonOk([
                'id_operacion'     => $id,
                'numero_operacion' => (string)($actual['numero_operacion'] ?? ''),
                'msg'              => 'Operación actualizada',
            ]);
        } catch (\Throwable $e) {
            return $this->jsonError('Error inesperado al actualizar', 200);
        }
    }

    /* =============================
       ========== HELPERS JSON ======
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

    private function jsonWarning($msg, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'warning',
            'msg'    => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // ✅ Parsea errores del modelo (DUP_CONT_MES|CONT|OP|YYYY-MM)
    private function parseModeloError(\Throwable $e): array
    {
        $msg = trim((string)$e->getMessage());

        // Caso: DUP_CONT_MES|CSNU900101|LMF-199|2026-03
        if (strpos($msg, 'DUP_CONT_MES|') === 0) {
            $parts = explode('|', $msg);
            $contenedor = $parts[1] ?? '';
            $opConf     = $parts[2] ?? '';
            $mes        = $parts[3] ?? '';

            return [
                'status' => 'warning',
                'code'   => 'DUP_CONT_MES',
                'msg'    => "No se puede registrar el contenedor {$contenedor} porque ya está asignado a {$opConf} en {$mes}.",
                'data'   => [
                    'contenedor' => $contenedor,
                    'operacion'  => $opConf,
                    'mes'        => $mes,
                ],
            ];
        }

        // (Opcional) si tu trigger MySQL lanza MESSAGE_TEXT con DUP_CONT_MES|...
        if (strpos($msg, 'DUP_CONT_MES') !== false && strpos($msg, '|') !== false) {
            // intenta rescatar el segmento
            $pos = strpos($msg, 'DUP_CONT_MES|');
            if ($pos !== false) {
                $sub = substr($msg, $pos);
                $parts = explode('|', $sub);
                $contenedor = $parts[1] ?? '';
                $opConf     = $parts[2] ?? '';
                $mes        = $parts[3] ?? '';

                return [
                    'status' => 'warning',
                    'code'   => 'DUP_CONT_MES',
                    'msg'    => "No se puede registrar el contenedor {$contenedor} porque ya está asignado a {$opConf} en {$mes}.",
                    'data'   => [
                        'contenedor' => $contenedor,
                        'operacion'  => $opConf,
                        'mes'        => $mes,
                    ],
                ];
            }
        }

        // default
        return [
            'status' => 'error',
            'msg'    => ($msg !== '' ? $msg : 'Error inesperado'),
        ];
    }

    /* =============================
       ========== VISTAS (tabs) =====
       ============================= */

    public function ver($id)
    {
        $data['id_operacion']   = (int)$id;
        $data['title']          = 'Operaciones Maritimo-Ferroviarias';
        $data['subtipos']       = $this->model->subtiposMaritimoFerro();
        $data['estatus']        = $this->model->catalogoEstatus();
        $data['navieras']       = $this->model->catalogoNavieras();
        $data['forwarders']     = $this->model->catalogoForwarders();
        $data['shippers']       = $this->model->catalogoShippers();
        $data['puertos']        = $this->model->catalogoPuertos();
        $data['brokers']        = $this->model->getBrokers();
        $data['transportistas'] = $this->model->getTransportistas();
        $data['ciudades']       = $this->model->listarDestinos();
        $data['categoriasCostos']     = $this->model->listarCategoriasCostos();

        $data['clientes']       = $this->model->catalogoClientes();

        $this->views->getView('admin/Operaciones_maritimo_ferro', "ver", $data);
    }

    public function crear_operacion($id)
    {
        $data['title'] = 'Crear Operación';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/operaciones_terrestres', "crear_operacion", $data);
    }

    public function detalles($id)
    {
        $data['title'] = 'Detalles Operacion';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/detalles_generales', "detalles", $data);
    }

    public function contenedores($id)
    {
        $data['title'] = 'Contenedores';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/contenedores', "contenedores", $data);
    }

    public function costos($id)
    {
        $data['title'] = 'Costos por Contenedor';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/costos', "costos", $data);
    }

    public function trazabilidad($id)
    {
        $data['title'] = 'Trazabilidad';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/trazabilidad', "trazabilidad", $data);
    }

    public function documentos($id)
    {
        $data['title'] = 'Documentos';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/documentos', "documentos", $data);
    }

    public function costos_operacion($id)
    {
        $data['title'] = 'Costos por Operación';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/costos_operacion', "costos", $data);
    }

    public function log($id)
    {
        $data['title'] = 'Bitácora';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/log', "log", $data);
    }

    public function detalles_logisticos($id)
    {
        $data['title'] = 'Detalles Logísticos';
        $this->views->getView('admin/Operaciones_maritimo_ferro/tabs/detalles_logisticos', "detalles_logisticos", $data);
    }
}
