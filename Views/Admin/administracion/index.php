<?php include_once 'Views/Template/admin_header.php'; ?>

<style>
    /* Mini tema PacificNort */
    .kpi-card {
        border: 0;
        border-radius: 1rem;
        color: #fff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
        transition: transform .12s ease, box-shadow .12s ease, filter .2s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .10);
    }

    .kpi-icon {
        opacity: .9;
        width: 40px;
        height: 40px;
    }

    .kpi-value {
        line-height: 1;
        font-weight: 800;
        letter-spacing: .3px;
    }

    .subtle {
        opacity: .9;
    }

    /* Colores */
    .bg-pacific {
        background: linear-gradient(135deg, #1b2256, #2b4b9b);
    }

    .bg-emerald {
        background: linear-gradient(135deg, #0ea5a3, #22c55e);
    }

    .bg-sunset {
        background: linear-gradient(135deg, #f59e0b, #ef4444);
    }

    .bg-indigo {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
    }

    .bg-royal {
        background: linear-gradient(135deg, #06b6d4, #3b82f6);
    }

    .bg-rose {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
        /* rojo/alarma */
    }

    /* NUEVO */

    /* Grid 5 columnas responsive */
    .kpi-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(6, minmax(180px, 1fr));
    }

    @media (max-width: 1400px) {
        .kpi-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (max-width: 1200px) {
        .kpi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 500px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (min-width: 1400px) {
        .kpi-grid {
            grid-template-columns: repeat(6, 1fr) !important;
        }
    }

    .card-soft {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
    }

    .section-title {
        font-weight: 700;
        letter-spacing: .2px;
    }

    .legend-dot {
        display: inline-block;
        width: .75rem;
        height: .75rem;
        border-radius: 50%;
        margin-right: .4rem;
    }
</style>

<div class="container-fluid">

    <!-- Encabezado / Hero -->
    <div class="mb-3">
        <h3 class="mb-1 fw-bold">Dashboard Principal</h3>
        <div class="text-muted">Visión global de operaciones, contenedores, eventos, clientes y costos.</div>
    </div>

    <!-- KPIs en color (5 en la misma fila) -->
    <div class="kpi-grid mb-4">
        <div class="kpi-card bg-pacific p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-3">
                    <div class="subtle small">Operaciones activas</div>
                    <div id="kpiOpsActivas" class="display-6 kpi-value">0</div>
                </div>
                <i data-feather="anchor" class="kpi-icon"></i>
            </div>
            <div class="small subtle mt-2" id="kpiOpsDetalle">Marítimas y terrestres</div>
        </div>

        <div class="kpi-card bg-emerald p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-3">
                    <div class="subtle small">Contenedores activos</div>
                    <div id="kpiContActivos" class="display-6 kpi-value">0</div>
                </div>
                <i data-feather="package" class="kpi-icon"></i>
            </div>
            <div class="small subtle mt-2" id="kpiContDetalle">Marítimos / Ferro</div>
        </div>

        <div class="kpi-card bg-sunset p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-3">
                    <div class="subtle small">Eventos (hechos/total)</div>
                    <div class="kpi-value">
                        <span id="kpiEventosHechos" class="h2">0</span>/<span id="kpiEventosTotal" class="h2">0</span>
                    </div>
                    <div class="small subtle">Avance: <span id="kpiEventosPct">0%</span></div>
                </div>
                <i data-feather="check-circle" class="kpi-icon"></i>
            </div>
        </div>

        <div class="kpi-card bg-indigo p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-3">
                    <div class="subtle small">Clientes activos</div>
                    <div id="kpiClientesActivos" class="display-6 kpi-value">0</div>
                </div>
                <i data-feather="users" class="kpi-icon"></i>
            </div>
            <div class="small subtle mt-2">Con operaciones en curso</div>
        </div>

        <!-- NUEVO color para diferenciar Ops próximas a ETA -->
        <div class="kpi-card bg-royal p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-3">
                    <div class="subtle small">Ops próximas a ETA (≤ 7 días)</div>
                    <div id="kpiOpsProxETA" class="display-6 kpi-value">0</div>
                </div>
                <i data-feather="clock" class="kpi-icon"></i>
            </div>
            <div class="small subtle mt-2">Ventana de llegada inmediata</div>
        </div>
        <div class="kpi-card bg-rose p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-3">
                    <div class="subtle small">Alertas</div>
                    <div id="kpiAlertas" class="display-6 kpi-value">0</div>
                </div>
                <i data-feather="alert-triangle" class="kpi-icon"></i>
            </div>
            <div id="kpiAlertasDetalle" class="small subtle mt-2">—</div>
        </div>
    </div>

    <!-- Gráficos principales -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="section-title mb-0">Operaciones por subtipo</h5>
                        <i data-feather="pie-chart"></i>
                    </div>
                    <canvas id="chartOpsPorSubtipo" height="50" aria-label="Distribución por subtipo"
                        role="img"></canvas>
                    <div class="small text-muted mt-2" id="legendOpsPorSubtipo">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="section-title mb-0">Puntualidad de entregas por semana (% On-time + volumen)</h5>
                        <i data-feather="bar-chart-2"></i>
                    </div>
                    <canvas id="chartEventosSemana" height="150" aria-label="Eventos por semana" role="img"></canvas>
                    <div class="small text-muted mt-2">Barras: entregas a tiempo/tarde · Línea: % de puntualidad.</div>

                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h5 class="section-title mb-0">Costos </h5>
                    <div class="d-flex justify-content-end align-items-center mb-2">
                        <label class="form-label small mb-1">Mostrar totales en</label>
                        <select id="costosDashboard" class="form-control form-control-sm" style="width:140px;">
                            <option value="MXN">MXN (pesos)</option>
                            <option value="USD">USD (dólares)</option>
                        </select>
                        <div class="input-group input-group-sm" style="width:600px;">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.0001" min="0" id="costosDashboardTipoCambio"
                                class="form-control mt-1" value="17.00">
                        </div>
                    </div>
                    <canvas id="chartCostos" height="240" aria-label="Costos" role="img"></canvas>
                    <div class="small text-muted mt-2">Conversión automática según tipo de cambio configurado.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="section-title mb-0">Línea de tiempo (ETD → ETA)</h5>
                        <i data-feather="clock"></i>
                    </div>
                    <div id="timelineOperaciones" style="height:700px;"></div>
                    <div class="small text-muted mt-2">Muestra ventanas próximas y demoras.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas / pendientes -->
    <div class="card card-soft mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="section-title mb-0">Alertas / Pendientes</h5>
                <button id="btnRefrescarAlertas" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="refresh-ccw"></i> Actualizar
                </button>
            </div>
            <ul id="listaAlertas" class="list-group list-group-flush">
                <!-- Rellenar dinámicamente -->
            </ul>
            <div id="alertasVacio" class="text-muted small mt-2" style="display:none;">
                No hay alertas por ahora 🎉
            </div>
        </div>
    </div>

</div>

<?php include_once 'Views/Template/admin_footer.php'; ?>

<script>
    // Asegura iconos Feather
    if (window.feather) feather.replace();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-timeline@3.1.0"></script>
<script src="<?php echo BASE_URL; ?>assets/Js/ModulosAdmin/dashboardprincipal.js"></script>