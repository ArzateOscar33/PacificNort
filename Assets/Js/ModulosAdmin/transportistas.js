// Assets/Js/ModulosAdmin/transportistas.js

const tabla = document.getElementById("tablaTransportistas");
const form = document.getElementById("formTransportista");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarTransportista"),
);
const btnAgregar = document.getElementById("btnAgregarTransportista");
const inputBuscar = document.getElementById("buscarTransportista");
const sugerenciasBox = document.getElementById("sugerenciasTransportistas");

// Campos del form
const fldId = document.getElementById("id_transportista");
const fldNombre = document.getElementById("nombre");
const fldTipo = document.getElementById("tipo");
const fldCodigo = document.getElementById("codigo");

// Nomenclatura de cuatro letras mayúsculas (ej. TSAN).
fldCodigo.addEventListener("input", function () {
  this.value = this.value
    .toUpperCase()
    .replace(/[^A-Z]/g, "")
    .slice(0, 4);
});

// Evitar insertar nombres ingresados por usuarios como HTML ejecutable.
function escTransportista(valor) {
  return String(valor ?? "").replace(/[&<>"']/g, function (c) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[c];
  });
}

// =============== LISTAR ===============
window.addEventListener("DOMContentLoaded", listar);

function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Transportistas/listar", true);
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
  http.send();
}

function renderTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML =
      "<tr><td colspan='4' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td><strong>${escTransportista(item.codigo || "—")}</strong></td>
      <td>${escTransportista(item.nombre)}</td>
      <td>${escTransportista(item.tipo)}</td>
      <td>
        <button class="btn btn-sm btn-info"   onclick="editarTransportista(${Number(item.id_transportista)})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarTransportista(${Number(item.id_transportista)})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// =============== ABRIR MODAL (AGREGAR) ===============
btnAgregar.addEventListener("click", () => {
  form.reset();
  fldId.value = "";
  document.getElementById("modalRegistrarTransportistaLabel").textContent =
    "Registrar Transportista";
  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML =
    '<i data-feather="check-circle" class="me-1"></i> Agregar';
  feather.replace();
});

// =============== SUBMIT (REGISTRAR / ACTUALIZAR) ===============
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const id_transportista = (fldId?.value || "").trim();
  const nombre = (fldNombre?.value || "").trim();
  const tipo = (fldTipo?.value || "").trim();
  const codigo = (fldCodigo?.value || "").trim().toUpperCase();

  if (!nombre || !tipo || !codigo) {
    Swal.fire("Campos requeridos", "Completa nombre, tipo y código", "warning");
    return;
  }

  if (!/^[A-Z]{4}$/.test(codigo)) {
    Swal.fire(
      "Código inválido",
      "El código debe contener exactamente cuatro letras (ej. TSAN)",
      "warning",
    );
    return;
  }

  const fd = new FormData();
  if (id_transportista !== "") fd.append("id_transportista", id_transportista);
  fd.append("nombre", nombre);
  fd.append("tipo", tipo);
  fd.append("codigo", codigo);

  const url =
    base_url +
    (id_transportista === ""
      ? "Transportistas/registrar"
      : "Transportistas/actualizar");

  const http = new XMLHttpRequest();
  http.open("POST", url, true);
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
        form.reset();
        fldId.value = "";
        modal.hide();
        listar();

        // Volver a modo Agregar
        document.getElementById(
          "modalRegistrarTransportistaLabel",
        ).textContent = "Registrar Transportista";
        const btnSubmit = document.getElementById("btnSubmit");
        btnSubmit.innerHTML =
          '<i data-feather="check-circle" class="me-1"></i> Agregar';
        feather.replace();
      }
    }
  };
  http.send(fd);
});

// =============== EDITAR ===============
window.editarTransportista = function (id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Transportistas/editar/" + id, true);
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status !== 200) {
        console.error("Error editar:", this.responseText);
        Swal.fire("Error", "No se pudo consultar el transportista", "error");
        return;
      }
      let data;
      try {
        data = JSON.parse(this.responseText);
      } catch (e) {
        console.error("JSON inválido:", this.responseText);
        Swal.fire("Error", "Respuesta no válida", "error");
        return;
      }
      if (!data || !data.id_transportista) {
        Swal.fire("Error", "Transportista no encontrado", "error");
        return;
      }

      fldId.value = data.id_transportista;
      fldNombre.value = data.nombre || "";
      fldCodigo.value = String(data.codigo || "")
        .trim()
        .toUpperCase();

      let tipo = (data.tipo || "").toString().toLowerCase();
      if (tipo === "terrestre" || tipo === "1") {
        fldTipo.value = "Terrestre";
      } else if (tipo === "ferroviario" || tipo === "2") {
        fldTipo.value = "Ferroviario";
      } else {
        fldTipo.value = ""; // En la vista actual no se ofrece el tipo Marítimo.
      }

      document.getElementById("modalRegistrarTransportistaLabel").textContent =
        "Editar Transportista";
      const btnSubmit = document.getElementById("btnSubmit");
      btnSubmit.innerHTML =
        '<i data-feather="check-circle" class="me-1"></i> Actualizar';
      feather.replace();
      modal.show();
    }
  };
  http.send();
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
    reverseButtons: true,
  }).then((r) => {
    if (!r.isConfirmed) return;

    const http = new XMLHttpRequest();
    http.open("GET", base_url + "Transportistas/eliminar/" + id, true);
    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) {
          console.error("Error eliminar:", this.responseText);
          Swal.fire("Error", "No se pudo eliminar", "error");
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
          res.status === "success" ? "Eliminado" : "Atención",
          res.msg,
          res.status,
        );
        if (res.status === "success") listar();
      }
    };
    http.send();
  });
};

// =============== BÚSQUEDA + SUGERENCIAS ===============
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
    base_url + "Transportistas/buscar?term=" + encodeURIComponent(term),
    true,
  );
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

      // Refrescar tabla con resultados.
      renderTabla(data);

      // Sugerencias por código, nombre y tipo.
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
        btn.textContent = `${t.codigo || "—"} — ${t.nombre} — ${t.tipo}`;
        btn.onclick = () => {
          inputBuscar.value = t.codigo || t.nombre;
          sugerenciasBox.innerHTML = "";
          sugerenciasBox.style.display = "none";
          listarTransportistasFiltrados(t.codigo || t.nombre);
        };
        sugerenciasBox.appendChild(btn);
      });
      sugerenciasBox.style.display = "block";
    }
  };
  http.send();
});

// Cerrar sugerencias clic fuera.
document.addEventListener("click", function (e) {
  if (!sugerenciasBox) return;
  if (!inputBuscar.contains(e.target) && !sugerenciasBox.contains(e.target)) {
    sugerenciasBox.innerHTML = "";
    sugerenciasBox.style.display = "none";
  }
});

// Helper para filtrar por término seleccionado.
function listarTransportistasFiltrados(termino) {
  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Transportistas/buscar?term=" + encodeURIComponent(termino),
    true,
  );
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
  http.send();
}
