const form  = document.getElementById("formAgregarEstatus");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarEstatus"));
const tabla = document.getElementById("tablaEstatus");

const inputBuscar   = document.getElementById("buscarEstatus");
const sugerenciasEl = document.getElementById("sugerenciasEstatus");

// Inicializar
document.addEventListener("DOMContentLoaded", () => {
  listar();
});

document.getElementById("btnAgregarEstatus").addEventListener("click", () => {
  form.reset();
  document.getElementById("id_estatus").value = "";
  document.getElementById("modalRegistrarEstatusLabel").textContent = "Registrar Estatus";
  document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// Listar
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estatus/listar", true);
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
    tabla.innerHTML = "<tr><td colspan='2' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.nombre}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarEstatus(${item.id_estatus})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarEstatus(${item.id_estatus})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// Submit (registrar/actualizar)
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Estatus/registrar", true);
  http.send(new FormData(form)); // incluye id_estatus + nombre
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
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

// Editar
function editarEstatus(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estatus/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      document.getElementById("id_estatus").value = data.id_estatus;
      form.nombre.value = data.nombre;
      document.getElementById("modalRegistrarEstatusLabel").textContent = "Editar Estatus";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Eliminar
function eliminarEstatus(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((r) => {
    if (r.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Estatus/eliminar/" + id, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          const res = JSON.parse(this.responseText);
          if (res.status === "success") listar();
          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        }
      };
    }
  });
}

// Buscar + sugerencias
inputBuscar.addEventListener("keyup", function () {
  const term = this.value.trim();
  if (term === "") {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
    listar();
    return;
  }
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estatus/buscar?term=" + encodeURIComponent(term), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      renderTabla(data);

      // Sugerencias
      sugerenciasEl.innerHTML = "";
      if (Array.isArray(data) && data.length > 0) {
        data.slice(0, 8).forEach((t) => {
          const item = document.createElement("button");
          item.classList.add("list-group-item", "list-group-item-action");
          item.type = "button";
          item.textContent = t.nombre;
          item.onclick = () => {
            inputBuscar.value = t.nombre;
            sugerenciasEl.innerHTML = "";
            sugerenciasEl.style.display = "none";
            renderTabla([t]);
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
  if (!inputBuscar.contains(e.target) && !sugerenciasEl.contains(e.target)) {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
  }
});

// Exponer para onclic
