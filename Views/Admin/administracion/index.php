<?php include_once 'Views/Template/admin_header.php'; ?>

<style>
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
  }

  .kpi-card {
    border-radius: 1rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    min-height: 112px;
  }

  .kpi-value {
    font-weight: 800;
    letter-spacing: .2px;
  }

  .kpi-icon {
    opacity: .25;
    width: 42px;
    height: 42px;
  }

  .subtle {
    opacity: .9;
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

  /* Ajusta tus fondos existentes (si ya los tienes, puedes borrar esto) */
  .bg-pacific {
    background: linear-gradient(135deg, #1D4ED8 0%, #0EA5E9 100%);
  }

  .bg-fo {
    background: linear-gradient(135deg, #16A34A 0%, #22C55E 100%);
  }

  .bg-amber {
    background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%);
  }

  .bg-rose {
    background: linear-gradient(135deg, #E11D48 0%, #FB7185 100%);
  }

  .bg-slate {
    background: linear-gradient(135deg, #334155 0%, #64748B 100%);
  }
</style>

<div class="container-fluid">

  <!-- Encabezado / Hero -->
  <div class="mb-3">
    <h3 class="mb-1 fw-bold">Dashboard Principal</h3>
    <div class="text-muted">Visión global de operaciones, contenedores, eventos, clientes y alertas.</div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid mb-4">

    <!-- Operaciones activas (Marítimo) -->
    <div class="kpi-card bg-pacific p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Contenedores en Agua</div>
          <div id="kpiOpsActivas" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="anchor" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2" id="kpiOpsDetalle">Operaciones en Agua</div>
    </div>

    <!-- Operaciones en transito  -->
    <div class="kpi-card bg-fo p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Contenedores en Tránsito</div>
          <div id="kpiFOActivasTransito" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="truck" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2" id="kpiFOActivasTransitoDetalle">Contenedores en Camino </div>
    </div>

    <!-- Contenedores en Bodega (BODEGA TJ / BODEGA SD) -->
    <div class="kpi-card bg-slate p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Contenedores en Bodega</div>
          <div id="kpiContenedoresBodega" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="package" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2" id="kpiContenedoresBodegaDetalle">BODEGA MX + BODEGA USA</div>
    </div>

    <!-- Operaciones sin ISF (EXCEPTO subtipo Lázaro) -->
    <div class="kpi-card bg-amber p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Operaciones sin ISF</div>
          <div id="kpiOpsSinISF" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="file-minus" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2" id="kpiOpsSinISFDetalle">Operaciones sin ISF</div>
    </div>

    <!-- Operaciones sin cita en puerto (EXCEPTO subtipo Lázaro) -->
    <div class="kpi-card bg-amber p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Sin cita en puerto</div>
          <div id="kpiOpsSinCitaPuerto" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="calendar" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2" id="kpiOpsSinCitaPuertoDetalle">Operaciones sin cita en puerto</div>
    </div>

    <!-- Cerca de su cita en puerto 
    <div class="kpi-card bg-rose p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Cita en puerto próxima</div>
          <div id="kpiOpsCitaPuertoProxima" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="alert-triangle" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2" id="kpiOpsCitaPuertoProximaDetalle">Cita en puerto próxima</div>
    </div>-->

    <!-- (Opcional) contenedores activos / eventos / clientes etc: conserva los tuyos 
    <div class="kpi-card bg-slate p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Contenedores activos</div>
          <div id="kpiContActivos" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="box" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2">Marítimo + Contenedores operación</div>
    </div>
-->
    <div class="kpi-card bg-pacific p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Clientes activos</div>
          <div id="kpiClientesActivos" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="users" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2">Clientes con operaciones activas</div>
    </div>

    <div class="kpi-card bg-pacific p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="me-3">
          <div class="subtle small">Operaciones próximas ETA</div>
          <div id="kpiOpsProxETA" class="display-6 kpi-value">0</div>
        </div>
        <i data-feather="clock" class="kpi-icon"></i>
      </div>
      <div class="small subtle mt-2">Ventana configurable</div>
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
          <canvas id="chartOpsPorSubtipo" height="50" aria-label="Distribución por subtipo" role="img"></canvas>
          <div class="small text-muted mt-2" id="legendOpsPorSubtipo">
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 ">
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
        No hay alertas por ahora
      </div>
    </div>
  </div>

</div>

<?php include_once 'Views/Template/admin_footer.php'; ?>

<script>
  // Asegura iconos Feather
  if (window.feather) feather.replace();
</script>

<!--<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-timeline@3.1.0"></script> 
<script src="<?= BASE_URL ?>Assets/Js/modulosAdmin/librerias/chart.js"></script>-->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/chart443.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/chartjs-adapter-date-fns.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/chartjs-chart-timeline.js"></script>

<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/dashboardprincipal.js"></script>