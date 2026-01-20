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
  const btnAgregar = document.getElementById("pf_btnAgregarLinea");
  const tbody      = document.getElementById("pf_tbody");
  const empty      = document.getElementById("pf_empty");
  const tplFila    = document.getElementById("pf_tplFilaProducto");

  // Si esta vista no tiene el modal, no hacemos nada
  if (!btnAgregar || !tbody || !tplFila) return;

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

  // Click en "+"
  btnAgregar.addEventListener("click", agregarFilaProducto);

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
        <tr class="text-center" data-id="${esc(idDetalle)}">
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

})();
