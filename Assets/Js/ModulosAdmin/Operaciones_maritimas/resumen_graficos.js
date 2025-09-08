// assets/js/costosChart.js
// Requiere Chart.js (v3+)

(function (global) {
  // ===== Estado de vista y cache =====
  let display = { moneda: 'MXN', tipoCambio: 17.00 }; // preferencia de visualización
  let lastRows = [];   // último desglose recibido (operación o contenedor)
  let chart = null;
  let $canvas, $legend;

  // ===== Utilidades de dinero / conversión =====
function convertAmount(amt, rowMoneda){
  const src = normMoneda(rowMoneda || 'MXN');  // 👈 usar normalizador
  const dst = (display.moneda || 'MXN').toUpperCase();
  const tc  = Number(display.tipoCambio || 0) || 1;

  if (src === dst) return amt;
  if (src === 'USD' && dst === 'MXN') return amt * tc;
  if (src === 'MXN' && dst === 'USD') return amt / tc;
  return amt;
}
console.log('rows ejemplo', lastRows.slice(0,3));

  function fmtMoney(num){
    // si quieres cambiar símbolos: 'US$'/'$'
    return '$ ' + Number(num).toLocaleString('es-MX',{minimumFractionDigits:2, maximumFractionDigits:2});
  }

  // Suma por nombre_movimiento con conversión a la moneda de display
  function agruparPorTipo(rows) {
    const acc = new Map();
    rows.forEach(r => {
      const key = String(r.nombre_movimiento || 'Sin tipo');
      const amtSrc = Number(r.monto || 0);
      const amtDst = convertAmount(amtSrc, r.moneda);
      acc.set(key, (acc.get(key) || 0) + amtDst);
    });
    const labels = [];
    const data = [];
    for (const [k, v] of acc.entries()) {
      labels.push(k);
      data.push(Number(v.toFixed(2)));
    }
    const total = data.reduce((a,b)=>a+b,0);
    return { labels, data, total };
  }

  function genColors(n) {
    const base = [
      '#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f',
      '#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ab'
    ];
    const out = [];
    for (let i=0; i<n; i++) out.push(base[i % base.length]);
    return out;
  }

  function renderLegend(labels, data, colors) {
    if (!$legend) return;
    $legend.innerHTML = '';
    labels.forEach((label, i) => {
      const li = document.createElement('li');
      li.className = 'd-flex align-items-center mb-1';
      li.innerHTML = `
        <span style="display:inline-block;width:12px;height:12px;background:${colors[i]};border-radius:2px;margin-right:8px;"></span>
        <span class="me-auto">${label}</span>
        <strong>${fmtMoney(data[i])}</strong>
      `;
      $legend.appendChild(li);
    });
  }

  function ensureChart() {
    if (chart) return chart;
    const ctx = $canvas.getContext('2d');
    chart = new Chart(ctx, {
      type: 'doughnut',
      data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const v = Number(ctx.parsed);
                return `${ctx.label}: ${fmtMoney(v)}`;
              }
            }
          }
        },
        cutout: '55%'
      }
    });
    return chart;
  }

  function setChart(labels, data) {
    const colors = genColors(labels.length);
    const ch = ensureChart();
    ch.data.labels = labels;
    ch.data.datasets[0].data = data;
    ch.data.datasets[0].backgroundColor = colors;
    ch.update();
    renderLegend(labels, data, colors);
  }

  function redrawFromCache(){
    const { labels, data, total } = agruparPorTipo(Array.isArray(lastRows) ? lastRows : []);
    if (labels.length === 0) {
      setChart(['Sin costos'], [1]);
    } else {
      setChart(labels, data);
    }
    if (typeof CostosChart.onTotalChanged === 'function') {
      CostosChart.onTotalChanged(fmtMoney(total));
    }
  }

  // ---- Fetchers
  function fetchJSON(url, onOk, onErr) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) { onErr && onErr(xhr); return; }
      try {
        const r = JSON.parse(xhr.responseText);
        if (r.status === 'ok') onOk(r.data ?? r);
        else onErr && onErr(r);
      } catch(e){ onErr && onErr(e); }
    };
    xhr.send();
  }

  function buildUrlDesgloseFisico(operacionId, idFisico) {
    return `${base_url}operaciones_maritimas_resumen/costos_desglosados_contenedor_fisico`
         + `?operacion_id=${encodeURIComponent(operacionId)}`
         + `&id_fisico=${encodeURIComponent(idFisico)}`;
  }

  function buildUrlDesgloseOperacion(operacionId) {
    return `${base_url}operaciones_maritimas_resumen/costos_desglosados_operacion`
         + `?operacion_id=${encodeURIComponent(operacionId)}`;
  }

  // ---- API pública
  const CostosChart = {
    init: function (canvasId, legendId) {
      $canvas = document.getElementById(canvasId);
      $legend = document.getElementById(legendId);
      if (!$canvas) { console.warn('[CostosChart] Canvas no encontrado'); return; }
      ensureChart();
    },

    /**
     * @param {{tipo: 'F'|'M', operacionId: number, idFisico?: number}} opts
     */
    update: function (opts) {
      if (!chart || !$canvas) return;
      setChart(['Cargando'], [1]);

      const tipo = (opts.tipo || '').toUpperCase();
      if (tipo === 'F') {
        const url = buildUrlDesgloseFisico(opts.operacionId, opts.idFisico);
        fetchJSON(url, (rows) => {
          lastRows = Array.isArray(rows) ? rows : [];
          const { labels, data, total } = agruparPorTipo(lastRows);
          if (labels.length === 0) setChart(['Sin costos'], [1]);
          else setChart(labels, data);
          if (typeof CostosChart.onTotalChanged === 'function') {
            CostosChart.onTotalChanged(fmtMoney(total));
          }
        }, () => setChart(['Error'], [1]));
      } else {
        const url = buildUrlDesgloseOperacion(opts.operacionId);
        fetchJSON(url, (rows) => {
          lastRows = Array.isArray(rows) ? rows : [];
          const { labels, data, total } = agruparPorTipo(lastRows);
          if (labels.length === 0) setChart(['Sin costos'], [1]);
          else setChart(labels, data);
          if (typeof CostosChart.onTotalChanged === 'function') {
            CostosChart.onTotalChanged(fmtMoney(total));
          }
        }, () => setChart(['Error'], [1]));
      }
    },
    

    setDisplayCurrency: function(moneda, tipoCambio){
      display.moneda = (moneda || 'MXN').toUpperCase();
      display.tipoCambio = Number(tipoCambio || 0) || 1;
      redrawFromCache(); // recalcula sin volver a pedir datos
    },

    // callback para sincronizar la card de totales
    onTotalChanged: null
  };

  // Exporta al scope global
  global.CostosChart = CostosChart;

function normMoneda(m){
  const s = (m || '').toString().trim().toUpperCase();
  if (['USD','DLLS','DOLAR','DÓLAR','DOLARES','DÓLARES','US DOLLARS'].includes(s)) return 'USD';
  // todo lo demás lo tratamos como MXN
  return 'MXN';
}
// Inicializa el gráfico
CostosChart.init('costosChart', 'costosLeyenda');

// Conecta el total formateado al badge de la card
CostosChart.onTotalChanged = (totalFmt) => {
  const $badge = document.getElementById('badgeTotalCostos');
  if ($badge) $badge.textContent = totalFmt || '—';
};
})(window);
