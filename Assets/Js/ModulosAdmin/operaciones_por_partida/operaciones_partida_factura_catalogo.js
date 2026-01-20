// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_factura.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // OJO: si tu ruteo es sensible a mayúsculas/minúsculas, prueba también:
  // const ENDPOINT_LISTAR = "operaciones_por_partida/listar";
  const ENDPOINT_LISTAR = "Operaciones_por_partida/listar";

  // ===== IDs reales de TU vista =====
  const tbody        = document.getElementById("operaciones_partida_facturasBody");
  const inputBuscar  = document.getElementById("operaciones_partida_buscar");
  const selectBodega = document.getElementById("operaciones_partida_filtroXDock");
  const inpFi        = document.getElementById("operaciones_partida_fechaInicio");
  const inpFf        = document.getElementById("operaciones_partida_fechaFin");
  const selectPerPage= document.getElementById("operaciones_partida_perPage");
  const ulPaginacion = document.getElementById("operaciones_partida_paginacion");
  const lblMeta      = document.getElementById("operaciones_partida_metaResumen");
  const tabla        = document.getElementById("operaciones_partida_TablaFacturasExportar");

  // ===== Estado =====
  let currentPage = 1;
  let perPage = parseInt(selectPerPage?.value || "10", 10);
  let debounceId = null;
  let currentXHR = null;

  // ===== Guard rails: si no existe el tbody, no tiene sentido seguir =====
  if (!tbody) {
    console.error("[OP Partida] No existe tbody #operaciones_partida_facturasBody. Revisa tu vista/IDs.");
    return;
  }

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
    tbody.innerHTML = `
      <tr class="text-center">
        <td colspan="9" class="py-4 text-muted">Cargando...</td>
      </tr>`;
  }

  function badgeRevision(val) {
    // ajusta según tu DB (0/1, SI/NO, etc.)
    const s = String(val ?? "").toLowerCase();
    const ok = (s === "1" || s === "si" || s === "sí" || s === "true");
    return ok
      ? `<span class="badge bg-success text-white">Sí</span>`
      : `<span class="badge bg-secondary text-white">No</span>`;
  }

  function renderRows(rows) {
    if (!rows || rows.length === 0) {
      tbody.innerHTML = `
        <tr class="text-center">
          <td colspan="9" class="py-4 text-muted">Sin resultados.</td>
        </tr>`;
      if (window.feather) window.feather.replace();
      return;
    }

    let html = "";

    rows.forEach((r) => {
      // Campos esperados del backend
      const idFactura = r.id_factura ?? "";
      const bodega = r.bodega_nombre ?? "—";
      const revision = r.revision_pasa ?? "";
      const numeroFactura = r.numero_factura ?? "—";
      const pallets = r.pallets_inv ?? 0;
      const proveedor = r.proveedor ?? "—";
      const fechaRec = fmtDateToDDMMMYY(r.fecha_recibido);
      const productosCount = r.productos_count ?? 0;

      // Si quieres estilo FAC-00043 como tu ejemplo:
      const idPretty = `FAC-${String(idFactura).padStart(5, "0")}`;

      html += `
        <tr class="text-center">
          <td class="fw-semibold">${esc(idPretty)}</td>
          <td>${esc(bodega)}</td>
          <td>
            <div class="d-flex flex-column gap-1 align-items-center">
              ${badgeRevision(revision)}
            </div>
          </td>
          <td>${esc(numeroFactura)}</td>
          <td>${esc(pallets)}</td>
          <td class="text-start">${esc(proveedor)}</td>
          <td>${esc(fechaRec)}</td>
          <td><span class="badge bg-light text-dark border">${esc(productosCount)}</span></td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
              <button type="button"
                class="btn btn-outline-primary btn-sm btnVerProductosFactura"
                data-bs-toggle="modal"
                data-bs-target="#modalProductosFactura"
                data-invoice="${esc(idFactura)}"
                data-vendor="${esc(proveedor)}"
                data-xdock="${esc(bodega)}"
                data-recibido="${esc(fechaRec)}"
                data-revision="${esc(String(revision).toUpperCase())}"
                data-costo="${esc(r.costo ?? "")}"
                data-pallets_inv="${esc(pallets)}"
                title="Ver productos">
                <i data-feather="list"></i>
              </button>

              <button type="button"
                class="btn btn-outline-warning btnEditarFactura"
                data-id="${esc(idFactura)}"
                title="Editar encabezado">
                <i data-feather="edit"></i>
              </button>

              <button type="button"
                class="btn btn-outline-danger btnEliminarFactura"
                data-id="${esc(idFactura)}"
                title="Eliminar">
                <i data-feather="trash-2"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    if (window.feather) window.feather.replace();
  }

  function renderMeta(meta) {
    if (!lblMeta) return;

    const total = meta?.total ?? 0;
    const page = meta?.page ?? 1;
    const per_page = meta?.per_page ?? perPage;

    // En tu UI tienes "Mostrando 0-0 de 0"
    const from = total === 0 ? 0 : ((page - 1) * per_page + 1);
    const to = Math.min(total, page * per_page);

    lblMeta.textContent = `Mostrando ${from}-${to} de ${total}`;
  }

  function renderPaginacion(meta) {
    if (!ulPaginacion) return;

    const page = meta?.page ?? 1;
    const totalPages = meta?.total_pages ?? 0;

    if (!totalPages || totalPages <= 1) {
      ulPaginacion.innerHTML = "";
      return;
    }

    const windowSize = 2;
    const start = Math.max(1, page - windowSize);
    const end = Math.min(totalPages, page + windowSize);

    let html = "";

    html += `
      <li class="page-item ${page <= 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page - 1}">«</a>
      </li>`;

    if (start > 1) {
      html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
      if (start > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    }

    for (let p = start; p <= end; p++) {
      html += `
        <li class="page-item ${p === page ? "active" : ""}">
          <a class="page-link" href="#" data-page="${p}">${p}</a>
        </li>`;
    }

    if (end < totalPages) {
      if (end < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
      html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    html += `
      <li class="page-item ${page >= totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page + 1}">»</a>
      </li>`;

    ulPaginacion.innerHTML = html;
  }

  function cargarFacturas() {
    // Abort previo
    try {
      if (currentXHR && currentXHR.readyState !== 4) currentXHR.abort();
    } catch (_) {}

    const bodega_id = selectBodega ? (selectBodega.value || "") : "";
    const term = inputBuscar ? (inputBuscar.value || "").trim() : "";
    const fi = inpFi ? (inpFi.value || "") : "";
    const ff = inpFf ? (inpFf.value || "") : "";
    perPage = parseInt(selectPerPage?.value || String(perPage || 10), 10) || 10;

    const qs = buildQuery({
      bodega_id,
      term,
      fi,
      ff,
      page: currentPage,
      per_page: perPage
    });

    const url = base_url + ENDPOINT_LISTAR + "?" + qs;

    setLoading();

    const xhr = new XMLHttpRequest();
    currentXHR = xhr;

    xhr.open("GET", url, true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        let res;
        try {
          res = JSON.parse(xhr.responseText);
        } catch (e) {
          console.error("[OP Partida] JSON inválido:", e, xhr.responseText);
          renderRows([]);
          renderMeta({ total: 0, page: 1, per_page: perPage, total_pages: 0 });
          renderPaginacion({ page: 1, total_pages: 0 });
          return;
        }

        if (!res || res.ok !== true) {
          console.warn("[OP Partida] Respuesta no ok:", res);
          renderRows([]);
          renderMeta(res?.meta || { total: 0, page: 1, per_page: perPage, total_pages: 0 });
          renderPaginacion(res?.meta || { page: 1, total_pages: 0 });
          return;
        }

        renderRows(res.data || []);
        renderMeta(res.meta || {});
        renderPaginacion(res.meta || {});
        return;
      }

      console.error("[OP Partida] Error HTTP:", xhr.status, xhr.responseText);
      renderRows([]);
      renderMeta({ total: 0, page: 1, per_page: perPage, total_pages: 0 });
      renderPaginacion({ page: 1, total_pages: 0 });
    };

    xhr.send();
  }

  function bindFilters() {
    if (inputBuscar) {
      inputBuscar.addEventListener("input", function () {
        clearTimeout(debounceId);
        debounceId = setTimeout(function () {
          currentPage = 1;
          cargarFacturas();
        }, 250);
      });
    }

    if (selectBodega) {
      selectBodega.addEventListener("change", function () {
        currentPage = 1;
        cargarFacturas();
      });
    }

    if (inpFi) {
      inpFi.addEventListener("change", function () {
        currentPage = 1;
        cargarFacturas();
      });
    }

    if (inpFf) {
      inpFf.addEventListener("change", function () {
        currentPage = 1;
        cargarFacturas();
      });
    }

    if (selectPerPage) {
      selectPerPage.addEventListener("change", function () {
        currentPage = 1;
        cargarFacturas();
      });
    }

    if (ulPaginacion) {
      ulPaginacion.addEventListener("click", function (e) {
        const a = e.target.closest("a.page-link");
        if (!a) return;
        e.preventDefault();
        const p = parseInt(a.getAttribute("data-page"), 10);
        if (!p || p < 1) return;
        currentPage = p;
        cargarFacturas();
      });
    }
  }



    // ===== Endpoint productos =====
  const ENDPOINT_LISTAR_PRODUCTOS = "Operaciones_por_partida/listarProductos";

  // ===== Refs MODAL productos =====
  const pfTbody     = document.getElementById("pf_tbody");
  const pfEmpty     = document.getElementById("pf_empty");
  const pfMeta      = document.getElementById("pf_meta");
  const pfBuscar    = document.getElementById("pf_buscar");

  const pfBadgeCount    = document.getElementById("pf_badgeCount");
  const pfTotalCajas    = document.getElementById("pf_totalCajas");
  const pfTotalPiezas   = document.getElementById("pf_totalPiezas");
  const pfTotalPallets  = document.getElementById("pf_totalPalletsRcv");

  // Estado modal
  let pfFacturaId = 0;
  let pfPage = 1;
  let pfPerPage = 200; // en modal normalmente quieres todo (puedes bajarlo a 50 si gustas)
  let pfDebounce = null;
  let pfXHR = null;

  function pfSetLoading() {
    if (!pfTbody) return;
    pfTbody.innerHTML = `
      <tr class="text-center">
        <td colspan="9" class="py-4 text-muted">Cargando...</td>
      </tr>`;
    if (pfEmpty) pfEmpty.classList.add("d-none");
  }

  function pfRenderEmpty() {
    if (!pfTbody) return;
    pfTbody.innerHTML = "";
    if (pfEmpty) pfEmpty.classList.remove("d-none");

    if (pfBadgeCount) pfBadgeCount.textContent = "0";
    if (pfTotalCajas) pfTotalCajas.textContent = "Cajas: 0";
    if (pfTotalPiezas) pfTotalPiezas.textContent = "Piezas: 0";
    if (pfTotalPallets) pfTotalPallets.textContent = "Pallets RCV: 0";
    if (pfMeta) pfMeta.textContent = "Mostrando 0 de 0";
  }
function pfRenderRows(rows) {
  if (!pfTbody) return;

  if (!rows || rows.length === 0) {
    pfRenderEmpty();
    if (window.feather) window.feather.replace();
    return;
  }

  if (pfEmpty) pfEmpty.classList.add("d-none");

  let html = "";
  rows.forEach((p) => {
    // Ajusta a tu PK real. Si tu API devuelve otro nombre, cámbialo aquí.
    const idDetalle = p.id ?? p.id_detalle ?? p.detalle_id ?? "";

    const descripcion = p.descripcion ?? "—";
    const upc         = p.upc ?? "—";
    const marca       = p.marca ?? "—";
    const expiracion  = p.expiracion ? fmtDateToDDMMMYY(p.expiracion) : "—";
    const innerPack   = (p.inner_pack ?? "") === null ? "" : (p.inner_pack ?? "");
    const casePack    = (p.case_pack ?? "") === null ? "" : (p.case_pack ?? "");
    const palletsInv  = p.pallets_rcv ?? 0;
    const cajas       = p.cajas ?? 0;
    const piezas      = p.piezas ?? 0;

    html += `
      <tr class="text-center"
          data-id="${esc(idDetalle)}"
          data-upc="${esc(upc)}">
        <td class="text-start">${esc(descripcion)}</td>
        <td>${esc(upc)}</td>
        <td>${esc(marca)}</td>
        <td>${esc(expiracion)}</td>
        <td>${esc(innerPack)}</td>
        <td>${esc(casePack)}</td>
        <td>${esc(palletsInv)}</td>
        <td>${esc(cajas)}</td>
        <td>${esc(piezas)}</td>

        <td>
          <div class="btn-group btn-group-sm" role="group" aria-label="Acciones producto">
            <button type="button"
              class="btn btn-outline-warning pf_btnEditar"
              data-id="${esc(idDetalle)}"
              title="Editar">
              <i data-feather="edit"></i>
            </button>

            <button type="button"
              class="btn btn-outline-danger pf_btnEliminar"
              data-id="${esc(idDetalle)}"
              title="Eliminar">
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

if (pfTbody) {
  pfTbody.addEventListener("click", function (e) {
    const btnEditar = e.target.closest(".pf_btnEditar");
    const btnEliminar = e.target.closest(".pf_btnEliminar");

    if (btnEditar) {
      const id = btnEditar.getAttribute("data-id") || "";
      // TODO: aquí llamas tu flujo de edición (por ejemplo: convertir la fila a inputs o abrir un modal pequeño)
      console.log("Editar producto id:", id);
      return;
    }

    if (btnEliminar) {
      const id = btnEliminar.getAttribute("data-id") || "";
      // TODO: aquí llamas tu endpoint de baja/eliminación del producto
      console.log("Eliminar producto id:", id);
      return;
    }
  });
}

  function pfRenderTotals(meta, totals) {
    const total = meta?.total ?? 0;

    if (pfBadgeCount) pfBadgeCount.textContent = String(total);

    if (pfTotalCajas) {
      pfTotalCajas.textContent = `Cajas: ${totals?.total_cajas ?? 0}`;
    }
    if (pfTotalPiezas) {
      pfTotalPiezas.textContent = `Piezas: ${totals?.total_piezas ?? 0}`;
    }
    if (pfTotalPallets) {
      pfTotalPallets.textContent = `Pallets RCV: ${totals?.total_pallets_rcv ?? 0}`;
    }

    // Meta simple (como tu vista)
    const showing = (meta?.page && meta?.per_page)
      ? Math.min(total, meta.page * meta.per_page)
      : total;

    if (pfMeta) pfMeta.textContent = `Mostrando ${showing} de ${total}`;
  }

  function cargarProductosFactura(facturaId) {
    if (!pfTbody) return;

    pfFacturaId = parseInt(facturaId, 10) || 0;
    if (pfFacturaId <= 0) {
      pfRenderEmpty();
      return;
    }

    // Abort previo
    try {
      if (pfXHR && pfXHR.readyState !== 4) pfXHR.abort();
    } catch (_) {}

    const term = pfBuscar ? (pfBuscar.value || "").trim() : "";

    const qs = buildQuery({
      factura_id: pfFacturaId,
      term: term,
      page: pfPage,
      per_page: pfPerPage
    });

    const url = base_url + ENDPOINT_LISTAR_PRODUCTOS + "?" + qs;

    pfSetLoading();

    const xhr = new XMLHttpRequest();
    pfXHR = xhr;

    xhr.open("GET", url, true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        let res;
        try {
          res = JSON.parse(xhr.responseText);
        } catch (e) {
          console.error("[OP Partida] JSON inválido (productos):", e, xhr.responseText);
          pfRenderEmpty();
          return;
        }

        if (!res || res.ok !== true) {
          console.warn("[OP Partida] Respuesta no ok (productos):", res);
          pfRenderEmpty();
          return;
        }

        pfRenderRows(res.data || []);
        pfRenderTotals(res.meta || {}, res.totals || {});
        return;
      }

      console.error("[OP Partida] Error HTTP productos:", xhr.status, xhr.responseText);
      pfRenderEmpty();
    };

    xhr.send();
  }

  function bindModalFilters() {
    if (!pfBuscar) return;

    pfBuscar.addEventListener("input", function () {
      clearTimeout(pfDebounce);
      pfDebounce = setTimeout(function () {
        pfPage = 1;
        if (pfFacturaId > 0) cargarProductosFactura(pfFacturaId);
      }, 250);
    });

    // Limpieza cuando se cierre el modal
    const modalEl = document.getElementById("modalProductosFactura");
    if (modalEl) {
      modalEl.addEventListener("hidden.bs.modal", function () {
        if (pfBuscar) pfBuscar.value = "";
        pfFacturaId = 0;
        pfPage = 1;
        pfRenderEmpty();
      });
    }
  }

  function bindRowActions() {
    const root = tabla || document;

    root.addEventListener("click", function (e) {
      const btnVer = e.target.closest(".btnVerProductosFactura");
      if (btnVer) {
        const lblId = document.getElementById("pf_lblFactura");
        const lblVendor = document.getElementById("pf_lblProveedor");
        const lblXdock = document.getElementById("pf_lblXdock");
        const lblRec = document.getElementById("pf_lblRecibido");
        const lblRev = document.getElementById("pf_lblRevision"); 
        const lblPal = document.getElementById("pf_lblPalletsRcv");

        if (lblId) lblId.textContent = btnVer.getAttribute("data-invoice") || "—";
        if (lblVendor) lblVendor.textContent = btnVer.getAttribute("data-vendor") || "—";
        if (lblXdock) lblXdock.textContent = btnVer.getAttribute("data-xdock") || "—";
        if (lblRec) lblRec.textContent = btnVer.getAttribute("data-recibido") || "—";
        if (lblRev) lblRev.textContent = btnVer.getAttribute("data-revision") || "—"; 
        if (lblPal) lblPal.textContent = btnVer.getAttribute("data-pallets_inv") || "—";

        // ===== NUEVO: cargar productos =====
        const facturaId = btnVer.getAttribute("data-invoice") || "0";
        pfPage = 1;
        if (pfBuscar) pfBuscar.value = "";
        cargarProductosFactura(facturaId);

        return;
      }

    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    bindFilters();
    bindRowActions();
    bindModalFilters();
    cargarFacturas();
  });
})();
