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
}
