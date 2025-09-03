<?php
class Operaciones_maritimas_resumen extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    // GET /operaciones_maritimas_resumen/sugerencias?term=EN-
    public function sugerencias()
    {
        header('Content-Type: application/json; charset=utf-8');

        // 1) Validar sesión (ajusta a tu lógica si usas otro mecanismo)
        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode([
                'status'  => 'warning',
                'data'    => [],
                'message' => 'Sesión expirada'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // 2) Leer y validar term
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if ($term === '' || mb_strlen($term, 'UTF-8') < 2) {
            echo json_encode([
                'status' => 'ok',
                'data'   => []
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // 3) Consultar modelo (usa el método que implementaste)
        // Nota: si tu controller usa $this->resumenModel, cámbialo aquí.
        $rows = $this->model->buscarOperacionesConContenedores($term);

        // 4) Responder JSON homogéneo
        echo json_encode([
            'status' => 'ok',
            'data'   => is_array($rows) ? $rows : []
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // GET /operaciones_maritimas_resumen/listarContenedoresPorOperacion?id_operacion=48
    public function listarContenedoresPorOperacion()
    {
        header('Content-Type: application/json; charset=utf-8');

        // 1) Validar sesión
        if (empty($_SESSION['nombre_usuario'])) {
            echo json_encode([
                'status'  => 'warning',
                'contenedores' => [],
                'message' => 'Sesión expirada'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // 2) Leer y validar id_operacion
        $id = isset($_GET['id_operacion']) ? (int)$_GET['id_operacion'] : 0;
        if ($id <= 0) {
            echo json_encode([
                'status'  => 'warning',
                'contenedores' => [],
                'message' => 'Parámetro id_operacion inválido'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // 3) Consultar modelo (UNION ALL marítimos + físicos)
        // IMPORTANTE: tu método debe bindear el id DOS veces ([$id, $id])
        $rows = $this->model->getContenedoresPorOperacion($id);

        // 4) Responder JSON homogéneo
        echo json_encode([
            'status'       => 'ok',
            'contenedores' => is_array($rows) ? $rows : [], // si el helper devolviera false
            'meta'         => ['operacion_id' => $id, 'total' => is_array($rows) ? count($rows) : 0]
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function detalles_contenedor()
{
    header('Content-Type: application/json; charset=utf-8');

    // 1) Sesión
    if (empty($_SESSION['nombre_usuario'])) {
        echo json_encode([
            'status'  => 'warning',
            'data'    => [],
            'message' => 'Sesión expirada'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // 2) Leer y validar params
    $operacionId   = isset($_GET['operacion_id'])   ? (int)$_GET['operacion_id']   : 0;
    $tipoRaw       = isset($_GET['tipo'])           ? trim($_GET['tipo'])           : '';
    $contenedorId  = isset($_GET['id_contenedor'])  ? (int)$_GET['id_contenedor']  : 0;

    if ($operacionId <= 0 || $contenedorId <= 0 || $tipoRaw === '') {
        echo json_encode([
            'status'  => 'warning',
            'data'    => [],
            'message' => 'Parámetros inválidos (operacion_id, tipo, id_contenedor)'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // Normaliza tipo
    $tipo = strtoupper($tipoRaw);
    if ($tipo === 'FISICO') { $tipo = 'FERRO'; } // tratamos FISICO y FERRO como el mismo caso

    // 3) Ruteo por tipo
    try {
        if ($tipo === 'MARITIMO') {
            $row = $this->model->getDetalleContenedorMaritimo($operacionId, $contenedorId);
                if (!$row) {
                    echo json_encode(['status'=>'ok','tipo'=>'MARITIMO','data'=>[],'message'=>'Sin datos'], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Normaliza $row a una sola fila asociativa
                if (is_array($row)) {
                    // si vino como array de filas, toma la primera
                    if (isset($row[0]) && is_array($row[0])) { $row = $row[0]; }
                } elseif (is_object($row)) {
                    // si vino como objeto, castea a array
                    $row = (array)$row;
                }

                $data = [
                    'numero_contenedor' => (string)($row['numero_contenedor'] ?? ''),
                    'puerto'            => (string)($row['puerto'] ?? ''),
                    'eta'               => (string)($row['eta'] ?? ''),
                    'etd'               => (string)($row['etd'] ?? ''),
                    'bl'                => (string)($row['numero_bl'] ?? ''),
                    'comentarios'       => (string)($row['comentarios_operacion'] ?? ($row['observaciones_contenedor'] ?? ''))
                ];

                echo json_encode([
                    'status' => 'ok',
                    'tipo'   => 'MARITIMO',
                    'data'   => $data,
                    'meta'   => ['operacion_id'=>$operacionId,'id_contenedor'=>$contenedorId]
                ], JSON_UNESCAPED_UNICODE);
                die();

        }

if ($tipo === 'FERRO') {
    $row = $this->model->getDetalleContenedorFisico($operacionId, $contenedorId);
    if (!$row) {
        echo json_encode([
            'status'  => 'ok',
            'tipo'    => 'FERRO',
            'data'    => [],
            'message' => 'Sin datos para ese contenedor'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // --- Normaliza a una sola fila asociativa ---
    if (is_array($row)) {
        // Si vino como array de filas, toma la primera
        if (isset($row[0]) && (is_array($row[0]) || is_object($row[0]))) {
            $row = $row[0];
        }
    }
    if (is_object($row)) {
        $row = (array)$row;
    }

    // --- Mapea con tolerancia al nombre de las llaves ---
    $data = [
        'numero_ferro'  => (string)($row['numero_ferro'] ?? $row['NUMERO_FERRO'] ?? $row['numeroFerro'] ?? ''),
        'arribo_puerto' => (string)($row['arribo_a_puerto'] ?? $row['arribo_puerto'] ?? $row['ARRIBO_A_PUERTO'] ?? ''),
        'bultos'        => (int)   ($row['bultos'] ?? $row['BULTOS'] ?? 0),
        'comentarios'   => (string)($row['comentarios_contenedor'] ?? $row['comentarios'] ?? '')
    ];

    echo json_encode([
        'status' => 'ok',
        'tipo'   => 'FERRO',
        'data'   => $data,
        'meta'   => ['operacion_id' => $operacionId, 'id_contenedor' => $contenedorId]
    ], JSON_UNESCAPED_UNICODE);
    die();
        }

        // Tipo no soportado
        echo json_encode([
            'status'  => 'warning',
            'data'    => [],
            'message' => 'Tipo no soportado. Use MARITIMO o FERRO.'
        ], JSON_UNESCAPED_UNICODE);
        die();

    } catch (Throwable $e) {
        echo json_encode([
            'status'  => 'error',
            'data'    => [],
            'message' => 'Error al obtener detalles: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        die();
    }
}

}
