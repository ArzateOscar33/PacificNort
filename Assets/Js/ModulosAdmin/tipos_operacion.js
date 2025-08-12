const tabla = document.querySelector("#tablaTiposOperacion");
const formTipoOperacion = document.getElementById("formTipoOperacion");
const nombreTipoOperacion = document.getElementById("nombreTipoOperacion");
const modalTipoOperacion = new bootstrap.Modal(document.getElementById("modalRegistrarTipoOperacion"));
let idEditar = null;

// Abrir modal en modo Agregar
document.getElementById("btnAgregarTipoOperacion").addEventListener("click", () => {
  formTipoOperacion.reset();
  idEditar = null;
  document.getElementById("id").value = "";
  document.getElementById("modalRegistrarTipoOperacionLabel").textContent = "Registrar Tipo de Operación";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// Cargar lista al iniciar
window.addEventListener("DOMContentLoaded", listarTipoOperaciones);

function listarTipoOperaciones() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_Operacion/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }
      tabla.innerHTML = "";
      data.forEach((tipo) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td>${tipo.nombre_operacion}</td> 
          <td>
            <button class="btn btn-sm btn-info" onclick="editarTipoOperacion(${tipo.id_tipo_operacion})">
              <i class="fas fa-edit"></i> Editar
            </button>
            <button class="btn btn-sm btn-danger" onclick="eliminarTipoOperacion(${tipo.id_tipo_operacion})">
              <i class="fas fa-trash-alt"></i> Eliminar
            </button>
          </td>
        `;
        tabla.appendChild(tr);
      });
    }
  };
}

// Registrar / Actualizar
formTipoOperacion.addEventListener("submit", function (e) {
  e.preventDefault();

  const nombre = nombreTipoOperacion.value.trim();
  if (nombre === "") {
    Swal.fire("Campo requerido", "El nombre es obligatorio", "warning");
    return;
  }

  const formData = new FormData();
  formData.append("nombreTipoOperacion", nombre);

  let url = "";
  if (idEditar === null) {
    url = base_url + "Tipos_operacion/registrar";
  } else {
    formData.append("id", idEditar);
    url = base_url + "Tipos_operacion/actualizar";
  }

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(formData);
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }

      if (data.status) {
        Swal.fire("Éxito", data.msg, "success");
        formTipoOperacion.reset();
        modalTipoOperacion.hide();
        listarTipoOperaciones();
        idEditar = null;
      } else {
        Swal.fire("Atención", data.msg, "warning");
      }
    }
  };
});

// Editar
function editarTipoOperacion(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_operacion/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }
      if (data) {
        idEditar = data.id_tipo_operacion;
        nombreTipoOperacion.value = data.nombre_operacion;

        document.getElementById("modalRegistrarTipoOperacionLabel").textContent = "Editar Tipo de Operación";
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
        feather.replace();

        modalTipoOperacion.show();
      }
    }
  };
}

// Eliminar (desactivar)
function eliminarTipoOperacion(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción desactivará el tipo de operación.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Tipos_operacion/eliminar/" + id, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          let data;
          try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }
          if (data.status) {
            Swal.fire("Eliminado", data.msg, "success");
            listarTipoOperaciones();
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        }
      };
    }
  });
}

// Buscar + sugerencias
const inputBuscarTipo = document.getElementById("buscarTipoOperacion");
const sugerenciasTipo = document.getElementById("sugerenciasTipoOperacion");

inputBuscarTipo.addEventListener("keyup", function () {
  const termino = this.value.trim();

  if (termino === "") {
    sugerenciasTipo.innerHTML = "";
    sugerenciasTipo.style.display = "none";
    listarTipoOperaciones();
    return;
  }

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_operacion/buscar?term=" + encodeURIComponent(termino), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }

      sugerenciasTipo.innerHTML = "";
      const tablaEl = document.getElementById("tablaTiposOperacion");
      tablaEl.innerHTML = ""; // limpiar

      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasTipo.style.display = "none";
        return;
      }

      data.forEach((tipo) => {
        // Sugerencia
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = tipo.nombre_operacion;
        item.type = "button";
        item.onclick = () => {
          inputBuscarTipo.value = tipo.nombre_operacion;
          sugerenciasTipo.innerHTML = "";
          sugerenciasTipo.style.display = "none";
          listarTipoOperacionesFiltrados(tipo.nombre_operacion);
        };
        sugerenciasTipo.appendChild(item);

        // Actualizar tabla
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td>${tipo.nombre_operacion}</td> 
          <td>
            <button class="btn btn-sm btn-info" onclick="editarTipoOperacion(${tipo.id_tipo_operacion})"><i class="fas fa-edit"></i> Editar</button>
            <button class="btn btn-sm btn-danger" onclick="eliminarTipoOperacion(${tipo.id_tipo_operacion})"><i class="fas fa-trash-alt"></i> Eliminar</button>
          </td>
        `;
        tablaEl.appendChild(tr);
      });

      sugerenciasTipo.style.display = "block";
    }
  };
});

// Cerrar sugerencias al hacer clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscarTipo.contains(e.target) && !sugerenciasTipo.contains(e.target)) {
    sugerenciasTipo.innerHTML = "";
    sugerenciasTipo.style.display = "none";
  }
});

// Helper opcional para filtrar tabla por término
function listarTipoOperacionesFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_operacion/buscar?term=" + encodeURIComponent(termino), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }
      const tablaEl = document.getElementById("tablaTiposOperacion");
      tablaEl.innerHTML = "";
      data.forEach((tipo) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td>${tipo.nombre_operacion}</td> 
          <td>
            <button class="btn btn-sm btn-info" onclick="editarTipoOperacion(${tipo.id_tipo_operacion})"><i class="fas fa-edit"></i> Editar</button>
            <button class="btn btn-sm btn-danger" onclick="eliminarTipoOperacion(${tipo.id_tipo_operacion})"><i class="fas fa-trash-alt"></i> Eliminar</button>
          </td>
        `;
        tablaEl.appendChild(tr);
      });
    }
  };
}
