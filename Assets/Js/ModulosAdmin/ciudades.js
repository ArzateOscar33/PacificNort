// assets/js/modulosAdmin/ciudades.js

// ====== ELEMENTOS ======
const tabla            = document.getElementById("tablaCiudades"); // puede ser <tbody> o <table>
const form             = document.getElementById("formCiudad");
const modal            = new bootstrap.Modal(document.getElementById("modalRegistrarCiudad"));
const inputBuscar      = document.getElementById("buscarCiudad");
const sugerenciasBox   = document.getElementById("sugerenciasCiudad");
const btnAgregar       = document.getElementById("btnAgregarCiudad");

// Filtros / Selectores opcionales
const selectEstadoFiltro = document.getElementById("estado_id_filtro"); // filtro en barra
const perPageSelect      = document.getElementById("perPageSelect");    // <select> 25/50 (opcional)
const paginacion         = document.getElementById("paginacion");       // <ul class="pagination"> (opcional)

// Campos del form
const fldId      = document.getElementById("id_ciudad");
const fldNombre  = document.getElementById("nombre_ciudad");
const fldEstado  = document.getElementById("estado_id");

// Estado interno
let currentPage = 1;
let perPage     = perPageSelect ? parseInt(perPageSelect.value, 10) : 25;
let buscarTimer = null;

// ====== INICIO ======
window.addEventListener("DOMContentLoaded", () => {
  if (!perPage || ![25, 50].includes(perPage)) perPage = 25;
  listar(1);
});

// ====== LISTAR (paginación + filtros q / estado_id) ======
function listar(page = 1) {
  currentPage = page;

  const q = (inputBuscar?.value || "").trim();
  const estadoId = (selectEstadoFiltro?.value || "").trim();

  const params = new URLSearchParams({
    page: String(currentPage),
    per_page: String(perPage),
    q
  });
  if (estadoId) params.append("estado_id", estadoId);

  const http = new XMLHttpRequest();
  http.open("GET", `${base_url}Ciudades/listar?${params.toString()}`, true);
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
  // Detecta tbody real si #tablaCiudades apunta a <table>
  let tbody = tabla;
  if (tabla && tabla.tagName === "TABLE") {
    tbody = tabla.querySelector("tbody") || tabla;
  }
  if (!tbody) return;

  tbody.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="3" class="text-center">No hay registros</td></tr>`;
    return;
  }

  data.forEach((t) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${t.nombre_ciudad}</td>
      <td>${t.estado ?? ""}</td>
      <td>
        <button class="btn btn-sm btn-info me-1" onclick="editarCiudad(${t.id_ciudad})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarCiudad(${t.id_ciudad})">
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
  form?.reset();
  if (fldId) fldId.value = "";
  document.getElementById("modalRegistrarCiudadLabel").textContent = "Registrar Ciudad";
  const btnSubmit = document.getElementById("btnSubmit");
  if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  if (window.feather) feather.replace();
  modal.show();
});

// ====== SUBMIT (REGISTRAR / ACTUALIZAR) ======
form?.addEventListener("submit", function (e) {
  e.preventDefault();

  const fd = new FormData(form);
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Ciudades/registrar", true);
  http.send(fd);

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error guardar:", this.responseText);
      Swal.fire("Error", "No se pudo guardar", "error");
      return;
    }

    let res;
    try { res = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg || "Operación realizada", res.status);
    if (res.status === "success") {
      modal.hide();
      form.reset();
      listar(currentPage);

      document.getElementById("modalRegistrarCiudadLabel").textContent = "Registrar Ciudad";
      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
      if (window.feather) feather.replace();
    }
  };
});

// ====== EDITAR ======
window.editarCiudad = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Ciudades/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error obtener:", this.responseText);
      Swal.fire("Error", "No se pudo cargar la ciudad", "error");
      return;
    }

    let data;
    try { data = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    if (fldId)     fldId.value     = data.id_ciudad;
    if (fldNombre) fldNombre.value = data.nombre_ciudad || "";
    if (fldEstado) fldEstado.value = data.estado_id || "";

    document.getElementById("modalRegistrarCiudadLabel").textContent = "Editar Ciudad";
    const btnSubmit = document.getElementById("btnSubmit");
    if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    if (window.feather) feather.replace();
    modal.show();
  };
};

// ====== ELIMINAR ======
window.eliminarCiudad = function (id) {
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
    http.open("GET", base_url + "Ciudades/eliminar/" + id, true);
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

      Swal.fire(res.status === "success" ? "Eliminado" : "Atención", res.msg || "Operación realizada", res.status);
      if (res.status === "success") listar(currentPage);
    };
  });
};

// ====== SELECTOR 25/50 (opcional) ======
perPageSelect?.addEventListener("change", () => {
  perPage = parseInt(perPageSelect.value, 10) || 25;
  listar(1);
});

// ====== BÚSQUEDA + SUGERENCIAS ======
inputBuscar?.addEventListener("keyup", function (e) {
  const term = this.value.trim();
  const estadoId = (selectEstadoFiltro?.value || "").trim();

  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(() => {
    // 1) Redibuja tabla con filtro q+estado
    listar(1);

    // 2) Sugerencias (máx 8)
    if (!sugerenciasBox) return;

    if (term === "") {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
      return;
    }

    const params = new URLSearchParams({ term });
    if (estadoId) params.append("estado_id", estadoId);

    const http = new XMLHttpRequest();
    http.open("GET", `${base_url}Ciudades/buscar?${params.toString()}`, true);
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

      sugerenciasBox.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasBox.style.display = "none";
        return;
      }

      data.slice(0, 8).forEach((c) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${c.nombre_ciudad} ${c.estado ? `(${c.estado})` : ""}`.trim();
        btn.onclick = () => {
          inputBuscar.value = c.nombre_ciudad;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listar(1); // aplica filtro con el término elegido
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
    if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
  }

  // Escape cierra sugerencias
  if (e.key === "Escape" && sugerenciasBox) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// ====== CAMBIO DE ESTADO (filtro) ======
selectEstadoFiltro?.addEventListener("change", () => {
  listar(1);
});

// Cerrar sugerencias al hacer clic fuera
document.addEventListener("click", (ev) => {
  if (!sugerenciasBox || !inputBuscar) return;
  const inside = sugerenciasBox.contains(ev.target) || inputBuscar.contains(ev.target);
  if (!inside) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});
