// assets/js/modulosAdmin/puertos.js

// ====== ELEMENTOS ======
const tabla           = document.getElementById("tablaPuertos"); // <tbody> o <table>
const form            = document.getElementById("formPuerto");
const modal           = new bootstrap.Modal(document.getElementById("modalRegistrarPuerto"));
const inputBuscar     = document.getElementById("buscarPuerto");
const sugerenciasBox  = document.getElementById("sugerenciasPuerto");
const btnAgregar      = document.getElementById("btnAgregarPuerto");

// Filtros / opcionales
const selectCiudadFiltro = document.getElementById("ciudades_filtro"); // selector de ciudad en la barra
const perPageSelect      = document.getElementById("perPageSelect");   // <select> 25/50 (opcional)
const paginacion         = document.getElementById("paginacion");      // <ul class="pagination"> (opcional)

// Campos del form
const fldId      = document.getElementById("id_puerto");
const fldNombre  = document.getElementById("nombre_puerto");
const fldCiudad  = document.getElementById("ciudad_id");

// Estado interno
let currentPage = 1;
let perPage     = perPageSelect ? parseInt(perPageSelect.value, 10) : 25;
let buscarTimer = null;

// ====== INICIO ======
window.addEventListener("DOMContentLoaded", () => {
  if (!perPage || ![25, 50].includes(perPage)) perPage = 25;
  listar(1);
});

// ====== LISTAR (paginación + q + ciudad) ======
function listar(page = 1) {
  currentPage = page;

  const q        = (inputBuscar?.value || "").trim();
  const ciudadId = (selectCiudadFiltro?.value || "").trim();

  const params = new URLSearchParams({
    page: String(currentPage),
    per_page: String(perPage),
    q
  });
  if (ciudadId) params.append("ciudad_id", ciudadId);

  const http = new XMLHttpRequest();
  http.open("GET", `${base_url}Puertos/listar?${params.toString()}`, true);
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
  // Si #tablaPuertos es <table>, obtenemos su <tbody>
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
      <td>${t.nombre}</td>
      <td>${t.nombre_ciudad ?? ""}</td>
      <td>
        <button class="btn btn-sm btn-info me-1" onclick="editarPuerto(${t.id_puerto})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarPuerto(${t.id_puerto})">
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
  document.getElementById("modalRegistrarPuertoLabel").textContent = "Registrar Puerto";
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
  http.open("POST", base_url + "Puertos/registrar", true);
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

      document.getElementById("modalRegistrarPuertoLabel").textContent = "Registrar Puerto";
      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
      if (window.feather) feather.replace();
    }
  };
});

// ====== EDITAR ======
window.editarPuerto = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Puertos/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error obtener:", this.responseText);
      Swal.fire("Error", "No se pudo cargar el puerto", "error");
      return;
    }

    let data;
    try { data = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    if (fldId)     fldId.value     = data.id_puerto;
    if (fldNombre) fldNombre.value = data.nombre || "";
    if (fldCiudad) fldCiudad.value = data.ciudad_id || "";

    document.getElementById("modalRegistrarPuertoLabel").textContent = "Editar Puerto";
    const btnSubmit = document.getElementById("btnSubmit");
    if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    if (window.feather) feather.replace();
    modal.show();
  };
};

// ====== ELIMINAR ======
window.eliminarPuerto = function (id) {
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
    http.open("GET", base_url + "Puertos/eliminar/" + id, true);
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
  const ciudadId = (selectCiudadFiltro?.value || "").trim();

  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(() => {
    // 1) Redibuja tabla aplicando filtro q + ciudad
    listar(1);

    // 2) Sugerencias (máx 8)
    if (!sugerenciasBox) return;

    if (term === "") {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
      return;
    }

    const params = new URLSearchParams({ term });
    if (ciudadId) params.append("ciudad_id", ciudadId);

    const http = new XMLHttpRequest();
    http.open("GET", `${base_url}Puertos/buscar?${params.toString()}`, true);
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

      data.slice(0, 8).forEach((p) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${p.nombre} ${p.nombre_ciudad ? `(${p.nombre_ciudad})` : ""}`.trim();
        btn.onclick = () => {
          inputBuscar.value = p.nombre;
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
    if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
  }

  // Escape cierra sugerencias
  if (e.key === "Escape" && sugerenciasBox) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// ====== CAMBIO DE CIUDAD (filtro) ======
selectCiudadFiltro?.addEventListener("change", () => {
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
