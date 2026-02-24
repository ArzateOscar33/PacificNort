const tabla = document.getElementById("tablaTiposMovimiento");
const form = document.getElementById("formTipoMovimiento");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarTipoMovimiento"),
);

const inputBuscar = document.getElementById("buscarMovimiento");
const sugerenciasMovimiento = document.getElementById("sugerenciasMovimiento");
const selectTipo = document.getElementById("tipoMovimiento");
const selectMoneda = document.getElementById("monedaMovimiento");

// Estado de filtros
let filtroTerm = "";
let filtroTipo = ""; // 'gasto' | 'abono' | ''
let filtroMoneda = ""; // 'PESOS' | 'DLLS' | ''

document
  .getElementById("btnAgregarTipoMovimiento")
  .addEventListener("click", () => {
    form.reset();
    document.getElementById("id_movimiento").value = "";
    document.getElementById("modalRegistrarTipoMovimientoLabel").textContent =
      "Registrar Tipo de Movimiento";
    document.getElementById("btnSubmit").innerHTML =
      '<i data-feather="check-circle" class="me-1"></i> Registrar';
    feather.replace();
  });

// ---------- Utilidades ----------
function renderizarTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    // AHORA SON 5 COLUMNAS
    tabla.innerHTML = `<tr><td colspan="4" class="text-center">No hay registros</td></tr>`;
    return;
  }
  data.forEach((mov) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = `
      <td>${mov.nombre}</td>
      <td>${mov.tipo || "-"}</td> 
      <td>${mov.moneda || "-"}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarTipoMovimiento(${mov.id_tipo_movimiento})"><i class="fas fa-edit"></i> Editar</button>
        <button class="btn btn-sm btn-danger" onclick="eliminarTipoMovimiento(${mov.id_tipo_movimiento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

function xhrGET(url, onOK) {
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
      try {
        const data = JSON.parse(http.responseText);
        onOK(data);
      } catch (e) {
        console.error("JSON inválido:", http.responseText);
      }
    }
  };
}

// ---------- Listado base ----------
function listarTiposMovimiento() {
  xhrGET(base_url + "Movimiento_logistico/listar", (data) => {
    renderizarTabla(data);
    // limpiar sugerencias al cargar
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  });
}

// ---------- Filtro combinado ----------
function aplicarFiltros() {
  const params = new URLSearchParams();
  if (filtroTerm) params.append("term", filtroTerm);
  if (filtroTipo) params.append("tipo", filtroTipo);
  if (filtroMoneda) params.append("moneda", filtroMoneda);

  const url =
    base_url +
    "Movimiento_logistico/filtrar" +
    (params.toString() ? "?" + params.toString() : "");
  xhrGET(url, (data) => {
    renderizarTabla(data);

    // Sugerencias (opcional)
    sugerenciasMovimiento.innerHTML = "";
    if (filtroTerm && Array.isArray(data) && data.length > 0) {
      data.slice(0, 8).forEach((mov) => {
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = mov.nombre;
        item.type = "button";
        item.onclick = () => {
          inputBuscar.value = mov.nombre;
          filtroTerm = mov.nombre;
          sugerenciasMovimiento.innerHTML = "";
          sugerenciasMovimiento.style.display = "none";
          aplicarFiltros();
        };
        sugerenciasMovimiento.appendChild(item);
      });
      sugerenciasMovimiento.style.display = "block";
    } else {
      sugerenciasMovimiento.style.display = "none";
    }
  });
}

// ---------- Eventos de filtros ----------
inputBuscar.addEventListener("keyup", function () {
  const termino = this.value.trim();
  filtroTerm = termino;
  if (termino === "") {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  }
  aplicarFiltros();
});

selectTipo.addEventListener("change", function () {
  // En tu vista el placeholder es "Tipo de Movimiento"
  filtroTipo =
    this.value === "Tipo de Movimiento" || this.value === "" ? "" : this.value;
  aplicarFiltros();
});

selectMoneda.addEventListener("change", function () {
  // En tu vista el placeholder es "Moneda" (no "Seleccione")
  filtroMoneda = this.value === "" || this.value === "Moneda" ? "" : this.value;
  aplicarFiltros();
});

// Cerrar sugerencias si se hace clic fuera
document.addEventListener("click", function (e) {
  if (
    !inputBuscar.contains(e.target) &&
    !sugerenciasMovimiento.contains(e.target)
  ) {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  }
});

// ---------- CRUD ----------
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const data = new FormData(this);
  const url = base_url + "Movimiento_logistico/registrar";
  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(data);

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const res = JSON.parse(this.responseText);
      if (res.status === "success") {
        modal.hide();
        form.reset();
        aplicarFiltros(); // respeta filtros activos tras guardar
      }
      Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
    }
  };
});

function editarTipoMovimiento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Movimiento_logistico/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      document.getElementById("id_movimiento").value = data.id_tipo_movimiento;
      form.nombre_movimiento.value = data.nombre;
      form.tipo.value = data.tipo;
      form.moneda.value = data.moneda;
      document.getElementById("modalRegistrarTipoMovimientoLabel").textContent =
        "Actualizar Tipo de Movimiento";
      document.getElementById("btnSubmit").innerHTML =
        '<i data-feather="check-circle" class="me-1"></i>Actualizar';
      feather.replace();
      modal.show();
    }
  };
}

function eliminarTipoMovimiento(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Movimiento_logistico/eliminar/" + id, true);
      http.send();

      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          const res = JSON.parse(this.responseText);
          if (res.status === "success") {
            aplicarFiltros();
          }
          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        }
      };
    }
  });
}

// ---------- Inicialización ----------
document.addEventListener("DOMContentLoaded", function () {
  listarTiposMovimiento();
});
