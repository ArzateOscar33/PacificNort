// =========================
// Refs principales
// =========================
const tabla = document.getElementById("tablaTiposMovimiento");
const form = document.getElementById("formTipoMovimiento");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarTipoMovimiento"),
);

const inputBuscar = document.getElementById("buscarMovimiento");
const sugerenciasMovimiento = document.getElementById("sugerenciasMovimiento");
const selectTipo = document.getElementById("tipoMovimiento");
const selectMoneda = document.getElementById("monedaMovimiento");

// ✅ select categoría en el MODAL de movimiento (este sí está bien en tu vista)
const selectCategoria = document.getElementById("categoriaMovimiento");

// ⚠️ En tu vista el filtro de categoría tiene id="categoria_id" y name="categoriaMovimiento"
// Lo soportamos así para que funcione ya. (Recomendado: renombrar a categoriaMovimientoFiltro + name=categoria_id)
const selectCategoriaFiltro =
  document.getElementById("categoriaMovimientoFiltro") ||
  document.getElementById("categoria_id");

// =========================
// Modal + form de Categoría
// =========================
const formCategoria = document.getElementById("formCategoria");
const modalCategoriaEl = document.getElementById("modalRegistrarCategoria");
const modalCategoria = modalCategoriaEl
  ? new bootstrap.Modal(modalCategoriaEl)
  : null;

// =========================
// Estado de filtros
// =========================
let filtroTerm = "";
let filtroTipo = ""; // 'gasto' | 'abono' | ''
let filtroMoneda = ""; // 'PESOS' | 'DLLS' | ''
let filtroCategoria = ""; // id_categoria | ''

document
  .getElementById("btnAgregarTipoMovimiento")
  .addEventListener("click", () => {
    form.reset();
    document.getElementById("id_movimiento").value = "";

    if (selectCategoria) selectCategoria.value = "";

    document.getElementById("modalRegistrarTipoMovimientoLabel").textContent =
      "Registrar Tipo de Costo";
    document.getElementById("btnSubmit").innerHTML =
      '<i data-feather="check-circle" class="me-1"></i> Registrar';
    feather.replace();
  });

// =========================
// Utilidades XHR / JSON
// =========================
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

function xhrPOST(url, formData, onOK) {
  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(formData);
  http.onreadystatechange = function () {
    if (http.readyState === 4) {
      if (http.status === 200) {
        try {
          const data = JSON.parse(http.responseText);
          onOK(data);
        } catch (e) {
          console.error("JSON inválido:", http.responseText);
          Swal.fire("Aviso", "RESPUESTA INVÁLIDA DEL SERVIDOR", "error");
        }
      } else {
        Swal.fire("Aviso", "ERROR HTTP: " + http.status, "error");
      }
    }
  };
}

// =========================
// Render tabla
// =========================
function renderizarTabla(data) {
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = `<tr><td colspan="5" class="text-center">No hay registros</td></tr>`;
    return;
  }

  data.forEach((mov) => {
    const tr = document.createElement("tr");
    tr.classList.add("text-center");

    tr.innerHTML = `
      <td>${mov.nombre || "-"}</td>
      <td>${mov.categoria || "-"}</td>
      <td>${mov.tipo || "-"}</td>
      <td>${mov.moneda || "-"}</td>
      <td>
        <button class="btn btn-sm btn-info" onclick="editarTipoMovimiento(${mov.id_tipo_movimiento})">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" onclick="eliminarTipoMovimiento(${mov.id_tipo_movimiento})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// =========================
// Listado / filtros
// =========================
function listarTiposMovimiento() {
  xhrGET(base_url + "Movimiento_logistico/listar", (data) => {
    renderizarTabla(data);
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  });
}

function aplicarFiltros() {
  const params = new URLSearchParams();
  if (filtroTerm) params.append("term", filtroTerm);
  if (filtroTipo) params.append("tipo", filtroTipo);
  if (filtroMoneda) params.append("moneda", filtroMoneda);
  if (filtroCategoria) params.append("categoria_id", filtroCategoria);

  const url =
    base_url +
    "Movimiento_logistico/filtrar" +
    (params.toString() ? "?" + params.toString() : "");

  xhrGET(url, (data) => {
    renderizarTabla(data);

    // sugerencias por nombre
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

// Eventos filtros
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
  // En tu vista el placeholder real es: "Tipo de Costo"
  filtroTipo = this.value === "" ? "" : this.value;
  aplicarFiltros();
});

selectMoneda.addEventListener("change", function () {
  filtroMoneda = this.value === "" ? "" : this.value;
  aplicarFiltros();
});

// Filtro por categoría (si existe)
if (selectCategoriaFiltro) {
  selectCategoriaFiltro.addEventListener("change", function () {
    filtroCategoria =
      this.value && String(this.value).trim() !== "" ? String(this.value) : "";
    aplicarFiltros();
  });
}

// Cerrar sugerencias
document.addEventListener("click", function (e) {
  if (
    !inputBuscar.contains(e.target) &&
    !sugerenciasMovimiento.contains(e.target)
  ) {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  }
});

// =========================
// CRUD movimientos
// =========================
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const data = new FormData(this);

  // ✅ En tu modal SÍ tiene name="categoria_id", así que ya se manda.
  // Pero por seguridad:
  if (selectCategoria && !data.has("categoria_id")) {
    data.append("categoria_id", selectCategoria.value || "");
  }

  xhrPOST(base_url + "Movimiento_logistico/registrar", data, (res) => {
    if (res.status === "success") {
      modal.hide();
      form.reset();
      if (selectCategoria) selectCategoria.value = "";
      aplicarFiltros();
    }
    Swal.fire("Aviso", String(res.msg || "").toUpperCase(), res.status);
  });
});

function editarTipoMovimiento(id) {
  xhrGET(base_url + "Movimiento_logistico/editar/" + id, (data) => {
    if (data && data.status && !data.id_tipo_movimiento) {
      Swal.fire(
        "Aviso",
        String(data.msg || "No encontrado").toUpperCase(),
        data.status,
      );
      return;
    }

    document.getElementById("id_movimiento").value = data.id_tipo_movimiento;
    form.nombre_movimiento.value = data.nombre || "";
    form.tipo.value = data.tipo || "";
    form.moneda.value = data.moneda || "";

    if (selectCategoria) {
      selectCategoria.value = data.categoria_id
        ? String(data.categoria_id)
        : "";
    }

    document.getElementById("modalRegistrarTipoMovimientoLabel").textContent =
      "Actualizar Tipo de Costo";
    document.getElementById("btnSubmit").innerHTML =
      '<i data-feather="check-circle" class="me-1"></i>Actualizar';
    feather.replace();
    modal.show();
  });
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
    if (!result.isConfirmed) return;

    xhrGET(base_url + "Movimiento_logistico/eliminar/" + id, (res) => {
      if (res.status === "success") aplicarFiltros();
      Swal.fire("Aviso", String(res.msg || "").toUpperCase(), res.status);
    });
  });
}

// =========================
// ✅ NUEVO: CRUD categorías (registrar + refrescar selects)
// =========================
function refrescarCategoriasSelects(keepSelectedId) {
  xhrGET(base_url + "Movimiento_logistico/listarCategorias", (rows) => {
    if (!Array.isArray(rows)) rows = [];

    // helper para pintar un select
    function fillSelect(sel, placeholderText) {
      if (!sel) return;
      const current =
        keepSelectedId != null
          ? String(keepSelectedId)
          : String(sel.value || "");
      sel.innerHTML = "";
      const opt0 = document.createElement("option");
      opt0.value = "";
      opt0.textContent = placeholderText || "Categoría";
      sel.appendChild(opt0);

      rows.forEach((c) => {
        const opt = document.createElement("option");
        opt.value = String(c.id_categoria);
        opt.textContent = c.nombre;
        sel.appendChild(opt);
      });

      // restaurar selección si aplica
      if (current) sel.value = current;
    }

    // modal movimiento
    fillSelect(selectCategoria, "Seleccione");

    // filtro (tu vista usa id=categoria_id como filtro)
    fillSelect(selectCategoriaFiltro, "Categoría");
  });
}

if (formCategoria) {
  formCategoria.addEventListener("submit", function (e) {
    e.preventDefault();

    const fd = new FormData(this);
    const nombre = String(fd.get("nombre_categoria") || "").trim();

    if (nombre === "") {
      Swal.fire("Aviso", "EL NOMBRE ES OBLIGATORIO", "warning");
      return;
    }

    xhrPOST(base_url + "Movimiento_logistico/registrarCategoria", fd, (res) => {
      if (res.status === "success") {
        // cerrar modal + reset
        if (modalCategoria) modalCategoria.hide();
        formCategoria.reset();

        // refrescar selects y (opcional) seleccionar la nueva categoría:
        // No tenemos el id recién creado en la respuesta. Si lo quieres, lo agregamos en el controlador.
        refrescarCategoriasSelects(null);
      }

      Swal.fire("Aviso", String(res.msg || "").toUpperCase(), res.status);
    });
  });
}

// =========================
// Init
// =========================
document.addEventListener("DOMContentLoaded", function () {
  listarTiposMovimiento();

  // (opcional) si por alguna razón quieres cargar categorías por ajax aunque ya vienen en PHP:
  // refrescarCategoriasSelects(null);
});
