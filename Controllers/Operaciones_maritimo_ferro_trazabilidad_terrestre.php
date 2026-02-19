<?php

class Operaciones_maritimo_ferro_trazabilidad_terrestre extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    // =========================
    // Helpers
    // =========================
    private function json(array $payload, int $httpCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['status' => 'error', 'msg' => 'Método no permitido'], 405);
        }
    }

    private function usuarioId(): int
    {
        // Ajusta a tu sesión real
        return isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
    }

    // =========================
    // Endpoint 1:
    // Cargar panel trazabilidad al seleccionar ferro/caja
    // =========================
    public function cargarPanel()
    {
        $this->requirePost();

        $operacionId        = isset($_POST['operacion_id']) ? (int)$_POST['operacion_id'] : 0;          // NT actual (hidden asigFerro_operacionId)
        $contenedorFisicoId = isset($_POST['contenedor_fisico_id']) ? (int)$_POST['contenedor_fisico_id'] : 0; // hidden asigFerro_ferroFisicoId
        $fechaSalida        = trim((string)($_POST['fecha_salida'] ?? ''));                               // viene de la fila seleccionada (F. salida)

        if ($operacionId <= 0) {
            $this->json(['status' => 'error', 'msg' => 'Operación inválida']);
        }
        if ($contenedorFisicoId <= 0) {
            $this->json(['status' => 'error', 'msg' => 'Ferro/Caja inválido']);
        }
        if ($fechaSalida === '') {
            $this->json(['status' => 'error', 'msg' => 'Fecha salida requerida']);
        }

        // Panel = origen (puerto NT), destino (FO), ubicación actual (última traza por FO)
        $panel = $this->model->obtenerPanelTrazabilidad($operacionId, $contenedorFisicoId, $fechaSalida);

        // Historial (opcional)
        $historial = [];
        if (!empty($panel['operacion_ferro_id'])) {
            $historial = $this->model->listarHistorial($contenedorFisicoId, (int)$panel['operacion_ferro_id'], 50);
        }

        $this->json([
            'status'   => 'success',
            'panel'    => $panel,
            'historial' => $historial,
        ]);
    }

    // =========================
    // Endpoint 2:
    // Guardar ubicación (insertar trazabilidad_ferro)
    // =========================
    public function guardar()
    {
        $this->requirePost();

        $usuarioId = $this->usuarioId();
        if ($usuarioId <= 0) {
            $this->json(['status' => 'error', 'msg' => 'Sesión inválida'], 401);
        }

        // Mapeo directo a tu modelo insertarTrazabilidad()
        $in = [
            'contenedor_fisico_id' => isset($_POST['contenedor_fisico_id']) ? (int)$_POST['contenedor_fisico_id'] : 0,
            'operacion_id'         => isset($_POST['operacion_id']) ? (int)$_POST['operacion_id'] : 0,
            'fecha_salida'         => trim((string)($_POST['fecha_salida'] ?? '')),
            'ubicacion_id'         => isset($_POST['ubicacion_id']) ? (int)$_POST['ubicacion_id'] : 0,
            'fecha_evento'         => trim((string)($_POST['fecha_evento'] ?? '')),
            'referencia'           => isset($_POST['referencia']) ? trim((string)$_POST['referencia']) : null,
            'notas'                => isset($_POST['notas']) ? trim((string)$_POST['notas']) : null,
        ];

        $res = $this->model->insertarTrazabilidad($in, $usuarioId);
        if (($res['status'] ?? 'error') !== 'success') {
            $this->json($res);
        }

        // Opcional: regresar panel actualizado para refrescar UI sin otra llamada
        $panel = $this->model->obtenerPanelTrazabilidad($in['operacion_id'], $in['contenedor_fisico_id'], $in['fecha_salida']);
        $historial = [];
        if (!empty($panel['operacion_ferro_id'])) {
            $historial = $this->model->listarHistorial((int)$in['contenedor_fisico_id'], (int)$panel['operacion_ferro_id'], 50);
        }

        $this->json([
            'status'    => 'success',
            'msg'       => $res['msg'] ?? 'Ubicación guardada',
            'id'        => $res['id'] ?? null,
            'panel'     => $panel,
            'historial' => $historial,
        ]);
    }
}
