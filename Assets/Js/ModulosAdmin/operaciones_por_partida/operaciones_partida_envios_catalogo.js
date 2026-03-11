(function () {
  "use strict";

  const URL_LISTAR = base_url + "Operaciones_por_partida_envios/listar";
  const URL_SUG_FERRO =
    base_url + "Operaciones_por_partida_envios/sugerirFerroCaja";
  const URL_SUG_FACTURAS =
    base_url + "Operaciones_por_partida_envios/sugerirFacturas";
  const URL_PRODUCTOS_FACTURA =
    base_url + "Operaciones_por_partida_envios/productosFactura";
  const URL_OBTENER = base_url + "Operaciones_por_partida_envios/obtener";
  const URL_ACTUALIZAR = base_url + "Operaciones_por_partida_envios/actualizar";

  // =========================
  // ELEMENTOS LISTADO
  // =========================
  const tbody = document.getElementById("partidas_transito_tbodyEnvios");
  const paginacion = document.getElementById("partidas_transito_paginacion");
  const meta = document.getElementById("partidas_transito_metaResumen");

  const filtroFerro = document.getElementById("partidas_transito_filtroFerro");
  const filtroFactura = document.getElementById(
    "partidas_transito_filtroFactura",
  );
  const filtroTransportista = document.getElementById(
    "partidas_transito_filtroTransportista",
  );
  const filtroEstatus = document.getElementById(
    "partidas_transito_filtroEstatus",
  );
  const perPage = document.getElementById("partidas_transito_perPage");
  const btnRefrescar = document.getElementById(
    "partidas_transito_btnRefrescar",
  );

  // =========================
  // ELEMENTOS MODAL / FORM
  // =========================
  const modalEl = document.getElementById("modalPartidasTransitoEnvio");
  const formEl = document.getElementById("partidas_transito_formEnvio");
  const btnGuardar = document.getElementById(
    "partidas_transito_btnGuardarEnvio",
  );

  const hiddenIdEnvio = document.getElementById("partidas_transito_id_envio");

  const selTransportista = document.getElementById(
    "partidas_transito_transportista_id",
  );
  const inpFechaEnvio = document.getElementById(
    "partidas_transito_fecha_envio",
  );
  const selDestino = document.getElementById("partidas_transito_destino_id");
  const selEstatus = document.getElementById("partidas_transito_estatus");
  const txtNotas = document.getElementById("partidas_transito_nota");

  // =========================
  // ELEMENTOS MODAL / CATALOGO
  // =========================
  const inpFerroTxt = document.getElementById("partidas_transito_fisico_txt");
  const inpFerroId = document.getElementById("partidas_transito_fisico_id");
  const boxSugFerro = document.getElementById("partidas_transito_fisico_sug");

  const inpBuscarFactura = document.getElementById(
    "partidas_transito_buscarFactura",
  );
  const inpFacturaId = document.getElementById("partidas_transito_factura_id");
  const boxSugFacturas = document.getElementById(
    "partidas_transito_sugerenciasFacturas",
  );

  const inpFacturaProveedor = document.getElementById(
    "partidas_transito_factura_proveedor",
  );
  const inpFacturaCajas = document.getElementById(
    "partidas_transito_factura_cajas",
  );

  const tbodyDetalle = document.getElementById(
    "partidas_transito_tbodyDetalleSeleccion",
  );
  const listaProductos = document.getElementById(
    "partidas_transito_listaProductos",
  );

  const modalTitleSpan = modalEl
    ? modalEl.querySelector(".modal-title span")
    : null;

  const modalSubtitulo = modalEl
    ? modalEl.querySelector(".modal-header .small")
    : null;

  // Opcionales, si existen en tu vista
  const txtResumenProductos = document.getElementById(
    "partidas_transito_resumenProductos",
  );
  const txtResumenFacturas = document.getElementById(
    "partidas_transito_resumenFacturas",
  );
  const txtResumenCajas = document.getElementById(
    "partidas_transito_resumenCajas",
  );

  // =========================
  // ESTADO
  // =========================
  let paginaActual = 1;

  let timerBusqueda = null;
  let timerSugFerro = null;
  let timerSugFactura = null;

  let detalleSeleccionado = [];
  let productosFacturaActual = [];
  let facturaActual = null;

  let modoFormulario = "crear"; // crear | editar
  let enviandoActualizacion = false;

  // =========================
  // HELPERS
  // =========================
  function escapar(texto) {
    return String(texto ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function featherRefresh() {
    if (window.feather) feather.replace();
  }

  function mostrarAlerta(tipo, mensaje) {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: tipo,
        text: mensaje,
        confirmButtonText: "Aceptar",
      });
      return;
    }
    alert(mensaje);
  }

  function obtenerBadgeEstatus(estatus) {
    const valor = String(estatus || "")
      .trim()
      .toLowerCase();

    if (valor === "en camino") {
      return '<span class="badge bg-warning text-dark">En camino</span>';
    }

    if (valor === "entregado") {
      return '<span class="badge bg-success">Entregado</span>';
    }

    if (valor === "programado") {
      return '<span class="badge bg-secondary">Programado</span>';
    }

    return (
      '<span class="badge bg-light text-dark border">' +
      escapar(estatus || "—") +
      "</span>"
    );
  }

  function xhrGetJson(url, onSuccess, onError) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const resp = JSON.parse(xhr.responseText);
          onSuccess(resp);
        } catch (e) {
          console.log("JSON inválido:", xhr.responseText);
          if (typeof onError === "function") {
            onError("La respuesta del servidor no es JSON válido.");
          }
        }
      } else {
        console.log("STATUS ERROR:", xhr.status);
        console.log("RESPUESTA ERROR:", xhr.responseText);
        if (typeof onError === "function") {
          onError("Ocurrió un error al consultar el servidor.");
        }
      }
    };

    xhr.onerror = function () {
      if (typeof onError === "function") {
        onError("Ocurrió un error de red.");
      }
    };

    xhr.send();
  }

  function ocultarSugerencias(el) {
    if (!el) return;
    el.innerHTML = "";
    el.classList.add("d-none");
  }

  function mostrarSugerencias(el, html) {
    if (!el) return;
    el.innerHTML = html;
    el.classList.remove("d-none");
  }

  function normalizarNumero(valor) {
    const n = Number(valor || 0);
    return Number.isFinite(n) ? n : 0;
  }

  function obtenerDetallePorProducto(productoId) {
    productoId = Number(productoId || 0);
    return detalleSeleccionado.find(function (item) {
      return Number(item.producto_id) === productoId;
    });
  }

  function construirBusquedaGeneral() {
    const ferro = (filtroFerro.value || "").trim();
    const factura = (filtroFactura.value || "").trim();
    return (ferro + " " + factura).trim();
  }

  function construirUrl() {
    const params = new URLSearchParams();

    params.append("page", paginaActual);
    params.append("per_page", perPage.value || "10");

    if (filtroTransportista.value) {
      params.append("transportista_id", filtroTransportista.value);
    }

    if (filtroEstatus.value) {
      params.append("estatus_envio", filtroEstatus.value);
    }

    const q = construirBusquedaGeneral();
    if (q) {
      params.append("q", q);
    }

    return URL_LISTAR + "?" + params.toString();
  }

  function setDisabled(el, disabled) {
    if (!el) return;
    el.disabled = !!disabled;
    el.readOnly = !!disabled;
  }

  function bloquearBotonGuardar(bloquear, texto) {
    if (!btnGuardar) return;

    btnGuardar.disabled = !!bloquear;

    if (bloquear) {
      if (!btnGuardar.dataset.htmlOriginal) {
        btnGuardar.dataset.htmlOriginal = btnGuardar.innerHTML;
      }
      btnGuardar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
        (texto || "Guardando...");
    } else if (btnGuardar.dataset.htmlOriginal) {
      btnGuardar.innerHTML = btnGuardar.dataset.htmlOriginal;
      featherRefresh();
    }
  }

  function abrirModalBootstrap() {
    if (!modalEl || !window.bootstrap) return;

    const instancia =
      window.bootstrap.Modal.getInstance(modalEl) ||
      new window.bootstrap.Modal(modalEl);

    instancia.show();
  }

  function cerrarModalBootstrap() {
    if (!modalEl || !window.bootstrap) return;

    const instancia =
      window.bootstrap.Modal.getInstance(modalEl) ||
      new window.bootstrap.Modal(modalEl);

    instancia.hide();
  }

  function setFechaHoySiEstaVacia() {
    if (!inpFechaEnvio) return;
    if ((inpFechaEnvio.value || "").trim() !== "") return;

    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, "0");
    const dd = String(hoy.getDate()).padStart(2, "0");

    inpFechaEnvio.value = yyyy + "-" + mm + "-" + dd;
  }

  // =========================
  // REQUEST LISTADO
  // =========================
  function mostrarCargando() {
    tbody.innerHTML = `
      <tr>
        <td colspan="10" class="text-center text-muted py-4">
          Cargando envíos...
        </td>
      </tr>
    `;
  }

  function mostrarVacio(mensaje) {
    tbody.innerHTML = `
      <tr>
        <td colspan="10" class="text-center text-muted py-4">
          ${escapar(mensaje)}
        </td>
      </tr>
    `;
    paginacion.innerHTML = "";
    meta.textContent = "Mostrando 0 a 0 de 0 registros";
  }

  function cargarListado() {
    mostrarCargando();

    const xhr = new XMLHttpRequest();
    xhr.open("GET", construirUrl(), true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const resp = JSON.parse(xhr.responseText);

          if (!resp.ok) {
            mostrarVacio(resp.msg || "No fue posible cargar los envíos.");
            return;
          }

          pintarTabla(resp.rows || []);
          pintarMeta(
            Number(resp.total || 0),
            Number(resp.page || 1),
            Number(resp.per_page || 10),
          );
          pintarPaginacion(
            Number(resp.page || 1),
            Number(resp.total_pages || 1),
          );
        } catch (e) {
          console.log("Respuesta inválida:", xhr.responseText);
          mostrarVacio("La respuesta del servidor no es JSON válido.");
        }
      } else {
        console.log("STATUS ERROR:", xhr.status);
        console.log("RESPUESTA ERROR:", xhr.responseText);
        mostrarVacio("Error al cargar los envíos.");
      }
    };

    xhr.send();
  }

  // =========================
  // TABLA
  // =========================
  function pintarTabla(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      mostrarVacio("No se encontraron envíos.");
      return;
    }

    let html = "";

    rows.forEach(function (row) {
      const productos = String(row.productos || "")
        .split(" | ")
        .map(function (item) {
          return escapar(item);
        })
        .join("<br>");

      const idEnvio = Number(row.id_envio || 0);

      html += `
        <tr>
          <td class="text-center fw-semibold">${escapar(row.ferro || "—")}</td>
          <td>${escapar(row.transportista || "—")}</td>
          <td class="text-center">${escapar(row.fecha_envio || "—")}</td>
          <td>${escapar(row.destino || "—")}</td>
          <td class="text-center">${obtenerBadgeEstatus(row.estatus_envio || "")}</td>
          <td>${escapar(row.facturas || "—")}</td>
          <td>${productos || "—"}</td>
          <td class="text-center fw-semibold">${Number(row.total_cajas || 0)}</td>
          <td>${escapar(row.notas || "")}</td>
          <td class="text-center">
            <button
              type="button"
              class="btn btn-sm btn-primary"
              data-partidas-transito-editar="1"
              data-id-envio="${idEnvio}"
              ${idEnvio > 0 ? "" : "disabled"}
            >
              Editar
            </button>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    featherRefresh();
  }

  // =========================
  // META
  // =========================
  function pintarMeta(total, page, per_page) {
    if (total <= 0) {
      meta.textContent = "Mostrando 0 a 0 de 0 registros";
      return;
    }

    const inicio = (page - 1) * per_page + 1;
    const fin = Math.min(page * per_page, total);

    meta.textContent = `Mostrando ${inicio} a ${fin} de ${total} registros`;
  }

  // =========================
  // PAGINACIÓN
  // =========================
  function pintarPaginacion(page, totalPages) {
    paginacion.innerHTML = "";

    if (totalPages <= 1) return;

    function crearBoton(texto, pagina, deshabilitado, activo) {
      const li = document.createElement("li");
      li.className = "page-item";

      if (deshabilitado) li.classList.add("disabled");
      if (activo) li.classList.add("active");

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "page-link";
      btn.textContent = texto;

      if (!deshabilitado && !activo) {
        btn.addEventListener("click", function () {
          paginaActual = pagina;
          cargarListado();
        });
      }

      li.appendChild(btn);
      paginacion.appendChild(li);
    }

    crearBoton("«", 1, page === 1, false);
    crearBoton("‹", page - 1, page === 1, false);

    for (let i = 1; i <= totalPages; i++) {
      crearBoton(String(i), i, false, i === page);
    }

    crearBoton("›", page + 1, page === totalPages, false);
    crearBoton("»", totalPages, page === totalPages, false);
  }

  // =========================
  // CATALOGO: FERRO / CAJA
  // =========================
  function limpiarFerroSeleccionado() {
    if (inpFerroId) inpFerroId.value = "";
  }

  function pintarSugerenciasFerro(rows) {
    if (!boxSugFerro) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      ocultarSugerencias(boxSugFerro);
      return;
    }

    let html = '<div class="list-group shadow-sm">';

    rows.forEach(function (item) {
      html += `
        <button
          type="button"
          class="list-group-item list-group-item-action partidas-transito-item-ferro"
          data-id="${Number(item.id || 0)}"
          data-label="${escapar(item.label || item.value || item.numero_ferro || "")}"
          data-numero="${escapar(item.numero_ferro || item.value || "")}"
        >
          <div class="fw-semibold">${escapar(item.numero_ferro || item.value || "—")}</div>
          <small class="text-muted">${escapar(item.label || "—")}</small>
        </button>
      `;
    });

    html += "</div>";
    mostrarSugerencias(boxSugFerro, html);

    const botones = boxSugFerro.querySelectorAll(
      ".partidas-transito-item-ferro",
    );
    botones.forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (inpFerroId) inpFerroId.value = this.getAttribute("data-id") || "";
        if (inpFerroTxt)
          inpFerroTxt.value = this.getAttribute("data-numero") || "";
        ocultarSugerencias(boxSugFerro);
      });
    });
  }

  function buscarSugerenciasFerro(term) {
    if (!inpFerroTxt || !boxSugFerro) return;
    if (modoFormulario === "editar") return;

    const texto = String(term || "").trim();

    if (texto.length < 2) {
      limpiarFerroSeleccionado();
      ocultarSugerencias(boxSugFerro);
      return;
    }

    const url =
      URL_SUG_FERRO + "?term=" + encodeURIComponent(texto) + "&limit=8";

    xhrGetJson(
      url,
      function (resp) {
        const rows = Array.isArray(resp) ? resp : resp.rows || [];
        pintarSugerenciasFerro(rows);
      },
      function () {
        ocultarSugerencias(boxSugFerro);
      },
    );
  }

  // =========================
  // CATALOGO: FACTURAS
  // =========================
  function limpiarFacturaSeleccionada() {
    if (inpFacturaId) inpFacturaId.value = "";
    if (inpFacturaProveedor) inpFacturaProveedor.value = "";
    if (inpFacturaCajas) inpFacturaCajas.value = "";
    facturaActual = null;
    productosFacturaActual = [];
    renderProductosFactura();
  }

  function pintarSugerenciasFacturas(rows) {
    if (!boxSugFacturas) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      ocultarSugerencias(boxSugFacturas);
      return;
    }

    let html = '<div class="list-group shadow-sm">';

    rows.forEach(function (item) {
      html += `
        <button
          type="button"
          class="list-group-item list-group-item-action partidas-transito-item-factura"
          data-id="${Number(item.id || 0)}"
          data-factura="${escapar(item.numero_factura || "")}"
          data-proveedor="${escapar(item.proveedor || "")}"
          data-cajas="${Number(item.cajas_disponibles || 0)}"
        >
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-semibold">${escapar(item.numero_factura || "—")}</div>
              <small class="text-muted">
                ${escapar(item.proveedor || "Sin proveedor")}
              </small>
            </div>
            <span class="badge bg-light text-dark border">
              ${Number(item.cajas_disponibles || 0)} cajas
            </span>
          </div>
        </button>
      `;
    });

    html += "</div>";
    mostrarSugerencias(boxSugFacturas, html);

    const botones = boxSugFacturas.querySelectorAll(
      ".partidas-transito-item-factura",
    );

    botones.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const facturaId = Number(this.getAttribute("data-id") || 0);
        const factura = this.getAttribute("data-factura") || "";
        const proveedor = this.getAttribute("data-proveedor") || "";
        const cajas = Number(this.getAttribute("data-cajas") || 0);

        if (inpFacturaId) inpFacturaId.value = facturaId;
        if (inpBuscarFactura) inpBuscarFactura.value = factura;
        if (inpFacturaProveedor) inpFacturaProveedor.value = proveedor;
        if (inpFacturaCajas) inpFacturaCajas.value = cajas;

        facturaActual = {
          id: facturaId,
          numero_factura: factura,
          proveedor: proveedor,
          cajas_disponibles: cajas,
        };

        ocultarSugerencias(boxSugFacturas);
        cargarProductosFactura(facturaId);
      });
    });
  }

  function buscarSugerenciasFacturas(term) {
    if (!inpBuscarFactura || !boxSugFacturas) return;
    if (modoFormulario === "editar") return;

    const texto = String(term || "").trim();

    if (texto.length < 2) {
      if (inpFacturaId && !inpFacturaId.value) {
        limpiarFacturaSeleccionada();
      }
      ocultarSugerencias(boxSugFacturas);
      return;
    }

    const url =
      URL_SUG_FACTURAS + "?term=" + encodeURIComponent(texto) + "&limit=8";

    xhrGetJson(
      url,
      function (resp) {
        const rows = Array.isArray(resp) ? resp : resp.rows || [];
        pintarSugerenciasFacturas(rows);
      },
      function () {
        ocultarSugerencias(boxSugFacturas);
      },
    );
  }

  function cargarProductosFactura(facturaId) {
    if (!listaProductos) return;
    if (modoFormulario === "editar") return;

    facturaId = Number(facturaId || 0);

    if (facturaId <= 0) {
      productosFacturaActual = [];
      renderProductosFactura();
      return;
    }

    listaProductos.innerHTML = `
      <div class="text-center text-muted py-4">
        Cargando productos...
      </div>
    `;

    const url =
      URL_PRODUCTOS_FACTURA + "?factura_id=" + encodeURIComponent(facturaId);

    xhrGetJson(
      url,
      function (resp) {
        productosFacturaActual = Array.isArray(resp.productos)
          ? resp.productos
          : [];
        renderProductosFactura();
      },
      function () {
        productosFacturaActual = [];
        renderProductosFactura();
      },
    );
  }

  // =========================
  // DETALLE SELECCIONADO
  // =========================
  function agregarOActualizarDetalle(producto, cajas) {
    if (modoFormulario === "editar") return;

    const productoId = Number(producto.id || 0);
    const facturaId = Number(
      producto.factura_id || (facturaActual ? facturaActual.id : 0),
    );

    const cajasMax = normalizarNumero(producto.cajas_restantes);
    cajas = Math.floor(normalizarNumero(cajas));

    if (productoId <= 0 || facturaId <= 0) return;

    if (cajas <= 0) {
      detalleSeleccionado = detalleSeleccionado.filter(function (item) {
        return Number(item.producto_id) !== productoId;
      });
      renderProductosFactura();
      renderDetalleSeleccionado();
      return;
    }

    if (cajasMax <= 0) {
      mostrarAlerta("warning", "Este producto ya no tiene cajas disponibles.");
      return;
    }

    if (cajas > cajasMax) {
      cajas = cajasMax;
    }

    const existente = detalleSeleccionado.find(function (item) {
      return Number(item.producto_id) === productoId;
    });

    if (existente) {
      existente.cajas_enviadas = cajas;
      existente.notas_detalle = existente.notas_detalle || "";
      existente.descripcion = producto.descripcion || "";
      existente.upc = producto.upc || "";
      existente.marca = producto.marca || "";
      existente.factura = facturaActual ? facturaActual.numero_factura : "";
      existente.cajas_disponibles = cajasMax;
    } else {
      detalleSeleccionado.push({
        factura_id: facturaId,
        producto_id: productoId,
        cajas_enviadas: cajas,
        notas_detalle: "",
        descripcion: producto.descripcion || "",
        upc: producto.upc || "",
        marca: producto.marca || "",
        factura: facturaActual ? facturaActual.numero_factura : "",
        cajas_disponibles: cajasMax,
      });
    }

    renderProductosFactura();
    renderDetalleSeleccionado();
  }

  function quitarDetalle(productoId) {
    if (modoFormulario === "editar") return;

    productoId = Number(productoId || 0);

    detalleSeleccionado = detalleSeleccionado.filter(function (item) {
      return Number(item.producto_id) !== productoId;
    });

    renderProductosFactura();
    renderDetalleSeleccionado();
  }

  function actualizarNotasDetalle(productoId, notas) {
    if (modoFormulario === "editar") return;

    productoId = Number(productoId || 0);

    const item = detalleSeleccionado.find(function (row) {
      return Number(row.producto_id) === productoId;
    });

    if (item) {
      item.notas_detalle = String(notas || "");
    }
  }

  // =========================
  // RENDER PRODUCTOS FACTURA
  // =========================
  function renderProductosFactura() {
    if (!listaProductos) return;

    if (modoFormulario === "editar") {
      listaProductos.innerHTML = `
        <div class="text-center text-muted py-4">
          En edición no se modifican facturas ni productos del envío.
        </div>
      `;
      featherRefresh();
      return;
    }

    if (
      !Array.isArray(productosFacturaActual) ||
      productosFacturaActual.length === 0
    ) {
      listaProductos.innerHTML = `
        <div class="text-center text-muted py-4">
          Selecciona una factura para ver sus productos.
        </div>
      `;
      featherRefresh();
      return;
    }

    let html = "";

    productosFacturaActual.forEach(function (producto) {
      const productoId = Number(producto.id || 0);
      const cajasDisponibles = normalizarNumero(producto.cajas_restantes);
      const seleccionado = obtenerDetallePorProducto(productoId);
      const valorSeleccionado = seleccionado
        ? Number(seleccionado.cajas_enviadas || 0)
        : 0;

      html += `
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div class="flex-grow-1">
                <div class="fw-semibold mb-1">
                  ${escapar(producto.descripcion || "Sin descripción")}
                </div>
                <div class="small text-muted">
                  UPC: ${escapar(producto.upc || "—")} ·
                  Marca: ${escapar(producto.marca || "—")}
                </div>
              </div>
              <div class="text-end">
                <div class="badge bg-light text-dark border">
                  Disponibles: ${cajasDisponibles}
                </div>
              </div>
            </div>

            <div class="row g-2 align-items-end mt-2">
              <div class="col-md-4">
                <label class="form-label small mb-1">Cajas a enviar</label>
                <input
                  type="number"
                  min="0"
                  max="${cajasDisponibles}"
                  step="1"
                  class="form-control partidas-transito-input-cajas"
                  data-producto-id="${productoId}"
                  value="${valorSeleccionado}"
                >
              </div>
              <div class="col-md-4">
                <button
                  type="button"
                  class="btn btn-primary w-100 partidas-transito-btn-agregar"
                  data-producto-id="${productoId}"
                >
                  ${valorSeleccionado > 0 ? "Actualizar" : "Agregar"}
                </button>
              </div>
              <div class="col-md-4">
                <button
                  type="button"
                  class="btn btn-outline-danger w-100 partidas-transito-btn-quitar"
                  data-producto-id="${productoId}"
                  ${valorSeleccionado > 0 ? "" : "disabled"}
                >
                  Quitar
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    listaProductos.innerHTML = html;

    const btnsAgregar = listaProductos.querySelectorAll(
      ".partidas-transito-btn-agregar",
    );
    btnsAgregar.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const productoId = Number(this.getAttribute("data-producto-id") || 0);
        const input = listaProductos.querySelector(
          '.partidas-transito-input-cajas[data-producto-id="' +
            productoId +
            '"]',
        );

        const cajas = input ? Number(input.value || 0) : 0;
        const producto = productosFacturaActual.find(function (p) {
          return Number(p.id) === productoId;
        });

        if (!producto) return;
        agregarOActualizarDetalle(producto, cajas);
      });
    });

    const btnsQuitar = listaProductos.querySelectorAll(
      ".partidas-transito-btn-quitar",
    );
    btnsQuitar.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const productoId = Number(this.getAttribute("data-producto-id") || 0);
        quitarDetalle(productoId);
      });
    });

    featherRefresh();
  }

  // =========================
  // RENDER DETALLE TABLA
  // =========================
  function renderDetalleSeleccionado() {
    if (!tbodyDetalle) return;

    if (
      !Array.isArray(detalleSeleccionado) ||
      detalleSeleccionado.length === 0
    ) {
      tbodyDetalle.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted py-4">
          ${
            modoFormulario === "editar"
              ? "Este envío no tiene detalle registrado."
              : "No has agregado productos al envío."
          }
        </td>
      </tr>
    `;
      actualizarResumenModal();
      featherRefresh();
      return;
    }

    let html = "";

    detalleSeleccionado.forEach(function (item) {
      if (modoFormulario === "editar") {
        html += `
        <tr>
          <td>${escapar(item.factura || "—")}</td>
          <td>${escapar(item.descripcion || "—")}</td>
          <td>${escapar(item.upc || "—")}</td>
          <td class="text-center fw-semibold">${Number(item.cajas_enviadas || 0)}</td>
          <td>
            <input
              type="text"
              class="form-control form-control-sm"
              value="${escapar(item.notas_detalle || "")}"
              readonly
              disabled
            >
          </td>
          <td class="text-center text-muted">—</td>
        </tr>
      `;
        return;
      }

      html += `
      <tr>
        <td>${escapar(item.factura || "—")}</td>
        <td>${escapar(item.descripcion || "—")}</td>
        <td>${escapar(item.upc || "—")}</td>
        <td class="text-center fw-semibold">${Number(item.cajas_enviadas || 0)}</td>
        <td>
          <input
            type="text"
            class="form-control form-control-sm partidas-transito-input-nota"
            data-producto-id="${Number(item.producto_id || 0)}"
            value="${escapar(item.notas_detalle || "")}"
            placeholder="Notas"
          >
        </td>
        <td class="text-center">
          <button
            type="button"
            class="btn btn-outline-danger btn-sm partidas-transito-btn-eliminar-detalle"
            data-producto-id="${Number(item.producto_id || 0)}"
          >
            <i data-feather="trash-2"></i>
          </button>
        </td>
      </tr>
    `;
    });

    tbodyDetalle.innerHTML = html;

    if (modoFormulario !== "editar") {
      const btnsEliminar = tbodyDetalle.querySelectorAll(
        ".partidas-transito-btn-eliminar-detalle",
      );
      btnsEliminar.forEach(function (btn) {
        btn.addEventListener("click", function () {
          const productoId = Number(this.getAttribute("data-producto-id") || 0);
          quitarDetalle(productoId);
        });
      });

      const inputsNotas = tbodyDetalle.querySelectorAll(
        ".partidas-transito-input-nota",
      );
      inputsNotas.forEach(function (input) {
        input.addEventListener("input", function () {
          const productoId = Number(this.getAttribute("data-producto-id") || 0);
          actualizarNotasDetalle(productoId, this.value || "");
        });
      });
    }

    featherRefresh();
    actualizarResumenModal();
  }

  // =========================
  // RESUMEN MODAL
  // =========================
  function actualizarResumenModal() {
    const totalProductos = detalleSeleccionado.length;
    const totalCajas = detalleSeleccionado.reduce(function (acc, item) {
      return acc + Number(item.cajas_enviadas || 0);
    }, 0);

    const facturasUnicas = {};
    detalleSeleccionado.forEach(function (item) {
      const key = String(item.factura_id || "");
      if (key) facturasUnicas[key] = true;
    });

    const totalFacturas = Object.keys(facturasUnicas).length;

    if (txtResumenProductos) {
      txtResumenProductos.textContent = totalProductos;
    }
    if (txtResumenFacturas) {
      txtResumenFacturas.textContent = totalFacturas;
    }
    if (txtResumenCajas) {
      txtResumenCajas.textContent = totalCajas;
    }
  }

  // =========================
  // MODO FORMULARIO
  // =========================
  function actualizarUiModoFormulario() {
    const esEdicion = modoFormulario === "editar";

    if (modalTitleSpan) {
      modalTitleSpan.textContent = esEdicion
        ? "Editar envío"
        : "Registrar nuevo envío";
    }

    if (modalSubtitulo) {
      modalSubtitulo.textContent = esEdicion
        ? "Solo puedes actualizar el estatus y las notas del envío"
        : "Captura encabezado del envío y agrega productos desde una factura";
    }

    if (btnGuardar) {
      btnGuardar.innerHTML = esEdicion
        ? '<i data-feather="save" class="me-1"></i> Actualizar envío'
        : '<i data-feather="save" class="me-1"></i> Guardar envío';
      btnGuardar.dataset.htmlOriginal = btnGuardar.innerHTML;
    }

    setDisabled(inpFerroTxt, esEdicion);
    setDisabled(selTransportista, esEdicion);
    setDisabled(inpFechaEnvio, esEdicion);
    setDisabled(selDestino, esEdicion);

    if (inpFerroId) inpFerroId.disabled = esEdicion;
    if (inpFacturaId) inpFacturaId.disabled = esEdicion;

    setDisabled(inpBuscarFactura, esEdicion);
    setDisabled(inpFacturaProveedor, true);
    setDisabled(inpFacturaCajas, true);

    if (listaProductos) {
      listaProductos.style.pointerEvents = esEdicion ? "none" : "";
      listaProductos.style.opacity = esEdicion ? "0.65" : "";
    }

    if (tbodyDetalle) {
      tbodyDetalle.style.pointerEvents = esEdicion ? "none" : "";
      tbodyDetalle.style.opacity = esEdicion ? "0.65" : "";
    }

    ocultarSugerencias(boxSugFerro);
    ocultarSugerencias(boxSugFacturas);

    featherRefresh();
  }

  function prepararModoCrear() {
    modoFormulario = "crear";
    limpiarModalEnvio();
    setFechaHoySiEstaVacia();
    actualizarUiModoFormulario();
  }

  function prepararModoEditar() {
    modoFormulario = "editar";
    actualizarUiModoFormulario();
    renderProductosFactura();
    renderDetalleSeleccionado();
    actualizarResumenModal();
  }

  function poblarFormularioEdicion(envio) {
    if (!envio || typeof envio !== "object") {
      throw new Error("Datos de envío inválidos.");
    }

    const detalleApi = Array.isArray(envio.detalle) ? envio.detalle : [];

    detalleSeleccionado = detalleApi.map(function (item) {
      return {
        id_envio_detalle: Number(item.id_envio_detalle || 0),
        envio_id: Number(item.envio_id || 0),
        factura_id: Number(item.factura_id || 0),
        producto_id: Number(item.producto_id || 0),
        cajas_enviadas: Number(item.cajas_enviadas || 0),
        notas_detalle: String(item.notas_detalle || ""),
        descripcion: String(item.descripcion || ""),
        upc: String(item.upc || ""),
        marca: String(item.marca || ""),
        factura: String(item.numero_factura || ""),
        cajas_disponibles: 0,
      };
    });

    productosFacturaActual = [];
    facturaActual = null;

    if (hiddenIdEnvio) hiddenIdEnvio.value = envio.id_envio || "";
    if (inpFerroId) inpFerroId.value = envio.contenedor_fisico_id || "";
    if (inpFerroTxt) inpFerroTxt.value = envio.ferro || "";
    if (selTransportista) selTransportista.value = envio.transportista_id || "";
    if (inpFechaEnvio) inpFechaEnvio.value = envio.fecha_envio || "";
    if (selDestino) selDestino.value = envio.destino_ciudad_id || "";
    if (selEstatus) selEstatus.value = normalizarEstatus(envio.estatus_envio);
    if (txtNotas) txtNotas.value = envio.notas || "";

    if (inpFacturaId) inpFacturaId.value = "";
    if (inpBuscarFactura) inpBuscarFactura.value = "";
    if (inpFacturaProveedor) inpFacturaProveedor.value = "";
    if (inpFacturaCajas) inpFacturaCajas.value = "";

    prepararModoEditar();
  }

  // =========================
  // LIMPIEZA
  // =========================
  function limpiarModalEnvio() {
    limpiarFerroSeleccionado();
    limpiarFacturaSeleccionada();

    detalleSeleccionado = [];
    productosFacturaActual = [];
    facturaActual = null;

    if (hiddenIdEnvio) hiddenIdEnvio.value = "";
    if (inpFerroTxt) inpFerroTxt.value = "";
    if (inpBuscarFactura) inpBuscarFactura.value = "";
    if (inpFacturaId) inpFacturaId.value = "";
    if (selTransportista) selTransportista.value = "";
    if (inpFechaEnvio) inpFechaEnvio.value = "";
    if (selDestino) selDestino.value = "";
    if (selEstatus) selEstatus.value = "En camino";
    if (txtNotas) txtNotas.value = "";

    ocultarSugerencias(boxSugFerro);
    ocultarSugerencias(boxSugFacturas);

    renderProductosFactura();
    renderDetalleSeleccionado();
  }

  // =========================
  // EDICION
  // =========================
  function validarActualizacion() {
    const idEnvio = Number(hiddenIdEnvio ? hiddenIdEnvio.value : 0);
    const estatus = String(selEstatus ? selEstatus.value : "").trim();

    if (idEnvio <= 0) {
      return "No se encontró el ID del envío.";
    }

    if (!estatus) {
      return "Debes seleccionar un estatus de envío.";
    }

    return "";
  }

  function abrirEditarEnvio(idEnvio) {
    idEnvio = Number(idEnvio || 0);

    if (idEnvio <= 0) {
      mostrarAlerta("warning", "ID de envío inválido.");
      return;
    }

    bloquearBotonGuardar(true, "Cargando...");
    modoFormulario = "editar";
    actualizarUiModoFormulario();

    xhrGetJson(
      URL_OBTENER + "?id_envio=" + encodeURIComponent(idEnvio),
      function (resp) {
        bloquearBotonGuardar(false);

        if (!resp || !resp.ok || !resp.envio) {
          mostrarAlerta(
            "error",
            (resp && resp.msg) || "No fue posible obtener el envío.",
          );
          prepararModoCrear();
          return;
        }

        try {
          poblarFormularioEdicion(resp.envio);
          abrirModalBootstrap();
        } catch (err) {
          console.error(err);
          mostrarAlerta(
            "error",
            "No fue posible cargar la información del envío.",
          );
          prepararModoCrear();
        }
      },
      function (mensaje) {
        bloquearBotonGuardar(false);
        mostrarAlerta("error", mensaje || "Error al obtener el envío.");
        prepararModoCrear();
      },
    );
  }

  function actualizarEnvio() {
    if (modoFormulario !== "editar") return;
    if (enviandoActualizacion) return;

    const error = validarActualizacion();
    if (error) {
      mostrarAlerta("warning", error);
      return;
    }

    const fd = new FormData();
    fd.append("id_envio", hiddenIdEnvio ? hiddenIdEnvio.value : "");
    fd.append("estatus_envio", selEstatus ? selEstatus.value : "");
    fd.append("notas", txtNotas ? txtNotas.value : "");

    const xhr = new XMLHttpRequest();
    xhr.open("POST", URL_ACTUALIZAR, true);

    enviandoActualizacion = true;
    bloquearBotonGuardar(true, "Actualizando...");

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      enviandoActualizacion = false;
      bloquearBotonGuardar(false);

      let resp = null;
      try {
        resp = JSON.parse(xhr.responseText);
      } catch (e) {
        console.error("Respuesta inválida actualizar:", xhr.responseText);
        mostrarAlerta("error", "La respuesta del servidor no es JSON válido.");
        return;
      }

      if (xhr.status >= 200 && xhr.status < 300 && resp && resp.ok) {
        mostrarAlerta(
          "success",
          resp.msg || "Envío actualizado correctamente.",
        );
        cerrarModalBootstrap();
        cargarListado();
        return;
      }

      mostrarAlerta(
        "error",
        (resp && resp.msg) || "No fue posible actualizar el envío.",
      );
    };

    xhr.onerror = function () {
      enviandoActualizacion = false;
      bloquearBotonGuardar(false);
      mostrarAlerta("error", "Ocurrió un error de red al actualizar el envío.");
    };

    xhr.send(fd);
  }

  // =========================
  // FILTROS LISTADO
  // =========================
  function recargarDesdePrimeraPagina() {
    paginaActual = 1;
    cargarListado();
  }

  // =========================
  // EVENTOS
  // =========================
  function bindEventos() {
    // filtros listado
    if (filtroFerro) {
      filtroFerro.addEventListener("input", function () {
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(recargarDesdePrimeraPagina, 300);
      });
    }

    if (filtroFactura) {
      filtroFactura.addEventListener("input", function () {
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(recargarDesdePrimeraPagina, 300);
      });
    }

    if (filtroTransportista) {
      filtroTransportista.addEventListener(
        "change",
        recargarDesdePrimeraPagina,
      );
    }

    if (filtroEstatus) {
      filtroEstatus.addEventListener("change", recargarDesdePrimeraPagina);
    }

    if (perPage) {
      perPage.addEventListener("change", recargarDesdePrimeraPagina);
    }

    if (btnRefrescar) {
      btnRefrescar.addEventListener("click", cargarListado);
    }

    // autocomplete ferro
    if (inpFerroTxt) {
      inpFerroTxt.addEventListener("input", function () {
        if (modoFormulario === "editar") return;

        if (inpFerroId) inpFerroId.value = "";

        clearTimeout(timerSugFerro);
        timerSugFerro = setTimeout(function () {
          buscarSugerenciasFerro(inpFerroTxt.value || "");
        }, 250);
      });

      inpFerroTxt.addEventListener("blur", function () {
        setTimeout(function () {
          ocultarSugerencias(boxSugFerro);
        }, 180);
      });
    }

    // autocomplete factura
    if (inpBuscarFactura) {
      inpBuscarFactura.addEventListener("input", function () {
        if (modoFormulario === "editar") return;

        if (inpFacturaId) inpFacturaId.value = "";

        clearTimeout(timerSugFactura);
        timerSugFactura = setTimeout(function () {
          buscarSugerenciasFacturas(inpBuscarFactura.value || "");
        }, 250);
      });

      inpBuscarFactura.addEventListener("blur", function () {
        setTimeout(function () {
          ocultarSugerencias(boxSugFacturas);
        }, 180);
      });
    }

    // nuevo
    document.addEventListener("click", function (e) {
      const btnNuevo = e.target.closest("[data-partidas-transito-nuevo]");
      if (btnNuevo) {
        prepararModoCrear();
      }
    });

    // editar
    document.addEventListener("click", function (e) {
      const btnEditar = e.target.closest("[data-partidas-transito-editar]");
      if (!btnEditar) return;

      e.preventDefault();

      const idEnvio =
        btnEditar.getAttribute("data-id-envio") ||
        btnEditar.dataset.idEnvio ||
        btnEditar.getAttribute("data-envio-id") ||
        "0";

      abrirEditarEnvio(idEnvio);
    });

    // submit del modal en edición
    if (btnGuardar) {
      btnGuardar.addEventListener("click", function () {
        if (modoFormulario === "editar") {
          actualizarEnvio();
        }
      });
    }

    if (formEl) {
      formEl.addEventListener("submit", function (e) {
        if (modoFormulario === "editar") {
          e.preventDefault();
          actualizarEnvio();
        }
      });
    }

    if (modalEl) {
      modalEl.addEventListener("shown.bs.modal", function () {
        if (modoFormulario === "crear") {
          setFechaHoySiEstaVacia();
        }
      });

      modalEl.addEventListener("hidden.bs.modal", function () {
        prepararModoCrear();
      });
    }
  }

  // =========================
  // API GLOBAL
  // =========================
  window.partidasTransitoListado = {
    recargar: function () {
      cargarListado();
    },
    limpiarModalEnvio: function () {
      limpiarModalEnvio();
    },
    obtenerDetalle: function () {
      return detalleSeleccionado.slice();
    },
    abrirEditar: function (idEnvio) {
      abrirEditarEnvio(idEnvio);
    },
    obtenerModoFormulario: function () {
      return modoFormulario;
    },
  };

  // =========================
  // INIT
  // =========================
  prepararModoCrear();
  bindEventos();
  renderProductosFactura();
  renderDetalleSeleccionado();
  cargarListado();

  function normalizarEstatus(valor) {
    const v = String(valor || "")
      .trim()
      .toLowerCase();

    if (v === "en camino") return "En camino";
    if (v === "entregado") return "Entregado";
    if (v === "programado") return "Programado";

    return "";
  }
})();
