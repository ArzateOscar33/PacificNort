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
  const symbol = (String(moneda).toUpperCase() === "DLLS") ? "US$" : "$";
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
    moneda,    // 'PESOS' | 'DLLS' | ''
    tipo: String(tipoId) // id_tipo_movimiento
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
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${ccSafe(r.numero_operacion)}</td>
      <td>${ccSafe(r.contenedor)}</td>
      <td>${ccSafe(r.concepto)}</td>
      <td>${moneyWithSymbolCostosContenedor(r.monto, r.moneda)}</td>
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
      <a class="page-link" href="#" data-page="${page - 1}">Anterior</a>
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
      <a class="page-link" href="#" data-page="${page + 1}">Siguiente</a>
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
