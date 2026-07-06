// ============================================================================
//  MÓDULO: Costos por Operación por Partida / Domésticos
//  Catálogo / Vista
//  SIN guardar: el guardado se queda en otro JS
// ============================================================================

(function () {
  "use strict";

  // ---------------------- BASE URL ----------------------
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ---------------------- DOM (vista principal) ----------------------
  const tbody = document.getElementById("tbodyCostosPartida");
  const inpBuscar = document.getElementById("costosPartidaBuscar");
  const selMonedaFiltro = document.getElementById("costosPartidaFiltroMoneda");
  const selTipoFiltro = document.getElementById("costosPartidaFiltroTipo"); // opcional
  const selPerPage = document.getElementById("costosPartidaPerPage");
  const ulPag = document.getElementById("costosPartidaPaginacion");
  const metaTxt = document.getElementById("costosPartidaMeta");

  // Totales / cards
  const cardTotOperacion = document.getElementById(
    "costosPartidaTotalOperacion",
  );
  const cardTotGeneral = document.getElementById("costosPartidaTotalGeneral");
  const selMonedaVista = document.getElementById("costosPartidaMonedaVista");
  const inpTipoCambio = document.getElementById("costosPartidaTipoCambio");

  // Factura (filtro superior)
  const facturaIdHid = document.getElementById("costosPartidaFiltroFacturaId");
  const facturaNomInp = document.getElementById(
    "costosPartidaFiltroFacturaNombre",
  );
  const facturaListBox = document.getElementById(
    "costosPartidaFiltroFacturaSugerencias",
  );
  const facturaMeta = document.getElementById("costosPartidaFiltroFacturaMeta");

  // Ferro/Caja (filtro superior)
  const selFerroFiltro = document.getElementById("costosPartidaFiltroFerroId");
  const ferroFiltroMeta = document.getElementById(
    "costosPartidaFiltroFerroMeta",
  );

  // ---------------------- DOM (modal) ----------------------
  const modalEl = document.getElementById("modalCostoPartida");
  const hidRowId = document.getElementById("costosPartidaRowId");

  const facturaIdModal = document.getElementById("costosPartidaFacturaId");
  const facturaNomModal = document.getElementById("costosPartidaFacturaNombre");
  const listFacturasModal = document.getElementById(
    "costosPartidaSugerenciasFacturas",
  );

  const selFerroModal = document.getElementById("costosPartidaFerroId");
  const ferroModalMeta = document.getElementById("costosPartidaFerroMeta");

  const selTipoModal = document.getElementById("costosPartidaTipoMovimientoId");
  const selMonModal = document.getElementById("costosPartidaMoneda");
  const montoModal = document.getElementById("costosPartidaMonto");
  const comentModal = document.getElementById("costosPartidaComentario");
  const selPagadoModal = document.getElementById("costosPartidaPagado");

  const btnNuevo = document.getElementById("costosPartidaBtnNuevo");

  // ---------------------- Estado ----------------------
  let currentPage = 1;
  let perPage = parseInt(selPerPage?.value || "10", 10) || 10;
  let currentXHR = null;
  let debounceId = null;
  let isEditCosto = false;

  let facturaId = parseInt(facturaIdHid?.value || "0", 10) || 0;

  let totalesDetalleCache = null;
  let abonosDetalleCache = null;

  // ---------------------- Helpers ----------------------
  const safe = (v) => (v === undefined || v === null ? "" : String(v));
  const prettyMoneda = (m) => String(m || "").toUpperCase();
  const fmtFecha = (s) => (s ? String(s).substring(0, 10) : "");

  const fmtMoney = (n, sym = "$") =>
    sym +
    " " +
    Number(n || 0).toLocaleString("es-MX", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  function toast(kind, title, text) {
    if (window.Swal) Swal.fire({ icon: kind, title, text });
    else alert((title ? title + ": " : "") + (text || ""));
  }

  function renderCargando() {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando resultados…</td></tr>`;
  }

  function renderVacio(msg) {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">${msg || "No hay costos para mostrar."}</td></tr>`;
  }

  function ensureMonedaOptions(sel) {
    if (!sel) return;
    if (!sel.querySelector('option[value="PESOS"]')) {
      sel.insertAdjacentHTML(
        "beforeend",
        '<option value="PESOS">PESOS</option>',
      );
    }
    if (!sel.querySelector('option[value="DLLS"]')) {
      sel.insertAdjacentHTML("beforeend", '<option value="DLLS">DLLS</option>');
    }
  }
  ensureMonedaOptions(selMonModal);

  function badgePagado(v) {
    return Number(v) === 1
      ? `<span class="badge bg-success text-white">Pagado</span>`
      : `<span class="badge bg-danger text-white">Pendiente</span>`;
  }

  function lockEditFields() {
    if (facturaNomModal) facturaNomModal.readOnly = true;
    if (selTipoModal) selTipoModal.disabled = true;
    if (listFacturasModal) {
      listFacturasModal.innerHTML = "";
      listFacturasModal.style.display = "none";
    }
  }

  function unlockEditFields() {
    if (facturaNomModal) facturaNomModal.readOnly = false;
    if (selTipoModal) selTipoModal.disabled = false;
  }

  // ---------------------- Conversión vista ----------------------
  function normVistaMoneda(vista) {
    const v = String(vista || "").toUpperCase();
    return v === "USD" ? "USD" : "MXN";
  }

  function normRowMoneda(rowMon) {
    const m = String(rowMon || "").toUpperCase();
    if (m === "DLLS" || m === "USD") return "USD";
    return "MXN";
  }

  function getTipoCambio() {
    let tc = parseFloat(inpTipoCambio?.value || "0");
    if (!Number.isFinite(tc) || tc <= 0) tc = 1;
    return tc;
  }

  function getVistaSymbol() {
    const vista = normVistaMoneda(selMonedaVista?.value || "MXN");
    return vista === "USD" ? "US$" : "$";
  }

  function convertAmount(amt, rowMoneda) {
    const src = normRowMoneda(rowMoneda);
    const dst = normVistaMoneda(selMonedaVista?.value || "MXN");
    const tc = getTipoCambio();

    const n = Number(amt || 0) || 0;
    if (src === dst) return n;
    if (src === "USD" && dst === "MXN") return n * tc;
    if (src === "MXN" && dst === "USD") return n / tc;
    return n;
  }

  // ---------------------- Endpoints ----------------------
  const END = {
    tiposMovimiento: () =>
      `${base_url}Operaciones_por_partida_costos/tiposMovimiento`,
    listarPaginado: (qs) =>
      `${base_url}Operaciones_por_partida_costos/listarPaginado?${qs}`,
    buscarOperaciones: (term) =>
      `${base_url}Operaciones_por_partida_costos/buscarOperaciones?term=${encodeURIComponent(term)}`,
    obtenerFerrosPorFactura: (facturaId) =>
      `${base_url}Operaciones_por_partida_costos/obtenerFerrosPorFactura?factura_id=${encodeURIComponent(facturaId)}`,
    obtenerUno: (id) =>
      `${base_url}Operaciones_por_partida_costos/obtenerUno?id=${encodeURIComponent(id)}`,
    desactivar: () =>
      `${base_url}Operaciones_por_partida_costos/desactivarCostoOperacion`,
  };

  // ---------------------- Tipos movimiento ----------------------
  function loadTiposMovimiento(selectEl, selectedId = null, done = null) {
    if (!selectEl) return;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.tiposMovimiento(), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) {
        selectEl.innerHTML = '<option value="">Seleccione un tipo</option>';
        if (selectedId) selectEl.value = String(selectedId);
        if (typeof done === "function") done();
        return;
      }

      let rows = [];
      try {
        rows = JSON.parse(xhr.responseText) || [];
      } catch {
        rows = [];
      }

      if (selTipoFiltro) {
        const cur = selTipoFiltro.value;
        selTipoFiltro.innerHTML =
          `<option value="0">Todos los conceptos</option>` +
          rows
            .map(
              (t) =>
                `<option value="${t.id_tipo_movimiento}">${safe(t.nombre)}</option>`,
            )
            .join("");
        if (cur !== null && cur !== undefined && cur !== "") {
          selTipoFiltro.value = cur;
        }
      }

      let html = '<option value="">Seleccione un tipo</option>';
      rows.forEach((r) => {
        const id = r.id_tipo_movimiento;
        const nom = r.nombre || "";
        const mon = (r.moneda || "").toUpperCase();
        html += `<option value="${id}" data-moneda="${mon}">${nom}</option>`;
      });

      selectEl.innerHTML = html;

      if (selectedId) selectEl.value = String(selectedId);
      if (typeof done === "function") done();
    };
    xhr.send();
  }

  // ---------------------- Ferros por factura ----------------------
  function cargarFerrosPorFactura(
    facturaIdArg,
    selectEl,
    metaEl,
    selectedId = "",
    done = null,
  ) {
    if (!selectEl) return;

    const fid = parseInt(facturaIdArg || "0", 10) || 0;

    if (fid <= 0) {
      selectEl.innerHTML =
        '<option value="">Seleccione una factura primero</option>';
      if (metaEl)
        metaEl.textContent =
          "Selecciona la factura para cargar sus ferros/cajas.";
      if (typeof done === "function") done([]);
      return;
    }

    selectEl.innerHTML = '<option value="">Cargando ferros/cajas...</option>';
    if (metaEl) metaEl.textContent = "Cargando ferros/cajas vinculados...";

    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.obtenerFerrosPorFactura(fid), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) {
        selectEl.innerHTML = '<option value="">Error al cargar</option>';
        if (metaEl)
          metaEl.textContent = "No fue posible cargar los ferros/cajas.";
        if (typeof done === "function") done([]);
        return;
      }

      let resp = {};
      try {
        resp = JSON.parse(xhr.responseText) || {};
      } catch {
        resp = {};
      }

      const rows = Array.isArray(resp.data) ? resp.data : [];

      if (rows.length === 0) {
        selectEl.innerHTML =
          '<option value="">Sin ferros/cajas vinculados</option>';
        if (metaEl)
          metaEl.textContent = "La factura no tiene ferros/cajas vinculados.";
        if (typeof done === "function") done([]);
        return;
      }

      let html = '<option value="">Seleccione una caja/ferro</option>';
      rows.forEach((r) => {
        const id = r.contenedor_fisico_id || r.id_fisico || "";
        const numero =
          r.numero ||
          r.numero_ferro ||
          r.contenedor ||
          r.ferro ||
          `Ferro/Caja #${id}`;
        const totalEnvios = Number(r.total_envios || 0) || 0;

        html += `<option value="${id}">${safe(numero)}${totalEnvios > 0 ? `` : ""}</option>`;
      });

      selectEl.innerHTML = html;

      if (selectedId) {
        selectEl.value = String(selectedId);
      }

      if (!selectEl.value && rows.length === 1) {
        selectEl.value = String(
          rows[0].contenedor_fisico_id || rows[0].id_fisico || "",
        );
      }

      if (metaEl) {
        metaEl.textContent = `${rows.length} ferro(s)/caja(s) vinculado(s) a la factura.`;
      }

      if (typeof done === "function") done(rows);
    };
    xhr.send();
  }

  function obtenerCostoXHR(id, onDone) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.obtenerUno(id), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      let resp = {};
      try {
        resp = JSON.parse(xhr.responseText) || {};
      } catch {
        resp = {};
      }

      if (typeof onDone === "function") onDone(xhr.status, resp);
    };
    xhr.send();
  }

  function desactivarCostoXHR(id, onDone) {
    const fd = new FormData();
    fd.append("id", String(id));

    const xhr = new XMLHttpRequest();
    xhr.open("POST", END.desactivar(), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      let resp = {};
      try {
        resp = JSON.parse(xhr.responseText) || {};
      } catch {
        resp = {};
      }
      if (typeof onDone === "function") onDone(xhr.status, resp);
    };
    xhr.send(fd);
  }

  // ---------------------- Render tabla ----------------------
  function renderTabla(rows) {
    if (!tbody) return;

    tbody.innerHTML = "";
    if (!Array.isArray(rows) || rows.length === 0) {
      renderVacio("No hay costos para mostrar.");
      return;
    }

    rows.forEach((r) => {
      const tr = document.createElement("tr");

      const nat = String(r.naturaleza || "").toUpperCase();
      const isAbono = nat === "ABONO";

      const symbolVista = getVistaSymbol();
      const montoVista = convertAmount(r.monto || 0, r.moneda);
      const montoFmt = fmtMoney(montoVista, symbolVista);

      const montoCls = isAbono
        ? "text-success fw-semibold"
        : "text-danger fw-semibold";

      const montoConSigno = `${isAbono ? "+" : " "}${montoFmt}`;
      const badgeNat = nat
        ? `<span class="badge ${isAbono ? "bg-success-subtle text-success" : "bg-danger-subtle text-danger"} ms-1">${nat}</span>`
        : "";

      tr.dataset.rowId = r.row_id || "";
      tr.dataset.facturaId = r.factura_id || "";
      tr.dataset.facturaNom = r.numero_factura || r.numero_operacion || "";
      tr.dataset.tipoId = r.tipo_movimiento_id || "";
      tr.dataset.tipoNom = r.concepto || "";
      tr.dataset.moneda = r.moneda || "";
      tr.dataset.monto = r.monto || "";
      tr.dataset.coment = r.comentario || "";
      tr.dataset.pagado = r.pagado ?? "0";
      tr.dataset.nat = nat;
      tr.dataset.contenedorFisicoId =
        r.contenedor_fisico_id || r.contenedor_id || "";
      tr.dataset.contenedor = r.numero_ferro || r.contenedor || "";

      tr.innerHTML = `
        <td>${fmtFecha(r.fecha)}</td>
        <td>${safe(r.concepto)}${badgeNat}</td>
        <td class="text-end ${montoCls} jsMontoVista"
            data-amt="${Number(r.monto || 0) || 0}"
            data-mon="${prettyMoneda(r.moneda)}">
          ${montoConSigno}
        </td>
        <td class="text-center">${badgePagado(r.pagado)}</td>
        <td>${safe(r.comentario)}</td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary btnEditarCostoPartida" title="Ver / Editar">
              <i data-feather="edit-2"></i>
            </button>
            <button type="button" class="btn btn-outline-danger btnEliminarCostoPartida" title="Eliminar">
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    window.feather?.replace?.();
  }

  function refrescarMontosTablaVista() {
    if (!tbody) return;

    const symbolVista = getVistaSymbol();

    tbody.querySelectorAll("td.jsMontoVista").forEach((td) => {
      const amt = parseFloat(td.dataset.amt || "0") || 0;
      const mon = (td.dataset.mon || "").toUpperCase();
      const tr = td.closest("tr");
      const isAbono = String(tr?.dataset?.nat || "").toUpperCase() === "ABONO";

      const montoVista = convertAmount(amt, mon);
      const montoFmt = fmtMoney(montoVista, symbolVista);

      td.textContent = `${isAbono ? "+" : " "}${montoFmt}`;
    });
  }

  // ---------------------- Totales ----------------------
  function renderTotales(totalesDetalle) {
    if (totalesDetalle) totalesDetalleCache = totalesDetalle;

    const det = totalesDetalleCache || { operacion: { PESOS: 0, DLLS: 0 } };
    const opPesos = Number(det.operacion?.PESOS || 0);
    const opDlls = Number(det.operacion?.DLLS || 0);

    const vista = (selMonedaVista?.value || "MXN").toUpperCase();
    let tc = parseFloat(inpTipoCambio?.value || "0");
    if (!Number.isFinite(tc) || tc <= 0) tc = 1;

    let symbol = "$";
    let totalOpConv = 0;

    if (vista === "MXN") {
      symbol = "$";
      totalOpConv = opPesos + opDlls * tc;
    } else {
      symbol = "US$";
      totalOpConv = opDlls + opPesos / tc;
    }

    if (cardTotOperacion)
      cardTotOperacion.textContent = fmtMoney(totalOpConv, symbol);
    if (cardTotGeneral)
      cardTotGeneral.textContent = fmtMoney(totalOpConv, symbol);
  }

  function computeViewTotals(detCostos, detAbonos) {
    const vista = (selMonedaVista?.value || "MXN").toUpperCase();
    let tc = parseFloat(inpTipoCambio?.value || "0");
    if (!Number.isFinite(tc) || tc <= 0) tc = 1;

    const c = detCostos || { operacion: { PESOS: 0, DLLS: 0 } };
    const a = detAbonos || { operacion: { PESOS: 0, DLLS: 0 } };

    const opPesosC = Number(c.operacion?.PESOS || 0);
    const opDllsC = Number(c.operacion?.DLLS || 0);
    const opPesosA = Number(a.operacion?.PESOS || 0);
    const opDllsA = Number(a.operacion?.DLLS || 0);

    let opCost = 0;
    let opAbono = 0;
    let symbol = "$";

    if (vista === "MXN") {
      symbol = "$";
      opCost = opPesosC + opDllsC * tc;
      opAbono = opPesosA + opDllsA * tc;
    } else {
      symbol = "US$";
      opCost = opDllsC + opPesosC / tc;
      opAbono = opDllsA + opPesosA / tc;
    }

    const fmt = (n) =>
      symbol +
      " " +
      Number(n || 0).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    return { opCost, opAbono, fmt };
  }

  function setBadgeValueSimple(id, val, fmt) {
    const el = document.getElementById(id);
    if (!el) return;

    el.textContent = fmt(val);
    el.classList.remove(
      "bg-light",
      "text-dark",
      "bg-danger",
      "bg-success",
      "bg-secondary",
    );

    if (val > 0) el.classList.add("bg-success");
    else if (val < 0) el.classList.add("bg-danger");
    else el.classList.add("bg-secondary");
  }

  function renderCostosAbonosCardsSoloOperacion({
    opCost = 0,
    opAbono = 0,
    fmt,
  } = {}) {
    const opBalance = opAbono - opCost;

    const elTotOp = document.getElementById("costosPartidaTotalOperacion");
    const elAbOp = document.getElementById("costosPartidaAbonosOperacion");

    if (elTotOp) elTotOp.textContent = fmt(opCost);
    if (elAbOp) elAbOp.textContent = fmt(opAbono);

    setBadgeValueSimple("costosPartidaBalanceOperacion", opBalance, fmt);

    const totalAbonos = opAbono;
    const totalCostos = opCost;
    const totalBalance = totalAbonos - totalCostos;

    const elGen = document.getElementById("costosPartidaTotalGeneral");
    const elGenAb = document.getElementById("costosPartidaTotalAbonosGeneral");
    const elGenCost = document.getElementById(
      "costosPartidaTotalCostosGeneral",
    );

    if (elGen) elGen.textContent = fmt(totalBalance);
    if (elGenAb) elGenAb.textContent = fmt(totalAbonos);
    if (elGenCost) elGenCost.textContent = fmt(totalCostos);
  }

  // ---------------------- Paginación / meta ----------------------
  function renderPaginacion(meta) {
    if (!ulPag) return;

    const page = parseInt(meta.page || 1, 10);
    const totalPages = parseInt(meta.totalPages || 1, 10);

    if (totalPages <= 1) {
      ulPag.innerHTML = "";
      return;
    }

    let start = Math.max(1, page - 2);
    let end = Math.min(totalPages, start + 4);
    start = Math.max(1, end - 4);

    let html = `
      <li class="page-item ${page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page - 1}">«</a>
      </li>`;

    for (let p = start; p <= end; p++) {
      html += `
        <li class="page-item ${p === page ? "active" : ""}">
          <a class="page-link" href="#" data-page="${p}">${p}</a>
        </li>`;
    }

    html += `
      <li class="page-item ${page === totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page + 1}">»</a>
      </li>`;

    ulPag.innerHTML = html;

    ulPag.querySelectorAll("a.page-link").forEach((a) => {
      a.addEventListener("click", (e) => {
        e.preventDefault();
        const p = parseInt(a.dataset.page || "1", 10);
        if (!Number.isNaN(p)) listar(p);
      });
    });
  }

  function renderMeta(meta) {
    if (!metaTxt) return;

    const page = parseInt(meta.page || 1, 10);
    const perPg = parseInt(meta.perPage || perPage || 10, 10);
    const total = parseInt(meta.total || 0, 10);

    const ini = total === 0 ? 0 : (page - 1) * perPg + 1;
    const fin = Math.min(page * perPg, total);

    metaTxt.textContent = `Mostrando ${ini}–${fin} de ${total}`;
  }

  // ---------------------- Listar ----------------------
  function listar(page = 1) {
    currentPage = page;

    if (!facturaId || facturaId <= 0) {
      renderVacio("Selecciona una factura para ver sus costos.");
      renderTotales({ operacion: { PESOS: 0, DLLS: 0 } });
      renderPaginacion({ page: 1, totalPages: 0 });
      renderMeta({ page: 1, perPage, total: 0 });
      return;
    }

    const buscar = (inpBuscar?.value || "").trim();
    const moneda = (selMonedaFiltro?.value || "").toUpperCase();
    const tipoId = parseInt(selTipoFiltro?.value || "0", 10) || 0;
    const contenedorFisicoId = parseInt(selFerroFiltro?.value || "0", 10) || 0;

    if (currentXHR && currentXHR.readyState !== 4) currentXHR.abort();
    renderCargando();

    const params = new URLSearchParams({
      page: String(currentPage),
      perPage: String(perPage),
      buscar,
      moneda,
      tipo: String(tipoId),
      factura_id: String(facturaId),
      contenedor_fisico_id: String(contenedorFisicoId),
      solo_activos: "1",
    });

    const url = END.listarPaginado(params.toString());
    currentXHR = new XMLHttpRequest();
    currentXHR.open("GET", url, true);
    currentXHR.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error(this.responseText);
        renderVacio("Error al cargar costos.");
        return;
      }

      let payload = {};
      try {
        payload = JSON.parse(this.responseText) || {};
      } catch {
        payload = {};
      }

      const data = payload.data || [];
      const meta = payload.meta || {
        page: 1,
        totalPages: 1,
        total: 0,
        perPage,
      };
      const totalesDetalle = payload.totales_detalle || {
        operacion: { PESOS: 0, DLLS: 0 },
      };
      const abonosDetalle = payload.abonos_detalle || {
        operacion: { PESOS: 0, DLLS: 0 },
      };

      if (
        Array.isArray(data) &&
        data.length === 0 &&
        meta.totalPages > 0 &&
        currentPage > meta.totalPages
      ) {
        listar(meta.totalPages);
        return;
      }

      renderTabla(data);
      renderPaginacion(meta);
      renderMeta(meta);

      totalesDetalleCache = totalesDetalle;
      abonosDetalleCache = abonosDetalle;

      renderTotales(totalesDetalleCache);
      const { opCost, opAbono, fmt } = computeViewTotals(
        totalesDetalleCache,
        abonosDetalleCache,
      );
      renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
    };
    currentXHR.send();
  }
  window.listarCostosPartida = listar;

  // ---------------------- Buscar facturas (filtro superior) ----------------------
  function buscarFacturas(term) {
    if (!facturaListBox) return;

    if (!term || term.length < 2) {
      facturaListBox.style.display = "none";
      facturaListBox.innerHTML = "";
      if (facturaMeta) facturaMeta.textContent = "";
      return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.buscarOperaciones(term), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) {
        facturaListBox.style.display = "none";
        facturaListBox.innerHTML = "";
        if (facturaMeta) facturaMeta.textContent = "";
        return;
      }

      let rows = [];
      try {
        rows = JSON.parse(xhr.responseText) || [];
      } catch {
        rows = [];
      }

      if (!Array.isArray(rows) || rows.length === 0) {
        facturaListBox.style.display = "none";
        facturaListBox.innerHTML = "";
        if (facturaMeta) facturaMeta.textContent = "Sin resultados";
        return;
      }

      let html = "";
      rows.forEach((r) => {
        const id = r.id;
        const nom = r.numero_operacion || "";
        const cli = r.cliente || "";
        const prov = r.proveedor || "";

        html += `
          <button type="button" class="list-group-item list-group-item-action"
                  data-id="${id}" data-nom="${safe(nom)}" data-cli="${safe(cli)}" data-prov="${safe(prov)}">
            ${safe(nom)}${cli ? " - " + safe(cli) : ""}${prov ? " - " + safe(prov) : ""}
          </button>`;
      });

      facturaListBox.innerHTML = html;
      facturaListBox.style.display = "block";

      facturaListBox
        .querySelectorAll("button.list-group-item")
        .forEach((btn) => {
          btn.addEventListener("click", () => {
            const id = parseInt(btn.dataset.id || "0", 10) || 0;

            if (facturaIdHid) facturaIdHid.value = String(id);
            if (facturaNomInp) facturaNomInp.value = btn.textContent.trim();
            facturaId = id;

            facturaListBox.innerHTML = "";
            facturaListBox.style.display = "none";

            cargarFerrosPorFactura(
              facturaId,
              selFerroFiltro,
              ferroFiltroMeta,
              "",
              function () {
                listar(1);
              },
            );

            if (facturaMeta) facturaMeta.textContent = "Factura seleccionada.";
          });
        });
    };
    xhr.send();
  }

  facturaNomInp?.addEventListener("input", () => {
    const term = (facturaNomInp.value || "").trim();

    if (facturaIdHid) facturaIdHid.value = "";
    facturaId = 0;

    if (selFerroFiltro) {
      selFerroFiltro.innerHTML =
        '<option value="">Seleccione una factura primero</option>';
    }
    if (ferroFiltroMeta) {
      ferroFiltroMeta.textContent =
        "Selecciona la factura para cargar sus ferros/cajas.";
    }

    buscarFacturas(term);
  });

  // ---------------------- Buscar facturas (modal) ----------------------
  function buscarFacturasModal(term) {
    if (!listFacturasModal) return;

    if (!term || term.length < 2) {
      listFacturasModal.style.display = "none";
      listFacturasModal.innerHTML = "";
      return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.buscarOperaciones(term), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) {
        listFacturasModal.style.display = "none";
        listFacturasModal.innerHTML = "";
        return;
      }

      let rows = [];
      try {
        rows = JSON.parse(xhr.responseText) || [];
      } catch {
        rows = [];
      }

      if (!Array.isArray(rows) || rows.length === 0) {
        listFacturasModal.style.display = "none";
        listFacturasModal.innerHTML = "";
        return;
      }

      let html = "";
      rows.forEach((r) => {
        const id = r.id;
        const nom = r.numero_operacion || "";
        const cli = r.cliente || "";
        const prov = r.proveedor || "";

        html += `
          <button type="button" class="list-group-item list-group-item-action"
                  data-id="${id}" data-nom="${safe(nom)}" data-cli="${safe(cli)}" data-prov="${safe(prov)}">
            ${safe(nom)}${cli ? " - " + safe(cli) : ""}${prov ? " - " + safe(prov) : ""}
          </button>`;
      });

      listFacturasModal.innerHTML = html;
      listFacturasModal.style.display = "block";

      listFacturasModal
        .querySelectorAll("button.list-group-item")
        .forEach((btn) => {
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();

            const id = parseInt(btn.dataset.id || "0", 10) || 0;

            if (facturaIdModal) facturaIdModal.value = String(id);
            if (facturaNomModal) facturaNomModal.value = btn.textContent.trim();

            cargarFerrosPorFactura(id, selFerroModal, ferroModalMeta);

            listFacturasModal.innerHTML = "";
            listFacturasModal.style.display = "none";
          });
        });
    };
    xhr.send();
  }

  // ---------------------- Init ----------------------
  document.addEventListener("DOMContentLoaded", () => {
    if (perPage < 1) perPage = 10;

    loadTiposMovimiento(selTipoModal);

    if (facturaId > 0) {
      cargarFerrosPorFactura(
        facturaId,
        selFerroFiltro,
        ferroFiltroMeta,
        "",
        function () {
          listar(1);
        },
      );
    } else {
      renderVacio("Selecciona una factura para ver sus costos.");
    }
  });

  // ---------------------- Filtros ----------------------
  document.addEventListener("click", (e) => {
    if (
      facturaListBox &&
      !facturaListBox.contains(e.target) &&
      e.target !== facturaNomInp
    ) {
      facturaListBox.style.display = "none";
    }

    if (
      listFacturasModal &&
      !listFacturasModal.contains(e.target) &&
      e.target !== facturaNomModal
    ) {
      listFacturasModal.style.display = "none";
    }
  });

  selPerPage?.addEventListener("change", () => {
    perPage = parseInt(selPerPage.value || "10", 10) || 10;
    listar(1);
  });

  inpBuscar?.addEventListener("keyup", (e) => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => listar(1), 250);
    if (e.key === "Enter") {
      clearTimeout(debounceId);
      listar(1);
    }
  });

  selMonedaFiltro?.addEventListener("change", () => listar(1));
  selTipoFiltro?.addEventListener("change", () => listar(1));
  selFerroFiltro?.addEventListener("change", () => listar(1));

  selMonedaVista?.addEventListener("change", () => {
    renderTotales(totalesDetalleCache);
    const { opCost, opAbono, fmt } = computeViewTotals(
      totalesDetalleCache,
      abonosDetalleCache,
    );
    renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
    refrescarMontosTablaVista();
  });

  inpTipoCambio?.addEventListener("input", () => {
    renderTotales(totalesDetalleCache);
    const { opCost, opAbono, fmt } = computeViewTotals(
      totalesDetalleCache,
      abonosDetalleCache,
    );
    renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
    refrescarMontosTablaVista();
  });

  // ---------------------- Modal: moneda por tipo ----------------------
  function syncMonedaPorTipoModal() {
    if (isEditCosto) return;
    if (!selTipoModal) return;

    const opt = selTipoModal.selectedOptions?.[0];
    const m = opt ? (opt.getAttribute("data-moneda") || "").toUpperCase() : "";
    if (selMonModal) {
      selMonModal.value = m === "PESOS" || m === "DLLS" ? m : "";
    }
  }

  selTipoModal?.addEventListener("change", syncMonedaPorTipoModal);

  function resetModalView() {
    if (hidRowId) hidRowId.value = "";
    if (facturaIdModal) facturaIdModal.value = "";
    if (facturaNomModal) facturaNomModal.value = "";

    if (selTipoModal) selTipoModal.value = "";
    if (selMonModal) selMonModal.value = "";
    if (montoModal) montoModal.value = "";
    if (comentModal) comentModal.value = "";
    if (selPagadoModal) selPagadoModal.value = "0";

    if (selFerroModal) {
      selFerroModal.innerHTML =
        '<option value="">Seleccione una factura primero</option>';
    }
    if (ferroModalMeta) {
      ferroModalMeta.textContent =
        "Selecciona una factura para cargar sus ferros/cajas.";
    }

    if (listFacturasModal) {
      listFacturasModal.innerHTML = "";
      listFacturasModal.style.display = "none";
    }
  }

  // ---------------------- Abrir modal nuevo ----------------------
  btnNuevo?.addEventListener("click", () => {
    isEditCosto = false;
    unlockEditFields();
    resetModalView();
    loadTiposMovimiento(selTipoModal);

    const fid = parseInt(facturaIdHid?.value || "0", 10) || 0;
    const fnom = (facturaNomInp?.value || "").trim();
    const ferroSel = parseInt(selFerroFiltro?.value || "0", 10) || 0;

    if (fid && fnom) {
      if (facturaIdModal) facturaIdModal.value = String(fid);
      if (facturaNomModal) facturaNomModal.value = fnom;

      cargarFerrosPorFactura(
        fid,
        selFerroModal,
        ferroModalMeta,
        ferroSel ? String(ferroSel) : "",
      );
    }
  });

  facturaNomModal?.addEventListener("input", () => {
    if (isEditCosto) return;
    buscarFacturasModal((facturaNomModal.value || "").trim());
  });

  modalEl?.addEventListener("shown.bs.modal", () => {
    if (isEditCosto) {
      montoModal?.focus();
      try {
        montoModal?.select();
      } catch {}
    } else {
      syncMonedaPorTipoModal();
      facturaNomModal?.focus();
    }
  });

  // ---------------------- Acciones en tabla ----------------------
  tbody?.addEventListener("click", (e) => {
    const btnEdit = e.target.closest(".btnEditarCostoPartida");
    if (btnEdit) {
      const tr = btnEdit.closest("tr");
      if (!tr) return;

      const rowId = parseInt(tr.dataset.rowId || "0", 10) || 0;
      if (!rowId) return;

      isEditCosto = true;
      resetModalView();

      obtenerCostoXHR(rowId, (status, resp) => {
        if (status !== 200) {
          return toast("error", "Error", resp?.message || `HTTP ${status}`);
        }

        if (
          String(resp?.status || "").toLowerCase() !== "success" ||
          !resp.data
        ) {
          return toast(
            "warning",
            "Aviso",
            resp?.message || "No se pudo obtener el registro.",
          );
        }

        const row = resp.data;

        if (hidRowId) hidRowId.value = row.row_id || "";
        if (facturaIdModal) facturaIdModal.value = row.factura_id || "";
        if (facturaNomModal) {
          facturaNomModal.value =
            row.numero_factura || row.numero_operacion || "";
        }
        if (montoModal) montoModal.value = row.monto || "";
        if (comentModal) comentModal.value = row.comentario || "";
        if (selPagadoModal) selPagadoModal.value = String(row.pagado ?? "0");

        const tipoIdSel = row.tipo_movimiento_id || "";
        const tipoNomSel = row.concepto || "";

        loadTiposMovimiento(selTipoModal, tipoIdSel, () => {
          if (
            selTipoModal &&
            tipoIdSel &&
            !selTipoModal.querySelector(`option[value="${String(tipoIdSel)}"]`)
          ) {
            const opt = document.createElement("option");
            opt.value = String(tipoIdSel);
            opt.textContent = tipoNomSel || "Concepto";
            opt.setAttribute("data-moneda", (row.moneda || "").toUpperCase());
            selTipoModal.appendChild(opt);
            selTipoModal.value = String(tipoIdSel);
          }

          if (selMonModal) {
            selMonModal.value = (row.moneda || "").toUpperCase();
          }
        });

        const fid = parseInt(row.factura_id || "0", 10) || 0;
        const cfid = parseInt(row.contenedor_fisico_id || "0", 10) || 0;

        cargarFerrosPorFactura(
          fid,
          selFerroModal,
          ferroModalMeta,
          cfid ? String(cfid) : "",
          () => {
            lockEditFields();

            try {
              const bs = bootstrap.Modal.getOrCreateInstance(modalEl);
              bs.show();
            } catch {}
          },
        );
      });

      return;
    }

    const btnDel = e.target.closest(".btnEliminarCostoPartida");
    if (btnDel) {
      const tr = btnDel.closest("tr");
      if (!tr) return;

      const rowId = parseInt(tr.dataset.rowId || "0", 10) || 0;
      if (!rowId) return;

      const confirmar = () => {
        const oldHtml = btnDel.innerHTML;
        btnDel.disabled = true;
        btnDel.innerHTML =
          '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>…';

        desactivarCostoXHR(rowId, (status, resp) => {
          btnDel.disabled = false;
          btnDel.innerHTML = oldHtml;
          window.feather?.replace?.();

          if (status !== 200) {
            return toast("error", "Error", resp?.message || `HTTP ${status}`);
          }

          const st = String(resp?.status || "").toLowerCase();
          if (st === "success") {
            toast("success", "Desactivado", "El costo fue desactivado.");
            listar(1);
          } else if (st === "warning") {
            toast(
              "warning",
              "Aviso",
              resp?.message || "No se pudo desactivar.",
            );
          } else {
            toast("error", "Error", resp?.message || "No se desactivó.");
          }
        });
      };

      if (window.Swal) {
        Swal.fire({
          title: "¿Eliminar costo?",
          text: "Esta acción no se puede deshacer",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, eliminar",
          cancelButtonText: "Cancelar",
        }).then((res) => {
          if (res.isConfirmed) confirmar();
        });
      } else {
        if (confirm("¿Desactivar este costo?")) confirmar();
      }
      return;
    }
  });

  // ---------------------- Exportación ----------------------
  const btnXlsx = document.getElementById("btnExportarExcelCostosPartida");
  if (btnXlsx && !btnXlsx.dataset.bound) {
    btnXlsx.dataset.bound = "1";
    btnXlsx.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (typeof ExportarTablas === "undefined") {
        return toast(
          "warning",
          "Aviso",
          "No se encontró el módulo de exportación.",
        );
      }

      ExportarTablas.exportar({
        ref: "#tablaCostosPartidaExportar",
        formato: "xlsx",
        nombre: "CostosOperacionPartida.xlsx",
        columnasOcultas: [5],
        soloVisibles: true,
        sheetName: "Costos Partida",
      });
    });
  }

  const btnPdf = document.getElementById("btnExportarPDFCostosPartida");
  if (btnPdf && !btnPdf.dataset.bound) {
    btnPdf.dataset.bound = "1";
    btnPdf.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (typeof ExportarTablas === "undefined") {
        return toast(
          "warning",
          "Aviso",
          "No se encontró el módulo de exportación.",
        );
      }

      ExportarTablas.exportar({
        ref: "#tablaCostosPartidaExportar",
        formato: "pdf",
        nombre: "CostosOperacionPartida.pdf",
        titulo: "Costos Operación por Partida",
        orientacion: "landscape",
        formatoPagina: "letter",
        columnasOcultas: [5],
        soloVisibles: true,
      });
    });
  }
})();
