// assets/js/modulosAdmin/bitacora.js

// =========================
// Refs y estado
// =========================
const tbodyLog         = document.getElementById("tbodyOperacionesLog");
const metaResumenLog   = document.getElementById("metaResumenLog");
const paginacionLog    = document.getElementById("paginacionLog");

const inpUsuarioLog    = document.getElementById("buscarUsuarioLog");
const inpOperacionLog  = document.getElementById("buscarOperacionLog");
const selAccionLog     = document.getElementById("filtroAccionLog");
const inpFechaIniLog   = document.getElementById("filtroFechaInicioLog");
const inpFechaFinLog   = document.getElementById("filtroFechaFinLog");
const selPerPageLog    = document.getElementById("perPageLog");
const btnRefrescarLog  = document.getElementById("btnRefrescarBitacora");

const btnExcelLog      = document.getElementById("btnExportarExcelLog");
const btnPDFLog        = document.getElementById("btnExportarPDFLog");

// Estado de paginación
let paginaActualLog = 1;
let totalPaginasLog = 1;
let debounceTimerLog = null;

// =========================
// Init
// =========================
window.addEventListener("DOMContentLoaded", function () {
  cargarBitacora(1);

  // Filtros con debounce para texto
  if (inpUsuarioLog) {
    inpUsuarioLog.addEventListener("keyup", () => {
      debounceRecargar(1);
    });
  }
  if (inpOperacionLog) {
    inpOperacionLog.addEventListener("keyup", () => {
      debounceRecargar(1);
    });
  }

  // Filtros directos
  if (selAccionLog) {
    selAccionLog.addEventListener("change", () => cargarBitacora(1));
  }
  if (inpFechaIniLog) {
    inpFechaIniLog.addEventListener("change", () => cargarBitacora(1));
  }
  if (inpFechaFinLog) {
    inpFechaFinLog.addEventListener("change", () => cargarBitacora(1));
  }
  if (selPerPageLog) {
    selPerPageLog.addEventListener("change", () => cargarBitacora(1));
  }

  if (btnRefrescarLog) {
    btnRefrescarLog.addEventListener("click", () => {
      cargarBitacora(paginaActualLog);
    });
  }

  // Exportar (solo arma la URL; el backend lo implementas después)
  if (btnExcelLog) {
    btnExcelLog.addEventListener("click", () => {
      const url = construirUrlListar("Bitacora/exportarExcel");
      window.location.href = url;
    });
  }
  if (btnPDFLog) {
    btnPDFLog.addEventListener("click", () => {
      const url = construirUrlListar("Bitacora/exportarPDF");
      window.location.href = url;
    });
  }
});

// =========================
// Helpers
// =========================

function debounceRecargar(pagina) {
  if (debounceTimerLog) clearTimeout(debounceTimerLog);
  debounceTimerLog = setTimeout(() => {
    cargarBitacora(pagina);
  }, 350);
}

// Construye la URL con los filtros actuales
function construirUrlListar(rutaRelativa) {
  const usuario   = inpUsuarioLog ? inpUsuarioLog.value.trim() : "";
  const operacion = inpOperacionLog ? inpOperacionLog.value.trim() : "";
  const accion    = selAccionLog ? (selAccionLog.value || "") : "";
  const fIni      = inpFechaIniLog ? (inpFechaIniLog.value || "") : "";
  const fFin      = inpFechaFinLog ? (inpFechaFinLog.value || "") : "";
  const perPage   = selPerPageLog ? (selPerPageLog.value || "10") : "10";

  const params = new URLSearchParams();
  if (usuario !== "")   params.append("usuario", usuario);
  if (operacion !== "") params.append("operacion", operacion);
  if (accion !== "")    params.append("accion", accion);
  if (fIni !== "")      params.append("fecha_desde", fIni);
  if (fFin !== "")      params.append("fecha_hasta", fFin);
  params.append("perPage", perPage);
  params.append("page", paginaActualLog.toString());

  return base_url + rutaRelativa + "?" + params.toString();
}

// Renderiza badge según tipo de acción
function renderAccionBadge(accion) {
  const acc = (accion || "").toLowerCase();

  if (acc === "creacion") {
    return `
      <span class="badge bg-success-subtle text-success border border-success-subtle">
        <i data-feather="plus-circle" class="me-1" style="width:14px;height:14px;"></i>
        ${acc}
      </span>
    `;
  }

  if (acc === "actualizacion" || acc === "actualización") {
    return `
      <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
        <i data-feather="edit-3" class="me-1" style="width:14px;height:14px;"></i>
        ${acc}
      </span>
    `;
  }

  if (acc === "eliminacion" || acc === "eliminación") {
    return `
      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
        <i data-feather="trash-2" class="me-1" style="width:14px;height:14px;"></i>
        ${acc}
      </span>
    `;
  }

  // Default genérico
  return `
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
      <i data-feather="zap" class="me-1" style="width:14px;height:14px;"></i>
      ${acc || "acción"}
    </span>
  `;
}

// =========================
// XHR principal
// =========================

function cargarBitacora(pagina) {
  paginaActualLog = pagina || 1;

  const usuario   = inpUsuarioLog ? inpUsuarioLog.value.trim() : "";
  const operacion = inpOperacionLog ? inpOperacionLog.value.trim() : "";
  const accion    = selAccionLog ? (selAccionLog.value || "") : "";
  const fIni      = inpFechaIniLog ? (inpFechaIniLog.value || "") : "";
  const fFin      = inpFechaFinLog ? (inpFechaFinLog.value || "") : "";
  const perPage   = selPerPageLog ? (selPerPageLog.value || "10") : "10";

  const params = new URLSearchParams();
  if (usuario !== "")   params.append("usuario", usuario);
  if (operacion !== "") params.append("operacion", operacion);
  if (accion !== "")    params.append("accion", accion);
  if (fIni !== "")      params.append("fecha_desde", fIni);
  if (fFin !== "")      params.append("fecha_hasta", fFin);
  params.append("perPage", perPage);
  params.append("page", paginaActualLog.toString());

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Bitacora/listar?" + params.toString(), true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error Bitacora/listar:", this.responseText);
        return;
      }
      let res;
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido Bitacora/listar:", this.responseText);
        return;
      }

      if (!res || res.success === false) {
        renderTablaBitacora([]);
        actualizarResumenBitacora(0, 0, 0);
        renderPaginacionBitacora(1, 1);
        return;
      }

      const rows       = Array.isArray(res.rows) ? res.rows : [];
      const total      = parseInt(res.total || 0, 10);
      const page       = parseInt(res.page || paginaActualLog, 10);
      const perPageRes = parseInt(res.perPage || perPage, 10);
      const totalPages = parseInt(res.totalPages || 1, 10);
      const from       = parseInt(res.from || 0, 10);
      const to         = parseInt(res.to || 0, 10);

      paginaActualLog = page;
      totalPaginasLog = totalPages;

      renderTablaBitacora(rows);
      actualizarResumenBitacora(from, to, total);
      renderPaginacionBitacora(totalPages, page);

      // Reemplazar íconos feather que se generaron en las filas
      if (typeof feather !== "undefined") {
        feather.replace();
      }
    }
  };
}

// =========================
// Render tabla / resumen / paginación
// =========================

function renderTablaBitacora(rows) {
  if (!tbodyLog) return;

  tbodyLog.innerHTML = "";

  if (!Array.isArray(rows) || rows.length === 0) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td colspan="6" class="text-center text-muted py-3">
        No se encontraron registros de bitácora.
      </td>
    `;
    tbodyLog.appendChild(tr);
    return;
  }

  rows.forEach((row) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");

    const idLog          = row.id_log ?? "";
    const opId           = row.operacion_id ?? "";
    const numOperacion   = row.numero_operacion || "";
    const usuarioNombre  = row.usuario_nombre || ("ID " + (row.usuario_id ?? ""));
    const accion         = row.accion || "";
    const descripcion    = row.descripcion || "";
    const fecha          = row.fecha || "";

    // Operación: mostramos número si existe, si no el id
    const opMostrar = numOperacion !== "" ? numOperacion : (opId !== "" ? opId : "-");

    tr.innerHTML = `
      <td class="text-center">${idLog}</td>
      <td class="text-center">${opMostrar}</td>
      <td>${usuarioNombre}</td>
      <td class="text-center">
        ${renderAccionBadge(accion)}
      </td>
      <td class="text-start">${descripcion}</td>
      <td>${fecha}</td>
    `;

    tbodyLog.appendChild(tr);
  });
}

function actualizarResumenBitacora(from, to, total) {
  if (!metaResumenLog) return;

  const f = parseInt(from || 0, 10);
  const t = parseInt(to || 0, 10);
  const tot = parseInt(total || 0, 10);

  if (tot === 0) {
    metaResumenLog.textContent = "Mostrando 0–0 de 0";
    return;
  }

  metaResumenLog.textContent = `Mostrando ${f}–${t} de ${tot}`;
}

function renderPaginacionBitacora(totalPages, currentPage) {
  if (!paginacionLog) return;

  paginacionLog.innerHTML = "";

  totalPages  = totalPages || 1;
  currentPage = currentPage || 1;

  // Prev
  const liPrev = document.createElement("li");
  liPrev.className = "page-item" + (currentPage <= 1 ? " disabled" : "");
  liPrev.innerHTML = `
    <button class="page-link" type="button" aria-label="Anterior">
      <span aria-hidden="true">&laquo;</span>
    </button>
  `;
  if (currentPage > 1) {
    liPrev.querySelector("button").addEventListener("click", () => {
      cargarBitacora(currentPage - 1);
    });
  }
  paginacionLog.appendChild(liPrev);

  // Ventana de páginas (máx 5)
  const maxLinks = 5;
  let start = Math.max(1, currentPage - 2);
  let end   = Math.min(totalPages, start + maxLinks - 1);
  if (end - start + 1 < maxLinks) {
    start = Math.max(1, end - maxLinks + 1);
  }

  for (let i = start; i <= end; i++) {
    const li = document.createElement("li");
    li.className = "page-item" + (i === currentPage ? " active" : "");
    li.innerHTML = `
      <button class="page-link" type="button">${i}</button>
    `;
    if (i !== currentPage) {
      li.querySelector("button").addEventListener("click", () => {
        cargarBitacora(i);
      });
    }
    paginacionLog.appendChild(li);
  }

  // Next
  const liNext = document.createElement("li");
  liNext.className = "page-item" + (currentPage >= totalPages ? " disabled" : "");
  liNext.innerHTML = `
    <button class="page-link" type="button" aria-label="Siguiente">
      <span aria-hidden="true">&raquo;</span>
    </button>
  `;
  if (currentPage < totalPages) {
    liNext.querySelector("button").addEventListener("click", () => {
      cargarBitacora(currentPage + 1);
    });
  }
  paginacionLog.appendChild(liNext);
}
