<?php
class Operaciones_por_partida extends Controller
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

    public function index()
    {
        $data['title'] = 'Operaciones por Partida';
        $data['bodegas'] = $this->model->listarBodegasActivas();

        // Si en tu vista el filtro de bodegas se llena con PHP:
        // $data['bodegas'] = $this->model->getBodegasActivas();  (opcional, después)
        $this->views->getView('admin/Operaciones_por_partida', "ver", $data);
    }

    /**
     * Endpoint JSON para listar facturas
     * URL ejemplo:
     *  /Operaciones_por_partida/listar?bodega_id=&term=&fi=&ff=&page=1&per_page=10
     */
    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // ====== Inputs (GET) ======
            $bodegaId = isset($_GET['bodega_id']) ? trim($_GET['bodega_id']) : '';
            $term     = isset($_GET['term']) ? trim($_GET['term']) : '';
            $fi       = isset($_GET['fi']) ? trim($_GET['fi']) : '';
            $ff       = isset($_GET['ff']) ? trim($_GET['ff']) : '';

            $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

            if ($page < 1) $page = 1;
            if ($perPage < 1) $perPage = 10;

            // Limitar per_page a opciones típicas
            $allowed = [10, 25, 50, 100];
            if (!in_array($perPage, $allowed, true)) $perPage = 10;

            // ====== Modelo ======
            $result = $this->model->listarFacturas([
                'bodega_id' => $bodegaId,
                'term'      => $term,
                'fi'        => $fi,
                'ff'        => $ff,
                'page'      => $page,
                'per_page'  => $perPage
            ]);

            $rows  = $result['rows'] ?? [];
            $total = (int)($result['total'] ?? 0);

            $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

            echo json_encode([
                'ok'   => true,
                'data' => $rows,
                'meta' => [
                    'total'       => $total,
                    'page'        => $page,
                    'per_page'    => $perPage,
                    'total_pages' => $totalPages
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/listar ERROR: " . $e->getMessage());

            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar facturas.',
                'data' => [],
                'meta' => [
                    'total' => 0, 'page' => 1, 'per_page' => 10, 'total_pages' => 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }


    public function listarProductos()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        // ===== Inputs (GET) =====
        $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
        $term      = isset($_GET['term']) ? trim($_GET['term']) : '';

        $page      = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage   = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

        if ($facturaId <= 0) {
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Factura inválida.',
                'data' => [],
                'meta' => ['total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0],
                'totals' => ['total_cajas' => 0, 'total_piezas' => 0, 'total_pallets_inv' => 0]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 50;

        // Limitar per_page (modal)
        $allowed = [10, 25, 50, 100, 200];
        if (!in_array($perPage, $allowed, true)) $perPage = 50;

        // ===== Modelo =====
        $result = $this->model->listarProductos($facturaId, [
            'term'     => $term,
            'page'     => $page,
            'per_page' => $perPage
        ]);

        $rows   = $result['rows'] ?? [];
        $total  = (int)($result['total'] ?? 0);
        $totals = $result['totals'] ?? [
            'total_cajas' => 0,
            'total_piezas' => 0,
            'total_pallets_rcv' => 0
        ];

        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

        echo json_encode([
            'ok'     => true,
            'data'   => $rows,
            'meta'   => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages
            ],
            'totals' => [
                'total_cajas'       => (int)($totals['total_cajas'] ?? 0),
                'total_piezas'      => (int)($totals['total_piezas'] ?? 0),
                'total_pallets_rcv' => (int)($totals['total_pallets_rcv'] ?? 0),
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/listarProductos ERROR: " . $e->getMessage());

        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al listar productos.',
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0],
            'totals' => ['total_cajas' => 0, 'total_piezas' => 0, 'total_pallets_rcv' => 0]
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

public function registrar()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'ok'  => false,
                'msg' => 'Método no permitido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ====== Inputs (POST) desde tu modal ======
        // IDs/vista:
        // - operaciones_partida_bodega (select)
        // - operaciones_partida_factura (input)
        // - operaciones_partida_proveedor (input)
        // - operaciones_partida_revision (checkbox)
        // - operaciones_partida_pallets_rcv (number)
        // - operaciones_partida_fechaRecibido (date)
        // - operaciones_partida_notas (text)

        $bodegaId      = isset($_POST['operaciones_partida_bodega']) ? (int)$_POST['operaciones_partida_bodega'] : 0;
        $numeroFactura = isset($_POST['invoice_number']) ? trim((string)$_POST['invoice_number']) : (isset($_POST['operaciones_partida_factura']) ? trim((string)$_POST['operaciones_partida_factura']) : '');
        $proveedor     = isset($_POST['vendor_name']) ? trim((string)$_POST['vendor_name']) : (isset($_POST['operaciones_partida_proveedor']) ? trim((string)$_POST['operaciones_partida_proveedor']) : '');

        // checkbox switch: puede venir "on", "1", etc.
        $revisionPasa  = isset($_POST['revision_pasa']) ? 1 : 0;

        $palletsRcv    = isset($_POST['pallets_rcv']) ? (int)$_POST['pallets_rcv'] : (isset($_POST['operaciones_partida_pallets_rcv']) ? (int)$_POST['operaciones_partida_pallets_rcv'] : 0);

        $fechaRecibido = isset($_POST['received_date']) ? trim((string)$_POST['received_date']) : (isset($_POST['operaciones_partida_fechaRecibido']) ? trim((string)$_POST['operaciones_partida_fechaRecibido']) : '');
        $notas         = isset($_POST['comentarios']) ? trim((string)$_POST['comentarios']) : (isset($_POST['operaciones_partida_notas']) ? trim((string)$_POST['operaciones_partida_notas']) : '');

        // ====== Sesión: id usuario (FK: usuarios.id_usuario, ON DELETE SET NULL) ======
        // Ajusta el nombre exacto de tu sesión si difiere.
        $creadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

        // ====== Modelo ======
        $resp = $this->model->registrarFactura([
            'bodega_id'      => $bodegaId,
            'numero_factura' => $numeroFactura,
            'proveedor'      => $proveedor,
            'revision_pasa'  => $revisionPasa,
            'pallets_rcv'    => $palletsRcv,
            'fecha_recibido' => $fechaRecibido,
            'notas'          => $notas,
            'creado_por'     => $creadoPor
        ]);

        if (empty($resp) || empty($resp['ok'])) {
            echo json_encode([
                'ok'        => false,
                'msg'       => $resp['msg'] ?? 'No se pudo registrar la factura.',
                'id_factura'=> $resp['id_factura'] ?? null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // (Opcional) traer la factura recién creada para refrescar UI
        $idFactura = (int)$resp['id_factura'];
        $factura   = $this->model->getFacturaById($idFactura);

        echo json_encode([
            'ok'        => true,
            'msg'       => $resp['msg'] ?? 'Factura registrada correctamente.',
            'id_factura'=> $idFactura,
            'factura'   => $factura ?: null
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/registrar ERROR: " . $e->getMessage());

        echo json_encode([
            'ok'  => false,
            'msg' => 'Ocurrió un error al registrar la factura.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

}
