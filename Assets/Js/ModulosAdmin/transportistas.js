// assets/js/modulosAdmin/transportistas.js

const tabla                 = document.getElementById("tablaTransportistas");
const form                  = document.getElementById("formTransportista");
const modal                 = new bootstrap.Modal(document.getElementById("modalRegistrarTransportista"));
const btnAgregar            = document.getElementById("btnAgregarTransportista");
const inputBuscar           = document.getElementById("buscarTransportista");
const sugerenciasBox        = document.getElementById("sugerenciasTransportistas");

// Campos del form
const fldId     = document.getElementById("id_transportista");
const fldNombre = document.getElementById("nombre");
const fldTipo   = document.getElementById("tipo");

// =============== LISTAR ===============
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Transportistas/listar", true);
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
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${item.nombre}</td>
      <td>${item.tipo}</td>
      <td>
        <button class="btn btn-sm btn-info"   onclick="editarTransportista(${item.id_transportista})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarTransportista(${item.id_transportista})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// =============== ABRIR MODAL (AGREGAR) ===============
btnAgregar.addEventListener("click", () => {
  form.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarTransportistaLabel").textContent = "Registrar Transportista";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// =============== SUBMIT (REGISTRAR / ACTUALIZAR) ===============
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_transportista = (fldId?.value || "").trim();
  const nombre           = (fldNombre?.value || "").trim();
  const tipo             = (fldTipo?.value || "").trim();

  if (!nombre || !tipo) {
    Swal.fire("Campos requeridos", "Completa nombre y tipo", "warning");
    return;
  }

  const fd = new FormData();
  if (id_transportista !== "") fd.append("id_transportista", id_transportista);
  fd.append("nombre", nombre);
  fd.append("tipo", tipo);

  const url = base_url + (id_transportista === "" ? "Transportistas/registrar" : "Transportistas/actualizar");

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
        document.getElementById("modalRegistrarTransportistaLabel").textContent = "Registrar Transportista";
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

// =============== EDITAR ===============
window.editarTransportista = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Transportistas/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error obtener:", this.responseText); Swal.fire("Error", "No se pudo cargar el transportista", "error"); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch (e) { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      fldId.value     = data.id_transportista;
      fldNombre.value = data.nombre || "";
      fldTipo.value   = data.tipo || "";

      document.getElementById("modalRegistrarTransportistaLabel").textContent = "Editar Transportista";
      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
};

// =============== ELIMINAR (LÓGICO) ===============
window.eliminarTransportista = function (id) {
  Swal.fire({
    title: "¿Desactivar transportista?",
    text: "Podrás reactivarlo más adelante si lo necesitas.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Transportistas/eliminar/" + id, true);
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
  http.open("GET", base_url + "Transportistas/buscar?term=" + encodeURIComponent(term), true);
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

      data.slice(0, 8).forEach((t) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${t.nombre} — ${t.tipo}`;
        btn.onclick = () => {
          inputBuscar.value = t.nombre;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listarTransportistasFiltrados(t.nombre);
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

// Helper para filtrar por término exacto/seleccionado
function listarTransportistasFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Transportistas/buscar?term=" + encodeURIComponent(termino), true);
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
