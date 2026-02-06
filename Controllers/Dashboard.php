<?php
class Dashboard extends Controller
{
    const EST_FINALIZADA = 7;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        $this->model = new DashboardModel();
    }

    public function index()
    {
        $data['title'] = 'Dashboard';
        $this->views->getView('admin/Dashboard', 'index', $data);
    }

    /**
     * Endpoint único para KPIs (tarjetas).
     * GET params opcionales:
     * - eta_days=7
     * - cita_days=5
     */
public function kpis()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $etaDays  = isset($_GET['eta_days'])  ? max(1, (int)$_GET['eta_days'])  : 7;
        $citaDays = isset($_GET['cita_days']) ? max(0, (int)$_GET['cita_days']) : 5;

        // KPIs existentes
        $opsActivas     = (int)$this->model->kpiOperacionesActivas();      // Marítimo
        $opsFOEnCamino  = (int)$this->model->kpiOperacionesFOEnCamino();    // FO en tránsito
        $contActivos    = (int)$this->model->kpiContenedoresActivos();      // Contenedores activos

        $evt     = $this->model->kpiEventosHechosTotal();
        $hechos  = (int)($evt['hechos'] ?? 0);
        $total   = (int)($evt['total'] ?? 0);
        $pct     = $total > 0 ? round(($hechos / $total) * 100, 2) : 0.0;

        $clientesAct = (int)$this->model->kpiClientesActivos();
        $opsProxEta  = (int)$this->model->kpiOpsProximasETA($etaDays);

        // KPIs nuevos
        $opsSinISF   = (int)$this->model->kpiOperacionesSinISF();           // excluye Lázaro
        $opsSinCita  = (int)$this->model->kpiOperacionesSinCitaPuerto();    // excluye Lázaro
        $opsCitaProx = (int)$this->model->kpiCitaPuertoProxima($citaDays);  // excluye Lázaro

        // ✅ KPI: Contenedores en Bodega
        $bodega = $this->model->kpiContenedoresBodegaPendientes();
 

        // ✅ (opcional) detalle TJ/SD
        $contBodegaDetalle = null;
        if (method_exists($this->model, 'kpiContenedoresEnBodegaDetalle')) {
            $contBodegaDetalle = $this->model->kpiContenedoresEnBodegaDetalle();
            // seguridad mínima
            if (!is_array($contBodegaDetalle)) $contBodegaDetalle = null;
        }

            // Alertas existentes
        $alertasAlta = $this->model->alertasAltaPrioridadISFyCita(15);

        $alertasArribo = $this->model->alertasArriboProximoETA($etaDays, 15);
        $alertasLazaro = $this->model->alertasLazaroSinCitaPuerto(15);


        echo json_encode([
            'status' => 'ok',
            'meta'   => [
                'eta_days'  => $etaDays,
                'cita_days' => $citaDays
            ],
            'data'   => [
                // Marítimo
                'ops_activas'     => $opsActivas,

                // FO
                'ops_activas_fo'  => $opsFOEnCamino,

                // Contenedores
                'cont_activos'    => $contActivos,

                // ✅ Bodega
    'cont_bodega'     => $bodega['total'],
    'cont_bodega_tj'  => $bodega['tj'],
    'cont_bodega_sd'  => $bodega['sd'],

    // ✅ lo que tu JS ya intenta leer
    'cont_bodega_det' => [
      'tj'    => $bodega['tj'],
      'sd'    => $bodega['sd'],
      'total' => $bodega['total'],
      ],

                // Eventos
                'eventos' => [
                    'hechos' => $hechos,
                    'total'  => $total,
                    'pct'    => $pct
                ],

                // Clientes + ETA
                'clientes_activos' => $clientesAct,
                'ops_prox_eta'     => $opsProxEta,

                // Reglas ISF / Cita puerto
                'ops_sin_isf'             => $opsSinISF,
                'ops_sin_cita_puerto'     => $opsSinCita,
                'ops_cita_puerto_proxima' => $opsCitaProx,

                // Alertas Alta Prioridad
                'alertas_alta' => $alertasAlta,
                'alertas_arribo' => $alertasArribo,
                'alertas_lc_sin_cita' => $alertasLazaro,
            ]
        ], JSON_UNESCAPED_UNICODE);
        die();

    } catch (\Throwable $e) {
        error_log('[Dashboard::kpis] ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'msg'    => 'No fue posible obtener KPIs'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }
}


    /**
     * Alertas separadas: próximas vs vencidas (y opcional: finalizada sin entrega).
     * GET params:
     * - limit=20
     * - eta_window=7
     * - eta_past=7
     * - include_final_sin_entrega=1 (default 1)
     */
    public function alertas()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $limit   = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
            $window  = isset($_GET['eta_window']) ? max(0, (int)$_GET['eta_window']) : 7;
            $past    = isset($_GET['eta_past'])   ? max(0, (int)$_GET['eta_past'])   : 7;
            $inclFin = isset($_GET['include_final_sin_entrega']) ? (int)$_GET['include_final_sin_entrega'] : 1;

            // 1) ETA próximas / vencidas (separadas)
            $rowsProx = $this->model->alertasEtaProximas($window, $limit);
            $rowsVenc = $this->model->alertasEtaVencidas($past, $limit);

            $mapEta = function(array $r) {
                $op   = !empty($r['numero_operacion']) ? $r['numero_operacion'] : ('#'.$r['id_operacion']);
                $cli  = $r['cliente'] ?? '—';
                $eta  = $r['eta_fecha'] ?? '—';
                $dias = (int)($r['dias_restantes'] ?? 0);

                // prioridad por regla simple
                $prio = ($dias <= 2) ? 'alta' : 'media';

                $msg = ($dias < 0)
                    ? "Op {$op} ({$cli}) — ETA vencida hace ".abs($dias)." día(s) (ETA: {$eta})"
                    : (($dias === 0)
                        ? "Op {$op} ({$cli}) — ETA HOY (ETA: {$eta})"
                        : "Op {$op} ({$cli}) — ETA en {$dias} día(s) (ETA: {$eta})");

                return [
                    'tipo'          => 'eta',
                    'mensaje'       => $msg,
                    'prioridad'     => $prio,
                    'op_id'         => (int)$r['id_operacion'],
                    'numero_op'     => $r['numero_operacion'] ?? null,
                    'cliente'       => $cli,
                    'eta'           => $eta,
                    'dias_restantes'=> $dias
                ];
            };

            $dataProx = array_map($mapEta, is_array($rowsProx) ? $rowsProx : []);
            $dataVenc = array_map($mapEta, is_array($rowsVenc) ? $rowsVenc : []);

            // 2) Opcional: Finalizada sin entrega (lo puedes mostrar como bloque aparte o mezclar)
            $finalSinEntrega = [];
            if ($inclFin === 1) {
                $rowsFinal = $this->model->alertasFinalizadaSinEntrega(self::EST_FINALIZADA, $limit);

                $finalSinEntrega = array_map(function($r){
                    $op  = !empty($r['numero_operacion']) ? $r['numero_operacion'] : ('#'.$r['id_operacion']);
                    $cli = $r['cliente'] ?? '—';
                    $eta = $r['eta'] ?: ($r['etd'] ?: '—');
                    return [
                        'tipo'      => 'evento',
                        'mensaje'   => "Op {$op} ({$cli}) finalizada sin evento de entrega. ETA/ETD: {$eta}",
                        'prioridad' => 'media',
                        'op_id'     => (int)$r['id_operacion'],
                    ];
                }, is_array($rowsFinal) ? $rowsFinal : []);
            }

            echo json_encode([
                'status' => 'ok',
                'meta'   => [
                    'limit' => $limit,
                    'eta_window' => $window,
                    'eta_past'   => $past,
                    'include_final_sin_entrega' => $inclFin
                ],
                'data' => [
                    'proximas' => $dataProx,
                    'vencidas' => $dataVenc,
                    'finalizada_sin_entrega' => $finalSinEntrega
                ]
            ], JSON_UNESCAPED_UNICODE);
            die();

        } catch (\Throwable $e) {
            error_log('[Dashboard::alertas] ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'msg'    => 'No fue posible obtener alertas'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function ops_por_subtipo()
    {
        try {
            $rows = $this->model->chartOpsPorSubtipo();
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
        $weeks = isset($_GET['weeks']) ? max(1, (int)$_GET['weeks']) : 8;

        $rows = $this->model->chartPuntualidadEntregasSemana($weeks);
        if (!is_array($rows)) $rows = [];

        echo json_encode([
            'status' => 'ok',
            'meta'   => ['weeks' => $weeks],
            'data'   => $rows
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function timeline()
    {
        $dias   = isset($_GET['days'])  ? max(1, (int)$_GET['days'])  : 60;
        $limite = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;

        $rows = $this->model->timelineETD_ETA($dias);
        $rows = is_array($rows) ? array_slice($rows, 0, $limite) : [];

        echo json_encode([
            'status' => 'ok',
            'meta'   => ['days' => $dias, 'limit' => $limite, 'count' => count($rows)],
            'data'   => $rows
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function costos_vs_abonos_mensual()
    {
        $meses   = isset($_GET['meses']) ? (int)$_GET['meses'] : 12;
        $moneda  = isset($_GET['moneda']) ? strtoupper($_GET['moneda']) : 'MXN';
        $tc      = isset($_GET['tc']) ? (float)$_GET['tc'] : 17.00;

        try {
            $rows = $this->model->costosVsAbonosPorMes($meses, $moneda, $tc);
            echo json_encode(['status'=>'ok','data'=>$rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['status'=>'error','msg'=>'No fue posible obtener el dataset'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
