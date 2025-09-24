<?php
class Operaciones_maritimo_ferro_contenedores extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
       
    }
public function listar()
{
    // --- Filtros
    $q       = isset($_GET['q'])       ? trim($_GET['q']) : '';
    $desde   = isset($_GET['desde'])   ? trim($_GET['desde']) : '';
    $hasta   = isset($_GET['hasta'])   ? trim($_GET['hasta']) : '';
    $page    = isset($_GET['page'])    ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;

    // Normaliza paginación
    if ($page < 1) $page = 1;
    $allowedPer = [10, 25, 50, 100, 200];
    if (!in_array($perPage, $allowedPer, true)) $perPage = 10;

    $filters = [
        'term'      => mb_strtolower($q, 'UTF-8'),
        'date_from' => ($desde !== '' ? $desde : null),
        'date_to'   => ($hasta !== '' ? $hasta : null),
    ];

    // --- Modelo
    $res  = $this->model->listarFerrosPaginado($filters, $page, $perPage);
    $rows = $res['data'] ?? [];
    $meta = $res['meta'] ?? ['total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 1];

    // --- Mapeo a las llaves que tu JS espera (9 columnas)
    $data = array_map(function ($r) {
        // Acepta 'contenedor_maritimo' o 'contenedores_maritimos'
        $maritimo = '';
        if (isset($r['contenedor_maritimo'])) {
            $maritimo = (string)$r['contenedor_maritimo'];
        } elseif (isset($r['contenedores_maritimos'])) {
            $maritimo = (string)$r['contenedores_maritimos'];
        }

        return [
            // usa 'id_row' si viene, y además deja 'id' por compatibilidad con botones
            'id_row'              => (int)($r['id_row'] ?? 0),
            'id'                  => (int)($r['id_row'] ?? 0),

            'numero_operacion'    => (string)($r['numero_operacion'] ?? ''),
            'contenedores_maritimos' => $maritimo,
            'contenedor_maritimo' => $maritimo, // alias por compatibilidad

            'bultos_maritimo'     => isset($r['bultos_maritimo']) ? (int)$r['bultos_maritimo'] : null,
            'cliente'             => (string)($r['cliente'] ?? ''),

            // NUEVOS: pásalos tal cual del modelo
            'transportista'       => (string)($r['transportista'] ?? ''),
            'ferro'               => (string)($r['ferro'] ?? ''),
            'division_bultos'     => (string)($r['division_bultos'] ?? ''),
            'destino'             => (string)($r['destino'] ?? ''),

            // opcional: por si quieres usarlo después
            'bultos_asignados_total' => isset($r['bultos_asignados_total']) ? (int)$r['bultos_asignados_total'] : 0,
            'fecha_header'        => (string)($r['fecha_header'] ?? ''),
        ];
    }, $rows);

    // --- Resumen
    $total = (int)($meta['total'] ?? 0);
    $pp    = (int)($meta['per_page'] ?? $perPage);
    $pg    = (int)($meta['page'] ?? $page);
    $from  = ($total > 0) ? (($pg - 1) * $pp + 1) : 0;
    $to    = ($total > 0) ? min($total, $pg * $pp) : 0;

    // --- Paginación HTML Bootstrap
    $paginationHtml = $this->buildPaginationHtml(
        (int)($meta['total_pages'] ?? 1),
        $pg
    );

    // --- Respuesta
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data'            => $data,
        'from'            => $from,
        'to'              => $to,
        'total'           => $total,
        'page'            => $pg,
        'per_page'        => $pp,
        'total_pages'     => (int)($meta['total_pages'] ?? 1),
        'pagination_html' => $paginationHtml,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}



// En Operaciones_maritimo_ferro_contenedores (controlador)
public function buscar_ferros()
{
    header('Content-Type: application/json; charset=utf-8');
    $term  = isset($_GET['term'])  ? trim($_GET['term'])  : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit']  : 15;

    try {
        $items = $this->model->sugerenciasFerros($term, $limit);
        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al buscar ferros/cajas']);
    }
    exit;
}
public function buscar_destinos()
{
    header('Content-Type: application/json; charset=utf-8');
    $term  = isset($_GET['term'])  ? trim($_GET['term'])  : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit']  : 15;

    try {
        $items = $this->model->sugerenciasDestinos($term, $limit);
        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al buscar destinos']);
    }
    exit;
}

    /** Paginación Bootstrap simple */
    private function buildPaginationHtml(int $totalPages, int $currentPage): string
    {
        if ($totalPages <= 1) return '';

        $html = '<li class="page-item' . ($currentPage <= 1 ? ' disabled' : '') . '">
                   <a class="page-link" href="#" data-page="' . max(1, $currentPage - 1) . '">&laquo;</a>
                 </li>';

        // Si quieres acotar el rango visible, aquí puedes limitar i
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
    public function suma_bultos_operacion()
    {
        $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($opId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'operacion_id requerido', 'total_asignados' => 0]);
            exit;
        }
        $row = $this->model->getSumaBultosPorOperacion($opId);
        $total = (int)($row['total_asignados'] ?? 0);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'total_asignados' => $total], JSON_UNESCAPED_UNICODE);
        exit;
    }
    public function sugerencias_operaciones()
    {
        $q     = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 15;

        $data = $this->model->sugerenciasOperacionesFerroOP($q, $limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function buscar_transportistas()
{
    header('Content-Type: application/json; charset=utf-8');
    $term  = isset($_GET['term'])  ? trim($_GET['term'])  : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit']  : 15;

    $tipos = [];
    if (!empty($_GET['tipo'])) {
        $tipos = array_values(array_filter(array_map('trim', explode(',', $_GET['tipo']))));
    } else {
        $tipos = ['ferroviario']; // ← default coherente para ferros
    }

    try {
        $data = $this->model->sugerenciasTransportistas($term, $limit, $tipos);
        echo json_encode(['ok' => true, 'items' => $data], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al buscar transportistas']);
    }
    exit;
}
/** POST /operaciones_maritimo_ferro_contenedores/guardar_asignacion */
public function guardar_asignacion()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Lee ambos: hidden ID + texto visible
    $contenedorFerroId    = (int)($_POST['contenedorFerroIdFerroOP'] ?? 0);
    $contenedorFerroName  = trim((string)($_POST['contenedorFerroNombreFerroOP'] ?? ''));

    // Si NO hay ID pero SÍ hay texto => intenta upsert en caliente
    if ($contenedorFerroId <= 0 && $contenedorFerroName !== '') {
        $mk = $this->model->upsertFerro($contenedorFerroName);
        if (empty($mk['ok'])) {
            return $this->jsonBad($mk['msg'] ?? 'No se pudo crear el ferro/caja.');
        }
        // Re-inyecta el id para continuar el flujo normal
        $_POST['contenedorFerroIdFerroOP'] = $mk['id_fisico'];
        $contenedorFerroId = (int)$mk['id_fisico'];
    }

    // === Continúa con tus lecturas / validaciones actuales ===
    $operacionId          = (int)($_POST['operacionIdFerroOP'] ?? 0);
    $contenedorMaritimoId = (int)($_POST['contenedorMaritimoIdFerroOP'] ?? 0);
    $bultosAsignados      = (int)($_POST['bultosAsignadosFerroOP'] ?? 0);
    $transportistaId      = (int)($_POST['transportistaIdFerroOP'] ?? 0);
    $destinoId            = (int)($_POST['destinoIdFerroOP'] ?? 0);
    $comentario           = trim((string)($_POST['comentariosFerroOP'] ?? ''));

    if ($operacionId <= 0)          { $this->jsonBad('Operación requerida'); }
    if ($contenedorMaritimoId <= 0) { $this->jsonBad('Contenedor marítimo requerido'); }
    if ($contenedorFerroId <= 0)    { $this->jsonBad('Caja/Ferro requerido'); } // ← ya contempló el alta en caliente
    if ($transportistaId <= 0)      { $this->jsonBad('Transportista requerido'); }
    if ($destinoId <= 0)            { $this->jsonBad('Destino requerido'); }
    if ($bultosAsignados <= 0)      { $this->jsonBad('Los bultos asignados deben ser > 0'); }

    // === 3) Payload para el modelo ===
    $payload = [
        'operacion_id'           => $operacionId,
        'contenedor_maritimo_id' => $contenedorMaritimoId,
        'contenedor_fisico_id'   => $contenedorFerroId,
        'destino_id'             => $destinoId,
        'transportista_id'       => $transportistaId,
        'bultos_asignados'       => $bultosAsignados,
        'comentario'             => $comentario,
        // 'fecha' => 'YYYY-mm-dd', // si después agregas fecha en el modal
    ];

    // === 4) Guardar (modelo) ===
    $res = $this->model->registrarAsignacionFerro($payload);

    // === 5) Respuesta ===
    header('Content-Type: application/json; charset=utf-8');
    if (!is_array($res) || empty($res['ok'])) {
        $msg = is_array($res) && isset($res['msg']) ? $res['msg'] : 'No se pudo registrar la asignación.';
        // 200 para que el front procese el mensaje y muestre alerta
        http_response_code(200);
        echo json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok'   => true,
        'msg'  => (string)($res['msg'] ?? 'Asignación registrada'),
        'data' => [
            'numero_operacion_ferro' => (string)($res['numero_operacion_ferro'] ?? ''),
            'saldo'                  => (int)($res['saldo'] ?? 0),
            'ids' => [
                'operacion_ferro_id'           => (int)($res['ids']['operacion_ferro_id'] ?? 0),
                'contenedor_maritimo_ferro_id' => (int)($res['ids']['contenedor_maritimo_ferro_id'] ?? 0),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Helper local para errores 200 legibles por el front */
private function jsonBad(string $msg): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
// En tu controlador Operaciones_maritimo_ferro_contenedores
public function saldos_por_operacion()
{
    // Acepta operacion_id o numero (numero_operacion)
    $operacion_id = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
    $numero       = isset($_GET['numero']) ? trim($_GET['numero']) : '';

    if ($operacion_id <= 0) {
        if ($numero === '') {
            echo json_encode(['ok' => false, 'msg' => 'Falta operacion_id o numero']); return;
        }
        // Resolver id_operacion por numero_operacion
        $row = $this->model->select(
            "SELECT id_operacion FROM operaciones WHERE numero_operacion=? LIMIT 1",
            [$numero]
        );
        if (!$row) { echo json_encode(['ok'=>false,'msg'=>'Operación no encontrada']); return; }
        $operacion_id = (int)$row['id_operacion'];
    }

    // Traer los saldos por MG de esa operación
    $items = $this->model->listarSaldosMGPorOperacion($operacion_id);

    echo json_encode([
        'ok'           => true,
        'operacion_id' => $operacion_id,
        'items'        => $items  // cada item trae: id_cmo, numero_contenedor, bultos_totales, bultos_asignados, bultos_restantes
    ]);
}

// POST /Operaciones_maritimo_ferro_contenedores/crear_ferro
public function crear_ferro()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
    }

    $numero = isset($_POST['numero_ferro']) ? trim((string)$_POST['numero_ferro']) : '';
    if ($numero === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'msg'=>'Número de ferro/caja requerido']); exit;
    }

    try {
        $res = $this->model->upsertFerro($numero);
        header('Content-Type: application/json; charset=utf-8');
        if (empty($res['ok'])) {
            echo json_encode(['ok'=>false,'msg'=>$res['msg'] ?? 'No se pudo crear']); exit;
        }
        echo json_encode([
            'ok'=>true,
            'id' => (int)$res['id_fisico'],
            'label' => (string)$res['label'],
            'created' => !empty($res['created'])
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'msg'=>'Error al crear ferro/caja']); 
    }
    exit;
}

}
