<?php

class Piso extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }

    /**
     * (Opcional) Si tienes una vista dedicada:
     * public function index()
     * {
     *     $this->views->getView($this, "index"); // o la vista que corresponda
     * }
     */

    /**
     * Listar mercancía en piso (paginado + filtros).
     * Devuelve JSON:
     * {
     *   ok: true,
     *   data: [...],
     *   meta: { total, page, per_page, total_pages },
     *   badges: { total, tj, sd }
     * }
     */
    public function listar()
    {
        // Acepta POST (FormData) o GET (querystring)
        $term      = isset($_REQUEST['term']) ? trim((string)$_REQUEST['term']) : '';
        $bodega    = isset($_REQUEST['bodega']) ? trim((string)$_REQUEST['bodega']) : '';
        $date_from = isset($_REQUEST['date_from']) ? trim((string)$_REQUEST['date_from']) : '';
        $date_to   = isset($_REQUEST['date_to']) ? trim((string)$_REQUEST['date_to']) : '';

        $page      = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
        $per_page  = isset($_REQUEST['per_page']) ? (int)$_REQUEST['per_page'] : 10;

        // Normalizaciones
        if ($page <= 0) $page = 1;
        if ($per_page <= 0) $per_page = 10;
        if ($per_page > 200) $per_page = 200;

        // Validar bodega (si viene algo raro lo ignoramos)
        if ($bodega !== '' && !in_array($bodega, ['BODEGA TJ', 'BODEGA SD'], true)) {
            $bodega = '';
        }

        // Validación ligera de fechas (formato esperado YYYY-MM-DD)
        if ($date_from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = '';
        if ($date_to   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to = '';

        $filters = [
            'term'      => $term,
            'bodega'    => $bodega,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        try {
            $resp = $this->model->listarEnPisoPaginado($filters, $page, $per_page);

            $out = [
                'ok'     => true,
                'data'   => $resp['data'] ?? [],
                'meta'   => $resp['meta'] ?? ['total' => 0, 'page' => $page, 'per_page' => $per_page, 'total_pages' => 1],
                'badges' => $resp['badges'] ?? ['total' => 0, 'tj' => 0, 'sd' => 0],
            ];

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            die();

        } catch (Throwable $e) {
            error_log('Piso/listar error: ' . $e->getMessage());

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'  => false,
                'msg' => 'Error al listar mercancía en piso.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }
    }


}

