// =======================================================
//  Módulo: Costos por Operación (LISTAR COMBINADO)
//  Archivo: assets/js/modulosAdmin/operaciones_maritimas/catalogos/costos_operacion_catalogo.js
//  Nota: todas las vars/funcs quedan con sufijo CostosOperacion para evitar choques.
// =======================================================
(function(){
  "use strict";

  // ---------- Refs del DOM ----------
  const tbodyCostosOperacionCombined     = document.getElementById("tbodyCostosOperacionCombined");
  const costosOperacionBuscar            = document.getElementById("costosOperacionBuscar");
  const costosOperacionFiltroOrigen      = document.getElementById("costosOperacionFiltroOrigen");
  const costosOperacionFiltroMoneda      = document.getElementById("costosOperacionFiltroMoneda");
  const costosOperacionFiltroTipo        = document.getElementById("costosOperacionFiltroTipo");
  const costosOperacionPerPageSel        = document.getElementById("costosOperacionPerPage");
  const costosOperacionPaginacion        = document.getElementById("costosOperacionPaginacion");
  const costosOperacionMeta              = document.getElementById("costosOperacionMeta");

  // Tarjetas totales + controles de vista
  const costosOperacionTotalOperacion    = document.getElementById("costosOperacionTotalOperacion");
  const costosOperacionTotalContenedores = document.getElementById("costosOperacionTotalContenedores");
  const costosOperacionTotalGeneral      = document.getElementById("costosOperacionTotalGeneral");
  const costosOperacionMonedaVistaSel    = document.getElementById("costosOperacionMonedaVista");  // MXN | USD
  const costosOperacionTipoCambioInp     = document.getElementById("costosOperacionTipoCambio");   // MXN por 1 USD

  // Autocomplete Operación
  const opIdInp        = document.getElementById("costosOperacionFiltroOpId");
  const opNombreInp    = document.getElementById("costosOperacionFiltroOpNombre");
  const opSugBox       = document.getElementById("costosOperacionFiltroOpSugerencias");
  const opMeta         = document.getElementById("costosOperacionFiltroOpMeta");

  // ---------- Estado ----------
  let currentPageCostosOperacion   = 1;
  let perPageCostosOperacion       = parseInt(costosOperacionPerPageSel?.value || "10", 10);
  let currentXHRCostosOperacion    = null;
  let debounceIdCostosOperacion    = null;

  // Operación elegida (0 hasta que seleccionen)
  let operacionIdCostosOperacion   = parseInt(opIdInp?.value || "0", 10) || 0;

  // Cache para conversiones de totales
  let totalesDetalleCacheCostosOperacion = null;
  let abonosDetalleCacheCostosOperacion  = null;

  // ---------- Helpers ----------
  const safeCostosOperacion = (v)=> (v === undefined || v === null) ? "" : v;

  function moneyCostosOperacion(n){
    const num = Number(n);
    if (!Number.isFinite(num)) return "$ 0.00";
    return "$ " + num.toLocaleString("es-MX", { minimumFractionDigits:2, maximumFractionDigits:2 });
  }
  function prettyMonedaCostosOperacion(m){
    const t = String(m||"").toUpperCase();
    return (t==="PESOS" || t==="DLLS") ? t : t;
  }
  function fmtFechaCostosOperacion(s){ return s ? String(s).substring(0,10) : ""; }

  function formatMoneyGenericCostosOperacion(n, symbol) {
    const num = Number(n) || 0;
    return (symbol || "$") + " " + num.toLocaleString("es-MX",{minimumFractionDigits:2, maximumFractionDigits:2});
  }

  // ---------- Render: estados ----------
  function renderCargandoCostosOperacion(){
    if (!tbodyCostosOperacionCombined) return;
    tbodyCostosOperacionCombined.innerHTML = `
      <tr><td colspan="8" class="text-center text-muted py-4">Cargando resultados…</td></tr>`;
  }
  function renderErrorCostosOperacion(msg){
    if (!tbodyCostosOperacionCombined) return;
    tbodyCostosOperacionCombined.innerHTML = `
      <tr><td colspan="8" class="text-center text-danger py-4">${msg||"Error al cargar."}</td></tr>`;
  }
  function renderVacioCostosOperacion(){
    if (!tbodyCostosOperacionCombined) return;
    tbodyCostosOperacionCombined.innerHTML = `
      <tr><td colspan="8" class="text-center text-muted py-4">No hay costos para mostrar.</td></tr>`;
  }

  // ---------- Render: tabla ----------
function renderTablaCostosOperacion(rows){
  if (!tbodyCostosOperacionCombined) return;
  tbodyCostosOperacionCombined.innerHTML = "";
  if (!Array.isArray(rows) || rows.length===0){ renderVacioCostosOperacion(); return; }

  rows.forEach(r=>{
    const tr = document.createElement("tr");
    const origen = String(r.origen||"").toUpperCase();
    const badgeOrigen  = (origen==="CONTENEDOR")
      ? `<span class="badge bg-info-subtle text-info">CONTENEDOR</span>`
      : `<span class="badge bg-primary-subtle text-primary">OPERACIÓN</span>`;

    // NUEVO: naturaleza (gasto|abono)
    const nat = String(r.naturaleza || "").toUpperCase(); // "GASTO" | "ABONO"
    const isAbono = (nat === "ABONO");
    const montoFmt = moneyCostosOperacion(r.monto || 0);
    const montoCls = isAbono ? "text-success fw-semibold" : "text-danger fw-semibold";
    const montoConSigno = `${isAbono ? "+" : " "} ${montoFmt}`;

    // (opcional) badge pequeño junto al concepto
    const badgeNat = nat
      ? `<span class="badge ${isAbono ? 'bg-success-subtle text-success':'bg-danger-subtle text-danger'} ms-1"> ${nat}</span>`
      : "";

    // data-* actuales (por si editas desde dataset)
    tr.dataset.origen = origen;
    tr.dataset.rowId  = r.row_id || "";
    tr.dataset.opId   = r.operacion_id || "";
    tr.dataset.opNom  = r.numero_operacion || "";
    tr.dataset.tipoId = r.tipo_movimiento_id || "";
    tr.dataset.moneda = r.moneda || "";
    tr.dataset.monto  = r.monto || "";
    tr.dataset.coment = r.comentario || "";

     
    tr.innerHTML = `
      <td>${fmtFechaCostosOperacion(r.fecha)}</td>
      <td>${badgeOrigen}</td>
      <td>${origen==="CONTENEDOR" ? safeCostosOperacion(r.contenedor||"") : ""}</td>
      <td>${safeCostosOperacion(r.concepto||"")}${badgeNat}</td>
      <td>${prettyMonedaCostosOperacion(r.moneda||"")}</td>
      <td class="text-end ${montoCls}">${montoConSigno}</td>
      <td>${safeCostosOperacion(r.comentario||"")}</td>
      <td class="text-center">
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary btnEditarCostoOperacion" title="Editar" ${origen==="OPERACION"?"":"disabled"}>
            <i data-feather="edit-2"></i>
          </button>
          <button class="btn btn-outline-danger btnEliminarCostoOperacion" title="Eliminar" ${origen==="OPERACION"?"":"disabled"}>
            <i data-feather="trash-2"></i>
          </button>
        </div>
      </td>`;
    tbodyCostosOperacionCombined.appendChild(tr);
  });
  window.feather?.replace?.();
}


  // ---------- Render: totales con conversión ----------
  function renderTotalesCostosOperacion(totales, totalesDetalle){
    // guarda cache para recalcular al cambiar selector/tipoCambio
    if (totalesDetalle) totalesDetalleCacheCostosOperacion = totalesDetalle;

    // si no tenemos detalle, pintamos simples
    if (!totalesDetalleCacheCostosOperacion ||
        !totalesDetalleCacheCostosOperacion.operacion ||
        !totalesDetalleCacheCostosOperacion.contenedores) {
      const tOp  = Number(totales?.total_operacion||0);
      const tCo  = Number(totales?.total_contenedores||0);
      const tGen = Number(totales?.total_general || (tOp+tCo));
      if (costosOperacionTotalOperacion)    costosOperacionTotalOperacion.textContent    = moneyCostosOperacion(tOp);
      if (costosOperacionTotalContenedores) costosOperacionTotalContenedores.textContent = moneyCostosOperacion(tCo);
      if (costosOperacionTotalGeneral)      costosOperacionTotalGeneral.textContent      = moneyCostosOperacion(tGen);
      return;
    }

    const det = totalesDetalleCacheCostosOperacion;
    const opPesos  = Number(det.operacion.PESOS||0);
    const opDlls   = Number(det.operacion.DLLS ||0);
    const conPesos = Number(det.contenedores.PESOS||0);
    const conDlls  = Number(det.contenedores.DLLS ||0);

    const vista = (costosOperacionMonedaVistaSel?.value || "MXN").toUpperCase(); // MXN|USD
    let tc = parseFloat(costosOperacionTipoCambioInp?.value || "0");
    if (!Number.isFinite(tc) || tc<=0) tc = 1;

    let symbol = "$";
    let totalOpConv = 0, totalConConv = 0, totalGenConv = 0;

    if (vista === "MXN"){
      symbol = "$";
      totalOpConv  = opPesos  + (opDlls  * tc);
      totalConConv = conPesos + (conDlls * tc);
    } else {
      symbol = "US$";
      totalOpConv  = opDlls  + (opPesos  / tc);
      totalConConv = conDlls + (conPesos / tc);
    }
    totalGenConv = totalOpConv + totalConConv;

    if (costosOperacionTotalOperacion)    costosOperacionTotalOperacion.textContent    = formatMoneyGenericCostosOperacion(totalOpConv, symbol);
    if (costosOperacionTotalContenedores) costosOperacionTotalContenedores.textContent = formatMoneyGenericCostosOperacion(totalConConv, symbol);
    if (costosOperacionTotalGeneral)      costosOperacionTotalGeneral.textContent      = formatMoneyGenericCostosOperacion(totalGenConv, symbol);
  }
  function computeViewTotalsCostosOperacion(detCostos, detAbonos){
  // detCostos: {operacion:{PESOS,DLLS}, contenedores:{PESOS,DLLS}}
  // detAbonos: {operacion:{PESOS,DLLS}, contenedores:{PESOS,DLLS}}
  const vista = (costosOperacionMonedaVistaSel?.value || "MXN").toUpperCase();
  let tc = parseFloat(costosOperacionTipoCambioInp?.value || "0"); if (!Number.isFinite(tc) || tc<=0) tc = 1;

  const c = detCostos || {operacion:{PESOS:0,DLLS:0}, contenedores:{PESOS:0,DLLS:0}};
  const a = detAbonos || {operacion:{PESOS:0,DLLS:0}, contenedores:{PESOS:0,DLLS:0}};

  const opPesosC  = Number(c.operacion?.PESOS||0),  opDllsC  = Number(c.operacion?.DLLS||0);
  const conPesosC = Number(c.contenedores?.PESOS||0), conDllsC = Number(c.contenedores?.DLLS||0);

  const opPesosA  = Number(a.operacion?.PESOS||0),  opDllsA  = Number(a.operacion?.DLLS||0);
  const conPesosA = Number(a.contenedores?.PESOS||0), conDllsA = Number(a.contenedores?.DLLS||0);

  let opCost=0, contCost=0, opAbono=0, contAbono=0, symbol="$";

  if (vista === "MXN"){
    symbol = "$";
    opCost     = opPesosC  + (opDllsC  * tc);
    contCost   = conPesosC + (conDllsC * tc);
    opAbono    = opPesosA  + (opDllsA  * tc);
    contAbono  = conPesosA + (conDllsA * tc);
  } else {
    symbol = "US$";
    opCost     = opDllsC  + (opPesosC  / tc);
    contCost   = conDllsC + (conPesosC / tc);
    opAbono    = opDllsA  + (opPesosA  / tc);
    contAbono  = conDllsA + (conPesosA / tc);
  }

  const fmt = (n) => symbol + " " + Number(n).toLocaleString("es-MX",{minimumFractionDigits:2, maximumFractionDigits:2});
  return { opCost, contCost, opAbono, contAbono, fmt };
}


  // ---------- Render: paginación + meta ----------
  function renderPaginacionCostosOperacion(meta){
    if (!costosOperacionPaginacion) return;
    const page= parseInt(meta.page||1,10);
    const totalPages = parseInt(meta.totalPages||1,10);
    if (totalPages<=1){ costosOperacionPaginacion.innerHTML=""; return; }

    let start=Math.max(1,page-2), end=Math.min(totalPages,start+4); start=Math.max(1,end-4);
    let html = `
      <li class="page-item ${page===1?"disabled":""}">
        <a class="page-link" href="#" data-page="${page-1}">«</a>
      </li>`;
    for (let p=start; p<=end; p++){
      html += `
        <li class="page-item ${p===page?"active":""}">
          <a class="page-link" href="#" data-page="${p}">${p}</a>
        </li>`;
    }
    html += `
      <li class="page-item ${page===totalPages?"disabled":""}">
        <a class="page-link" href="#" data-page="${page+1}">»</a>
      </li>`;
    costosOperacionPaginacion.innerHTML = html;

    costosOperacionPaginacion.querySelectorAll("a.page-link").forEach(a=>{
      a.addEventListener("click",(e)=>{
        e.preventDefault();
        const p = parseInt(a.dataset.page,10);
        if (!Number.isNaN(p)) listarCostosOperacion(p);
      });
    });
  }

  function renderMetaCostosOperacion(meta){
    if (!costosOperacionMeta) return;
    const page  = parseInt(meta.page||1,10);
    const perPg = parseInt(meta.perPage||perPageCostosOperacion||10,10);
    const total = parseInt(meta.total||0,10);
    const ini   = total===0 ? 0 : ((page-1)*perPg)+1;
    const fin   = Math.min(page*perPg, total);
    costosOperacionMeta.textContent = `Mostrando ${ini}–${fin} de ${total}`;
  }

  // ---------- Listar ----------
  function listarCostosOperacion(page=1){
    currentPageCostosOperacion = page;

    // sin operación seleccionada: vacío
    if (!operacionIdCostosOperacion || operacionIdCostosOperacion<=0){
      renderVacioCostosOperacion();
      renderTotalesCostosOperacion({total_operacion:0,total_contenedores:0,total_general:0}, null);
      renderPaginacionCostosOperacion({page:1,totalPages:0});
      renderMetaCostosOperacion({page:1,perPage:perPageCostosOperacion,total:0});
      return;
    }

    const buscar = (costosOperacionBuscar?.value||"").trim();
    const moneda = (costosOperacionFiltroMoneda?.value||"").toUpperCase();
    const tipoId = parseInt(costosOperacionFiltroTipo?.value||"0",10) || 0;
    const origen = (costosOperacionFiltroOrigen?.value||"").toUpperCase();

    if (currentXHRCostosOperacion && currentXHRCostosOperacion.readyState!==4) currentXHRCostosOperacion.abort();
    renderCargandoCostosOperacion();

    const params = new URLSearchParams({
      page: String(currentPageCostosOperacion),
      perPage: String(perPageCostosOperacion),
      buscar,
      moneda,
      tipo: String(tipoId),
      operacion_id: String(operacionIdCostosOperacion),
      origen
    });

    const url = `${base_url}Operaciones_maritimas_costos_operacion/listarPaginado?${params.toString()}`;
    currentXHRCostosOperacion = new XMLHttpRequest();
    currentXHRCostosOperacion.open("GET", url, true);
    currentXHRCostosOperacion.send();
    currentXHRCostosOperacion.onreadystatechange = function(){
      
      if (this.readyState!==4) return;
      if (this.status!==200){ console.error(this.responseText); renderErrorCostosOperacion("No se pudo cargar la información."); return; }

      let payload;
      try{ payload = JSON.parse(this.responseText); } catch{ renderErrorCostosOperacion("Respuesta inválida del servidor."); return; }

      const data            = payload.data    || [];
      const meta            = payload.meta    || {page:1,totalPages:1,total:0,perPage:perPageCostosOperacion};
      const totales         = payload.totales || {total_operacion:0,total_contenedores:0,total_general:0};
      const totalesDetalle  = payload.totales_detalle || null;

      if (data.length===0 && meta.totalPages>0 && currentPageCostosOperacion>meta.totalPages){
        listarCostosOperacion(meta.totalPages);
        return;
      }

      renderTablaCostosOperacion(data);
      renderPaginacionCostosOperacion(meta);
      renderMetaCostosOperacion(meta);
      renderTotalesCostosOperacion(totales, totalesDetalle);
    const abonosDetalle   = payload.abonos_detalle || { operacion:{PESOS:0,DLLS:0}, contenedores:{PESOS:0,DLLS:0} };

abonosDetalleCacheCostosOperacion = abonosDetalle; // cacheamos
const { opCost, contCost, opAbono, contAbono, fmt } =
  computeViewTotalsCostosOperacion(totalesDetalleCacheCostosOperacion, abonosDetalleCacheCostosOperacion);

renderCostosAbonosCards({ opCost, contCost, opAbono, contAbono, fmt });
    
    };
  }

  // ---------- Eventos ----------
  document.addEventListener("DOMContentLoaded", ()=>{
    if (!perPageCostosOperacion || perPageCostosOperacion<1) perPageCostosOperacion = 10;
    if (operacionIdCostosOperacion>0) listarCostosOperacion(1);
  });

  costosOperacionPerPageSel?.addEventListener("change", ()=>{
    perPageCostosOperacion = parseInt(costosOperacionPerPageSel.value||"10",10);
    if (!perPageCostosOperacion || perPageCostosOperacion<1) perPageCostosOperacion = 10;
    listarCostosOperacion(1);
  });

  costosOperacionBuscar?.addEventListener("keyup",(e)=>{
    clearTimeout(debounceIdCostosOperacion);
    debounceIdCostosOperacion = setTimeout(()=>listarCostosOperacion(1), 250);
    if (e.key==="Enter"){ clearTimeout(debounceIdCostosOperacion); listarCostosOperacion(1); }
  });

  costosOperacionFiltroMoneda?.addEventListener("change",   ()=>listarCostosOperacion(1));
  costosOperacionFiltroTipo?.addEventListener("change",     ()=>listarCostosOperacion(1));
  costosOperacionFiltroOrigen?.addEventListener("change",   ()=>listarCostosOperacion(1));

  // Recalcular tarjetas al cambiar vista de moneda o tipo de cambio
costosOperacionMonedaVistaSel?.addEventListener("change", ()=>{
  renderTotalesCostosOperacion(null, totalesDetalleCacheCostosOperacion);
  const { opCost, contCost, opAbono, contAbono, fmt } =
    computeViewTotalsCostosOperacion(totalesDetalleCacheCostosOperacion, abonosDetalleCacheCostosOperacion);
  renderCostosAbonosCards({ opCost, contCost, opAbono, contAbono, fmt });
});

costosOperacionTipoCambioInp?.addEventListener("input", ()=>{
  renderTotalesCostosOperacion(null, totalesDetalleCacheCostosOperacion);
  const { opCost, contCost, opAbono, contAbono, fmt } =
    computeViewTotalsCostosOperacion(totalesDetalleCacheCostosOperacion, abonosDetalleCacheCostosOperacion);
  renderCostosAbonosCards({ opCost, contCost, opAbono, contAbono, fmt });
});

  // ---------- Autocomplete de Operación ----------
  function buscarOperacionesSugerenciasCostosOperacion(term){
    if (!opSugBox) return;
    if (!term || term.length<2){ opSugBox.style.display="none"; opSugBox.innerHTML=""; opMeta && (opMeta.textContent=""); return; }

    const url = `${base_url}Operaciones_maritimas_costos_operacion/buscarOperaciones?term=${encodeURIComponent(term)}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.send();
    xhr.onreadystatechange = function(){
      if (this.readyState!==4) return;
      if (this.status!==200){ opSugBox.style.display="none"; opSugBox.innerHTML=""; opMeta && (opMeta.textContent=""); return; }

      let rows = [];
      try{ rows = JSON.parse(this.responseText) || []; } catch{ rows = []; }

      if (!Array.isArray(rows) || rows.length===0){
        opSugBox.style.display="none"; opSugBox.innerHTML=""; opMeta && (opMeta.textContent="Sin resultados"); return;
      }

      let html = "";
      rows.forEach(r=>{
        const id  = r.id_operacion;
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
      opSugBox.innerHTML = html;
      opSugBox.style.display = "block";
      opMeta && (opMeta.textContent = `${rows.length} resultado(s)`);

      opSugBox.querySelectorAll("button.list-group-item").forEach(btn=>{
        btn.addEventListener("click", ()=>{
          const id  = parseInt(btn.dataset.id||"0",10) || 0;
          const nom = btn.dataset.nom || "";
          if (opIdInp)     opIdInp.value = String(id);
          if (opNombreInp) opNombreInp.value = nom;
          operacionIdCostosOperacion = id;
          opSugBox.innerHTML = "";
          opSugBox.style.display = "none";
          listarCostosOperacion(1);
        });
      });
    };
  }

  opNombreInp?.addEventListener("input", ()=>{
    const term = (opNombreInp.value||"").trim();
    if (opIdInp) opIdInp.value = "";
    operacionIdCostosOperacion = 0;
    buscarOperacionesSugerenciasCostosOperacion(term);
  });

  document.addEventListener("click",(e)=>{
    if (!opSugBox) return;
    if (!opSugBox.contains(e.target) && e.target !== opNombreInp){
      opSugBox.style.display = "none";
    }
  });

  // Exponer (opcional) para fijar operación desde fuera
  window.setOperacionIdCostosOperacion = function(opId){
    operacionIdCostosOperacion = parseInt(opId||"0",10) || 0;
    if (opIdInp) opIdInp.value = String(operacionIdCostosOperacion);
    if (operacionIdCostosOperacion>0) listarCostosOperacion(1);
  };
// Al final del primer IIFE:
window.listarCostosOperacion = listarCostosOperacion;
})();
 
// ---------- Modal: Autocomplete + Tipo->Moneda + Sync operación seleccionada ----------
(function(){
  "use strict";
  let isEditModeCostosOperacion = false;
  const modalElCostosOperacion       = document.getElementById("modalCostoOperacion");
  const opIdModalInpCostosOperacion  = document.getElementById("costosOperacionOpId");
  const opNomModalInpCostosOperacion = document.getElementById("costosOperacionOpNombre");
  const opSugModalBoxCostosOperacion = document.getElementById("costosOperacionOpSugerencias");

  const tipoSelCostosOperacion       = document.getElementById("costosOperacionTipo");
  const monedaSelCostosOperacion     = document.getElementById("costosOperacionMoneda");
  const montoInpCostosOperacion      = document.getElementById("costosOperacionMonto");
  const comentTAreaCostosOperacion   = document.getElementById("costosOperacionComentario");
  const formCostoOperacion           = document.getElementById("formCostoOperacion");

  // Refs del filtro superior (para copiar la operación si ya está elegida)
  const opIdFiltroInp                = document.getElementById("costosOperacionFiltroOpId");
  const opNombreFiltroInp            = document.getElementById("costosOperacionFiltroOpNombre");

  // Limpia el formulario
  function resetFormCostosOperacion(){
    formCostoOperacion?.reset();
    if (opIdModalInpCostosOperacion)  opIdModalInpCostosOperacion.value = "";
    if (opNomModalInpCostosOperacion) opNomModalInpCostosOperacion.value = "";
    if (opSugModalBoxCostosOperacion){ opSugModalBoxCostosOperacion.innerHTML = ""; opSugModalBoxCostosOperacion.style.display = "none"; }
    // deja "Moneda" deshabilitado, pero reseteado
    if (monedaSelCostosOperacion) monedaSelCostosOperacion.value = "";
     // limpiar row_id (valor actual y valor por defecto)
 const hidRow = document.getElementById("costosOperacionRowId");
 if (hidRow){
   hidRow.value = "";
   hidRow.removeAttribute("value"); // por si algún flujo lo dejó fijado
 }
  }

  // Copia operación del filtro superior si existe
  function seedOperacionDesdeFiltroCostosOperacion(){
    const filtroId  = parseInt(opIdFiltroInp?.value || "0", 10) || 0;
    const filtroNom = (opNombreFiltroInp?.value || "").trim();
    if (filtroId > 0 && filtroNom){
      if (opIdModalInpCostosOperacion)  opIdModalInpCostosOperacion.value  = String(filtroId);
      if (opNomModalInpCostosOperacion) opNomModalInpCostosOperacion.value = filtroNom;
    }
  }

  // Autocomplete dentro del modal (idéntico al de la cabecera, pero con IDs del modal)
  function buscarOperacionesModalCostosOperacion(term){
    if (!opSugModalBoxCostosOperacion) return;
    if (!term || term.length < 2){
      opSugModalBoxCostosOperacion.style.display = "none";
      opSugModalBoxCostosOperacion.innerHTML = "";
      return;
    }
    const url = `${base_url}Operaciones_maritimas_costos_operacion/buscarOperaciones?term=${encodeURIComponent(term)}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.send();
    xhr.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200){ opSugModalBoxCostosOperacion.style.display="none"; opSugModalBoxCostosOperacion.innerHTML=""; return; }

      let rows = [];
      try { rows = JSON.parse(this.responseText) || []; } catch { rows = []; }

      if (!Array.isArray(rows) || rows.length === 0){
        opSugModalBoxCostosOperacion.style.display = "none";
        opSugModalBoxCostosOperacion.innerHTML = "";
        return;
      }

      let html = "";
      rows.forEach(r=>{
        const id  = r.id_operacion;
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
      opSugModalBoxCostosOperacion.innerHTML = html;
      opSugModalBoxCostosOperacion.style.display = "block";

opSugModalBoxCostosOperacion.querySelectorAll("button.list-group-item").forEach(btn=>{
  btn.addEventListener("click", (e)=>{
    e.preventDefault();
    e.stopPropagation(); // <- evita que otro listener “afuera” toque el input de cabecera
    const id  = parseInt(btn.dataset.id || "0", 10) || 0;
    const nom = btn.dataset.nom || "";
    if (opIdModalInpCostosOperacion)  opIdModalInpCostosOperacion.value  = String(id);
    if (opNomModalInpCostosOperacion) opNomModalInpCostosOperacion.value = nom;
    opSugModalBoxCostosOperacion.innerHTML = "";
    opSugModalBoxCostosOperacion.style.display = "none";
  });
});
    };
  }

  // Auto-moneda según tipo seleccionado
  /*
function syncMonedaPorTipoCostosOperacion(m){
  const val = (m||"").toUpperCase();
  if (monedaSelCostosOperacion) monedaSelCostosOperacion.value = val;
  const hid = document.getElementById("costosOperacionMonedaHidden");
  if (hid) hid.value = val;
}*/
function syncMonedaPorTipoCostosOperacion(){
  if (!tipoSelCostosOperacion) return;
  const opt = tipoSelCostosOperacion.selectedOptions?.[0];
  const m   = opt ? (opt.getAttribute("data-moneda") || "").toUpperCase() : "";
  setMonedaCostosOperacion((m==="PESOS"||m==="DLLS")? m : "");
}


  // Eventos
  opNomModalInpCostosOperacion?.addEventListener("input", ()=>{
    // Si el usuario edita el nombre, reseteamos el ID hasta que seleccione de la lista
    if (opIdModalInpCostosOperacion) opIdModalInpCostosOperacion.value = "";
    buscarOperacionesModalCostosOperacion(opNomModalInpCostosOperacion.value.trim());
  });

  document.addEventListener("click", (e)=>{
    if (!opSugModalBoxCostosOperacion) return;
    if (!opSugModalBoxCostosOperacion.contains(e.target) &&
        e.target !== opNomModalInpCostosOperacion) {
      opSugModalBoxCostosOperacion.style.display = "none";
    }
  });

  tipoSelCostosOperacion?.addEventListener("change", syncMonedaPorTipoCostosOperacion);

  // Al abrir el modal: limpia, copia operación (si existe), y sincroniza moneda por tipo
  modalElCostosOperacion?.addEventListener("show.bs.modal", ()=>{
  // Solo resetea si es NUEVO. En edición no limpies para no borrar row_id.
  if (!isEditModeCostosOperacion) {
    resetFormCostosOperacion();
    seedOperacionDesdeFiltroCostosOperacion();
  }

  });

  modalElCostosOperacion?.addEventListener("shown.bs.modal", ()=>{
    syncMonedaPorTipoCostosOperacion();
    opNomModalInpCostosOperacion?.focus();
  });

  // (Aún no guardamos: solo llenado) – evita submit accidental
formCostoOperacion?.addEventListener("submit", (e)=>{
  e.preventDefault();

  // Validación
  const opId = parseInt(opIdModalInpCostosOperacion?.value || "0", 10) || 0;
  if (!opId){ alert("Selecciona una operación."); return; }

  // Asegura moneda en hidden
  syncMonedaPorTipoCostosOperacion();

  // Si es ALTA, limpia el hidden row_id ANTES de crear el FormData
  const hidRow = document.getElementById("costosOperacionRowId");
  if (!isEditModeCostosOperacion && hidRow) {
    hidRow.value = "";
    // (opcional) hidRow.removeAttribute("value");
  }

  // AHORA sí crea el FormData y úsalo
  const formData = new FormData(formCostoOperacion);
  formData.append("accion", isEditModeCostosOperacion ? "actualizar" : "crear");

  // (opcional) debug:
  // console.log('row_id ->', formData.get('row_id'), 'edit?', isEditModeCostosOperacion);

  const url = `${base_url}Operaciones_maritimas_costos_operacion/guardar`;
  const xhr = new XMLHttpRequest();
  xhr.open("POST", url, true);
  xhr.onreadystatechange = function(){
    if (this.readyState !== 4) return;
    if (this.status !== 200){
      console.error(this.responseText);
      alert("No se pudo guardar el costo.");
      return;
    }
    let resp = {};
    try { resp = JSON.parse(this.responseText) || {}; } catch { resp = {}; }

    if (resp.status === "success"){
      Swal.fire('Éxito', isEditModeCostosOperacion
        ? '✅ Costo actualizado correctamente.'
        : '✅ Costo registrado correctamente.', 'success');
      const bsModal = modalElCostosOperacion ? bootstrap.Modal.getInstance(modalElCostosOperacion) : null;
      bsModal?.hide();
      if (typeof listarCostosOperacion === "function") listarCostosOperacion(1);
    } else {
      Swal.fire('Error', resp.message || 'No se pudo guardar el costo.', 'error');
    }
  };
  xhr.send(formData);
});

 

 

// Sincroniza moneda visual (select) + hidden (para submit)
function setMonedaCostosOperacion(m){
  const val = (m||"").toUpperCase();
  if (monedaSelCostosOperacion) monedaSelCostosOperacion.value = val;
  const hid = document.getElementById("costosOperacionMonedaHidden");
  if (hid) hid.value = val;
}
/*
function syncMonedaPorTipoCostosOperacion(){
  if (!tipoSelCostosOperacion) return;
  const opt = tipoSelCostosOperacion.selectedOptions?.[0];
  const m   = opt ? (opt.getAttribute("data-moneda") || "").toUpperCase() : "";
  setMonedaCostosOperacion((m==="PESOS"||m==="DLLS")? m : "");
}*/

// Rellena el formulario con datos (para editar o para “sembrar” op desde el filtro)
function fillFormCostosOperacion({rowId="", opId="", opNom="", tipoId="", moneda="", monto="", comentario=""}={}){
  const hidRow = document.getElementById("costosOperacionRowId");
  if (hidRow){ hidRow.value = rowId; } // ← sin setAttribute

  if (opIdModalInpCostosOperacion)  opIdModalInpCostosOperacion.value  = opId;
  if (opNomModalInpCostosOperacion) opNomModalInpCostosOperacion.value = opNom;

  if (tipoSelCostosOperacion){
    tipoSelCostosOperacion.value = String(tipoId||"");
    syncMonedaPorTipoCostosOperacion();
  }
  if (moneda) setMonedaCostosOperacion(moneda);

  if (montoInpCostosOperacion)    montoInpCostosOperacion.value = (monto??"");
  if (comentTAreaCostosOperacion) comentTAreaCostosOperacion.value = comentario||"";
}


// Abrir modal en modo NUEVO
function openModalNuevoCostosOperacion(){
  isEditModeCostosOperacion = false;
  resetFormCostosOperacion();
   // doble seguro: sin row_id
 const hidRow = document.getElementById("costosOperacionRowId");
 if (hidRow){
   hidRow.value = "";
   hidRow.removeAttribute("value");
 }
  seedOperacionDesdeFiltroCostosOperacion();
  syncMonedaPorTipoCostosOperacion();
  const title = document.getElementById("modalCostoOperacionLabel");
  if (title) title.innerHTML = `<i data-feather="plus-circle" class="me-1"></i> Añadir Costo a Operación`;
  window.feather?.replace?.();
}

// Abrir modal en modo EDITAR (usamos data-* de la fila o pedimos al backend)
function openModalEditarCostosOperacion(rowEl){
  isEditModeCostosOperacion = true;
  resetFormCostosOperacion();

  // Puedes cargar directo del dataset (rápido) o, si prefieres, pedir con AJAX usando el row_id:
  fillFormCostosOperacion({
    rowId:    rowEl.dataset.rowId,
    opId:     rowEl.dataset.opId,
    opNom:    rowEl.dataset.opNom,
    tipoId:   rowEl.dataset.tipoId,
    moneda:   rowEl.dataset.moneda,
    monto:    rowEl.dataset.monto,
    comentario: rowEl.dataset.coment
  });

  const title = document.getElementById("modalCostoOperacionLabel");
  if (title) title.innerHTML = `<i data-feather="edit-2" class="me-1"></i> Editar Costo de Operación`;
  window.feather?.replace?.();

  // Mostrar modal programáticamente si lo necesitas:
  const modal = modalElCostosOperacion ? bootstrap.Modal.getOrCreateInstance(modalElCostosOperacion) : null;
  modal?.show();
}

// Click en “Nuevo”
document.getElementById("costosOperacionBtnNuevo")?.addEventListener("click", openModalNuevoCostosOperacion);

 
// EDITAR
document.getElementById("tbodyCostosOperacionCombined")?.addEventListener("click", (e)=>{
  const btn = e.target.closest(".btnEditarCostoOperacion");
  if (!btn) return;
  const tr = btn.closest("tr");
  if (!tr || tr.dataset.origen !== "OPERACION") return; // respeta tu regla
  // Esta función está dentro del IIFE del modal;
  // la exponemos más abajo con window.openModalEditarCostosOperacion
  window.openModalEditarCostosOperacion?.(tr);
});

// ELIMINAR
document.getElementById("tbodyCostosOperacionCombined")?.addEventListener("click", (e)=>{
  const btn = e.target.closest(".btnEliminarCostoOperacion");
  if (!btn) return;
  const tr = btn.closest("tr");
  if (!tr || tr.dataset.origen !== "OPERACION") return; // respeta tu regla
  const rowId = tr.dataset.rowId;
  Swal.fire({
    title: '¿Eliminar costo?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((r)=>{
    if (!r.isConfirmed) return;
    const fd = new FormData();
    fd.append("id", rowId);
    const xhr = new XMLHttpRequest();
    xhr.open("POST", base_url + "Operaciones_maritimas_costos_operacion/desactivarCostoOperacion", true);
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) { Swal.fire("Error", "Error HTTP " + xhr.status, "error"); return; }
      let resp={}; try{ resp=JSON.parse(xhr.responseText)||{} }catch{}
      if (resp.status === "success"){
        Swal.fire("Éxito", resp.message || "Costo eliminado", "success");
        window.listarCostosOperacion?.(1);
      } else {
        Swal.fire("Error", resp.message || "No se pudo eliminar", "error");
      }
    };
    xhr.send(fd);
  });
});
window.openModalEditarCostosOperacion = openModalEditarCostosOperacion;

})(); 
// Excel
  document.getElementById('btnExportarExcelCostosOperacion')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: 'tablaCostosOperacionExportar',       // "#tablaEventos" o el elemento también funciona
      formato: 'xlsx',
      nombre: 'CostosOperacion.xlsx',
      columnasOcultas: [7],      // oculta columna ID
      soloVisibles: true,
      sheetName: 'Costos Operacion'
    });
  });

  // PDF
  document.getElementById('btnExportarPDFCostosOperacion')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: '#tablaCostosOperacionExportar',
      formato: 'pdf',
      nombre: 'CostosOperacion.pdf',
      titulo: 'Costos Operacion',
      orientacion: 'landscape',  // o 'portrait'
      formatoPagina: 'letter',   // o 'a4'
      columnasOcultas: [7],
      soloVisibles: true
    });
  });
  // Llama esto cuando refresques los totales
function renderCostosAbonosCards({ 
  opCost = 0, opAbono = 0, 
  contCost = 0, contAbono = 0, 
  fmt = (n) => `$ ${Number(n).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}`
} = {}) {
  // Operación
  const opBalance = (opAbono - opCost);
  document.getElementById('costosOperacionTotalOperacion').textContent = fmt(opCost);
  document.getElementById('costosOperacionAbonosOperacion').textContent = fmt(opAbono);
  setBadgeValue('costosOperacionBalanceOperacion', opBalance, fmt);

  // Contenedores
  const contBalance = (contAbono - contCost);
  document.getElementById('costosOperacionTotalContenedores').textContent = fmt(contCost);
  document.getElementById('costosOperacionAbonosContenedores').textContent = fmt(contAbono);
  setBadgeValue('costosOperacionBalanceContenedores', contBalance, fmt);

  // General
  const totalAbonos = opAbono + contAbono;
  const totalCostos = opCost + contCost;
  const totalBalance = totalAbonos - totalCostos;

  document.getElementById('costosOperacionTotalGeneral').textContent = fmt(totalBalance);
  document.getElementById('costosOperacionTotalAbonosGeneral').textContent = fmt(totalAbonos);
  document.getElementById('costosOperacionTotalCostosGeneral').textContent = fmt(totalCostos);
}

// Badge con color dinámico (verde si >0, gris si 0, rojo si <0)
function setBadgeValue(id, val, fmt){
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = fmt(val);
  el.classList.remove('bg-light','text-dark','bg-danger','bg-success','bg-secondary');
  if (val > 0) {
    el.classList.add('bg-success');
  } else if (val < 0) {
    el.classList.add('bg-danger');
  } else {
    el.classList.add('bg-secondary');
  }
}
