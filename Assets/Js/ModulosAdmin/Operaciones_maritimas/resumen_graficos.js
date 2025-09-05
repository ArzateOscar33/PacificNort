// assets/js/costosChart.js
// Requiere Chart.js cargado en la página (v3+)

(function (global) {
  let chart = null;
  let $canvas, $legend;

  function genColors(n) {
    // genera n colores (básicos y luego variaciones)
    const base = [
      '#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f',
      '#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ab'
    ];
    const out = [];
    for (let i=0; i<n; i++) out.push(base[i % base.length]);
    return out;
  }

  // Suma por tipo_movimiento_id para el gráfico
  function agruparPorTipo(rows) {
    const acc = new Map();
    rows.forEach(r => {
      const key = String(r.nombre_movimiento ?? '0');
      const amt = Number(r.monto || 0);
      acc.set(key, (acc.get(key) || 0) + amt);
    });
    // arma labels y data
    const labels = [];
    const data = [];
    for (const [k, v] of acc.entries()) {
      labels.push(`Mov. ${k}`);
      data.push(Number(v.toFixed(2)));
    }
    return { labels, data };
  }

  function renderLegend(labels, data, colors) {
    if (!$legend) return;
    $legend.innerHTML = '';
    labels.forEach((label, i) => {
      const li = document.createElement('li');
      li.className = 'd-flex align-items-center mb-1';
      li.innerHTML = `
        <span style="display:inline-block;width:12px;height:12px;background:${colors[i]};border-radius:2px;margin-right:8px;"></span>
        <span class="me-auto">-${label}-  $</span>
        <strong>${data[i].toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}</strong>
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
                return `${ctx.label}: ${v.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
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

  // Endpoint que debes implementar similar al de contenedor:
  // GET /operaciones_maritimas_resumen/costos_desglosados_operacion?operacion_id=...
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
      ensureChart(); // crea la instancia
    },

    /**
     * @param {{tipo: 'F'|'M', operacionId: number, idFisico?: number, idMaritimo?: number}} opts
     */
    update: function (opts) {
      if (!chart || !$canvas) return;
      // loading UI simple
      setChart(['Cargando'], [1]);

      const tipo = (opts.tipo || '').toUpperCase();
      if (tipo === 'F') {
        const url = buildUrlDesgloseFisico(opts.operacionId, opts.idFisico);
        fetchJSON(url, (rows) => {
          const { labels, data } = agruparPorTipo(Array.isArray(rows) ? rows : []);
          if (labels.length === 0) setChart(['Sin costos'], [1]);
          else setChart(labels, data);
        }, () => setChart(['Error'], [1]));
      } else {
        // Marítimo => costos por operación
        const url = buildUrlDesgloseOperacion(opts.operacionId);
        fetchJSON(url, (rows) => {
          const { labels, data } = agruparPorTipo(Array.isArray(rows) ? rows : []);
          if (labels.length === 0) setChart(['Sin costos'], [1]);
          else setChart(labels, data);
        }, () => setChart(['Error'], [1]));
      }
    }
  };

  // Exporta al scope global
  global.CostosChart = CostosChart;

})(window);
