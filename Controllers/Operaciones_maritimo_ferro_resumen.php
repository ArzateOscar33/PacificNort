<?php
class Operaciones_maritimo_ferro_resumen extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    // ==========================================================
    // GET /operaciones_maritimo_ferro_resumen/sugerencias?term=...
    // Autocomplete SOLO MF (tipo_operacion_id = 11)
    // ==========================================================
    public function sugerencias()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode([
                'status'  => 'warning',
                'data'    => [],
                'message' => 'Sesión expirada'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if ($term === '' || mb_strlen($term, 'UTF-8') < 2) {
            echo json_encode([
                'status' => 'ok',
                'data'   => []
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // ✅ Modelo nuevo
        $rows = $this->model->buscarOperacionesMF($term);

        echo json_encode([
            'status' => 'ok',
            'data'   => is_array($rows) ? $rows : []
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // ==========================================================================
    // GET /operaciones_maritimo_ferro_resumen/listarContenedoresPorOperacion?id_operacion=...
    // Lista SOLO contenedores MARÍTIMOS de la operación MF
    // ==========================================================================
    public function listarContenedoresPorOperacion()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode([
                'status'       => 'warning',
                'contenedores' => [],
                'message'      => 'Sesión expirada'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        $id = isset($_GET['id_operacion']) ? (int)$_GET['id_operacion'] : 0;
        if ($id <= 0) {
            echo json_encode([
                'status'       => 'warning',
                'contenedores' => [],
                'message'      => 'Parámetro id_operacion inválido'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // ✅ Modelo nuevo (solo marítimos)
        $rows = $this->model->getContenedoresPorOperacion($id);

        echo json_encode([
            'status'       => 'ok',
            'contenedores' => is_array($rows) ? $rows : [],
            'meta'         => [
                'operacion_id' => $id,
                'total'        => is_array($rows) ? count($rows) : 0
            ]
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // =================================================================================
    // GET /operaciones_maritimo_ferro_resumen/detalles_contenedor?operacion_id=..&tipo=MARITIMO&id_contenedor=..
    // Detalle SOLO MARÍTIMO (id_contenedor = id_contenedor_maritimo)
    // =================================================================================
    public function detalles_contenedor()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode([
                'status'  => 'warning',
                'data'    => [],
                'message' => 'Sesión expirada'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        $operacionId  = isset($_GET['operacion_id'])  ? (int)$_GET['operacion_id']  : 0;
        $tipoRaw      = isset($_GET['tipo'])          ? trim((string)$_GET['tipo']) : '';
        $contenedorId = isset($_GET['id_contenedor']) ? (int)$_GET['id_contenedor'] : 0;

        if ($operacionId <= 0 || $contenedorId <= 0 || $tipoRaw === '') {
            echo json_encode([
                'status'  => 'warning',
                'data'    => [],
                'message' => 'Parámetros inválidos (operacion_id, tipo, id_contenedor)'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        $tipo = mb_strtoupper($tipoRaw, 'UTF-8');
        if ($tipo === 'M' || $tipo === 'MARITIMO' || $tipo === 'MARÍTIMO') $tipo = 'MARITIMO';

        if ($tipo !== 'MARITIMO') {
            echo json_encode([
                'status'  => 'warning',
                'data'    => [],
                'message' => 'Tipo no soportado. Use MARITIMO.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $row = $this->model->getDetalleContenedorMaritimo($operacionId, $contenedorId);

            if (!$row) {
                echo json_encode([
                    'status'  => 'ok',
                    'tipo'    => 'MARITIMO',
                    'data'    => [],
                    'message' => 'Sin datos'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            // Normaliza si viene como array de filas
            if (is_array($row) && isset($row[0]) && is_array($row[0])) {
                $row = $row[0];
            } elseif (is_object($row)) {
                $row = (array)$row;
            }

            // ✅ Nuevo: string ya listo para badges: "FXCEU10-Ferromex, 767-Lozagui"
            $asigStr = (string)($row['ferros_cajas_vinculadas'] ?? $row['detalle_ferros_cajas'] ?? '');

            // ✅ Útil si quieres render “avanzado” después (listas paralelas)
            $asigFerros = (string)($row['ferros_cajas_lista'] ?? $row['ferros_cajas'] ?? '');
            $asigTrs    = (string)($row['transportistas_ferros_cajas_lista'] ?? $row['transportistas_ferros_cajas'] ?? '');

            $data = [
                'numero_contenedor' => (string)($row['numero_contenedor'] ?? ''),
                'puerto'            => (string)($row['puerto'] ?? ''),
                'eta'               => (string)($row['eta'] ?? ''),
                'etd'               => (string)($row['etd'] ?? ''),
                'bl'                => (string)($row['numero_bl'] ?? ''),
                'comentarios'       => (string)($row['comentarios_operacion'] ?? ($row['observaciones_contenedor'] ?? '')),
                'isf'               => isset($row['isf']) ? (int)$row['isf'] : null,

                // si en DB es datetime/string, mejor mándalo como string
                'cita_puerto'       => (string)($row['cita_puerto'] ?? ''),

                'broker'            => (string)($row['broker'] ?? ''),
                'transportista'     => (string)($row['transportista'] ?? ''),

                /* ✅ NUEVO: asignaciones */
                'ferros_cajas_badges' => $asigStr,          // "FXCEU10-Ferromex, 767-Lozagui"
                'ferros_cajas'        => $asigFerros,       // "FXCEU10, 767"
                'transportistas_fo'   => $asigTrs,          // "Ferromex, Lozagui"
            ];

            echo json_encode([
                'status' => 'ok',
                'tipo'   => 'MARITIMO',
                'data'   => $data,
                'meta'   => [
                    'operacion_id'  => $operacionId,
                    'id_contenedor' => $contenedorId
                ]
            ], JSON_UNESCAPED_UNICODE);
            die();
        } catch (Throwable $e) {
            error_log("DETALLES_CONT_MARITIMO: " . $e->getMessage());
            echo json_encode([
                'status'  => 'error',
                'data'    => [],
                'message' => 'Error al obtener detalles'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // =================================================================================
    // GET /operaciones_maritimo_ferro_resumen/faltantes?operacion_id=..&contenedor_id=..&tipo=M&q=...
    // Faltantes SOLO para CONTENEDOR MARÍTIMO
    // Nota: contenedor_id aquí es id_contenedor_maritimo (como ya usas en el front)
    // =================================================================================
    public function faltantes()
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode([]);
            return;
        }

        $operacionId        = (int)($_GET['operacion_id'] ?? 0);
        $contenedorMarId    = (int)($_GET['contenedor_id'] ?? 0); // 👈 id_contenedor_maritimo
        $busca              = isset($_GET['q']) ? trim($_GET['q']) : null;

        if ($operacionId <= 0 || $contenedorMarId <= 0) {
            echo json_encode([]);
            return;
        }

        try {
            $rows = $this->model->faltantesPorContenedorMaritimo($operacionId, $contenedorMarId, true, $busca);
            echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("FALTANTES_RESUMEN_MF: " . $e->getMessage());
            echo json_encode([]);
        }
    }

    // =========================
    // COSTOS (solo por operación MF)
    // =========================
    public function costos_totales_operacion()
    {
        header('Content-Type: application/json; charset=utf-8');

        $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($operacionId <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Parámetro operacion_id inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $total = $this->model->getCostosTotalesOperacion($operacionId);
            echo json_encode([
                'status' => 'ok',
                'data'   => [
                    'operacion_id' => $operacionId,
                    'total'        => $total,
                    'total_fmt'    => number_format($total, 2),
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("ERR costos_totales_operacion: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'No fue posible calcular el total'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function costos_desglosados_operacion()
    {
        header('Content-Type: application/json; charset=utf-8');

        $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($operacionId <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Parámetro operacion_id inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $rows = $this->model->getCostosDesglosadosOperacion($operacionId);
            echo json_encode([
                'status' => 'ok',
                'data'   => is_array($rows) ? $rows : []
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("ERR costos_desglosados_operacion: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'No fue posible obtener el desglose'], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================
    // EVENTOS (solo por contenedor MARÍTIMO)
    // =========================
    public function eventos_contenedor()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode(['status' => 'warning', 'data' => [], 'message' => 'Sesión expirada'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $operacionId  = (int)($_GET['operacion_id'] ?? 0);
        $idContenedor = (int)($_GET['id_contenedor'] ?? 0);
        $tipoRaw      = trim($_GET['tipo'] ?? '');

        if ($operacionId <= 0 || $idContenedor <= 0 || $tipoRaw === '') {
            echo json_encode(['status' => 'error', 'data' => [], 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $t = mb_strtoupper($tipoRaw, 'UTF-8');
        if ($t === 'M' || $t === 'MARITIMO' || $t === 'MARÍTIMO') $t = 'MARITIMO';

        if ($t !== 'MARITIMO') {
            echo json_encode(['status' => 'warning', 'data' => [], 'message' => 'Tipo no soportado. Use MARITIMO.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $rows = $this->model->getEventosLogisticosMaritimo($operacionId, $idContenedor);

            echo json_encode([
                'status' => 'ok',
                'data'   => is_array($rows) ? $rows : [],
                'meta'   => [
                    'operacion_id'  => $operacionId,
                    'tipo'          => 'MARITIMO',
                    'id_contenedor' => $idContenedor,
                    'total'         => is_array($rows) ? count($rows) : 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("ERR eventos_contenedor_maritimo: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'data' => [], 'message' => 'No fue posible obtener los eventos'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function eventos_progreso()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode(['status' => 'warning', 'data' => [], 'message' => 'Sesión expirada'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $operacionId  = (int)($_GET['operacion_id'] ?? 0);
        $idContenedor = (int)($_GET['id_contenedor'] ?? 0);
        $tipoRaw      = trim($_GET['tipo'] ?? '');

        if ($operacionId <= 0 || $idContenedor <= 0 || $tipoRaw === '') {
            echo json_encode(['status' => 'error', 'data' => [], 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $t = mb_strtoupper($tipoRaw, 'UTF-8');
        if ($t === 'M' || $t === 'MARITIMO' || $t === 'MARÍTIMO') $t = 'MARITIMO';

        if ($t !== 'MARITIMO') {
            echo json_encode(['status' => 'warning', 'data' => [], 'message' => 'Tipo no soportado. Use MARITIMO.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $data = $this->model->getEventosProgresoMaritimo($operacionId, $idContenedor);
            echo json_encode(['status' => 'ok', 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("ERR eventos_progreso_maritimo: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'data' => [], 'message' => 'No fue posible obtener el progreso'], JSON_UNESCAPED_UNICODE);
        }
    }
}
