// Assets/Js/ModulosAdmin/bodegas.js

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
const fldCodigo = document.getElementById("codigo");
const fldTipo = document.getElementById("tipo_ubicacion");

const prefijosBodega = {
  BODEGA: "BD",
  YARDA: "YD",
  ESTACIONAMIENTO: "ES",
};

// Evita insertar contenido de la BD como HTML ejecutable en la tabla.
function escBodega(v) {
  return String(v ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      })[c],
  );
}

// Generar nomenclatura del tipo seleccionado, conservando las dos letras finales.
fldTipo.addEventListener("change", function () {
  const actual = fldCodigo.value.trim().toUpperCase();
  const final = /^(BD|YD|ES)/.test(actual) ? actual.slice(2, 4) : "";
  fldCodigo.value = (prefijosBodega[fldTipo.value] || "") + final;
});

fldCodigo.addEventListener("input", function () {
  this.value = this.value
    .toUpperCase()
    .replace(/[^A-Z]/g, "")
    .slice(0, 4);
});

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
      "<tr><td colspan='6' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const row = document.createElement("tr");
    row.classList.add("text-center");
    const tipo =
      {
        BODEGA: "Bodega",
        YARDA: "Yarda",
        ESTACIONAMIENTO: "Estacionamiento",
      }[item.tipo_ubicacion] ||
      item.tipo_ubicacion ||
      "Bodega";

    row.innerHTML = `
      <td><strong>${escBodega(item.codigo || "—")}</strong></td>
      <td>${escBodega(tipo)}</td>
      <td>${escBodega(item.nombre)}</td>
      <td>${escBodega(item.direccion)}</td>
      <td>${escBodega(item.nombre_ciudad)}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarBodega(${Number(item.id_bodega)})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarBodega(${Number(item.id_bodega)})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(row);
  });
}

// ===================== ABRIR MODAL (AGREGAR) =====================
btnAgregarBodega.addEventListener("click", () => {
  formBodega.reset();
  fldId.value = "";
  fldTipo.value = "BODEGA";
  fldCodigo.value = "BD";
  document.getElementById("modalRegistrarBodegaLabel").textContent =
    "Registrar Ubicación";
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
  const codigo = fldCodigo.value.trim().toUpperCase();
  const tipo_ubicacion = fldTipo.value;

  if (!nombre || !direccion || !ciudad_id || !codigo || !tipo_ubicacion) {
    Swal.fire(
      "Campos requeridos",
      "Completa tipo, código, nombre, dirección y ciudad",
      "warning",
    );
    return;
  }

  // Validación sencilla adicional. El controlador también valida.
  const prefijo = prefijosBodega[tipo_ubicacion];
  if (!prefijo || !new RegExp(`^${prefijo}[A-Z]{2}$`).test(codigo)) {
    Swal.fire(
      "Código inválido",
      `Para ${tipo_ubicacion.toLowerCase()} escribe ${prefijo || "BD/YD/ES"} y dos letras (ej. YDTE)`,
      "warning",
    );
    return;
  }

  const fd = new FormData();
  if (id !== "") fd.append("id", id);
  fd.append("nombre", nombre);
  fd.append("direccion", direccion);
  fd.append("ciudad_id", ciudad_id);
  fd.append("codigo", codigo);
  fd.append("tipo_ubicacion", tipo_ubicacion);

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
          "Registrar Ubicación";
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
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        Swal.fire("Error", "Respuesta no válida", "error");
        return;
      }
      if (!data || !data.id_bodega) {
        Swal.fire("Error", "Ubicación no encontrada", "error");
        return;
      }
      fldId.value = data.id_bodega;
      fldNombre.value = data.nombre || "";
      fldDireccion.value = data.direccion || "";
      fldCiudad.value = data.ciudad_id || "";
      // Recuperar tipo y código de la ubicación.
      const tipoGuardado = String(
        data.tipo_ubicacion || "BODEGA",
      ).toUpperCase();

      fldTipo.value = prefijosBodega[tipoGuardado] ? tipoGuardado : "BODEGA";

      fldCodigo.value =
        String(data.codigo || "")
          .trim()
          .toUpperCase() || prefijosBodega[fldTipo.value];
      document.getElementById("modalRegistrarBodegaLabel").textContent =
        "Editar Ubicación";
      document.getElementById("btnSubmit").innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modalBodega.show();
    }
  };
}

// Eliminar (baja lógica original)
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
          let res;
          try {
            res = JSON.parse(this.responseText);
          } catch (e) {
            Swal.fire("Error", "Respuesta no válida", "error");
            return;
          }
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

      renderTabla(data);

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
        btn.textContent = `${b.codigo || "—"} — ${b.nombre} — ${b.nombre_ciudad}`;
        btn.onclick = () => {
          inputBuscar.value = b.codigo || b.nombre;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listarBodegasFiltradas(b.codigo || b.nombre);
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
