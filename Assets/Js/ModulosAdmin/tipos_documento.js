//definimimos las variables y constantes
const tabla = document.getElementById("tablaTipoDocumentos");
const form = document.getElementById("formTipoDocumento");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarTipoDocumento"),
);
const btnAgregarTipoDocumento = document.getElementById(
  "btnAgregarTipoDocumento",
);
const label = document.getElementById("modalRegistrarTipoDocumentoLabel");
listar();
// Listar
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_documentos/listar", true);
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
    const aplicaSobre =
      item.aplica_sobre === "contenedor_maritimo"
        ? "Contenedor Maritimo"
        : "Operacion Por Partida";
    row.innerHTML = `
      <td>${item.clave}</td>
      <td>${item.nombre}</td>
      <td>${aplicaSobre}</td>  
      <td>
        <button class="btn btn-sm btn-info" onclick="editarTipoDocumento(${item.id_tipo_documento})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarTipoDocumento(${item.id_tipo_documento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// Abrir modal modo Agregar
btnAgregarTipoDocumento.addEventListener("click", () => {
  form.reset();
  document.getElementById("idTipoDocumento").value = "";
  document.getElementById("modalRegistrarTipoDocumentoLabel").textContent =
    "Registrar Tipo de Documento";
  // si usas un botón con id para cambiar texto, cámbialo aquí
  feather.replace();
});

// Submit (registrar)
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Tipos_documentos/registrar", true);
  http.send(new FormData(form)); // incluye id_estatus + nombre
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      // console.log(this.responseText);
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

function editarTipoDocumento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Tipos_documentos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;

      try {
        //console.log(this.responseText);
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        Swal.fire("Aviso", "Respuesta inválida del servidor", "error");
        return;
      }

      // Setear campos
      document.getElementById("idTipoDocumento").value = data.id_tipo_documento;
      form.nombreDocumento.value = data.nombre || "";
      form.clave.value = data.clave || "";
      form.descripcionDocumento.value = data.descripcion || "";
      form.aplicaSobre.value = data.aplica_sobre || "";
      document.getElementById("modalRegistrarTipoDocumentoLabel").textContent =
        "Editar Tipo Documento";
      document.getElementById("btnSubmit").innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Eliminar
function eliminarTipoDocumento(id) {
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
      const url = base_url + "Tipos_documentos/eliminar/" + id;
      http.open("GET", url, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          //console.log(this.responseText);
          const res = JSON.parse(this.responseText);
          if (res.status === "success") listar();
          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        }
      };
    }
  });
}

const inputBuscar = document.getElementById("buscarTipoDocumento");
const sugerenciasEl = document.getElementById("sugerenciasTipoDocumento");
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
    base_url + "Tipos_documentos/buscar?term=" + encodeURIComponent(term),
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

      // refresca tabla con el resultado
      renderTabla(data);

      // pinta sugerencias
      sugerenciasEl.innerHTML = "";
      if (Array.isArray(data) && data.length > 0) {
        data.slice(0, 8).forEach((u) => {
          const item = document.createElement("button");
          item.classList.add("list-group-item", "list-group-item-action");
          item.type = "button";
          item.textContent = `${u.clave} - ${u.nombre}`.trim();
          item.onclick = () => {
            inputBuscar.value = `${u.clave} - ${u.nombre}`.trim();
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
