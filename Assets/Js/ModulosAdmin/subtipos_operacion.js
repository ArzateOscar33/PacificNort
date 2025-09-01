  //definimimos las variables y constantes
const tabla =document.getElementById('tablaSubTiposOperacion');
const form  = document.getElementById("formSubtipoOperacion");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarSubtipoOperacion"));
const btnAgregarSubtipoOperacion = document.getElementById("btnAgregarSubtipoOperacion");
const label=document.getElementById("modalRegistrarSubtipoOperacionLabel");
listar();
// Listar
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "subtipoOperacion/listar", true);
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
      <td>${item.nombre_operacion}</td>
      <td>${item.clave}</td>
      <td>${item.nombre}</td>
      <td>${item.prefijo}</td>
      <td>${item.puerto}</td>  
      <td>
        <button class="btn btn-sm btn-info" onclick="editar(${item.id_subtipo})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminar(${item.id_subtipo})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// Abrir modal modo Agregar
btnAgregarSubtipoOperacion.addEventListener("click", () => {
  form.reset();
  document.getElementById("id").value = "";
   
  label.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Registrar Tipo de Documento';
  document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id = (document.getElementById("id").value || "").trim();
  const url = base_url + "subtipoOperacion/" + (id ? "actualizar" : "registrar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(new FormData(form));
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let res;
      try { res = JSON.parse(this.responseText); } catch { 
        console.log(this.responseText);
        Swal.fire("Aviso", "Respuesta inválida del servidor", "error"); 
        return; 
      }
      if (res.status === true || res.status === "success") {
        modal.hide();
        form.reset();
        listar();
      }
      // normaliza status para Swal
      const tipo = (res.status === true || res.status === "success") ? "success" : "error";
      Swal.fire("Aviso", (res.msg || "").toUpperCase(), tipo);
    }
  };
});


function editar(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "subtipoOperacion/editar/" + id, true);
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
      document.getElementById("id").value = data.id_subtipo;
      form.tipo_operacion_id.value    = data.tipo_operacion_id || "";
      form.claveSubtipoOperacion.value  = data.clave || "";
      form.nombreSubtipoOperacion.value    = data.nombre || "";
      form.puerto_id.value  = data.puerto_arribo_default_id || ""; 
      form.prefijo_codigo.value = data.prefijo_codigo || "";
      label.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Editar Subtipo de Documento';
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
      

      
    }
  };
}
// Eliminar
function eliminar(id) {    
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
      const url=base_url + "subtipooperacion/eliminar/" + id;
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
const inputBuscar   = document.getElementById("buscarSubSubtipoOperacion");
const sugerenciasEl = document.getElementById("sugerenciasSubtipoOperacion");
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
  http.open("GET", base_url + "subtipooperacion/buscar?term=" + encodeURIComponent(term), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); }
      catch(e){ console.error("JSON inválido:", this.responseText); return; }

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
