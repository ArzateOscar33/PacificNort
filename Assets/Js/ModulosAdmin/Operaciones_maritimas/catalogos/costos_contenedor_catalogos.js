// ============================================
//   Catálogo para filtro: Tipos de movimiento
// ============================================
function cargarTiposMovimientoFiltroCostosContenedor() {
  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/catalogoTiposMovimiento?&categoria=Terrestre`;

  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;
    if (this.status !== 200) {
      console.warn("No se pudo cargar tipos de movimiento (filtro)");
      return;
    }

    let tipos = [];
    try {
      tipos = JSON.parse(this.responseText) || [];
    } catch {
      return;
    }

    if (!selTipoCostoContenedor) return;
    selTipoCostoContenedor.innerHTML = `<option value="">Todos</option>`;
    tipos.forEach((t) => {
      if (String(t.estatus) === "1" || t.estatus === 1 || t.estatus === true) {
        const opt = document.createElement("option");
        opt.value = t.id_tipo_movimiento;
        opt.textContent = t.nombre + (t.moneda ? ` (${t.moneda})` : "");
        selTipoCostoContenedor.appendChild(opt);
      }
    });
  };
}

// ============================================
//           Eventos de filtros/UI
// ============================================
perPageSelectCostosContenedor?.addEventListener("change", () => {
  perPageCostosContenedor = parseInt(
    perPageSelectCostosContenedor.value || "10",
    10,
  );
  if (!perPageCostosContenedor || perPageCostosContenedor < 1)
    perPageCostosContenedor = 10;
  listarCostosContenedor(1);
});

inputBuscarCostosContenedor?.addEventListener("keyup", (e) => {
  clearTimeout(debounceIdCostosContenedor);
  debounceIdCostosContenedor = setTimeout(() => listarCostosContenedor(1), 250);

  if (e.key === "Enter") {
    clearTimeout(debounceIdCostosContenedor);
    listarCostosContenedor(1);
  }
});
const selNaturaleza = document.getElementById(
  "filtroNaturalezaCostoContenedor",
);
selNaturaleza?.addEventListener("change", () => listarCostosContenedor(1));
selMonedaCostosContenedor?.addEventListener("change", () =>
  listarCostosContenedor(1),
);
selTipoCostoContenedor?.addEventListener("change", () =>
  listarCostosContenedor(1),
);

// ============================================
//           Acciones (stubs únicos)
// ============================================
/*window.ccEditarCostoContenedor = function(id){
  // TODO: abrir modal, cargar por id, setear campos
  console.log("[CostosContenedor] Editar id:", id);
};

window.ccEliminarCostoContenedor = function(id){
  // TODO: confirm + request para eliminar (baja lógica) y refrescar
  console.log("[CostosContenedor] Eliminar id:", id);
};*/
// =======================================================
//  Catálogos Modal - Costos por Contenedor (EXCLUSIVO)
//  Endpoints:
//   - GET Operaciones_maritimas_costos_Contenedor/catalogoTiposMovimiento
//   - GET Operaciones_maritimas_costos_Contenedor/buscarOperaciones?term=...&limit=8
//   - GET Operaciones_maritimas_costos_Contenedor/buscarContenedoresPorOperacion?operacion_id=7&term=FXE&limit=10
// =======================================================

// ------- Refs Modal -------
const ccModalEl = document.getElementById("modalAgregarCosto");
const ccForm = document.getElementById("formAgregarCostoContenedores");
const ccBtnNuevo = document.getElementById("btnNuevoCostoContenedor");

// Operación
const ccInpOperacionId = document.getElementById("costosOperacionid");
const ccInpOperacionNombre = document.getElementById("costosOperacionNombre");
const ccBoxSugerenciasOperaciones = document.getElementById(
  "costosSugerenciasOperaciones",
);

// Contenedor (por operación)
const ccInpContOpId = document.getElementById("costosContenedorContenedorId"); // contenedor_operacion_id
const ccInpContOpNombre = document.getElementById(
  "costosContenedorContenedorNombre",
); // input texto
const ccBoxSugerenciasContenedores = document.getElementById(
  "sugerenciasCostosContenedor",
);

// Tipo / Moneda
const ccSelTipoCosto = document.getElementById("costosContenedoresTipoCosto");
const ccSelMoneda = document.getElementById("costosContenedoresMoneda");

// Monto / Comentarios (para validar más adelante)
const ccInpMonto = document.getElementById("costosContenedoresMonto");
const ccInpComentarios = document.getElementById(
  "costosContenedoresComentarios",
);

// ------- Estado / Cache -------
let ccXHR_Tipos = null;
let ccXHR_Operaciones = null;
let ccXHR_Contenedores = null;

// { [id_tipo_movimiento]: { id_tipo_movimiento, nombre, moneda ('PESOS'|'DLLS'), estatus } }
const ccTiposCache = Object.create(null);

// =======================================================
//            Utilidades UI simples (estilo brokers)
// =======================================================
function ccOcultarSugerencias(box) {
  if (!box) return;
  box.innerHTML = "";
  box.style.display = "none";
}
function ccMostrarSugerencias(box) {
  if (!box) return;
  // Truco clave: z-index > modal (1055)
  box.style.zIndex = 1061;
  box.style.maxHeight = "260px";
  box.style.overflowY = "auto";
  box.style.display = "block";
}
function ccBloquearMoneda() {
  ccSelMoneda?.setAttribute("readonly", "readonly");
  ccSelMoneda?.setAttribute("disabled", "disabled");
}
function ccDesbloquearMoneda() {
  ccSelMoneda?.removeAttribute("readonly");
  ccSelMoneda?.removeAttribute("disabled");
}
function ccPrepararMoneda() {
  if (!ccSelMoneda) return;
  ccSelMoneda.innerHTML = `
    <option value="">Seleccione</option>
    <option value="PESOS">PESOS</option>
    <option value="DLLS">DLLS</option>
  `;
  ccBloquearMoneda();
}

// =======================================================
//                    APERTURA / RESET
// =======================================================
ccBtnNuevo?.addEventListener("click", () => {
  ccForm?.reset();

  if (ccInpOperacionId) ccInpOperacionId.value = "";
  if (ccInpContOpId) ccInpContOpId.value = "";

  ccOcultarSugerencias(ccBoxSugerenciasOperaciones);
  ccOcultarSugerencias(ccBoxSugerenciasContenedores);

  // Cargar tipos en cada apertura (o podrías cachearlo a nivel global si prefieres)
  ccCargarTiposMovimiento();
  ccPrepararMoneda();

  feather?.replace();
});

// =======================================================
//             Cargar catálogo: Tipos de Costo
// =======================================================
function ccCargarTiposMovimiento() {
  if (ccXHR_Tipos && ccXHR_Tipos.readyState !== 4) ccXHR_Tipos.abort();

  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/catalogoTiposMovimiento?categoria=Terrestre`;
  ccXHR_Tipos = new XMLHttpRequest();
  ccXHR_Tipos.open("GET", url, true);
  ccXHR_Tipos.send();
  ccXHR_Tipos.onreadystatechange = function () {
    if (this.readyState !== 4) return;
    if (this.status !== 200) {
      console.error("[CostosContenedor] Tipos error:", this.responseText);
      return;
    }

    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch {
      console.error("JSON inválido (tipos):", this.responseText);
      return;
    }

    if (ccSelTipoCosto)
      ccSelTipoCosto.innerHTML = `<option value="">Seleccione un tipo</option>`;
    for (const k in ccTiposCache) delete ccTiposCache[k];

    (data || []).forEach((t) => {
      if (!t) return;
      const id = parseInt(t.id_tipo_movimiento ?? t.id, 10);
      const est = String(t.estatus ?? "1");
      const mon = String(t.moneda ?? "").toUpperCase();
      const tipo = String(t.tipo ?? t.naturaleza ?? "").toUpperCase(); // <-- aquí

      if (!id || est === "0") return;

      ccTiposCache[id] = {
        id_tipo_movimiento: id,
        nombre: String(t.nombre ?? ""),
        moneda: mon === "DLLS" ? "DLLS" : "PESOS",
        tipo, // <-- guarda el tipo
        estatus: est,
      };

      if (ccSelTipoCosto) {
        const opt = document.createElement("option");
        opt.value = String(id);
        opt.textContent = `${ccTiposCache[id].nombre}${tipo ? ` (${tipo})` : ""}`; // <-- muestra (GASTO/ABONO)
        opt.dataset.moneda = ccTiposCache[id].moneda;
        opt.dataset.tipo = tipo;
        ccSelTipoCosto.appendChild(opt);
      }
    });
  };
}

// Al cambiar el tipo → auto-moneda (cc* - REEMPLAZAR ESTE LISTENER)
ccSelTipoCosto?.addEventListener("change", () => {
  if (!ccSelMoneda) return;
  const tipoId = parseInt(ccSelTipoCosto.value || "0", 10) || 0;

  // Intenta con ambos caches
  const mon =
    ccTiposCache[tipoId]?.moneda ||
    costosContenedorTiposMap[String(tipoId)] ||
    "";

  if (!mon) return; // 👈 NO resetees a "Seleccione" si no hay dato
  ccSelMoneda.value = mon; // 'PESOS' | 'DLLS'
  ccBloquearMoneda();
});

// =======================================================
//               Autocomplete: OPERACIÓN
// =======================================================
ccInpOperacionNombre?.addEventListener("keyup", function (e) {
  const term = (this.value || "").trim();

  if (term === "") {
    ccOcultarSugerencias(ccBoxSugerenciasOperaciones);
    return;
  }

  if (e.key === "Enter") {
    ccBuscarOperaciones(term);
    return;
  }

  // estilo brokers: sin debounce sofisticado
  ccBuscarOperaciones(term);
});

function ccBuscarOperaciones(term) {
  if (!ccBoxSugerenciasOperaciones) return;

  if (ccXHR_Operaciones && ccXHR_Operaciones.readyState !== 4)
    ccXHR_Operaciones.abort();

  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/buscarOperaciones?term=${encodeURIComponent(term)}&limit=8`;
  ccXHR_Operaciones = new XMLHttpRequest();
  ccXHR_Operaciones.open("GET", url, true);
  ccXHR_Operaciones.send();
  ccXHR_Operaciones.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status !== 200) {
      console.error("Buscar operaciones error:", this.responseText);
      ccOcultarSugerencias(ccBoxSugerenciasOperaciones);
      return;
    }

    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch {
      console.error("JSON inválido (ops):", this.responseText);
      ccOcultarSugerencias(ccBoxSugerenciasOperaciones);
      return;
    }

    ccBoxSugerenciasOperaciones.innerHTML = "";
    if (!Array.isArray(data) || data.length === 0) {
      ccOcultarSugerencias(ccBoxSugerenciasOperaciones);
      return;
    }

    data.forEach((op) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = `${op.numero_operacion || ""}${op.cliente ? " — " + op.cliente : ""}`;
      btn.onclick = () => {
        if (ccInpOperacionId) ccInpOperacionId.value = op.id_operacion;
        if (ccInpOperacionNombre)
          ccInpOperacionNombre.value = op.numero_operacion || "";

        // Reset contenedor si cambió de operación
        if (ccInpContOpId) ccInpContOpId.value = "";
        if (ccInpContOpNombre) ccInpContOpNombre.value = "";

        ccOcultarSugerencias(ccBoxSugerenciasOperaciones);

        // Tip UX: mueve el foco al input de contenedor
        ccInpContOpNombre?.focus();
      };
      ccBoxSugerenciasOperaciones.appendChild(btn);
    });

    ccMostrarSugerencias(ccBoxSugerenciasOperaciones);
  };
}

// =======================================================
//      Autocomplete: CONTENEDOR POR OPERACIÓN
// =======================================================
ccInpContOpNombre?.addEventListener("keyup", function (e) {
  const term = (this.value || "").trim();
  const opId = parseInt(ccInpOperacionId?.value || "0", 10) || 0;

  if (opId <= 0) {
    // sin operación seleccionada, no buscamos
    ccOcultarSugerencias(ccBoxSugerenciasContenedores);
    return;
  }

  if (term === "") {
    // puedes decidir: mostrar todos los contenedores de la operación o cerrar
    ccOcultarSugerencias(ccBoxSugerenciasContenedores);
    return;
  }

  if (e.key === "Enter") {
    ccBuscarContenedoresPorOperacion(opId, term);
    return;
  }

  ccBuscarContenedoresPorOperacion(opId, term);
});

function ccBuscarContenedoresPorOperacion(operacionId, term) {
  if (!ccBoxSugerenciasContenedores) return;

  // LOGS para comprobar qué se está enviando
  // console.log("[CostosContenedor] opId:", operacionId, "term:", term);

  if (!operacionId || operacionId <= 0) {
    console.warn(
      "[CostosContenedor] operacion_id vacío. Selecciona una operación primero.",
    );
    ccOcultarSugerencias(ccBoxSugerenciasContenedores);
    return;
  }

  const url = `${base_url}Operaciones_maritimas_costos_Contenedor/buscarContenedoresPorOperacion?operacion_id=${encodeURIComponent(operacionId)}&term=${encodeURIComponent(term)}&limit=10`;
  //console.log("[CostosContenedor] GET:", url);

  if (ccXHR_Contenedores && ccXHR_Contenedores.readyState !== 4)
    ccXHR_Contenedores.abort();
  ccXHR_Contenedores = new XMLHttpRequest();
  ccXHR_Contenedores.open("GET", url, true);
  ccXHR_Contenedores.send();
  ccXHR_Contenedores.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    /*.log(
      "[CostosContenedor] status:",
      this.status,
      "resp:",
      this.responseText,
    ); */ // 👈

    if (this.status !== 200) {
      console.error("Buscar contenedores error:", this.responseText);
      ccOcultarSugerencias(ccBoxSugerenciasContenedores);
      return;
    }

    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch {
      console.error("JSON inválido (conts):", this.responseText);
      ccOcultarSugerencias(ccBoxSugerenciasContenedores);
      return;
    }

    ccBoxSugerenciasContenedores.innerHTML = "";
    if (!Array.isArray(data) || data.length === 0) {
      ccOcultarSugerencias(ccBoxSugerenciasContenedores);
      return;
    }

    data.forEach((c) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = `${c.numero_ferro || ""}`;
      btn.onclick = () => {
        if (ccInpContOpId) ccInpContOpId.value = c.contenedor_operacion_id;
        if (ccInpContOpNombre) ccInpContOpNombre.value = c.numero_ferro || "";
        ccOcultarSugerencias(ccBoxSugerenciasContenedores);
      };
      ccBoxSugerenciasContenedores.appendChild(btn);
    });

    ccMostrarSugerencias(ccBoxSugerenciasContenedores);
  };
}

// =======================================================
//            Cerrar sugerencias clic fuera
// =======================================================
document.addEventListener("click", function (e) {
  if (
    ccBoxSugerenciasOperaciones &&
    !ccInpOperacionNombre?.contains(e.target) &&
    !ccBoxSugerenciasOperaciones.contains(e.target)
  ) {
    ccOcultarSugerencias(ccBoxSugerenciasOperaciones);
  }
  if (
    ccBoxSugerenciasContenedores &&
    !ccInpContOpNombre?.contains(e.target) &&
    !ccBoxSugerenciasContenedores.contains(e.target)
  ) {
    ccOcultarSugerencias(ccBoxSugerenciasContenedores);
  }
});
