// ============================================
//  Módulo: Costos por Contenedor (Frontend)
//  Archivo sugerido: assets/js/modulosAdmin/costos_contenedor.js
// ============================================

// ------- Refs del DOM -------
const tbodyCostosContenedor             = document.getElementById("tbodyCostosContenedores");
const inputBuscarCostosContenedor       = document.getElementById("buscarCostoContenedor");
const selMonedaCostosContenedor         = document.getElementById("filtroMonedaCostoContenedor");
const selTipoCostoContenedor            = document.getElementById("filtroTipoCostoContenedor");
const perPageSelectCostosContenedor     = document.getElementById("perPageCostos");
const paginacionCostosContenedor        = document.getElementById("paginacionCostos");
const metaResumenCostosContenedor       = document.getElementById("metaResumenCostos");
const costosContenedorTiposMap = Object.create(null);
// ------- Estado -------
let currentPageCostosContenedor = 1;
let perPageCostosContenedor     = parseInt(perPageSelectCostosContenedor?.value || "10", 10);
let currentXHRCostosContenedor  = null;
let debounceIdCostosContenedor  = null;

// ------- Helpers -------
function ccSafe(v){ return (v === undefined || v === null) ? "" : v; }

function toMoneyCostosContenedor(n){
  if (n === null || n === undefined || n === "") return "";
  const num = Number(n);
  if (Number.isNaN(num)) return n;
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Normaliza “Pesos/Dólares” (vista) -> “PESOS/DLLS” (catálogo)
function normalizeMonedaCostosContenedor(val){
  const t = (val || "").toLowerCase();
  if (t === "pesos") return "PESOS";
  if (t === "dólares" || t === "dolares") return "DLLS";
  return "";
}

function prettyMonedaCostosContenedor(m){
  if (!m) return "";
  const t = String(m).toUpperCase();
  if (t === "PESOS") return "Pesos";
  if (t === "DLLS")  return "Dólares";
  return m;
}

function moneyWithSymbolCostosContenedor(monto, moneda){
  const n = Number(monto);
  if (Number.isNaN(n)) return monto;
  const symbol = (String(moneda).toUpperCase() === "DLLS") ? "$" : "$";
  return `${symbol}${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// ============================================
//                 INICIO
// ============================================
document.addEventListener("DOMContentLoaded", () => {
  if (!perPageCostosContenedor || perPageCostosContenedor < 1) perPageCostosContenedor = 10;
  // Cargar catálogo de tipos para el filtro
  cargarTiposMovimientoFiltroCostosContenedor();
  // Primer listado
  listarCostosContenedor(1);
});

// ============================================
//           Listado (paginación+filtros)
// ============================================
function listarCostosContenedor(page = 1){
  currentPageCostosContenedor = page;
  const naturaleza = (document.getElementById("filtroNaturalezaCostoContenedor")?.value || "").toUpperCase();

  const buscar  = (inputBuscarCostosContenedor?.value || "").trim();
  const moneda  = normalizeMonedaCostosContenedor(selMonedaCostosContenedor?.value || "");
  const tipoId  = parseInt(selTipoCostoContenedor?.value || "0", 10) || 0;
 
  // Cancelar XHR en vuelo
  if (currentXHRCostosContenedor && currentXHRCostosContenedor.readyState !== 4) {
    currentXHRCostosContenedor.abort();
  }

  renderCargandoCostosContenedor();

const params = new URLSearchParams({
  page:    String(currentPageCostosContenedor),
  perPage: String(perPageCostosContenedor),
  buscar,
  moneda,
  tipo: String(tipoId),
  naturaleza
});


  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/listarPaginado?${params.toString()}`;
  currentXHRCostosContenedor = new XMLHttpRequest();
  currentXHRCostosContenedor.open("GET", url, true);
  currentXHRCostosContenedor.send();
  currentXHRCostosContenedor.onreadystatechange = function(){
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("CostosContenedor listar error:", this.responseText);
      renderErrorCostosContenedor("No se pudo cargar la información.");
      return;
    }

    let payload;
    try { payload = JSON.parse(this.responseText); }
    catch { renderErrorCostosContenedor("Respuesta inválida del servidor."); return; }

    const data = payload.data || [];
    const meta = payload.meta || { page: 1, totalPages: 1, total: 0, perPage: perPageCostosContenedor };

    // Si estoy en una página > totalPages, ajusto
    if (data.length === 0 && meta.totalPages > 0 && currentPageCostosContenedor > meta.totalPages) {
      listarCostosContenedor(meta.totalPages);
      return;
    }

    renderTablaCostosContenedor(data);
    renderPaginacionCostosContenedor(meta);
    renderMetaCostosContenedor(meta);
    feather?.replace();
  };
}

// ============================================
//           Renders de estados y tabla
// ============================================
function renderCargandoCostosContenedor(){
  if (!tbodyCostosContenedor) return;
  tbodyCostosContenedor.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-muted py-4">
        Cargando resultados...
      </td>
    </tr>`;
}

function renderErrorCostosContenedor(msg){
  if (!tbodyCostosContenedor) return;
  tbodyCostosContenedor.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-danger py-4">
        ${msg}
      </td>
    </tr>`;
}

function renderTablaCostosContenedor(rows){
  if (!tbodyCostosContenedor) return;

  tbodyCostosContenedor.innerHTML = "";
  if (!Array.isArray(rows) || rows.length === 0){
    tbodyCostosContenedor.innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-4">No se encontraron resultados</td>
      </tr>`;
    return;
  }

   rows.forEach(r => {
    const nat = String(r.tipo || '').toUpperCase(); // 'GASTO'|'ABONO'
    const sign = (nat === 'ABONO') ? '+' : '';
    const cls  = (nat === 'ABONO') ? 'text-success' : 'text-danger';
    const montoFmt = moneyWithSymbolCostosContenedor(r.monto, r.moneda);

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${ccSafe(r.numero_operacion)}</td>
      <td>${ccSafe(r.contenedor)}</td>
      <td>
        ${ccSafe(r.concepto)}
        <span class="badge ${nat==='ABONO'?'bg-success-subtle text-success':'bg-danger-subtle text-danger'} ms-1">
          ${nat}
        </span>
      </td>
      <td class="${cls} fw-semibold">${sign} ${montoFmt}</td>
      <td>${prettyMonedaCostosContenedor(r.moneda)}</td>
      <td>${ccSafe(r.comentario)}</td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-secondary me-1" title="Editar"
                onclick="ccEditarCostoContenedor(${r.id_costo_contenedor})">
          <i data-feather="edit"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" title="Eliminar"
                onclick="ccEliminarCostoContenedor(${r.id_costo_contenedor})">
          <i data-feather="x"></i>
        </button>
      </td>
    `;
    tbodyCostosContenedor.appendChild(tr);
  });
}

// ============================================
//                Paginación
// ============================================
function renderPaginacionCostosContenedor(meta){
  if (!paginacionCostosContenedor) return;

  const page       = parseInt(meta.page || 1, 10);
  const totalPages = parseInt(meta.totalPages || 1, 10);

  if (totalPages <= 1) {
    paginacionCostosContenedor.innerHTML = "";
    return;
  }

  let start = Math.max(1, page - 2);
  let end   = Math.min(totalPages, start + 4);
  start     = Math.max(1, end - 4);

  let html = `
    <li class="page-item ${page === 1 ? "disabled" : ""}">
      <a class="page-link" href="#" data-page="${page - 1}">«</a>
    </li>
  `;
  for (let p = start; p <= end; p++){
    html += `
      <li class="page-item ${p === page ? "active" : ""}">
        <a class="page-link" href="#" data-page="${p}">${p}</a>
      </li>
    `;
  }
  html += `
    <li class="page-item ${page === totalPages ? "disabled" : ""}">
      <a class="page-link" href="#" data-page="${page + 1}">»</a>
    </li>
  `;

  paginacionCostosContenedor.innerHTML = html;

  // Delegación
  paginacionCostosContenedor.querySelectorAll("a.page-link").forEach(a => {
    a.addEventListener("click", (e) => {
      e.preventDefault();
      const p = parseInt(a.dataset.page, 10);
      if (!Number.isNaN(p)) listarCostosContenedor(p);
    });
  });
}

// ============================================
//          Meta “Mostrando X–Y de Z”
// ============================================
function renderMetaCostosContenedor(meta){
  if (!metaResumenCostosContenedor) return;

  const page    = parseInt(meta.page || 1, 10);
  const perPg   = parseInt(meta.perPage || perPageCostosContenedor || 10, 10);
  const total   = parseInt(meta.total || 0, 10);
  const inicio  = total === 0 ? 0 : ((page - 1) * perPg) + 1;
  const fin     = Math.min(page * perPg, total);

  metaResumenCostosContenedor.textContent = `Mostrando ${inicio}–${fin} de ${total}`;
}
// =======================================================
//  INSERTAR: Costos por Contenedor (prefijo: costosContenedor*)
// =======================================================

// ------- Refs Modal -------
const costosContenedorModalEl         = document.getElementById("modalAgregarCosto");
const costosContenedorModal           = costosContenedorModalEl ? new bootstrap.Modal(costosContenedorModalEl) : null;
const costosContenedorForm            = document.getElementById("formAgregarCostoContenedores");
const costosContenedorBtnNuevo        = document.getElementById("btnNuevoCostoContenedor");

// Operación
const costosContenedorInpOpId         = document.getElementById("costosOperacionid");
const costosContenedorInpOpNombre     = document.getElementById("costosOperacionNombre");
const costosContenedorBoxOps          = document.getElementById("costosSugerenciasOperaciones");

// Contenedor (en operación)
const costosContenedorInpContOpId     = document.getElementById("costosContenedorContenedorId");
const costosContenedorInpContOpNombre = document.getElementById("costosContenedorContenedorNombre");
const costosContenedorBoxConts        = document.getElementById("sugerenciasCostosContenedor");

// Tipo / Moneda / Monto / Comentarios
const costosContenedorSelTipo         = document.getElementById("costosContenedoresTipoCosto");
const costosContenedorSelMoneda       = document.getElementById("costosContenedoresMoneda");
const costosContenedorInpMonto        = document.getElementById("costosContenedoresMonto");
const costosContenedorInpComentarios  = document.getElementById("costosContenedoresComentarios");
const costosContenedorBtnSubmit       = document.getElementById("btnSubmitCostoContenedor");

 
// ------- Catálogo: tipos (solo TERRESTRE + GASTO) -------
// ACEPTA callback opcional que se ejecuta cuando termina de poblar el select
function costosContenedorCargarTiposMovimiento(done){
  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/catalogoTiposMovimiento?categoria=Terrestre`;
  const xhr = new XMLHttpRequest();
  xhr.open("GET", url, true);
  xhr.send();
  xhr.onreadystatechange = function () {
    if (this.readyState !== 4) return;
    if (this.status !== 200) { console.error("[costosContenedor] tipos error:", this.responseText); return; }

    let data = [];
    try { data = JSON.parse(this.responseText) || []; } catch { return; }

    if (!costosContenedorSelTipo) return;
    costosContenedorSelTipo.innerHTML = `<option value="">Seleccione un tipo</option>`;
    for (const k in costosContenedorTiposMap) delete costosContenedorTiposMap[k];

    data.forEach(t => {
      if (!t) return;
      const opt = document.createElement("option");
      opt.value = t.id_tipo_movimiento;
      opt.textContent = `${t.nombre} (${t.tipo})`; // GASTO / ABONO
      const mon = (String(t.moneda || "").toUpperCase() === "DLLS") ? "DLLS" : "PESOS";
      opt.dataset.moneda = mon;

      costosContenedorTiposMap[String(t.id_tipo_movimiento)] = mon;
      costosContenedorSelTipo.appendChild(opt);
    });

    if (typeof done === "function") done();
  };
}




// Autollenar moneda al cambiar tipo
function costosContenedorPrepararMoneda() {
  if (!costosContenedorSelMoneda) return;
  costosContenedorSelMoneda.innerHTML = `
    <option value="">Seleccione</option>
    <option value="PESOS">PESOS</option>
    <option value="DLLS">DLLS</option>
  `;
  costosContenedorSelMoneda.setAttribute("readonly","readonly");
  costosContenedorSelMoneda.setAttribute("disabled","disabled");
}
costosContenedorSelTipo?.addEventListener("change", () => {
  if (!costosContenedorSelMoneda) return;
  const sel = costosContenedorSelTipo.options[costosContenedorSelTipo.selectedIndex];
  // usa dataset o, si no viene, el mapa
  const mon = sel?.dataset?.moneda || costosContenedorTiposMap[costosContenedorSelTipo.value] || "";
  costosContenedorSelMoneda.value = mon;
  costosContenedorSelMoneda.setAttribute("disabled","disabled");
});


// ------- Abrir modal (reset limpio) -------
costosContenedorBtnNuevo?.addEventListener("click", () => {
  costosContenedorForm?.reset();
   document.getElementById("row_id")?.setAttribute("value", "");
 document.getElementById("row_id").value = "";
 costosContenedorForm?.removeAttribute("data-mode");
 if (costosContenedorBtnSubmit) {
   costosContenedorBtnSubmit.innerHTML = `<i data-feather="save"></i> Guardar`;
 }
  if (costosContenedorInpOpId)         costosContenedorInpOpId.value = "";
  if (costosContenedorInpContOpId)     costosContenedorInpContOpId.value = "";
  if (costosContenedorBoxOps)          { costosContenedorBoxOps.innerHTML = ""; costosContenedorBoxOps.style.display = "none"; }
  if (costosContenedorBoxConts)        { costosContenedorBoxConts.innerHTML = ""; costosContenedorBoxConts.style.display = "none"; }

  costosContenedorCargarTiposMovimiento();
  costosContenedorPrepararMoneda();

  feather?.replace();
});
 
// ------- Submit: insertar/actualizar costo -------
costosContenedorForm?.addEventListener("submit", (e) => {
  e.preventDefault();

  const contOpId = parseInt(costosContenedorInpContOpId?.value || "0", 10) || 0;
  const tipoId   = parseInt(costosContenedorSelTipo?.value || "0", 10) || 0;
  const montoVal = parseFloat(costosContenedorInpMonto?.value || "0") || 0;

  if (contOpId <= 0) { Swal.fire("Aviso","Selecciona una operación y un contenedor válido.","warning"); costosContenedorInpOpNombre?.focus(); return; }
  if (tipoId <= 0)   { Swal.fire("Aviso","Selecciona un tipo de costo.","warning"); costosContenedorSelTipo?.focus(); return; }
  if (montoVal <= 0) { Swal.fire("Aviso","El monto debe ser mayor a 0.","warning"); costosContenedorInpMonto?.focus(); return; }

  const isEdit = costosContenedorForm?.dataset.mode === "edit";
  const url = isEdit
    ? `${base_url}Operaciones_maritimas_costos_Contenedor/actualizarCostoContenedor`
    : `${base_url}Operaciones_maritimas_costos_Contenedor/registrarCostoContenedor`;

  

  costosContenedorBtnSubmit?.setAttribute("disabled","disabled");
  costosContenedorBtnSubmit?.classList.add("disabled");
  const fd  = new FormData(costosContenedorForm);
  if (isEdit) fd.set('row_id', document.getElementById('row_id').value);
  fd.append('moneda', (costosContenedorSelMoneda?.value || '').toUpperCase());
  const xhr = new XMLHttpRequest();
  xhr.open("POST", url, true);
  xhr.send(fd);
  xhr.onreadystatechange = function(){
    if (this.readyState !== 4) return;

    costosContenedorBtnSubmit?.removeAttribute("disabled");
    costosContenedorBtnSubmit?.classList.remove("disabled");

    if (this.status !== 200) {
      console.error("[costosContenedor] guardar error:", this.responseText);
      Swal.fire("Error","No se pudo guardar el costo.","error");
      return;
    }

    let res; try { res = JSON.parse(this.responseText); } catch { Swal.fire("Error","Respuesta inválida.","error"); return; }
    if (res.status === "success") {
      costosContenedorModal?.hide();
      costosContenedorForm?.reset();
           // aseguramos volver a modo "nuevo":
     document.getElementById("row_id").value = "";
     costosContenedorForm?.removeAttribute("data-mode");
     if (costosContenedorBtnSubmit) {
       costosContenedorBtnSubmit.innerHTML = `<i data-feather="save"></i> Guardar`;
     }
      // refresca manteniendo la página actual
      listarCostosContenedor(currentPageCostosContenedor || 1);
      Swal.fire("Listo", res.msg || "Guardado correctamente.", "success");
    } else {
      Swal.fire("Aviso", res.msg || "No se pudo guardar.", res.status || "warning");
    }
  };
});


window.ccEditarCostoContenedor = function(id){
  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/obtenerCosto/${encodeURIComponent(id)}`;
  const xhr = new XMLHttpRequest();
  xhr.open("GET", url, true);
  xhr.send();
  xhr.onreadystatechange = function(){
    if (this.readyState !== 4) return;
    if (this.status !== 200) { Swal.fire("Error","No se pudo cargar el costo.","error"); return; }
    console.log(this.responseText);
    let res; try { res = JSON.parse(this.responseText); } catch { Swal.fire("Error","Respuesta inválida.","error"); return; }
    if (res.status !== "success" || !res.data) { Swal.fire("Aviso", res.msg || "Registro no encontrado.","warning"); return; }

    const d = res.data;

    // setear campos básicos
    document.getElementById("row_id").value = d.id_costo_contenedor;
    costosContenedorInpOpId.value         = d.id_operacion || "";
    costosContenedorInpOpNombre.value     = d.numero_operacion || "";
    costosContenedorInpContOpId.value     = d.id_contenedor_operacion || d.contenedor_operacion_id || "";
    costosContenedorInpContOpNombre.value = d.contenedor || "";
    costosContenedorInpMonto.value        = d.monto ?? "";
    costosContenedorInpComentarios.value  = d.comentario ?? "";

    // cargar catálogo y luego preseleccionar el tipo + moneda
costosContenedorCargarTiposMovimiento(() => {
  // Tipo
  if (costosContenedorSelTipo) {
    costosContenedorSelTipo.value = String(d.id_tipo_movimiento || "");
  }

  // Moneda (mostrarla aunque no sea editable)
  costosContenedorPrepararMoneda(); // ← asegura opciones PESOS/DLLS disponibles
  if (costosContenedorSelMoneda) {
    const sel = costosContenedorSelTipo.options[costosContenedorSelTipo.selectedIndex];
    const mon = (sel?.dataset?.moneda) || (String(d.moneda || "").toUpperCase());
    costosContenedorSelMoneda.value = mon || "";
    costosContenedorSelMoneda.setAttribute("disabled","disabled"); // no editable
    // (no uses readonly en <select>; no aplica en HTML)
  }

  // abrir modal + botón
  costosContenedorModal?.show();
  if (costosContenedorBtnSubmit) {
    costosContenedorBtnSubmit.innerHTML = `<i data-feather="save"></i> Actualizar`;
  }
  costosContenedorForm?.setAttribute("data-mode", "edit");
  feather?.replace();
});
  }
}
costosContenedorModalEl?.addEventListener('hidden.bs.modal', () => {
  costosContenedorForm?.reset();
  document.getElementById("row_id").value = "";
  costosContenedorForm?.removeAttribute("data-mode");
  if (costosContenedorBtnSubmit) {
    costosContenedorBtnSubmit.innerHTML = `<i data-feather="save"></i> Guardar`;
  }
});
window.ccEliminarCostoContenedor = function(id){
  // Blindaje rápido
  id = Number(id) || 0;
  if (!id) { Swal.fire("Aviso","ID inválido.","warning"); return; }

  Swal.fire({
    title: "¿Eliminar costo?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((r) => {
    if (!r.isConfirmed) return;

    const fd = new FormData();
    fd.append("id", String(id));

    const xhr = new XMLHttpRequest();
    xhr.open("POST", `${base_url}Operaciones_maritimas_costos_Contenedor/eliminarCostoContenedor`, true);
    xhr.send(fd);
    xhr.onreadystatechange = function(){
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error("[costosContenedor] eliminar error:", this.responseText);
        Swal.fire("Error","No se pudo eliminar.","error");
        return;
      }

      let res;
      try { res = JSON.parse(this.responseText); }
      catch { Swal.fire("Error","Respuesta inválida.","error"); return; }

      if (res.status === "success") {
        // Refresca manteniendo página; tu listar ajusta página si queda vacía
        listarCostosContenedor(currentPageCostosContenedor || 1);
        Swal.fire("Eliminado", res.msg || "Costo eliminado correctamente.", "success");
      } else {
        Swal.fire("Aviso", res.msg || "No se pudo eliminar.", res.status || "warning");
      }
    };
  });
};
// Excel
  document.getElementById('btnExportarExcelCostosContenedor')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: 'tablaCostosContenedores',       // "#tablaEventos" o el elemento también funciona
      formato: 'xlsx',
      nombre: 'CostosContenedor.xlsx',
      columnasOcultas: [6],      // oculta columna ID
      soloVisibles: true,
      sheetName: 'Costos Contenedor'
    });
  });

  // PDF
  document.getElementById('btnExportarPDFCostosContenedor')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: '#tablaCostosContenedores',
      formato: 'pdf',
      nombre: 'CostosContenedor.pdf',
      titulo: 'Costos Contenedor',
      orientacion: 'landscape',  // o 'portrait'
      formatoPagina: 'letter',   // o 'a4'
      columnasOcultas: [6],
      soloVisibles: true
    });
  });