<?php
class Operaciones_por_partida_rutas extends Controller
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

    // ===================== RUTAS: SUGERENCIAS FACTURAS =====================
    public function sugerirFacturasRutas()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

            if ($limit < 1) $limit = 10;
            if ($limit > 25) $limit = 25;

            if ($term === '' || mb_strlen($term) < 2) {
                echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rows = $this->model->sugerirFacturas($term, $limit);

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_rutas/sugerirFacturasRutas ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al buscar facturas.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ===================== RUTAS: LISTAR PRODUCTOS (TABLA) =====================
    public function listarProductosRutas()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
            $term      = isset($_GET['term']) ? trim((string)$_GET['term']) : '';

            if ($facturaId <= 0) {
                echo json_encode([
                    'ok'   => false,
                    'msg'  => 'Factura inválida.',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!$this->model->existeFacturaActiva($facturaId)) {
                echo json_encode([
                    'ok'   => false,
                    'msg'  => 'La factura no existe o está inactiva.',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rows = $this->model->listarProductosRutas($facturaId, $term);

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_rutas/listarProductosRutas ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar productos de rutas.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ===================== RUTAS: LISTAR ENVIOS DE UN PRODUCTO =====================
    public function listarEnviosProductoRutas()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId  = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
            $productoId = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;

            if ($facturaId <= 0 || $productoId <= 0) {
                echo json_encode([
                    'ok'   => false,
                    'msg'  => 'Parámetros inválidos.',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rows = $this->model->listarEnviosProducto($facturaId, $productoId);

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_rutas/listarEnviosProductoRutas ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar envíos del producto.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ==== RUTAS: LISTAR CIUDADES (DESTINOS) ====
    public function listarCiudadesRutas()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $rows = $this->model->listarCiudadesActivas();

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_rutas/listarCiudadesRutas ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar ciudades.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

 

    // ==== RUTAS: SUGERIR CAJA/FERRO (contenedores_fisicos) ====
public function sugerirFerroCajaRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if ($limit < 1)  $limit = 10;
        if ($limit > 25) $limit = 25;

        if ($term === '' || mb_strlen($term) < 2) {
            echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // El model debe tener sugerirFisicos() -> sugerirCajaFerro()
        $rows = $this->model->sugerirFisicos($term, $limit);

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida_rutas/sugerirFerroCajaRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al buscar Caja/Ferro.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


// ==== RUTAS: SUGERIR CIUDADES (DESTINOS) ====
public function sugerirCiudadesRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if ($limit < 1)  $limit = 10;
        if ($limit > 25) $limit = 25;

        if ($term === '' || mb_strlen($term) < 2) {
            echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = $this->model->sugerirCiudades($term, $limit);

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida_rutas/sugerirCiudadesRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al buscar ciudades.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


//alta

// ===================== RUTAS: GUARDAR ENVIOS (MULTI-ROW) =====================
public function guardarEnviosRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok'=>false,'msg'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $facturaId  = (int)($_POST['factura_id'] ?? 0);
        $productoId = (int)($_POST['producto_id'] ?? 0);

        $enviosRaw = $_POST['envios'] ?? '[]';
        $envios = is_array($enviosRaw) ? $enviosRaw : json_decode((string)$enviosRaw, true);

        if ($facturaId <= 0 || $productoId <= 0 || !is_array($envios) || count($envios) === 0) {
            echo json_encode(['ok'=>false,'msg'=>'Datos inválidos (factura/producto/envíos).'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Normaliza llaves a lo que espera tu Model guardarEnviosProducto()
        $norm = [];
        foreach ($envios as $i => $r) {
            $norm[] = [
                'ciudad_id'    => (int)($r['destino_id'] ?? $r['ciudad_id'] ?? 0),
                'fecha_envio'  => trim((string)($r['fecha_envio'] ?? '')),
                'fisico_id'    => (int)($r['fisico_id'] ?? 0),
                'fisico_texto' => trim((string)($r['fisico_txt'] ?? $r['fisico_texto'] ?? '')),
                'cajas'        => (int)($r['cajas'] ?? 0),
                'estatus'      => (int)($r['estatus'] ?? 1),
                'nota'         => trim((string)($r['nota'] ?? '')),
            ];
        }

        // Aquí se resuelve TODO: totales/enviadas/restantes, ciudad válida, fisico crear si no existe, inserts, etc.
        $res = $this->model->guardarEnviosProducto($facturaId, $productoId, $norm);

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida_rutas/guardarEnviosRutas ERROR: " . $e->getMessage());
        echo json_encode(['ok'=>false,'msg'=>'Ocurrió un error al guardar envíos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}



}
