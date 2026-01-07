// assets/js/modulosAdmin/contenedores_fisicos.js

// ====== ELEMENTOS DEL DOM ======
const tabla = document.getElementById("tablaContenedoresFisicos");
const form = document.getElementById("formContenedorFisico");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarContenedorFisico"));
const btnAgregarContenedorFisico = document.getElementById("btnAgregarContenedorFisico");
const inputBuscar = document.getElementById("buscarContenedorFisico");
const sugerenciasBox = document.getElementById("sugerenciasContenedorFisico");

// Campos del formulario
const fldId = document.getElementById("id");
const fldNombre = document.getElementById("numero_ferro_fisico");

// Paginación
const perPageSelect = document.getElementById("perPageSelect"); // <select> con 25/50
const paginacion = document.getElementById("paginacion");       // <ul class="pagination" id="paginacion">

let currentPage = 1;
let perPage = perPageSelect ? parseInt(perPageSelect.value, 10) : 25;
let buscarTimer = null;

// ====== INICIO ======
window.addEventListener("DOMContentLoaded", () => {
  // valor inicial por si el select no existe
  if (!perPage || ![25, 50].includes(perPage)) perPage = 25;
  listar(1);
});

// ====== LISTAR (con paginación y filtro q) ======
function listar(page = 1) {
  currentPage = page;
  const q = (inputBuscar?.value || "").trim();

  const http = new XMLHttpRequest();
  const url = `${base_url}Contenedores_fisicos/listar?page=${currentPage}&per_page=${perPage}&q=${encodeURIComponent(q)}`;
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error listar:", this.responseText);
      return;
    }

    let payload;
    try {
      payload = JSON.parse(this.responseText);
    } catch {
      console.error("JSON inválido:", this.responseText);
      return;
    }

    const data = payload.data || [];
    const meta = payload.pagination || { page: 1, total_pages: 1, total: 0 };

    // Si la página actual quedó vacía por eliminaciones, reintenta con la última
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
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = "<tr><td colspan='2' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }

  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${item.numero_ferro}</td>
      <td>
        <button class="btn btn-sm btn-info me-1" onclick="editarContenedor(${item.id_fisico})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarContenedor(${item.id_fisico})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// ====== RENDER PAGINACIÓN ======
function renderPaginacion(meta) {
  if (!paginacion) return;

  const page = meta.page || 1;
  const totalPages = meta.total_pages || 1;

  if (totalPages <= 1) {
    paginacion.innerHTML = "";
    return;
  }

  let start = Math.max(1, page - 2);
  let end = Math.min(totalPages, start + 4);
  start = Math.max(1, end - 4);

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

  // Delegación de eventos
  paginacion.querySelectorAll("a.page-link").forEach((a) => {
    a.addEventListener("click", (e) => {
      e.preventDefault();
      const p = parseInt(a.dataset.page, 10);
      if (!isNaN(p)) listar(p);
    });
  });
}

// ====== SUBMIT (crear / actualizar) ======
form?.addEventListener("submit", function (e) {
  e.preventDefault();

  const id = (fldId?.value || "").trim();
  const nombre = (fldNombre?.value || "").trim();

  if (!nombre) {
    Swal.fire("Campo requerido", "El número de ferro es obligatorio", "warning");
    return;
  }

  const fd = new FormData();
  if (id !== "") fd.append("id", id);

  // El backend usa 'nombre' en registrar() y 'numero_ferro_fisico' en actualizar().
  // Mandamos ambos; el server tomará el que necesite.
  fd.append("nombre", nombre);
  fd.append("numero_ferro_fisico", nombre);

  const url = base_url + (id === "" ? "Contenedores_fisicos/registrar" : "Contenedores_fisicos/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error guardar:", this.responseText);
      Swal.fire("Error", "No se pudo guardar", "error");
      return;
    }

    let res;
    try {
      res = JSON.parse(this.responseText);
    } catch {
      console.error("JSON inválido:", this.responseText);
      Swal.fire("Error", "Respuesta no válida", "error");
      return;
    }

    Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg, res.status);
    if (res.status === "success") {
      form.reset();
      if (fldId) fldId.value = "";
      modal.hide();
      listar(currentPage); // recarga la misma página
      document.getElementById("modalRegistrarContenedorFisicoLabel").textContent = "Registrar Contenedor Físico";
      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
      feather.replace();
    }
  };
});

// ====== ABRIR MODAL (AGREGAR) ======
btnAgregarContenedorFisico?.addEventListener("click", () => {
  form?.reset();
  if (fldId) fldId.value = "";
  document.getElementById("modalRegistrarContenedorFisicoLabel").textContent = "Registrar Contenedor Físico";
  const btnSubmit = document.getElementById("btnSubmit");
  if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// ====== EDITAR ======
window.editarContenedor = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Contenedores_fisicos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error obtener:", this.responseText);
      Swal.fire("Error", "No se pudo cargar el contenedor", "error");
      return;
    }

    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch {
      console.error("JSON inválido:", this.responseText);
      Swal.fire("Error", "Respuesta no válida", "error");
      return;
    }

    // En el Model se devuelve 'id_fisico AS id'
    if (fldId) fldId.value = data.id;
    if (fldNombre) fldNombre.value = data.numero_ferro || "";

    document.getElementById("modalRegistrarContenedorFisicoLabel").textContent = "Editar Contenedor";
    const btnSubmit = document.getElementById("btnSubmit");
    if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    feather.replace();
    modal.show();
  };
};

// ====== ELIMINAR (lógico) ======
window.eliminarContenedor = function (id) {
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
    http.open("GET", base_url + "Contenedores_fisicos/eliminar/" + id, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error("Error eliminar:", this.responseText);
        Swal.fire("Error", "No se pudo eliminar", "error");
        return;
      }

      let res;
      try {
        res = JSON.parse(this.responseText);
      } catch {
        console.error("JSON inválido:", this.responseText);
        Swal.fire("Error", "Respuesta no válida", "error");
        return;
      }

      Swal.fire(res.status === "success" ? "Eliminado" : "Atención", res.msg, res.status);
      if (res.status === "success") listar(currentPage);
    };
  });
};

// ====== SELECTOR 25/50 ======
perPageSelect?.addEventListener("change", () => {
  perPage = parseInt(perPageSelect.value, 10) || 25;
  listar(1);
});

// ====== BÚSQUEDA + SUGERENCIAS ======
inputBuscar?.addEventListener("keyup", function (e) {
  const term = this.value.trim();

  // Debounce
  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(() => {
    // 1) Redibuja tabla paginada con el filtro q
    listar(1);

    // 2) Pinta sugerencias (máx 8)
    if (!sugerenciasBox) return;

    if (term === "") {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
      return;
    }

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Contenedores_fisicos/buscar?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        console.error("Error buscar:", this.responseText);
        return;
      }

      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch {
        console.error("JSON inválido:", this.responseText);
        return;
      }

      sugerenciasBox.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasBox.style.display = "none";
        return;
      }

      data.slice(0, 8).forEach((c) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = c.numero_ferro;
        btn.onclick = () => {
          inputBuscar.value = c.numero_ferro;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listar(1);
        };
        sugerenciasBox.appendChild(btn);
      });
      sugerenciasBox.style.display = "block";
    };
  }, 250);

  // Enter aplica filtro inmediato
  if (e.key === "Enter") {
    clearTimeout(buscarTimer);
    listar(1);
    if (sugerenciasBox) {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
    }
  }

  // Esc cierra sugerencias
  if (e.key === "Escape" && sugerenciasBox) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// Cerrar sugerencias al hacer click fuera
document.addEventListener("click", (ev) => {
  if (!sugerenciasBox) return;
  const clickInside = sugerenciasBox.contains(ev.target) || inputBuscar.contains(ev.target);
  if (!clickInside) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});
