<?php
class Operaciones_por_partida_ferros extends Controller
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
        $data['title'] = 'Revisión de Ferros y Envíos';

        // OJO: cambia la ruta de vista a la que creaste para este módulo
        // Ej: Views/admin/Operaciones_por_partida_ferros/ver.php
        $this->views->getView('admin/Operaciones_por_partida_ferros', "ver", $data);
    }

    /* =========================
       AJAX: SUGERENCIAS FERROS
       Endpoint sugerido:
       Operaciones_por_partida_ferros/sugerirFerros
       ========================= */
    public function sugerirFerros()
    {
        try {
            $term  = trim((string)($_GET['term'] ?? ''));
            $limit = (int)($_GET['limit'] ?? 10);

            if ($term === '') {
                $this->json(['status' => 'success', 'data' => []]);
                return;
            }

            $rows = $this->model->sugerirFerros($term, $limit);
            $this->json(['status' => 'success', 'data' => $rows]);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'msg'    => 'Ocurrió un error al sugerir ferros.'
            ]);
        }
    }

    /* =========================
       AJAX: LISTAR FERROS + ENVIOS (SOLO LECTURA)
       Endpoint sugerido:
       Operaciones_por_partida_ferros/listarFerrosEnvios
       ========================= */
    public function listarFerrosEnvios()
    {
        try {
            $filters = [
                'ferro_id' => (int)($_GET['ferro_id'] ?? 0),
                'fi'       => trim((string)($_GET['fi'] ?? '')),   // YYYY-MM-DD
                'ff'       => trim((string)($_GET['ff'] ?? '')),   // YYYY-MM-DD
                'term'     => trim((string)($_GET['term'] ?? '')), // producto: desc/upc/marca
            ];

            // Validación suave de formato fecha (evita basura)
            if ($filters['fi'] !== '' && !$this->isDateYmd($filters['fi'])) $filters['fi'] = '';
            if ($filters['ff'] !== '' && !$this->isDateYmd($filters['ff'])) $filters['ff'] = '';

            $rows = $this->model->listarFerrosEnvios($filters);

            $this->json([
                'status' => 'success',
                'data'   => $rows
            ]);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'msg'    => 'Ocurrió un error al listar ferros y envíos.'
            ]);
        }
    }

    /* =========================
       Helpers
       ========================= */
    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        die();
    }

    private function isDateYmd(string $s): bool
    {
        // Valida YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
        [$y, $m, $d] = array_map('intval', explode('-', $s));
        return checkdate($m, $d, $y);
    }
}
