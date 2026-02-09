/* =========================
   Usuarios.js (corregido)
   ========================= */

const form = document.getElementById("formUsuario");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarUsuario"),
);
const btnAgregarUsuario = document.getElementById("btnAgregarUsuario");
const tabla = document.getElementById("tablaUsuarios");

// ====== Refs Cliente ======
const selRol = document.getElementById("rol_id");
const wrapCliente = document.getElementById("wrap_cliente");
const selCliente = document.getElementById("cliente_id");

// ✅ Ajusta este ID al rol Cliente real (el mismo que pusiste en el Controller)
const ROL_CLIENTE_ID = 3;

// Mostrar/ocultar select cliente según rol
function toggleClienteByRol() {
  if (!wrapCliente || !selCliente || !selRol) return;

  const esCliente = String(selRol.value) === String(ROL_CLIENTE_ID);
  wrapCliente.classList.toggle("d-none", !esCliente);

  // Si no es cliente, limpiamos para que no mande basura
  if (!esCliente) selCliente.value = "";
}

// ---- Refs password
const chkCambiarClave = document.getElementById("toggleCambiarClave");
const wrapNuevaClave = document.getElementById("wrapNuevaClave");
const wrapConfirmarClave = document.getElementById("wrapConfirmarClave");
const inputNuevaClave = document.getElementById("nueva_clave");
const inputConfirma = document.getElementById("confirmar_clave");

// Toggle cambiar contraseña
chkCambiarClave?.addEventListener("change", () => {
  const on = chkCambiarClave.checked;
  wrapNuevaClave?.classList.toggle("d-none", !on);
  wrapConfirmarClave?.classList.toggle("d-none", !on);

  if (inputNuevaClave) inputNuevaClave.required = on;
  if (inputConfirma) inputConfirma.required = on;

  if (!on) {
    if (inputNuevaClave) inputNuevaClave.value = "";
    if (inputConfirma) inputConfirma.value = "";
  }
});

// ====== Refs puesto/depto ======
const selDepto = document.getElementById("departamento_id");
const wrapPuesto = document.getElementById("wrap_puesto");
const selPuesto = document.getElementById("puesto_id");

// Ocultar/limpiar puesto
function ocultarPuesto() {
  if (!wrapPuesto || !selPuesto) return;
  wrapPuesto.classList.add("d-none");
  selPuesto.required = false;
  selPuesto.disabled = true;
  selPuesto.innerHTML = '<option value="">Seleccione un puesto</option>';
}

// Mostrar puesto
function mostrarPuesto() {
  if (!wrapPuesto || !selPuesto) return;
  wrapPuesto.classList.remove("d-none");
  selPuesto.required = true;
  selPuesto.disabled = false;
}

// Inicialización
document.addEventListener("DOMContentLoaded", () => {
  ocultarPuesto();
  toggleClienteByRol();
});

selRol?.addEventListener("change", toggleClienteByRol);

// =========================
// Listar
// =========================
listar();

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Usuarios/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data = [];
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
      }
      renderTabla(data);
    }
  };
}

// =========================
// Render Tabla
// =========================
function renderTabla(data) {
  tabla.innerHTML = "";

  // Ajusta este colspan a tus columnas reales:
  // Nombre, Apellido, Correo, Tel, Departamento, Puesto, Rol, Cliente, Acciones = 9
  const COLS = 9;

  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = `<tr><td colspan="${COLS}" class="text-center">No se encontraron resultados</td></tr>`;
    return;
  }

  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.nombre ?? ""}</td>
      <td>${item.apellido ?? ""}</td>
      <td>${item.correo ?? ""}</td>
      <td>${item.telefono ?? ""}</td>
      <td>${item.departamento ?? ""}</td>
      <td>${item.puesto ?? ""}</td>
      <td>${item.roles ?? ""}</td>
      <td>${item.cliente ?? ""}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarUsuario(${item.id_usuario})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${item.id_usuario})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// =========================
// Abrir modal modo AGREGAR
// =========================
btnAgregarUsuario?.addEventListener("click", () => {
  form.reset();

  // ids/labels
  const idEl = document.getElementById("id_usuario");
  if (idEl) idEl.value = "";

  const label = document.getElementById("modalRegistrarUsuarioLabel");
  if (label) label.textContent = "Registrar Usuario";

  const btn = document.querySelector(
    '#modalRegistrarUsuario button[type="submit"]',
  );
  if (btn)
    btn.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';

  // Password: en alta, se muestra y es obligatorio
  document.getElementById("wrapToggleCambiarClave")?.classList.add("d-none");
  if (chkCambiarClave) {
    chkCambiarClave.checked = true;
    chkCambiarClave.dispatchEvent(new Event("change"));
  }

  // Puestos: limpio y deshabilito
  if (selPuesto) {
    selPuesto.innerHTML = '<option value="">Seleccione</option>';
    selPuesto.disabled = true;
  }

  // Rol/Cliente: limpio y oculto cliente
  if (selRol) selRol.value = "";
  if (selCliente) selCliente.value = "";
  toggleClienteByRol();

  feather?.replace?.();
});

// =========================
// Submit (registrar/actualizar)
// =========================
form?.addEventListener("submit", function (e) {
  e.preventDefault();

  // Validación Cliente (frontend)
  const esCliente = selRol && String(selRol.value) === String(ROL_CLIENTE_ID);
  if (esCliente && (!selCliente || selCliente.value === "")) {
    Swal.fire(
      "Aviso",
      "Debes seleccionar un cliente para el rol Cliente",
      "warning",
    );
    return;
  }

  // Validación contraseña (si checkbox activo)
  if (chkCambiarClave?.checked) {
    if ((inputNuevaClave?.value || "").length < 8) {
      Swal.fire(
        "Aviso",
        "La contraseña debe tener al menos 8 caracteres",
        "warning",
      );
      return;
    }
    if ((inputNuevaClave?.value || "") !== (inputConfirma?.value || "")) {
      Swal.fire("Aviso", "Las contraseñas no coinciden", "warning");
      return;
    }
  }

  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Usuarios/registrar", true);
  http.send(new FormData(form));
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
        modal.hide();
        form.reset();
        listar();
      }
      Swal.fire(
        "Aviso",
        String(res.msg || "").toUpperCase(),
        res.status || "info",
      );
    }
  };
});

// =========================
// Deptos → puestos
// =========================
function cargarPuestosPorDepto(deptoId, puestoSeleccionado = "") {
  if (!deptoId) {
    ocultarPuesto();
    return;
  }

  if (selPuesto) {
    selPuesto.innerHTML = '<option value="">Cargando...</option>';
    selPuesto.disabled = true;
  }
  wrapPuesto?.classList.remove("d-none");

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Usuarios/puestosPorDepartamento/" + encodeURIComponent(deptoId),
    true,
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
          selPuesto.innerHTML =
            '<option value="">Seleccione un puesto</option>';
          data.forEach((p) => {
            const opt = document.createElement("option");
            opt.value = p.id_puesto;
            opt.textContent = p.nombre;
            selPuesto.appendChild(opt);
          });

          if (puestoSeleccionado) selPuesto.value = String(puestoSeleccionado);

          mostrarPuesto();
          selPuesto.disabled = false;
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
}

selDepto?.addEventListener("change", function () {
  cargarPuestosPorDepto(this.value, "");
});

// =========================
// Editar usuario
// =========================
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

      // Campos base
      document.getElementById("id_usuario").value = data.id_usuario;
      form.nombre.value = data.nombre || "";
      form.apellido.value = data.apellido || "";
      form.correo.value = data.correo || "";
      form.telefono.value = data.telefono || "";

      // Estado
      const selActivo = document.querySelector('select[name="active"]');
      if (selActivo)
        selActivo.value =
          typeof data.estatus !== "undefined" ? String(data.estatus) : "1";

      // Password: oculto por defecto en edición
      inputNuevaClave.value = "";
      inputConfirma.value = "";
      chkCambiarClave.checked = false;
      chkCambiarClave.dispatchEvent(new Event("change"));

      // Departamento y Puesto (preselección)
      const deptoId = data.departamento_id ? String(data.departamento_id) : "";
      const puestoId = data.puesto_id ? String(data.puesto_id) : "";
      selDepto.value = deptoId || "";
      cargarPuestosPorDepto(deptoId, puestoId);

      // Rol
      if (selRol) selRol.value = data.rol_id ? String(data.rol_id) : "";

      // Cliente (según rol)
      toggleClienteByRol();
      if (selCliente)
        selCliente.value = data.cliente_id ? String(data.cliente_id) : "";

      // UI modal
      document.getElementById("modalRegistrarUsuarioLabel").textContent =
        "Editar Usuario";
      const btn = document.querySelector(
        '#modalRegistrarUsuario button[type="submit"]',
      );
      if (btn)
        btn.innerHTML =
          '<i data-feather="check-circle" class="me-1"></i> Actualizar';

      document
        .getElementById("wrapToggleCambiarClave")
        ?.classList.remove("d-none");

      feather?.replace?.();
      modal.show();
    }
  };
}
window.editarUsuario = editarUsuario;

// =========================
// Eliminar
// =========================
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

        if (res.status === "success") listar();
        Swal.fire(
          "Aviso",
          String(res.msg || "").toUpperCase(),
          res.status || "info",
        );
      }
    };
  });
}
window.eliminarUsuario = eliminarUsuario;

// =========================
// Buscar + sugerencias
// =========================
const inputBuscar = document.getElementById("buscarUsuario");
const sugerenciasEl = document.getElementById("sugerenciasUsuario");

inputBuscar?.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
    listar();
    return;
  }

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Usuarios/buscar?term=" + encodeURIComponent(term),
    true,
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

      renderTabla(data);

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
