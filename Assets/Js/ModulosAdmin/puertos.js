const btnAgregarPuerto=document.getElementById("btnAgregarPuerto");
const tabla=document.getElementById("tablaPuertos");
const form= document.getElementById("formPuerto");
  
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarPuerto"));
const inputBuscar   = document.getElementById("buscarPuerto");
const sugerenciasEl = document.getElementById("sugerenciasPuerto");
const btnAgregarEstados=document.getElementById("btnAgregarCiudad");
const idEstado=document.getElementById("estado_id");

document.getElementById("btnAgregarPuerto").addEventListener("click", () => {
  form.reset();
  document.getElementById("id_puerto").value = ""; // limpiar ID
  document.getElementById("modalRegistrarPuertoLabel").textContent = "Registrar Puerto";
  document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});


listar();

// Listar
function listar() {
  const http = new XMLHttpRequest();
  const url= base_url + "puertos/listar"
  http.open("GET", url, true);
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
      <td>${t.nombre}</td> 
      <td>${t.nombre_ciudad}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarPuerto(${t.id_puerto})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarPuerto(${t.id_puerto})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// Submit (registrar/actualizar)
form.addEventListener("submit", function (e) {
      
  e.preventDefault();
  const http = new XMLHttpRequest();
  http.open("POST", base_url + "puertos/registrar", true);
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


function editarPuerto(id) {
  const http = new XMLHttpRequest();
  const url = base_url + "puertos/editar/" + id;
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
 
      document.getElementById("id_puerto").value = data.id_puerto;

      form.nombre_puerto.value = data.nombre || "";
      form.ciudad_id.value = data.ciudad_id;

      document.getElementById("modalRegistrarPuertoLabel").textContent = "Editar Puerto";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Eliminar
function eliminarPuerto(id) {    
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
      http.open("GET", base_url + "puertos/eliminar/" + id, true);
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
  http.open("GET", base_url + "puertos/buscar?term=" + encodeURIComponent(term), true);
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

 // --- refs 
const sugerencias   = document.getElementById("sugerenciasPuerto");
const selectCiudad  = document.getElementById("ciudades_filtro");

// Estado de filtros
let filtroTerm   = "";
let filtroCiudad = "";

// Listar inicial (sin filtros)
document.addEventListener("DOMContentLoaded", () => {
  aplicarFiltros();
});

// Refs
 

// Inicial
document.addEventListener("DOMContentLoaded", () => {
  aplicarFiltros();
});

// Filtro combinado (term + ciudad_id) SIN helper
function aplicarFiltros() {
  const params = [];
  if (filtroTerm)   params.push("term=" + encodeURIComponent(filtroTerm));
  if (filtroCiudad) params.push("ciudad_id=" + encodeURIComponent(filtroCiudad));
  const url = base_url + "Puertos/filtrar" + (params.length ? "?" + params.join("&") : "");

  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); }
      catch (e) { console.error("JSON inválido:", this.responseText); return; }

      // Renderiza tu tabla (usa tu función existente)
      renderTabla(data);

      // Sugerencias (solo si hay término)
      sugerencias.innerHTML = "";
      if (filtroTerm && Array.isArray(data) && data.length > 0) {
        data.slice(0, 8).forEach((p) => {
          const item = document.createElement("button");
          item.classList.add("list-group-item", "list-group-item-action");
          item.type = "button";
          item.textContent = p.nombre; // nombre del puerto
          item.onclick = () => {
            inputBuscar.value = p.nombre;
            filtroTerm = p.nombre;
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
    }
  };
}

// Eventos
inputBuscar.addEventListener("keyup", function () {
  filtroTerm = this.value.trim();
  if (filtroTerm === "") {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
  }
  aplicarFiltros();
});

selectCiudad.addEventListener("change", function () {
  filtroCiudad = this.value === "" ? "" : this.value; // '' o id_ciudad
  aplicarFiltros();
});

// Cerrar sugerencias si se hace clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar.contains(e.target) && !sugerencias.contains(e.target)) {
    sugerencias.innerHTML = "";
    sugerencias.style.display = "none";
  }
});

// Llama a esto después de registrar/actualizar/eliminar para refrescar respetando filtros
function refrescarPuertos() {
  aplicarFiltros();
}
