// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_catalogo.js
(function () {
  "use strict";

  // ==========================
  // CONFIG / BASE URL
  // ==========================
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const ENDPOINT_LISTAR_PRODUCTOS = "Operaciones_por_partida/listarProductos";

  // ==========================
  // REFS MODAL PRODUCTOS
  // ==========================
  const modalEl = document.getElementById("modalProductosFactura");

  const inputFacturaHidden = document.getElementById("pf_invoice_id");
  const pfTbody = document.getElementById("pf_tbody");
  const pfEmpty = document.getElementById("pf_empty");
  const pfMeta  = document.getElementById("pf_meta");
  const pfBuscar = document.getElementById("pf_buscar");

  const pfBadgeCount   = document.getElementById("pf_badgeCount");
  const pfTotalCajas   = document.getElementById("pf_totalCajas");
  const pfTotalPiezas  = document.getElementById("pf_totalPiezas");
  const pfTotalPallets = document.getElementById("pf_totalPalletsRcv");

  // Labels header del modal
  const lblId     = document.getElementById("pf_lblFactura");
  const lblVendor = document.getElementById("pf_lblProveedor");
  const lblXdock  = document.getElementById("pf_lblXdock");
  const lblRec    = document.getElementById("pf_lblRecibido");
  const lblRev    = document.getElementById("pf_lblRevision");
  const lblPal    = document.getElementById("pf_lblPalletsRcv");


   // ===== Refs del modal =====
  const tbody      = document.getElementById("pf_tbody");
  const empty      = document.getElementById("pf_empty");
  const tplFila    = document.getElementById("pf_tplFilaProducto");

  // Si esta vista no tiene el modal, no hacemos nada
  if (  !tbody || !tplFila) return;

  function ocultarEmpty() {
    if (!empty) return;
    empty.classList.add("d-none");
  }

  function mostrarEmptySiNoHayFilas() {
    if (!empty) return;
    if (tbody.children.length === 0) empty.classList.remove("d-none");
    else empty.classList.add("d-none");
  }

  function agregarFilaProducto() {
    // 1) Ocultar placeholder "No hay productos"
    ocultarEmpty();

    // 2) Clonar el template
    const fragmento = tplFila.content.cloneNode(true);
    const tr = fragmento.querySelector("tr");
    if (!tr) return;

    // (Opcional) Marcar como fila nueva/draft
    tr.dataset.state = "draft";

    // 3) Insertar fila (arriba o abajo, tú decides)
    // Arriba:
    tbody.insertBefore(tr, tbody.firstChild);
    // Abajo (alternativa):
    // tbody.appendChild(tr);

    // 4) Re-render iconos feather
    if (window.feather) window.feather.replace();

    // 5) Enfocar el primer input (Descripción)
    const inpDesc = tr.querySelector(".pf_descripcion");
    if (inpDesc) inpDesc.focus();

    // 6) Asegurar que el empty se muestre/oculte correctamente
    mostrarEmptySiNoHayFilas();
  }

 

  // (Opcional) Si el modal se abre sin filas, mostrar empty
   
  if (modalEl) {
    modalEl.addEventListener("shown.bs.modal", function () {
      mostrarEmptySiNoHayFilas();
    });
  }


  // Guard rails
  if (!pfTbody) {
    // Si el modal no existe en esta vista, no hacemos nada
    return;
  }

  // ==========================
  // ESTADO MODAL
  // ==========================
  let facturaIdActual = 0;
  let page = 1;
  let perPage = 200; // modal suele traer todo
  let debounce = null;
  let xhrActual = null;

  // ==========================
  // HELPERS
  // ==========================
  function esc(s) {
    if (s === null || s === undefined) return "";
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function fmtDateToDDMMMYY(dateStr) {
    if (!dateStr) return "—";
    const part = String(dateStr).slice(0, 10);
    const [y, m, d] = part.split("-");
    if (!y || !m || !d) return esc(dateStr);

    const months = ["ene","feb","mar","abr","may","jun","jul","ago","sep","oct","nov","dic"];
    const mm = parseInt(m, 10);
    const yy = String(y).slice(-2);
    return `${parseInt(d, 10)}-${months[(mm - 1) || 0]}-${yy}`;
  }

  function buildQuery(params) {
    const qp = [];
    Object.keys(params).forEach((k) => {
      const v = params[k];
      if (v === null || v === undefined) return;
      qp.push(encodeURIComponent(k) + "=" + encodeURIComponent(String(v)));
    });
    return qp.join("&");
  }

  function setLoading() {
    pfTbody.innerHTML = `
      <tr class="text-center">
        <td colspan="10" class="py-4 text-muted">Cargando...</td>
      </tr>`;
    if (pfEmpty) pfEmpty.classList.add("d-none");
  }

  function renderEmpty() {
    pfTbody.innerHTML = "";
    if (pfEmpty) pfEmpty.classList.remove("d-none");

    if (pfBadgeCount) pfBadgeCount.textContent = "0";
    if (pfTotalCajas) pfTotalCajas.textContent = "Cajas: 0";
    if (pfTotalPiezas) pfTotalPiezas.textContent = "Piezas: 0";
    if (pfTotalPallets) pfTotalPallets.textContent = "Pallets RCV: 0";
    if (pfMeta) pfMeta.textContent = "Mostrando 0 de 0";
  }

  function renderRows(rows) {
    if (!rows || rows.length === 0) {
      renderEmpty();
      if (window.feather) window.feather.replace();
      return;
    }

    if (pfEmpty) pfEmpty.classList.add("d-none");

    let html = "";
    rows.forEach((p) => {
      const idDetalle = p.id ?? p.id_producto ?? p.id_detalle ?? p.detalle_id ?? "";
      const descripcion = p.descripcion ?? "—";
      const upc = p.upc ?? "—";
      const marca = p.marca ?? "—";
      const expiracion = p.expiracion ? fmtDateToDDMMMYY(p.expiracion) : "—";
      const innerPack = (p.inner_pack ?? "") === null ? "" : (p.inner_pack ?? "");
      const casePack = (p.case_pack ?? "") === null ? "" : (p.case_pack ?? "");
      const palletsRcv = p.pallets_rcv ?? 0;
      const cajas = p.cajas ?? 0;
      const piezas = p.piezas ?? 0;

      html += `
        <tr class="text-center"
      data-id="${esc(idDetalle)}"
      data-expiracion="${esc((p.expiracion || "").slice(0,10))}">
          <td class="text-start">${esc(descripcion)}</td>
          <td>${esc(upc)}</td>
          <td>${esc(marca)}</td>
          <td>${esc(expiracion)}</td>
          <td>${esc(innerPack)}</td>
          <td>${esc(casePack)}</td>
          <td>${esc(palletsRcv)}</td>
          <td>${esc(cajas)}</td>
          <td>${esc(piezas)}</td>

          <td>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-warning pf_btnEditar" data-id="${esc(idDetalle)}" title="Editar">
                <i data-feather="edit"></i>
              </button>
              <button type="button" class="btn btn-outline-danger pf_btnEliminar" data-id="${esc(idDetalle)}" title="Eliminar">
                <i data-feather="trash-2"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    pfTbody.innerHTML = html;
    if (window.feather) window.feather.replace();
  }

  function renderTotals(meta, totals) {
    const total = meta?.total ?? 0;
    if (pfBadgeCount) pfBadgeCount.textContent = String(total);

    if (pfTotalCajas) pfTotalCajas.textContent = `Cajas: ${totals?.total_cajas ?? 0}`;
    if (pfTotalPiezas) pfTotalPiezas.textContent = `Piezas: ${totals?.total_piezas ?? 0}`;
    if (pfTotalPallets) pfTotalPallets.textContent = `Pallets RCV: ${totals?.total_pallets_rcv ?? 0}`;

    const showing = (meta?.page && meta?.per_page)
      ? Math.min(total, meta.page * meta.per_page)
      : total;

    if (pfMeta) pfMeta.textContent = `Mostrando ${showing} de ${total}`;
  }

  // ==========================
// INLINE EDIT (SOLO UI)
// Convierte un renglón en inputs y permite cancelar
// ==========================

// Solo 1 fila en edición a la vez (opcional pero recomendado)
function cancelarOtrasEdiciones(exceptTr) {
  const editando = pfTbody.querySelectorAll('tr[data-editing="1"]');
  editando.forEach(tr => {
    if (tr !== exceptTr) cancelarEdicionFila(tr);
  });
}

function entrarEdicionFila(tr) {
  if (!tr) return;

  // Si ya está editando, no lo vuelve a convertir
  if (tr.dataset.editing === "1") return;

  cancelarOtrasEdiciones(tr);

  // Guardar snapshot para cancelar
  tr.dataset.editing = "1";
  tr.dataset.originalHtml = tr.innerHTML;

  const tds = tr.querySelectorAll("td");
  // Estructura esperada en tu tabla:
  // 0 descripcion, 1 upc, 2 marca, 3 expiracion, 4 inner, 5 case,
  // 6 pallets_rcv, 7 cajas, 8 piezas, 9 acciones
  if (tds.length < 10) return;

  const getText = (i) => (tds[i]?.textContent || "").trim();

  const descripcion = getText(0);
  const upc         = getText(1);
  const marca       = getText(2);

  // Para date, usa el raw si lo guardaste en data-expiracion (YYYY-MM-DD)
  const expiracionRaw = (tr.dataset.expiracion || "").trim();

  const innerPack   = getText(4);
  const casePack    = getText(5);
  const palletsRcv  = getText(6);
  const cajas       = getText(7);
  const piezas      = getText(8);

  // Reemplazar celdas por inputs
  tds[0].innerHTML = `<input type="text" class="form-control form-control-sm pf_edit_descripcion" value="${esc(descripcion)}">`;
  tds[1].innerHTML = `<input type="text" class="form-control form-control-sm pf_edit_upc" value="${esc(upc)}">`;
  tds[2].innerHTML = `<input type="text" class="form-control form-control-sm pf_edit_marca" value="${esc(marca)}">`;

  // Date (si no hay raw, lo dejamos vacío)
  tds[3].innerHTML = `<input type="date" class="form-control form-control-sm pf_edit_expiracion" value="${esc(expiracionRaw)}">`;

  tds[4].innerHTML = `<input type="text" class="form-control form-control-sm pf_edit_inner" value="${esc(innerPack)}">`;
  tds[5].innerHTML = `<input type="text" class="form-control form-control-sm pf_edit_case" value="${esc(casePack)}">`;

  tds[6].innerHTML = `<input type="number" min="0" step="1" class="form-control form-control-sm pf_edit_pallets_rcv" value="${esc(palletsRcv || "0")}">`;
  tds[7].innerHTML = `<input type="number" min="0" step="1" class="form-control form-control-sm pf_edit_cajas" value="${esc(cajas || "0")}">`;
  tds[8].innerHTML = `<input type="number" min="0" step="1" class="form-control form-control-sm pf_edit_piezas" value="${esc(piezas || "0")}">`;

  // Cambiar botón Editar a Cancelar en la celda de acciones (última)
  const btnEditar = tr.querySelector(".pf_btnEditar");
  if (btnEditar) {
    btnEditar.classList.remove("btn-outline-warning");
    btnEditar.classList.add("btn-outline-secondary");
    btnEditar.dataset.mode = "cancel"; // marcador
    btnEditar.title = "Cancelar";
    // Cambiar icono a X
    btnEditar.innerHTML = `<i data-feather="x"></i>`;
  }

  if (window.feather) window.feather.replace();

  // Enfocar primer input
  const first = tr.querySelector(".pf_edit_descripcion");
  if (first) first.focus();
}

function cancelarEdicionFila(tr) {
  if (!tr) return;
  if (tr.dataset.editing !== "1") return;

  const snap = tr.dataset.originalHtml || "";
  if (snap) tr.innerHTML = snap;

  delete tr.dataset.editing;
  delete tr.dataset.originalHtml;

  if (window.feather) window.feather.replace();
}

// Delegación de evento para el botón editar/cancelar
pfTbody.addEventListener("click", function (e) {
  const btn = e.target.closest(".pf_btnEditar");
  if (!btn) return;

  const tr = btn.closest("tr");
  if (!tr) return;

  // Si está en modo cancelar o ya está editando, cancela
  if (tr.dataset.editing === "1" || btn.dataset.mode === "cancel") {
    cancelarEdicionFila(tr);
    return;
  }

  // Entra en edición
  entrarEdicionFila(tr);
});

// Opcional: si cierran el modal, cancela cualquier edición activa
if (modalEl) {
  modalEl.addEventListener("hidden.bs.modal", function () {
    const editando = pfTbody.querySelectorAll('tr[data-editing="1"]');
    editando.forEach(tr => cancelarEdicionFila(tr));
  });
}

  // ==========================
  // CARGA PRODUCTOS
  // ==========================
  function cargarProductosFactura(facturaId) {
    facturaIdActual = parseInt(facturaId, 10) || 0;

    if (inputFacturaHidden) inputFacturaHidden.value = String(facturaIdActual);

    if (facturaIdActual <= 0) {
      renderEmpty();
      return;
    }

    try {
      if (xhrActual && xhrActual.readyState !== 4) xhrActual.abort();
    } catch (_) {}

    const term = pfBuscar ? (pfBuscar.value || "").trim() : "";

    const qs = buildQuery({
      factura_id: facturaIdActual,
      term,
      page,
      per_page: perPage
    });

    const url = base_url + ENDPOINT_LISTAR_PRODUCTOS + "?" + qs;

    setLoading();

    const xhr = new XMLHttpRequest();
    xhrActual = xhr;

    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        let res;
        try { res = JSON.parse(xhr.responseText); }
        catch (e) {
          console.error("[OP Partida Productos] JSON inválido:", e, xhr.responseText);
          renderEmpty();
          return;
        }

        if (!res || res.ok !== true) {
          console.warn("[OP Partida Productos] Respuesta no ok:", res);
          renderEmpty();
          return;
        }

        renderRows(res.data || []);
        renderTotals(res.meta || {}, res.totals || {});
        return;
      }

      console.error("[OP Partida Productos] Error HTTP:", xhr.status, xhr.responseText);
      renderEmpty();
    };

    xhr.send();
  }

  // Hacer disponible esta función (para registrar, editar, etc.)
  window.opPartidaCargarProductosFactura = function (facturaId) {
    page = 1;
    cargarProductosFactura(facturaId);
  };

  // ==========================
  // CLICK EN "VER PRODUCTOS" (VIENE DESDE FACTURAS)
  // ==========================
  function bindClickVerProductos() {
    // Escuchamos en todo el documento para que funcione aunque la tabla se renderice dinámicamente
    document.addEventListener("click", function (e) {
      const btnVer = e.target.closest(".btnVerProductosFactura");
      if (!btnVer) return;

      // Llenar labels del modal
      if (lblId) lblId.textContent = btnVer.getAttribute("data-invoice") || "—";
      if (lblVendor) lblVendor.textContent = btnVer.getAttribute("data-vendor") || "—";
      if (lblXdock) lblXdock.textContent = btnVer.getAttribute("data-xdock") || "—";
      if (lblRec) lblRec.textContent = btnVer.getAttribute("data-recibido") || "—";
      if (lblRev) lblRev.textContent = btnVer.getAttribute("data-revision") || "—";
      if (lblPal) lblPal.textContent = btnVer.getAttribute("data-pallets_inv") || "—";

      // Reset búsqueda y cargar
      page = 1;
      if (pfBuscar) pfBuscar.value = "";

      const facturaId = btnVer.getAttribute("data-invoice") || "0";
      cargarProductosFactura(facturaId);
    });
  }

  // ==========================
  // BUSCADOR MODAL + LIMPIEZA
  // ==========================
  function bindModal() {
    if (pfBuscar) {
      pfBuscar.addEventListener("input", function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          page = 1;
          if (facturaIdActual > 0) cargarProductosFactura(facturaIdActual);
        }, 250);
      });
    }

    if (modalEl) {
      modalEl.addEventListener("hidden.bs.modal", function () {
        if (pfBuscar) pfBuscar.value = "";
        facturaIdActual = 0;
        page = 1;
        renderEmpty();
      });
    }
  }

  // ==========================
  // EVENTO DE RECARGA (PARA TU JS DE REGISTRAR)
  // ==========================
  document.addEventListener("opPartida:productos:refresh", function (ev) {
    const facturaId = ev.detail?.facturaId || 0;
    if (facturaId > 0) {
      page = 1;
      cargarProductosFactura(facturaId);
    }
  });

  // ==========================
  // INIT
  // ==========================
  document.addEventListener("DOMContentLoaded", function () {
    bindClickVerProductos();
    bindModal();
  });


  // ==========================
// Eliminar fila (renglón agregado desde template)
// ==========================
pfTbody.addEventListener("click", function (e) {
  const btn = e.target.closest(".pf_btnEliminarFila");
  if (!btn) return;

  const tr = btn.closest("tr");
  if (!tr) return;

  // Si había una fila "en edición" guardada, limpiamos markers
  if (tr.dataset && tr.dataset.editing === "1") {
    delete tr.dataset.editing;
    delete tr.dataset.originalHtml;
  }

  tr.remove();

  // Si manejas totales en el modal, recalcula aquí (opcional)
  // calcularTotalesEnModal(); // <-- si ya tienes una función así
});



// ==========================
// Auto-cálculo Piezas = CasePack * Cajas
// Permite override manual si el usuario edita piezas
// ==========================

function toNum(v) {
  // Acepta "12", "12.5", " 12 ", etc. Si no es número => 0
  const n = parseFloat(String(v ?? "").replace(",", ".").trim());
  return Number.isFinite(n) ? n : 0;
}

function calcularPiezasFila(tr) {
  if (!tr) return;

  const inpCase  = tr.querySelector(".pf_case");
  const inpCajas = tr.querySelector(".pf_cajas");
  const inpPzs   = tr.querySelector(".pf_piezas"); // AJUSTA si tu clase es distinta

  if (!inpCase || !inpCajas || !inpPzs) return;

  const casePack = toNum(inpCase.value);
  const cajas    = toNum(inpCajas.value);

  // Si no hay datos, no fuerces nada
  if (casePack <= 0 || cajas <= 0) return;

  const calculado = Math.round(casePack * cajas); // si necesitas decimales, quita Math.round

  // Si el usuario ya hizo override manual, no pisar
  if (tr.dataset.pzsManual === "1") return;

  inpPzs.value = String(calculado);
  // Guardamos el último valor calculado para comparar
  tr.dataset.pzsCalc = String(calculado);
}

function evaluarOverrideManual(tr) {
  if (!tr) return;

  const inpPzs = tr.querySelector(".pf_piezas"); // AJUSTA si tu clase es distinta
  if (!inpPzs) return;

  const val = (inpPzs.value || "").trim();

  // Si el usuario deja vacío, “desbloquea” y vuelve a auto-calcular en el siguiente cambio
  if (val === "") {
    delete tr.dataset.pzsManual;
    return;
  }

  const actual = toNum(val);
  const calc   = toNum(tr.dataset.pzsCalc);

  // Si todavía no hay calc guardado, no marques manual
  if (!Number.isFinite(calc) || calc <= 0) return;

  // Si es diferente al calculado, marcamos override manual
  if (actual !== calc) {
    tr.dataset.pzsManual = "1";
  } else {
    // Si coincide, podemos considerarlo “no manual”
    delete tr.dataset.pzsManual;
  }
}

// 1) Cuando cambie CasePack o Cajas => auto-calcula piezas (si no está manual)
pfTbody.addEventListener("input", function (e) {
  const el = e.target;

  if (!el.classList) return;

  if (el.classList.contains("pf_case") || el.classList.contains("pf_cajas")) {
    const tr = el.closest("tr");
    calcularPiezasFila(tr);
  }

  // 2) Si el usuario escribe piezas => detectar override manual
  if (el.classList.contains("pf_piezas")) {
    const tr = el.closest("tr");
    evaluarOverrideManual(tr);
  }
});

// 3) Opcional: al salir del input piezas, reafirmar override
pfTbody.addEventListener("blur", function (e) {
  const el = e.target;
  if (el?.classList?.contains("pf_piezas")) {
    evaluarOverrideManual(el.closest("tr"));
  }
}, true);

})();
