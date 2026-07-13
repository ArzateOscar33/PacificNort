/* ============================================================
   MÓDULO: Costos por Cliente - LISTAR (XHR)
   Archivo: costos_clientes.js
   ============================================================ */

(function () {
  "use strict";

  const base =
    (typeof window.base !== "undefined" && window.base) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  //console.log("BASE_URL:", base);

  const api =
    base + "Operaciones_maritimo_ferro_costos_clientes/listarPaginado";

  // ---- Refs filtros ----
  const clienteSel = document.getElementById("clienteId_cc");

  const fechaInicio = document.getElementById("costosCliente_fechaInicio");
  const fechaFin = document.getElementById("costosCliente_fechaFin");

  const brokerSel = document.getElementById("brokerId_cc");

  // Transportista marítimo
  const transportistaSel = document.getElementById("transportistaId_cc");

  // NUEVO: Transportista ferro/caja
  const transportistaFerroSel = document.getElementById(
    "transportistaFerroId_cc",
  );

  // Categoría
  const categoriaSel = document.getElementById("categoriaId_cc");

  const estatusPagoSel = document.getElementById("costosCliente_estatusPago");
  const termInput = document.getElementById("costosCliente_term");
  const perPageSel = document.getElementById("costosCliente_perPage");

  const btnLimpiar = document.getElementById("costosCliente_btnLimpiar");

  // ---- Moneda vista + tipo de cambio ----
  const monedaVistaSel = document.getElementById("costosClienteMonedaVista");
  const tipoCambioInp = document.getElementById("costosClienteTipoCambio");

  // ---- Tabla ----
  const tbody = document.getElementById("costosCliente_tbody");

  // ---- Meta ----
  const metaOps = document.getElementById("costosCliente_metaTotalOps");
  const metaConceptos = document.getElementById(
    "costosCliente_metaTotalConceptos",
  );
  const metaPend = document.getElementById("costosCliente_metaPendientes");
  const metaPag = document.getElementById("costosCliente_metaPagados");

  // ---- Paginación ----
  const pagInfo = document.getElementById("costosCliente_metaPaginacion");
  const pagUl = document.getElementById("costosCliente_paginacion");

  let state = { page: 1, loading: false };

  // Ahora la tabla tiene 13 columnas
  const COLS = 13;

  // ---------------- helpers ----------------
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
    if (!s) return "—";
    return String(s).slice(0, 10);
  };

  function buildQuery(page) {
    const qs = new URLSearchParams();

    qs.set("page", String(page));
    qs.set("per_page", perPageSel?.value || "25");

    // Cliente
    qs.set("clienteId_cc", clienteSel?.value ?? "");

    if (fechaInicio?.value) qs.set("fecha_inicio", fechaInicio.value);
    if (fechaFin?.value) qs.set("fecha_fin", fechaFin.value);

    if (brokerSel?.value) qs.set("brokerId_cc", brokerSel.value);

    // Transportista marítimo
    if (transportistaSel?.value) {
      qs.set("transportistaId_cc", transportistaSel.value);
    }

    // NUEVO: Transportista ferro/caja
    if (transportistaFerroSel?.value) {
      qs.set("transportistaFerroId_cc", transportistaFerroSel.value);
    }

    // Categoría
    if (categoriaSel?.value) qs.set("categoriaId_cc", categoriaSel.value);

    if (estatusPagoSel?.value !== "") {
      qs.set("costosCliente_estatusPago", estatusPagoSel.value);
    }

    const t = (termInput?.value || "").trim();
    if (t) qs.set("costosCliente_term", t);

    return qs.toString();
  }

  // -------- conversión de moneda --------
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

  function groupByOperacion(rows) {
    const map = new Map();

    rows.forEach((r) => {
      const k = String(r.id_operacion);
      if (!map.has(k)) map.set(k, []);
      map.get(k).push(r);
    });

    return Array.from(map.entries()).map(([id, items]) => ({
      id_operacion: Number(id),
      items,
    }));
  }

  function badgeIsf(isf) {
    const ok = Number(isf) === 1;
    return ok
      ? `<span class="badge bg-success text-white">Sí</span>`
      : `<span class="badge bg-secondary text-white">No</span>`;
  }

  function badgePagado(p) {
    const ok = Number(p) === 1;
    return ok
      ? `<span class="badge bg-success text-white">Sí</span>`
      : `<span class="badge bg-danger text-white">No</span>`;
  }

  function renderTable(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      renderEmpty("Sin resultados con los filtros actuales.");
      return;
    }

    const groups = groupByOperacion(rows);
    let html = "";

    const isTodos = !clienteSel || clienteSel.value === "";
    const monedaVista = getMonedaVista();
    const tc = getTipoCambio();

    groups.forEach((g) => {
      const items = g.items;
      const first = items[0];
      const rowspan = items.length;

      const estatus = first.estatus
        ? `<span class="badge bg-primary text-white">${esc(first.estatus)}</span>`
        : "—";

      //const brokers = first.brokers ? esc(first.brokers) : "—";
      const conts = first.contenedores ? esc(first.contenedores) : "—";
      const ferros = first.ferros_cajas ? esc(first.ferros_cajas) : "—";
      const transMar = first.transportista ? esc(first.transportista) : "—";
      const transFerro = first.transportistas_ferro
        ? esc(first.transportistas_ferro)
        : "—";

      const opLabel = (() => {
        const op = first.numero_operacion ? esc(first.numero_operacion) : "—";
        const cli = (first.cliente || "").trim();

        if (isTodos && cli) {
          return `<div class="d-flex flex-column">
                    <span class="fw-semibold">${op}</span>
                    <small class="text-muted">${esc(cli)}</small>
                  </div>`;
        }

        return op;
      })();

      items.forEach((r, idx) => {
        const categoria = (r.categoria || "").trim() ? esc(r.categoria) : "—";
        const concepto = r.concepto ? esc(r.concepto) : "—";

        const montoConv = convertAmount(r.monto, r.moneda, monedaVista, tc);
        const montoTxt = money(montoConv);
        const broker = (r.broker || r.brokers || "").trim()
          ? esc(r.broker || r.brokers)
          : "—";
        const factura = (r.factura || "").trim() ? esc(r.factura) : "—";

        if (idx === 0) {
          html += `
    <tr>
      <td rowspan="${rowspan}">${opLabel}</td>
      <td rowspan="${rowspan}">${conts}</td>
      <td rowspan="${rowspan}">${ferros}</td>
      <td rowspan="${rowspan}">${transMar}</td>
      <td rowspan="${rowspan}">${transFerro}</td>
      <td rowspan="${rowspan}">${estatus}</td>
      <td rowspan="${rowspan}">${fmtDate(first.cita_puerto)}</td>
      <td rowspan="${rowspan}" class="text-center">${badgeIsf(first.isf)}</td>

      <td>${broker}</td>
      <td>${factura}</td>
      <td>${categoria}</td>
      <td>${concepto}</td>
      <td class="text-end">$${montoTxt}</td>
      <td class="text-center">${badgePagado(r.Pagado)}</td>
    </tr>
  `;
        } else {
          html += `
    <tr>
      <td>${broker}</td>
      <td>${factura}</td>
      <td>${categoria}</td>
      <td>${concepto}</td>
      <td class="text-end">$${montoTxt}</td>
      <td class="text-center">${badgePagado(r.Pagado)}</td>
    </tr>
  `;
        }
      });
    });

    tbody.innerHTML = html;
    if (window.feather) feather.replace();
  }

  function renderMeta(meta) {
    const ops = Number(meta?.total_ops || 0);
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

    if (metaOps) metaOps.textContent = `Ops: ${ops}`;
    if (metaConceptos) metaConceptos.textContent = `Conceptos: ${conceptos}`;
    if (metaPend)
      metaPend.textContent = `Pendientes: ${monedaVista} $${money(totalPend)}`;
    if (metaPag)
      metaPag.textContent = `Pagados: ${monedaVista} $${money(totalPag)}`;
  }

  function renderPagination(page, totalPages, totalOps) {
    const p = Number(page || 1);
    const tp = Number(totalPages || 1);

    const pp = Number(perPageSel?.value || 25);
    const showing = pp >= 10000000 ? totalOps : Math.min(totalOps, p * pp);

    if (pagInfo) pagInfo.textContent = `Mostrando ${showing} de ${totalOps}`;

    if (!pagUl) return;

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

  function listar(page = 1) {
    if (state.loading) return;
    state.loading = true;

    const url = api + "?" + buildQuery(page);

    xhrGet(url, (json) => {
      state.loading = false;

      if (!json || json.status !== "success") {
        renderEmpty(json?.msg || "No se pudo listar.");

        renderMeta({
          total_ops: 0,
          total_conceptos: 0,
          pendientes: {},
          pagados: {},
        });

        // console.log("Error al listar:", json);

        if (pagUl) pagUl.innerHTML = "";
        if (pagInfo) pagInfo.textContent = "Mostrando 0 de 0";
        return;
      }

      renderTable(json.rows || []);
      renderMeta(json.meta || {});

      state.page = Number(json.page || 1);
      renderPagination(
        state.page,
        json.total_pages || 1,
        Number(json.total || 0),
      );
    });
  }

  // ---------------- eventos ----------------
  function hookChange(el) {
    if (!el) return;
    el.addEventListener("change", () => listar(1));
  }

  hookChange(clienteSel);
  hookChange(fechaInicio);
  hookChange(fechaFin);
  hookChange(brokerSel);
  hookChange(transportistaSel);
  hookChange(transportistaFerroSel);
  hookChange(categoriaSel);
  hookChange(estatusPagoSel);
  hookChange(perPageSel);
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
      if (transportistaFerroSel) transportistaFerroSel.value = "";
      if (categoriaSel) categoriaSel.value = "";
      if (estatusPagoSel) estatusPagoSel.value = "";
      if (termInput) termInput.value = "";
      if (perPageSel) perPageSel.value = "25";

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

  // Carga inicial
  listar(1);
})();
