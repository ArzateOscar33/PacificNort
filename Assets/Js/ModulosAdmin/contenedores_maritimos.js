// assets/js/modulosAdmin/contenedores_maritimos.js

const tabla            = document.getElementById("tablaContenedoresMaritimos");
const form             = document.getElementById("formContenedorMaritimo");
const modal            = new bootstrap.Modal(document.getElementById("modalRegistrarContenedorMaritimo"));
const btnAgregar       = document.getElementById("btnAgregarContenedorMaritimo");
const inputBuscar      = document.getElementById("buscarContenedorMaritimo");
const sugerenciasBox   = document.getElementById("sugerenciasContenedores");

// Campos del form
const fldId      = document.getElementById("id_contenedor");
const fldNumero  = document.getElementById("numero_contenedor");
const fldTipo    = document.getElementById("tipo"); 
const fldObs     = document.getElementById("observaciones");

// =============== LISTAR ===============
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Contenedores_maritimos/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error listar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); return; }
      renderTabla(data);
    }
  };
}

function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = "<tr><td colspan='5' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${item.numero_contenedor}</td>
      <td>${item.tipo}</td>
      <td>${item.observaciones ?? ""}</td>
      <td>
        <button class="btn btn-sm btn-info"   onclick="editarContenedorMaritimo(${item.id_contenedor})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarContenedorMaritimo(${item.id_contenedor})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// =============== ABRIR MODAL (AGREGAR) ===============
btnAgregar?.addEventListener("click", () => {
  form.reset();
  if (fldId) fldId.value = "";
  document.getElementById("modalRegistrarContenedorMaritimoLabel").textContent = "Registrar Contenedor Marítimo";
  const btnSubmit = document.getElementById("btnSubmit");
  if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// =============== SUBMIT (REGISTRAR / ACTUALIZAR) ===============
form?.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_contenedor     = (fldId?.value || "").trim();
  const numero_contenedor = (fldNumero?.value || "").trim().toUpperCase();
  const tipo              = (fldTipo?.value || "").trim(); // puede ir vacío; backend lo transforma a NULL
  const observaciones     = (fldObs?.value || "").trim();

  if (!numero_contenedor || !tipo) {
    Swal.fire("Campos requeridos", "Completa número de contenedor y tipo", "warning");
    return;
  }

  const fd = new FormData();
  if (id_contenedor) fd.append("id_contenedor", id_contenedor);
  fd.append("numero_contenedor", numero_contenedor);
  fd.append("tipo", tipo);
  fd.append("observaciones", observaciones);

  const url = base_url + (id_contenedor === "" ? "Contenedores_maritimos/registrar" : "Contenedores_maritimos/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error guardar:", this.responseText); Swal.fire("Error", "No se pudo guardar", "error"); return; }
      let res;
      try { res = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      Swal.fire(res.status === "success" ? "Éxito" : "Atención", res.msg, res.status);
      if (res.status === "success") {
        form.reset();
        if (fldId) fldId.value = "";
        modal.hide();
        listar();
        document.getElementById("modalRegistrarContenedorMaritimoLabel").textContent = "Registrar Contenedor Marítimo";
        const btnSubmit = document.getElementById("btnSubmit");
        if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

// =============== EDITAR ===============
window.editarContenedorMaritimo = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Contenedores_maritimos/editar/" + id, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error obtener:", this.responseText); Swal.fire("Error", "No se pudo cargar el contenedor", "error"); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

      if (fldId)      fldId.value      = data.id_contenedor;
      if (fldNumero)  fldNumero.value  = data.numero_contenedor || "";
      if (fldTipo)    fldTipo.value    = data.tipo || ""; 
      if (fldObs)     fldObs.value     = data.observaciones || "";

      document.getElementById("modalRegistrarContenedorMaritimoLabel").textContent = "Editar Contenedor Marítimo";
      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
};

// =============== ELIMINAR (LÓGICO) ===============
window.eliminarContenedorMaritimo = function (id) {
  Swal.fire({
    title: "¿Desactivar contenedor?",
    text: "Podrás reactivarlo registrándolo de nuevo con el mismo número.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Contenedores_maritimos/eliminar/" + id, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) { console.error("Error eliminar:", this.responseText); Swal.fire("Error", "No se pudo eliminar", "error"); return; }
        let res;
        try { res = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); Swal.fire("Error", "Respuesta no válida", "error"); return; }

        Swal.fire(res.status === "success" ? "Eliminado" : "Atención", res.msg, res.status);
        if (res.status === "success") listar();
      }
    };
  });
};
// =============== BÚSQUEDA + SUGERENCIAS ===============
let buscarTimer;

inputBuscar?.addEventListener("keyup", function () {
  const term = this.value.trim();

  // Debounce: espera 250ms antes de buscar
  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(() => {
    if (term === "") {
      if (sugerenciasBox) { sugerenciasBox.innerHTML = ""; sugerenciasBox.style.display = "none"; }
      listar();
      return;
    }

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Contenedores_maritimos/buscar?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) { console.error("Error buscar:", this.responseText); return; }
        let data;
        try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); return; }

        // refrescar tabla con resultados
        renderTabla(data);

        // pintar sugerencias
        if (!sugerenciasBox) return;
        sugerenciasBox.innerHTML = "";
        if (!Array.isArray(data) || data.length === 0) {
          sugerenciasBox.style.display = "none";
          return;
        }

        data.slice(0, 8).forEach((c) => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "list-group-item list-group-item-action";
          // <<< texto visible de cada sugerencia
          btn.textContent = `${c.numero_contenedor} ${c.tipo ? `(${c.tipo})` : ""}`.trim();

          btn.onclick = () => {
            if (inputBuscar) inputBuscar.value = c.numero_contenedor;
            sugerenciasBox.innerHTML = "";
            sugerenciasBox.style.display = "none";
            // si quieres evitar otra petición, puedes renderizar directo:
            renderTabla([c]); 
            // o, si prefieres volver a consultar exacto:
            // listarContenedoresFiltrados(c.numero_contenedor);
          };
          sugerenciasBox.appendChild(btn);
        });
        sugerenciasBox.style.display = "block";
      }
    };
  }, 250);
});

// Helper para filtrar por término exacto/seleccionado (si lo usas)
function listarContenedoresFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Contenedores_maritimos/buscar?term=" + encodeURIComponent(termino), true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) { console.error("Error filtrar:", this.responseText); return; }
      let data;
      try { data = JSON.parse(this.responseText); } catch { console.error("JSON inválido:", this.responseText); return; }
      renderTabla(data);
    }
  };
}
