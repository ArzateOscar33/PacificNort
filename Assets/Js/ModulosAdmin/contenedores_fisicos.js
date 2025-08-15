const tabla=document.getElementById("tablaContenedoresFisicos");
const form=document.getElementById("formContenedorFisico");
const modal=new bootstrap.Modal(document.getElementById("modalRegistrarContenedorFisico"));
const btnAgregarContenedorFisico=document.getElementById("btnAgregarContenedorFisico");
const inputBuscar      = document.getElementById("buscarContenedorFisico");
const sugerenciasBox   = document.getElementById("sugerenciasContenedorFisico");
// Campos del formulario
const fldId        = document.getElementById("id");
const fldNombre    = document.getElementById("numero_ferro_fisico");  
listar();
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "contenedores_fisicos/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error listar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }
      renderTabla(data);
    }
  };
}

function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = "<tr><td colspan='4' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.numero_ferro}</td> 
      <td>
        <button class="btn btn-sm btn-info" onclick="editarContenedor(${item.id_fisico})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarContenedor(${item.id_fisico})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}  


form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id        = fldId.value.trim();
  const nombre    = fldNombre.value.trim();

  if (!nombre ) {
    Swal.fire("Campos requeridos", "Completa nombre y contacto", "warning");
    return;
  }

  const fd = new FormData();
  if (id !== "") fd.append("id", id); 
  fd.append("nombre", nombre); 

  const url = base_url + (id === "" ? "contenedores_fisicos/registrar" : "contenedores_fisicos/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error guardar:", this.responseText); Swal.fire("Error", "No se pudo guardar", "error"); return; }
    
      let res;
      try { res = JSON.parse(this.responseText); } 
      catch (e) { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg, res.status);
      if (res.status === "success") {
        console.log(this.responseText);
        form.reset();
        fldId.value = "";
        modal.hide();
        listar();

        document.getElementById("modalRegistrarContenedorFisicoLabel").textContent = "Registrar Contenedor Fisico";
        const btnSubmit = form.querySelector('button[type="submit"]');
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

btnAgregarContenedorFisico.addEventListener("click", () => {
  form.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarContenedorFisicoLabel").textContent = "Registrar Contenedor Fisico";
  const btnSubmit = form.querySelector('button[type="submit"]');
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

function editarContenedor(id) {
  const http = new XMLHttpRequest();
  const url= base_url + "contenedores_fisicos/editar/" + id;
  http.open("GET",url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
        console.log(this.responseText);
      const data = JSON.parse(this.responseText);
      document.getElementById("id").value = data.id;   
      form.numero_ferro_fisico.value = data.numero_ferro || ""; 
      document.getElementById("modalRegistrarContenedorFisicoLabel").textContent = "Editar Contenedor";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}
// Eliminar
function eliminarContenedor(id) {    
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
      const url=base_url + "contenedores_fisicos/eliminar/" + id;
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