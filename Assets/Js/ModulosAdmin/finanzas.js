/* ============================================================
   MÓDULO: Finanzas - LISTAR (XHR)
   Archivo: costos_clientes.js
   Ahora trabaja por renglón de costo unificado:
   - MARITIMO-FERRO
   - PARTIDA/DOMESTICO
   ============================================================ */
(function () {
  "use strict";

  const base =
    (typeof window.base !== "undefined" && window.base) ||
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const api = base + "Finanzas/listarPaginado";

  // =========================
  // REFS FILTROS
  // =========================
  const clienteSel = document.getElementById("clienteId_cc");
  const fechaInicio = document.getElementById("costosCliente_fechaInicio");
  const fechaFin = document.getElementById("costosCliente_fechaFin");
  const brokerSel = document.getElementById("brokerId_cc");
  const transportistaSel = document.getElementById("transportistaId_cc");
  const categoriaSel = document.getElementById("categoriaId_cc");
  const estatusPagoSel = document.getElementById("costosCliente_estatusPago");
  const termInput = document.getElementById("costosCliente_term");
  const perPageSel = document.getElementById("costosCliente_perPage");
  const btnLimpiar = document.getElementById("costosCliente_btnLimpiar");

  // NUEVO: filtro por origen
  const origenSel = document.getElementById("origenTipo_cc");

  // Moneda vista + tipo de cambio
  const monedaVistaSel = document.getElementById("costosClienteMonedaVista");
  const tipoCambioInp = document.getElementById("costosClienteTipoCambio");

  // =========================
  // TABLA
  // =========================
  const tbody = document.getElementById("costosCliente_tbody");

  // =========================
  // META
  // =========================
  const metaOps = document.getElementById("costosCliente_metaTotalOps");
  const metaConceptos = document.getElementById(
    "costosCliente_metaTotalConceptos",
  );
  const metaPend = document.getElementById("costosCliente_metaPendientes");
  const metaPag = document.getElementById("costosCliente_metaPagados");

  // =========================
  // PAGINACIÓN
  // =========================
  const pagInfo = document.getElementById("costosCliente_metaPaginacion");
  const pagUl = document.getElementById("costosCliente_paginacion");

  let state = { page: 1, loading: false };

  // AJUSTA ESTO A TU THEAD NUEVO
  // Origen | Referencia | Contenedor | Ferro/Caja | Transportista |
  // Broker | Estatus | Cita Puerto | ISF | Categoría | Concepto |
  // Monto | Pagado
  const COLS = 13;

  // =========================
  // HELPERS
  // =========================
  const esc = (s) =>
    String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const money = (n) => {
    const x = Number(n || 0);
    return x.toLocaleString("es-MX", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  };

  const fmtDate = (s) => {
    if (!s || s === "No aplica") return "No aplica";
    return String(s).slice(0, 10);
  };

  function xhrGet(url, cb) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      let json = null;
      try {
        json = JSON.parse(xhr.responseText);
      } catch (e) {}

      cb(json, xhr.status);
    };
    xhr.send();
  }

  function renderEmpty(msg) {
    if (!tbody) return;

    tbody.innerHTML = `
      <tr>
        <td colspan="${COLS}" class="text-center text-muted py-5">
          <i data-feather="inbox"></i>
          <div class="mt-2">${esc(msg || "Sin datos.")}</div>
        </td>
      </tr>
    `;
    if (window.feather) feather.replace();
  }

  function buildQuery(page) {
    const qs = new URLSearchParams();
    qs.set("page", String(page));
    qs.set("per_page", perPageSel?.value || "25");

    if (clienteSel && clienteSel.value !== "") {
      qs.set("clienteId_cc", clienteSel.value);
    }

    if (fechaInicio?.value) qs.set("fecha_inicio", fechaInicio.value);
    if (fechaFin?.value) qs.set("fecha_fin", fechaFin.value);

    if (brokerSel?.value) qs.set("brokerId_cc", brokerSel.value);
    if (transportistaSel?.value) {
      qs.set("transportistaId_cc", transportistaSel.value);
    }

    if (categoriaSel?.value) qs.set("categoriaId_cc", categoriaSel.value);

    if (origenSel?.value) qs.set("origenTipo_cc", origenSel.value);

    if (estatusPagoSel?.value !== "") {
      qs.set("costosCliente_estatusPago", estatusPagoSel.value);
    }

    const t = (termInput?.value || "").trim();
    if (t) qs.set("costosCliente_term", t);

    return qs.toString();
  }

  // =========================
  // CONVERSIÓN DE MONEDA
  // =========================
  function normMoneda(m) {
    const x = String(m || "")
      .trim()
      .toUpperCase();

    if (x === "DLLS" || x === "USD" || x === "DLS") return "USD";
    if (x === "MXN" || x === "PESOS" || x === "MX") return "MXN";

    return "MXN";
  }

  function getTipoCambio() {
    const tc = Number(tipoCambioInp?.value || 0);
    return tc > 0 ? tc : 1;
  }

  function getMonedaVista() {
    return normMoneda(monedaVistaSel?.value || "MXN");
  }

  function convertAmount(amt, monedaOrigen, monedaDestino, tc) {
    const src = normMoneda(monedaOrigen);
    const dst = normMoneda(monedaDestino);
    const n = Number(amt || 0);

    if (src === dst) return n;
    if (src === "USD" && dst === "MXN") return n * tc;
    if (src === "MXN" && dst === "USD") return n / tc;

    return n;
  }

  // =========================
  // BADGES
  // =========================
  function badgeOrigen(origen) {
    const x = String(origen || "")
      .trim()
      .toUpperCase();

    if (x === "MARITIMO-FERRO") {
      return `<span class="badge bg-primary text-white">Marítimo-Ferro</span>`;
    }

    if (x === "PARTIDA/DOMESTICO") {
      return `<span class="badge bg-secondary text-white">Partida/Doméstico</span>`;
    }

    return `<span class="badge bg-light text-dark">${esc(origen || "—")}</span>`;
  }

  function badgePagado(p) {
    const ok = Number(p) === 1;
    return ok
      ? `<span class="badge bg-success text-white">Sí</span>`
      : `<span class="badge bg-danger text-white">No</span>`;
  }

  function badgeIsf(v) {
    const x = String(v ?? "")
      .trim()
      .toLowerCase();

    if (x === "sí" || x === "si" || x === "yes" || x === "1") {
      return `<span class="badge bg-success text-white">Sí</span>`;
    }

    if (x === "no aplica") {
      return `<span class="badge bg-secondary text-white">No aplica</span>`;
    }

    return `<span class="badge bg-secondary text-white">No</span>`;
  }

  function badgeEstatus(v) {
    const txt = String(v || "").trim();
    if (!txt) return "—";

    const low = txt.toLowerCase();

    let cls = "bg-secondary text-white";

    if (
      low.includes("entregado") ||
      low.includes("cerrado") ||
      low.includes("finalizado")
    ) {
      cls = "bg-success text-white";
    } else if (
      low.includes("camino") ||
      low.includes("tránsito") ||
      low.includes("transito") ||
      low.includes("programado")
    ) {
      cls = "bg-warning text-dark";
    } else if (low.includes("cancelado")) {
      cls = "bg-danger text-white";
    } else if (
      low.includes("puerto") ||
      low.includes("proceso") ||
      low.includes("activo")
    ) {
      cls = "bg-primary text-white";
    }

    return `<span class="badge ${cls}">${esc(txt)}</span>`;
  }

  // =========================
  // TABLA
  // =========================
  function renderTable(rows) {
    if (!tbody) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      renderEmpty("Sin resultados con los filtros actuales.");
      return;
    }

    const monedaVista = getMonedaVista();
    const tc = getTipoCambio();

    let html = "";

    rows.forEach((r) => {
      const origen = badgeOrigen(r.origen_tipo);
      const referencia = r.referencia ? esc(r.referencia) : "—";
      const contenedor = r.contenedor ? esc(r.contenedor) : "—";
      const ferroCaja = r.ferro_caja ? esc(r.ferro_caja) : "—";
      const transportista = r.transportista ? esc(r.transportista) : "—";
      const broker = r.broker ? esc(r.broker) : "—";
      const estatus = badgeEstatus(r.estatus_operacion);
      const citaPuerto = fmtDate(r.cita_puerto);
      const isf = badgeIsf(r.isf);
      const categoria = r.categoria ? esc(r.categoria) : "—";
      const concepto = r.concepto ? esc(r.concepto) : "—";

      const montoConv = convertAmount(r.monto, r.moneda, monedaVista, tc);
      const montoTxt = `${monedaVista} $${money(montoConv)}`;

      html += `
        <tr>
          <td>${origen}</td>
          <td>
            <div class="fw-semibold">${referencia}</div>
            ${
              r.cliente
                ? `<small class="text-muted">${esc(r.cliente)}</small>`
                : ""
            }
          </td>
          <td>${contenedor}</td>
          <td>${ferroCaja}</td>
          <td>${transportista}</td>
          <td>${broker}</td>
          <td>${estatus}</td>
          <td>${esc(citaPuerto)}</td>
          <td class="text-center">${isf}</td>
          <td>${categoria}</td>
          <td>${concepto}</td>
          <td class="text-end fw-semibold">${montoTxt}</td>
          <td class="text-center">${badgePagado(r.pagado)}</td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    if (window.feather) feather.replace();
  }

  // =========================
  // META
  // =========================
  function renderMeta(meta) {
    const totalRows = Number(meta?.total_rows || 0);
    const conceptos = Number(meta?.total_conceptos || 0);

    const pend = meta?.pendientes || {};
    const pag = meta?.pagados || {};

    const monedaVista = getMonedaVista();
    const tc = getTipoCambio();

    const sumConvert = (obj) => {
      let total = 0;
      Object.entries(obj).forEach(([mon, val]) => {
        total += convertAmount(val, mon, monedaVista, tc);
      });
      return total;
    };

    const totalPend = sumConvert(pend);
    const totalPag = sumConvert(pag);

    if (metaOps) metaOps.textContent = `Registros: ${totalRows}`;
    if (metaConceptos) metaConceptos.textContent = `Conceptos: ${conceptos}`;
    if (metaPend) {
      metaPend.textContent = `Pendientes: ${monedaVista} $${money(totalPend)}`;
    }
    if (metaPag) {
      metaPag.textContent = `Pagados: ${monedaVista} $${money(totalPag)}`;
    }
  }

  // =========================
  // PAGINACIÓN
  // =========================
  function renderPagination(page, totalPages, totalRows) {
    if (!pagInfo || !pagUl) return;

    const p = Number(page || 1);
    const tp = Number(totalPages || 1);
    const pp = Number(perPageSel?.value || 25);

    let showing = totalRows;
    if (pp < 10000000) {
      showing = Math.min(totalRows, p * pp);
    }

    pagInfo.textContent = `Mostrando ${showing} de ${totalRows}`;

    if (tp <= 1) {
      pagUl.innerHTML = "";
      return;
    }

    const mkItem = (label, targetPage, disabled, active) => {
      const cls = [
        "page-item",
        disabled ? "disabled" : "",
        active ? "active" : "",
      ].join(" ");

      return `
        <li class="${cls}">
          <a class="page-link" href="#" data-cc-page="${targetPage}">${label}</a>
        </li>
      `;
    };

    let html = "";
    html += mkItem("«", Math.max(1, p - 1), p === 1, false);

    const start = Math.max(1, p - 2);
    const end = Math.min(tp, p + 2);

    for (let i = start; i <= end; i++) {
      html += mkItem(String(i), i, false, i === p);
    }

    html += mkItem("»", Math.min(tp, p + 1), p === tp, false);

    pagUl.innerHTML = html;
  }

  // =========================
  // LISTAR
  // =========================
  function listar(page = 1) {
    if (state.loading) return;
    state.loading = true;

    const url = api + "?" + buildQuery(page);

    xhrGet(url, (json) => {
      state.loading = false;

      if (!json || json.status !== "success") {
        renderEmpty(json?.msg || "No se pudo listar.");
        renderMeta({
          total_rows: 0,
          total_conceptos: 0,
          pendientes: {},
          pagados: {},
        });

        if (pagUl) pagUl.innerHTML = "";
        if (pagInfo) pagInfo.textContent = "Mostrando 0 de 0";

        console.error("Error al listar finanzas:", json);
        return;
      }

      renderTable(json.rows || []);
      renderMeta(json.meta || {});

      state.page = Number(json.page || 1);
      renderPagination(
        state.page,
        Number(json.total_pages || 1),
        Number(json.total || 0),
      );
    });
  }

  // =========================
  // EVENTOS
  // =========================
  function hookChange(el) {
    if (!el) return;
    console.log("finanzas.js cargado");
    el.addEventListener("change", () => listar(1));
  }

  hookChange(clienteSel);
  hookChange(fechaInicio);
  hookChange(fechaFin);
  hookChange(brokerSel);
  hookChange(transportistaSel);
  hookChange(categoriaSel);
  hookChange(estatusPagoSel);
  hookChange(perPageSel);
  hookChange(origenSel);
  hookChange(monedaVistaSel);

  let tmr = null;

  if (termInput) {
    termInput.addEventListener("input", () => {
      clearTimeout(tmr);
      tmr = setTimeout(() => listar(1), 350);
    });
  }

  if (tipoCambioInp) {
    tipoCambioInp.addEventListener("input", () => {
      clearTimeout(tmr);
      tmr = setTimeout(() => listar(state.page || 1), 250);
    });
  }

  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", () => {
      if (clienteSel) clienteSel.value = "";
      if (fechaInicio) fechaInicio.value = "";
      if (fechaFin) fechaFin.value = "";
      if (brokerSel) brokerSel.value = "";
      if (transportistaSel) transportistaSel.value = "";
      if (categoriaSel) categoriaSel.value = "";
      if (estatusPagoSel) estatusPagoSel.value = "";
      if (termInput) termInput.value = "";
      if (perPageSel) perPageSel.value = "25";
      if (origenSel) origenSel.value = "";

      if (monedaVistaSel) monedaVistaSel.value = "MXN";
      if (tipoCambioInp) tipoCambioInp.value = "17.00";

      listar(1);
    });
  }

  if (pagUl) {
    pagUl.addEventListener("click", (e) => {
      const a = e.target.closest("[data-cc-page]");
      if (!a) return;

      e.preventDefault();

      const p = Number(a.getAttribute("data-cc-page") || 1);
      listar(p);
    });
  }

  // =========================
  // CARGA INICIAL
  // =========================
  listar(1);
})();
