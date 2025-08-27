const form = document.getElementById("formUsuario");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarUsuario")
);
const btnAgregarUsuario = document.getElementById("btnAgregarUsuario");
const tabla = document.getElementById("tablaUsuarios");
// ---- Refs password (mover arriba, antes de usarlas)
const chkCambiarClave = document.getElementById("toggleCambiarClave");
const wrapNuevaClave = document.getElementById("wrapNuevaClave");
const wrapConfirmarClave = document.getElementById("wrapConfirmarClave");
const inputNuevaClave = document.getElementById("nueva_clave");
const inputConfirma = document.getElementById("confirmar_clave");

// Toggle cambiar contraseña
chkCambiarClave.addEventListener("change", () => {
  const on = chkCambiarClave.checked;
  wrapNuevaClave.classList.toggle("d-none", !on);
  wrapConfirmarClave.classList.toggle("d-none", !on);
  inputNuevaClave.required = on;
  inputConfirma.required = on;

  if (!on) {
    inputNuevaClave.value = "";
    inputConfirma.value = "";
  }
});

listar();

// Listar
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Usuarios/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      renderTabla(data);
    }
  };
}

// Render
function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML =
      "<tr><td colspan='2' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.nombre}</td>
      <td>${item.apellido}</td>
      <td>${item.correo}</td> 
      <td>${item.telefono}</td>
      <td>${item.departamento}</td>
      <td>${item.puesto}</td>
      <td>${item.roles}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarUsuario(${item.id_usuario})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${item.id_usuario})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// Abrir modal modo Agregar
// Abrir modal modo AGREGAR (único)
btnAgregarUsuario.addEventListener("click", () => {
  form.reset();
  document.getElementById("id_usuario").value = "";
  document.getElementById("modalRegistrarUsuarioLabel").textContent =
    "Registrar Usuario";
  const btn = document.querySelector(
    '#modalRegistrarUsuario button[type="submit"]'
  );
  if (btn)
    btn.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  document.getElementById("wrapToggleCambiarClave").classList.add("d-none");
  chkCambiarClave.checked = true; // forzamos como si fuera activo
  chkCambiarClave.dispatchEvent(new Event("change")); // muestra inputs
  // En ALTA: pedir contraseña => mostrar/obligar campos
  chkCambiarClave.checked = true;
  chkCambiarClave.dispatchEvent(new Event("change"));

  feather.replace();

  // limpiar puestos
  const selPuesto = document.getElementById("puesto_id");
  selPuesto.innerHTML = '<option value="">Seleccione</option>';
  selPuesto.disabled = true;
});

// Submit (registrar)
form.addEventListener("submit", function (e) {
  e.preventDefault();
  if (chkCambiarClave.checked) {
    if (inputNuevaClave.value.length < 8) {
      e.preventDefault();
      Swal.fire(
        "Aviso",
        "La contraseña debe tener al menos 8 caracteres",
        "warning"
      );
      return;
    }
    if (inputNuevaClave.value !== inputConfirma.value) {
      e.preventDefault();
      Swal.fire("Aviso", "Las contraseñas no coinciden", "warning");
      return;
    }
  }
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Usuarios/registrar", true);
  http.send(new FormData(form)); // incluye id_estatus + nombre
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      console.log(this.responseText);
      const res = JSON.parse(this.responseText);
      if (res.status === "success") {
        modal.hide();
        form.reset();
        listar();
      }
      Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
    }
  };
});

const selDepto = document.getElementById("departamento_id");
const wrapPuesto = document.getElementById("wrap_puesto");
const selPuesto = document.getElementById("puesto_id");

// Ocultar/limpiar puesto
function ocultarPuesto() {
  wrapPuesto.classList.add("d-none");
  selPuesto.required = false;
  selPuesto.disabled = true;
  selPuesto.innerHTML = '<option value="">Seleccione un puesto</option>';
}

// Mostrar puesto
function mostrarPuesto() {
  wrapPuesto.classList.remove("d-none");
  selPuesto.required = true;
  selPuesto.disabled = false;
}

// Al cargar, el puesto está oculto
document.addEventListener("DOMContentLoaded", ocultarPuesto);

// Cambiar de departamento -> cargar puestos
selDepto.addEventListener("change", function () {
  const depId = this.value;

  // Si limpian el departamento, ocultamos el puesto
  if (!depId) {
    ocultarPuesto();
    return;
  }

  // Estado de carga
  selPuesto.innerHTML = '<option value="">Cargando...</option>';
  wrapPuesto.classList.remove("d-none");
  selPuesto.required = true;
  selPuesto.disabled = true;

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Usuarios/puestosPorDepartamento/" + encodeURIComponent(depId),
    true
  );
  http.send();

  http.onreadystatechange = function () {
    if (http.readyState === 4) {
      if (http.status === 200) {
        let data = [];
        try {
          data = JSON.parse(http.responseText);
        } catch (e) {
          console.error("JSON inválido:", http.responseText);
          Swal.fire("Aviso", "Respuesta inválida del servidor", "error");
          ocultarPuesto();
          return;
        }

        if (Array.isArray(data) && data.length > 0) {
          // Rellenar puestos
          selPuesto.innerHTML =
            '<option value="">Seleccione un puesto</option>';
          data.forEach((p) => {
            const opt = document.createElement("option");
            opt.value = p.id_puesto; // ajusta si tu columna se llama distinto
            opt.textContent = p.nombre; // ajusta si tu columna se llama distinto
            selPuesto.appendChild(opt);
          });
          mostrarPuesto();
        } else {
          ocultarPuesto();
          Swal.fire("Aviso", "No hay puestos en este departamento", "info");
        }
      } else {
        ocultarPuesto();
        Swal.fire("Error", "No se pudieron cargar los puestos", "error");
      }
    }
  };
});
function editarUsuario(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Usuarios/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        Swal.fire("Aviso", "Respuesta inválida del servidor", "error");
        return;
      }

      // set campos...
      document.getElementById("id_usuario").value = data.id_usuario;
      form.nombre.value = data.nombre || "";
      form.apellido.value = data.apellido || "";
      form.correo.value = data.correo || "";
      form.telefono.value = data.telefono || "";
      document.querySelector('select[name="active"]').value =
        typeof data.estatus !== "undefined" ? data.estatus : 1;

      // IMPORTANTÍSIMO: evitar autocompletar del navegador
      inputNuevaClave.value = "";
      inputConfirma.value = "";
      // En edición NO cambiar contraseña por defecto
      chkCambiarClave.checked = false;
      chkCambiarClave.dispatchEvent(new Event("change"));

      // ...cargar depto/puesto/rol como ya tenías...

      document.getElementById("modalRegistrarUsuarioLabel").textContent =
        "Editar Usuario";
      const btn = document.querySelector(
        '#modalRegistrarUsuario button[type="submit"]'
      );
      if (btn)
        btn.innerHTML =
          '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      // mostrar bloque "cambiar contraseña"
      document
        .getElementById("wrapToggleCambiarClave")
        .classList.remove("d-none");
      chkCambiarClave.checked = false; // por defecto no se cambia
      chkCambiarClave.dispatchEvent(new Event("change"));
      feather.replace();
      modal.show();
    }
  };
}
window.editarUsuario = editarUsuario;

// expón para onclick en la tabla
window.editarUsuario = editarUsuario;
document.getElementById("btnAgregarUsuario").addEventListener("click", () => {
  form.reset();
  document.getElementById("id_usuario").value = "";
  document.getElementById("modalRegistrarUsuarioLabel").textContent =
    "Registrar Usuario";
  const btn = document.querySelector(
    '#modalRegistrarUsuario button[type="submit"]'
  );
  if (btn)
    btn.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();

  // limpiar puestos
  const selPuesto = document.getElementById("puesto_id");
  selPuesto.innerHTML = '<option value="">Seleccione</option>';
  selPuesto.disabled = true;
});

function eliminarUsuario(id) {
  Swal.fire({
    title: "¿Eliminar usuario?",
    text: "El usuario no podrá acceder al sistema.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Usuarios/eliminar/" + id, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        let res;
        try {
          res = JSON.parse(this.responseText);
        } catch (e) {
          console.error("JSON inválido:", this.responseText);
          Swal.fire("Aviso", "Respuesta inválida del servidor", "error");
          return;
        }
        if (res.status === "success") {
          listar(); // refresca tabla (listar ya trae estatus=1)
        }
        Swal.fire("Aviso", (res.msg || "").toUpperCase(), res.status || "info");
      }
    };
  });
}

const inputBuscar = document.getElementById("buscarUsuario");
const sugerenciasEl = document.getElementById("sugerenciasUsuario");
// Buscar + sugerencias
inputBuscar?.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    // Limpia sugerencias y vuelve a listar todo
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
    listar();
    return;
  }

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Usuarios/buscar?term=" + encodeURIComponent(term),
    true
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        return;
      }

      // refresca tabla con el resultado
      renderTabla(data);

      // pinta sugerencias
      sugerenciasEl.innerHTML = "";
      if (Array.isArray(data) && data.length > 0) {
        data.slice(0, 8).forEach((u) => {
          const item = document.createElement("button");
          item.classList.add("list-group-item", "list-group-item-action");
          item.type = "button";
          item.textContent = `${u.nombre} ${u.apellido}`.trim();
          item.onclick = () => {
            inputBuscar.value = `${u.nombre} ${u.apellido}`.trim();
            sugerenciasEl.innerHTML = "";
            sugerenciasEl.style.display = "none";
            // si quieres mostrar SOLO ese registro en la tabla:
            renderTabla([u]);
          };
          sugerenciasEl.appendChild(item);
        });
        sugerenciasEl.style.display = "block";
      } else {
        sugerenciasEl.style.display = "none";
      }
    }
  };
});

// Cerrar sugerencias click fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar?.contains(e.target) && !sugerenciasEl?.contains(e.target)) {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
  }
});

// Exponer si usas onclick en la tabla:
window.eliminarUsuario = eliminarUsuario;
