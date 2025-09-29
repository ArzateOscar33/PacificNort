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

                
                'transportista'       => (string)($r['transportista'] ?? ''),
                'ferro'               => (string)($r['ferro'] ?? ''),
                'division_bultos'     => (string)($r['division_bultos'] ?? ''),
                'destino'             => (string)($r['destino'] ?? ''),

                 
                'bultos_asignados_total' => isset($r['bultos_asignados_total']) ? (int)$r['bultos_asignados_total'] : 0,
                'fecha_header'        => (string)($r['fecha_header'] ?? ''),
                'estatus'                => (string)($r['estatus'] ?? ''),
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
    public function sugerencias_operaciones_maritimas()
    {
        header('Content-Type: application/json; charset=utf-8');
        $q     = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 15;

        try {
            $data = $this->model->sugerenciasOperacionesMaritimasParaFerro($q, $limit);
            echo json_encode(['ok' => true, 'items' => $data], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'Error al buscar operaciones marítimas']);
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
        $foId = isset($_GET['operacion_ferro_id']) ? (int)$_GET['operacion_ferro_id'] : 0;
        if ($foId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'operacion_ferro_id requerido', 'total_asignados' => 0]);
            exit;
        }
        $total = $this->model->getSumaBultosPorOperacionFerro($foId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'total_asignados' => (int)$total], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function sugerencias_operaciones()
    {
        return $this->sugerencias_operaciones_maritimas();
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

        // ====== HEADER (names de tu vista) ======
        $fecha             = isset($_POST['fechaFerroOP']) ? trim($_POST['fechaFerroOP']) : '';
        $estatus_id        = isset($_POST['estatus_id_f']) ? (int)$_POST['estatus_id_f'] : 9;
        $contenedorFerroId = (int)($_POST['contenedorFerroIdFerroOP'] ?? 0);
        $contenedorFerroNm = trim((string)($_POST['contenedorFerroNombreFerroOP'] ?? ''));
        $transportistaId   = (int)($_POST['transportistaIdFerroOP'] ?? 0);
        $destinoId         = (int)($_POST['destinoIdFerroOP'] ?? 0);
        $comentarioHeader  = trim((string)($_POST['comentariosFerroOP'] ?? ''));
        $creadoPor         = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

        // Validación mínima del header
        if ($fecha === '') {
            return $this->jsonBad('La fecha es requerida');
        }
        if ($contenedorFerroId <= 0 && $contenedorFerroNm === '') {
            return $this->jsonBad('Ferro/Caja requerida');
        }
        if ($transportistaId <= 0) {
            return $this->jsonBad('Transportista requerido');
        }
        if ($destinoId <= 0) {
            return $this->jsonBad('Destino requerido');
        }

        // Alta en caliente del ferro si vino solo el texto
        if ($contenedorFerroId <= 0 && $contenedorFerroNm !== '') {
            $mk = $this->model->upsertFerro($contenedorFerroNm);
            if (empty($mk['ok'])) {
                return $this->jsonBad($mk['msg'] ?? 'No se pudo crear el ferro/caja.');
            }
            $contenedorFerroId = (int)$mk['id_fisico'];
        }



        // ====== ASIGNACIONES (lista) ======
        // Espera $_POST['asignaciones'] como JSON:
        // [{ "cmo_id": 123, "bultos_asignados": 10, "comentario": "opc" }, ...]
        $asignaciones = [];
        if (!empty($_POST['asignaciones'])) {
            $parsed = json_decode((string)$_POST['asignaciones'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $asignaciones = $parsed;
            }
            if (!array_filter($asignaciones, fn($x) => (int)($x['bultos_asignados'] ?? 0) > 0)) {
                return $this->jsonBad('Debes asignar al menos 1 bulto en alguna fila.');
            }
        }

        // Fallback por si envías un único registro suelto (no recomendado ya, pero compatible)
        if (empty($asignaciones)) {
            $cmoIdUna   = (int)($_POST['contMaritimoOperacionIdFerroOP'] ?? 0);
            $bultosUna  = (int)($_POST['bultosAsignadosFerroOP'] ?? 0);
            $comentUna  = trim((string)($_POST['comentarioLinea'] ?? ''));
            if ($cmoIdUna > 0 && $bultosUna > 0) {
                $asignaciones[] = ['cmo_id' => $cmoIdUna, 'bultos_asignados' => $bultosUna, 'comentario' => $comentUna];
            }
        }

        if (empty($asignaciones)) {
            return $this->jsonBad('Debes agregar al menos un contenedor marítimo con bultos.');
        }

        // ====== Construir payload para el modelo (según tu nueva firma) ======
        $payload = [
            'contenedor_fisico_id' => $contenedorFerroId,
            'destino_id'           => $destinoId,
            'transportista_id'     => $transportistaId,
            'fecha'                => $fecha,
            'estatus_id'           => $estatus_id,           // 9 = Abierta (ok)
            'comentario'           => $comentarioHeader,
            'creado_por'           => $creadoPor,
            'asignaciones'         => $asignaciones,         // <- múltiples CMO
        ];

        // ====== Guardar ======
        $res = $this->model->registrarAsignacionFerro($payload);

        header('Content-Type: application/json; charset=utf-8');
        if (!is_array($res) || empty($res['ok'])) {
            $msg = is_array($res) && isset($res['msg']) ? $res['msg'] : 'No se pudo registrar la operación ferroviaria.';
            http_response_code(200);
            echo json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'ok'   => true,
            'msg'  => (string)($res['msg'] ?? 'Operación ferroviaria creada.'),
            'data' => [
                'numero_operacion_ferro' => (string)($res['numero_operacion_ferro'] ?? ''),
                'operacion_ferro_id'     => (int)($res['ids']['operacion_ferro_id'] ?? 0),
                'total_bultos'           => (int)($res['total_bultos'] ?? 0),
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
        header('Content-Type: application/json; charset=utf-8');

        $operacion_id = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        $numero       = isset($_GET['numero']) ? trim($_GET['numero']) : '';

        if ($operacion_id <= 0) {
            if ($numero === '') {
                echo json_encode(['ok' => false, 'msg' => 'Falta operacion_id o numero']);
                exit;
            }
            $row = $this->model->select("SELECT id_operacion FROM operaciones WHERE numero_operacion=? LIMIT 1", [$numero]);
            if (!$row) {
                echo json_encode(['ok' => false, 'msg' => 'Operación no encontrada']);
                exit;
            }
            $operacion_id = (int)$row['id_operacion'];
        }

        $items = $this->model->listarSaldosMGPorOperacion($operacion_id);
        echo json_encode(['ok' => true, 'operacion_id' => $operacion_id, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }


    // POST /Operaciones_maritimo_ferro_contenedores/crear_ferro
    public function crear_ferro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
            exit;
        }

        $numero = isset($_POST['numero_ferro']) ? trim((string)$_POST['numero_ferro']) : '';
        if ($numero === '') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'msg' => 'Número de ferro/caja requerido']);
            exit;
        }

        try {
            $res = $this->model->upsertFerro($numero);
            header('Content-Type: application/json; charset=utf-8');
            if (empty($res['ok'])) {
                echo json_encode(['ok' => false, 'msg' => $res['msg'] ?? 'No se pudo crear']);
                exit;
            }
            echo json_encode([
                'ok' => true,
                'id' => (int)$res['id_fisico'],
                'label' => (string)$res['label'],
                'created' => !empty($res['created'])
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'Error al crear ferro/caja']);
        }
        exit;
    }
    public function numero_fo_preview()
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $subtipo = isset($_GET['subtipo_id']) ? (int)$_GET['subtipo_id'] : 26; // FO por defecto
        $res = $this->model->previewNumeroOperacionFerro($subtipo);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'msg'=>'Error al calcular preview FO'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
//editar
public function obtener_operacion()
{
    header('Content-Type: application/json; charset=utf-8');

    $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $numero = isset($_GET['numero']) ? trim($_GET['numero']) : '';

    
    if ($id <= 0 && $numero !== '') {
        $found = $this->model->findOperacionFerroIdByNumero($numero);
        $id = $found ? (int)$found : 0;
    }

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Falta id o número de la operación.']);
        exit;
    }

    try {
        $res = $this->model->getOperacionFerroEditable($id);
        if (empty($res['ok'])) {
            echo json_encode(['ok' => false, 'msg' => $res['msg'] ?? 'No se encontró la operación.']);
            exit;
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al obtener la operación.']);
    }
    exit;
}

public function actualizar_operacion()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');

    $foId = isset($_POST['operacion_ferro_id']) ? (int)$_POST['operacion_ferro_id'] : 0;
    if ($foId <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'operacion_ferro_id requerido']);
        exit;
    }

    // Solo estos campos del header son editables
    $estatus_id  = isset($_POST['estatus_id_f']) ? (int)$_POST['estatus_id_f'] : null;
    $comentarios = isset($_POST['comentariosFerroOP']) ? trim((string)$_POST['comentariosFerroOP']) : null;

    $userId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

    // Acepta 'lineas' o 'asignaciones' como JSON
    $lineas = [];
    $raw = $_POST['lineas'] ?? $_POST['asignaciones'] ?? $_POST['asignacionesHidden'] ?? '';
    if ($raw !== '') {
        $parsed = json_decode((string)$raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            echo json_encode(['ok' => false, 'msg' => 'JSON de líneas inválido.']);
            exit;
        }
        $lineas = array_map(function($x){
            return [
                'cmo_id'           => (int)($x['cmo_id'] ?? 0),
                'bultos_asignados' => (int)($x['bultos_asignados'] ?? 0),
                'comentario'       => isset($x['comentario']) ? trim((string)$x['comentario']) : null,
            ];
        }, $parsed);
    }

    try {
        $payload = [
            'estatus_id'      => $estatus_id,
            'comentarios'     => $comentarios,
            'actualizado_por' => $userId,
            'lineas'          => $lineas,  // si lo omites, el modelo conserva las existentes
        ];

        $res = $this->model->actualizarEstatusYLineasOperacionFerro($foId, $payload);
        if (empty($res['ok'])) {
            echo json_encode(['ok' => false, 'msg' => $res['msg'] ?? 'No se pudo actualizar la operación.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'ok'   => true,
            'msg'  => (string)($res['msg'] ?? 'Operación actualizada.'),
            'data' => $res['data'] ?? ['operacion_ferro_id' => $foId]
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error inesperado al actualizar.']);
    }
    exit;
}


}
