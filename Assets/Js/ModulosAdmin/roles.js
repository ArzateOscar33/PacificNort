const formRol = document.getElementById("formRol");
const modalRol = new bootstrap.Modal(document.getElementById("modalRegistrarRol"));


// Cargar lista al cargar
window.addEventListener("DOMContentLoaded", listarRoles);

function listarRoles() {
    fetch(base_url + "Roles/listar")
        .then(res => res.json())
        .then(data => {
            console.log("Roles cargados:", data); // ✅ Agrega esto
            const tabla = document.getElementById("tablaRoles");
            tabla.innerHTML = "";

            data.forEach(rol => {
                const tr = document.createElement("tr");
                tr.classList.add("text-center");
                tr.innerHTML = `
                    <td>${rol.nombre}</td>
                    <td>${rol.descripcion}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarRol(${rol.id_rol})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarRol(${rol.id_rol})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
                tabla.appendChild(tr);
            });
        })
        .catch(err => console.error("Error al cargar roles:", err));
}


function editarRol(id) {
  fetch(base_url + "Roles/obtener/" + id)
    .then((res) => res.json())
    .then((data) => {
      // Rellenar campos del formulario
      document.getElementById("id").value = data.id_rol;
      document.getElementById("nombre").value = data.nombre;
      document.getElementById("descripcion").value = data.descripcion;

      // Cambiar título del modal
      document.getElementById("modalRegistrarRolLabel").textContent = "Editar Rol";

      // Cambiar texto del botón submit
      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';

      // Actualizar íconos feather (por si se renderiza nuevo contenido)
      feather.replace();

      // Mostrar el modal
      modalRol.show();
    })
    .catch((err) => {
      console.error("Error al obtener datos del rol:", err);
      Swal.fire("Error", "No se pudo cargar el rol", "error");
    });
}


document.getElementById("btnAgregarRol").addEventListener("click", () => {
  formRol.reset();
  document.getElementById("id").value = "";
  document.getElementById("modalRegistrarRolLabel").textContent = "Registrar Rol";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});



// Evento para abrir el modal de registro
formRol.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(formRol);
    const nombre = formData.get("nombre").trim();
    const descripcion = formData.get("descripcion").trim();

    if (nombre === "" || descripcion === "") {
        Swal.fire("Campos requeridos", "Por favor llena todos los campos", "warning");
        return;
    }

    fetch(base_url + "Roles/registrar", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                Swal.fire("Éxito", data.msg, "success");
                formRol.reset();
                modalRol.hide();
                listarRoles(); 
            } else {
                Swal.fire("Error", data.msg, data.status);
            }
        })
        .catch(err => {
            console.error("Error en registrar rol:", err);
            Swal.fire("Error", "No se pudo registrar el rol", "error");
        });
});
 function eliminarRol(id) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(base_url + "Roles/eliminar/" + id)
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        Swal.fire("Eliminado", data.msg, "success");
                        listarRoles();
                    } else if (data.status === "warning") {
                        Swal.fire("Advertencia", data.msg, "warning");
                    } else {
                        Swal.fire("Error", data.msg, "error");
                    }
                })
                .catch(err => {
                    console.error("Error al eliminar rol:", err);
                    Swal.fire("Error", "No se pudo eliminar el rol", "error");
                });
        }
    });
}


// Funcionalidad de búsqueda con sugerencias
const inputBuscarRol = document.getElementById("buscarRol");
const sugerenciasRoles = document.getElementById("sugerenciasRoles");

inputBuscarRol.addEventListener("keyup", function () {
  const termino = this.value.trim();

  if (termino === "") {
    sugerenciasRoles.innerHTML = "";
    sugerenciasRoles.style.display = "none";
    listarRoles(); // Si se borra el input, recargar todo
    return;
  }

  fetch(base_url + "Roles/buscar?term=" + encodeURIComponent(termino))
    .then((res) => res.json())
    .then((data) => {
       sugerenciasRoles.innerHTML = "";

  const tabla = document.getElementById("tablaRoles");
  tabla.innerHTML = ""; // ✅ Limpiar la tabla cada vez que escribes

  if (data.length === 0) {
    sugerenciasRoles.style.display = "none";
    return;
  }

  data.forEach((rol) => {
    // Sugerencias
    const item = document.createElement("button");
    item.classList.add("list-group-item", "list-group-item-action");
    item.textContent = rol.nombre;
    item.type = "button";
    item.onclick = () => {
      inputBuscarRol.value = rol.nombre;
      sugerenciasRoles.innerHTML = "";
      sugerenciasRoles.style.display = "none";
      listarRolesFiltrados(rol.nombre); // puedes usar una función externa
    };
    sugerenciasRoles.appendChild(item);

    // Tabla actualizada dinámicamente también aquí 👇
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${rol.nombre}</td>
      <td>${rol.descripcion}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarRol(${rol.id_rol})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarRol(${rol.id_rol})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });

  sugerenciasRoles.style.display = "block";
})
    .catch((err) => {
      console.error("Error al buscar roles:", err);
    });
});

// Ocultar sugerencias si se hace clic fuera del input
document.addEventListener("click", function (e) {
  if (!inputBuscarRol.contains(e.target) && !sugerenciasRoles.contains(e.target)) {
    sugerenciasRoles.innerHTML = "";
    sugerenciasRoles.style.display = "none";
  }
});
