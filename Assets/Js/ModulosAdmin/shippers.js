 

const tabla           = document.getElementById("tablaShippers");
const form            = document.getElementById("formShipper");
const modal           = new bootstrap.Modal(document.getElementById("modalRegistrarShipper"));
const btnAgregar      = document.getElementById("btnAgregarShipper");
const inputBuscar     = document.getElementById("buscarShipper"); // <-- pon este id en la vista
const sugerenciasBox  = document.getElementById("sugerenciasShippers");

// Campos del form
const fldId         = document.getElementById("id_shipper");
const fldNombre     = document.getElementById("nombre");
const fldContacto   = document.getElementById("contacto");
const fldDireccion  = document.getElementById("direccion");

// ================= LISTAR =================
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Shippers/listar", true);
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
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${item.nombre}</td>
      <td>${item.contacto}</td>
      <td>${item.direccion}</td>
      <td>
        <button class="btn btn-sm btn-info"   onclick="editarShipper(${item.id_shipper})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarShipper(${item.id_shipper})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// ================= ABRIR MODAL (AGREGAR) =================
btnAgregar.addEventListener("click", () => {
  form.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarShipperLabel").textContent = "Registrar Shipper";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// ================= SUBMIT (REGISTRAR / ACTUALIZAR) =================
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_shipper = (fldId?.value || "").trim();
  const nombre     = (fldNombre?.value || "").trim();
  const contacto   = (fldContacto?.value || "").trim();
  const direccion  = (fldDireccion?.value || "").trim();

  if (!nombre || !contacto || !direccion) {
    Swal.fire("Campos requeridos", "Completa nombre, contacto y dirección", "warning");
    return;
  }

  const fd = new FormData();
  if (id_shipper !== "") fd.append("id_shipper", id_shipper);
  fd.append("nombre", nombre);
  fd.append("contacto", contacto);
  fd.append("direccion", direccion);

  const url = base_url + (id_shipper === "" ? "Shippers/registrar" : "Shippers/actualizar");

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
        document.getElementById("modalRegistrarShipperLabel").textContent = "Registrar Shipper";
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

// ================= EDITAR =================
window.editarShipper = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Shippers/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error obtener:", this.responseText); Swal.fire("Error", "No se pudo cargar el shipper", "error"); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      fldId.value        = data.id_shipper;
      fldNombre.value    = data.nombre || "";
      fldContacto.value  = data.contacto || "";
      fldDireccion.value = data.direccion || "";

      document.getElementById("modalRegistrarShipperLabel").textContent = "Editar Shipper";
      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
};

// ================= ELIMINAR (LÓGICO) =================
window.eliminarShipper = function (id) {
  Swal.fire({
    title: "¿Desactivar shipper?",
    text: "Podrás reactivarlo más adelante si lo necesitas.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Shippers/eliminar/" + id, true);
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

// ================= BÚSQUEDA + SUGERENCIAS =================
if (inputBuscar) {
  inputBuscar.addEventListener("keyup", function () {
    const term = this.value.trim();

    if (term === "") {
      if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
      listar();
      return;
    }

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Shippers/buscar?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) { console.error("Error buscar:", this.responseText); return; }
        let data;
        try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); return; }

        // refrescar tabla con resultados
        renderTabla(data);

        // sugerencias
        if (!sugerenciasBox) return;
        sugerenciasBox.innerHTML = "";
        if (!Array.isArray(data) || data.length === 0) {
          sugerenciasBox.style.display = "none";
          return;
        }

        data.slice(0, 8).forEach((s) => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "list-group-item list-group-item-action";
          btn.textContent = `${s.nombre} — ${s.contacto}`;
          btn.onclick = () => {
            inputBuscar.value = s.nombre;
            sugerenciasBox.innerHTML = "";
            sugerenciasBox.style.display = "none";
            listarShippersFiltrados(s.nombre);
          };
          sugerenciasBox.appendChild(btn);
        });
        sugerenciasBox.style.display = "block";
      }
    };
  });

  // Ocultar sugerencias al hacer clic fuera
  document.addEventListener("click", function (e) {
    if (!sugerenciasBox) return;
    if (!inputBuscar.contains(e.target) && !sugerenciasBox.contains(e.target)) {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
    }
  });
}

// Helper para filtrar por término seleccionado
function listarShippersFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Shippers/buscar?term=" + encodeURIComponent(termino), true);
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
