 const tabla= document.getElementById("tablaClientes");
listar();
// Listar
function listar() {
  const http = new XMLHttpRequest();
  const url=base_url + "clientes/listar";
  http.open("GET", url, true);
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
    tabla.innerHTML = "<tr><td colspan='6' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.nombre}</td>
      <td>${item.rfc}</td>
      <td>${item.telefono}</td> 
      <td>${item.correo}</td>
      <td>${item.direccion}</td> 
      <td>
        <button class="btn btn-sm btn-info" onclick="editarCliente(${item.id_cliente})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarCliente(${item.id_cliente})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

//dar de alta y actualizar

const form  = document.getElementById("formClientes");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarCliente"));
const btnAgregarCliente = document.getElementById("btnAgregarCliente");

btnAgregarCliente.addEventListener("click", () => {
  form.reset();
  document.getElementById("id_cliente").value = "";
  document.getElementById("modalRegistrarClienteLabel").textContent = "Registrar Cliente";
  document.getElementById("btnSubmit").innerHTML='<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});


// Submit (registrar)
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const http = new XMLHttpRequest();
  const url =base_url + "Clientes/registrar";
  http.open("POST", url, true);
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

 
function editarCliente(id) {
  const http = new XMLHttpRequest();
  const url= base_url + "Clientes/editar/" + id;
  http.open("GET",url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
        console.log(this.responseText);
      const data = JSON.parse(this.responseText);
      document.getElementById("id_cliente").value = data.id_cliente;   
      form.nombre.value = data.nombre || "";
      form.rfc.value  = data.rfc || "";
      form.correo.value    = data.correo || "";
      form.telefono.value  = data.telefono || "";
      form.direccion.value = data.direccion || "";
      document.getElementById("modalRegistrarClienteLabel").textContent = "Editar Cliente";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

// Eliminar
function eliminarCliente(id) {    
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
      const url=base_url + "Clientes/eliminar/" + id;
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

const inputBuscarCliente = document.getElementById("buscarCliente");
const sugerenciasCliente = document.getElementById("sugerenciasCliente");

// Buscar + sugerencias
inputBuscarCliente?.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    sugerenciasCliente.innerHTML = "";
    sugerenciasCliente.style.display = "none";
    listar(); // vuelve a cargar todo
    return;
  }

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Clientes/buscar?term=" + encodeURIComponent(term), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      let data;
      try { data = JSON.parse(this.responseText); }
      catch (e) { console.error("JSON inválido:", this.responseText); return; }

      // refresca tabla con resultados
      renderTabla(data);

      // pinta sugerencias
      sugerenciasCliente.innerHTML = "";
      if (Array.isArray(data) && data.length > 0) {
        data.slice(0, 8).forEach((c) => {
          const item = document.createElement("button");
          item.classList.add("list-group-item", "list-group-item-action");
          item.type = "button";
          item.textContent = `${c.nombre} — ${c.rfc || c.correo || ''}`.trim();
          item.onclick = () => {
            inputBuscarCliente.value = c.nombre;
            sugerenciasCliente.innerHTML = "";
            sugerenciasCliente.style.display = "none";
            renderTabla([c]); // mostrar solo el seleccionado
          };
          sugerenciasCliente.appendChild(item);
        });
        sugerenciasCliente.style.display = "block";
      } else {
        sugerenciasCliente.style.display = "none";
      }
    }
  };
});

// Cerrar sugerencias si clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscarCliente?.contains(e.target) && !sugerenciasCliente?.contains(e.target)) {
    sugerenciasCliente.innerHTML = "";
    sugerenciasCliente.style.display = "none";
  }
});
