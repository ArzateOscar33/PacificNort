<?php

class Operaciones_maritimo_ferro_asignacion_ferro extends Controller
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

    /* =========================================================
       ENDPOINT 1: Lista izquierda (Ferros/Cajas de esta operación)
       URL sugerida:
       BASE_URL + "Operaciones_maritimo_ferro_asignacion_ferro/listarFerrosOperacion"
       Params (POST/GET):
       - operacion_id (int)
    ========================================================= */
    public function listarFerrosOperacion()
    {
        try {
            // permite POST o GET (tu JS puede usar cualquiera)
            $operacionId = isset($_POST['operacion_id']) ? (int)$_POST['operacion_id'] : (int)($_GET['operacion_id'] ?? 0);

            if ($operacionId <= 0) {
                echo json_encode(['status' => 'error', 'msg' => 'operacion_id requerido', 'rows' => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $rows = $this->model->listarFerrosDeOperacion($operacionId);

            echo json_encode([
                'status' => 'success',
                'rows'   => is_array($rows) ? $rows : [],
                'total'  => is_array($rows) ? count($rows) : 0
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("listarFerrosOperacion error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error interno', 'rows' => []], JSON_UNESCAPED_UNICODE);
        }
    }

    /* =========================================================
       ENDPOINT 2: Lista derecha (Operaciones en Ferro/Caja + fecha)
       URL sugerida:
       BASE_URL + "Operaciones_maritimo_ferro_asignacion_ferro/listarOpsEnFerro"
       Params (POST/GET):
       - numero_ferro (string)
       - fecha (YYYY-MM-DD)   <-- IMPORTANTE: en tu lógica FO = (ferro, fecha)
    ========================================================= */
    public function listarOpsEnFerro()
    {
        try {
            $numeroFerro = isset($_POST['numero_ferro']) ? trim((string)$_POST['numero_ferro']) : trim((string)($_GET['numero_ferro'] ?? ''));
            $fecha       = isset($_POST['fecha']) ? trim((string)$_POST['fecha']) : trim((string)($_GET['fecha'] ?? ''));

            if ($numeroFerro === '' || $fecha === '') {
                echo json_encode(['status' => 'error', 'msg' => 'numero_ferro y fecha son requeridos', 'rows' => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            // validación básica YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                echo json_encode(['status' => 'error', 'msg' => 'Formato de fecha inválido (YYYY-MM-DD)', 'rows' => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $rows = $this->model->listarOperacionesEnFerroFecha($numeroFerro, $fecha);

            echo json_encode([
                'status' => 'success',
                'rows'   => is_array($rows) ? $rows : [],
                'total'  => is_array($rows) ? count($rows) : 0
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("listarOpsEnFerro error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error interno', 'rows' => []], JSON_UNESCAPED_UNICODE);
        }
    }
}
