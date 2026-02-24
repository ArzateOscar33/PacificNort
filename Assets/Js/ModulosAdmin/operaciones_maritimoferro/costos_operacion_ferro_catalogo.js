// ============================================================================
//  MÓDULO: Costos por Operación (SOLO MF - Operación marítima maestra)
//  — Autocomplete operación (MF).
//  — Rellena contenedor ligado por operación.
//  — Lista costos/abonos de la operación seleccionada.
//  — Carga tipos de movimiento (sin fuente).
//  — Editar/Eliminar: usa desactivar (soft delete). Transporte (id 23) bloqueado.
// ============================================================================

(function () {
  "use strict";

  // ---------------------- DOM (vista principal) ----------------------
  const tbody = document.getElementById("tbodyCostosOperacionCombined");
  const inpBuscar = document.getElementById("costosOperacionBuscar");
  const selMonedaFiltro = document.getElementById(
    "costosOperacionFiltroMoneda",
  );
  const selTipoFiltro = document.getElementById("costosOperacionFiltroTipo"); // podría no existir
  const selPerPage = document.getElementById("costosOperacionPerPage");
  const ulPag = document.getElementById("costosOperacionPaginacion");
  const metaTxt = document.getElementById("costosOperacionMeta");

  // Totales / vista
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

  // Contenedor ligado (filtro)
  const contFiltroIdHid = document.getElementById(
    "costosOperacionFiltroFerroId",
  );
  const contFiltroNomInp = document.getElementById(
    "costosOperacionFiltroFerroNombre",
  );
  const contFiltroList = document.getElementById(
    "costosOperacionFiltroFerroSugerencias",
  ); // no se usa aquí

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

  // Botón Nuevo
  const btnNuevo = document.getElementById("costosOperacionBtnNuevo");

  // ---------------------- Estado ----------------------
  let currentPage = 1;
  let perPage = parseInt(selPerPage?.value || "10", 10);
  let currentXHR = null;
  let debounceId = null;
  let isEditCosto = false;

  // Operación seleccionada (SOLO MF)
  let operacionId = parseInt(opIdHid?.value || "0", 10) || 0;

  // Cache para totales
  let totalesDetalleCache = null; // { operacion:{PESOS, DLLS} }
  let abonosDetalleCache = null; // { operacion:{PESOS, DLLS} }

  // ---------------------- Helpers generales ----------------------
  const safe = (v) => (v === undefined || v === null ? "" : v);
  const fmtMoney = (n, sym = "$") =>
    sym +
    " " +
    Number(n || 0).toLocaleString("es-MX", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  const prettyMoneda = (m) => String(m || "").toUpperCase();
  const fmtFecha = (s) => (s ? String(s).substring(0, 10) : "");

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
    if (!sel.querySelector('option[value="PESOS"]'))
      sel.insertAdjacentHTML(
        "beforeend",
        '<option value="PESOS">PESOS</option>',
      );
    if (!sel.querySelector('option[value="DLLS"]'))
      sel.insertAdjacentHTML("beforeend", '<option value="DLLS">DLLS</option>');
  }
  ensureMonedaOptions(selMonModal);

  // ---------------------- Modal: lock/unlock ----------------------
  function lockEditFields() {
    opNomModal && (opNomModal.readOnly = true);
    selTipoModal && (selTipoModal.disabled = true);
    if (listOpsModal) {
      listOpsModal.innerHTML = "";
      listOpsModal.style.display = "none";
    }
  }
  function unlockEditFields() {
    opNomModal && (opNomModal.readOnly = false);
    selTipoModal && (selTipoModal.disabled = false);
  }

  // ---------------------- Endpoints (SOLO MF) ----------------------
  function loadTiposMovimiento(selectEl, selectedId = null, done = null) {
    if (!selectEl) return;
    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/tiposMovimiento`; // ✅ sin fuente

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
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

      // llenar filtro superior si existe
      if (selTipoFiltro) {
        const cur = selTipoFiltro.value;
        selTipoFiltro.innerHTML =
          `<option value="0">Todos los conceptos</option>` +
          rows
            .map(
              (t) =>
                `<option value="${t.id_tipo_movimiento}">${t.nombre}</option>`,
            )
            .join("");
        if (cur) selTipoFiltro.value = cur;
      }

      // llenar modal
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
    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/contenedorLigado?operacion_id=${encodeURIComponent(opId)}`; // ✅ sin fuente
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) {
        cb(null);
        return;
      }
      let resp = {};
      try {
        resp = JSON.parse(xhr.responseText) || {};
      } catch {
        resp = {};
      }
      if (resp.status !== "success" || !resp.data) {
        cb(null);
        return;
      }
      cb(resp.data);
    };
    xhr.send();
  }

  function desactivarCostoXHR(id, onDone) {
    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/desactivarCostoOperacion`; // ✅ sin fuente
    const fd = new FormData();
    fd.append("id", String(id));

    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
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

      const nat = String(r.naturaleza || "").toUpperCase(); // "GASTO" | "ABONO"
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

      // dataset para modal
      tr.dataset.rowId = r.row_id || "";
      tr.dataset.opId = r.operacion_id || "";
      tr.dataset.opNom = r.numero_operacion || "";
      tr.dataset.tipoId = r.tipo_movimiento_id || "";
      tr.dataset.tipoNom = r.concepto || "";
      tr.dataset.moneda = r.moneda || "";
      tr.dataset.monto = r.monto || "";
      tr.dataset.coment = r.comentario || "";

      tr.innerHTML = `
        <td>${fmtFecha(r.fecha)}</td>
        <td>${safe(r.concepto || "")}${badgeNat}</td>
        <td>${prettyMoneda(r.moneda || "")}</td>
        <td class="text-end ${montoCls}">${montoConSigno}</td>
        <td>${safe(r.comentario || "")}</td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary btnEditarCostoOperacion" title="Ver / Editar">
              <i data-feather="edit-2"></i>
            </button>
            <button class="btn btn-outline-danger btnEliminarCostoOperacion"
                    title="${isTransporte ? "Eliminar deshabilitado para Transporte" : "Eliminar"}"
                    ${isTransporte ? 'disabled aria-disabled="true"' : ""}>
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });

    window.feather?.replace?.();
  }

  // ---------------------- Totales ----------------------
  function renderTotales(totalesDetalle) {
    if (totalesDetalle) totalesDetalleCache = totalesDetalle;

    const det = totalesDetalleCache || { operacion: { PESOS: 0, DLLS: 0 } };
    const opPesos = Number(det.operacion.PESOS || 0);
    const opDlls = Number(det.operacion.DLLS || 0);

    const vista = (selMonedaVista?.value || "MXN").toUpperCase(); // MXN|USD
    let tc = parseFloat(inpTipoCambio?.value || "0");
    if (!Number.isFinite(tc) || tc <= 0) tc = 1;

    let symbol = "$",
      totalOpConv = 0;
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

    const opPesosC = Number(c.operacion?.PESOS || 0),
      opDllsC = Number(c.operacion?.DLLS || 0);
    const opPesosA = Number(a.operacion?.PESOS || 0),
      opDllsA = Number(a.operacion?.DLLS || 0);

    let opCost = 0,
      opAbono = 0,
      symbol = "$";
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
      Number(n).toLocaleString("es-MX", {
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

    let start = Math.max(1, page - 2),
      end = Math.min(totalPages, start + 4);
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
        const p = parseInt(a.dataset.page, 10);
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

  // ---------------------- Listar (SOLO MF) ----------------------
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

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/listarPaginado?${params.toString()}`;
    currentXHR = new XMLHttpRequest();
    currentXHR.open("GET", url, true);
    currentXHR.send();
    currentXHR.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) {
        console.error(this.responseText);
        return;
      }

      let payload;
      try {
        payload = JSON.parse(this.responseText);
      } catch {
        return;
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
      renderTotales(totalesDetalle);

      abonosDetalleCache = abonosDetalle;
      const { opCost, opAbono, fmt } = computeViewTotals(
        totalesDetalleCache,
        abonosDetalleCache,
      );
      renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
    };
  }
  window.listarCostosOperacion = listar;

  // ---------------------- Autocomplete Operación (vista principal) ----------------------
  function buscarOps(term) {
    if (!opListBox) return;
    if (!term || term.length < 2) {
      opListBox.style.display = "none";
      opListBox.innerHTML = "";
      opMeta && (opMeta.textContent = "");
      return;
    }

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/buscarOperaciones?term=${encodeURIComponent(term)}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.send();
    xhr.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) {
        opListBox.style.display = "none";
        opListBox.innerHTML = "";
        opMeta && (opMeta.textContent = "");
        return;
      }

      let rows = [];
      try {
        rows = JSON.parse(this.responseText) || [];
      } catch {
        rows = [];
      }

      if (!Array.isArray(rows) || rows.length === 0) {
        opListBox.style.display = "none";
        opListBox.innerHTML = "";
        opMeta && (opMeta.textContent = "Sin resultados");
        return;
      }

      // Formato: NUMERO_OPERACION - CLIENTE
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
      opMeta && (opMeta.textContent = `Resultados: ${rows.length}`);

      opListBox.querySelectorAll("button.list-group-item").forEach((btn) => {
        btn.addEventListener("click", () => {
          const id = parseInt(btn.dataset.id || "0", 10) || 0;

          // Seleccionar operación
          if (opIdHid) opIdHid.value = String(id);
          if (opNomInp) opNomInp.value = btn.textContent.trim(); // muestra "OP - CLIENTE"
          operacionId = id;

          // Cerrar lista
          opListBox.innerHTML = "";
          opListBox.style.display = "none";

          // Contenedor ligado (filtro)
          fetchContenedorLigado(operacionId, (data) => {
            if (!data) {
              if (contFiltroNomInp) contFiltroNomInp.value = "";
              if (contFiltroIdHid) contFiltroIdHid.value = "";
            } else {
              if (contFiltroNomInp) contFiltroNomInp.value = data.numero || "";
              if (contFiltroIdHid) {
                const ids = data.ids || {};
                contFiltroIdHid.value = String(
                  ids.contenedor_maritimo_id || "",
                );
              }
            }
          });

          // Tipos de movimiento (para modal)
          loadTiposMovimiento(selTipoModal);

          // Listar
          listar(1);
        });
      });
    };
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

  // ---------------------- Eventos filtros/paginación ----------------------
  document.addEventListener("DOMContentLoaded", () => {
    if (!perPage || perPage < 1) perPage = 10;
    if (operacionId > 0) loadTiposMovimiento(selTipoModal);
  });

  selPerPage?.addEventListener("change", () => {
    perPage = parseInt(selPerPage.value || "10", 10);
    if (!perPage || perPage < 1) perPage = 10;
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
  }

  // Abrir modal en “nuevo”
  btnNuevo?.addEventListener("click", () => {
    isEditCosto = false;
    unlockEditFields();
    resetModalView();

    // Si ya hay operación seleccionada arriba, sembrarla en modal
    const fid = parseInt(opIdHid?.value || "0", 10) || 0;
    const fnom = (opNomInp?.value || "").trim();
    if (fid && fnom) {
      if (opIdModal) opIdModal.value = String(fid);
      if (opNomModal) opNomModal.value = fnom;

      loadTiposMovimiento(selTipoModal);
      fetchContenedorLigado(fid, (data) => {
        if (data) {
          if (contNomModal) contNomModal.value = data.numero || "";
          if (contIdModal)
            contIdModal.value = String(data.ids?.contenedor_maritimo_id || "");
        }
      });
    } else {
      loadTiposMovimiento(selTipoModal);
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

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/buscarOperaciones?term=${encodeURIComponent(term)}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.send();
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

          // Contenedor ligado (solo mostrar)
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

  // ---------------------- Acciones en tabla ----------------------
  tbody?.addEventListener("click", (e) => {
    // EDITAR (abre modal con datos)
    const btnEdit = e.target.closest(".btnEditarCostoOperacion");
    if (btnEdit) {
      const tr = btnEdit.closest("tr");
      if (!tr) return;

      isEditCosto = true;
      resetModalView();

      const tipoIdSel = tr.dataset.tipoId || "";
      const tipoNomSel = tr.dataset.tipoNom || "";

      loadTiposMovimiento(selTipoModal, tipoIdSel, () => {
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

        const bs = bootstrap.Modal.getOrCreateInstance(modalEl);
        bs.show();
        setTimeout(() => {
          montoModal?.focus();
          try {
            montoModal?.select();
          } catch {}
        }, 150);
      });

      if (hidRowId) hidRowId.value = tr.dataset.rowId || "";
      if (opIdModal) opIdModal.value = tr.dataset.opId || "";
      if (opNomModal) opNomModal.value = tr.dataset.opNom || "";
      if (montoModal) montoModal.value = tr.dataset.monto || "";
      if (comentModal) comentModal.value = tr.dataset.coment || "";

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

      const tipoId = parseInt(tr.dataset.tipoId || "0", 10);
      if (tipoId === 23) {
        if (window.Swal) {
          Swal.fire({
            icon: "info",
            title: "No permitido",
            text: "Los costos de tipo Transporte no pueden eliminarse.",
            confirmButtonText: "Entendido",
          });
        } else {
          alert("Los costos de tipo Transporte no pueden eliminarse.");
        }
        return;
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

          const st = String(resp?.status || "").toLowerCase();
          if (status !== 200) {
            if (window.Swal)
              Swal.fire({
                icon: "error",
                title: "Error",
                text: resp?.message || `HTTP ${status}`,
              });
            else alert(`Error: ${resp?.message || `HTTP ${status}`}`);
            return;
          }

          if (st === "success") {
            if (window.Swal)
              Swal.fire({
                icon: "success",
                title: "Desactivado",
                text: "El costo fue desactivado.",
              });
            if (typeof window.listarCostosOperacion === "function")
              window.listarCostosOperacion(1);
          } else if (st === "warning") {
            if (window.Swal)
              Swal.fire({
                icon: "warning",
                title: "Aviso",
                text: resp?.message || "No se pudo desactivar.",
              });
            else alert(resp?.message || "No se pudo desactivar.");
          } else {
            if (window.Swal)
              Swal.fire({
                icon: "error",
                title: "Error",
                text: resp?.message || "No se desactivó.",
              });
            else alert(resp?.message || "No se desactivó.");
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
        nombre: `CostosOperacion_MF.xlsx`,
        columnasOcultas: [5],
        soloVisibles: true,
        sheetName: `Costos MF`,
      });
    });

  document
    .getElementById("btnExportarPDFCostosOperacion")
    ?.addEventListener("click", () => {
      ExportarTablas.exportar({
        ref: "#tablaCostosOperacionExportar",
        formato: "pdf",
        nombre: `CostosOperacion_MF.pdf`,
        titulo: `Costos Operación MF`,
        orientacion: "landscape",
        formatoPagina: "letter",
        columnasOcultas: [5],
        soloVisibles: true,
      });
    });
})();
