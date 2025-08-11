const tabla= document.getElementById("tablaEstados");
const form = document.getElementById("formEstado"); 
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarEstado"));
const inputBuscar   = document.getElementById("buscarEstado");
const sugerenciasEl = document.getElementById("sugerenciasEstado");
const btnAgregarEstados=document.getElementById("btnAgregarEstado");
listar();

// Al hacer clic en "Agregar Estado" -> modo agregar
document.getElementById("btnAgregarEstado").addEventListener("click", () => {
  form.reset();
  document.getElementById("id_estado").value = "";
  document.getElementById("modalRegistrarEstadoLabel").textContent = "Registrar Estado";
  document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});
 


function listar(){
const url= base_url + "Estados/listar";
const http = new XMLHttpRequest();
http.open("GET", url, true);
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
      <td>${t.nombre_estado}</td> 
      <td>
        <button class="btn btn-sm btn-info" onclick="editarEstado(${t.id_estado})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarEstado(${t.id_estado})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// Submit (registrar/actualizar)
form.addEventListener("submit", function (e) {
      
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Estados/registrar", true);
  http.send(new FormData(form));  
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

function editarEstado(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estados/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      document.getElementById("id_estado").value = data.id_estado;   
      form.nombre.value = data.nombre_estado || "";
      document.getElementById("modalRegistrarEstadoLabel").textContent = "Editar Estado";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Eliminar
function eliminarEstado(id) {    
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
      http.open("GET", base_url + "Estados/eliminar/" + id, true);
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
  http.open("GET", base_url + "Estados/buscar?term=" + encodeURIComponent(term), true);
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
          item.textContent = t.nombre_estado;           // <-- FIX
          item.onclick = () => {
            inputBuscar.value = t.nombre_estado;        // <-- FIX
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

// Exponer para onclick del render
window.editarEstado = editarEstado;
window.eliminarEstado = eliminarEstado;