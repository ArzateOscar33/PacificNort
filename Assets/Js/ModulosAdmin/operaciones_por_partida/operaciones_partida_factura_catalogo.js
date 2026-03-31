// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_facturas_catalogo.js
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

  const ENDPOINT_LISTAR = "Operaciones_por_partida/listar";

  // ==========================
  // REFS (TU VISTA)
  // ==========================
  const tbody = document.getElementById("operaciones_partida_facturasBody");
  const inputBuscar = document.getElementById("operaciones_partida_buscar");
  const selectBodega = document.getElementById(
    "operaciones_partida_filtroXDock",
  );
  const inpFi = document.getElementById("operaciones_partida_fechaInicio");
  const inpFf = document.getElementById("operaciones_partida_fechaFin");
  const selectPerPage = document.getElementById("operaciones_partida_perPage");
  const ulPaginacion = document.getElementById(
    "operaciones_partida_paginacion",
  );
  const lblMeta = document.getElementById("operaciones_partida_metaResumen");
  const tabla = document.getElementById(
    "operaciones_partida_TablaFacturasExportar",
  );

  // Guard rail
  if (!tbody) {
    console.error(
      "[OP Partida Facturas] No existe #operaciones_partida_facturasBody. Revisa tu vista/IDs.",
    );
    return;
  }

  // ==========================
  // ESTADO
  // ==========================
  let currentPage = 1;
  let perPage = parseInt(selectPerPage?.value || "10", 10);
  let debounceId = null;
  let currentXHR = null;

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

    const months = [
      "ene",
      "feb",
      "mar",
      "abr",
      "may",
      "jun",
      "jul",
      "ago",
      "sep",
      "oct",
      "nov",
      "dic",
    ];
    const mm = parseInt(m, 10);
    const yy = String(y).slice(-2);
    return `${parseInt(d, 10)}-${months[mm - 1 || 0]}-${yy}`;
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
        <td colspan="13" class="py-4 text-muted">Cargando...</td>
      </tr>`;
  }

  function badgeRevision(val) {
    const n = parseInt(String(val ?? "0"), 10);

    switch (n) {
      case 0:
        return `<span class="badge bg-secondary text-white">Factura No Revisada</span>`;
      case 1:
        return `<span class="badge bg-success text-white">Factura Revisada</span>`;
      case 2:
        return `<span class="badge bg-warning text-dark">Envío sin Revisión</span>`;
      case 3:
        return `<span class="badge bg-danger text-white">Factura No Cuadrada</span>`;
      default:
        return `<span class="badge bg-light text-dark border">Sin estatus</span>`;
    }
  }
  function revisionLabel(val) {
    const n = parseInt(String(val ?? "0"), 10);

    switch (n) {
      case 0:
        return "Factura No Revisada";
      case 1:
        return "Factura Revisada";
      case 2:
        return "Envío sin Revisión";
      case 3:
        return "Factura No Cuadrada";
      default:
        return "Sin estatus";
    }
  }

  // ==========================
  // RENDER
  // ==========================
  function renderRows(rows) {
    if (!rows || rows.length === 0) {
      tbody.innerHTML = `
      <tr class="text-center">
        <td colspan="13" class="py-4 text-muted">Sin resultados.</td>
      </tr>`;
      if (window.feather) window.feather.replace();
      return;
    }

    let html = "";

    rows.forEach((r) => {
      const idFactura = r.id_factura ?? "";
      const bodega = r.bodega_nombre ?? "—";
      const revision = r.revision_estatus ?? 0;
      const numeroFactura = r.numero_factura ?? "—";
      const pallets = r.pallets_inv ?? 0;
      const proveedor = r.proveedor ?? "—";
      const fechaRec = fmtDateToDDMMMYY(r.fecha_recibido);
      const productosCount = r.productos_count ?? 0;
      const cliente = r.cliente_nombre ?? "—";

      const cajasTotales = parseInt(r.cajas_totales ?? 0, 10);
      const cajasEnviadas = parseInt(r.cajas_enviadas ?? 0, 10);
      const cajasRestantes = parseInt(r.cajas_restantes ?? 0, 10);

      const idPretty = `FAC-${String(idFactura).padStart(5, "0")}`;

      html += `
      <tr class="text-center">
        <td class="fw-semibold">${esc(idPretty)}</td>
        <td>${esc(bodega)}</td>
        <td>${badgeRevision(revision)}</td>
        <td>${esc(numeroFactura)}</td>
        <td>${esc(cliente)}</td>
        <td>${esc(pallets)}</td>
        <td class="text-start">${esc(proveedor)}</td>
        <td>${esc(fechaRec)}</td>
        <td><span class="badge bg-light text-dark border">${esc(productosCount)}</span></td>
        <td><span class="badge bg-primary text-white pd-2">${esc(cajasTotales)}</span></td>
        <td><span class="badge bg-info text-white">${esc(cajasEnviadas)}</span></td>
        <td><span class="badge bg-warning text-dark">${esc(cajasRestantes)}</span></td>

        <td class="text-end">
          <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
            <button type="button"
              class="btn btn-outline-primary btn-sm btnVerProductosFactura"
              data-bs-toggle="modal"
              data-bs-target="#modalProductosFactura"
              data-invoice="${esc(idFactura)}"
              data-vendor="${esc(proveedor)}"
              data-cliente="${esc(cliente)}"
              data-xdock="${esc(bodega)}"
              data-recibido="${esc(fechaRec)}"
              data-revision="${esc(revisionLabel(revision))}"
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

    const from = total === 0 ? 0 : (page - 1) * per_page + 1;
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
      if (start > 2)
        html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    }

    for (let p = start; p <= end; p++) {
      html += `
        <li class="page-item ${p === page ? "active" : ""}">
          <a class="page-link" href="#" data-page="${p}">${p}</a>
        </li>`;
    }

    if (end < totalPages) {
      if (end < totalPages - 1)
        html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
      html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    html += `
      <li class="page-item ${page >= totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page + 1}">»</a>
      </li>`;

    ulPaginacion.innerHTML = html;
  }

  // ==========================
  // CARGA
  // ==========================
  function cargarFacturas() {
    try {
      if (currentXHR && currentXHR.readyState !== 4) currentXHR.abort();
    } catch (_) {}

    const bodega_id = selectBodega ? selectBodega.value || "" : "";
    const term = inputBuscar ? (inputBuscar.value || "").trim() : "";
    const fi = inpFi ? inpFi.value || "" : "";
    const ff = inpFf ? inpFf.value || "" : "";
    perPage = parseInt(selectPerPage?.value || String(perPage || 10), 10) || 10;

    const qs = buildQuery({
      bodega_id,
      term,
      fi,
      ff,
      page: currentPage,
      per_page: perPage,
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
          console.error(
            "[OP Partida Facturas] JSON inválido:",
            e,
            xhr.responseText,
          );
          renderRows([]);
          renderMeta({ total: 0, page: 1, per_page: perPage, total_pages: 0 });
          renderPaginacion({ page: 1, total_pages: 0 });
          return;
        }

        if (!res || res.ok !== true) {
          console.warn("[OP Partida Facturas] Respuesta no ok:", res);
          renderRows([]);
          renderMeta(
            res?.meta || {
              total: 0,
              page: 1,
              per_page: perPage,
              total_pages: 0,
            },
          );
          renderPaginacion(res?.meta || { page: 1, total_pages: 0 });
          return;
        }

        renderRows(res.data || []);
        renderMeta(res.meta || {});
        renderPaginacion(res.meta || {});
        return;
      }

      console.error(
        "[OP Partida Facturas] Error HTTP:",
        xhr.status,
        xhr.responseText,
      );
      renderRows([]);
      renderMeta({ total: 0, page: 1, per_page: perPage, total_pages: 0 });
      renderPaginacion({ page: 1, total_pages: 0 });
    };

    xhr.send();
  }

  // Exponer recarga (por si otros scripts quieren refrescar facturas)
  window.opPartidaListarFacturas = function (opts) {
    const reset = opts && opts.resetPage === true;
    if (reset) currentPage = 1;
    cargarFacturas();
  };

  // ==========================
  // BIND FILTROS + PAGINACIÓN
  // ==========================
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

  // ==========================
  // INIT
  // ==========================
  document.addEventListener("DOMContentLoaded", function () {
    bindFilters();
    cargarFacturas();
  });
})();
