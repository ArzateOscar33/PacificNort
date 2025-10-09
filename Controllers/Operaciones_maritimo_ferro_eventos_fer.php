<?php
class Operaciones_maritimo_ferro_eventos_fer extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    }

    /* =========================================================
       LISTAR (FERRO)
       GET /Operaciones_maritimo_ferro_eventos_fer/listar
       Params:
         - page, per_page
         - op_id   (operaciones_ferroviarias.id_operacion_ferro)
         - cont_id (contenedor_fisico_id / cfo_id)
         - q       (texto: numero_operacion, numero_ferro, cliente)
       Respuesta:
         { data: [...], total: N, page: X, per_page: Y }
       ========================================================= */
    public function listar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page    = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 10;

        // Filtros
        $opId   = (isset($_GET['op_id'])   && $_GET['op_id']   !== '') ? (int)$_GET['op_id']   : null;
        $contId = (isset($_GET['cont_id']) && $_GET['cont_id'] !== '') ? (int)$_GET['cont_id'] : null; // cfo_id
        $q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        try {
            $res = $this->model->listarEventosFERPaginado($page, $perPage, $opId, $contId, $q);

            echo json_encode([
                'data'     => $res['rows']     ?? [],
                'total'    => (int)($res['total']    ?? 0),
                'page'     => (int)($res['page']     ?? $page),
                'per_page' => (int)($res['per_page'] ?? $perPage),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('listar FER eventos: ' . $e->getMessage());
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

    /* =========================================================
       COLUMNAS (catálogo de eventos TERRESTRES para FERRO)
       GET /Operaciones_maritimo_ferro_eventos_fer/eventos_ferro_columnas
       Respuesta:
         { ok:true, count:N, columns:[{id,nombre,key}, ...] }
       ========================================================= */
    public function eventos_ferro_columnas()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            // Método espejo en el modelo: id_tipo_operacion = 2 (Terrestre/Ferro)
            $rows = $this->model->listarEventosTerrestresParaColumnas();

            $out = array_map(function ($r) {
                return [
                    'id'     => (int)($r['id'] ?? $r['id_tipo_evento'] ?? 0),
                    'nombre' => (string)($r['nombre'] ?? ''),
                    'key'    => (string)($r['key'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode([
                'ok'      => true,
                'count'   => count($out),
                'columns' => $out
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('eventos_ferro_columnas: '.$e->getMessage());
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'No fue posible obtener las columnas de eventos ferroviarios'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
