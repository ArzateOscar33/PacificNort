<?php
class Operaciones_maritimo_ferro_trazabilidad extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }
    public function listar()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $filters = [
            'term'         => $_GET['term'] ?? '',
            'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'    => $_GET['fecha_fin'] ?? '',
        ];

        $res = $this->model->listarPaginado($filters, $page, $per);

        echo json_encode([
            'status' => 'success',
            'rows'   => $res['rows'],
            'meta'   => [
                'page'        => $res['page'],
                'per_page'    => $res['per_page'],
                'total'       => $res['total'],
                'total_pages' => $res['total_pages'],
            ],
        ]);
        die();
    }

    public function listarHistorial()
    {
        header('Content-Type: application/json; charset=utf-8');



        $contenedorFisicoId = isset($_GET['contenedor_fisico_id']) ? (int)$_GET['contenedor_fisico_id'] : 0;
        $operacionFerroId   = isset($_GET['operacion_ferro_id']) ? (int)$_GET['operacion_ferro_id'] : 0;
        $limit              = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        if ($contenedorFisicoId <= 0 || $operacionFerroId <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Parámetros incompletos (contenedor_fisico_id y operacion_ferro_id).',
                'rows'   => [],
            ]);
            return;
        }

        $limit = max(1, min(200, $limit));

        try {
            $res = $this->model->listarHistorial($contenedorFisicoId, $operacionFerroId, $limit);

            $rows = $res['rows'] ?? [];
            $meta = $res['meta'] ?? [];

            echo json_encode([
                'status' => 'success',
                'rows'   => $rows,
                'meta'   => [
                    'contenedor_fisico_id'  => (int)$contenedorFisicoId,
                    'operacion_ferro_id'    => (int)$operacionFerroId,
                    'limit'                 => (int)$limit,
                    'count'                 => is_array($rows) ? count($rows) : 0,

                    // ✅ nuevos campos para UI
                    'destino_id_efectivo'   => $meta['destino_id_efectivo'] ?? null,
                    'ubicacion_id_last'     => $meta['ubicacion_id_last'] ?? null,
                    'destino_nombre'        => $meta['destino_nombre'] ?? null,
                    'ubicacion_nombre_last' => $meta['ubicacion_nombre_last'] ?? null,
                    'llego_destino'         => (int)($meta['llego_destino'] ?? 0),
                ],
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'Error al obtener historial.',
                'rows'   => [],
            ]);
        }
    }
}
