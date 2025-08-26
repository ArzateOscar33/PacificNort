 //definimimos las variables y constantes
const tabla =document.getElementById('tablaTipoDocumentos');
const form  = document.getElementById("formTipoDocumento");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarTipoDocumento"));
const btnAgregarTipoDocumento = document.getElementById("btnAgregarTipoDocumento");
const label=document.getElementById("modalRegistrarTipoDocumentoLabel");
listar();
// Listar
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "tipos_documentos/listar", true);
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
      <td>${item.clave}</td>
      <td>${item.nombre}</td>
      <td>${item.aplica_sobre}</td>  
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
  document.getElementById("modalRegistrarTipoDocumentoLabel").textContent = "Registrar Tipo de Documento";
  // si usas un botón con id para cambiar texto, cámbialo aquí
  feather.replace();
});

// Submit (registrar)
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "tipos_documentos/registrar", true);
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


function editarTipoDocumento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "tipos_documentos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      
        let data;
      
      try { 
        console.log(this.responseText);
        data = JSON.parse(this.responseText); 
    } catch (e) {
        console.error("JSON inválido:", this.responseText);
        Swal.fire("Aviso", "Respuesta inválida del servidor", "error");
        return;
      }

      // Setear campos
      document.getElementById("idTipoDocumento").value = data.id_tipo_documento;
      form.nombreDocumento.value    = data.nombre || "";
      form.clave.value  = data.clave || "";
      form.descripcionDocumento.value    = data.descripcion || "";
      form.aplicaSobre.value  = data.aplica_sobre || ""; 
      document.getElementById("modalRegistrarTipoDocumentoLabel").textContent = "Editar Tipo Documento";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
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
      const url=base_url + "tipos_documentos/eliminar/" + id;
      http.open("GET", url, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          console.log(this.responseText);
          const res = JSON.parse(this.responseText);
          if (res.status === "success") listar();
          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        }
      };
    }
  });
}