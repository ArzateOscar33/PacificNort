 // =======================================================
//  Módulo: Costos por Operación FERRO (LISTAR)
//  Archivo sugerido: assets/js/modulosAdmin/operaciones_maritimoferro/costos_operacion_ferro_catalogo.js
// =======================================================
(function () {
  "use strict";

  // ---------- Refs del DOM ----------
  const tbody = document.getElementById("tbodyCostosOperacionCombined");
  const inpBuscar = document.getElementById("costosOperacionBuscar");
  const selMonedaFiltro = document.getElementById("costosOperacionFiltroMoneda");
  const selTipoFiltro = document.getElementById("costosOperacionFiltroTipo");
  const selPerPage = document.getElementById("costosOperacionPerPage");
  const ulPag = document.getElementById("costosOperacionPaginacion");
  const metaTxt = document.getElementById("costosOperacionMeta");

  // Tarjetas totales + controles de vista
  const cardTotOperacion = document.getElementById("costosOperacionTotalOperacion");
  const cardTotGeneral   = document.getElementById("costosOperacionTotalGeneral");
  const selMonedaVista   = document.getElementById("costosOperacionMonedaVista"); // MXN | USD
  const inpTipoCambio    = document.getElementById("costosOperacionTipoCambio");  // MXN por 1 USD

  // Autocomplete Operación (filtro superior)
  const opIdHid   = document.getElementById("costosOperacionFiltroOpId");
  const opNomInp  = document.getElementById("costosOperacionFiltroOpNombre");
  const opListBox = document.getElementById("costosOperacionFiltroOpSugerencias");
  const opMeta    = document.getElementById("costosOperacionFiltroOpMeta");

  // ---------- Estado ----------
  let currentPage = 1;
  let perPage = parseInt(selPerPage?.value || "10", 10);
  let currentXHR = null;
  let debounceId = null;

  // Operación FERRO elegida
  let operacionFerroId = parseInt(opIdHid?.value || "0", 10) || 0;

  // Cache para conversiones de totales
  let totalesDetalleCache = null;  // { operacion:{PESOS, DLLS} }
  let abonosDetalleCache  = null;  // { operacion:{PESOS, DLLS} }

  // ---------- Helpers ----------
  const safe = (v) => (v === undefined || v === null) ? "" : v;
  const fmtMoney = (n, sym = "$") => sym + " " + Number(n || 0).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const moneyMx = (n) => fmtMoney(n, "$");
  const prettyMoneda = (m) => String(m || "").toUpperCase();
  const fmtFecha = (s) => s ? String(s).substring(0, 10) : "";

  function renderCargando() {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando resultados…</td></tr>`;
  }
  function renderVacio() {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay costos para mostrar.</td></tr>`;
  }

  // ---------- Render: tabla ----------
  function renderTabla(rows) {
    if (!tbody) return;
    tbody.innerHTML = "";
    if (!Array.isArray(rows) || rows.length === 0) { renderVacio(); return; }

    rows.forEach(r => {
      const tr = document.createElement("tr");

      const nat = String(r.naturaleza || "").toUpperCase(); // "GASTO" | "ABONO"
      const isAbono = (nat === "ABONO");
      const montoFmt = moneyMx(r.monto || 0);
      const montoCls = isAbono ? "text-success fw-semibold" : "text-danger fw-semibold";
      const montoConSigno = `${isAbono ? "+" : " "}${montoFmt}`;
      const badgeNat = nat
        ? `<span class="badge ${isAbono ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} ms-1">${nat}</span>`
        : "";

      // dataset para edición
      tr.dataset.rowId   = r.row_id || "";
      tr.dataset.opId    = r.operacion_ferro_id || r.operacion_id || "";
      tr.dataset.opNom   = r.numero_operacion || "";
      tr.dataset.tipoId  = r.tipo_movimiento_id || "";
      tr.dataset.moneda  = r.moneda || "";
      tr.dataset.monto   = r.monto || "";
      tr.dataset.coment  = r.comentario || "";

      tr.innerHTML = `
        <td>${fmtFecha(r.fecha)}</td>
        <td>${safe(r.concepto || "")}${badgeNat}</td>
        <td>${prettyMoneda(r.moneda || "")}</td>
        <td class="text-end ${montoCls}">${montoConSigno}</td>
        <td>${safe(r.comentario || "")}</td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary btnEditarCostoOperacion" title="Editar">
              <i data-feather="edit-2"></i>
            </button>
            <button class="btn btn-outline-danger btnEliminarCostoOperacion" title="Eliminar">
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
    window.feather?.replace?.();
  }

  // ---------- Totales + Conversión ----------
  function renderTotales(totalesDetalle) {
    if (totalesDetalle) totalesDetalleCache = totalesDetalle;

    const det = totalesDetalleCache || { operacion: { PESOS: 0, DLLS: 0 } };
    const opPesos = Number(det.operacion.PESOS || 0);
    const opDlls  = Number(det.operacion.DLLS  || 0);

    const vista = (selMonedaVista?.value || "MXN").toUpperCase(); // MXN|USD
    let tc = parseFloat(inpTipoCambio?.value || "0"); if (!Number.isFinite(tc) || tc <= 0) tc = 1;

    let symbol = "$", totalOpConv = 0;
    if (vista === "MXN") { symbol = "$";   totalOpConv = opPesos + (opDlls * tc); }
    else                 { symbol = "US$"; totalOpConv = opDlls + (opPesos / tc); }

    if (cardTotOperacion) cardTotOperacion.textContent = fmtMoney(totalOpConv, symbol);
    if (cardTotGeneral)   cardTotGeneral.textContent   = fmtMoney(totalOpConv, symbol);
  }

  function computeViewTotals(detCostos, detAbonos) {
    const vista = (selMonedaVista?.value || "MXN").toUpperCase();
    let tc = parseFloat(inpTipoCambio?.value || "0"); if (!Number.isFinite(tc) || tc <= 0) tc = 1;

    const c = detCostos || { operacion: { PESOS: 0, DLLS: 0 } };
    const a = detAbonos || { operacion: { PESOS: 0, DLLS: 0 } };

    const opPesosC = Number(c.operacion?.PESOS || 0), opDllsC = Number(c.operacion?.DLLS || 0);
    const opPesosA = Number(a.operacion?.PESOS || 0), opDllsA = Number(a.operacion?.DLLS || 0);

    let opCost = 0, opAbono = 0, symbol = "$";
    if (vista === "MXN") {
      symbol = "$";
      opCost  = opPesosC + (opDllsC * tc);
      opAbono = opPesosA + (opDllsA * tc);
    } else {
      symbol = "US$";
      opCost  = opDllsC + (opPesosC / tc);
      opAbono = opDllsA + (opPesosA / tc);
    }

    const fmt = (n) => symbol + " " + Number(n).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return { opCost, opAbono, fmt };
  }

  // ---------- Paginación + meta ----------
  function renderPaginacion(meta) {
    if (!ulPag) return;
    const page = parseInt(meta.page || 1, 10);
    const totalPages = parseInt(meta.totalPages || 1, 10);
    if (totalPages <= 1) { ulPag.innerHTML = ""; return; }

    let start = Math.max(1, page - 2), end = Math.min(totalPages, start + 4); start = Math.max(1, end - 4);
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

    ulPag.querySelectorAll("a.page-link").forEach(a => {
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
    const ini = total === 0 ? 0 : ((page - 1) * perPg) + 1;
    const fin = Math.min(page * perPg, total);
    metaTxt.textContent = `Mostrando ${ini}–${fin} de ${total}`;
  }

  // ---------- Listar ----------
  function listar(page = 1) {
    currentPage = page;

    // Sin operación seleccionada: limpio
    if (!operacionFerroId || operacionFerroId <= 0) {
      renderVacio();
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
      operacion_ferro_id: String(operacionFerroId),
      solo_activos: "1",
    });

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/listarPaginado?${params.toString()}`;
    currentXHR = new XMLHttpRequest();
    currentXHR.open("GET", url, true);
    currentXHR.send();
    currentXHR.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.error(this.responseText); return; }

      let payload;
      try { payload = JSON.parse(this.responseText); } catch { return; }

      const data           = payload.data || [];
      const meta           = payload.meta || { page: 1, totalPages: 1, total: 0, perPage };
      const totalesDetalle = payload.totales_detalle || { operacion: { PESOS: 0, DLLS: 0 } };
      const abonosDetalle  = payload.abonos_detalle  || { operacion: { PESOS: 0, DLLS: 0 } };

      if (data.length === 0 && meta.totalPages > 0 && currentPage > meta.totalPages) {
        listar(meta.totalPages);
        return;
      }

      renderTabla(data);
      renderPaginacion(meta);
      renderMeta(meta);
      renderTotales(totalesDetalle);

      abonosDetalleCache = abonosDetalle;
      const { opCost, opAbono, fmt } = computeViewTotals(totalesDetalleCache, abonosDetalleCache);
      renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
    };
  }

  // ---------- Eventos de filtros/paginación ----------
  document.addEventListener("DOMContentLoaded", () => {
    if (!perPage || perPage < 1) perPage = 10;
    if (operacionFerroId > 0) listar(1);
  });

  selPerPage?.addEventListener("change", () => {
    perPage = parseInt(selPerPage.value || "10", 10);
    if (!perPage || perPage < 1) perPage = 10;
    listar(1);
  });

  inpBuscar?.addEventListener("keyup", (e) => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => listar(1), 250);
    if (e.key === "Enter") { clearTimeout(debounceId); listar(1); }
  });

  selMonedaFiltro?.addEventListener("change", () => listar(1));
  selTipoFiltro?.addEventListener("change",   () => listar(1));

  // Recalcular tarjetas al cambiar vista de moneda o tipo de cambio
  selMonedaVista?.addEventListener("change", () => {
    renderTotales(totalesDetalleCache);
    const { opCost, opAbono, fmt } = computeViewTotals(totalesDetalleCache, abonosDetalleCache);
    renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
  });

  inpTipoCambio?.addEventListener("input", () => {
    renderTotales(totalesDetalleCache);
    const { opCost, opAbono, fmt } = computeViewTotals(totalesDetalleCache, abonosDetalleCache);
    renderCostosAbonosCardsSoloOperacion({ opCost, opAbono, fmt });
  });

  // ---------- Autocomplete de Operación FERRO (filtro superior) ----------
  function buscarOps(term) {
    if (!opListBox) return;
    if (!term || term.length < 2) {
      opListBox.style.display = "none"; opListBox.innerHTML = ""; opMeta && (opMeta.textContent = "");
      return;
    }
    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/buscarOperaciones?term=${encodeURIComponent(term)}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.send();
    xhr.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) { opListBox.style.display = "none"; opListBox.innerHTML = ""; opMeta && (opMeta.textContent = ""); return; }

      let rows = [];
      try { rows = JSON.parse(this.responseText) || []; } catch { rows = []; }

      if (!Array.isArray(rows) || rows.length === 0) {
        opListBox.style.display = "none"; opListBox.innerHTML = ""; opMeta && (opMeta.textContent = "Sin resultados"); return;
      }

      let html = "";
      rows.forEach(r => {
        const id  = r.id_operacion_ferro || r.id_operacion; // tolerante
        const nom = r.numero_operacion;
        const cli = r.cliente || "";
        html += `
          <button type="button" class="list-group-item list-group-item-action" data-id="${id}" data-nom="${nom}">
            <div class="d-flex justify-content-between">
              <div><strong>${nom}</strong></div>
              <small class="text-muted">${cli}</small>
            </div>
          </button>`;
      });
      opListBox.innerHTML = html;
      opListBox.style.display = "block";
      opMeta && (opMeta.textContent = `Resultados: ${rows.length}`);

      opListBox.querySelectorAll("button.list-group-item").forEach(btn => {
        btn.addEventListener("click", () => {
          const id  = parseInt(btn.dataset.id || "0", 10) || 0;
          const nom = btn.dataset.nom || "";
          if (opIdHid) opIdHid.value = String(id);
          if (opNomInp) opNomInp.value = nom;
          operacionFerroId = id;
          opListBox.innerHTML = "";
          opListBox.style.display = "none";
          listar(1);
        });
      });
    };
  }

  opNomInp?.addEventListener("input", () => {
    const term = (opNomInp.value || "").trim();
    if (opIdHid) opIdHid.value = "";
    operacionFerroId = 0;
    buscarOps(term);
  });

  document.addEventListener("click", (e) => {
    if (!opListBox) return;
    if (!opListBox.contains(e.target) && e.target !== opNomInp) {
      opListBox.style.display = "none";
    }
  });

  // Exponer (opcional) para fijar operación desde fuera
  window.setOperacionIdCostosOperacion = function (opId) {
    operacionFerroId = parseInt(opId || "0", 10) || 0;
    if (opIdHid) opIdHid.value = String(operacionFerroId);
    if (operacionFerroId > 0) listar(1);
  };

  // Para otros módulos
  window.listarCostosOperacion = listar;

})();

/* ==========================================================================================
   Modal: Alta/Edición (usa los IDs que tienes en tu vista del modal de FERRO)
   ========================================================================================== */
(function () {
  "use strict";

  let isEditMode = false;

  const modalEl   = document.getElementById("modalCostoOperacion");
  const formEl    = document.getElementById("formAgregarCostoContenedores");

  // Campos del modal (según tu vista actual)
  const hidRowId  = document.getElementById("row_id");
  const opIdModal = document.getElementById("costosOperacionid");         // hidden → operacion_ferro_id
  const opNomModal= document.getElementById("costosOperacionNombre");      // visible autocomplete
  const listOps   = document.getElementById("costosSugerenciasOperaciones");

  const selTipo   = document.getElementById("costosContenedoresTipoCosto"); // → tipo_movimiento_id
  const selMoneda = document.getElementById("costosContenedoresMoneda");    // visual; deriva de tipo
  const inpMonto  = document.getElementById("costosContenedoresMonto");     // → monto
  const taComent  = document.getElementById("costosContenedoresComentarios");// → comentario

  // Filtro superior (para sembrar operación si ya está seleccionada)
  const opIdFiltro  = document.getElementById("costosOperacionFiltroOpId");
  const opNomFiltro = document.getElementById("costosOperacionFiltroOpNombre");

  function resetForm() {
    formEl?.reset();
    if (hidRowId) hidRowId.value = "";
    if (listOps) { listOps.innerHTML = ""; listOps.style.display = "none"; }
    if (selMoneda) selMoneda.value = "";
  }

  function seedOperacionDesdeFiltro() {
    const fid  = parseInt(opIdFiltro?.value || "0", 10) || 0;
    const fnom = (opNomFiltro?.value || "").trim();
    if (fid > 0 && fnom) {
      if (opIdModal)  opIdModal.value  = String(fid);
      if (opNomModal) opNomModal.value = fnom;
    }
  }

  // Autocomplete de operación FERRO dentro del modal
  function buscarOpsModal(term) {
    if (!listOps) return;
    if (!term || term.length < 2) {
      listOps.style.display = "none"; listOps.innerHTML = "";
      return;
    }
    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/buscarOperaciones?term=${encodeURIComponent(term)}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.send();
    xhr.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) { listOps.style.display = "none"; listOps.innerHTML = ""; return; }

      let rows = [];
      try { rows = JSON.parse(this.responseText) || []; } catch { rows = []; }

      if (!Array.isArray(rows) || rows.length === 0) {
        listOps.style.display = "none"; listOps.innerHTML = "";
        return;
      }

      let html = "";
      rows.forEach(r => {
        const id  = r.id_operacion_ferro || r.id_operacion;
        const nom = r.numero_operacion;
        const cli = r.cliente || "";
        html += `
          <button type="button" class="list-group-item list-group-item-action" data-id="${id}" data-nom="${nom}">
            <div class="d-flex justify-content-between">
              <div><strong>${nom}</strong></div>
              <small class="text-muted">${cli}</small>
            </div>
          </button>`;
      });
      listOps.innerHTML = html;
      listOps.style.display = "block";

      listOps.querySelectorAll("button.list-group-item").forEach(btn => {
        btn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          const id  = parseInt(btn.dataset.id || "0", 10) || 0;
          const nom = btn.dataset.nom || "";
          if (opIdModal)  opIdModal.value  = String(id);
          if (opNomModal) opNomModal.value = nom;
          listOps.innerHTML = "";
          listOps.style.display = "none";
        });
      });
    };
  }

  opNomModal?.addEventListener("input", () => {
    if (opIdModal) opIdModal.value = "";
    buscarOpsModal(opNomModal.value.trim());
  });

  document.addEventListener("click", (e) => {
    if (!listOps) return;
    if (!listOps.contains(e.target) && e.target !== opNomModal) {
      listOps.style.display = "none";
    }
  });

  // Auto-moneda visual según Tipo seleccionado (usa data-moneda del <option>)
  function syncMonedaPorTipo() {
    if (!selTipo) return;
    const opt = selTipo.selectedOptions?.[0];
    const m = opt ? (opt.getAttribute("data-moneda") || "").toUpperCase() : "";
    if (selMoneda) selMoneda.value = (m === "PESOS" || m === "DLLS") ? m : "";
  }
  selTipo?.addEventListener("change", syncMonedaPorTipo);

  // Apertura del modal
  modalEl?.addEventListener("show.bs.modal", () => {
    if (!isEditMode) {
      resetForm();
      seedOperacionDesdeFiltro();
    }
  });
  modalEl?.addEventListener("shown.bs.modal", () => {
    syncMonedaPorTipo();
    opNomModal?.focus();
  });

  // Submit (crear/actualizar)
  formEl?.addEventListener("submit", (e) => {
    e.preventDefault();

    const opId = parseInt(opIdModal?.value || "0", 10) || 0;
    const tipoId = parseInt(selTipo?.value || "0", 10) || 0;
    const monto = parseFloat(inpMonto?.value || "0") || 0;

    if (!opId)  { Swal.fire('Atención', 'Selecciona una operación FERRO.', 'warning'); return; }
    if (!tipoId){ Swal.fire('Atención', 'Selecciona un tipo de movimiento.', 'warning'); return; }
    if (monto <= 0){ Swal.fire('Atención', 'Monto inválido.', 'warning'); return; }

    // Si es ALTA, asegúrate de que row_id esté vacío
    if (!isEditMode && hidRowId) hidRowId.value = "";

    // Construir payload para backend:
    const fd = new FormData();
    if (hidRowId?.value) fd.append("row_id", hidRowId.value);
    fd.append("operacion_ferro_id", String(opId));
    fd.append("tipo_movimiento_id", String(tipoId));
    fd.append("monto", String(monto));
    fd.append("comentario", String(taComent?.value || ""));

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/guardar`;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) {
        console.error(this.responseText);
        Swal.fire('Error', 'No se pudo guardar el costo (HTTP).', 'error');
        return;
      }
      let resp = {};
      try { resp = JSON.parse(this.responseText) || {}; } catch { resp = {}; }

      if (resp.status === "success") {
        Swal.fire('Éxito', isEditMode ? '✅ Costo actualizado correctamente.' : '✅ Costo registrado correctamente.', 'success');
        const bs = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
        bs?.hide();
        window.listarCostosOperacion?.(1);
      } else {
        Swal.fire('Error', resp.message || 'No se pudo guardar el costo.', 'error');
      }
    };
    xhr.send(fd);
  });

  // API para abrir modal (nuevo / editar)
  function fillForm({ rowId = "", opId = "", opNom = "", tipoId = "", moneda = "", monto = "", comentario = "" } = {}) {
    if (hidRowId) hidRowId.value = rowId;
    if (opIdModal)  opIdModal.value  = opId;
    if (opNomModal) opNomModal.value = opNom;
    if (selTipo) { selTipo.value = String(tipoId || ""); syncMonedaPorTipo(); }
    if (moneda && selMoneda) selMoneda.value = moneda;
    if (inpMonto)  inpMonto.value = (monto ?? "");
    if (taComent)  taComent.value = comentario || "";
  }

  function openNuevo() {
    isEditMode = false;
    resetForm();
    seedOperacionDesdeFiltro();
    syncMonedaPorTipo();
    const title = document.getElementById("modalCostoOperacionLabel");
    if (title) title.innerHTML = `<i data-feather="plus-circle" class="me-1"></i> Añadir Costo a Operación`;
    window.feather?.replace?.();
  }

  function openEditar(tr) {
    isEditMode = true;
    resetForm();
    fillForm({
      rowId:   tr.dataset.rowId,
      opId:    tr.dataset.opId,
      opNom:   tr.dataset.opNom,
      tipoId:  tr.dataset.tipoId,
      moneda:  tr.dataset.moneda,
      monto:   tr.dataset.monto,
      comentario: tr.dataset.coment
    });
    const title = document.getElementById("modalCostoOperacionLabel");
    if (title) title.innerHTML = `<i data-feather="edit-2" class="me-1"></i> Editar Costo de Operación`;
    window.feather?.replace?.();
    const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    modal?.show();
  }

  // Botón "Nuevo"
  document.getElementById("costosOperacionBtnNuevo")?.addEventListener("click", openNuevo);

  // Editar / Eliminar en la tabla
  document.getElementById("tbodyCostosOperacionCombined")?.addEventListener("click", (e) => {
    const btnEdit = e.target.closest(".btnEditarCostoOperacion");
    if (btnEdit) {
      const tr = btnEdit.closest("tr");
      openEditar(tr);
      return;
    }
    const btnDel = e.target.closest(".btnEliminarCostoOperacion");
    if (btnDel) {
      const tr = btnDel.closest("tr");
      const rowId = tr.dataset.rowId;
      Swal.fire({
        title: '¿Eliminar costo?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((r) => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append("id", rowId);
        const xhr = new XMLHttpRequest();
        xhr.open("POST", base_url + "Operaciones_maritimo_ferro_costos_Contenedor/desactivarCostoOperacion", true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState !== 4) return;
          if (xhr.status !== 200) { Swal.fire("Error", "Error HTTP " + xhr.status, "error"); return; }
          let resp = {}; try { resp = JSON.parse(xhr.responseText) || {} } catch { }
          if (resp.status === "success") {
            Swal.fire("Éxito", resp.message || "Costo eliminado", "success");
            window.listarCostosOperacion?.(1);
          } else {
            Swal.fire("Error", resp.message || "No se pudo eliminar", "error");
          }
        };
        xhr.send(fd);
      });
    }
  });

})();
  
// ===== Exportar (igual que en marítimas) =====
document.getElementById('btnExportarExcelCostosOperacion')?.addEventListener('click', () => {
  ExportarTablas.exportar({
    ref: 'tablaCostosOperacionExportar',
    formato: 'xlsx',
    nombre: 'CostosOperacionFerro.xlsx',
    columnasOcultas: [],
    soloVisibles: true,
    sheetName: 'Costos FERRO'
  });
});

document.getElementById('btnExportarPDFCostosOperacion')?.addEventListener('click', () => {
  ExportarTablas.exportar({
    ref: '#tablaCostosOperacionExportar',
    formato: 'pdf',
    nombre: 'CostosOperacionFerro.pdf',
    titulo: 'Costos Operación FERRO',
    orientacion: 'landscape',
    formatoPagina: 'letter',
    columnasOcultas: [],
    soloVisibles: true
  });
});

// ===== Utilidades para tarjetas (reusadas) =====
function setBadgeValueSimple(id, val, fmt) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = fmt(val);
  el.classList.remove('bg-light','text-dark','bg-danger','bg-success','bg-secondary');
  if (val > 0) el.classList.add('bg-success');
  else if (val < 0) el.classList.add('bg-danger');
  else el.classList.add('bg-secondary');
}

function renderCostosAbonosCardsSoloOperacion({ opCost = 0, opAbono = 0, fmt } = {}) {
  const opBalance = (opAbono - opCost);

  // Operación
  const elTotOp = document.getElementById('costosOperacionTotalOperacion');
  const elAbOp  = document.getElementById('costosOperacionAbonosOperacion');
  if (elTotOp) elTotOp.textContent = fmt(opCost);
  if (elAbOp)  elAbOp.textContent  = fmt(opAbono);
  setBadgeValueSimple('costosOperacionBalanceOperacion', opBalance, fmt);

  // General = SOLO operación
  const totalAbonos  = opAbono;
  const totalCostos  = opCost;
  const totalBalance = totalAbonos - totalCostos;

  const elGen     = document.getElementById('costosOperacionTotalGeneral');
  const elGenAb   = document.getElementById('costosOperacionTotalAbonosGeneral');
  const elGenCost = document.getElementById('costosOperacionTotalCostosGeneral');
  if (elGen)     elGen.textContent     = fmt(totalBalance);
  if (elGenAb)   elGenAb.textContent   = fmt(totalAbonos);
  if (elGenCost) elGenCost.textContent = fmt(totalCostos);
}
