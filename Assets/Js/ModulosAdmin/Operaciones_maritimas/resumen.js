 
// =========================
// Refs + estado
// =========================
const selectContenedorResumen            = document.getElementById("selectContenedorResumen");
const inpBuscarOpResumen                 = document.getElementById("buscarOperacionResumen");
const boxSugsOpResumen                   = document.getElementById("sugerenciasOperacionResumen");
const btnRefResumen                      = document.getElementById("btnRefrescarResumen");

// Estado  
let operacionIdActivoResumen             = null;
let lastXHRContenedoresResumen           = null;
let lastXHRSugerenciasResumen            = null;
let lastXHRFaltantesResumen              = null;
let debounceTimerResumen                 = null;
let opLabelSeleccionadaResumen = null;

// =========================
// Helpers UI
// =========================
function setContenedoresLoadingResumen() {
  selectContenedorResumen.innerHTML = '<option value="">Cargando contenedores…</option>';
}
function setContenedoresEmptyResumen(msg = 'Sin contenedores') {
  selectContenedorResumen.innerHTML = `<option value="">${msg}</option>`;
  setExportPdfEnabledResumen(false);
}
function clearSugerenciasResumen() {
  boxSugsOpResumen.style.display = 'none';
  boxSugsOpResumen.innerHTML = '';
}
document.addEventListener('DOMContentLoaded', function(){
  CostosChart.init('costosChart', 'costosLeyenda');
  if (window.TimelineChart) TimelineChart.init('timelineChart');
});
function limpiarDetalleUIResumen() {
  document.getElementById('nombreContenedorResumen').textContent = '—';
  // Marítimo
  document.getElementById('puertoResumen').textContent           = '—';
  document.getElementById('etaContenedor').textContent           = '—';
  document.getElementById('etdContenedor').textContent           = '—';
  document.getElementById('blContenedor').textContent            = '—';
  document.getElementById('comentarioContenedor').textContent    = '—';
  // Ferro
  document.getElementById('arriboPuerto').textContent            = '—';
  document.getElementById('bultos').textContent                  = '—';
}
function setDetalleLoadingResumen() {
  document.getElementById('comentarioContenedor').textContent = 'Cargando…';
}
function resetOperacionSeleccionResumen(){
  // 1) Estado
  operacionIdActivoResumen = null;
  try { lastXHRContenedoresResumen?.abort(); } catch(e){}
  try { lastXHRSugerenciasResumen?.abort(); } catch(e){}
  try { lastXHRFaltantesResumen?.abort(); } catch(e){}

  // 2) UI de select de contenedores
  setContenedoresEmptyResumen('-- Selecciona una Operación --');

  // 3) Panel de detalle izq
  limpiarDetalleUIResumen();
  // Oculta ambos bloques tipo por neutralidad
  document.getElementById('bloqueMaritimo')?.classList.add('d-none');
  document.getElementById('bloqueFerro')?.classList.add('d-none');

  // 4) Documentos faltantes
  setDFHeaderResumen('Seleccione un contenedor…', 0);
  toggleDFResumen(false, false, false);
  dfListaResumen.innerHTML = '';

  // 5) Eventos (tabla + badge + timeline)
  setEventosEmptyResumen();
  setEventosBadgeResumen(0, 0);
  if (window.TimelineChart && typeof TimelineChart.setEventos === 'function') {
    TimelineChart.setEventos([]);
  }

  // 6) Costos (badge + lista + gráfico)
  setTotalCostos('—');
  if (listaCostos) listaCostos.innerHTML = '<li class="list-group-item text-muted">Sin costos</li>';
  if (window.CostosChart && typeof CostosChart.clear === 'function') {
    CostosChart.clear();  // ver parche de CostosChart abajo
  }

  // 7) PDF deshabilitado
  setExportPdfEnabledResumen(false);
}

// ---- Helpers de validación / estado (Resumen) ----
const btnPdfResumen = document.getElementById('btnExportPdfResumen');

function hasOperacionSeleccionadaResumen(){
  // Operación seleccionada y al menos un contenedor cargado/seleccionado
  const opt = selectContenedorResumen?.selectedOptions?.[0];
  return !!operacionIdActivoResumen && !!opt && !!opt.value;
}

function setExportPdfEnabledResumen(enabled){
  if (!btnPdfResumen) return;
  btnPdfResumen.disabled = !enabled;
  btnPdfResumen.classList.toggle('disabled', !enabled);
}

// Deshabilitado al inicio
document.addEventListener('DOMContentLoaded', () => {
  setExportPdfEnabledResumen(false);
});
// =========================
// Sugerencias (autocomplete)
// =========================
function renderSugerenciasResumen(itemsResumen) {
  if (!Array.isArray(itemsResumen) || itemsResumen.length === 0) { clearSugerenciasResumen(); return; }
  boxSugsOpResumen.innerHTML = '';
  itemsResumen.forEach(rowResumen => {
    const aResumen = document.createElement('a');
    aResumen.className   = 'list-group-item list-group-item-action';
    aResumen.href        = '#';
    aResumen.textContent = rowResumen.label; // "EN-06 — Cliente X"
    aResumen.addEventListener('click', (e) => {
      e.preventDefault();
      seleccionarSugerenciaResumen(rowResumen);
    });
    boxSugsOpResumen.appendChild(aResumen);
  });
  boxSugsOpResumen.style.display = 'block';
}
function normStrResumen(s){
  return String(s || '')
    .trim()
    .replace(/\s+/g, ' ')       // colapsa espacios
    .toUpperCase();             // compara sin sensibilidad a mayúsculas
}

function isInputSyncedResumen(){
  // true si el texto visible del input coincide con la última etiqueta elegida
  const a = normStrResumen(inpBuscarOpResumen.value);
  const b = normStrResumen(opLabelSeleccionadaResumen);
  return !!a && a === b;
}

function doSearchSugerenciasResumen(termResumen) {
  if (lastXHRSugerenciasResumen) { try { lastXHRSugerenciasResumen.abort(); } catch(e){} }
  const httpResumen = new XMLHttpRequest();
  lastXHRSugerenciasResumen = httpResumen;

  httpResumen.open("GET", base_url + "operaciones_maritimas_resumen/sugerencias?term=" + encodeURIComponent(termResumen), true);
  httpResumen.onreadystatechange = function() {
    if (this.readyState !== 4) return;
    if (this.status === 200) {
      let resResumen; try { resResumen = JSON.parse(this.responseText); } catch { resResumen = null; }
      if (!resResumen || resResumen.status !== 'ok') { clearSugerenciasResumen(); return; }
      renderSugerenciasResumen(resResumen.data);
    } else {
      clearSugerenciasResumen();
    }
  };
  httpResumen.send();
}

inpBuscarOpResumen.addEventListener('input', function() {
  const termResumen = this.value.trim();
  clearTimeout(debounceTimerResumen);

  // si quedó vacío → reset total
  if (termResumen.length === 0) {
    resetOperacionSeleccionResumen();
    opLabelSeleccionadaResumen = null; // 👈 también olvidamos el label
    clearSugerenciasResumen();
    return;
  }

  // si el texto ya NO coincide con el label seleccionado → desincroniza (reset sin borrar el input)
  if (operacionIdActivoResumen && !isInputSyncedResumen()) {
    // no tocamos el valor del input, solo limpiamos los datos derivados
    resetOperacionSeleccionResumen();
    // importante: NO poner opLabelSeleccionadaResumen = null aquí,
    // así si el usuario vuelve a escribir idéntico, igual seguiremos pidiendo sugerencias;
    // la reselcción vendrá desde el clic en sugerencia.
  }

  if (termResumen.length < 2) { 
    clearSugerenciasResumen(); 
    return; 
  }
  debounceTimerResumen = setTimeout(() => doSearchSugerenciasResumen(termResumen), 250);
});

inpBuscarOpResumen.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    inpBuscarOpResumen.value = '';
    clearSugerenciasResumen();
    resetOperacionSeleccionResumen();
  }
});


document.addEventListener('click', (e) => {
  if (!boxSugsOpResumen.contains(e.target) && e.target !== inpBuscarOpResumen) {
    clearSugerenciasResumen();
  }
});
inpBuscarOpResumen.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') clearSugerenciasResumen();
  opLabelSeleccionadaResumen = null;
});

// Elegir operación de las sugerencias
function seleccionarSugerenciaResumen(rowResumen) {
  inpBuscarOpResumen.value = rowResumen.label;
  operacionIdActivoResumen = String(rowResumen.id);
  opLabelSeleccionadaResumen = rowResumen.label;
  cargarContenedoresResumen(operacionIdActivoResumen);
  clearSugerenciasResumen();
  setExportPdfEnabledResumen(true);
}

// =========================
// Contenedores por operación
// =========================
function cargarContenedoresResumen(operacionIdResumen) {
  if (lastXHRContenedoresResumen) { try { lastXHRContenedoresResumen.abort(); } catch(e){} }
  setContenedoresLoadingResumen();

  const httpResumen = new XMLHttpRequest();
  lastXHRContenedoresResumen = httpResumen;

  httpResumen.open(
    "GET",
    base_url + "operaciones_maritimas_resumen/listarContenedoresPorOperacion?id_operacion=" + encodeURIComponent(operacionIdResumen),
    true
  );
  httpResumen.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status === 200) {
      console.log('Respuesta contenedores:', this.responseText);
      let resResumen; try { resResumen = JSON.parse(this.responseText); } catch { resResumen = null; }
      if (!resResumen || (resResumen.status && resResumen.status !== 'ok')) {
        setContenedoresEmptyResumen('No se pudieron cargar contenedores');
        return;
      }
      renderContenedoresResumen(resResumen);
    } else {
      setContenedoresEmptyResumen('Error al cargar contenedores');
    }
  };
  httpResumen.send();
}

function renderContenedoresResumen(resResumen) {
  selectContenedorResumen.innerHTML = "";

  const dataResumen = Array.isArray(resResumen.contenedores) ? resResumen.contenedores
                    : Array.isArray(resResumen.data)         ? resResumen.data
                    : [];

  if (!dataResumen || dataResumen.length === 0) { setContenedoresEmptyResumen(); return; }

  dataResumen.forEach(cResumen => {
    const optionResumen = document.createElement("option");
    // value = ID PIVOT si viene; si no, usa id_contenedor (base)
    optionResumen.value = cResumen.id_pivot ?? cResumen.id_contenedor ?? '';
    optionResumen.textContent = `${cResumen.tipo_contenedor} · ${cResumen.numero_contenedor}`;

    optionResumen.dataset.tipo    = (cResumen.tipo_contenedor || '').toUpperCase(); // "MARITIMO" | "FERRO"
    optionResumen.dataset.fm      = (cResumen.fm_tipo || '').toUpperCase();         // "M" | "F" (si viene)
    optionResumen.dataset.numero  = cResumen.numero_contenedor || '';
    optionResumen.dataset.baseId  = cResumen.id_contenedor ?? ''; // <-- ID BASE (id_fisico | id_contenedor_maritimo)
    optionResumen.dataset.pivotId = cResumen.id_pivot ?? '';      // <-- ID PIVOT (co.id_contenedor | cmo.id)

    selectContenedorResumen.appendChild(optionResumen);
  });

  if (selectContenedorResumen.options.length > 0) {
    selectContenedorResumen.selectedIndex = 0;
    consultarDetallesContenedorResumen();
  }
}


// =========================
// Detalle del contenedor
// =========================
selectContenedorResumen.addEventListener('change', consultarDetallesContenedorResumen);

if (btnRefResumen) btnRefResumen.addEventListener('click', (e) => {
  e.preventDefault();
  consultarDetallesContenedorResumen();
});

function consultarDetallesContenedorResumen() {
   const optResumen = selectContenedorResumen.options[selectContenedorResumen.selectedIndex];
  if (!optResumen || !operacionIdActivoResumen) return;

  const tipoUIResumen = (optResumen.dataset.tipo || '').toUpperCase();
  const tipoFMResumen = mapTipoToFMResumen(tipoUIResumen, optResumen.dataset.fm); // 'M' | 'F'
  const idPivotResumen = optResumen.value;                 // pivot (co.id_contenedor | cmo.id)
  const idBaseResumen  = optResumen.dataset.baseId || '';  // base (id_fisico | id_contenedor_maritimo)
  const numeroResumen  = optResumen.dataset.numero || optResumen.textContent || '—';

  // Cabecera contenedor en “Detalle”
  document.getElementById('nombreContenedorResumen').textContent = numeroResumen;

  // Mostrar/ocultar bloques por tipo UI
  const esMaritimoResumen = (tipoFMResumen === 'M');
  document.getElementById('bloqueMaritimo').classList.toggle('d-none', !esMaritimoResumen);
  document.getElementById('bloqueFerro').classList.toggle('d-none', esMaritimoResumen);

  // Limpia y pone "Cargando…" en el panel
  limpiarDetalleUIResumen();
  setDetalleLoadingResumen();

  const opt = selectContenedorResumen.options[selectContenedorResumen.selectedIndex];
  const tipoUI = (opt.dataset.tipo || '').toUpperCase();

  if (tipoUI.startsWith('FERRO')) {
    const idFisico = opt.dataset.idFisico || opt.value; // preferimos dataset
    const operacionId = operacionIdActivoResumen;
    fetchCostosTotalesFisico(operacionId, idFisico);
    fetchCostosDesglosadosFisico(operacionId, idFisico);
    CostosChart.update({ tipo: 'F', operacionId: Number(operacionIdActivoResumen), idFisico: Number(idFisico) });
  } else {
    // Marítimo: de momento no mostramos costos por contenedor
    setTotalCostos('—');
    if (listaCostos) listaCostos.innerHTML = '<li class="list-group-item text-muted">No aplica (Marítimo)</li>';
     CostosChart.update({ tipo: 'M', operacionId: Number(operacionIdActivoResumen) });
  }

  // ====> 1) Detalle del contenedor
  const httpResumen = new XMLHttpRequest();
  const urlResumen = `${base_url}operaciones_maritimas_resumen/detalles_contenedor`
    + `?operacion_id=${encodeURIComponent(operacionIdActivoResumen)}`
    + `&tipo=${encodeURIComponent(esMaritimoResumen ? 'MARITIMO' : 'FERRO')}`
    + `&id_contenedor=${encodeURIComponent(idPivotResumen)}`; // El endpoint debe aceptar pivot para resolver detalle
  httpResumen.open('GET', urlResumen, true);
  httpResumen.onreadystatechange = function() {
    if (this.readyState !== 4) return;
    if (this.status === 200) {
      let resDetResumen; try { resDetResumen = JSON.parse(this.responseText); } catch { resDetResumen = null; }
      if (resDetResumen && resDetResumen.status === 'ok' && resDetResumen.data) {
        pintarDetalleContenedorResumen(esMaritimoResumen ? 'MARITIMO' : 'FERRO', resDetResumen.data);
      }
    }
  };
  httpResumen.send();

  // ====> 2) FALTANTES (nuevo endpoint sencillo)
  const etiquetaTextoResumen = `${esMaritimoResumen ? 'Contenedor' : 'Ferro'} ${numeroResumen}`;
  cargarFaltantesResumen(operacionIdActivoResumen, tipoFMResumen, idPivotResumen, etiquetaTextoResumen);
  // Cargar progreso eventos (para la card)
  fetchEventosProgresoResumen(operacionIdActivoResumen, tipoFMResumen, idPivotResumen);
cargarEventosResumen(
    operacionIdActivoResumen,
    (tipoFMResumen === 'M' ? 'MARITIMO' : 'FERRO'),
    idBaseResumen || idPivotResumen // fallback si por algo no vino baseId
  );
}

function pintarDetalleContenedorResumen(tipoResumen, dataResumen) {
  if (tipoResumen === 'MARITIMO') {
    document.getElementById('nombreContenedorResumen').textContent = dataResumen.numero_contenedor || '—';
    document.getElementById('puertoResumen').textContent           = dataResumen.puerto || '—';
    document.getElementById('etaContenedor').textContent           = dataResumen.eta || '—';
    document.getElementById('etdContenedor').textContent           = dataResumen.etd || '—';
    document.getElementById('blContenedor').textContent            = dataResumen.bl || '—';
    document.getElementById('comentarioContenedor').textContent    = dataResumen.comentarios || '—';
  } else {
    document.getElementById('nombreContenedorResumen').textContent = dataResumen.numero_ferro || '—';
    document.getElementById('arriboPuerto').textContent            = dataResumen.arribo_puerto || 'Falta Registrar Arribo';
    document.getElementById('bultos').textContent                  = (dataResumen.bultos != null ? dataResumen.bultos : '—');
    document.getElementById('comentarioContenedor').textContent    = dataResumen.comentarios || '—';
  }
}

// =========================
// Faltantes (card + lista)
// =========================
const dfContenedorInfoResumen = document.getElementById('dfContenedorInfo');
const dfBadgeCountResumen     = document.getElementById('dfBadgeCount');
const dfLoadingResumen        = document.getElementById('dfLoading');
const dfEmptyResumen          = document.getElementById('dfEmpty');
const dfListaResumen          = document.getElementById('dfLista');
const docsPendientesResumen   = document.getElementById('docsPendientesResumen');

function toggleDFResumen(loadingResumen=false, hasDataResumen=false, emptyResumen=false){
  dfLoadingResumen.style.display = loadingResumen ? '' : 'none';
  dfListaResumen.style.display   = hasDataResumen ? '' : 'none';
  dfEmptyResumen.style.display   = emptyResumen ? '' : 'none';
}

function setDFHeaderResumen(infoTextoResumen, countResumen=0){
  dfContenedorInfoResumen.textContent = infoTextoResumen || 'Seleccione un contenedor…';
  dfBadgeCountResumen.textContent     = String(countResumen || 0);
  docsPendientesResumen.textContent   = String(countResumen || 0); // número grande de la tarjeta
}

function escapeHtmlResumen(sResumen){
  return String(sResumen ?? '').replace(/[&<>"']/g, m => (
    { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[m]
  ));
}

// Mapea tipo UI a 'F'/'M'; si ya viene fm ('F'|'M'), úsalo directo
function mapTipoToFMResumen(tipoUIResumen, fmResumen){
  const fmCleanResumen = (fmResumen || '').toUpperCase();
  if (fmCleanResumen === 'F' || fmCleanResumen === 'M') return fmCleanResumen;

  const tResumen = (tipoUIResumen || '').toUpperCase();
  if (tResumen === 'M' || tResumen === 'MARITIMO' || tResumen === 'MARÍTIMO') return 'M';
  if (tResumen === 'F' || tResumen === 'FERRO' || tResumen === 'FISICO' || tResumen === 'FÍSICO') return 'F';
  return null;
}

function renderFaltantesResumen(itemsResumen){
  dfListaResumen.innerHTML = '';

  const countResumen = Array.isArray(itemsResumen) ? itemsResumen.length : 0;
  if (countResumen === 0){
    toggleDFResumen(false, false, true);
    setDFHeaderResumen(dfContenedorInfoResumen.textContent, 0);
    return;
  }

  itemsResumen.forEach(rowResumen => {
    const liResumen = document.createElement('li');
    liResumen.className = 'list-group-item d-flex justify-content-between align-items-center';
    const nombreResumen = escapeHtmlResumen(rowResumen.nombre);
    const claveResumen  = escapeHtmlResumen(rowResumen.clave ?? '');
    liResumen.innerHTML = `<span>${nombreResumen}</span><span class="badge bg-light text-dark">${claveResumen}</span>`;
    dfListaResumen.appendChild(liResumen);
  });

  toggleDFResumen(false, true, false);
  setDFHeaderResumen(dfContenedorInfoResumen.textContent, countResumen);
}

function cargarFaltantesResumen(operacionIdResumen, tipoFMResumen, idPivotResumen, etiquetaTextoResumen){
  if (!operacionIdResumen || !tipoFMResumen || !idPivotResumen){
    setDFHeaderResumen('Seleccione un contenedor…', 0);
    toggleDFResumen(false, false, false);
    dfListaResumen.innerHTML = '';
    return;
  }

  // Header e indicadores
  setDFHeaderResumen(etiquetaTextoResumen || '—', 0);
  toggleDFResumen(true, false, false);

  if (lastXHRFaltantesResumen){ try{ lastXHRFaltantesResumen.abort(); }catch(e){} }

  const httpResumen = new XMLHttpRequest();
  lastXHRFaltantesResumen = httpResumen;

  const urlResumen = `${base_url}operaciones_maritimas_resumen/faltantes`
    + `?operacion_id=${encodeURIComponent(operacionIdResumen)}`
    + `&contenedor_id=${encodeURIComponent(idPivotResumen)}` // PIVOT
    + `&tipo=${encodeURIComponent(tipoFMResumen)}`;

  httpResumen.open('GET', urlResumen, true);
  httpResumen.onreadystatechange = function(){
    if (this.readyState !== 4) return;

    if (this.status === 200){
      let dataResumen; 
      try { dataResumen = JSON.parse(this.responseText); } catch { dataResumen = null; }
      renderFaltantesResumen(Array.isArray(dataResumen) ? dataResumen : []);
    } else {
      renderFaltantesResumen([]); // en error, muestra vacío
    }
  };
  httpResumen.send();
}
 
const badgeTotalCostos = document.getElementById('badgeTotalCostos'); // <span> del total
function setTotalCostos(v){ if (badgeTotalCostos) badgeTotalCostos.textContent = String(v ?? '—'); }

// (opcional) donde pintar el desglose: ul/tabla
const listaCostos = document.getElementById('listaCostosContenedor'); 
function renderCostosDesglosados(rows){
  if (!listaCostos) return;
  listaCostos.innerHTML = '';
  if (!Array.isArray(rows) || rows.length === 0){
    listaCostos.innerHTML = '<li class="list-group-item text-muted">Sin costos</li>';
    return;
  }
  rows.forEach(r => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between';
    li.innerHTML = `<span>#${r.id_costo_contenedor} · ${r.comentario ?? ''}</span><strong>${Number(r.monto).toFixed(2)}</strong>`;
    listaCostos.appendChild(li);
  });
}

function fetchCostosTotalesFisico(operacionId, idFisico){
  setTotalCostos('…');
  const url = `${base_url}operaciones_maritimas_resumen/costos_totales_contenedor_fisico?operacion_id=${encodeURIComponent(operacionId)}&id_fisico=${encodeURIComponent(idFisico)}`;
  const xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.onreadystatechange = function(){
    if (xhr.readyState !== 4) return;
    if (xhr.status !== 200) { setTotalCostos('—'); return; }
    let r; try { r = JSON.parse(xhr.responseText); } catch { setTotalCostos('—'); return; }
    if (r.status !== 'ok') { setTotalCostos('—'); return; }
    const data = r.data || {};
    const total = typeof data.total === 'number' ? data.total : 0;
    const totalFmt = data.total_fmt ?? total.toFixed(2);
    setTotalCostos(totalFmt);
  };
  xhr.send();
}

function fetchCostosDesglosadosFisico(operacionId, idFisico){
  if (listaCostos) listaCostos.innerHTML = '<li class="list-group-item text-muted">Cargando…</li>';
  const url = `${base_url}operaciones_maritimas_resumen/costos_desglosados_contenedor_fisico?operacion_id=${encodeURIComponent(operacionId)}&id_fisico=${encodeURIComponent(idFisico)}`;
  const xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.onreadystatechange = function(){
    if (xhr.readyState !== 4) return;
    if (xhr.status !== 200) { renderCostosDesglosados([]); return; }
    let r; try { r = JSON.parse(xhr.responseText); } catch { renderCostosDesglosados([]); return; }
    if (r.status !== 'ok') { renderCostosDesglosados([]); return; }
    renderCostosDesglosados(r.data);
  };
  xhr.send();
}
const selMoneda = document.getElementById('costosResumenMonedaVista');
const inpTC     = document.getElementById('costosResumenTipoCambio');

selMoneda.addEventListener('change', () => {
  CostosChart.setDisplayCurrency(selMoneda.value, Number(inpTC.value));
});
inpTC.addEventListener('input', () => {
  CostosChart.setDisplayCurrency(selMoneda.value, Number(inpTC.value));
});
// ====== Eventos logísticos (tabla) ======
const tbodyEventosResumen = document.getElementById('tablaEventosLogisticos');

function setEventosLoadingResumen() {
  if (!tbodyEventosResumen) return;
  tbodyEventosResumen.innerHTML = `
    <tr><td colspan="2" class="text-muted">Cargando eventos…</td></tr>
  `;
}

function setEventosEmptyResumen() {
  if (!tbodyEventosResumen) return;
  tbodyEventosResumen.innerHTML = `
    <tr><td colspan="2" class="text-muted">Sin eventos</td></tr>
  `;
}

function fmtFechaResumen(isoLike){
  if (!isoLike) return '—';
  const s = String(isoLike).trim();
  const [y,m,dRest] = s.split('-');
  if (!y || !m || !dRest) return s;

  let d = dRest, h='00:00';
  if (dRest.includes(' ')) {
    const [dd, hh] = dRest.split(' ');
    d = dd; h = (hh || '00:00').slice(0,5);
  }
  return `${d}/${m}/${y}${h ? ' '  : ''}`;
}

function renderEventosResumen(rows){
  if (!tbodyEventosResumen) return;
  if (!Array.isArray(rows) || rows.length === 0) { setEventosEmptyResumen(); return; }

  const frag = document.createDocumentFragment();
  rows.forEach(r => {
    const tr = document.createElement('tr');
    const tdF = document.createElement('td');
    const tdE = document.createElement('td');
    tdF.textContent = fmtFechaResumen(r.fecha);
    tdE.textContent = r.nombre_evento || '(sin nombre)';
    tr.appendChild(tdF);
    tr.appendChild(tdE);
    frag.appendChild(tr);
  });
  tbodyEventosResumen.innerHTML = '';
  tbodyEventosResumen.appendChild(frag);
}

function buildUrlEventosResumen(operacionId, tipoUi, idBase){
  // Tu controlador normaliza: 'Ferro' / 'Maritimo'
  const t = (tipoUi || '').toUpperCase();
  const tipoParam = (t.startsWith('F')) ? 'Ferro' : 'Maritimo';
  return `${base_url}operaciones_maritimas_resumen/eventos_contenedor`
       + `?operacion_id=${encodeURIComponent(operacionId)}`
       + `&tipo=${encodeURIComponent(tipoParam)}`
       + `&id_contenedor=${encodeURIComponent(idBase)}`; // id_fisico o id_contenedor_maritimo
}

function cargarEventosResumen(operacionId, tipoUi, idBase){
  if (!tbodyEventosResumen) return;
  if (!operacionId || !tipoUi || !idBase) { setEventosEmptyResumen(); return; }

  setEventosLoadingResumen();

  const xhr = new XMLHttpRequest();
  xhr.open('GET', buildUrlEventosResumen(operacionId, tipoUi, idBase), true);
  xhr.onreadystatechange = function(){
    if (xhr.readyState !== 4) return;
    if (xhr.status !== 200) { setEventosEmptyResumen(); if (window.TimelineChart) TimelineChart.setEventos([]); return; }

    let r; try { r = JSON.parse(xhr.responseText); } catch { setEventosEmptyResumen(); if (window.TimelineChart) TimelineChart.setEventos([]); return; }

    if (r.status === 'ok' && Array.isArray(r.data)) {
      renderEventosResumen(r.data);
      if (window.TimelineChart) TimelineChart.setEventos(r.data);   // <— ACTUALIZA GRÁFICO AQUÍ
    } else {
      setEventosEmptyResumen();
      if (window.TimelineChart) TimelineChart.setEventos([]);
    }
  };
  xhr.send();
}
const badgeEventosResumen = document.getElementById('badgeEventosResumen');

function setEventosBadgeResumen(completados, total){
  if (!badgeEventosResumen) return;
  badgeEventosResumen.textContent = `${Number(completados||0)} / ${Number(total||0)}`;
}

function buildUrlEventosProgresoResumen(operacionId, tipoUi, idBase){
  // tipoUi: 'M' | 'F'
  return `${base_url}operaciones_maritimas_resumen/eventos_progreso`
       + `?operacion_id=${encodeURIComponent(operacionId)}`
       + `&tipo=${encodeURIComponent(tipoUi)}`
       + `&id_contenedor=${encodeURIComponent(idBase)}`;
}

function fetchEventosProgresoResumen(operacionId, tipoUi, idBase){
  if (!operacionId || !tipoUi || !idBase){ setEventosBadgeResumen(0,0); return; }
  const xhr = new XMLHttpRequest();
  xhr.open('GET', buildUrlEventosProgresoResumen(operacionId, tipoUi, idBase), true);
  xhr.onreadystatechange = function(){
    if (xhr.readyState !== 4) return;
    if (xhr.status !== 200){ setEventosBadgeResumen(0,0); return; }
    let r; try { r = JSON.parse(xhr.responseText); } catch { setEventosBadgeResumen(0,0); return; }
    if (r.status === 'ok' && r.data){
      setEventosBadgeResumen(r.data.completados, r.data.total);
    } else {
      setEventosBadgeResumen(0,0);
    }
  };
  xhr.send();
}



