const tabla=document.getElementById("tablaBrokers");
const form=document.getElementById("formBroker");
const modal=new bootstrap.Modal(document.getElementById("modalRegistrarBroker"));
const btnAgregarBroker=document.getElementById("btnAgregarBroker");
const inputBuscar      = document.getElementById("buscarBroker");
const sugerenciasBox   = document.getElementById("sugerenciasBroker"); 
// Campos del formulario
const fldId        = document.getElementById("id");
const fldNombre    = document.getElementById("nombre"); 
const fldContacto    = document.getElementById("contacto");

listar();
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "brokers/listar", true);
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
      <td>${item.nombre}</td>
      <td>${item.contacto}</td> 
      <td>
        <button class="btn btn-sm btn-info" onclick="editarBroker(${item.id_broker})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarBroker(${item.id_broker})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}  

form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id        = fldId.value.trim();
  const nombre    = fldNombre.value.trim();
  const contacto = fldContacto.value.trim(); 

  if (!nombre || !contacto ) {
    Swal.fire("Campos requeridos", "Completa nombre y contacto", "warning");
    return;
  }

  const fd = new FormData();
  if (id !== "") fd.append("id", id); 
  fd.append("nombre", nombre);
  fd.append("contacto", contacto); 

  const url = base_url + (id === "" ? "brokers/registrar" : "brokers/actualizar");

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

        document.getElementById("modalRegistrarBrokerLabel").textContent = "Registrar Broker";
        const btnSubmit = form.querySelector('button[type="submit"]');
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

btnAgregarBroker.addEventListener("click", () => {
  form.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarBrokerLabel").textContent = "Registrar Broker";
  const btnSubmit = form.querySelector('button[type="submit"]');
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});


function editarBroker(id) {
  const http = new XMLHttpRequest();
  const url= base_url + "brokers/editar/" + id;
  http.open("GET",url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
        console.log(this.responseText);
      const data = JSON.parse(this.responseText);
      document.getElementById("id").value = data.id;   
      form.nombre.value = data.nombre || "";
      form.contacto.value  = data.contacto || ""; 
      document.getElementById("modalRegistrarBrokerLabel").textContent = "Editar Broker";
      document.getElementById("btnSubmit").innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
}
// Eliminar
function eliminarBroker(id) {    
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
      const url=base_url + "Brokers/eliminar/" + id;
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
// === BÚSQUEDA + SUGERENCIAS ===
inputBuscar.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
    listar();
    return;
  }

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Brokers/buscar?term=" + encodeURIComponent(term), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error buscar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }

      // refresca la tabla con resultados
      renderTabla(data);

      // sugerencias (opcional)
      if (!sugerenciasBox) return;
      sugerenciasBox.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasBox.style.display = "none";
        return;
      }

      data.slice(0, 8).forEach((b) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${b.nombre} — ${b.contacto}`;
        btn.onclick = () => {
          inputBuscar.value = b.nombre;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listarBodegasFiltradas(b.nombre);
        };
        sugerenciasBox.appendChild(btn);
      });
      sugerenciasBox.style.display = "block";
    }
  };
});

// Cerrar sugerencias clic fuera
document.addEventListener("click", function (e) {
  if (!sugerenciasBox) return;
  if (!inputBuscar.contains(e.target) && !sugerenciasBox.contains(e.target)) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});
