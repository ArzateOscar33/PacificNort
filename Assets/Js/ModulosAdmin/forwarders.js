// assets/js/modulosAdmin/forwarders.js

const tabla          = document.getElementById("tablaForwarders");
const form           = document.getElementById("formForwarder");
const modal          = new bootstrap.Modal(document.getElementById("modalRegistrarForwarder"));
const btnAgregar     = document.getElementById("btnAgregarForwarder");
const inputBuscar    = document.getElementById("buscarForwarder");
const sugerenciasBox = document.getElementById("sugerenciasForwarders");

// Campos del form (nota: el hidden en tu vista es id_Forwarder con F mayúscula)
const fldId        = document.getElementById("id_Forwarder");
const fldNombre    = document.getElementById("nombre");
const fldContacto  = document.getElementById("contacto"); 

// ================= LISTAR =================
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Forwarders/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error listar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); return; }
      renderTabla(data);
    }
  };
}

function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = "<tr><td colspan='3' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center",);
    tr.innerHTML = `
    <td>${item.nombre}</td>
    <td>${item.contacto ?? ""}</td>
    <td>
        <div class="d-inline-flex gap-2">
        <button class="btn btn-sm btn-info" onclick="editarForwarder(${item.id_forwarder})">
            <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarForwarder(${item.id_forwarder})">
            <i class="fas fa-trash-alt"></i> Eliminar
        </button>
        </div>
    </td>
    `;

    tabla.appendChild(tr);
  });
}

// ================= ABRIR MODAL (AGREGAR) =================
btnAgregar?.addEventListener("click", () => {
  form.reset();
  if (fldId) fldId.value = "";
  document.getElementById("modalRegistrarForwarderLabel").textContent = "Registrar Forwarder";
  const btnSubmit = document.getElementById("btnSubmit");
  if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// ================= SUBMIT (REGISTRAR / ACTUALIZAR) =================
form?.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_forwarder = (fldId?.value || "").trim();
  const nombre       = (fldNombre?.value || "").trim();
  const contacto     = (fldContacto?.value || "").trim();

  if (!nombre || !contacto) {
    Swal.fire("Campos requeridos", "Completa nombre y contacto", "warning");
    return;
  }

  const fd = new FormData();
  if (id_forwarder) fd.append("id_forwarder", id_forwarder); // el controlador acepta id_forwarder e id_Forwarder
  fd.append("nombre", nombre);
  fd.append("contacto", contacto); 

  const url = base_url + (id_forwarder === "" ? "Forwarders/registrar" : "Forwarders/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error guardar:", this.responseText); Swal.fire("Error", "No se pudo guardar", "error"); return; }
      let res;
      try { res = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg, res.status);
      if (res.status === "success") {
        form.reset();
        if (fldId) fldId.value = "";
        modal.hide();
        listar();

        document.getElementById("modalRegistrarForwarderLabel").textContent = "Registrar Forwarder";
        const btnSubmit = document.getElementById("btnSubmit");
        if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

// ================= EDITAR =================
window.editarForwarder = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Forwarders/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error obtener:", this.responseText); Swal.fire("Error", "No se pudo cargar el forwarder", "error"); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      if (fldId)       fldId.value      = data.id_forwarder;
      if (fldNombre)   fldNombre.value  = data.nombre || "";
      if (fldContacto) fldContacto.value= data.contacto || ""; 

      document.getElementById("modalRegistrarForwarderLabel").textContent = "Editar Forwarder";
      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
};

// ================= ELIMINAR (LÓGICO) =================
window.eliminarForwarder = function (id) {
  Swal.fire({
    title: "¿Desactivar forwarder?",
    text: "Podrás reactivarlo registrándolo de nuevo con el mismo nombre.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Forwarders/eliminar/" + id, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) { console.error("Error eliminar:", this.responseText); Swal.fire("Error", "No se pudo eliminar", "error"); return; }
        let res;
        try { res = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

        Swal.fire(res.status === "success" ? "Eliminado" : "Atención", res.msg, res.status);
        if (res.status === "success") listar();
      }
    };
  });
};

// ================= BÚSQUEDA + SUGERENCIAS =================
let buscarTimer;

inputBuscar?.addEventListener("keyup", function () {
  const term = this.value.trim();

  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(() => {
    if (term === "") {
      if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
      listar();
      return;
    }

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Forwarders/buscar?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) { console.error("Error buscar:", this.responseText); return; }
        let data;
        try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); return; }

        // refrescar tabla con resultados
        renderTabla(data);

        // sugerencias
        if (!sugerenciasBox) return;
        sugerenciasBox.innerHTML = "";
        if (!Array.isArray(data) || data.length === 0) {
          sugerenciasBox.style.display = "none";
          return;
        }

        data.slice(0, 8).forEach((f) => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "list-group-item list-group-item-action";
          btn.textContent = `${f.nombre}${f.contacto ? " — " + f.contacto : ""}`;
          btn.onclick = () => {
            if (inputBuscar) inputBuscar.value = f.nombre;
            sugerenciasBox.innerHTML = "";
            sugerenciasBox.style.display = "none";
            listarForwardersFiltrados(f.nombre);
          };
          sugerenciasBox.appendChild(btn);
        });
        sugerenciasBox.style.display = "block";
      }
    };
  }, 250);
});

// Ocultar sugerencias al hacer click fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar || !sugerenciasBox) return;
  if (!inputBuscar.contains(e.target) && !sugerenciasBox.contains(e.target)) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// Helper para filtrar por término seleccionado
function listarForwardersFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Forwarders/buscar?term=" + encodeURIComponent(termino), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error filtrar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); return; }
      renderTabla(data);
    }
  };
}


