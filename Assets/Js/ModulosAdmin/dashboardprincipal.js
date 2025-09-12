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
      <span class="badge rounded-pill ${badgePorPrioridad(a.prioridad)}">${
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
