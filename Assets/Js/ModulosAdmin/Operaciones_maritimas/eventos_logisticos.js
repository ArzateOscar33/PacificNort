 
  // Referencias
  const tablaDetallesLogisticos = document.getElementById("tbodyDetallesLogisticos");
  const inputBuscarDetallesLogisticos = document.getElementById("buscarDetalles"); // opcional si lo tienes
listarEventoLogistico();
  // LISTAR (GET)
  function listarEventoLogistico() {
    const http = new XMLHttpRequest();
 
    http.open("GET", base_url + "operaciones_maritimas_eventos/listar", true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        console.log(this.responseText); // para depuración
        const data = JSON.parse(this.responseText);
        renderTablaEventoLogistico(data);
      }
    };
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
          <button class="btn btn-sm btn-info" onclick="editarEvento(${item.id_evento})">
            <i class="fas fa-edit"></i> Editar
          </button>
          <button class="btn btn-sm btn-danger" onclick="eliminarEvento(${item.id_evento})">
            <i class="fas fa-trash-alt"></i> Eliminar
          </button>
        </td>
      `;
      tablaDetallesLogisticos.appendChild(tr);
    });
    // refresca íconos feather si los usas dentro de la tabla
    if (window.feather) feather.replace();
  }
 
// ===============================
// Utilidades (sufijo: detallesLogisticos)
// ===============================
function renderSugerenciasDetallesLogisticos(listEl, items, onPick) {
  listEl.innerHTML = "";
  if (!items || items.length === 0) { listEl.style.display = "none"; return; }
  items.forEach(it => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
    btn.innerHTML = `
      <span>${it.label}</span>
      ${it.meta ? `<small class="text-muted">${it.meta}</small>` : (it.tipo ? `<small class="text-muted">${it.tipo}</small>` : "")}
    `;
    btn.addEventListener("click", () => onPick(it));
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
  const inputContTxt = document.getElementById("eventoContenedorNombre"); // depende de la operación

  if (!input || !list) return;

  let lastTermDetallesLogisticos = "";
  input.addEventListener("input", () => {
    const term = input.value.trim();
    hidden.value = ""; // reset seleccionado
    if (term.length < 1) { list.style.display = "none"; return; }
    if (term === lastTermDetallesLogisticos) return;
    lastTermDetallesLogisticos = term;

    const url = base_url + "operaciones_maritimas_eventos/buscar_operaciones?term=" + encodeURIComponent(term);
    xhrGetDetallesLogisticos(url, (rows) => {
      renderSugerenciasDetallesLogisticos(list, rows, (it) => {
        input.value = it.label;
        hidden.value = it.id;
        list.style.display = "none";
        meta && (meta.textContent = it.meta || "");
        // reset contenedor porque depende de operación
        if (inputContTxt) inputContTxt.value = "";
        const hiddenCont = document.getElementById("eventoContenedorOperacionId");
        if (hiddenCont) hiddenCont.value = "";
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
  const hiddenF = document.getElementById("eventoContenedorOperacionId"); // guarda id de físico o marítimo
  const list    = document.getElementById("eventoContenedorSugerencias");
  const opHidden= document.getElementById("eventoOperacionId");

  if (!input || !list || !opHidden) return;

  let lastTermDetallesLogisticos = "";
  input.addEventListener("input", () => {
    const term = input.value.trim();
    hiddenF.value = "";
    const opId = parseInt(opHidden.value || "0", 10);
    if (!opId) { list.style.display = "none"; return; } // requiere operación
    if (term === lastTermDetallesLogisticos) return;
    lastTermDetallesLogisticos = term;

    const url = base_url + "operaciones_maritimas_eventos/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
    xhrGetDetallesLogisticos(url, (rows) => {
      renderSugerenciasDetallesLogisticos(list, rows, (it) => {
        input.value = it.label;
        hiddenF.value = it.id; // OJO: puede ser FISICO o MARITIMO
        list.style.display = "none";
      });
    });
  });

  document.addEventListener("click", (e) => {
    if (!list.contains(e.target) && e.target !== input) list.style.display = "none";
  });
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
    hidden.value = "";
    if (term.length < 1) { list.style.display = "none"; return; }
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
    hidden.value = "";
    const opId = parseInt(opHidden.value || "0", 10);
    if (!opId) { list.style.display = "none"; return; }
    if (term === lastTermDetallesLogisticos) return;
    lastTermDetallesLogisticos = term;

    const url = base_url + "operaciones_maritimas_eventos/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
    xhrGetDetallesLogisticos(url, (rows) => {
      renderSugerenciasDetallesLogisticos(list, rows, (it) => {
        input.value = it.label;
        hidden.value = it.id; // id de físico o marítimo
        list.style.display = "none";
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

  // ===== Validaciones =====
  if (!operacionId || !tipoEventoId || !fecha) {
    Swal.fire("Campos requeridos", "Debes seleccionar operación, tipo de evento y fecha", "warning");
    return;
  }

  // ===== Construir FormData =====
  const fd = new FormData();
  if (idEvento !== "") fd.append("id_evento", idEvento);
  fd.append("operacion_id", operacionId);
  if (contenedorId) fd.append("contenedor_operacion_id", contenedorId); // opcional
  fd.append("tipo_evento_id", tipoEventoId);
  fd.append("fecha", fecha);
  fd.append("comentario", comentario);

  const url = base_url + (idEvento === "" 
    ? "operaciones_maritimas_eventos/registrar" 
    : "operaciones_maritimas_eventos/actualizar");

  // ===== AJAX =====
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
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        console.log(this.responseText);
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
        bootstrap.Modal.getInstance(modalDetalles).hide();
        listarEventoLogistico();
         

        // Reset del modal
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
  fldIdEventoDetallesLogisticos.value = "";
  document.getElementById("modalTituloDetalles").textContent = "Registrar Evento";
  const btnSubmit = formDetalles.querySelector('button[type="submit"]');
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});
