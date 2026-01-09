 // Assets/Js/ModulosAdmin/operaciones_maritimoferro/costos_combinados.js
(function () {
  "use strict";

  // ===== Compat base_url / BASE_URL =====
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Endpoints (según tu controlador) =====
  const ENDPOINT_LISTAR = "operaciones_maritimo_ferro_costos_combinados/listar";
  const ENDPOINT_SUGERENCIAS = "operaciones_maritimo_ferro_costos_combinados/sugerencias";

  // ===== Refs UI (IDs con sufijo CostosCombinados) =====
  const inputContenedor = document.getElementById("inputContenedorBuscarCostosCombinados");
  const fechaDesde = document.getElementById("filtroFechaInicioCostosContCostosCombinados");
  const fechaHasta = document.getElementById("filtroFechaFinCostosContCostosCombinados");
  const perPageSel = document.getElementById("perPageCostosCostosCombinados");
  const monedaVistaSel = document.getElementById("costosOperacionMonedaVistaCostosCombinados");
  const tipoCambioInput = document.getElementById("costosOperacionTipoCambioCostosCombinados");

  const btnExcel = document.getElementById("btnExportarExcelCostosContenedorCostosCombinados");
  const btnPdf = document.getElementById("btnExportarPDFCostosContenedorCostosCombinados");

  const tabla = document.getElementById("tablaCostosContenedoresCostosCombinados");
  const tbody = document.getElementById("tbodyCostosContenedoresCostosCombinados");
  const metaResumen = document.getElementById("metaResumenCostosCostosCombinados");
  const ulPaginacion = document.getElementById("paginacionCostosCostosCombinados");

  // ===== Estado =====
  let state = {
    page: 1,
    per_page: parseInt(perPageSel?.value || "10", 10) || 10,
    contenedor: "",
    fecha_ini: "",
    fecha_fin: "",
    moneda_vista: (monedaVistaSel?.value || "MXN").toUpperCase(),
    tc: parseFloat(tipoCambioInput?.value || "17.00") || 17.0,
  };

  // ===== Debounce =====
  let tDebounce = null;
  function debounce(fn, wait) {
    if (tDebounce) clearTimeout(tDebounce);
    tDebounce = setTimeout(fn, wait);
  }

  // ===== Autocomplete (datalist simple) =====
  const DATALIST_ID = "datalistContenedoresCostosCombinados";
  function ensureDatalist() {
    if (!inputContenedor) return null;

    let dl = document.getElementById(DATALIST_ID);
    if (!dl) {
      dl = document.createElement("datalist");
      dl.id = DATALIST_ID;
      document.body.appendChild(dl);
    }
    inputContenedor.setAttribute("list", DATALIST_ID);
    return dl;
  }

  function sugerirContenedores(term) {
    const dl = ensureDatalist();
    if (!dl) return;

    dl.innerHTML = "";
    if (!term || term.length < 2) return;

    const url = base_url + ENDPOINT_SUGERENCIAS + "?term=" + encodeURIComponent(term) + "&limit=10";

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        // No bloqueamos UX por sugerencias
        return;
      }

      let res;
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        return;
      }

      if (!res || res.ok !== true || !Array.isArray(res.data)) return;

      dl.innerHTML = "";
      res.data.forEach((it) => {
        const val = (it && (it.text || it.numero_contenedor || it.value) ? (it.text || it.numero_contenedor || it.value) : "").toString();
        if (!val) return;
        const opt = document.createElement("option");
        opt.value = val;
        dl.appendChild(opt);
      });
    };
  }

  // ===== Init =====
  window.addEventListener("DOMContentLoaded", function () {
    if (!tbody || !ulPaginacion || !metaResumen) return;

    // Eventos filtros
    if (inputContenedor) {
      inputContenedor.addEventListener("keyup", function (e) {
        const val = (inputContenedor.value || "").trim().toUpperCase();

        // Autocomplete
        debounce(() => sugerirContenedores(val), 220);

        // Enter = listar
        if (e.key === "Enter") {
          state.contenedor = val;
          state.page = 1;
          listar();
        }
      });

      // Change (cuando selecciona de sugerencias)
      inputContenedor.addEventListener("change", function () {
        const val = (inputContenedor.value || "").trim().toUpperCase();
        state.contenedor = val;
        state.page = 1;
        listar();
      });
    }

    if (fechaDesde) {
      fechaDesde.addEventListener("change", function () {
        state.fecha_ini = (fechaDesde.value || "").trim();
        state.page = 1;
        if (state.contenedor) listar();
      });
    }

    if (fechaHasta) {
      fechaHasta.addEventListener("change", function () {
        state.fecha_fin = (fechaHasta.value || "").trim();
        state.page = 1;
        if (state.contenedor) listar();
      });
    }

    if (perPageSel) {
      perPageSel.addEventListener("change", function () {
        state.per_page = parseInt(perPageSel.value || "10", 10) || 10;
        state.page = 1;
        if (state.contenedor) listar();
      });
    }

    if (monedaVistaSel) {
      monedaVistaSel.addEventListener("change", function () {
        state.moneda_vista = (monedaVistaSel.value || "MXN").toUpperCase();
        state.page = 1;
        if (state.contenedor) listar();
      });
    }

    if (tipoCambioInput) {
      tipoCambioInput.addEventListener("change", function () {
        state.tc = parseFloat(tipoCambioInput.value || "17.00") || 17.0;
        state.page = 1;
        if (state.contenedor) listar();
      });
    }

    // Exportaciones (si hay tabla)
    if (btnExcel) {
      btnExcel.addEventListener("click", function () {
        if (typeof ExportarTablas === "undefined") return;
        ExportarTablas.exportar({
          ref: "tablaCostosContenedoresCostosCombinados",
          formato: "xlsx",
          nombre: "CostosCombinadosContenedor.xlsx",
          columnasOcultas: [],
          soloVisibles: true,
          sheetName: "Costos Combinados",
        });
      });
    }

    if (btnPdf) {
      btnPdf.addEventListener("click", function () {
        if (typeof ExportarTablas === "undefined") return;
        ExportarTablas.exportar({
          ref: "#tablaCostosContenedoresCostosCombinados",
          formato: "pdf",
          nombre: "CostosCombinadosContenedor.pdf",
          titulo: "Costos por Contenedor (Marítimo + FO)",
          orientacion: "landscape",
          formatoPagina: "letter",
          columnasOcultas: [],
          soloVisibles: true,
        });
      });
    }

    // Estado inicial (no listamos hasta que haya contenedor)
    renderVacioInicial();
  });

  // ===== Helper: construir querystring =====
  function buildQuery() {
    const p = new URLSearchParams();
    p.append("page", String(state.page));
    p.append("per_page", String(state.per_page));

    if (state.contenedor) p.append("contenedor", state.contenedor);
    if (state.fecha_ini) p.append("fecha_ini", state.fecha_ini);
    if (state.fecha_fin) p.append("fecha_fin", state.fecha_fin);

    p.append("moneda_vista", state.moneda_vista || "MXN");
    p.append("tc", String(state.tc || 17.0));

    return p.toString();
  }

  // ===== Request: listar =====
  function listar() {
    if (!state.contenedor) {
      renderVacioInicial();
      return;
    }

    const qs = buildQuery();
    const url = base_url + ENDPOINT_LISTAR + (qs ? "?" + qs : "");

    renderCargando();

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        renderError("No fue posible cargar la información.");
        console.error("Costos combinados listar error:", this.responseText);
        return;
      }

      let res;
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        renderError("Respuesta no válida del servidor.");
        console.error("JSON inválido:", this.responseText);
        return;
      }

      if (!res || res.ok !== true) {
        renderError(res && res.msg ? res.msg : "No fue posible cargar la información.");
        return;
      }

      renderTabla(res.data || []);
      renderPaginacion(res.meta || null);
      renderMetaResumen(res.meta || null);

      if (typeof feather !== "undefined") feather.replace();
    };
  }

  // ===== Renderers =====
  function renderVacioInicial() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-4">
          Escribe y selecciona un contenedor para consultar sus costos.
        </td>
      </tr>`;
    if (metaResumen) metaResumen.textContent = "Mostrando 0-0 de 0";
    if (ulPaginacion) ulPaginacion.innerHTML = "";
  }

  function renderCargando() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-4">
          Cargando...
        </td>
      </tr>`;
    if (metaResumen) metaResumen.textContent = "Mostrando 0-0 de 0";
    if (ulPaginacion) ulPaginacion.innerHTML = "";
  }

  function renderError(msg) {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-danger py-4">
          ${escapeHtml(msg || "Error")}
        </td>
      </tr>`;
    if (metaResumen) metaResumen.textContent = "Mostrando 0-0 de 0";
    if (ulPaginacion) ulPaginacion.innerHTML = "";
  }

  function renderTabla(rows) {
    if (!tbody) return;

    tbody.innerHTML = "";

    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            No se encontraron costos para este contenedor con los filtros seleccionados.
          </td>
        </tr>`;
      return;
    }

    const monedaVista = (state.moneda_vista || "MXN").toUpperCase();

    rows.forEach((r) => {
const opOrigen = (r.operacion_origen || (r.origen === "FO" ? "FO: " : "MAR: ") + (r.origen === "FO" ? (r.operacion_terrestre || "") : (r.operacion_maritima || "")) || "").toString();

const cont = (r.contenedor || "").toString();
const cli = (r.cliente || "").toString();
const concepto = (r.concepto || r.tipo_movimiento || "").toString();

const montoNum = toFloat(
  (typeof r.monto_vista !== "undefined" && r.monto_vista !== null) ? r.monto_vista : r.monto
);
const montoFmt = formatMoney(montoNum, monedaVista);

const tr = document.createElement("tr");
tr.innerHTML = `
  <td><span class="fw-semibold">${escapeHtml(opOrigen || "—")}</span></td>
  <td><span class="fw-semibold">${escapeHtml(cont || "—")}</span></td>
  <td>${escapeHtml(cli || "—")}</td>
  <td>${escapeHtml(concepto || "—")}</td>
  <td class="text-end">${escapeHtml(montoFmt)}</td>
`;
tbody.appendChild(tr);
    });
  }

  function renderMetaResumen(meta) {
    if (!metaResumen) return;

    const total = toInt(meta && meta.total);
    const page = toInt(meta && meta.page) || 1;
    const per_page = toInt(meta && meta.per_page) || state.per_page;

    if (total <= 0) {
      metaResumen.textContent = "Mostrando 0-0 de 0";
      return;
    }

    const start = (page - 1) * per_page + 1;
    const end = Math.min(page * per_page, total);
    metaResumen.textContent = `Mostrando ${start}-${end} de ${total}`;
  }

  function renderPaginacion(meta) {
    if (!ulPaginacion) return;

    ulPaginacion.innerHTML = "";

    const total_pages = toInt(meta && meta.total_pages) || 1;
    const page = toInt(meta && meta.page) || 1;

    if (total_pages <= 1) return;

    ulPaginacion.appendChild(pagItem("«", page - 1, page <= 1));

    const windowSize = 5;
    let start = Math.max(1, page - Math.floor(windowSize / 2));
    let end = Math.min(total_pages, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);

    for (let p = start; p <= end; p++) {
      ulPaginacion.appendChild(pagItem(String(p), p, false, p === page));
    }

    ulPaginacion.appendChild(pagItem("»", page + 1, page >= total_pages));
  }

  function pagItem(label, pageTarget, disabled, active) {
    const li = document.createElement("li");
    li.className = "page-item";
    if (disabled) li.classList.add("disabled");
    if (active) li.classList.add("active");

    const a = document.createElement("a");
    a.className = "page-link";
    a.href = "javascript:void(0)";
    a.textContent = label;

    a.onclick = function () {
      if (disabled) return;
      state.page = pageTarget;
      listar();
    };

    li.appendChild(a);
    return li;
  }

  // ===== Utils =====
  function toInt(v) {
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : 0;
  }

  function toFloat(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
  }

  function formatMoney(num, currency) {
    // currency: MXN / USD
    try {
      return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: currency === "USD" ? "USD" : "MXN",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(num || 0);
    } catch (e) {
      // fallback simple
      const n = (num || 0).toFixed(2);
      return (currency === "USD" ? "USD " : "MXN ") + n;
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
})();
