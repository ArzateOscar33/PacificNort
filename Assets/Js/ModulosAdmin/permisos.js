// assets/js/modulosAdmin/permisos.js

const tablaPermisos   = document.getElementById("tablaPermisos");
const formPermiso     = document.getElementById("formPermisoOperacion");
const modalPermiso    = new bootstrap.Modal(document.getElementById("modalAsignarPermiso"));
const btnAgregar      = document.getElementById("btnAgregarPermiso");

// Inputs/selects del modal
const hiddenId        = document.getElementById("id");
const selUsuario      = formPermiso.querySelector('select[name="usuario_id"]');
const selTipoOp       = formPermiso.querySelector('select[name="tipo_operacion_id"]');

// Buscador y sugerencias (asegúrate de poner id="buscarPermiso" al input)
const inputBuscar     = document.getElementById("buscarPermiso");
const sugerenciasBox  = document.getElementById("sugerenciasPermisos");

// ==================== Helpers ====================
function parseJSON(raw) {
  try { return JSON.parse(raw); } catch (e) { console.error("JSON inválido:", raw); return null; }
}

function xhrGET(url, cb) {
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) cb(this.status, this.responseText);
  };
}

function xhrPOST(url, formData, cb) {
  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(formData);
  http.onreadystatechange = function () {
    if (this.readyState === 4) cb(this.status, this.responseText);
  };
}

// ==================== Listar ====================
window.addEventListener("DOMContentLoaded", listarPermisos);

function listarPermisos() {
  xhrGET(base_url + "Permisos/listar", (status, res) => {
    if (status !== 200) return console.error("Error listar:", res);
    const data = parseJSON(res) || [];
    renderTabla(data);
  });
}

function renderTabla(data) {
  tablaPermisos.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tablaPermisos.innerHTML = `<tr><td colspan="4" class="text-center">Sin permisos asignados</td></tr>`;
    return;
  }
  data.forEach((item, idx) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${idx + 1}</td>
      <td>${item.usuario}</td>
      <td>${item.tipo_operacion}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarPermiso(${item.id_permiso})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarPermiso(${item.id_permiso})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;
    tablaPermisos.appendChild(tr);
  });
}

// ==================== Abrir modal (Agregar) ====================
btnAgregar.addEventListener("click", () => {
  formPermiso.reset();
  hiddenId.value = "";
  document.getElementById("modalAsignarPermisoLabel").textContent = "Asignar Permiso de Operación";
  document.getElementById("btnSubmit").innerHTML =  '<i data-feather="check-circle" class="me-1"></i> Registrar';
  feather.replace();
  cargarUsuarios();           // sin seleccionado
  cargarTiposOperacion();     // sin seleccionado
});

// ==================== Cargar selects ====================
function cargarUsuarios(selectedId = null) {
  xhrGET(base_url + "Permisos/usuarios", (status, res) => {
    if (status !== 200) return;
    const data = parseJSON(res) || [];
    selUsuario.innerHTML = `<option value="">Seleccione usuario</option>`;
    data.forEach(u => {
      const opt = document.createElement("option");
      opt.value = u.id_usuario;
      opt.textContent = u.nombre;
      if (selectedId && String(selectedId) === String(u.id_usuario)) opt.selected = true;
      selUsuario.appendChild(opt);
    });
  });
}

function cargarTiposOperacion(selectedId = null) {
  xhrGET(base_url + "Permisos/tipos_operacion", (status, res) => {
    if (status !== 200) return;
    const data = parseJSON(res) || [];
    selTipoOp.innerHTML = `<option value="">Seleccione tipo de operación</option>`;
    data.forEach(t => {
      const opt = document.createElement("option");
      opt.value = t.id_tipo_operacion;
      opt.textContent = t.nombre_operacion;
      if (selectedId && String(selectedId) === String(t.id_tipo_operacion)) opt.selected = true;
      selTipoOp.appendChild(opt);
    });
  });
}

// ==================== Guardar (crear/actualizar) ====================
formPermiso.addEventListener("submit", function (e) {
  e.preventDefault();

  const id  = hiddenId.value.trim();
  const uid = selUsuario.value.trim();
  const tid = selTipoOp.value.trim();

  if (uid === "" || tid === "") {
    Swal.fire("Campos requeridos", "Selecciona usuario y tipo de operación", "warning");
    return;
  }

  const fd = new FormData();
  if (id !== "") fd.append("id", id);
  fd.append("usuario_id", uid);
  fd.append("tipo_operacion_id", tid);

  xhrPOST(base_url + "Permisos/registrar", fd, (status, res) => {
    if (status !== 200) {
      console.error("Error registrar:", res);
      Swal.fire("Error", "No se pudo guardar el permiso", "error");
      return;
    }
    const data = parseJSON(res);
    if (!data) return Swal.fire("Error", "Respuesta no válida", "error");

    Swal.fire(data.status === "success" ? "Éxito" : "Atención", data.msg, data.status);
    if (data.status === "success") {
      formPermiso.reset();
      hiddenId.value = "";
      modalPermiso.hide();
      listarPermisos();
    }
  });
});

// ==================== Editar ====================
window.editarPermiso = function (id) {
  xhrGET(base_url + "Permisos/editar/" + id, (status, res) => {
    if (status !== 200) {
      console.error("Error obtener permiso:", res);
      return Swal.fire("Error", "No se pudo cargar el permiso", "error");
    }
    const data = parseJSON(res);
    if (!data) return Swal.fire("Error", "Respuesta no válida", "error");

    hiddenId.value = data.id_permiso;
    // Cargar selects con selección
    cargarUsuarios(data.usuario_id);
    cargarTiposOperacion(data.tipo_operacion_id);

    document.getElementById("modalAsignarPermisoLabel").textContent = "Editar Permiso de Operación";
    document.getElementById("btnSubmit").innerHTML =  '<i data-feather="check-circle" class="me-1"></i> Actualizar';
    feather.replace();
    modalPermiso.show();
  });
};

// ==================== Eliminar (borrado lógico) ====================
window.eliminarPermiso = function (id) {
  Swal.fire({
    title: "¿Desactivar permiso?",
    text: "Podrás reactivarlo más tarde si lo necesitas.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;
    xhrGET(base_url + "Permisos/eliminar/" + id, (status, res) => {
      if (status !== 200) {
        console.error("Error eliminar:", res);
        return Swal.fire("Error", "No se pudo desactivar", "error");
      }
      const data = parseJSON(res);
      if (!data) return Swal.fire("Error", "Respuesta no válida", "error");
      Swal.fire(data.status === "success" ? "Eliminado" : "Atención", data.msg, data.status);
      if (data.status === "success") listarPermisos();
    });
  });
};

// ==================== Buscar + sugerencias ====================
// (Asegúrate de tener <input id="buscarPermiso"> en tu vista)
inputBuscar?.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
    listarPermisos();
    return;
  }

  xhrGET(base_url + "Permisos/buscar?term=" + encodeURIComponent(term), (status, res) => {
    if (status !== 200) return;
    const data = parseJSON(res) || [];

    // Refrescar tabla con resultados de búsqueda
    renderTabla(data);

    // Sugerencias
    sugerenciasBox.innerHTML = "";
    if (data.length === 0) {
      sugerenciasBox.style.display = "none";
      return;
    }

    data.slice(0, 8).forEach(p => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = `${p.usuario} — ${p.tipo_operacion}`;
      btn.onclick = () => {
        inputBuscar.value = `${p.usuario} — ${p.tipo_operacion}`;
        sugerenciasBox.innerHTML = "";
        sugerenciasBox.style.display = "none";
        listarPermisosFiltrados(p.usuario); // o p.tipo_operacion, como prefieras
      };
      sugerenciasBox.appendChild(btn);
    });
    sugerenciasBox.style.display = "block";
  });
});

// Ocultar sugerencias al hacer clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar?.contains(e.target) && !sugerenciasBox?.contains(e.target)) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// Helper para filtrar por término exacto/seleccionado
function listarPermisosFiltrados(termino) {
  xhrGET(base_url + "Permisos/buscar?term=" + encodeURIComponent(termino), (status, res) => {
    if (status !== 200) return;
    const data = parseJSON(res) || [];
    renderTabla(data);
  });
}
