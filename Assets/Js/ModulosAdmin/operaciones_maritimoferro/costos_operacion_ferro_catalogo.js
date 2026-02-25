// ============================================================================
//  MÓDULO: Costos por Operación (SOLO MF - Operación marítima maestra)
//  Catálogo / Vista
// ============================================================================

(function () {
  "use strict";

  // ---------------------- DOM (vista principal) ----------------------
  const tbody = document.getElementById("tbodyCostosOperacionCombined");
  const inpBuscar = document.getElementById("costosOperacionBuscar");
  const selMonedaFiltro = document.getElementById(
    "costosOperacionFiltroMoneda",
  );
  const selTipoFiltro = document.getElementById("costosOperacionFiltroTipo"); // puede no existir
  const selPerPage = document.getElementById("costosOperacionPerPage");
  const ulPag = document.getElementById("costosOperacionPaginacion");
  const metaTxt = document.getElementById("costosOperacionMeta");

  // Totales / vista (cards)
  const cardTotOperacion = document.getElementById(
    "costosOperacionTotalOperacion",
  );
  const cardTotGeneral = document.getElementById("costosOperacionTotalGeneral");
  const selMonedaVista = document.getElementById("costosOperacionMonedaVista");
  const inpTipoCambio = document.getElementById("costosOperacionTipoCambio");

  // Autocomplete Operación (filtro superior)
  const opIdHid = document.getElementById("costosOperacionFiltroOpId");
  const opNomInp = document.getElementById("costosOperacionFiltroOpNombre");
  const opListBox = document.getElementById(
    "costosOperacionFiltroOpSugerencias",
  );
  const opMeta = document.getElementById("costosOperacionFiltroOpMeta");

  // Contenedor ligado (solo informativo en filtro superior)
  const contFiltroIdHid = document.getElementById(
    "costosOperacionFiltroFerroId",
  ); // OJO: en tu vista se llama "Ferro"
  const contFiltroNomInp = document.getElementById(
    "costosOperacionFiltroFerroNombre",
  );

  // ---------------------- DOM (modal) ----------------------
  const modalEl = document.getElementById("modalCostoOperacion");
  const hidRowId = document.getElementById("row_id");

  const opIdModal = document.getElementById("costosOperacionid");
  const opNomModal = document.getElementById("costosOperacionNombre");
  const listOpsModal = document.getElementById("costosSugerenciasOperaciones");

  const selTipoModal = document.getElementById("costosContenedoresTipoCosto");
  const selMonModal = document.getElementById("costosContenedoresMoneda");
  const montoModal = document.getElementById("costosContenedoresMonto");
  const comentModal = document.getElementById("costosContenedoresComentarios");

  const contIdModal = document.getElementById("costosContenedorContenedorId");
  const contNomModal = document.getElementById(
    "costosContenedorContenedorNombre",
  );

  const selPagadoModal = document.getElementById("costosContenedoresPagado");

  const btnNuevo = document.getElementById("costosOperacionBtnNuevo");
  const btnGuardar = document.getElementById("costosOperacionBtnGuardar"); // recomendado en tu modal (si existe)

  // ---------------------- Estado ----------------------
  let currentPage = 1;
  let perPage = parseInt(selPerPage?.value || "10", 10) || 10;
  let currentXHR = null;
  let debounceId = null;
  let isEditCosto = false;

  // Operación seleccionada (SOLO MF)
  let operacionId = parseInt(opIdHid?.value || "0", 10) || 0;

  // Cache totales
  let totalesDetalleCache = null;
  let abonosDetalleCache = null;

  // ---------------------- Helpers ----------------------
  const safe = (v) => (v === undefined || v === null ? "" : v);
  const prettyMoneda = (m) => String(m || "").toUpperCase();
  const fmtFecha = (s) => (s ? String(s).substring(0, 10) : "");

  const fmtMoney = (n, sym = "$") =>
    sym +
    " " +
    Number(n || 0).toLocaleString("es-MX", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  function renderCargando() {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Cargando resultados…</td></tr>`;
  }

  function renderVacio(msg) {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">${msg || "No hay costos para mostrar."}</td></tr>`;
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
    const n = Number(v);
    return n === 1
      ? `<span class="badge bg-success text-white">Pagado</span>`
      : `<span class="badge bg-danger text-white">Pendiente</span>`;
  }

  function toast(kind, title, text) {
    if (window.Swal) {
      Swal.fire({ icon: kind, title, text });
    } else {
      alert((title ? title + ": " : "") + (text || ""));
    }
  }

  // ---------------------- Modal lock/unlock ----------------------
  function lockEditFields() {
    if (opNomModal) opNomModal.readOnly = true;
    if (selTipoModal) selTipoModal.disabled = true;
    if (listOpsModal) {
      listOpsModal.innerHTML = "";
      listOpsModal.style.display = "none";
    }
  }

  function unlockEditFields() {
    if (opNomModal) opNomModal.readOnly = false;
    if (selTipoModal) selTipoModal.disabled = false;
  }

  // ---------------------- Endpoints ----------------------
  const END = {
    tiposMovimiento: () =>
      `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/tiposMovimiento`,
    listarPaginado: (qs) =>
      `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/listarPaginado?${qs}`,
    buscarOperaciones: (term) =>
      `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/buscarOperaciones?term=${encodeURIComponent(term)}`,
    contenedorLigado: (opId) =>
      `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/contenedorLigado?operacion_id=${encodeURIComponent(opId)}`,
    desactivar: () =>
      `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/desactivarCostoOperacion`,
    guardar: () =>
      `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/guardar`,
  };

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

      // filtro superior
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
        if (cur !== null && cur !== undefined && cur !== "")
          selTipoFiltro.value = cur;
      }

      // modal
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

  function fetchContenedorLigado(opId, cb) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.contenedorLigado(opId), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) return cb(null);

      let resp = {};
      try {
        resp = JSON.parse(xhr.responseText) || {};
      } catch {
        resp = {};
      }
      if (resp.status !== "success" || !resp.data) return cb(null);
      cb(resp.data);
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

  function guardarCostoXHR(payload, onDone) {
    const fd = new FormData();
    Object.keys(payload || {}).forEach((k) =>
      fd.append(k, String(payload[k] ?? "")),
    );

    const xhr = new XMLHttpRequest();
    xhr.open("POST", END.guardar(), true);
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

      const nat = String(r.naturaleza || "").toUpperCase(); // GASTO | ABONO
      const isAbono = nat === "ABONO";

      const montoFmt = fmtMoney(r.monto || 0, "$");
      const montoCls = isAbono
        ? "text-success fw-semibold"
        : "text-danger fw-semibold";
      const montoConSigno = `${isAbono ? "+" : " "}${montoFmt}`;
      const badgeNat = nat
        ? `<span class="badge ${isAbono ? "bg-success-subtle text-success" : "bg-danger-subtle text-danger"} ms-1">${nat}</span>`
        : "";

      const isTransporte = Number(r.tipo_movimiento_id) === 23;

      // dataset (para editar)
      tr.dataset.rowId = r.row_id || "";
      tr.dataset.opId = r.operacion_id || "";
      tr.dataset.opNom = r.numero_operacion || "";
      tr.dataset.tipoId = r.tipo_movimiento_id || "";
      tr.dataset.tipoNom = r.concepto || "";
      tr.dataset.moneda = r.moneda || "";
      tr.dataset.monto = r.monto || "";
      tr.dataset.coment = r.comentario || "";
      tr.dataset.pagado = r.pagado ?? "0";

      tr.innerHTML = `
        <td>${fmtFecha(r.fecha)}</td>
        <td>${safe(r.concepto)}${badgeNat}</td>
        <td>${prettyMoneda(r.moneda)}</td>
        <td class="text-end ${montoCls}">${montoConSigno}</td>
        <td class="text-center">${badgePagado(r.pagado)}</td>
        <td>${safe(r.comentario)}</td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary btnEditarCostoOperacion" title="Ver / Editar">
              <i data-feather="edit-2"></i>
            </button>
            <button type="button" class="btn btn-outline-danger btnEliminarCostoOperacion"
                    title="${isTransporte ? "Eliminar deshabilitado para Transporte" : "Eliminar"}"
                    ${isTransporte ? 'disabled aria-disabled="true"' : ""}>
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    window.feather?.replace?.();
  }

  // ---------------------- Totales ----------------------
  function renderTotales(totalesDetalle) {
    if (totalesDetalle) totalesDetalleCache = totalesDetalle;

    const det = totalesDetalleCache || { operacion: { PESOS: 0, DLLS: 0 } };
    const opPesos = Number(det.operacion?.PESOS || 0);
    const opDlls = Number(det.operacion?.DLLS || 0);

    const vista = (selMonedaVista?.value || "MXN").toUpperCase(); // MXN|USD
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

    const elTotOp = document.getElementById("costosOperacionTotalOperacion");
    const elAbOp = document.getElementById("costosOperacionAbonosOperacion");

    if (elTotOp) elTotOp.textContent = fmt(opCost);
    if (elAbOp) elAbOp.textContent = fmt(opAbono);

    setBadgeValueSimple("costosOperacionBalanceOperacion", opBalance, fmt);

    // General = SOLO operación
    const totalAbonos = opAbono;
    const totalCostos = opCost;
    const totalBalance = totalAbonos - totalCostos;

    const elGen = document.getElementById("costosOperacionTotalGeneral");
    const elGenAb = document.getElementById(
      "costosOperacionTotalAbonosGeneral",
    );
    const elGenCost = document.getElementById(
      "costosOperacionTotalCostosGeneral",
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

    if (!operacionId || operacionId <= 0) {
      renderVacio("Selecciona una operación para ver sus costos.");
      renderTotales({ operacion: { PESOS: 0, DLLS: 0 } });
      renderPaginacion({ page: 1, totalPages: 0 });
      renderMeta({ page: 1, perPage, total: 0 });
      return;
    }

    const buscar = (inpBuscar?.value || "").trim();
    const moneda = (selMonedaFiltro?.value || "").toUpperCase();
    const tipoId = parseInt(selTipoFiltro?.value || "0", 10) || 0;

    if (currentXHR && currentXHR.readyState !== 4) currentXHR.abort();
    renderCargando();

    const params = new URLSearchParams({
      page: String(currentPage),
      perPage: String(perPage),
      buscar,
      moneda,
      tipo: String(tipoId),
      operacion_id: String(operacionId),
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

      // safety: si cambió la pag por totalPages
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

      // caches
      totalesDetalleCache = totalesDetalle;
      abonosDetalleCache = abonosDetalle;

      // cards
      renderTotales(totalesDetalleCache);
      const { opCost, opAbono, fmt } = computeViewTotals(
        totalesDetalleCache,
        abonosDetalleCache,
      );
      renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
    };
    currentXHR.send();
  }
  window.listarCostosOperacion = listar;

  // ---------------------- Autocomplete Operación (vista principal) ----------------------
  function buscarOps(term) {
    if (!opListBox) return;

    if (!term || term.length < 2) {
      opListBox.style.display = "none";
      opListBox.innerHTML = "";
      if (opMeta) opMeta.textContent = "";
      return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.buscarOperaciones(term), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) {
        opListBox.style.display = "none";
        opListBox.innerHTML = "";
        if (opMeta) opMeta.textContent = "";
        return;
      }

      let rows = [];
      try {
        rows = JSON.parse(xhr.responseText) || [];
      } catch {
        rows = [];
      }

      if (!Array.isArray(rows) || rows.length === 0) {
        opListBox.style.display = "none";
        opListBox.innerHTML = "";
        if (opMeta) opMeta.textContent = "Sin resultados";
        return;
      }

      let html = "";
      rows.forEach((r) => {
        const id = r.id;
        const nom = r.numero_operacion || "";
        const cli = r.cliente || "";
        html += `
          <button type="button" class="list-group-item list-group-item-action"
                  data-id="${id}" data-nom="${nom}" data-cli="${cli}">
            ${nom}${cli ? " - " + cli : ""}
          </button>`;
      });

      opListBox.innerHTML = html;
      opListBox.style.display = "block";

      opListBox.querySelectorAll("button.list-group-item").forEach((btn) => {
        btn.addEventListener("click", () => {
          const id = parseInt(btn.dataset.id || "0", 10) || 0;

          if (opIdHid) opIdHid.value = String(id);
          if (opNomInp) opNomInp.value = btn.textContent.trim();
          operacionId = id;

          opListBox.innerHTML = "";
          opListBox.style.display = "none";

          // contenedor ligado informativo
          fetchContenedorLigado(operacionId, (data) => {
            if (!data) {
              if (contFiltroNomInp) contFiltroNomInp.value = "";
              if (contFiltroIdHid) contFiltroIdHid.value = "";
            } else {
              if (contFiltroNomInp) contFiltroNomInp.value = data.numero || "";
              if (contFiltroIdHid)
                contFiltroIdHid.value = String(
                  data.ids?.contenedor_maritimo_id || "",
                );
            }
          });

          // carga tipos (filtro + modal)
          loadTiposMovimiento(selTipoModal);

          listar(1);
        });
      });
    };
    xhr.send();
  }

  opNomInp?.addEventListener("input", () => {
    const term = (opNomInp.value || "").trim();

    if (opIdHid) opIdHid.value = "";
    operacionId = 0;

    if (contFiltroNomInp) contFiltroNomInp.value = "";
    if (contFiltroIdHid) contFiltroIdHid.value = "";

    buscarOps(term);
  });

  document.addEventListener("click", (e) => {
    if (!opListBox) return;
    if (!opListBox.contains(e.target) && e.target !== opNomInp) {
      opListBox.style.display = "none";
    }
  });

  // ---------------------- Filtros / paginación ----------------------
  document.addEventListener("DOMContentLoaded", () => {
    if (perPage < 1) perPage = 10;

    // Cargar tipos siempre (para poblar filtro superior aunque no haya op seleccionada)
    loadTiposMovimiento(selTipoModal);

    // Si ya venía una operación precargada, pinta contenedor y lista
    if (operacionId > 0) {
      fetchContenedorLigado(operacionId, (data) => {
        if (data) {
          if (contFiltroNomInp) contFiltroNomInp.value = data.numero || "";
          if (contFiltroIdHid)
            contFiltroIdHid.value = String(
              data.ids?.contenedor_maritimo_id || "",
            );
        }
      });
      listar(1);
    } else {
      renderVacio("Selecciona una operación para ver sus costos.");
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

  selMonedaVista?.addEventListener("change", () => {
    renderTotales(totalesDetalleCache);
    const { opCost, opAbono, fmt } = computeViewTotals(
      totalesDetalleCache,
      abonosDetalleCache,
    );
    renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
  });

  inpTipoCambio?.addEventListener("input", () => {
    renderTotales(totalesDetalleCache);
    const { opCost, opAbono, fmt } = computeViewTotals(
      totalesDetalleCache,
      abonosDetalleCache,
    );
    renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
  });

  // ---------------------- Modal: moneda depende del tipo ----------------------
  function syncMonedaPorTipoModal() {
    if (isEditCosto) return;
    if (!selTipoModal) return;
    const opt = selTipoModal.selectedOptions?.[0];
    const m = opt ? (opt.getAttribute("data-moneda") || "").toUpperCase() : "";
    if (selMonModal) selMonModal.value = m === "PESOS" || m === "DLLS" ? m : "";
  }
  selTipoModal?.addEventListener("change", syncMonedaPorTipoModal);

  function resetModalView() {
    if (hidRowId) hidRowId.value = "";
    if (opIdModal) opIdModal.value = "";
    if (opNomModal) opNomModal.value = "";

    if (selTipoModal) selTipoModal.value = "";
    if (selMonModal) selMonModal.value = "";
    if (montoModal) montoModal.value = "";
    if (comentModal) comentModal.value = "";

    if (contIdModal) contIdModal.value = "";
    if (contNomModal) contNomModal.value = "";

    if (listOpsModal) {
      listOpsModal.innerHTML = "";
      listOpsModal.style.display = "none";
    }

    if (selPagadoModal) selPagadoModal.value = "0";
  }

  // Abrir modal "Nuevo"
  btnNuevo?.addEventListener("click", () => {
    isEditCosto = false;
    unlockEditFields();
    resetModalView();

    // Si ya hay operación seleccionada, sembrarla
    const fid = parseInt(opIdHid?.value || "0", 10) || 0;
    const fnom = (opNomInp?.value || "").trim();

    loadTiposMovimiento(selTipoModal);

    if (fid && fnom) {
      if (opIdModal) opIdModal.value = String(fid);
      if (opNomModal) opNomModal.value = fnom;

      fetchContenedorLigado(fid, (data) => {
        if (data) {
          if (contNomModal) contNomModal.value = data.numero || "";
          if (contIdModal)
            contIdModal.value = String(data.ids?.contenedor_maritimo_id || "");
        }
      });
    }
  });

  // Autocomplete operación dentro del modal
  opNomModal?.addEventListener("input", () => {
    if (isEditCosto) return;
    if (!listOpsModal) return;

    const term = (opNomModal.value || "").trim();
    if (!term || term.length < 2) {
      listOpsModal.style.display = "none";
      listOpsModal.innerHTML = "";
      return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("GET", END.buscarOperaciones(term), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status !== 200) {
        listOpsModal.style.display = "none";
        listOpsModal.innerHTML = "";
        return;
      }

      let rows = [];
      try {
        rows = JSON.parse(xhr.responseText) || [];
      } catch {
        rows = [];
      }

      if (!Array.isArray(rows) || rows.length === 0) {
        listOpsModal.style.display = "none";
        listOpsModal.innerHTML = "";
        return;
      }

      let html = "";
      rows.forEach((r) => {
        const id = r.id;
        const nom = r.numero_operacion || "";
        const cli = r.cliente || "";
        html += `
          <button type="button" class="list-group-item list-group-item-action"
                  data-id="${id}" data-nom="${nom}" data-cli="${cli}">
            ${nom}${cli ? " - " + cli : ""}
          </button>`;
      });

      listOpsModal.innerHTML = html;
      listOpsModal.style.display = "block";

      listOpsModal.querySelectorAll("button.list-group-item").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();

          const id = parseInt(btn.dataset.id || "0", 10) || 0;
          const nom = btn.dataset.nom || "";
          const cli = btn.dataset.cli || "";

          if (opIdModal) opIdModal.value = String(id);
          if (opNomModal) opNomModal.value = `${nom}${cli ? " - " + cli : ""}`;

          loadTiposMovimiento(selTipoModal);

          fetchContenedorLigado(id, (data) => {
            if (data) {
              if (contNomModal) contNomModal.value = data.numero || "";
              if (contIdModal)
                contIdModal.value = String(
                  data.ids?.contenedor_maritimo_id || "",
                );
            } else {
              if (contNomModal) contNomModal.value = "";
              if (contIdModal) contIdModal.value = "";
            }
          });

          listOpsModal.innerHTML = "";
          listOpsModal.style.display = "none";
        });
      });
    };
    xhr.send();
  });

  modalEl?.addEventListener("shown.bs.modal", () => {
    if (isEditCosto) {
      montoModal?.focus();
      try {
        montoModal?.select();
      } catch {}
    } else {
      syncMonedaPorTipoModal();
      opNomModal?.focus();
    }
  });

  // ---------------------- Guardar (crear/editar) ----------------------
  function validarModal() {
    const rowId = parseInt(hidRowId?.value || "0", 10) || 0;
    const opId = parseInt(opIdModal?.value || "0", 10) || 0;
    const tipoId = parseInt(selTipoModal?.value || "0", 10) || 0;

    const monto = parseFloat(montoModal?.value || "0") || 0;
    const comentario = (comentModal?.value || "").trim();
    const pagado = parseInt(selPagadoModal?.value || "0", 10) === 1 ? 1 : 0;

    if (rowId <= 0 && opId <= 0) return { ok: false, msg: "Falta operación." };
    if (tipoId <= 0)
      return { ok: false, msg: "Selecciona un tipo de movimiento." };
    if (!(monto > 0)) return { ok: false, msg: "Monto inválido." };

    return {
      ok: true,
      data: {
        row_id: rowId,
        operacion_id: opId,
        tipo_movimiento_id: tipoId,
        monto: monto,
        comentario: comentario,
        costosContenedoresPagado: pagado, // 👈 coincide con tu controlador actual
      },
    };
  }

  function onGuardarClick() {
    const v = validarModal();
    if (!v.ok) return toast("warning", "Validación", v.msg);

    const old = btnGuardar ? btnGuardar.innerHTML : "";
    if (btnGuardar) {
      btnGuardar.disabled = true;
      btnGuardar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Guardando…';
    }

    guardarCostoXHR(v.data, (status, resp) => {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = old;
      }

      if (status !== 200) {
        return toast("error", "Error", resp?.message || `HTTP ${status}`);
      }

      const st = String(resp?.status || "").toLowerCase();
      if (st === "success") {
        toast("success", "OK", resp?.message || "Guardado");
        // cerrar modal
        try {
          const bs = bootstrap.Modal.getOrCreateInstance(modalEl);
          bs.hide();
        } catch {}
        // refrescar
        listar(1);
      } else if (st === "warning") {
        toast("warning", "Aviso", resp?.message || "No se pudo guardar.");
      } else {
        toast("error", "Error", resp?.message || "No se pudo guardar.");
      }
    });
  }

  if (btnGuardar) btnGuardar.addEventListener("click", onGuardarClick);

  // ---------------------- Acciones en tabla ----------------------
  tbody?.addEventListener("click", (e) => {
    // EDITAR
    const btnEdit = e.target.closest(".btnEditarCostoOperacion");
    if (btnEdit) {
      const tr = btnEdit.closest("tr");
      if (!tr) return;

      isEditCosto = true;
      resetModalView();

      if (hidRowId) hidRowId.value = tr.dataset.rowId || "";
      if (opIdModal) opIdModal.value = tr.dataset.opId || "";
      if (opNomModal) opNomModal.value = tr.dataset.opNom || "";
      if (montoModal) montoModal.value = tr.dataset.monto || "";
      if (comentModal) comentModal.value = tr.dataset.coment || "";
      if (selPagadoModal)
        selPagadoModal.value = String(tr.dataset.pagado ?? "0");

      const tipoIdSel = tr.dataset.tipoId || "";
      const tipoNomSel = tr.dataset.tipoNom || "";

      loadTiposMovimiento(selTipoModal, tipoIdSel, () => {
        // fallback si el tipo no vino en catálogo
        if (
          selTipoModal &&
          tipoIdSel &&
          !selTipoModal.querySelector(`option[value="${String(tipoIdSel)}"]`)
        ) {
          const opt = document.createElement("option");
          opt.value = String(tipoIdSel);
          opt.textContent = tipoNomSel || "Concepto";
          opt.setAttribute(
            "data-moneda",
            (tr.dataset.moneda || "").toUpperCase(),
          );
          selTipoModal.appendChild(opt);
          selTipoModal.value = String(tipoIdSel);
        }

        if (selMonModal)
          selMonModal.value = (tr.dataset.moneda || "").toUpperCase();
        lockEditFields();

        try {
          const bs = bootstrap.Modal.getOrCreateInstance(modalEl);
          bs.show();
        } catch {}
      });

      const opId = parseInt(tr.dataset.opId || "0", 10) || 0;
      if (opId) {
        fetchContenedorLigado(opId, (data) => {
          if (data) {
            if (contNomModal) contNomModal.value = data.numero || "";
            if (contIdModal)
              contIdModal.value = String(
                data.ids?.contenedor_maritimo_id || "",
              );
          }
        });
      }

      return;
    }

    // ELIMINAR (desactivar)
    const btnDel = e.target.closest(".btnEliminarCostoOperacion");
    if (btnDel) {
      const tr = btnDel.closest("tr");
      if (!tr) return;

      const tipoId = parseInt(tr.dataset.tipoId || "0", 10) || 0;
      if (tipoId === 23) {
        return toast(
          "info",
          "No permitido",
          "Los costos de tipo Transporte no pueden eliminarse.",
        );
      }

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
  document
    .getElementById("btnExportarExcelCostosOperacion")
    ?.addEventListener("click", () => {
      ExportarTablas.exportar({
        ref: "tablaCostosOperacionExportar",
        formato: "xlsx",
        nombre: "CostosOperacion_MF.xlsx",
        columnasOcultas: [5], // ajusta si cambió tu tabla
        soloVisibles: true,
        sheetName: "Costos MF",
      });
    });

  document
    .getElementById("btnExportarPDFCostosOperacion")
    ?.addEventListener("click", () => {
      ExportarTablas.exportar({
        ref: "#tablaCostosOperacionExportar",
        formato: "pdf",
        nombre: "CostosOperacion_MF.pdf",
        titulo: "Costos Operación MF",
        orientacion: "landscape",
        formatoPagina: "letter",
        columnasOcultas: [5], // ajusta si cambió tu tabla
        soloVisibles: true,
      });
    });
})();
