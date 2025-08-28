 
  // Referencias
  const tablaDetallesLogisticos = document.getElementById("tbodyDetallesLogisticos");
  const inputBuscarDetallesLogisticos = document.getElementById("buscarDetalles"); // opcional si lo tienes
listar();
  // LISTAR (GET)
  function listar() {
    const http = new XMLHttpRequest();
    // Ajusta el path del controlador según tu ruta real:
    // Ej: "operaciones_maritimas_detalles/listar"
    http.open("GET", base_url + "operaciones_maritimas_detalles/listar", true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        console.log(this.responseText); // para depuración
        const data = JSON.parse(this.responseText);
        renderTabla(data);
      }
    };
  }

  // RENDER
  function renderTabla(data) {
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

    const url = base_url + "operaciones_maritimas_detalles/buscar_operaciones?term=" + encodeURIComponent(term);
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

    const url = base_url + "operaciones_maritimas_detalles/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
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

    const url = base_url + "operaciones_maritimas_detalles/buscar_operaciones?term=" + encodeURIComponent(term);
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

    const url = base_url + "operaciones_maritimas_detalles/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
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

  