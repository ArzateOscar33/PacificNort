// assets/js/modulosAdmin/bodegas.js

const tabla = document.getElementById("tablaBodegas");
const formBodega = document.getElementById("formBodega");
const modalBodega = new bootstrap.Modal(
  document.getElementById("modalRegistrarBodega"),
);
const btnAgregarBodega = document.getElementById("btnAgregarBodega");
const inputBuscar = document.getElementById("buscarBodega");
const sugerenciasBox = document.getElementById("sugerenciasBodega");

// Campos del formulario
const fldId = document.getElementById("id");
const fldNombre = document.getElementById("nombre");
const fldDireccion = document.getElementById("direccion");
const fldCiudad = document.getElementById("ciudad_id");

// ===================== LISTAR =====================
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Bodegas/listar", true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error listar:", this.responseText);
        return;
      }
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        return;
      }
      renderTabla(data);
    }
  };
}

function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML =
      "<tr><td colspan='4' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    row.innerHTML = `
      <td>${item.nombre}</td>
      <td>${item.direccion}</td>
      <td>${item.nombre_ciudad}</td> 
      <td>
        <button class="btn btn-sm btn-info" onclick="editarBodega(${item.id_bodega})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarBodega(${item.id_bodega})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// ===================== ABRIR MODAL (AGREGAR) =====================
btnAgregarBodega.addEventListener("click", () => {
  formBodega.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarBodegaLabel").textContent =
    "Registrar Bodega";
  const btnSubmit = formBodega.querySelector('button[type="submit"]');
  btnSubmit.innerHTML =
    '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

formBodega.addEventListener("submit", function (e) {
  e.preventDefault();

  const id = fldId.value.trim();
  const nombre = fldNombre.value.trim();
  const direccion = fldDireccion.value.trim();
  const ciudad_id = fldCiudad.value.trim();

  if (!nombre || !direccion || !ciudad_id) {
    Swal.fire(
      "Campos requeridos",
      "Completa nombre, dirección y ciudad",
      "warning",
    );
    return;
  }

  const fd = new FormData();
  if (id !== "") fd.append("id", id); // <-- si hay id, será actualización
  fd.append("nombre", nombre);
  fd.append("direccion", direccion);
  fd.append("ciudad_id", ciudad_id);

  const url =
    base_url + (id === "" ? "Bodegas/registrar" : "Bodegas/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(fd);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error guardar:", this.responseText);
        Swal.fire("Error", "No se pudo guardar", "error");
        return;
      }
      let res;
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        Swal.fire("Error", "Respuesta no válida", "error");
        return;
      }

      Swal.fire(
        res.status === "success" ? "Éxito" : "Atención",
        res.msg,
        res.status,
      );
      if (res.status === "success") {
        formBodega.reset();
        fldId.value = "";
        modalBodega.hide();
        listar();

        document.getElementById("modalRegistrarBodegaLabel").textContent =
          "Registrar Bodega";
        const btnSubmit = formBodega.querySelector('button[type="submit"]');
        btnSubmit.innerHTML =
          '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
});

function editarBodega(id) {
  const http = new XMLHttpRequest();
  const url = base_url + "Bodegas/editar/" + id;
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      //console.log(this.responseText);
      const data = JSON.parse(this.responseText);
      document.getElementById("id").value = data.id_bodega;
      formBodega.nombre.value = data.nombre || "";
      formBodega.direccion.value = data.direccion || "";
      formBodega.ciudad_id.value = data.ciudad_id || "";
      document.getElementById("modalRegistrarBodegaLabel").textContent =
        "Editar Bodega";
      document.getElementById("btnSubmit").innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modalBodega.show();
    }
  };
}

// Eliminar
function eliminarBodega(id) {
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
      const url = base_url + "Bodegas/eliminar/" + id;
      http.open("GET", url, true);
      http.send();
      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          //console.log(this.responseText);
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
    if (sugerenciasBox) {
      sugerenciasBox.innerHTML = "";
      sugerenciasBox.style.display = "none";
    }
    listar();
    return;
  }

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Bodegas/buscar?term=" + encodeURIComponent(term),
    true,
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error buscar:", this.responseText);
        return;
      }
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        return;
      }

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
        btn.textContent = `${b.nombre} — ${b.nombre_ciudad}`;
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

// Helper para filtrar por término seleccionado (usa la misma ruta)
function listarBodegasFiltradas(termino) {
  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Bodegas/buscar?term=" + encodeURIComponent(termino),
    true,
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error filtrar:", this.responseText);
        return;
      }
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        return;
      }
      renderTabla(data);
    }
  };
}
