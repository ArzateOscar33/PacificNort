// assets/js/modulosAdmin/navieras.js

const tabla              = document.getElementById("tablaNavieras");
const form               = document.getElementById("formNaviera");
const modal              = new bootstrap.Modal(document.getElementById("modalRegistrarNaviera"));
const btnAgregarNaviera  = document.getElementById("btnAgregarNaviera");
const inputBuscar        = document.getElementById("buscarNaviera");
const sugerenciasBox     = document.getElementById("sugerenciasNavieras");

// Campos del formulario
const fldId        = document.getElementById("id_naviera");
const fldNombre    = document.getElementById("nombre");
const fldContacto  = document.getElementById("contacto");

// =============== LISTAR ===============
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Navieras/listar", true);
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
    tabla.innerHTML = "<tr><td colspan='3' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.nombre}</td>
      <td>${item.contacto}</td>
      <td>
        <button class="btn btn-sm btn-info"    onclick="editarNaviera(${item.id_naviera})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger"  onclick="eliminarNaviera(${item.id_naviera})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// =============== ABRIR MODAL (AGREGAR) ===============
btnAgregarNaviera.addEventListener("click", () => {
  form.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarNavieraLabel").textContent = "Registrar Naviera";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// =============== SUBMIT (REGISTRAR / ACTUALIZAR) ===============
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_naviera = fldId.value.trim();
  const nombre     = fldNombre.value.trim();
  const contacto   = fldContacto.value.trim();

  if (!nombre || !contacto) {
    Swal.fire("Campos requeridos", "Completa nombre y contacto", "warning");
    return;
  }

  const fd = new FormData();
  if (id_naviera !== "") fd.append("id_naviera", id_naviera); // <- clave correcta para actualizar
  fd.append("nombre", nombre);
  fd.append("contacto", contacto);

  const url = base_url + (id_naviera === "" ? "Navieras/registrar" : "Navieras/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error guardar:", this.responseText); Swal.fire("Error", "No se pudo guardar", "error"); return; }
      let res;
      try { res = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg, res.status);
      if (res.status === "success") {
        form.reset();
        fldId.value = "";
        modal.hide();
        listar();

        // Volver a modo Agregar
        document.getElementById("modalRegistrarNavieraLabel").textContent = "Registrar Naviera";
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

// =============== EDITAR ===============
window.editarNaviera = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Navieras/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error obtener:", this.responseText); Swal.fire("Error", "No se pudo cargar la naviera", "error"); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      fldId.value       = data.id_naviera;
      fldNombre.value   = data.nombre || "";
      fldContacto.value = data.contacto || "";

      document.getElementById("modalRegistrarNavieraLabel").textContent = "Editar Naviera";
      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
};

// =============== ELIMINAR (LÓGICO) ===============
window.eliminarNaviera = function (id) {
  Swal.fire({
    title: "¿Desactivar naviera?",
    text: "Podrás reactivarla más adelante si lo decides.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Navieras/eliminar/" + id, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) { console.error("Error eliminar:", this.responseText); Swal.fire("Error", "No se pudo eliminar", "error"); return; }
        let res;
        try { res = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

        Swal.fire(res.status === "success" ? "Eliminado" : "Atención", res.msg, res.status);
        if (res.status === "success") listar();
      }
    };
  });
};

// =============== BÚSQUEDA + SUGERENCIAS ===============
inputBuscar.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
    listar();
    return;
  }

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Navieras/buscar?term=" + encodeURIComponent(term), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error buscar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }

      // refrescar tabla
      renderTabla(data);

      // sugerencias
      if (!sugerenciasBox) return;
      sugerenciasBox.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasBox.style.display = "none";
        return;
      }

      data.slice(0, 8).forEach((n) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${n.nombre} — ${n.contacto}`;
        btn.onclick = () => {
          inputBuscar.value = n.nombre;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listarNavierasFiltradas(n.nombre);
        };
        sugerenciasBox.appendChild(btn);
      });
      sugerenciasBox.style.display = "block";
    }
  };
});

// Cerrar sugerencias al hacer clic fuera
document.addEventListener("click", function (e) {
  if (!sugerenciasBox) return;
  if (!inputBuscar.contains(e.target) && !sugerenciasBox.contains(e.target)) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// Helper para filtrar por término seleccionado
function listarNavierasFiltradas(termino) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Navieras/buscar?term=" + encodeURIComponent(termino), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error filtrar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }
      renderTabla(data);
    }
  };
}
