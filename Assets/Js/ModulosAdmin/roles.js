const formRol = document.getElementById("formRol");
const modalRol = new bootstrap.Modal(
  document.getElementById("modalRegistrarRol"),
);

// Cargar lista al cargar
window.addEventListener("DOMContentLoaded", listarRoles);

function listarRoles() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Roles/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data = JSON.parse(this.responseText);
      //console.log("Roles cargados:", data);
      const tabla = document.getElementById("tablaRoles");
      tabla.innerHTML = "";

      data.forEach((rol) => {
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
    }
  };
}

function editarRol(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Roles/obtener/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      document.getElementById("id").value = data.id_rol;
      document.getElementById("nombre").value = data.nombre;
      document.getElementById("descripcion").value = data.descripcion;
      document.getElementById("modalRegistrarRolLabel").textContent =
        "Editar Rol";

      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modalRol.show();
    }
  };
}

document.getElementById("btnAgregarRol").addEventListener("click", () => {
  formRol.reset();
  document.getElementById("id").value = "";
  document.getElementById("modalRegistrarRolLabel").textContent =
    "Registrar Rol";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML =
    '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

formRol.addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(formRol);
  const nombre = formData.get("nombre").trim();
  const descripcion = formData.get("descripcion").trim();

  if (nombre === "" || descripcion === "") {
    Swal.fire(
      "Campos requeridos",
      "Por favor llena todos los campos",
      "warning",
    );
    return;
  }

  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Roles/registrar", true);
  http.send(formData);
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      if (data.status === "success") {
        Swal.fire("Éxito", data.msg, "success");
        formRol.reset();
        modalRol.hide();
        listarRoles();
      } else {
        Swal.fire("Error", data.msg, data.status);
      }
    }
  };
});

function eliminarRol(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Roles/eliminar/" + id, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          const data = JSON.parse(this.responseText);
          if (data.status === "success") {
            Swal.fire("Eliminado", data.msg, "success");
            listarRoles();
          } else if (data.status === "warning") {
            Swal.fire("Advertencia", data.msg, "warning");
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        }
      };
    }
  });
}

// Buscar con sugerencias
const inputBuscarRol = document.getElementById("buscarRol");
const sugerenciasRoles = document.getElementById("sugerenciasRoles");

inputBuscarRol.addEventListener("keyup", function () {
  const termino = this.value.trim();

  if (termino === "") {
    sugerenciasRoles.innerHTML = "";
    sugerenciasRoles.style.display = "none";
    listarRoles();
    return;
  }

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Roles/buscar?term=" + encodeURIComponent(termino),
    true,
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      sugerenciasRoles.innerHTML = "";

      const tabla = document.getElementById("tablaRoles");
      tabla.innerHTML = "";

      if (data.length === 0) {
        sugerenciasRoles.style.display = "none";
        return;
      }

      data.forEach((rol) => {
        // Sugerencia
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = rol.nombre;
        item.type = "button";
        item.onclick = () => {
          inputBuscarRol.value = rol.nombre;
          sugerenciasRoles.innerHTML = "";
          sugerenciasRoles.style.display = "none";
          listarRolesFiltrados(rol.nombre);
        };
        sugerenciasRoles.appendChild(item);

        // Tabla
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
    }
  };
});

document.addEventListener("click", function (e) {
  if (
    !inputBuscarRol.contains(e.target) &&
    !sugerenciasRoles.contains(e.target)
  ) {
    sugerenciasRoles.innerHTML = "";
    sugerenciasRoles.style.display = "none";
  }
});
