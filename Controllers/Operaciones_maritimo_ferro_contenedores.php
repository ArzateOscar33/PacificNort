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

        // --- Mapeo a las llaves esperadas por la vista
        // Campos esperados por la tabla:
        // numero_operacion | contenedor_maritimo | bultos_maritimo | cliente | ferro | bultos_asignados
        $data = array_map(function ($r) {
            // Acepta 'contenedor_maritimo' o 'contenedores_maritimos' del modelo
            $maritimo = '';
            if (isset($r['contenedor_maritimo'])) {
                $maritimo = (string)$r['contenedor_maritimo'];
            } elseif (isset($r['contenedores_maritimos'])) {
                $maritimo = (string)$r['contenedores_maritimos']; // GROUP_CONCAT si hay varios
            }

            return [
                'id'                 => (int)($r['id_row'] ?? 0),
                'numero_operacion'   => (string)($r['numero_operacion'] ?? ''),
                'contenedor_maritimo' => $maritimo,
                'bultos_maritimo'    => array_key_exists('bultos_maritimo', $r) ? (int)$r['bultos_maritimo'] : null,
                'cliente'            => (string)($r['cliente'] ?? ''),
                'ferro'              => (string)($r['ferro'] ?? ''),
                'bultos_asignados'   => (int)($r['bultos_asignados'] ?? 0),
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


}
