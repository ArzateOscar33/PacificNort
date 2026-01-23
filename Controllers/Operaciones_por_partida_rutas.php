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

    // ==== RUTAS: SUGERIR CAJA/FERRO ====
    public function sugerirFerroCajaRutas()
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

            // IMPORTANTE: en tu controller original llamabas sugerirFisicos()
            // aquí lo dejamos igual, pero el MODEL nuevo ya trae ese wrapper.
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
}
