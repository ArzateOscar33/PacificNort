const btnAgregarDepartamento=document.getElementById("btnAgregarDepartamento");
const tabla=document.getElementById("tablaCiudades");
const form= document.getElementById("formCiudad");
  
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarCiudad"));
const inputBuscar   = document.getElementById("buscarCiudad");
const sugerenciasEl = document.getElementById("sugerenciasCiudad");
const btnAgregarEstados=document.getElementById("btnAgregarCiudad");
const idEstado=document.getElementById("estado_id");


// Al hacer clic en "Agregar Estado" -> modo agregar
document.getElementById("btnAgregarCiudad").addEventListener("click", () => {
  form.reset();
  document.getElementById("id_ciudad").value = "";
  document.getElementById("modalRegistrarCiudadLabel").textContent = "Registrar Ciudad";
  document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

listar();

// Listar
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Ciudades/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      renderTabla(data);
    }
  };
}
//renderizar tabla
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
      <td>${t.nombre_ciudad}</td> 
      <td>${t.estado}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarCiudad(${t.id_ciudad})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarCiudad(${t.id_ciudad})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// Submit (registrar/actualizar)
form.addEventListener("submit", function (e) {
      
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "ciudades/registrar", true);
  http.send(new FormData(form));  
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


function editarCiudad(id) {
  const http = new XMLHttpRequest();
  const url=  base_url + "Ciudades/editar/" + id;
  http.open("GET",url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
     console.log(this.responseText);
      const data = JSON.parse(this.responseText);
      document.getElementById("id_ciudad").value = data.id_ciudad;   
      form.nombre_ciudad.value = data.nombre_ciudad || "";
      
      form.estado_id.value=data.estado_id;
       
      document.getElementById("modalRegistrarCiudadLabel").textContent = "Editar Ciudad";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Eliminar
function eliminarCiudad(id) {    
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
      http.open("GET", base_url + "Ciudades/eliminar/" + id, true);
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
  http.open("GET", base_url + "ciudades/buscar?term=" + encodeURIComponent(term), true);
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
          item.textContent = t.nombre_ciudad;           
          item.onclick = () => {
            inputBuscar.value = t.nombre_ciudad;        
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

  
const sugerencias = document.getElementById("sugerenciasCiudad");
const selectEstado = document.getElementById("estado_id_filtro");

// Estado de filtros
let filtroTerm = "";
let filtroEstado = "";

// Listar inicial (sin filtros)
document.addEventListener("DOMContentLoaded", () => {
  aplicarFiltros();
});

 

function xhrGET(url, onOK) {
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
      try {
        onOK(JSON.parse(http.responseText));
      } catch (e) {
        console.error("JSON inválido:", http.responseText);
      }
    }
  };
} 

function aplicarFiltros() {
  const params = new URLSearchParams();
  if (filtroTerm) params.append("term", filtroTerm);
  if (filtroEstado) params.append("estado_id", filtroEstado);

  const url = base_url + "Ciudades/filtrar" + (params.toString() ? "?" + params.toString() : "");
  xhrGET(url, (data) => {
    renderTabla(data);

    // Sugerencias (solo si hay término)
    sugerencias.innerHTML = "";
    if (filtroTerm && Array.isArray(data) && data.length > 0) {
      data.slice(0, 8).forEach((t) => {
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.type = "button";
        item.textContent = t.nombre_ciudad;
        item.onclick = () => {
          inputBuscar.value = t.nombre_ciudad;
          filtroTerm = t.nombre_ciudad;
          sugerencias.innerHTML = "";
          sugerencias.style.display = "none";
          aplicarFiltros();
        };
        sugerencias.appendChild(item);
      });
      sugerencias.style.display = "block";
    } else {
      sugerencias.style.display = "none";
    }
  });
}

// Eventos
inputBuscar?.addEventListener("keyup", function () {
  filtroTerm = this.value.trim();
  if (filtroTerm === "") {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
  }
  aplicarFiltros();
});

selectEstado?.addEventListener("change", function () {
  filtroEstado = this.value === "" ? "" : this.value;
  aplicarFiltros();
});

// Cerrar sugerencias si se hace clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar?.contains(e.target) && !sugerencias?.contains(e.target)) {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
  }
});
 