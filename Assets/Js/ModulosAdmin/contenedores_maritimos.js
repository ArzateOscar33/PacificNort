// assets/js/modulosAdmin/contenedores_maritimos.js

// ====== ELEMENTOS DEL DOM ======
const tabla            = document.getElementById("tablaContenedoresMaritimos");
const form             = document.getElementById("formContenedorMaritimo");
const modal            = new bootstrap.Modal(document.getElementById("modalRegistrarContenedorMaritimo"));
const btnAgregar       = document.getElementById("btnAgregarContenedorMaritimo");
const inputBuscar      = document.getElementById("buscarContenedorMaritimo");
const sugerenciasBox   = document.getElementById("sugerenciasContenedores");

// Campos del form
const fldId      = document.getElementById("id_contenedor");
const fldNumero  = document.getElementById("numero_contenedor");
const fldTipo    = document.getElementById("tipo");
const fldObs     = document.getElementById("observaciones");

// Paginación
const perPageSelect = document.getElementById("perPageSelect"); // <select> 25/50
const paginacion    = document.getElementById("paginacion");    // <ul class="pagination" id="paginacion">

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
  const url  = `${base_url}Contenedores_maritimos/listar?page=${currentPage}&per_page=${perPage}&q=${encodeURIComponent(q)}`;
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

    const data = payload.data || [];
    const meta = payload.pagination || { page: 1, total_pages: 1, total: 0 };

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
    tabla.innerHTML = "<tr><td colspan='4' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }

  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${item.numero_contenedor}</td>
      <td>${item.tipo ?? ""}</td>
      <td>${item.observaciones ?? ""}</td>
      <td>
        <button class="btn btn-sm btn-info me-1" onclick="editarContenedorMaritimo(${item.id_contenedor})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarContenedorMaritimo(${item.id_contenedor})">
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

  // Delegación
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
  document.getElementById("modalRegistrarContenedorMaritimoLabel").textContent = "Registrar Contenedor Marítimo";
  const btnSubmit = document.getElementById("btnSubmit");
  if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// ====== SUBMIT (REGISTRAR / ACTUALIZAR) ======
form?.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_contenedor     = (fldId?.value || "").trim();
  const numero_contenedor = (fldNumero?.value || "").trim().toUpperCase();
  const tipo              = (fldTipo?.value || "").trim();
  const observaciones     = (fldObs?.value || "").trim();

  if (!numero_contenedor || !tipo) {
    Swal.fire("Campos requeridos", "Completa número de contenedor y tipo", "warning");
    return;
  }

  const fd = new FormData();
  if (id_contenedor) fd.append("id_contenedor", id_contenedor);
  fd.append("numero_contenedor", numero_contenedor);
  fd.append("tipo", tipo);
  fd.append("observaciones", observaciones);

  const url = base_url + (id_contenedor === "" ? "Contenedores_maritimos/registrar" : "Contenedores_maritimos/actualizar");

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
    try { res = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg, res.status);
    if (res.status === "success") {
      form.reset();
      if (fldId) fldId.value = "";
      modal.hide();
      listar(currentPage);
      document.getElementById("modalRegistrarContenedorMaritimoLabel").textContent = "Registrar Contenedor Marítimo";
      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
      feather.replace();
    }
  };
});

// ====== EDITAR ======
window.editarContenedorMaritimo = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Contenedores_maritimos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Error obtener:", this.responseText);
      Swal.fire("Error", "No se pudo cargar el contenedor", "error");
      return;
    }

    let data;
    try { data = JSON.parse(this.responseText); }
    catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

    if (fldId)     fldId.value     = data.id_contenedor;
    if (fldNumero) fldNumero.value = data.numero_contenedor || "";
    if (fldTipo)   fldTipo.value   = data.tipo || "";
    if (fldObs)    fldObs.value    = data.observaciones || "";

    document.getElementById("modalRegistrarContenedorMaritimoLabel").textContent = "Editar Contenedor Marítimo";
    const btnSubmit = document.getElementById("btnSubmit");
    if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    feather.replace();
    modal.show();
  };
};

// ====== ELIMINAR (lógico) ======
window.eliminarContenedorMaritimo = function (id) {
  Swal.fire({
    title: "¿Desactivar contenedor?",
    text: "Podrás reactivarlo registrándolo de nuevo con el mismo número.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Contenedores_maritimos/eliminar/" + id, true);
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
    // 1) Redibuja tabla con filtro q
    listar(1);

    // 2) Sugerencias
    if (!sugerenciasBox) return;

    if (term === "") {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
      return;
    }

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Contenedores_maritimos/buscar?term=" + encodeURIComponent(term), true);
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
        btn.textContent = `${c.numero_contenedor} ${c.tipo ? `(${c.tipo})` : ""}`.trim();
        btn.onclick = () => {
          inputBuscar.value = c.numero_contenedor;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listar(1); // filtra con el valor elegido
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
  const inside = sugerenciasBox.contains(ev.target) || inputBuscar.contains(ev.target);
  if (!inside) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});
