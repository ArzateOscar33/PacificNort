const modalDepartamento = new bootstrap.Modal(
  document.getElementById("staticBackdrop"),
);
const formDepartamento = document.querySelector("#formDepartamento");
const nombreDepartamento = document.querySelector("#nombreDepartamento");
const tabla = document.querySelector("#tablaDepartamentos tbody");
let idEditar = null;

document
  .getElementById("btnAgregarDepartamento")
  .addEventListener("click", () => {
    formDepartamento.reset();
    document.getElementById("idDepartamento").value = "";
    document.getElementById("staticBackdropLabel").textContent =
      "Agregar Departamento";
    const btnSubmit = document.getElementById("btnSubmit");
    btnSubmit.innerHTML =
      '<i data-feather="check-circle" class="me-1"></i> Agregar';
    feather.replace();
  });

window.addEventListener("DOMContentLoaded", listarDepartamentos);

// Guardar o actualizar
formDepartamento.addEventListener("submit", function (e) {
  e.preventDefault();
  const id = document.getElementById("idDepartamento").value;
  const nombre = document.getElementById("nombreDepartamento").value.trim();

  if (nombre === "") {
    Swal.fire("Campo requerido", "El nombre es obligatorio", "warning");
    return;
  }

  const formData = new FormData();
  formData.append("id", id);
  formData.append("nombreDepartamento", nombre);

  const url =
    base_url +
    (id === "" ? "Departamentos/registrar" : "Departamentos/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(formData);
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (error) {
        console.error("Error al parsear JSON:\n", this.responseText);
        Swal.fire("Error", "La respuesta del servidor no es válida", "error");
        return;
      }

      Swal.fire({
        icon: data.status ? "success" : "error",
        title: data.msg,
      });

      if (data.status) {
        formDepartamento.reset();
        document.getElementById("idDepartamento").value = "";
        modalDepartamento.hide();
        listarDepartamentos();
        document.getElementById("staticBackdropLabel").textContent =
          "Agregar Departamento";
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML =
          '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

function listarDepartamentos() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Departamentos/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      //console.log("Respuesta del servidor:", data);
      tabla.innerHTML = "";
      data.forEach((dep) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
                    <td>${dep.nombre}</td> 
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarDepartamento(${dep.id_departamento})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${dep.id_departamento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
        tabla.appendChild(tr);
      });
    }
  };
}

function editarDepartamento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Departamentos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      document.getElementById("idDepartamento").value = data.id_departamento;
      document.getElementById("nombreDepartamento").value = data.nombre;
      document.getElementById("staticBackdropLabel").textContent =
        "Editar Departamento";
      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modalDepartamento.show();
    }
  };
}

function eliminarDepartamento(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Departamentos/eliminar/" + id, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          let data;
          try {
            data = JSON.parse(this.responseText);
          } catch (err) {
            console.error("Error al parsear JSON:\n", this.responseText);
            Swal.fire(
              "Error",
              "La respuesta del servidor no es válida",
              "error",
            );
            return;
          }

          Swal.fire({
            icon: data.status ? "success" : "error",
            title: data.msg,
          });

          if (data.status) {
            listarDepartamentos();
          }
        }
      };
    }
  });
}

// Búsqueda en tabla
document
  .getElementById("buscarDepartamento")
  .addEventListener("keyup", function () {
    const termino = this.value.trim();
    if (termino === "") {
      listarDepartamentos();
      return;
    }
    const http = new XMLHttpRequest();
    http.open(
      "GET",
      base_url + "Departamentos/buscar?term=" + encodeURIComponent(termino),
      true,
    );
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        const data = JSON.parse(this.responseText);
        tabla.innerHTML = "";
        data.forEach((dep) => {
          const tr = document.createElement("tr");
          tr.classList.add("text-center");
          tr.innerHTML = `
                    <td>${dep.nombre}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarDepartamento(${dep.id_departamento})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${dep.id_departamento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
          tabla.appendChild(tr);
        });
      }
    };
  });

// Sugerencias
const inputBuscar = document.getElementById("buscarDepartamento");
const sugerencias = document.getElementById("sugerencias");

inputBuscar.addEventListener("keyup", function () {
  const termino = this.value.trim();
  if (termino === "") {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
    return;
  }
  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Departamentos/buscar?term=" + encodeURIComponent(termino),
    true,
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      sugerencias.innerHTML = "";
      if (data.length === 0) {
        sugerencias.style.display = "none";
        return;
      }
      data.forEach((dep) => {
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = dep.nombre;
        item.type = "button";
        item.onclick = () => {
          inputBuscar.value = dep.nombre;
          sugerencias.innerHTML = "";
          sugerencias.style.display = "none";
          listarDepartamentosFiltrados(dep.nombre);
        };
        sugerencias.appendChild(item);
      });
      sugerencias.style.display = "block";
    }
  };
});

document.addEventListener("click", function (e) {
  if (!inputBuscar.contains(e.target) && !sugerencias.contains(e.target)) {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
  }
});

// Función opcional para filtrar la tabla por un término exacto
function listarDepartamentosFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Departamentos/buscar?term=" + encodeURIComponent(termino),
    true,
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      tabla.innerHTML = "";
      data.forEach((dep) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
                    <td>${dep.nombre}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarDepartamento(${dep.id_departamento})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${dep.id_departamento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
        tabla.appendChild(tr);
      });
    }
  };
}
