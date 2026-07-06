// ============================================================
// resumen_graficos.js (MF ONLY)
// - CostosChart: SOLO por operación MF (costos_desglosados_operacion)
// - TimelineChart: sin cambios de lógica (recibe rows de eventos_contenedor)
// Requiere Chart.js (v3+)
// ============================================================

(function (global) {
  "use strict";

  // ===== Estado de vista y cache =====
  let display = { moneda: "MXN", tipoCambio: 17.0 };
  let lastRows = []; // último desglose recibido (solo operación)
  let chart = null;
  let $canvas = null,
    $legend = null;

  // ===== Normalizador de moneda =====
  function normMoneda(m) {
    const s = (m || "").toString().trim().toUpperCase();
    if (
      [
        "USD",
        "DLLS",
        "DOLAR",
        "DÓLAR",
        "DOLARES",
        "DÓLARES",
        "US DOLLARS",
      ].includes(s)
    )
      return "USD";
    return "MXN";
  }

  // ===== Utilidades de dinero / conversión =====
  function convertAmount(amt, rowMoneda) {
    const src = normMoneda(rowMoneda || "MXN");
    const dst = (display.moneda || "MXN").toUpperCase();
    const tc = Number(display.tipoCambio || 0) || 1;

    if (src === dst) return amt;
    if (src === "USD" && dst === "MXN") return amt * tc;
    if (src === "MXN" && dst === "USD") return amt / tc;
    return amt;
  }

  function fmtMoney(num) {
    return (
      "$ " +
      Number(num).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function computePct(data) {
    const sum = data.reduce((a, b) => a + Number(b || 0), 0);
    return {
      sum,
      pct: data.map((v) => (sum ? (Number(v || 0) / sum) * 100 : 0)),
    };
  }

  // Suma por nombre_movimiento con conversión a la moneda de display
  function agruparPorTipo(rows) {
    const acc = new Map();
    (rows || []).forEach((r) => {
      const key = String(r.nombre_movimiento || "Sin tipo");
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
    const total = data.reduce((a, b) => a + b, 0);
    return { labels, data, total };
  }

  function genColors(n) {
    const base = [
      "#4e79a7",
      "#f28e2b",
      "#e15759",
      "#76b7b2",
      "#59a14f",
      "#edc948",
      "#b07aa1",
      "#ff9da7",
      "#9c755f",
      "#bab0ab",
    ];
    const out = [];
    for (let i = 0; i < n; i++) out.push(base[i % base.length]);
    return out;
  }

  function renderLegend(labels, data, colors) {
    if (!$legend) return;
    const { pct } = computePct(data);
    $legend.innerHTML = "";
    labels.forEach((label, i) => {
      const li = document.createElement("li");
      li.className = "d-flex align-items-center mb-1";
      li.innerHTML = `
        <span style="display:inline-block;width:12px;height:12px;background:${colors[i]};border-radius:2px;margin-right:8px;"></span>
        <span class="me-auto">${label}</span>
        <strong class="text-nowrap">${fmtMoney(data[i])} (${pct[i].toFixed(1)}%)</strong>
      `;
      $legend.appendChild(li);
    });
  }

  function ensureChart() {
    if (chart) return chart;
    if (!$canvas) return null;

    const ctx = $canvas.getContext("2d");
    chart = new Chart(ctx, {
      type: "bar",
      data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const v = Number(ctx.parsed) || 0;
                const ds = ctx.chart.data.datasets?.[0]?.data || [];
                const { sum } = computePct(ds);
                const p = sum ? (v / sum) * 100 : 0;
                return `${ctx.label}: ${fmtMoney(v)} (${p.toFixed(1)}%)`;
              },
            },
          },
        },
        cutout: "55%",
      },
    });

    return chart;
  }

  function setChart(labels, data) {
    const colors = genColors(labels.length);
    const ch = ensureChart();
    if (!ch) return;
    ch.data.labels = labels;
    ch.data.datasets[0].data = data;
    ch.data.datasets[0].backgroundColor = colors;
    ch.update();
    renderLegend(labels, data, colors);
  }

  function redrawFromCache() {
    const { labels, data, total } = agruparPorTipo(
      Array.isArray(lastRows) ? lastRows : [],
    );
    if (labels.length === 0) setChart(["Sin costos"], [1]);
    else setChart(labels, data);

    if (typeof CostosChart.onTotalChanged === "function") {
      CostosChart.onTotalChanged(fmtMoney(total));
    }
  }

  // ---- Fetchers
  function fetchJSON(url, onOk, onErr) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) {
        onErr && onErr(xhr);
        return;
      }
      try {
        const r = JSON.parse(xhr.responseText);
        if (r.status === "ok") onOk(r.data ?? r);
        else onErr && onErr(r);
      } catch (e) {
        onErr && onErr(e);
      }
    };
    xhr.send();
  }

  function buildUrlDesgloseOperacion(operacionId) {
    return (
      `${base_url}Operaciones_maritimo_ferro_resumen/costos_desglosados_operacion` +
      `?operacion_id=${encodeURIComponent(operacionId)}`
    );
  }

  // ---- API pública
  const CostosChart = {
    init: function (canvasId, legendId) {
      $canvas = document.getElementById(canvasId);
      $legend = document.getElementById(legendId);
      if (!$canvas) {
        console.warn("[CostosChart] Canvas no encontrado");
        return;
      }
      ensureChart();
    },

    clear: function () {
      try {
        lastRows = [];
        const ch = ensureChart();
        if (ch) {
          ch.data.labels = ["Sin costos"];
          ch.data.datasets[0].data = [1];
          ch.data.datasets[0].backgroundColor = ["#bab0ab"];
          ch.update();
        }
        if (typeof CostosChart.onTotalChanged === "function") {
          CostosChart.onTotalChanged("—");
        }
        if ($legend) $legend.innerHTML = "";
      } catch (e) {
        console.warn("[CostosChart.clear] noop", e);
      }
    },

    /**
     * MF ONLY:
     * @param {{operacionId:number}} opts
     */
    update: function (opts) {
      if (!ensureChart()) return;

      setChart(["Cargando"], [1]);

      const operacionId = Number(opts?.operacionId || 0);
      if (!operacionId) {
        setChart(["Sin costos"], [1]);
        if (typeof CostosChart.onTotalChanged === "function")
          CostosChart.onTotalChanged("—");
        return;
      }

      const url = buildUrlDesgloseOperacion(operacionId);
      fetchJSON(
        url,
        (rows) => {
          lastRows = Array.isArray(rows) ? rows : [];
          const { labels, data, total } = agruparPorTipo(lastRows);
          if (labels.length === 0) setChart(["Sin costos"], [1]);
          else setChart(labels, data);

          if (typeof CostosChart.onTotalChanged === "function") {
            CostosChart.onTotalChanged(fmtMoney(total));
          }
        },
        () => setChart(["Error"], [1]),
      );
    },

    setDisplayCurrency: function (moneda, tipoCambio) {
      display.moneda = (moneda || "MXN").toUpperCase();
      display.tipoCambio = Number(tipoCambio || 0) || 1;
      redrawFromCache(); // recalcula sin volver a pedir datos
    },

    onTotalChanged: null,
  };

  global.CostosChart = CostosChart;

  // Si quieres auto-init aquí (solo si tu proyecto no lo hace en otro lado)
  // CostosChart.init('costosChart', 'costosLeyenda');
  // CostosChart.onTotalChanged = (totalFmt) => {
  //   const $badge = document.getElementById('badgeTotalCostos');
  //   if ($badge) $badge.textContent = totalFmt || '—';
  // };
})(window);

// ============================================================
// TimelineChart (línea horizontal con puntos por evento)
// - Sigue igual: recibe array de eventos de eventos_contenedor
// ============================================================
(function (global) {
  "use strict";

  let chart = null;
  let $canvas = null;

  function ensureChart() {
    if (chart) return chart;
    if (!$canvas) return null;

    const ctx = $canvas.getContext("2d");
    chart = new Chart(ctx, {
      type: "line",
      data: {
        labels: [],
        datasets: [
          {
            label: "Eventos",
            data: [],
            showLine: true,
            tension: 0.35,
            borderColor: "rgba(13,110,253,0.5)",
            borderWidth: 2,
            fill: false,
            segment: { borderDash: [4, 4] },
            pointStyle: "circle",
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBorderWidth: 2,
            pointBorderColor: "rgba(13,110,253,0.9)",
            pointBackgroundColor: "rgba(13,110,253,0.15)",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        layout: { padding: { top: 10, right: 8, bottom: 8, left: 8 } },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              autoSkip: false,
              maxRotation: 0,
              callback: function (v) {
                const txt = this.getLabelForValue(v);
                return String(txt).split("\n");
              },
            },
          },
          y: { display: false, min: -1, max: 1 },
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: (items) => items[0].label.replace("\n", " · "),
              label: () => "",
            },
          },
        },
      },
    });

    return chart;
  }

  function setData(labels, data) {
    const ch = ensureChart();
    if (!ch) return;
    ch.data.labels = labels;
    ch.data.datasets[0].data = data;
    ch.update();
  }

  function fmtEtiqueta(fechaIso, nombreEvento) {
    if (!fechaIso) return `${nombreEvento || "(sin nombre)"}`;
    const s = String(fechaIso).trim();
    const [Y, M, rest] = s.split("-");
    if (!Y || !M || !rest) return `${s}\n${nombreEvento || "(sin nombre)"}`;

    let D = rest,
      hhmm = "";
    if (rest.includes(" ")) {
      const [dd, hh] = rest.split(" ");
      D = dd;
      hhmm = (hh || "").slice(0, 5);
    }
    const fecha = `${D}/${M}${hhmm ? " " + hhmm : ""}`;
    return `${fecha}\n${nombreEvento || "(sin nombre)"}`;
  }

  function buildFromEventos(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      return { labels: ["Sin eventos"], data: [0] };
    }

    // Orden: fechas nulas al final, luego fecha asc, luego id_evento asc
    const sorted = rows.slice().sort((a, b) => {
      const fa = a.fecha ? 0 : 1;
      const fb = b.fecha ? 0 : 1;
      if (fa !== fb) return fa - fb;
      const da = a.fecha || "";
      const db = b.fecha || "";
      if (da < db) return -1;
      if (da > db) return 1;
      return (a.id_evento || 0) - (b.id_evento || 0);
    });

    const labels = sorted.map((r) => fmtEtiqueta(r.fecha, r.nombre_evento));
    const data = new Array(labels.length).fill(0);
    return { labels, data };
  }

  const TimelineChart = {
    init: function (canvasId) {
      $canvas = document.getElementById(canvasId);
      if (!$canvas) {
        console.warn("[TimelineChart] canvas no encontrado");
        return;
      }
      ensureChart();
      setData(["Seleccione un contenedor"], [0]);
    },
    setEventos: function (rows) {
      const { labels, data } = buildFromEventos(rows);
      setData(labels, data);
    },
  };

  global.TimelineChart = TimelineChart;
})(window);
