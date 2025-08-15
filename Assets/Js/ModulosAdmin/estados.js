// assets/js/modulosAdmin/estados.js

// ====== ELEMENTOS DEL DOM ======
const tabla          = document.getElementById("tablaEstados");              // <tbody> o <table> depende tu vista (usamos innerHTML del tbody)
const form           = document.getElementById("formEstado");
const modal          = new bootstrap.Modal(document.getElementById("modalRegistrarEstado"));
const inputBuscar    = document.getElementById("buscarEstado");
const sugerenciasEl  = document.getElementById("sugerenciasEstado");
const btnAgregar     = document.getElementById("btnAgregarEstado");

// Opcionales (si existen en la vista)
const perPageSelect  = document.getElementById("perPageSelect");  // <select> 25/50
const paginacion     = document.getElementById("paginacion");     // <ul class="pagination">

// Campos
const fldId          = document.getElementById("id_estado");      // hidden ID
// El input del nombre debe tener name="nombre" en el <form>

let currentPage = 1;
let perPage     = perPageSelect ? parseInt(perPageSelect.value, 10) : 25;
let buscarTimer = null;

// ====== INICIO ======
window.addEventListener("DOMContentLoaded", () => {
  if (!perPage || ![25, 50].includes(perPage)) perPage = 25;
  listar(1);
});

// ====== LISTAR (paginación + filtro q) ======
function listar(page = 1) {
  currentPage = page;
  const q = (inputBuscar?.value || "").trim();

  const http = new XMLHttpRequest();
  const url  = `${base_url}Estados/listar?page=${currentPage}&per_page=${perPage}&q=${encodeURIComponent(q)}`;
  http.open("GET", url, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error listar:", this.responseText);
      return;
    }

    let payload;
    try { payload = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); return; }

    // Soporta respuesta nueva {status,data,pagination} y la vieja [..]
    const data = Array.isArray(payload) ? payload : (payload.data || []);
    const meta = Array.isArray(payload)
      ? { page: 1, total_pages: 1, total: data.length }
      : (payload.pagination || { page: 1, total_pages: 1, total: 0 });

    if (data.length === 0 && meta.total_pages > 0 && currentPage > meta.total_pages) {
      listar(meta.total_pages);
      return;
    }

    renderTabla(data);
    renderPaginacion(meta);
  };
}

// ====== RENDER TABLA ======
function renderTabla(data) {
  // Si #tablaEstados es <tbody>, usamos directamente.
  // Si es <table>, buscamos el <tbody>.
  let tbody = tabla;
  if (tabla && tabla.tagName === "TABLE") {
    tbody = tabla.querySelector("tbody") || tabla;
  }
  if (!tbody) return;

  tbody.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="2" class="text-center">No hay registros</td></tr>`;
    return;
  }

  data.forEach((t) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${t.nombre_estado}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarEstado(${t.id_estado})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarEstado(${t.id_estado})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// ====== RENDER PAGINACIÓN (opcional) ======
function renderPaginacion(meta) {
  if (!paginacion) return;

  const page       = meta.page || 1;
  const totalPages = meta.total_pages || 1;

  if (totalPages <= 1) {
    paginacion.innerHTML = "";
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
  for (let p = start; p <= end; p++) {
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

  paginacion.innerHTML = html;

  paginacion.querySelectorAll("a.page-link").forEach((a) => {
    a.addEventListener("click", (e) => {
      e.preventDefault();
      const p = parseInt(a.dataset.page, 10);
      if (!isNaN(p)) listar(p);
    });
  });
}

// ====== ABRIR MODAL (AGREGAR) ======
btnAgregar?.addEventListener("click", () => {
  form.reset();
  if (fldId) fldId.value = "";
  document.getElementById("modalRegistrarEstadoLabel").textContent = "Registrar Estado";
  document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  if (window.feather) feather.replace();
  // Si tu botón ya abre el modal con data-bs-toggle no necesitas modal.show(),
  // pero lo dejamos por compatibilidad:
  modal.show();
});

// ====== SUBMIT (registrar/actualizar) ======
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const fd = new FormData(form);
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Estados/registrar", true);
  http.send(fd);

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error registrar/actualizar:", this.responseText);
      Swal.fire("Error", "No se pudo guardar", "error");
      return;
    }

    let res;
    try { res = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    Swal.fire(res.status === "success" ? "Éxito" : "Atención", (res.msg || "").toUpperCase(), res.status);
    if (res.status === "success") {
      modal.hide();
      form.reset();
      listar(currentPage);
      document.getElementById("modalRegistrarEstadoLabel").textContent = "Registrar Estado";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
      if (window.feather) feather.replace();
    }
  };
});

// ====== EDITAR ======
function editarEstado(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estados/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error editar:", this.responseText);
      Swal.fire("Error", "No se pudo cargar el estado", "error");
      return;
    }

    let data;
    try { data = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    if (fldId) fldId.value = data.id_estado;
    form.nombre.value = data.nombre_estado || "";
    document.getElementById("modalRegistrarEstadoLabel").textContent = "Editar Estado";
    document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    if (window.feather) feather.replace();
    modal.show();
  };
}

// ====== ELIMINAR ======
function eliminarEstado(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Estados/eliminar/" + id, true);
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error("Error eliminar:", this.responseText);
        Swal.fire("Error", "No se pudo eliminar", "error");
        return;
      }

      let res;
      try { res = JSON.parse(this.responseText); }
      catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      Swal.fire(res.status === "success" ? "Eliminado" : "Atención", (res.msg || "").toUpperCase(), res.status);
      if (res.status === "success") listar(currentPage);
    };
  });
}

// ====== SELECTOR 25/50 ======
perPageSelect?.addEventListener("change", () => {
  perPage = parseInt(perPageSelect.value, 10) || 25;
  listar(1);
});

// ====== BÚSQUEDA + SUGERENCIAS ======
inputBuscar.addEventListener("keyup", function (e) {
  const term = this.value.trim();

  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(() => {
    // 1) Redibuja la tabla paginada usando q (filtro)
    listar(1);

    // 2) Sugerencias (máximo 8)
    if (!sugerenciasEl) return;

    if (term === "") {
      sugerenciasEl.innerHTML = "";
      sugerenciasEl.style.display = "none";
      return;
    }

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Estados/buscar?term=" + encodeURIComponent(term), true);
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error("Error buscar:", this.responseText);
        return;
      }

      let data;
      try { data = JSON.parse(this.responseText); }
      catch { console.error("JSON inválido:", this.responseText); return; }

      sugerenciasEl.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasEl.style.display = "none";
        return;
      }

      data.slice(0, 8).forEach((t) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "list-group-item list-group-item-action";
        item.textContent = t.nombre_estado;
        item.onclick = () => {
          inputBuscar.value = t.nombre_estado;
          sugerenciasEl.innerHTML = "";
          sugerenciasEl.style.display = "none";
          listar(1); // filtra con el valor elegido
        };
        sugerenciasEl.appendChild(item);
      });
      sugerenciasEl.style.display = "block";
    };
  }, 250);

  // Enter aplica filtro inmediato
  if (e.key === "Enter") {
    clearTimeout(buscarTimer);
    listar(1);
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
  }

  // Escape cierra sugerencias
  if (e.key === "Escape") {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
  }
});

// Cerrar sugerencias al hacer click fuera
document.addEventListener("click", (ev) => {
  if (!sugerenciasEl || !inputBuscar) return;
  const inside = sugerenciasEl.contains(ev.target) || inputBuscar.contains(ev.target);
  if (!inside) {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
  }
});

// Exponer para onclick del render
window.editarEstado = editarEstado;
window.eliminarEstado = eliminarEstado;
