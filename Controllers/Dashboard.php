<?php
class Dashboard extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Model dedicado
        $this->model = new DashboardModel();
    }

    public function index()
    {
        $data['title'] = 'Dashboard';
        // Ajusta la ruta a tu vista (ej: Views/admin/Dashboard/index.php)
        $this->views->getView('admin/Dashboard', 'index', $data);
    }


    public function kpis()
    {
        try {
            $opsActivas   = (int)$this->model->kpiOperacionesActivas();
            $contActivos  = (int)$this->model->kpiContenedoresActivos();

            $evt          = $this->model->kpiEventosHechosTotal();
            $hechos       = (int)($evt['hechos'] ?? 0);
            $total        = (int)($evt['total'] ?? 0);
            $pct          = $total > 0 ? round(($hechos / $total) * 100, 2) : 0.0;

            $clientesAct  = (int)$this->model->kpiClientesActivos();
            $opsProxEta   = (int)$this->model->kpiOpsProximasETA(7); // cambia 7 si quieres

            echo json_encode([
                'status' => 'ok',
                'data'   => [
                    'ops_activas'      => $opsActivas,
                    'cont_activos'     => $contActivos,
                    'eventos'          => ['hechos' => $hechos, 'total' => $total, 'pct' => $pct],
                    'clientes_activos' => $clientesAct,
                    'ops_prox_eta'     => $opsProxEta
                ]
            ], JSON_UNESCAPED_UNICODE);
            die();
        } catch (\Throwable $e) {
            error_log('[Dashboard::kpis] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'No fue posible obtener KPIs'], JSON_UNESCAPED_UNICODE);
            die();
        }
    }



    public function kpi_operaciones()
    {
        $n = (int)$this->model->kpiOperacionesActivas();
        echo json_encode(['status' => 'ok', 'data' => ['value' => $n]], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function kpi_contenedores()
    {
        $n = (int)$this->model->kpiContenedoresActivos();
        echo json_encode(['status' => 'ok', 'data' => ['value' => $n]], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function kpi_eventos()
    {
        $evt    = $this->model->kpiEventosHechosTotal();
        $hechos = (int)($evt['hechos'] ?? 0);
        $total  = (int)($evt['total'] ?? 0);
        $pct    = $total > 0 ? round(($hechos / $total) * 100, 2) : 0.0;
        echo json_encode(['status' => 'ok', 'data' => ['hechos' => $hechos, 'total' => $total, 'pct' => $pct]], JSON_UNESCAPED_UNICODE);
        die();
    }
    public function ops_por_subtipo()
{
    try {
        $rows = $this->model->chartOpsPorSubtipo(); 
        // Devuelve: [{ id_subtipo, nombre, prefijo_codigo, total }, ...]
        echo json_encode(['status' => 'ok', 'data' => $rows], JSON_UNESCAPED_UNICODE);
        die();
    } catch (\Throwable $e) {
        error_log('[Dashboard::ops_por_subtipo] ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => 'No fue posible obtener datos'], JSON_UNESCAPED_UNICODE);
        die();
    }
}
public function puntualidad_semana()
{
    // Parámetro opcional: ?weeks=8
    $weeks = isset($_GET['weeks']) ? max(1, (int)$_GET['weeks']) : 8;

   
    // chartPuntualidadEntregasSemana(int $semanas = 8): array
    $rows = $this->model->chartPuntualidadEntregasSemana($weeks);
    if (!is_array($rows)) { $rows = []; }

    echo json_encode([
        'status' => 'ok',
        'meta'   => ['weeks' => $weeks],
        'data'   => $rows  // filas con: semana_inicio, semana_fin, semana_iso, a_tiempo, tarde, retraso_prom_dias
    ], JSON_UNESCAPED_UNICODE);
    die();
}

public function costos_mensuales() {
  $meses = isset($_GET['months']) ? max(1, (int)$_GET['months']) : 12;
  $moneda = ($_GET['currency'] ?? 'MXN') === 'USD' ? 'USD' : 'MXN';
  $fx = (float)($_GET['fx'] ?? 17.00); // viene del input del usuario (MXN por USD)

  $rows = $this->model->costosPorMesMoneda($meses, $moneda, $fx);
  echo json_encode(['status'=>'ok', 'meta'=>['months'=>$meses,'currency'=>$moneda,'fx'=>$fx], 'data'=>$rows], JSON_UNESCAPED_UNICODE);
  die();
}
public function timeline()
{
    $dias   = isset($_GET['days'])  ? max(1, (int)$_GET['days'])  : 60;
    $limite = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;

    $rows = $this->model->timelineETD_ETA($dias);
    $rows = is_array($rows) ? array_slice($rows, 0, $limite) : [];

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => 'ok',
        'meta'   => ['days' => $dias, 'limit' => $limite, 'count' => count($rows)],
        'data'   => $rows
    ], JSON_UNESCAPED_UNICODE);
    die();
}





}
