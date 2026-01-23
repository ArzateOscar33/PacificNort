// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_rutas_catalogo.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Endpoints =====
const EP_SUGERIR_FACTURAS = "Operaciones_por_partida_rutas/sugerirFacturasRutas";
const EP_LISTAR_PRODUCTOS = "Operaciones_por_partida_rutas/listarProductosRutas";
const EP_SUGERIR_FISICOS  = "Operaciones_por_partida_rutas/sugerirFerroCajaRutas"; 
const EP_SUGERIR_CIUDADES = "Operaciones_por_partida_rutas/sugerirCiudadesRutas";



  // ===== Refs DOM   =====
  const btnRefrescar = document.getElementById("partidas_transito_btnRefrescar");

  const hidFacturaId = document.getElementById("partidas_transito_facturaId");
  const inpFactura   = document.getElementById("partidas_transito_buscarFactura");
  const boxSug       = document.getElementById("partidas_transito_sugerenciasFacturas");

  const inpProducto  = document.getElementById("partidas_transito_buscarProducto");
  const tbody        = document.getElementById("partidas_transito_tbodyProductos");
  const emptyBox     = document.getElementById("partidas_transito_empty");


    // ===== Modal Envío   =====
  const modalEnvioEl   = document.getElementById("modalPartidasTransitoEnvio");

  const hidProductoId  = document.getElementById("partidas_transito_idProducto");
  const hidFacturaId2  = document.getElementById("partidas_transito_factura"); // hidden dentro del modal

  const lblFactura     = document.getElementById("partidas_transito_lblFactura");
  const lblProducto    = document.getElementById("partidas_transito_lblProducto");
  const lblRestantes   = document.getElementById("partidas_transito_lblRestantes");

  const selDestino     = document.getElementById("partidas_transito_destino");
  const inpFechaEnvio  = document.getElementById("partidas_transito_fechaEnvio");
  const inpCajaFerro   = document.getElementById("partidas_transito_cajaFerro");
  const inpCajasEnv    = document.getElementById("partidas_transito_cajasEnviadas");
  const txtNotasEnv    = document.getElementById("partidas_transito_notasEnvio");

  // ===== Estado =====
  let debounceSugId = null;
  let debounceProdId = null;

  let xhrSug = null;
  let xhrList = null;

  // ========== Utils ==========
  function esc(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function setEmptyVisible(isVisible) {
    if (!emptyBox) return;
    emptyBox.classList.toggle("d-none", !isVisible);
  }

  function clearTable() {
    if (tbody) tbody.innerHTML = "";
  }

  function abortXHR(x) {
    try { if (x && x.readyState !== 4) x.abort(); } catch (_) {}
  }

  function buildUrl(endpoint, paramsObj) {
    const qs = new URLSearchParams();
    Object.keys(paramsObj || {}).forEach((k) => {
      const v = paramsObj[k];
      if (v === undefined || v === null || String(v).trim() === "") return;
      qs.append(k, String(v));
    });
    return base_url + endpoint + (qs.toString() ? ("?" + qs.toString()) : "");
  }

  // ========== Sugerencias Facturas ==========
  function limpiarSugerencias() {
    if (!boxSug) return;
    boxSug.innerHTML = "";
    boxSug.style.display = "none";
  }

  function renderSugerencias(rows) {
    if (!boxSug) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      limpiarSugerencias();
      return;
    }

    const html = rows.map((r) => {
      const id = r.id_factura ?? "";
      const num = r.numero_factura ?? "";
      const prov = r.proveedor ?? "";
      const bod = r.bodega_nombre ?? r.bodega ?? "";

      return `
        <button type="button"
          class="list-group-item list-group-item-action py-2"
          data-id="${esc(id)}"
          data-num="${esc(num)}"
          data-prov="${esc(prov)}"
          data-bod="${esc(bod)}">
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-semibold">${esc(num)}</div>
            <small class="text-muted">${esc(bod)}</small>
          </div>
          <div class="small text-muted text-truncate">${esc(prov)}</div>
        </button>
      `;
    }).join("");

    boxSug.innerHTML = html;
    boxSug.style.display = "block";
  }

  function fetchSugerencias(term) {
    abortXHR(xhrSug);

    xhrSug = new XMLHttpRequest();
    const url = buildUrl(EP_SUGERIR_FACTURAS, { term: term, limit: 10 });

    xhrSug.open("GET", url, true);
    xhrSug.onreadystatechange = function () {
      if (xhrSug.readyState !== 4) return;

      if (xhrSug.status < 200 || xhrSug.status >= 300) {
        limpiarSugerencias();
        return;
      }

      let json = null;
      try { json = JSON.parse(xhrSug.responseText || "{}"); } catch (_) {}

      if (!json || !json.ok) {
        limpiarSugerencias();
        return;
      }

      renderSugerencias(json.data || []);
    };
    xhrSug.send();
  }

  function onFacturaInput() {
    // Al escribir, limpiamos la selección actual (porque ya no es confiable)
    if (hidFacturaId) hidFacturaId.value = "";
    clearTable();
    setEmptyVisible(true);

    const term = (inpFactura?.value || "").trim();

    if (term.length < 2) {
      limpiarSugerencias();
      return;
    }

    clearTimeout(debounceSugId);
    debounceSugId = setTimeout(() => fetchSugerencias(term), 250);
  }

  function onSelectSugerencia(btn) {
    if (!btn) return;

    const id = btn.getAttribute("data-id") || "";
    const num = btn.getAttribute("data-num") || "";
    // prov/bod disponibles por si después los quieres mostrar
    // const prov = btn.getAttribute("data-prov") || "";
    // const bod  = btn.getAttribute("data-bod")  || "";

    if (hidFacturaId) hidFacturaId.value = id;
    if (inpFactura) inpFactura.value = num;

    limpiarSugerencias();

    // Al seleccionar factura, resetea filtro de producto
    if (inpProducto) inpProducto.value = "";

    // Cargar tabla
    listarProductos();
  }

  // ========== Listar Productos (Tabla) ==========
  function renderProductos(rows) {
    if (!tbody) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      clearTable();
      setEmptyVisible(false); // ya hay factura seleccionada, pero sin productos
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-muted py-4">
            No hay productos para esta factura.
          </td>
        </tr>
      `;
      return;
    }

    const html = rows.map((r) => {
      const idProd = r.id_producto ?? "";
      const desc   = r.descripcion ?? "";
      const upc    = r.upc ?? "";
      const marca  = r.marca ?? "";

      const total  = Number(r.cajas_total ?? r.cajas ?? 0) || 0;
      const enviad = Number(r.cajas_enviadas ?? 0) || 0;
      const rest   = Number(r.cajas_restantes ?? 0) || 0;

      // Por ahora, “Destinos / Envíos” y “Caja/Ferro” en placeholder
      // (los llenaremos después con listarEnviosProductoRutas o resumen)
      const destinosHtml = `
        <span class="badge bg-light text-dark border">Enviadas: ${esc(enviad)}</span>
        <span class="badge bg-light text-dark border">Pendientes: ${esc(rest)}</span>
      `;

      return `
        <tr class="text-center align-middle">
          <td class="text-start">
            <div class="fw-semibold">${esc(desc)}</div>
          </td>

          <td><span class="small">${esc(upc)}</span></td>
          <td><span class="small">${esc(marca)}</span></td>

          <td><span class="fw-semibold">${esc(total)}</span></td>

          <td class="text-start">${destinosHtml}</td>

          <td>
            <span class="text-muted small">—</span>
          </td>

          <td>
            <span class="badge ${rest > 0 ? "bg-success text-white" : "bg-secondary text-white"}">
              ${esc(rest)}
            </span>
          </td>

          <td>
            <button type="button"
              class="btn btn-outline-secondary btn-sm btnRegistrarEnvio"
              data-id="${esc(idProd)}"
              data-desc="${esc(desc)}"
              data-rest="${esc(rest)}"
              title="Registrar envío">
              <i data-feather="send"></i>
            </button>
          </td>
        </tr>
      `;
    }).join("");

    tbody.innerHTML = html;
    setEmptyVisible(false);

    // Re-render feather icons
    try {
      if (window.feather) window.feather.replace();
    } catch (_) {}
  }

  function listarProductos() {
    const facturaId = (hidFacturaId?.value || "").trim();
    const term = (inpProducto?.value || "").trim();

    if (!facturaId) {
      clearTable();
      setEmptyVisible(true);
      return;
    }

    abortXHR(xhrList);

    xhrList = new XMLHttpRequest();
    const url = buildUrl(EP_LISTAR_PRODUCTOS, { factura_id: facturaId, term: term });

    xhrList.open("GET", url, true);
    xhrList.onreadystatechange = function () {
      if (xhrList.readyState !== 4) return;

      if (xhrList.status < 200 || xhrList.status >= 300) {
        clearTable();
        setEmptyVisible(false);
        if (tbody) {
          tbody.innerHTML = `
            <tr>
              <td colspan="8" class="text-center text-danger py-4">
                Error al listar productos.
              </td>
            </tr>
          `;
        }
        return;
      }

      let json = null;
      try { json = JSON.parse(xhrList.responseText || "{}"); } catch (_) {}

      if (!json || !json.ok) {
        clearTable();
        setEmptyVisible(false);
        if (tbody) {
          tbody.innerHTML = `
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                ${(json && json.msg) ? esc(json.msg) : "No se pudieron cargar los productos."}
              </td>
            </tr>
          `;
        }
        return;
      }

      renderProductos(json.data || []);
    };

    xhrList.send();
  }

  function onProductoInput() {
    // Solo filtra si ya hay factura seleccionada
    const facturaId = (hidFacturaId?.value || "").trim();
    if (!facturaId) return;

    clearTimeout(debounceProdId);
    debounceProdId = setTimeout(() => listarProductos(), 250);
  }

  // ========== Eventos globales (cerrar sugerencias al click fuera) ==========
 function onDocClick(e) {
  const t = e.target;
  if (!t) return;

  // ============================
  // 1) FACTURAS (catálogo)
  // ============================
  if (boxSug && boxSug.style.display !== "none") {
    const clickEnFacturaInp = inpFactura && (t === inpFactura || inpFactura.contains(t));
    const clickEnFacturaBox = boxSug && (t === boxSug || boxSug.contains(t));

    if (!clickEnFacturaInp && !clickEnFacturaBox) {
      limpiarSugerencias();
    }
  }

  // ============================
  // 2) MODAL: FÍSICOS + CIUDADES
  // ============================
  if (modalEnvioEl && (modalEnvioEl.contains(t) || document.body.contains(t))) {
    // --- FÍSICOS ---
    const clickEnFisico = t.closest(".pt_fisico_txt") || t.closest(".pt_fisico_sug");
    if (!clickEnFisico) {
      modalEnvioEl.querySelectorAll(".pt_fisico_sug").forEach((b) => {
        b.innerHTML = "";
        b.style.display = "none";
      });
    }

    // --- CIUDADES ---
    const clickEnCiudad = t.closest(".pt_destino_txt") || t.closest(".pt_destino_sug");
    if (!clickEnCiudad) {
      modalEnvioEl.querySelectorAll(".pt_destino_sug").forEach((b) => {
        b.innerHTML = "";
        b.style.display = "none";
      });
    }
  }
}

// ===================== SUGERENCIAS CIUDADES (DESTINOS) POR RENGLÓN =====================
let debounceCiudadId = null;
let xhrCiudad = null;

function hideCiudadSug(rowEl) {
  const box = rowEl?.querySelector(".pt_destino_sug");
  if (!box) return;
  box.innerHTML = "";
  box.style.display = "none";
}

function renderCiudadSug(rowEl, rows) {
  const box = rowEl?.querySelector(".pt_destino_sug");
  if (!box) return;

  if (!Array.isArray(rows) || rows.length === 0) {
    hideCiudadSug(rowEl);
    return;
  }

  const html = rows.map((r) => {
    const id = r.id ?? r.id_ciudad ?? "";
    const texto = r.texto ?? r.nombre ?? r.nombre_ciudad ?? "";

    return `
      <button type="button"
        class="list-group-item list-group-item-action py-2 pt_ciudad_item"
        data-id="${esc(id)}"
        data-texto="${esc(texto)}">
        <div class="fw-semibold">${esc(texto)}</div>
      </button>
    `;
  }).join("");

  box.innerHTML = html;
  box.style.display = "block";
}

function fetchCiudadSug(rowEl, term) {
  abortXHR(xhrCiudad);

  xhrCiudad = new XMLHttpRequest();
  const url = buildUrl(EP_SUGERIR_CIUDADES, { term: term, limit: 10 });

  xhrCiudad.open("GET", url, true);
  xhrCiudad.onreadystatechange = function () {
    if (xhrCiudad.readyState !== 4) return;

    if (xhrCiudad.status < 200 || xhrCiudad.status >= 300) {
      hideCiudadSug(rowEl);
      return;
    }

    let json = null;
    try { json = JSON.parse(xhrCiudad.responseText || "{}"); } catch (_) {}

    if (!json || !json.ok) {
      hideCiudadSug(rowEl);
      return;
    }

    renderCiudadSug(rowEl, json.data || []);
  };

  xhrCiudad.send();
}

function onCiudadInput(e) {
  const input = e.target;
  if (!input || !input.classList.contains("pt_destino_txt")) return;

  const rowEl = input.closest(".partidas_transito_row");
  if (!rowEl) return;

  // al teclear, invalida selección previa
  const hid = rowEl.querySelector(".pt_destino_id");
  if (hid) hid.value = "";

  const term = (input.value || "").trim();
  if (term.length < 2) {
    hideCiudadSug(rowEl);
    return;
  }

  clearTimeout(debounceCiudadId);
  debounceCiudadId = setTimeout(() => fetchCiudadSug(rowEl, term), 250);
}

function onCiudadPick(e) {
  const btn = e.target?.closest(".pt_ciudad_item");
  if (!btn) return;

  const rowEl = btn.closest(".partidas_transito_row");
  if (!rowEl) return;

  const id = btn.getAttribute("data-id") || "";
  const texto = btn.getAttribute("data-texto") || "";

  const hid = rowEl.querySelector(".pt_destino_id");
  const inp = rowEl.querySelector(".pt_destino_txt");

  if (hid) hid.value = id;
  if (inp) inp.value = texto;

  hideCiudadSug(rowEl);
}


  // ========== Init ==========
  function init() {
    if (!inpFactura || !hidFacturaId || !tbody) return;

    // Estado inicial
    clearTable();
    setEmptyVisible(true);
    limpiarSugerencias();

    // Input factura => sugerencias
    inpFactura.addEventListener("input", onFacturaInput);

    // Enter en factura: evita submit y no selecciona nada
    inpFactura.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        return false;
      }
    });

    // Click en sugerencia
    if (boxSug) {
      boxSug.addEventListener("click", function (e) {
        const btn = e.target?.closest(".list-group-item");
        if (!btn) return;
        onSelectSugerencia(btn);
      });
    }

    // Input producto => filtrar lista
    if (inpProducto) inpProducto.addEventListener("input", onProductoInput);

    // Refrescar
    if (btnRefrescar) {
      btnRefrescar.addEventListener("click", function () {
        // Si hay factura: relistar, si no: solo limpiar
        const facturaId = (hidFacturaId?.value || "").trim();
        limpiarSugerencias();
        if (!facturaId) {
          clearTable();
          setEmptyVisible(true);
          return;
        }
        listarProductos();
      });
    }

    // Click fuera => cerrar sugerencias
    document.addEventListener("click", onDocClick);

    // Feather icons
    try {
      if (window.feather) window.feather.replace();
    } catch (_) {}
  


    // Click en botón Acción => abrir modal
    tbody.addEventListener("click", function (e) {
      const btn = e.target?.closest(".btnRegistrarEnvio");
      if (!btn) return;

      const productoId = btn.getAttribute("data-id");
      const desc = btn.getAttribute("data-desc") || "";
      const rest = btn.getAttribute("data-rest") || "0";

      // Si está disabled, no hacemos nada
      if (btn.disabled) return;

      // Reset modal antes de abrir (para no “arrastrar” valores)
      resetModalEnvio();

      abrirModalEnvio({
        productoId: productoId,
        productoDesc: desc,
        restantes: rest
      });
    });

    // Delegación dentro del modal: inputs y clicks de sugerencias de físicos
// Delegación dentro del modal: inputs y clicks de sugerencias
if (modalEnvioEl) {

  // CIUDADES
  modalEnvioEl.addEventListener("input", onCiudadInput);

  modalEnvioEl.addEventListener("click", function (e) {
    if (e.target?.closest(".pt_ciudad_item")) {
      onCiudadPick(e);
      return;
    }
  });

  // FÍSICOS (lo que ya tienes)
  modalEnvioEl.addEventListener("input", onFisicoInput);

  modalEnvioEl.addEventListener("click", function (e) {
    if (e.target?.closest(".pt_fisico_item")) {
      onFisicoPick(e);
      return;
    }
  });

  // cerrar sugerencias al presionar ESC dentro del modal
  modalEnvioEl.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;

    modalEnvioEl.querySelectorAll(".pt_fisico_sug, .pt_destino_sug").forEach((b) => {
      b.innerHTML = "";
      b.style.display = "none";
    });
  });
}


    // Limpieza cada vez que se cierre el modal (por si el usuario cancela)
    if (modalEnvioEl) {
      modalEnvioEl.addEventListener("hidden.bs.modal", function () {
        resetModalEnvio();
      });
    }
}

    function resetModalEnvio() {
    if (hidProductoId) hidProductoId.value = "";
    if (hidFacturaId2) hidFacturaId2.value = "";

    if (lblFactura)   lblFactura.textContent = "—";
    if (lblProducto)  lblProducto.textContent = "—";
    if (lblRestantes) lblRestantes.textContent = "0";

    if (selDestino) selDestino.value = "";
    if (inpFechaEnvio) inpFechaEnvio.value = "";
    if (inpCajaFerro) inpCajaFerro.value = "";
    if (inpCajasEnv) {
      inpCajasEnv.value = "";
      inpCajasEnv.removeAttribute("max");
    }
    if (txtNotasEnv) txtNotasEnv.value = "";
  }

  function abrirModalEnvio({ productoId, productoDesc, restantes }) {
    if (!modalEnvioEl) return;

    const facturaId = (hidFacturaId?.value || "").trim();
    const facturaNum = (inpFactura?.value || "").trim();

    // Guardar IDs en hidden del modal
    if (hidProductoId) hidProductoId.value = String(productoId || "");
    if (hidFacturaId2) hidFacturaId2.value = String(facturaId || "");

    // Labels
    if (lblFactura) lblFactura.textContent = facturaNum || "—";
    if (lblProducto) lblProducto.textContent = productoDesc || "—";
    if (lblRestantes) lblRestantes.textContent = String(restantes ?? 0);

    // Max permitido en input
    const restNum = Number(restantes ?? 0) || 0;
    if (inpCajasEnv) {
      inpCajasEnv.value = "";
      inpCajasEnv.min = "1";
      inpCajasEnv.step = "1";
      inpCajasEnv.setAttribute("max", String(restNum));
    }

    // Mostrar modal (Bootstrap 5)
    try {
      const modal = bootstrap.Modal.getOrCreateInstance(modalEnvioEl, {
        backdrop: "static",
        keyboard: true
      });
      modal.show();
    } catch (e) {
      // Fallback por si bootstrap no está disponible por alguna razón
      console.error("No se pudo abrir el modal (bootstrap).", e);
    }
  }

  init();


// ===================== SUGERENCIAS FÍSICOS (Caja/Ferro) POR RENGLÓN =====================
let debounceFisicoId = null; // debounce global (suficiente)
let xhrFisico = null;

function hideFisicoSug(rowEl) {
  const box = rowEl?.querySelector(".pt_fisico_sug");
  if (!box) return;
  box.innerHTML = "";
  box.style.display = "none";
}

function renderFisicoSug(rowEl, rows) {
  const box = rowEl?.querySelector(".pt_fisico_sug");
  if (!box) return;

  if (!Array.isArray(rows) || rows.length === 0) {
    hideFisicoSug(rowEl);
    return;
  }

  const html = rows.map((r) => {
    const id = r.id ?? "";
    const tipo = r.tipo ?? "FERRO";
    const texto = r.texto ?? "";

    return `
      <button type="button"
        class="list-group-item list-group-item-action py-2 pt_fisico_item"
        data-id="${esc(id)}"
        data-tipo="${esc(tipo)}"
        data-texto="${esc(texto)}">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold">${esc(texto)}</div>
          
        </div>
      </button>
    `;
  }).join("");

  box.innerHTML = html;
  box.style.display = "block";
}

function fetchFisicoSug(rowEl, term) {
  abortXHR(xhrFisico);

  xhrFisico = new XMLHttpRequest();
  const url = buildUrl(EP_SUGERIR_FISICOS, { term: term, limit: 10 });

  xhrFisico.open("GET", url, true);
  xhrFisico.onreadystatechange = function () {
    if (xhrFisico.readyState !== 4) return;

    if (xhrFisico.status < 200 || xhrFisico.status >= 300) {
      hideFisicoSug(rowEl);
      return;
    }

    let json = null;
    try { json = JSON.parse(xhrFisico.responseText || "{}"); } catch (_) {}

    if (!json || !json.ok) {
      hideFisicoSug(rowEl);
      return;
    }

    renderFisicoSug(rowEl, json.data || []);
  };

  xhrFisico.send();
}

function onFisicoInput(e) {
  const input = e.target;
  if (!input || !input.classList.contains("pt_fisico_txt")) return;

  const rowEl = input.closest(".partidas_transito_row");
  if (!rowEl) return;

  // al teclear, invalida selección previa
  const hid = rowEl.querySelector(".pt_fisico_id");
  if (hid) hid.value = "";

  const term = (input.value || "").trim();
  if (term.length < 2) {
    hideFisicoSug(rowEl);
    return;
  }

  clearTimeout(debounceFisicoId);
  debounceFisicoId = setTimeout(() => fetchFisicoSug(rowEl, term), 250);
}

function onFisicoPick(e) {
  const btn = e.target?.closest(".pt_fisico_item");
  if (!btn) return;

  const rowEl = btn.closest(".partidas_transito_row");
  if (!rowEl) return;

  const id = btn.getAttribute("data-id") || "";
  const texto = btn.getAttribute("data-texto") || "";

  const hid = rowEl.querySelector(".pt_fisico_id");
  const inp = rowEl.querySelector(".pt_fisico_txt");

  if (hid) hid.value = id;
  if (inp) inp.value = texto;

  hideFisicoSug(rowEl);
}

  

})();


