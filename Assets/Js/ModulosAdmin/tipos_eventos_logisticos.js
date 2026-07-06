const tabla = document.getElementById("tablaTiposEventos");
const form = document.getElementById("formTipoEvento");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarTipoEvento"),
);

const inputBuscar = document.getElementById("buscarTipoEvento");
const sugerencias = document.getElementById("sugerenciasTipoEvento");

// Listar
function listarTiposEventos() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_eventos_logisticos/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      renderTabla(data);
    }
  };
}

function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = `<tr><td colspan="3" class="text-center">No hay registros</td></tr>`;
    return;
  }
  data.forEach((t) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${t.nombre}</td>
      <td>${t.nombre_operacion ? t.nombre_operacion : '<span class="text-muted">Global</span>'}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarTipoEvento(${t.id_tipo_evento})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarTipoEvento(${t.id_tipo_evento})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// Abrir modal (Agregar)
document
  .getElementById("btnAgregarTipoEvento")
  ?.addEventListener("click", () => {
    form.reset();
    document.getElementById("id_tipo_evento").value = "";
    document.getElementById("tipo_operacion_id").value = ""; // reset select
    document.getElementById("modalRegistrarTipoEventoLabel").textContent =
      "Registrar Tipo de Evento";
    document.getElementById("btnSubmit").innerHTML =
      '<i data-feather="check-circle" class="me-1"></i> Agregar';
    feather.replace();
    modal.show();
  });

// Editar (cargar datos)
function editarTipoEvento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_eventos_logisticos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      document.getElementById("id_tipo_evento").value = data.id_tipo_evento;
      form.nombre.value = data.nombre;
      document.getElementById("tipo_operacion_id").value =
        data.id_tipo_operacion ?? "";
      document.getElementById("modalRegistrarTipoEventoLabel").textContent =
        "Editar Tipo de Evento";
      document.getElementById("btnSubmit").innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Submit (Registrar / Actualizar)
form?.addEventListener("submit", function (e) {
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Tipos_eventos_logisticos/registrar", true);
  http.send(new FormData(form));
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      // console.log(this.responseText);
      const res = JSON.parse(this.responseText);
      if (res.status === "success") {
        modal.hide();
        form.reset();
        listarTiposEventos();
      }
      Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
    }
  };
});

// Eliminar
function eliminarTipoEvento(id) {
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
      http.open(
        "GET",
        base_url + "Tipos_eventos_logisticos/eliminar/" + id,
        true,
      );
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          const res = JSON.parse(this.responseText);
          if (res.status === "success") {
            listarTiposEventos();
          }
          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        }
      };
    }
  });
}

// Búsqueda opcional con sugerencias
inputBuscar?.addEventListener("keyup", function () {
  const term = this.value.trim();
  if (term === "") {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
    listarTiposEventos();
    return;
  }
  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url +
      "Tipos_eventos_logisticos/buscar?term=" +
      encodeURIComponent(term),
    true,
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      renderTabla(data);

      // sugerencias
      sugerencias.innerHTML = "";
      if (Array.isArray(data) && data.length > 0) {
        data.slice(0, 8).forEach((t) => {
          const item = document.createElement("button");
          item.classList.add("list-group-item", "list-group-item-action");
          item.type = "button";
          item.textContent = t.nombre;
          item.onclick = () => {
            inputBuscar.value = t.nombre;
            sugerencias.innerHTML = "";
            sugerencias.style.display = "none";
            renderTabla([t]);
          };
          sugerencias.appendChild(item);
        });
        sugerencias.style.display = "block";
      } else {
        sugerencias.style.display = "none";
      }
    }
  };
});

// Cerrar sugerencias click fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar?.contains(e.target) && !sugerencias?.contains(e.target)) {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
  }
});

// Init
document.addEventListener("DOMContentLoaded", listarTiposEventos);

// Exponer funciones a window (si tu bundler no lo hace)
window.editarTipoEvento = editarTipoEvento;
window.eliminarTipoEvento = eliminarTipoEvento;
