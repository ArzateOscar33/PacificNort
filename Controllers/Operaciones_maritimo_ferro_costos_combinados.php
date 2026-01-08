<?php

class Operaciones_maritimo_ferro_costos_combinados extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) { @session_start(); }

        require_once "Models/OperacionesLogModel.php";
        $this->opLog = new OperacionesLogModel();
    }

    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        if ($operacionId <= 0) return;

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) { error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}"); }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: " . $e->getMessage());
        }
    }

    private function json(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getStr(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
    }

    private function getInt(string $key, int $default = 0): int
    {
        return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
    }

    private function getFloat(string $key, float $default = 0.0): float
    {
        return isset($_GET[$key]) ? (float)$_GET[$key] : $default;
    }

    /**
     * Endpoint: /operaciones_maritimo_ferro_costos_combinados/sugerencias?term=TRH
     * Devuelve array simple para autocomplete: [{id,text}, ...]
     */
    public function sugerencias(): void
    {
        try {
            $term  = $this->getStr('term', '');
            $limit = $this->getInt('limit', 10);

            $rows = $this->model->sugerenciasContenedores($term, $limit);

            $this->json([
                'ok' => true,
                'data' => $rows
            ]);
        } catch (\Throwable $e) {
            error_log("sugerencias error: " . $e->getMessage());
            $this->json([
                'ok' => false,
                'msg' => 'Error al obtener sugerencias'
            ], 500);
        }
    }

    /**
     * Endpoint:
     * /operaciones_maritimo_ferro_costos_combinados/listar?
     *   contenedor=TRHU6818550
     *   &page=1&per_page=10
     *   &term=
     *   &fecha_ini=2025-01-01&fecha_fin=2025-12-31
     *   &tc=17.50&moneda_vista=MXN
     */
    public function listar(): void
    {
        try {
            $contenedor   = $this->getStr('contenedor', '');
            $page         = $this->getInt('page', 1);
            $per_page     = $this->getInt('per_page', 10);
            $term         = $this->getStr('term', '');
            $fecha_ini    = $this->getStr('fecha_ini', '');
            $fecha_fin    = $this->getStr('fecha_fin', '');
            $tc           = $this->getFloat('tc', 17.00);
            $moneda_vista = strtoupper($this->getStr('moneda_vista', 'MXN'));

            // Validación mínima
            if ($contenedor === '') {
                $this->json([
                    'ok' => true,
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'page' => max(1, $page),
                        'per_page' => max(1, $per_page),
                        'total_pages' => 0
                    ],
                    'totals' => [
                        'total_pesos' => 0,
                        'total_dlls'  => 0,
                        'total_mxn'   => 0,
                        'total_usd'   => 0
                    ]
                ]);
            }

            $filters = [
                'contenedor'   => $contenedor,
                'term'         => $term,
                'fecha_ini'    => $fecha_ini,
                'fecha_fin'    => $fecha_fin,
                'tc'           => $tc,
                'moneda_vista' => $moneda_vista
            ];

            $res = $this->model->listarCostosCombinadosPaginado($filters, $page, $per_page);

            // Si deseas loguear la consulta (op marítima no es única; aquí no logueo por defecto)
            // $this->logOp($algunaOperacionId, 'CONSULTA_COSTOS_CONTENEDOR', "Contenedor {$contenedor}");

            $this->json([
                'ok' => true,
                'data' => $res['data'] ?? [],
                'meta' => $res['meta'] ?? [
                    'total' => 0, 'page' => $page, 'per_page' => $per_page, 'total_pages' => 0
                ],
                'totals' => $res['totals'] ?? [
                    'total_pesos' => 0, 'total_dlls' => 0, 'total_mxn' => 0, 'total_usd' => 0
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("listar costos combinados error: " . $e->getMessage());
            $this->json([
                'ok' => false,
                'msg' => 'Error al listar costos combinados'
            ], 500);
        }
    }

    /**
     * Si tu export Excel/PDF ya existe en otro módulo, normalmente aquí solo llamas ese flujo.
     * Te dejo los endpoints listos; los implementamos cuando pegues tu lógica actual de exportación.
     */
    public function export_excel(): void
    {
        $this->json(['ok' => false, 'msg' => 'Pendiente: implementar export_excel'], 501);
    }

    public function export_pdf(): void
    {
        $this->json(['ok' => false, 'msg' => 'Pendiente: implementar export_pdf'], 501);
    }
}
