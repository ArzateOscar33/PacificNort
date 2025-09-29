// Referencias
const tablaDetallesLogisticos = document.getElementById("tbodyDetallesLogisticos");
const fldContenedorTipoEventosLogisticos = document.getElementById("eventoContenedorTipo");

const perPageEventosLogisticos    = document.getElementById("detPerPage");
const paginacionEventosLogisticos = document.getElementById("paginacionDetalles");
const resumenEventosLogisticos    = document.getElementById("detMetaResumen");
const filtroOpIdEventosLogisticos   = document.getElementById("eventosFiltroOpId");
const filtroContIdEventosLogisticos = document.getElementById("eventosFiltroContenedorId");
const buscarEventosLogisticos       = document.getElementById("buscarDetalles");

// El contenedor visible del modal: SIEMPRE readonly
const contTxtModal = document.getElementById("eventoContenedorNombre");
if (contTxtModal) {
  contTxtModal.readOnly = true;
  contTxtModal.placeholder = "Se autollenará al elegir la operación";
}

let paginaEventosLogisticos = 1; // página actual

listarEventoLogistico();

// LISTAR (GET)
function listarEventoLogistico() {
  const perPage = parseInt(perPageEventosLogisticos.value || "10", 10);
  const opId    = filtroOpIdEventosLogisticos.value || "";
  const contId  = filtroContIdEventosLogisticos.value || "";
  const q       = buscarEventosLogisticos.value ? buscarEventosLogisticos.value.trim() : "";

  const params = [];
  params.push("page=" + paginaEventosLogisticos);
  params.push("per_page=" + perPage);
  if (opId)   params.push("op_id=" + encodeURIComponent(opId));
  if (contId) params.push("cont_id=" + encodeURIComponent(contId));
  if (q)      params.push("q=" + encodeURIComponent(q));

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "operaciones_maritimas_eventos/listar?" + params.join("&"), true);
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;
    if (this.status !== 200) { console.error(this.responseText); return; }

    let res;
    try { res = JSON.parse(this.responseText); }
    catch { res = { data: [], total: 0, page: 1, per_page: perPage }; }

    renderTablaEventoLogistico(res.data || []);
    renderPaginacionEventosLogisticos(res.total || 0, res.page || 1, res.per_page || perPage);
  };
  http.send();
}

function renderPaginacionEventosLogisticos(total, page, perPage) {
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const start = total === 0 ? 0 : ((page - 1) * perPage + 1);
  const end   = Math.min(total, page * perPage);
  resumenEventosLogisticos.textContent = `Mostrando ${start}–${end} de ${total}`;

  paginacionEventosLogisticos.innerHTML = "";

  function addItemEventosLogisticos(label, targetPage, disabled, active) {
    const li = document.createElement("li");
    li.className = "page-item" + (disabled ? " disabled" : "") + (active ? " active" : "");
    const a = document.createElement("a");
    a.className = "page-link";
    a.href = "#";
    a.textContent = label;
    if (!disabled && !active) {
      a.addEventListener("click", function (e) {
        e.preventDefault();
        paginaEventosLogisticos = targetPage;
        listarEventoLogistico();
      });
    }
    li.appendChild(a);
    paginacionEventosLogisticos.appendChild(li);
  }

  addItemEventosLogisticos("«", Math.max(1, page - 1), page === 1, false);

  const windowSize = 5;
  let startPage = Math.max(1, page - Math.floor(windowSize / 2));
  let endPage   = Math.min(totalPages, startPage + windowSize - 1);
  if (endPage - startPage < windowSize - 1) startPage = Math.max(1, endPage - windowSize + 1);

  for (let p = startPage; p <= endPage; p++) {
    addItemEventosLogisticos(String(p), p, false, p === page);
  }

  addItemEventosLogisticos("»", Math.min(totalPages, page + 1), page === totalPages, false);
}

// RENDER
function renderTablaEventoLogistico(data) {
  tablaDetallesLogisticos.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tablaDetallesLogisticos.innerHTML = "<tr><td colspan='6' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${item.evento ?? ""}</td>
      <td>${item.fecha ?? ""}</td>
      <td>${item.operacion ?? ""}</td> 
      <td>${item.contenedor ?? ""}</td>
      <td>${item.comentario ?? ""}</td>
      <td>
        <button class="btn btn-sm btn-outline-secondary me-1" onclick="editarEvento(${item.id_evento})">
          <i data-feather="edit"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="eliminarEvento(${item.id_evento})">
          <i data-feather="x"></i>
        </button>
      </td>
    `;
    tablaDetallesLogisticos.appendChild(tr);
  });
  if (window.feather) feather.replace();
}

// ===============================
// Utilidades (sufijo: detallesLogisticos)
// ===============================
function renderSugerenciasDetallesLogisticos(listEl, items, onPick) {
  listEl.innerHTML = "";
  if (!Array.isArray(items) || items.length === 0) {
    listEl.style.display = "none";
    return;
  }
  items.forEach(function (it) {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
    btn.innerHTML =
      "<span>" + (it.label || "") + "</span>" +
      (it.meta ? '<small class="text-muted">' + it.meta + "</small>"
               : (it.tipo ? '<small class="text-muted">' + it.tipo + "</small>" : ""));
    btn.addEventListener("click", function () { onPick(it); });
    listEl.appendChild(btn);
  });
  listEl.style.display = "block";
}

function xhrGetDetallesLogisticos(url, cb) {
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      try { cb(JSON.parse(this.responseText)); } catch { cb([]); }
    }
  };
  http.send();
}

// ===============================
// AUTOCOMPLETE: MODAL -> Operación
// ===============================
(function setupOperacionModalAutocompleteDetallesLogisticos(){
  const input   = document.getElementById("eventoOperacionNombre");
  const hidden  = document.getElementById("eventoOperacionId");
  const list    = document.getElementById("eventoOperacionSugerencias");
  const meta    = document.getElementById("eventoOperacionMeta");

  if (!input || !list) return;

  let lastTermDetallesLogisticos = "";
  input.addEventListener("input", () => {
    if (input.disabled) return;
    const term = input.value.trim();
    hidden.value = "";
    if (term.length < 1) { list.style.display = "none"; return; }
    if (term === lastTermDetallesLogisticos) return;
    lastTermDetallesLogisticos = term;

    const url = base_url + "operaciones_maritimas_eventos/buscar_operaciones?term=" + encodeURIComponent(term);
    xhrGetDetallesLogisticos(url, (rows) => {
      renderSugerenciasDetallesLogisticos(list, rows, (it) => {
        // 1) Seteamos la operación
        input.value  = it.label;
        hidden.value = it.id;
        list.style.display = "none";
        meta && (meta.textContent = it.meta || "");

        // 2) Reset y readonly del contenedor visible
        const hiddenCont = document.getElementById("eventoContenedorOperacionId");
        if (hiddenCont) hiddenCont.value = "";
        if (contTxtModal) {
          contTxtModal.value = "";
          contTxtModal.readOnly = true; // mantiene readonly
        }
        if (fldContenedorTipoEventosLogisticos) fldContenedorTipoEventosLogisticos.value = "";

        // 3) Traer el contenedor marítimo asociado a la operación y autollenar
        const urlC = base_url + "operaciones_maritimas_eventos/contenedor_maritimo_de_operacion?operacion_id=" + it.id;
        xhrGetDetallesLogisticos(urlC, (cont) => {
          if (cont && cont.id) {
            if (hiddenCont) hiddenCont.value = cont.id;               // cmo.id
            if (contTxtModal) contTxtModal.value = cont.label || "";  // número contenedor
            if (fldContenedorTipoEventosLogisticos) fldContenedorTipoEventosLogisticos.value = "MARITIMO";

            // Catálogo tipos de evento para marítimo
            cargarTiposEventoPorContenedorUI("MARITIMO");
            if (contTxtModal) contTxtModal.readOnly = true;
          } else {
            // Si no hay, seguimos readonly por regla de negocio
            if (contTxtModal) contTxtModal.readOnly = true;
          }
        });
      });
    });
  });

  document.addEventListener("click", (e) => {
    if (!list.contains(e.target) && e.target !== input) list.style.display = "none";
  });
})();

// ===============================
// AUTOCOMPLETE: MODAL -> Contenedor
// ===============================
(function setupContenedorModalAutocompleteDetallesLogisticos(){
  const input   = document.getElementById("eventoContenedorNombre");
  const hiddenF = document.getElementById("eventoContenedorOperacionId");
  const list    = document.getElementById("eventoContenedorSugerencias");
  const opHidden= document.getElementById("eventoOperacionId");

  if (!input || !list || !opHidden) return;

  // Como es readonly, no montamos el autocomplete.
  if (input.readOnly) return;

  // (Si en el futuro decides habilitarlo, aquí quedaría el autocomplete)
})();

// ======================================
// AUTOCOMPLETE: FILTROS SUPERIORES -> Operación
// ======================================
(function setupOperacionFiltroAutocompleteDetallesLogisticos(){
  const input   = document.getElementById("eventosFiltroOpNombre");
  const hidden  = document.getElementById("eventosFiltroOpId");
  const list    = document.getElementById("eventosFiltroOpSugerencias");
  const meta    = document.getElementById("eventosFiltroOpMeta");
  const contTxt = document.getElementById("eventosFiltroContenedorNombre");
  const contHid = document.getElementById("eventosFiltroContenedorId");
  const contList= document.getElementById("eventosFiltroContenedorSugerencias");

  if (!input || !list) return;

  let lastTermDetallesLogisticos = "";
  input.addEventListener("input", () => {
    const term = input.value.trim();

    if (term.length === 0) {
      hidden.value = "";
      if (meta)    meta.textContent = "";
      if (contTxt) contTxt.value = "";
      if (contHid) contHid.value = "";
      if (contList) contList.style.display = "none";
      list.style.display = "none";
      paginaEventosLogisticos = 1;
      listarEventoLogistico();
      return;
    }

    hidden.value = "";
    if (term === lastTermDetallesLogisticos) return;
    lastTermDetallesLogisticos = term;

    const url = base_url + "operaciones_maritimas_eventos/buscar_operaciones?term=" + encodeURIComponent(term);
    xhrGetDetallesLogisticos(url, (rows) => {
      renderSugerenciasDetallesLogisticos(list, rows, (it) => {
        input.value = it.label;
        hidden.value = it.id;
        list.style.display = "none";
        meta && (meta.textContent = it.meta || "");

        // reset contenedor (depende de operación)
        if (contTxt) contTxt.value = "";
        if (contHid) contHid.value = "";
        if (contList) contList.style.display = "none";

        // aplicar filtro
        paginaEventosLogisticos = 1;
        listarEventoLogistico();
      });
    });
  });

  document.addEventListener("click", (e) => {
    if (!list.contains(e.target) && e.target !== input) list.style.display = "none";
  });
})();

// ======================================
// AUTOCOMPLETE: FILTROS SUPERIORES -> Contenedor
// ======================================
(function setupContenedorFiltroAutocompleteDetallesLogisticos(){
  const input   = document.getElementById("eventosFiltroContenedorNombre");
  const hidden  = document.getElementById("eventosFiltroContenedorId");
  const list    = document.getElementById("eventosFiltroContenedorSugerencias");
  const opHidden= document.getElementById("eventosFiltroOpId");

  if (!input || !list || !opHidden) return;

  let lastTermDetallesLogisticos = "";
  input.addEventListener("input", () => {
    const term = input.value.trim();

    if (term.length === 0) {
      hidden.value = "";
      list.style.display = "none";
      paginaEventosLogisticos = 1;
      listarEventoLogistico();
      return;
    }

    hidden.value = "";
    const opId = parseInt(opHidden.value || "0", 10);
    if (!opId) { list.style.display = "none"; return; }
    if (term === lastTermDetallesLogisticos) return;
    lastTermDetallesLogisticos = term;

    const url = base_url + "operaciones_maritimas_eventos/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
    xhrGetDetallesLogisticos(url, (rows) => {
      renderSugerenciasDetallesLogisticos(list, rows, (it) => {
        input.value  = it.label;
        hidden.value = it.id; // cmo.id
        list.style.display = "none";

        paginaEventosLogisticos = 1;
        listarEventoLogistico();
      });
    });
  });

  document.addEventListener("click", (e) => {
    if (!list.contains(e.target) && e.target !== input) list.style.display = "none";
  });
})();

const formDetalles = document.getElementById("formEventosLogisticos");
const modalDetalles = document.getElementById("modalDetallesLogisticos");
const btnAgregarDetalles = document.getElementById("btnAbrirModalDetalles");

// Campos del formulario
const fldIdEventoDetallesLogisticos    = document.getElementById("idEvento");
const fldOperacionIdDetallesLogisticos = document.getElementById("eventoOperacionId");
const fldContenedorIdDetallesLogisticos= document.getElementById("eventoContenedorOperacionId");
const fldTipoEventoIdDetallesLogisticos= document.getElementById("tipoEventoId");
const fldFechaDetallesLogisticos       = document.getElementById("fechaEventoLogistico");
const fldComentarioDetallesLogisticos  = document.getElementById("comentarioEventoLogistico");

formDetalles.addEventListener("submit", function (e) {
  e.preventDefault();

  const idEvento    = fldIdEventoDetallesLogisticos.value.trim();
  const operacionId = fldOperacionIdDetallesLogisticos.value.trim();
  const contenedorId= fldContenedorIdDetallesLogisticos.value.trim();
  const tipoEventoId= fldTipoEventoIdDetallesLogisticos.value.trim();
  const fecha       = fldFechaDetallesLogisticos.value.trim();
  const comentario  = fldComentarioDetallesLogisticos.value.trim();

  if (!operacionId || !tipoEventoId || !fecha) {
    Swal.fire("Campos requeridos", "Debes seleccionar operación, tipo de evento y fecha", "warning");
    return;
  }

  const fd = new FormData();
  if (idEvento !== "") fd.append("id_evento", idEvento);
  fd.append("operacion_id", operacionId);

  const contTipo = (fldContenedorTipoEventosLogisticos && fldContenedorTipoEventosLogisticos.value) || "";
  if (contenedorId) {
    if (contTipo === "MARITIMO") {
      fd.append("cont_maritimo_operacion_id", contenedorId);
    } else {
      fd.append("contenedor_operacion_id", contenedorId);
    }
  }
  fd.append("tipo_evento_id", tipoEventoId);
  fd.append("fecha", fecha);
  fd.append("comentario", comentario);

  const url = base_url + (idEvento === "" 
    ? "operaciones_maritimas_eventos/registrar" 
    : "operaciones_maritimas_eventos/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error guardar:", this.responseText);
        Swal.fire("Error", "No se pudo guardar el evento", "error");
        return;
      }

      let res;
      try { res = JSON.parse(this.responseText); }
      catch {
        console.error("JSON inválido:", this.responseText);
        Swal.fire("Error", "Respuesta no válida del servidor", "error");
        return;
      }

      Swal.fire(
        res.status === "success" ? "Éxito" : "Atención",
        res.msg,
        res.status
      );

      if (res.status === "success") {
        formDetalles.reset();
        fldIdEventoDetallesLogisticos.value = "";
        if (fldContenedorTipoEventosLogisticos) fldContenedorTipoEventosLogisticos.value = "";

        // Re-habilitar operación si veníamos de editar (contenedor se queda readonly)
        document.getElementById("eventoOperacionNombre").disabled = false;

        bootstrap.Modal.getInstance(modalDetalles).hide();
        listarEventoLogistico();
        document.getElementById("modalTituloDetalles").textContent = "Registrar Evento";
        const btnSubmit = formDetalles.querySelector('button[type="submit"]');
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

// ===== Botón Agregar =====
btnAgregarDetalles.addEventListener("click", () => {
  formDetalles.reset();
  if (fldContenedorTipoEventosLogisticos) fldContenedorTipoEventosLogisticos.value = "";
  fldIdEventoDetallesLogisticos.value = "";

  // Operación editable
  document.getElementById("eventoOperacionNombre").disabled = false;

  // Contenedor: SIEMPRE readonly y limpio (lo llenará la operación)
  if (contTxtModal) { 
    contTxtModal.disabled = false; // se ve normal
    contTxtModal.readOnly = true;
    contTxtModal.value = "";
  }
  fldOperacionIdDetallesLogisticos.value = "";
  fldContenedorIdDetallesLogisticos.value = "";

  document.getElementById("modalTituloDetalles").textContent = "Registrar Evento";
  const btnSubmit = formDetalles.querySelector('button[type="submit"]');
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();

  setTiposEventoLoading(false);
  fillTiposEventoOptions([]);
  selectTipoEvento.disabled = true;
});

// ===== Editar =====
function editarEvento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "operaciones_maritimas_eventos/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error editar:", this.responseText);
      Swal.fire("Error", "No se pudo cargar el evento", "error");
      return;
    }

    let data;
    try { data = JSON.parse(this.responseText); }
    catch { Swal.fire("Error", "Respuesta no válida del servidor", "error"); return; }

    if (!data || !data.id_evento) {
      Swal.fire("Atención", "No se encontró el evento", "warning");
      return;
    }

    // ==== Rellenar campos ====
    fldIdEventoDetallesLogisticos.value    = data.id_evento || ""; 
    fldOperacionIdDetallesLogisticos.value = data.operacion_id || "";

    // Limpia contenedor (hidden + visible + tipo)
    fldContenedorIdDetallesLogisticos.value = "";
    if (contTxtModal) contTxtModal.value = "";
    if (fldContenedorTipoEventosLogisticos) fldContenedorTipoEventosLogisticos.value = "";

    // MARÍTIMO (tu módulo es marítimo)
    if (data.cont_maritimo_operacion_id) {
      fldContenedorIdDetallesLogisticos.value = data.cont_maritimo_operacion_id;
      if (contTxtModal) contTxtModal.value = data.contenedor_label || "";
      if (fldContenedorTipoEventosLogisticos) fldContenedorTipoEventosLogisticos.value = "MARITIMO";
    }

    document.getElementById("eventoOperacionNombre").value = data.operacion_label || "";
    fldTipoEventoIdDetallesLogisticos.value = data.tipo_evento_id || "";
    fldFechaDetallesLogisticos.value        = (data.fecha || "").substring(0, 10);
    fldComentarioDetallesLogisticos.value   = data.comentario || "";

    // Bloquear operación; contenedor visible readonly (no editable)
    document.getElementById("eventoOperacionNombre").disabled = true;
    if (contTxtModal) { 
      contTxtModal.disabled = false;
      contTxtModal.readOnly = true;
    }

    // Cargar catálogo acorde
    cargarTiposEventoPorContenedorUI("MARITIMO", data.tipo_evento_id);

    // UI modal
    document.getElementById("modalTituloDetalles").textContent = "Actualizar Evento";
    const btnSubmit = formDetalles.querySelector('button[type="submit"]');
    btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    if (window.feather) feather.replace();

    new bootstrap.Modal(modalDetalles).show();
  };
}

function eliminarEvento(id) {
  Swal.fire({
    title: "¿Eliminar evento?",
    text: "Se desactivará (eliminado lógico).",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar"
  }).then((r) => {
    if (!r.isConfirmed) return;

    const fdEventosLogisticosData = new FormData();
    fdEventosLogisticosData.append("id_evento", id);

    const http = new XMLHttpRequest();
    http.open("POST", base_url + "operaciones_maritimas_eventos/eliminar", true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error("Error eliminar:", this.responseText);
        Swal.fire("Error", "No se pudo eliminar", "error");
        return;
      }

      let res;
      try { res = JSON.parse(this.responseText); }
      catch { Swal.fire("Error", "Respuesta no válida del servidor", "error"); return; }

      Swal.fire(
        res.status === "success" ? "Eliminado" : "Atención",
        res.msg,
        res.status === "success" ? "success" : (res.status || "info")
      );

      if (res.status === "success") {
        listarEventoLogistico();
      }
    };
    http.send(fdEventosLogisticosData);
  });
}

// per-page
perPageEventosLogisticos.addEventListener("change", function () {
  paginaEventosLogisticos = 1;
  listarEventoLogistico();
});

// buscador con pequeño debounce
let buscarTimerEventosLogisticos = null;
buscarEventosLogisticos.addEventListener("input", function () {
  clearTimeout(buscarTimerEventosLogisticos);
  buscarTimerEventosLogisticos = setTimeout(function () {
    paginaEventosLogisticos = 1;
    listarEventoLogistico();
  }, 300);
});

// ===== Tipos de evento (catálogo dinámico) =====
const selectTipoEvento = document.getElementById("tipoEventoId");

function setTiposEventoLoading(flag) {
  if (!selectTipoEvento) return;
  selectTipoEvento.disabled = !!flag;
  selectTipoEvento.innerHTML = flag
    ? '<option value="">Cargando...</option>'
    : '<option value="">Selecciona...</option>';
}

function fillTiposEventoOptions(lista, preselectId = null) {
  if (!selectTipoEvento) return;
  let html = '<option value="">Selecciona...</option>';
  if (Array.isArray(lista)) {
    for (const r of lista) {
      const id = r.id ?? r.id_tipo_evento;
      const nom = r.nombre ?? '';
      const sel = preselectId && String(preselectId) === String(id) ? ' selected' : '';
      html += `<option value="${id}"${sel}>${nom}</option>`;
    }
  }
  selectTipoEvento.innerHTML = html;
  selectTipoEvento.disabled = false;
}

/**
 * MARITIMO -> 1 (marítima)
 * FISICO/FERRO -> 2 (terrestre)
 */
function mapTipoUIToTipoOperacionId(tipoUI) {
  const t = (tipoUI || '').toUpperCase();
  if (t === 'FISICO' || t === 'FÍSICO' || t === 'FERRO' || t === 'TERRESTRE') return 2;
  return 1; // default marítimo
}

/** Carga catálogo según tipo de contenedor (UI) */
function cargarTiposEventoPorContenedorUI(tipoUI, preselectId = null) {
  const tipoOperacionId = mapTipoUIToTipoOperacionId(tipoUI);
  setTiposEventoLoading(true);
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "operaciones_maritimas_eventos/tipos_evento?tipo_operacion_id=" + encodeURIComponent(tipoOperacionId), true);
  http.onreadystatechange = function(){
    if (this.readyState !== 4) return;
    if (this.status !== 200) { fillTiposEventoOptions([]); return; }
    let data;
    try { data = JSON.parse(this.responseText); } catch { data = []; }
    fillTiposEventoOptions(Array.isArray(data) ? data : [], preselectId);
  };
  http.send();
}

// Exportaciones
document.getElementById('btnExportarExcelEventosLogisticos')?.addEventListener('click', () => {
  ExportarTablas.exportar({
    ref: 'tablaDetallesLogisticos',
    formato: 'xlsx',
    nombre: 'DetallesLogisticos.xlsx',
    columnasOcultas: [5],
    soloVisibles: true,
    sheetName: 'Eventos Logisticos'
  });
});

document.getElementById('btnExportarPDFEventosLogisticos')?.addEventListener('click', () => {
  ExportarTablas.exportar({
    ref: '#tablaDetallesLogisticos',
    formato: 'pdf',
    nombre: 'EventosLogisticos.pdf',
    titulo: 'Eventos Logisticos',
    orientacion: 'landscape',
    formatoPagina: 'letter',
    columnasOcultas: [5],
    soloVisibles: true
  });
});
