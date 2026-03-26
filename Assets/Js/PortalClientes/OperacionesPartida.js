(function () {
  "use strict";

  // =========================================================
  // CONFIG
  // =========================================================
  const base =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const ENDPOINT_LISTAR = base + "PortalClientesPartidas/listar";
  const ENDPOINT_FACTURA = base + "PortalClientesPartidas/verFactura";
  const ENDPOINT_ENVIO = base + "PortalClientesPartidas/verEnvio";
  const ENDPOINT_IMAGENES_PRODUCTO =
    base + "PortalClientesPartidas/verImagenesProducto";

  // =========================================================
  // STATE
  // =========================================================
  const state = {
    page: 1,
    perPage: 15,
    total: 0,
    loading: false,
    filters: {
      buscar: "",
      estatus_envio: "",
      destino_id: "",
      fecha_inicio: "",
      fecha_fin: "",
      transportista_id: "",
    },
  };

  // =========================================================
  // REFS - KPI
  // =========================================================
  const kpiFacturas = document.getElementById("kpiPartidaFacturas");
  const kpiFerros = document.getElementById("kpiPartidaFerros");
  const kpiProductos = document.getElementById("kpiPartidaProductos");
  const kpiCajas = document.getElementById("kpiPartidaCajas");

  // =========================================================
  // REFS - FILTROS
  // =========================================================
  const inputSearch = document.getElementById("partidaSearch");
  const selEstatus = document.getElementById("partidaEstatus");
  const selDestino = document.getElementById("partidaDestino");
  const inpFechaIni = document.getElementById("partidaFechaIni");
  const inpFechaFin = document.getElementById("partidaFechaFin");
  const selTransportista = document.getElementById("partidaTransportista");

  const btnFiltrar = document.getElementById("btnPartidaFiltrar");
  const btnLimpiar = document.getElementById("btnPartidaLimpiar");
  const btnRefrescar = document.getElementById("btnRefrescarPartida");

  const filtrosActivosBar = document.getElementById("partidaFiltrosActivosBar");

  // =========================================================
  // REFS - TABLA / PAGINACIÓN
  // =========================================================
  const tbody = document.getElementById("tbOpsPartida");
  const pageSize = document.getElementById("partidaPageSize");
  const pagingLbl = document.getElementById("partidaPagingLbl");
  const paging = document.getElementById("partidaPaging");

  // =========================================================
  // REFS - MODAL FACTURA
  // =========================================================
  const modalFacturaEl = document.getElementById("modalPartidaFactura");
  const modalFactura = modalFacturaEl
    ? new bootstrap.Modal(modalFacturaEl)
    : null;

  const mf_numeroFactura = document.getElementById("mf_numeroFactura");
  const mf_proveedor = document.getElementById("mf_proveedor");
  const mf_palletsInv = document.getElementById("mf_palletsInv");
  const mf_fechaRecibido = document.getElementById("mf_fechaRecibido");
  const mf_revisionEstatus = document.getElementById("mf_revisionEstatus");
  const mf_tbodyProductos = document.getElementById("mf_tbodyProductos");
  const mf_gridFotosMercancia = document.getElementById(
    "mf_gridFotosMercancia",
  );

  // =========================================================
  // REFS - MODAL ENVÍO
  // =========================================================
  const modalEnvioEl = document.getElementById("modalPartidaEnvio");
  const modalEnvio = modalEnvioEl ? new bootstrap.Modal(modalEnvioEl) : null;

  const me_numeroFerro = document.getElementById("me_numeroFerro");
  const me_transportista = document.getElementById("me_transportista");
  const me_fechaEnvio = document.getElementById("me_fechaEnvio");
  const me_destino = document.getElementById("me_destino");
  const me_estatus = document.getElementById("me_estatus");
  const me_candado = document.getElementById("me_candado");
  const me_notas = document.getElementById("me_notas");
  const me_facturasWrap = document.getElementById("me_facturasWrap");
  const me_tbodyDetalle = document.getElementById("me_tbodyDetalle");
  const me_gridImagenesEnvio = document.getElementById("me_gridImagenesEnvio");

  // =========================================================
  // HELPERS GENERALES
  // =========================================================
  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function val(v, fallback) {
    return v == null || v === "" ? fallback || "--" : v;
  }

  function num(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
  }

  function formatDate(value) {
    if (!value) return "--";
    return String(value).slice(0, 10);
  }

  function nl2br(value) {
    return escapeHtml(value == null ? "" : value).replace(/\n/g, "<br>");
  }

  function getStatusClass(estatus) {
    const s = String(estatus || "")
      .trim()
      .toUpperCase();

    switch (s) {
      case "PROGRAMADO":
        return "is-programado";
      case "EN CAMINO":
        return "is-camino";
      case "ENTREGADO":
        return "is-entregado";
      case "DISPONIBLE EN DESTINO":
        return "is-destino";
      case "CANCELADO":
        return "is-cancelado";
      default:
        return "is-default";
    }
  }

  function renderStatusBadge(estatus) {
    const text = val(estatus, "Sin envío");
    const klass = getStatusClass(text);
    return `<span class="pn-badge-status ${klass}">${escapeHtml(text)}</span>`;
  }

  function xhrGet(url, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      let json = null;
      try {
        json = JSON.parse(xhr.responseText || "{}");
      } catch (e) {
        json = null;
      }

      callback(xhr.status, json, xhr.responseText);
    };
    xhr.send();
  }

  function buildQuery(params) {
    const qs = new URLSearchParams();

    Object.keys(params).forEach(function (key) {
      const value = params[key];
      if (value !== null && value !== undefined && String(value) !== "") {
        qs.append(key, value);
      }
    });

    return qs.toString();
  }

  function refreshFeather() {
    if (window.feather && typeof window.feather.replace === "function") {
      window.feather.replace();
    }
  }

  // =========================================================
  // FILTROS
  // =========================================================
  function syncFiltersFromUI() {
    state.filters.buscar = (inputSearch?.value || "").trim();
    state.filters.estatus_envio = selEstatus?.value || "";
    state.filters.destino_id = selDestino?.value || "";
    state.filters.fecha_inicio = inpFechaIni?.value || "";
    state.filters.fecha_fin = inpFechaFin?.value || "";
    state.filters.transportista_id = selTransportista?.value || "";
  }

  function resetFiltersUI() {
    if (inputSearch) inputSearch.value = "";
    if (selEstatus) selEstatus.value = "";
    if (selDestino) selDestino.value = "";
    if (inpFechaIni) inpFechaIni.value = "";
    if (inpFechaFin) inpFechaFin.value = "";
    if (selTransportista) selTransportista.value = "";
    if (pageSize) pageSize.value = "15";
  }

  function renderFilterChips() {
    if (!filtrosActivosBar) return;

    const chips = [];

    if (state.filters.buscar) {
      chips.push(
        `<span class="badge text-bg-light border">Búsqueda: ${escapeHtml(state.filters.buscar)}</span>`,
      );
    }
    if (state.filters.estatus_envio) {
      chips.push(
        `<span class="badge text-bg-light border">Estatus: ${escapeHtml(state.filters.estatus_envio)}</span>`,
      );
    }
    if (state.filters.destino_id && selDestino) {
      const txt =
        selDestino.options[selDestino.selectedIndex]?.text ||
        state.filters.destino_id;
      chips.push(
        `<span class="badge text-bg-light border">Destino: ${escapeHtml(txt)}</span>`,
      );
    }
    if (state.filters.fecha_inicio) {
      chips.push(
        `<span class="badge text-bg-light border">Desde: ${escapeHtml(state.filters.fecha_inicio)}</span>`,
      );
    }
    if (state.filters.fecha_fin) {
      chips.push(
        `<span class="badge text-bg-light border">Hasta: ${escapeHtml(state.filters.fecha_fin)}</span>`,
      );
    }
    if (state.filters.transportista_id && selTransportista) {
      const txt =
        selTransportista.options[selTransportista.selectedIndex]?.text ||
        state.filters.transportista_id;
      chips.push(
        `<span class="badge text-bg-light border">Transportista: ${escapeHtml(txt)}</span>`,
      );
    }

    filtrosActivosBar.innerHTML = chips.length
      ? chips.join(" ")
      : `<span class="text-muted small">Sin filtros activos.</span>`;
  }

  // =========================================================
  // TABLA
  // =========================================================
  function renderLoadingRow() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="13">
          <div class="pn-empty my-2">
            <div class="fw-semibold mb-1">Cargando información</div>
            <div>Espera un momento...</div>
          </div>
        </td>
      </tr>
    `;
  }

  function renderEmptyRow(message) {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="13">
          <div class="pn-empty my-2">
            <div class="fw-semibold mb-1">Sin resultados</div>
            <div>${escapeHtml(message || "No se encontró información para mostrar.")}</div>
          </div>
        </td>
      </tr>
    `;
  }
  function obtenerBadgeEstatus(estatus) {
    const valor = String(estatus || "")
      .trim()
      .toLowerCase();

    if (valor === "en camino") {
      return '<span class="badge bg-warning text-dark p-2">En camino</span>';
    }

    if (valor === "entregado") {
      return '<span class="badge bg-success text-white p-2">Entregado</span>';
    }

    if (valor === "programado") {
      return '<span class="badge bg-secondary text-white p-2">Programado</span>';
    }

    if (valor === "disponible en destino") {
      return '<span class="badge bg-primary text-white p-2">Disponible en destino</span>';
    }

    if (valor === "cancelado") {
      return '<span class="badge bg-danger text-white p-2">Cancelado</span>';
    }

    return (
      '<span class="badge bg-light text-dark border">' +
      (estatus || "—") +
      "</span>"
    );
  }
  function renderRows(rows) {
    if (!tbody) return;

    if (!Array.isArray(rows) || !rows.length) {
      renderEmptyRow(
        "No hay operaciones por partida con los filtros seleccionados.",
      );
      return;
    }

    const html = rows
      .map(function (row) {
        const idFactura = parseInt(row.id_factura || 0, 10);
        const idEnvio = parseInt(row.id_envio || 0, 10);

        const numeroFactura = val(row.numero_factura, "--");
        const palletsInv = num(row.pallets_inv);
        const proveedor = val(row.proveedor, "--");
        const fechaRecibido = formatDate(row.fecha_recibido);

        const numeroFerro = val(row.numero_ferro, "Sin asignar");
        const transportista = val(row.transportista_nombre, "Sin asignar");
        const fechaEnvio = formatDate(row.fecha_envio);
        const destino = val(row.destino_nombre, "Sin destino");
        const estatusEnvio =
          row.estatus_envio_texto || row.estatus_envio || "Sin envío";

        const productosEnviados = num(row.productos_enviados);
        const cajasEnviadas = num(row.cajas_enviadas);
        const notasDetalle = row.notas_detalle_resumen || "";
        const totalFotosMercancia = num(row.total_fotos_mercancia);
        const totalImagenesEnvio = num(row.total_imagenes_envio);

        return `
          <tr>
            <td>${escapeHtml(numeroFactura)}</td>
            <td>${escapeHtml(String(palletsInv))}</td>
            <td>${escapeHtml(proveedor)}</td>
            <td>${escapeHtml(fechaRecibido)}</td>
            <td>
              <button
                type="button"
                class="btn btn-sm btn-outline-success btn-icon-text js-ver-factura"
                data-factura-id="${idFactura}">
                <i data-feather="box"></i>
                Ver productos
              </button>
            </td>
            <td>${escapeHtml(numeroFerro)}</td>
            <td>${escapeHtml(transportista)}</td>
            <td>${escapeHtml(fechaEnvio)}</td>
            <td>${escapeHtml(destino)}</td>
            <td>${obtenerBadgeEstatus(estatusEnvio)}</td>
            <td>${escapeHtml(String(productosEnviados))}</td>
            <td>${escapeHtml(String(cajasEnviadas))}</td>
            <td>
              <div class="d-flex flex-wrap gap-2 mb-2">
                 
                
                ${
                  idEnvio > 0
                    ? `
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary js-ver-envio"
                    data-envio-id="${idEnvio}"><i data-feather="truck"></i>
                    Envío
                  </button>
                `
                    : ""
                }

         
 
              </div>

              <div class="small text-muted">
                ${notasDetalle ? nl2br(notasDetalle) : "Sin notas registradas."}
              </div>
            </td>
          </tr>
        `;
      })
      .join("");

    tbody.innerHTML = html;
    refreshFeather();
  }

  function renderPaging(total, page, perPage) {
    if (!paging || !pagingLbl) return;

    const totalPages =
      perPage >= 10000000 ? 1 : Math.max(1, Math.ceil(total / perPage));
    const start = total === 0 ? 0 : (page - 1) * perPage + 1;
    const end = perPage >= 10000000 ? total : Math.min(total, page * perPage);

    pagingLbl.textContent = `Mostrando ${start}–${end} de ${total}`;

    if (totalPages <= 1) {
      paging.innerHTML = "";
      return;
    }

    let html = "";

    html += `
      <li class="page-item ${page <= 1 ? "disabled" : ""}">
        <button class="page-link js-page" data-page="${page - 1}" type="button">«</button>
      </li>
    `;

    let from = Math.max(1, page - 2);
    let to = Math.min(totalPages, page + 2);

    if (page <= 3) to = Math.min(totalPages, 5);
    if (page >= totalPages - 2) from = Math.max(1, totalPages - 4);

    for (let i = from; i <= to; i++) {
      html += `
        <li class="page-item ${i === page ? "active" : ""}">
          <button class="page-link js-page" data-page="${i}" type="button">${i}</button>
        </li>
      `;
    }

    html += `
      <li class="page-item ${page >= totalPages ? "disabled" : ""}">
        <button class="page-link js-page" data-page="${page + 1}" type="button">»</button>
      </li>
    `;

    paging.innerHTML = html;
  }

  // =========================================================
  // LOAD LIST
  // =========================================================
  function loadListado() {
    if (state.loading) return;

    syncFiltersFromUI();
    renderFilterChips();
    renderLoadingRow();

    state.loading = true;

    const query = buildQuery({
      page: state.page,
      per_page: state.perPage,
      buscar: state.filters.buscar,
      estatus_envio: state.filters.estatus_envio,
      destino_id: state.filters.destino_id,
      fecha_inicio: state.filters.fecha_inicio,
      fecha_fin: state.filters.fecha_fin,
      transportista_id: state.filters.transportista_id,
    });

    xhrGet(ENDPOINT_LISTAR + "?" + query, function (status, json) {
      state.loading = false;

      if (status !== 200 || !json || json.ok !== true) {
        renderEmptyRow(
          (json && json.msg) || "No fue posible cargar la información.",
        );
        renderPaging(0, 1, state.perPage);
        return;
      }

      const rows = Array.isArray(json.rows) ? json.rows : [];
      state.total = parseInt(json.total || 0, 10) || 0;
      state.page = parseInt(json.page || state.page, 10) || 1;
      state.perPage =
        parseInt(json.per_page || state.perPage, 10) || state.perPage;

      renderRows(rows);
      renderPaging(state.total, state.page, state.perPage);
    });
  }

  // =========================================================
  // MODAL FACTURA
  // =========================================================
  function resetModalFactura() {
    if (mf_numeroFactura) mf_numeroFactura.textContent = "--";
    if (mf_proveedor) mf_proveedor.textContent = "--";
    if (mf_palletsInv) mf_palletsInv.textContent = "0";
    if (mf_fechaRecibido) mf_fechaRecibido.textContent = "--";
    if (mf_revisionEstatus) mf_revisionEstatus.textContent = "--";

    if (mf_tbodyProductos) {
      mf_tbodyProductos.innerHTML = `
        <tr>
          <td colspan="11" class="text-center text-muted py-4">
            Cargando productos...
          </td>
        </tr>
      `;
    }

    if (mf_gridFotosMercancia) {
      mf_gridFotosMercancia.innerHTML = `
        <div class="pn-empty">
          Cargando fotos...
        </div>
      `;
    }
  }

  function renderFacturaProductos(productos) {
    if (!mf_tbodyProductos) return;

    if (!Array.isArray(productos) || !productos.length) {
      mf_tbodyProductos.innerHTML = `
        <tr>
          <td colspan="11" class="text-center text-muted py-4">
            Sin productos para mostrar.
          </td>
        </tr>
      `;
      return;
    }

    mf_tbodyProductos.innerHTML = productos
      .map(function (p) {
        return `
          <tr>
            <td>${escapeHtml(val(p.descripcion, "--"))}</td>
            <td>${escapeHtml(val(p.item, "--"))}</td>
            <td>${escapeHtml(val(p.upc, "--"))}</td>
            <td>${escapeHtml(val(p.marca, "--"))}</td>
            <td>${escapeHtml(formatDate(p.expiracion))}</td>
            <td>${escapeHtml(String(num(p.inner_pack)))}</td>
            <td>${escapeHtml(String(num(p.case_pack)))}</td>
            <td>${escapeHtml(String(num(p.pallets_rcv)))}</td>
            <td>${escapeHtml(String(num(p.cajas)))}</td>
            <td>${escapeHtml(String(num(p.piezas)))}</td>
            <td>${escapeHtml(val(p.observaciones, "--"))}</td>
          </tr>
        `;
      })
      .join("");
  }

  function renderFacturaFotos(fotos) {
    if (!mf_gridFotosMercancia) return;

    if (!Array.isArray(fotos) || !fotos.length) {
      mf_gridFotosMercancia.innerHTML = `
        <div class="pn-empty">
          Sin fotos para mostrar.
        </div>
      `;
      return;
    }

    mf_gridFotosMercancia.innerHTML = fotos
      .map(function (foto) {
        const src = foto.ruta_archivo || "";
        const caption =
          foto.producto_descripcion ||
          foto.marca ||
          foto.item ||
          foto.nombre_archivo ||
          "Foto mercancía";

        return `
          <div class="pn-thumb" data-src="${escapeHtml(src)}">
            <img src="${escapeHtml(src)}" alt="${escapeHtml(caption)}">
            <div class="pn-thumb-caption">${escapeHtml(caption)}</div>
          </div>
        `;
      })
      .join("");
  }

  function openFactura(facturaId) {
    if (!facturaId || !modalFactura) return;

    resetModalFactura();
    modalFactura.show();

    const query = buildQuery({ factura_id: facturaId });

    xhrGet(ENDPOINT_FACTURA + "?" + query, function (status, json) {
      if (status !== 200 || !json || json.ok !== true) {
        if (mf_tbodyProductos) {
          mf_tbodyProductos.innerHTML = `
            <tr>
              <td colspan="11" class="text-center text-danger py-4">
                No fue posible cargar la factura.
              </td>
            </tr>
          `;
        }
        if (mf_gridFotosMercancia) {
          mf_gridFotosMercancia.innerHTML = `
            <div class="pn-empty">
              No fue posible cargar las fotos.
            </div>
          `;
        }
        return;
      }

      const factura = json.factura || {};
      const productos = Array.isArray(json.productos) ? json.productos : [];
      const fotos = Array.isArray(json.fotos) ? json.fotos : [];

      if (mf_numeroFactura)
        mf_numeroFactura.textContent = val(factura.numero_factura, "--");
      if (mf_proveedor) mf_proveedor.textContent = val(factura.proveedor, "--");
      if (mf_palletsInv)
        mf_palletsInv.textContent = String(num(factura.pallets_inv));
      if (mf_fechaRecibido)
        mf_fechaRecibido.textContent = formatDate(factura.fecha_recibido);
      if (mf_revisionEstatus) {
        mf_revisionEstatus.textContent =
          factura.revision_estatus_texto || val(factura.revision_estatus, "--");
      }

      renderFacturaProductos(productos);
      renderFacturaFotos(fotos);
    });
  }

  // =========================================================
  // MODAL ENVÍO
  // =========================================================
  function resetModalEnvio() {
    if (me_numeroFerro) me_numeroFerro.textContent = "--";
    if (me_transportista) me_transportista.textContent = "--";
    if (me_fechaEnvio) me_fechaEnvio.textContent = "--";
    if (me_destino) me_destino.textContent = "--";
    if (me_estatus) me_estatus.textContent = "--";
    if (me_candado) me_candado.textContent = "--";
    if (me_notas) me_notas.textContent = "Sin notas";

    if (me_facturasWrap) {
      me_facturasWrap.innerHTML = `<span class="factura-chip">Cargando...</span>`;
    }

    if (me_tbodyDetalle) {
      me_tbodyDetalle.innerHTML = `
        <tr>
          <td colspan="4" class="text-center text-muted py-4">
            Cargando detalle...
          </td>
        </tr>
      `;
    }

    if (me_gridImagenesEnvio) {
      me_gridImagenesEnvio.innerHTML = `
        <div class="pn-empty">
          Cargando imágenes...
        </div>
      `;
    }
  }

  function renderEnvioFacturas(facturas) {
    if (!me_facturasWrap) return;

    if (!Array.isArray(facturas) || !facturas.length) {
      me_facturasWrap.innerHTML = `<span class="factura-chip">Sin facturas</span>`;
      return;
    }

    me_facturasWrap.innerHTML = facturas
      .map(function (f) {
        const txt = `${val(f.numero_factura, "--")} · ${num(f.cajas)} cajas`;
        return `<span class="factura-chip p-4 badge  bg-success text-white fs-6">${escapeHtml(txt)}</span>`; //AQUI
      })
      .join("");
  }

  function renderEnvioDetalle(productos) {
    if (!me_tbodyDetalle) return;

    if (!Array.isArray(productos) || !productos.length) {
      me_tbodyDetalle.innerHTML = `
        <tr>
          <td colspan="4" class="text-center text-muted py-4">
            Sin detalle para mostrar.
          </td>
        </tr>
      `;
      return;
    }

    me_tbodyDetalle.innerHTML = productos
      .map(function (p) {
        return `
          <tr>
            <td>${escapeHtml(val(p.numero_factura, "--"))}</td>
            <td>${escapeHtml(val(p.descripcion, "--"))}</td>
            <td>${escapeHtml(String(num(p.cajas_enviadas)))}</td>
            <td>${escapeHtml(val(p.notas_detalle, "--"))}</td>
          </tr>
        `;
      })
      .join("");
  }

  function renderEnvioImagenes(imagenes) {
    if (!me_gridImagenesEnvio) return;

    if (!Array.isArray(imagenes) || !imagenes.length) {
      me_gridImagenesEnvio.innerHTML = `
        <div class="pn-empty">
          Sin imágenes para mostrar.
        </div>
      `;
      return;
    }

    me_gridImagenesEnvio.innerHTML = imagenes
      .map(function (img) {
        const src = img.ruta_archivo || "";
        const caption = img.nombre_archivo || "Imagen envío";

        return `
          <div class="pn-thumb" data-src="${escapeHtml(src)}">
            <img src="${escapeHtml(src)}" alt="${escapeHtml(caption)}">
            <div class="pn-thumb-caption">${escapeHtml(caption)}</div>
          </div>
        `;
      })
      .join("");
  }

  function openEnvio(envioId) {
    if (!envioId || !modalEnvio) return;

    resetModalEnvio();
    modalEnvio.show();

    const query = buildQuery({ envio_id: envioId });

    xhrGet(ENDPOINT_ENVIO + "?" + query, function (status, json) {
      if (status !== 200 || !json || json.ok !== true) {
        if (me_tbodyDetalle) {
          me_tbodyDetalle.innerHTML = `
            <tr>
              <td colspan="4" class="text-center text-danger py-4">
                No fue posible cargar el envío.
              </td>
            </tr>
          `;
        }
        if (me_gridImagenesEnvio) {
          me_gridImagenesEnvio.innerHTML = `
            <div class="pn-empty">
              No fue posible cargar las imágenes.
            </div>
          `;
        }
        return;
      }

      const envio = json.envio || {};
      const facturas = Array.isArray(json.facturas) ? json.facturas : [];
      const productos = Array.isArray(json.productos) ? json.productos : [];
      const imagenes = Array.isArray(json.imagenes) ? json.imagenes : [];

      if (me_numeroFerro)
        me_numeroFerro.textContent = val(envio.numero_ferro, "--");
      if (me_transportista)
        me_transportista.textContent = val(envio.transportista_nombre, "--");
      if (me_fechaEnvio)
        me_fechaEnvio.textContent = formatDate(envio.fecha_envio);
      if (me_destino) me_destino.textContent = val(envio.destino_nombre, "--");
      if (me_estatus)
        me_estatus.textContent =
          envio.estatus_envio_texto || val(envio.estatus_envio, "--");
      if (me_candado) me_candado.textContent = val(envio.candado, "--");
      if (me_notas) me_notas.textContent = val(envio.notas, "Sin notas");

      renderEnvioFacturas(facturas);
      renderEnvioDetalle(productos);
      renderEnvioImagenes(imagenes);
    });
  }

  let searchDebounceTimer = null;

  function reloadFromFilters() {
    state.page = 1;
    loadListado();
  }

  function debounceReload(delay) {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(function () {
      reloadFromFilters();
    }, delay || 350);
  }
  // =========================================================
  // EVENTS
  // =========================================================
  function bindEvents() {
    if (btnFiltrar) {
      btnFiltrar.addEventListener("click", function () {
        reloadFromFilters();
      });
    }

    if (btnLimpiar) {
      btnLimpiar.addEventListener("click", function () {
        resetFiltersUI();
        state.page = 1;
        state.perPage = 15;
        loadListado();
      });
    }

    if (btnRefrescar) {
      btnRefrescar.addEventListener("click", function () {
        loadListado();
      });
    }

    if (pageSize) {
      pageSize.addEventListener("change", function () {
        state.perPage = parseInt(this.value || "15", 10) || 15;
        state.page = 1;
        loadListado();
      });
    }

    if (inputSearch) {
      inputSearch.addEventListener("input", function () {
        debounceReload(400);
      });
    }
    if (inputSearch) {
      inputSearch.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          clearTimeout(searchDebounceTimer);
          reloadFromFilters();
        }
      });
    }

    if (paging) {
      paging.addEventListener("click", function (e) {
        const btn = e.target.closest(".js-page");
        if (!btn) return;

        const page = parseInt(btn.getAttribute("data-page") || "1", 10);
        if (!page || page < 1 || page === state.page) return;

        state.page = page;
        loadListado();
      });
    }

    if (tbody) {
      tbody.addEventListener("click", function (e) {
        const btnFactura = e.target.closest(".js-ver-factura");
        if (btnFactura) {
          const facturaId = parseInt(
            btnFactura.getAttribute("data-factura-id") || "0",
            10,
          );
          if (facturaId > 0) openFactura(facturaId);
          return;
        }

        const btnEnvio = e.target.closest(".js-ver-envio");
        if (btnEnvio) {
          const envioId = parseInt(
            btnEnvio.getAttribute("data-envio-id") || "0",
            10,
          );
          if (envioId > 0) openEnvio(envioId);
        }
      });
    }
    if (selEstatus) {
      selEstatus.addEventListener("change", reloadFromFilters);
    }

    if (selDestino) {
      selDestino.addEventListener("change", reloadFromFilters);
    }

    if (inpFechaIni) {
      inpFechaIni.addEventListener("change", reloadFromFilters);
    }

    if (inpFechaFin) {
      inpFechaFin.addEventListener("change", reloadFromFilters);
    }

    if (selTransportista) {
      selTransportista.addEventListener("change", reloadFromFilters);
    }
  }

  // =========================================================
  // INIT
  // =========================================================
  function initKpisFromDom() {
    // ya están pintados server-side, así que solo aseguramos formato
    if (kpiFacturas)
      kpiFacturas.textContent = String(num(kpiFacturas.textContent));
    if (kpiFerros) kpiFerros.textContent = String(num(kpiFerros.textContent));
    if (kpiProductos)
      kpiProductos.textContent = String(num(kpiProductos.textContent));
    if (kpiCajas) kpiCajas.textContent = String(num(kpiCajas.textContent));
  }

  function init() {
    if (pageSize) {
      state.perPage = parseInt(pageSize.value || "15", 10) || 15;
    }

    initKpisFromDom();
    renderFilterChips();
    bindEvents();
    loadListado();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
