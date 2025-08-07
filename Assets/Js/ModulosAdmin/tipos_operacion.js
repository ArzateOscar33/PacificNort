const tabla = document.querySelector("#tablaTiposOperacion");
const formTipoOperacion = document.getElementById("formTipoOperacion");
const nombreTipoOperacion = document.getElementById("nombreTipoOperacion");
const modalTipoOperacion = new bootstrap.Modal(document.getElementById("modalRegistrarTipoOperacion"));

//
document.getElementById("btnAgregarTipoOperacion").addEventListener("click", () => {
  formRol.reset();
  document.getElementById("id").value = "";
  document.getElementById("modalRegistrarTipoOperacionLabel").textContent = "Registrar Rol";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});


window.addEventListener("DOMContentLoaded", listarTipoOperaciones);
//listarTipoOperaciones();
function listarTipoOperaciones() {
  fetch(base_url + "Tipos_Operacion/listar")
    .then(res => res.json())
    .then(data => {
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
    })
    .catch(error => console.error("Error al listar tipos de operación:", error));
}



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

  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        Swal.fire("Éxito", data.msg, "success");
        formTipoOperacion.reset();
        modalTipoOperacion.hide();
        listarTipoOperaciones();
        idEditar = null;
      } else {
        Swal.fire("Atención", data.msg, "warning");
      }
    })
    .catch(err => {
      console.error("Error en la petición:", err);
      Swal.fire("Error", "Ocurrió un error inesperado", "error");
    });
});
let idEditar = null;

function editarTipoOperacion(id) {
  fetch(base_url + "Tipos_operacion/editar/" + id)
    .then(res => res.json())
    .then(data => {
      if (data) {
        idEditar = data.id_tipo_operacion;
        nombreTipoOperacion.value = data.nombre_operacion;
        // Cambiar texto del botón submit
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';

        // Actualizar íconos feather (por si se renderiza nuevo contenido)
        feather.replace();
        modalTipoOperacion.show();
      }
    })
    .catch(err => {
      console.error("Error al obtener tipo de operación:", err);
      Swal.fire("Error", "No se pudo cargar el registro", "error");
    });
}

//eliminar
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
      fetch(base_url + "Tipos_operacion/eliminar/" + id)
        .then((res) => res.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("Eliminado", data.msg, "success");
            listarTipoOperaciones();
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        })
        .catch((error) => {
          console.error("Error al eliminar:", error);
          Swal.fire("Error", "No se pudo eliminar el registro", "error");
        });
    }
  });
}


const inputBuscarTipo = document.getElementById("buscarTipoOperacion");
const sugerenciasTipo = document.getElementById("sugerenciasTipoOperacion");

inputBuscarTipo.addEventListener("keyup", function () {
  const termino = this.value.trim();

  if (termino === "") {
    sugerenciasTipo.innerHTML = "";
    sugerenciasTipo.style.display = "none";
    listarTipoOperaciones(); // Recarga toda la tabla si está vacío
    return;
  }

  fetch(base_url + "Tipos_operacion/buscar?term=" + encodeURIComponent(termino))
    .then((res) => res.json())
    .then((data) => {
      sugerenciasTipo.innerHTML = "";
      const tabla = document.getElementById("tablaTiposOperacion");
      tabla.innerHTML = ""; // Limpia la tabla

      if (data.length === 0) {
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
        tabla.appendChild(tr);
      });

      sugerenciasTipo.style.display = "block";
    })
    .catch((err) => {
      console.error("Error al buscar tipo de operación:", err);
    });
});

// Ocultar sugerencias si se hace clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscarTipo.contains(e.target) && !sugerenciasTipo.contains(e.target)) {
    sugerenciasTipo.innerHTML = "";
    sugerenciasTipo.style.display = "none";
  }
});



