/* ====== Refs (IDs de tu vista) ====== */
const elOpsActivas = document.getElementById("kpiOpsActivas");
const elContActivos = document.getElementById("kpiContActivos");
const elEvtHechos = document.getElementById("kpiEventosHechos");
const elEvtTotal = document.getElementById("kpiEventosTotal");
const elEvtPct = document.getElementById("kpiEventosPct");
const elDocsFaltantes = document.getElementById("kpiDocsFaltantes");

const btnRefAlertas = document.getElementById("btnRefrescarAlertas");
const ulAlertas = document.getElementById("listaAlertas");
const emptyAlertas = document.getElementById("alertasVacio");

/* ====== Utils ====== */
function n(x) {
  return Number(x || 0);
}
function fmtInt(x) {
  return n(x).toLocaleString("es-MX");
}
function setText(el, val) {
  if (el) el.textContent = String(val);
}


function xhrGET(url, onOk, onErr) {
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;
    if (this.status !== 200) {
      onErr?.(this.responseText);
      return;
    }
    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch (e) {
      onErr?.("JSON inválido: " + this.responseText);
      return;
    }
    onOk?.(data);
  };
}

/* ====== Render: KPIs ====== */
function renderKPIs(payload) {
  const d = payload?.data || {};
  const eventos = d.eventos || { hechos: 0, total: 0, pct: 0 };

  setText(elOpsActivas, fmtInt(d.ops_activas));
  setText(elContActivos, fmtInt(d.cont_activos));
  setText(elEvtHechos, fmtInt(eventos.hechos));
  setText(elEvtTotal, fmtInt(eventos.total));
  setText(elEvtPct, `${n(eventos.pct).toFixed(2)}%`);
  setText(elDocsFaltantes, fmtInt(d.docs_faltantes));

  if (window.feather) feather.replace(); // refresca iconos por si cambia algo
}

/* ====== Carga: KPIs ====== */
function cargarKPIs() {
  // Puedes mostrar “loading” si quieres: setText(elOpsActivas, '…');
  xhrGET(
    base_url + "dashboard/kpis",
    (res) => {
      if (res?.status !== "ok") {
        console.warn("KPIs no OK:", res);
        return;
      }
      renderKPIs(res);
    },
    (err) => {
      console.error("[KPIs] ", err);
    }
  );
}

/* ====== Alertas (opcional: solo lectura) ====== */
// Espera que /dashboard/alertas devuelva: {status:'ok', data:[{tipo, mensaje, prioridad}]}
function renderAlertas(items) {
  ulAlertas.innerHTML = "";
  const arr = Array.isArray(items) ? items : [];
  if (arr.length === 0) {
    emptyAlertas.style.display = "";
    return;
  }
  emptyAlertas.style.display = "none";
  arr.forEach((a) => {
    const li = document.createElement("li");
    li.className =
      "list-group-item d-flex justify-content-between align-items-center";
    li.innerHTML = `
      <span>
        ${iconoPorTipo(a.tipo)}
        ${escapeHtml(a.mensaje || "")}
      </span>
      <span class="badge rounded-pill text-white ${badgePorPrioridad(a.prioridad)}">${
      a.prioridad || "media"
    }</span>
    `;
    ulAlertas.appendChild(li);
  });
  if (window.feather) feather.replace();
}

function escapeHtml(s) {
  return String(s).replace(
    /[&<>"']/g,
    (m) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[
        m
      ])
  );
}
function iconoPorTipo(t) {
  const map = {
    eta: "clock",
    doc: "file-minus",
    evento: "activity",
    cont: "package",
    general: "alert-circle",
  };
  const icon = map[(t || "").toLowerCase()] || map.general;
  return `<i data-feather="${icon}" class="me-2"></i>`;
}
function badgePorPrioridad(p) {
  const v = (p || "").toLowerCase();
  if (v === "alta") return "bg-danger";
  if (v === "media") return "bg-warning text-dark";
  return "bg-secondary";
}

/* ====== Init ====== */
document.addEventListener("DOMContentLoaded", function () {
  cargarKPIs();
});

if (btnRefAlertas) {
  btnRefAlertas.addEventListener("click", function (e) {
    e.preventDefault();
  });
}

// Nuevos refs
const elClientesActivos = document.getElementById("kpiClientesActivos");
const elOpsProxETA = document.getElementById("kpiOpsProxETA");

// En renderKPIs(...) agrega:
function renderKPIs(payload) {
  const d = payload?.data || {};
  const eventos = d.eventos || { hechos: 0, total: 0, pct: 0 };

  setText(elOpsActivas, fmtInt(d.ops_activas));
  setText(elContActivos, fmtInt(d.cont_activos));
  setText(elEvtHechos, fmtInt(eventos.hechos));
  setText(elEvtTotal, fmtInt(eventos.total));
  setText(elEvtPct, `${n(eventos.pct).toFixed(2)}%`);

  // nuevos:
  setText(elClientesActivos, fmtInt(d.clientes_activos));
  setText(elOpsProxETA, fmtInt(d.ops_prox_eta));

  if (window.feather) feather.replace();
}

// ====== Utils ======
function xhrGET(url, onOk, onErr) {
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;
    if (this.status !== 200) {
      onErr?.(this.responseText);
      return;
    }
    console.log(this.responseText);
    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch (e) {
      onErr?.("JSON inválido: " + this.responseText);
      return;
    }
    onOk?.(data);
  };
}
function escapeHtml(s) {
  return String(s).replace(
    /[&<>"']/g,
    (m) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[
        m
      ])
  );
}

// Paleta estable (auto) para segmentos: genera colores en HSL distribuidos
function colorForIndex(i, total) {
  const hue = Math.round((360 / Math.max(total, 1)) * i); // distribución uniforme
  const sat = 70; // %
  const lig = 45; // %
  return `hsl(${hue} ${sat}% ${lig}%)`;
}

// ====== Render del gráfico ======
// referencia global para poder destruir/recrear el gráfico
let chartOpsPorSubtipoRef = null;

function renderOpsPorSubtipo(rows) {
  // rows: [{id_subtipo, nombre, prefijo_codigo, total}, ...]
  const labels = Array.isArray(rows) ? rows.map(r => r.prefijo_codigo || r.nombre || '—') : [];
  const data   = Array.isArray(rows) ? rows.map(r => Number(r.total || 0)) : [];
  const n      = labels.length;
  const colors = labels.map((_, i) => colorForIndex(i, n));

  const ctx = document.getElementById('chartOpsPorSubtipo');
  if (!ctx) return;

  // Si no hay datos, limpia leyenda y destruye gráfico previo
  const legend = document.getElementById('legendOpsPorSubtipo');
  if (!n) {
    if (legend) legend.innerHTML = '<span class="text-muted">Sin datos</span>';
    if (chartOpsPorSubtipoRef) { chartOpsPorSubtipoRef.destroy(); chartOpsPorSubtipoRef = null; }
    return;
  }

  // Calcula el total ANTES de usarlo en options
  const sum = data.reduce((a, b) => a + (Number(b) || 0), 0);

  // Plugin para escribir texto al centro de la dona (declarado antes de usarlo)
  const centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart, args, opts) {
      const { ctx, chartArea } = chart;
      if (!chartArea) return; // por si aún no está listo
      const text = opts && opts.text ? String(opts.text) : '';
      if (!text) return;
      const { left, right, top, bottom } = chartArea;
      ctx.save();
      ctx.font = '600 14px system-ui, -apple-system, "Segoe UI", Roboto';
      ctx.fillStyle = '#334155';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      const x = (left + right) / 2;
      const y = (top + bottom) / 2;
      ctx.fillText(text, x, y);
      ctx.restore();
    },
  };

  // Destruye instancia previa si existe
  if (chartOpsPorSubtipoRef) {
    chartOpsPorSubtipoRef.destroy();
    chartOpsPorSubtipoRef = null;
  }

  chartOpsPorSubtipoRef = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true, 
      aspectRatio: 3, 
      cutout: '60%',
      plugins: {
        legend: { display: true },
        tooltip: {
          callbacks: {
            label: function(item){
              const lbl = item.label || '';
              const val = item.raw ?? 0;
              const pct = sum > 0 ? ((val / sum) * 100).toFixed(1) : '0.0';
              return `${lbl}: ${val} (${pct}%)`;
            }
          }
        },
        // usamos el total calculado arriba
        centerText: { text: `Total: ${sum}` }
      }
    },
    plugins: [centerTextPlugin]
  });

  // Leyenda personalizada debajo del canvas (con %)
  if (legend) {
    legend.innerHTML = labels.map((lbl, i) => {
      const val = data[i] ?? 0;
      const pct = sum > 0 ? ((val / sum) * 100).toFixed(1) : '0.0';
      const color = colors[i];
      return `
        <span class="legend-dot" style="background:${color}; vertical-align:middle;"></span>
        <span>${escapeHtml(lbl)}: <strong>${val}</strong> <span class="text-muted">(${pct}%)</span></span>
      `;
    }).join(' &nbsp;•&nbsp; ');
  }
}


// ====== Carga (llamar al endpoint) ======
function cargarOpsPorSubtipo() {
  xhrGET(
    base_url + "/dashboard/ops_por_subtipo",
    (res) => {
      if (res?.status !== "ok" || !Array.isArray(res.data)) {
        console.warn("[ops_por_subtipo] respuesta no OK:", res);
        renderOpsPorSubtipo([]);
        return;
      }
      renderOpsPorSubtipo(res.data);
    },
    (err) => {
      console.error("[ops_por_subtipo] ", err);
      renderOpsPorSubtipo([]);
    }
  );
}

// ====== Init ======
document.addEventListener("DOMContentLoaded", function () {
  cargarOpsPorSubtipo();
});
function xhrGET(url, onOk, onErr){
  const http = new XMLHttpRequest();
  http.open('GET', url, true);
  http.send();
  http.onreadystatechange = function(){
    if (this.readyState !== 4) return;
    if (this.status !== 200) { onErr?.(this.responseText); return; }
    let data; try { data = JSON.parse(this.responseText); } catch(e){ onErr?.('JSON inválido'); return; }
    onOk?.(data);
  };
}
 
 /* =========================
   PUNTUALIDAD POR SEMANA
   ========================= */

// Rellena últimas N semanas (lunes–domingo) y mapea datos {a_tiempo, tarde, retraso_prom_dias}
function buildPuntualidadSeries(rows, weeks){
  const map = new Map(
    (rows || []).map(r => [
      String(r.semana_inicio),
      { at: Number(r.a_tiempo||0), td: Number(r.tarde||0), avg: Number(r.retraso_prom_dias||0) }
    ])
  );

  // Lunes de esta semana
  const now = new Date(); now.setHours(0,0,0,0);
  const w = (now.getDay() + 6) % 7; // 0=lunes
  const monday = new Date(now); monday.setDate(now.getDate() - w);

  // helpers
  const addDays = (d,n)=>{ const x=new Date(d); x.setDate(x.getDate()+n); return x; };
  const fmtShort = (d)=> new Intl.DateTimeFormat('es-MX',{day:'2-digit',month:'short'}).format(d).replace('.','');

  const labels=[], onTime=[], late=[], avgDelay=[], pctOnTime=[];
  for (let i = weeks-1; i >= 0; i--) {
    const start = addDays(monday, -i*7);
    const end   = addDays(start, 6);
    const key   = start.toISOString().slice(0,10);
    const it    = map.get(key) || {at:0, td:0, avg:0};
    const sum   = it.at + it.td;
    labels.push(`${fmtShort(start)}–${fmtShort(end)}`);
    onTime.push(it.at);
    late.push(it.td);
    avgDelay.push(it.avg);
    pctOnTime.push(sum>0 ? (it.at/sum*100) : 0);
  }
  return { labels, onTime, late, avgDelay, pctOnTime };
}

let chartPuntualidadRef = null;

function renderPuntualidadSemana(rows, weeks) {
  const canvas = document.getElementById('chartEventosSemana');
  if (!canvas) return;

  const s = buildPuntualidadSeries(rows, weeks);

  // destruye el chart previo
  if (chartPuntualidadRef) {
    chartPuntualidadRef.destroy();
    chartPuntualidadRef = null;
  }

  // si no hay datos, pintar placeholder
  const totalSum = s.onTime.reduce((a,b)=>a+b,0) + s.late.reduce((a,b)=>a+b,0);
  if (totalSum === 0) {
    const ctx2d = canvas.getContext('2d');
    ctx2d.clearRect(0, 0, canvas.width, canvas.height);
    ctx2d.font = '600 14px system-ui, -apple-system, "Segoe UI", Roboto';
    ctx2d.fillStyle = '#64748b';
    ctx2d.textAlign = 'center';
    ctx2d.textBaseline = 'middle';
    ctx2d.fillText('Sin entregas en el rango seleccionado', canvas.width/2, canvas.height/2);
    return;
  }

  // sugerir un máximo cómodo en Y (20% de aire)
  const maxStack = s.onTime.reduce((m, v, i) => Math.max(m, v + (s.late[i]||0)), 0);
  const suggestedMax = Math.max(5, Math.ceil(maxStack * 1.2));

  chartPuntualidadRef = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: s.labels,
      datasets: [
        {
          label: 'A tiempo',
          data: s.onTime,
          backgroundColor: '#22c55e',   // verde
          borderWidth: 0,
          borderRadius: 6,
          stack: 'sla'
        },
        {
          label: 'Tarde',
          data: s.late,
          backgroundColor: '#ef4444',   // rojo
          borderWidth: 0,
          borderRadius: 6,
          stack: 'sla'
        },
        {
          label: '% On-time',
          type: 'line',
          data: s.pctOnTime,
          borderColor: '#0ea5a3',
          pointBackgroundColor: '#0ea5a3',
          pointRadius: 3,
          tension: 0.3,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 3,  
      plugins: {
        legend: { display: true },
        tooltip: {
          callbacks: {
            label: (item) => {
              const idx = item.dataIndex;
              if (item.dataset.type === 'line') {
                return ` % On-time: ${item.formattedValue}%`;
              }
              const at  = s.onTime[idx] || 0;
              const td  = s.late[idx]   || 0;
              const sum = at + td;
              const pct = sum > 0 ? ((at / sum) * 100).toFixed(1) : '0.0';
              const lbl = item.dataset.label === 'A tiempo' ? 'A tiempo' : 'Tarde';
              const val = item.raw ?? 0;
              return ` ${lbl}: ${val}${lbl === 'A tiempo' ? ` (${pct}%)` : ''}`;
            },
            afterBody: (items) => {
              const i = items[0].dataIndex;
              const avg = s.avgDelay[i] || 0;
              if (avg === 0) return 'Δ promedio: 0 días';
              const sgn = avg > 0 ? 'Retraso prom.: ' : 'Anticipo prom.: ';
              return `${sgn}${Math.abs(avg).toFixed(1)} días`;
            }
          }
        }
      },
      scales: {
        x: {
          stacked: true,
          ticks: { maxRotation: 0, autoSkip: true }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          suggestedMax,
          ticks: { precision: 0 },
          title: { display: true, text: 'Operaciones entregadas' },
          grid: { drawBorder: false }
        },
        y1: {
          position: 'right',
          min: 0,
          max: 100,
          ticks: { callback: v => v + '%' },
          grid: { drawOnChartArea: false },
          title: { display: true, text: '% On-time' }
        }
      }
    }
  });
}

function cargarPuntualidadSemana(weeks = 8){
  xhrGET(base_url + 'dashboard/puntualidad_semana?weeks=' + weeks,
    (res)=>{
      if (res?.status !== 'ok' || !Array.isArray(res.data)) {
        console.warn('[puntualidad_semana] respuesta no OK:', res);
        renderPuntualidadSemana([], weeks);
        return;
      }
      renderPuntualidadSemana(res.data, res.meta?.weeks || weeks);
    },
    (err)=>{ console.error('[puntualidad_semana]', err); renderPuntualidadSemana([], weeks); }
  );
}

// Llama esto en tu init junto con los demás
document.addEventListener('DOMContentLoaded', function () {
  cargarPuntualidadSemana(8);
});
// === Costos mensuales ===
const selCostosMoneda = document.getElementById('costosDashboard');              // <select MXN/USD>
const inputCostosFx   = document.getElementById('costosDashboardTipoCambio');    // <input tipo cambio>
let chartCostosRef = null;
function nfForCurrency(curr) {
  return new Intl.NumberFormat(curr === 'USD' ? 'en-US' : 'es-MX', {
    style: 'currency', currency: curr, maximumFractionDigits: 0
  });
}
function cargarCostosMensuales(months = 12) {
  const currency = (selCostosMoneda?.value === 'USD') ? 'USD' : 'MXN';
  let fx = parseFloat(inputCostosFx?.value);
  if (!isFinite(fx) || fx <= 0) fx = 17; // fallback por si dejan vacío

  const url = base_url + `dashboard/costos_mensuales?months=${months}&currency=${encodeURIComponent(currency)}&fx=${encodeURIComponent(fx)}`;

  xhrGET(url, (res) => {
    if (res?.status !== 'ok' || !Array.isArray(res.data)) {
      console.warn('[costos_mensuales] respuesta no OK:', res);
      renderCostosMensuales([], currency);
      return;
    }
    renderCostosMensuales(res.data, currency);
  }, (err) => {
    console.error('[costos_mensuales]', err);
    renderCostosMensuales([], currency);
  });
}
// Debounce simple para no spamear el endpoint al teclear el tipo de cambio
function debounce(fn, ms){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; }
const debouncedReloadCostos = debounce(() => cargarCostosMensuales(12), 350);

if (selCostosMoneda)  selCostosMoneda.addEventListener('change', () => cargarCostosMensuales(12));
if (inputCostosFx)    inputCostosFx.addEventListener('input',  debouncedReloadCostos);
document.addEventListener('DOMContentLoaded', function () {
  cargarCostosMensuales(12);
});
// Promedio móvil simple k-periodos
function sma(series, k = 3) {
  const out = new Array(series.length).fill(null);
  let sum = 0;
  for (let i = 0; i < series.length; i++) {
    sum += (Number(series[i]) || 0);
    if (i >= k) sum -= (Number(series[i - k]) || 0);
    if (i >= k - 1) out[i] = sum / k;
  }
  return out;
}

function renderCostosMensuales(rows, currency) {
  const canvas = document.getElementById('chartCostos');
  if (!canvas) return;

  const labels = Array.isArray(rows) ? rows.map(r => String(r.anio_mes)) : [];
  const data   = Array.isArray(rows) ? rows.map(r => Number(r.total || r.total_mxn || 0)) : [];
  const nf     = nfForCurrency(currency);

  // Línea de tendencia (SMA-3)
  const trend3 = sma(data, 3);

  // Autoscale cómodo para barras
  const maxVal = data.reduce((m, v) => Math.max(m, v || 0), 0);
  const suggestedMax = maxVal > 0 ? Math.ceil(maxVal * 1.2) : 5;

  if (chartCostosRef) { chartCostosRef.destroy(); chartCostosRef = null; }

  chartCostosRef = new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: `Total mensual (${currency})`,
          data,
          backgroundColor: '#1b2256',
          borderWidth: 0,
          borderRadius: 6,
          yAxisID: 'y'
        },
        {
          label: `Tendencia 3M (${currency})`,
          type: 'line',
          data: trend3,
          borderColor: '#0ea5a3',
          pointBackgroundColor: '#0ea5a3',
          pointRadius: 2,
          tension: 0.3,
          yAxisID: 'y' // misma escala (misma moneda)
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,  
      aspectRatio:1.2,
      plugins: {
        legend: { display: true },
        tooltip: {
          callbacks: {
            label: (item) => ` ${nf.format(Number(item.raw || 0))}`
          }
        }
      },
      scales: {
        x: { ticks: { maxRotation: 0, autoSkip: true }, grid: { display: false } },
        y: {
          beginAtZero: true,
          suggestedMax,
          ticks: { callback: (v) => nf.format(Number(v)) },
          grid: { drawBorder: false }
        }
      }
    }
  });
}


/* =========================
   TIMELINE (ETD → ETA)
   ========================= */

 

// Colores por estado
function colorEstado(st) {
  switch (String(st || '').toLowerCase()) {
    case 'proxima':    return '#f59e0b'; // ámbar
    case 'vencida':    return '#ef4444'; // rojo
    case 'entregada':  return '#9ca3af'; // gris
    default:           return '#1b2256'; // azul pacific (en_curso)
  }
}
 

/* =========================
   TIMELINE (ETD → ETA)
   ========================= */

 

// Colores por estado
function colorEstado(st) {
  switch (String(st || '').toLowerCase()) {
    case 'proxima':    return '#f59e0b'; // ámbar
    case 'vencida':    return '#ef4444'; // rojo
    case 'entregada':  return '#9ca3af'; // gris
    default:           return '#1b2256'; // azul pacific (en_curso)
  }
}
/* =========================
   TIMELINE (ETD → ETA)
   ========================= */

// Refs
const timelineBox = document.getElementById('timelineOperaciones');

// Helpers
const MS_DAY = 24 * 60 * 60 * 1000;
const parseISO = (s) => (s ? new Date(s + 'T00:00:00') : null);
const clamp = (x, a, b) => Math.max(a, Math.min(b, x));
const fmtShort = (d) => new Intl.DateTimeFormat('es-MX',{ day:'2-digit', month:'short' }).format(d).replace('.','');
const fmtFull  = (d) => new Intl.DateTimeFormat('es-MX',{ day:'2-digit', month:'long', year:'numeric' }).format(d);
function colorEstado(st) {
  switch ((st || '').toLowerCase()) {
    case 'proxima':   return '#f59e0b'; // ámbar
    case 'vencida':   return '#ef4444'; // rojo
    case 'entregada': return '#9ca3af'; // gris
    default:          return '#1b2256'; // en_curso
  }
}

// Si el backend no manda estado/deltas, los calculamos aquí
function derivarEstadoYMetricas(row) {
  const hoy   = new Date(); hoy.setHours(0,0,0,0);
  const etd   = parseISO(row.etd);
  const eta   = parseISO(row.eta);
  const real  = parseISO(row.arribo_sd); // arribo real si existe

  let estado = 'en_curso';
  let dias_a_eta = null;
  let dias_retraso = 0;

  if (eta) dias_a_eta = Math.ceil((eta - hoy) / MS_DAY);

  if (real) {
    // Si hay arribo real: entregada. Retraso = real - eta (negativo = anticipó)
    estado = 'entregada';
    if (eta) dias_retraso = Math.round((real - eta) / MS_DAY);
  } else if (eta && hoy > eta) {
    estado = 'vencida';
    dias_retraso = Math.round((hoy - eta) / MS_DAY);
  } else if (eta && dias_a_eta !== null && dias_a_eta <= 7) {
    estado = 'proxima';
  } else {
    estado = 'en_curso';
  }

  return { estado, dias_a_eta, dias_retraso, etd, eta, real };
}

// Dibuja la timeline
function renderTimelineOperaciones(rows, days) {
  if (!timelineBox) return;

  timelineBox.innerHTML = '';
  timelineBox.style.position = 'relative';
  timelineBox.style.overflow = 'auto';
  const visibleH = timelineBox.clientHeight || 240;

  const now = new Date(); now.setHours(0,0,0,0);
  const minDate = new Date(now.getTime() - 14 * MS_DAY);
  const maxDate = new Date(now.getTime() + (days || 30) * MS_DAY);
  const rangeMs = Math.max(1, maxDate - minDate);

  // Contenedor interno
  const rowHeight = 26, rowGap = 8;
  const rowsCount = Array.isArray(rows) ? rows.length : 0;
  const innerH = Math.max(visibleH, rowsCount * (rowHeight + rowGap) + 32);
  const inner = document.createElement('div');
  inner.style.position = 'relative';
  inner.style.height = innerH + 'px';
  inner.style.width = '100%';
  inner.style.fontSize = '12px';
  timelineBox.appendChild(inner);

  // Línea "hoy"
  const todayPct = clamp((now - minDate) / rangeMs * 100, 0, 100);
  const todayLine = document.createElement('div');
  todayLine.style.position = 'absolute';
  todayLine.style.left = todayPct + '%';
  todayLine.style.top = '0';
  todayLine.style.bottom = '0';
  todayLine.style.width = '2px';
  todayLine.style.background = '#0ea5a3';
  todayLine.style.opacity = '0.8';
  inner.appendChild(todayLine);

  const todayLabel = document.createElement('div');
  todayLabel.textContent = 'HOY';
  todayLabel.style.position = 'absolute';
  todayLabel.style.left = `calc(${todayPct}% + 4px)`;
  todayLabel.style.top = '4px';
  todayLabel.style.color = '#0ea5a3';
  todayLabel.style.fontWeight = '600';
  inner.appendChild(todayLabel);

  // Regla semanal
  for (let d = new Date(minDate); d <= maxDate; d = new Date(d.getTime() + 7*MS_DAY)) {
    const p = clamp((d - minDate) / rangeMs * 100, 0, 100);
    const v = document.createElement('div');
    v.style.position = 'absolute';
    v.style.left = p + '%';
    v.style.top = '0';
    v.style.bottom = '0';
    v.style.width = '1px';
    v.style.background = 'rgba(148,163,184,.25)';
    inner.appendChild(v);

    const lbl = document.createElement('div');
    lbl.textContent = fmtShort(d);
    lbl.style.position = 'absolute';
    lbl.style.left = `calc(${p}% + 4px)`;
    lbl.style.bottom = '4px';
    lbl.style.color = '#64748b';
    inner.appendChild(lbl);
  }

  // Sin datos
  if (!rowsCount) {
    const empty = document.createElement('div');
    empty.className = 'text-muted';
    empty.style.position = 'absolute';
    empty.style.left = '50%';
    empty.style.top = '50%';
    empty.style.transform = 'translate(-50%, -50%)';
    empty.textContent = 'Sin operaciones en la ventana';
    inner.appendChild(empty);
    return;
  }

  // Barras
  rows.forEach((r, idx) => {
    const { estado, dias_a_eta, dias_retraso, etd, eta, real } = derivarEstadoYMetricas(r);

    const start = etd || minDate;
    const end   = eta || start;
    if (end < minDate || start > maxDate) return;

    const leftPct  = clamp((start - minDate) / rangeMs * 100, 0, 100);
    const rightPct = clamp((end   - minDate) / rangeMs * 100, 0, 100);
    const widthPct = Math.max(0.8, rightPct - leftPct);

    const top = 24 + idx * (rowHeight + rowGap);
    const bar = document.createElement('div');
    bar.style.position = 'absolute';
    bar.style.left = leftPct + '%';
    bar.style.top = top + 'px';
    bar.style.width = widthPct + '%';
    bar.style.height = rowHeight + 'px';
    bar.style.borderRadius = '8px';
    bar.style.background = colorEstado(estado);
    bar.style.opacity = estado === 'entregada' ? '.55' : '.9';
    bar.style.boxShadow = '0 2px 8px rgba(0,0,0,.07)';
    bar.style.cursor = 'pointer';

    const idText = r.numero_operacion ? `#${r.numero_operacion}` : `#${r.id_operacion}`;
    const subt   = r.subtipo_prefijo || r.subtipo_nombre || '';
    const etaTxt = r.eta ? fmtFull(parseISO(r.eta)) : '—';
    const etdTxt = r.etd ? fmtFull(parseISO(r.etd)) : '—';
    const arrTxt = r.arribo_sd ? fmtFull(parseISO(r.arribo_sd)) : '—';

    let extraLinea = '';
    if (estado === 'entregada') {
      const sgn = dias_retraso > 0 ? 'Retraso' : (dias_retraso < 0 ? 'Anticipo' : 'A tiempo');
      extraLinea = `${sgn}: ${Math.abs(dias_retraso)} día(s)`;
    } else if (typeof dias_a_eta === 'number') {
      extraLinea = (dias_a_eta >= 0) ? `Faltan: ${dias_a_eta} día(s)` : `Vencido: ${Math.abs(dias_a_eta)} día(s)`;
    }

    bar.title =
      `Op ${idText}\n` +
      `ETD (plan): ${etdTxt}\n` +
      `ETA (plan): ${etaTxt}\n` + 
      `Estado: ${estado}\n` +
      extraLinea;

    const lbl = document.createElement('div');
    lbl.textContent = `${subt} · ${idText}`;
    lbl.style.color = '#fff';
    lbl.style.fontWeight = '600';
    lbl.style.fontSize = '12px';
    lbl.style.padding = '4px 8px';
    lbl.style.whiteSpace = 'nowrap';
    lbl.style.overflow = 'hidden';
    lbl.style.textOverflow = 'ellipsis';
    bar.appendChild(lbl);

    inner.appendChild(bar);
  });
}

// Cargar desde el endpoint
function cargarTimelineOperaciones(days = 30, limit = 50) {
  xhrGET(
    base_url + `dashboard/timeline?days=${days}&limit=${limit}`,
    (res) => {
      if (res?.status !== 'ok' || !Array.isArray(res.data)) {
        console.warn('[timeline] respuesta no OK:', res);
        renderTimelineOperaciones([], days);
        return;
      }
      renderTimelineOperaciones(res.data, res.meta?.days || days);
    },
    (err) => {
      console.error('[timeline]', err);
      renderTimelineOperaciones([], days);
    }
  );
}

// Debounce para resize
function debounce(fn, ms){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; }
const onResizeTimeline = debounce(()=> { cargarTimelineOperaciones(30, 50); }, 200);
window.addEventListener('resize', onResizeTimeline);

// Init
document.addEventListener('DOMContentLoaded', function () {
  cargarTimelineOperaciones(30, 50);
});
function cargarAlertas() {
  xhrGET(base_url + "dashboard/alertas?limit=20",
    (res) => {
      if (res?.status !== "ok") { console.warn("alertas no OK", res); return; }
      renderAlertas(res.data);
    },
    (err) => console.error("[alertas]", err)
  );
}
document.addEventListener("DOMContentLoaded", function () {
  cargarKPIs();
  cargarOpsPorSubtipo();
  cargarPuntualidadSemana(8);
  cargarTimelineOperaciones(30, 50);
  cargarAlertas(); // ⬅️
});
// ====== Refs nuevas ======
const elKpiAlertas = document.getElementById('kpiAlertas');
const elKpiAlertasDetalle = document.getElementById('kpiAlertasDetalle');

// ====== Cargar KPI: Alertas ======
function cargarKPIAlertas(limit = 500) {
  xhrGET(
    base_url + 'dashboard/alertas?limit=' + limit,
    (res) => {
      if (res?.status !== 'ok' || !Array.isArray(res.data)) {
        setText(elKpiAlertas, '0');
        if (elKpiAlertasDetalle) elKpiAlertasDetalle.textContent = 'Sin alertas';
        if (window.feather) feather.replace();
        return;
      }
      const total = res.data.length;
      const altas = res.data.filter(a => (a.prioridad || '').toLowerCase() === 'alta').length;
      const medias = res.data.filter(a => (a.prioridad || '').toLowerCase() === 'media').length;

      setText(elKpiAlertas, total.toLocaleString('es-MX'));
      if (elKpiAlertasDetalle) {
        elKpiAlertasDetalle.textContent = total > 0
          ? `Alta: ${altas} · Media: ${medias}`
          : 'Sin alertas';
      }
      if (window.feather) feather.replace();
    },
    (err) => {
      console.error('[kpi alertas]', err);
      setText(elKpiAlertas, '0');
      if (elKpiAlertasDetalle) elKpiAlertasDetalle.textContent = 'Error al cargar';
      if (window.feather) feather.replace();
    }
  );
}

// Llamarlo en tu init junto a los demás
document.addEventListener('DOMContentLoaded', function () {
  cargarKPIAlertas(); // por defecto limit=500
});
