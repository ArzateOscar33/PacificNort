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

  const IMG_MIN = 3;
  const IMG_MAX = 5;

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
  const candado = document.getElementById("partidas_transito_candado");

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

  // =========================
  // ELEMENTOS IMÁGENES
  // =========================
  const txtResumenProductos = document.getElementById(
    "partidas_transito_resumenProductos",
  );
  const txtResumenFacturas = document.getElementById(
    "partidas_transito_resumenFacturas",
  );
  const txtResumenCajas = document.getElementById(
    "partidas_transito_resumenCajas",
  );
  const txtResumenImagenes = document.getElementById(
    "partidas_transito_resumenImagenes",
  );

  const inputImagenes = document.getElementById("partidas_transito_imagenes");
  const previewGrid = document.getElementById("partidas_transito_previewGrid");
  const previewVacio = document.getElementById(
    "partidas_transito_previewVacio",
  );
  const errorImagenes = document.getElementById(
    "partidas_transito_errorImagenes",
  );
  const hiddenImagenesEliminadas = document.getElementById(
    "partidas_transito_imagenes_eliminadas",
  );
  const txtEvidenciasCount = document.getElementById(
    "partidas_transito_evidenciasCount",
  );
  const modalPreviewImagen = document.getElementById(
    "modalPartidasTransitoPreviewImagen",
  );
  const imgPreviewGrande = document.getElementById(
    "partidas_transito_previewImagenGrande",
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

  let imagenesExistentes = [];
  let imagenesNuevas = [];
  let imagenesEliminadas = [];

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
      return '<span class="badge bg-dark text-white p-2">Cancelado</span>';
    }
    if (valor === "detenido") {
      return '<span class="badge bg-danger text-white p-2">Detenido</span>';
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

  function construirUrlArchivo(ruta) {
    const valor = String(ruta || "").trim();
    if (!valor) return "";
    return base_url + valor.replace(/^\/+/, "");
  }

  function bytesTexto(bytes) {
    const b = Number(bytes || 0);
    if (b <= 0) return "";
    if (b < 1024) return b + " B";
    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + " KB";
    return (b / (1024 * 1024)).toFixed(1) + " MB";
  }

  function limpiarErrorImagenes() {
    if (!errorImagenes) return;
    errorImagenes.style.display = "none";
    errorImagenes.innerHTML = "";
  }

  function mostrarErrorImagenes(mensaje) {
    if (!errorImagenes) {
      mostrarAlerta("warning", mensaje);
      return;
    }
    errorImagenes.innerHTML = escapar(mensaje);
    errorImagenes.style.display = "block";
  }

  function contarImagenesActivasVisuales() {
    return imagenesExistentes.length + imagenesNuevas.length;
  }

  function sincronizarHiddenImagenesEliminadas() {
    if (!hiddenImagenesEliminadas) return;
    hiddenImagenesEliminadas.value = JSON.stringify(imagenesEliminadas);
  }

  function abrirPreviewImagen(src) {
    if (!imgPreviewGrande || !src) return;
    imgPreviewGrande.src = src;

    if (!modalPreviewImagen || !window.bootstrap) return;

    const instancia =
      window.bootstrap.Modal.getInstance(modalPreviewImagen) ||
      new window.bootstrap.Modal(modalPreviewImagen);

    instancia.show();
  }

  // =========================
  // REQUEST LISTADO
  // =========================
  function mostrarCargando() {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" class="text-center text-muted py-4">
          Cargando envíos...
        </td>
      </tr>
    `;
  }

  function mostrarVacio(mensaje) {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" class="text-center text-muted py-4">
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
        .filter(Boolean)
        .map(function (item) {
          return escapar(item);
        })
        .join("<br>");

      const idEnvio = Number(row.id_envio || 0);

      html += `
        <tr>
          <td class="text-center fw-semibold">${escapar(row.ferro || "—")}</td>
          <td>${escapar(row.clientes || "—")}</td>
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
        if (inpFerroTxt) {
          inpFerroTxt.value = this.getAttribute("data-numero") || "";
        }
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
  // IMÁGENES
  // =========================
  function renderImagenesEnvio() {
    if (!previewGrid || !previewVacio) {
      actualizarResumenModal();
      return;
    }

    const total = contarImagenesActivasVisuales();

    if (txtEvidenciasCount) {
      txtEvidenciasCount.textContent = total;
    }

    if (txtResumenImagenes) {
      txtResumenImagenes.textContent = total;
    }

    sincronizarHiddenImagenesEliminadas();

    if (total <= 0) {
      previewGrid.innerHTML = "";
      previewGrid.classList.add("d-none");
      previewVacio.classList.remove("d-none");
      actualizarResumenModal();
      return;
    }

    let html = "";

    imagenesExistentes.forEach(function (img, idx) {
      const src = construirUrlArchivo(img.ruta_archivo);
      html += `
        <div class="partidas_transito_preview_item">
          <button
            type="button"
            class="partidas_transito_preview_remove partidas-transito-btn-remove-img-existente"
            data-id-imagen="${Number(img.id_imagen || 0)}"
            title="Quitar imagen"
          >
            ×
          </button>

          <img
            src="${escapar(src)}"
            alt="${escapar(img.nombre_archivo || "Imagen")}"
            class="partidas-transito-preview-click"
            data-src="${escapar(src)}"
          >

          <div class="partidas_transito_preview_meta" title="${escapar(
            img.nombre_archivo || "Imagen",
          )}">
            ${escapar(img.nombre_archivo || "Imagen " + (idx + 1))}
          </div>
        </div>
      `;
    });

    imagenesNuevas.forEach(function (img, idx) {
      html += `
        <div class="partidas_transito_preview_item">
          <button
            type="button"
            class="partidas_transito_preview_remove partidas-transito-btn-remove-img-nueva"
            data-index-nueva="${idx}"
            title="Quitar imagen"
          >
            ×
          </button>

          <img
            src="${escapar(img.preview || "")}"
            alt="${escapar(img.nombre || "Imagen nueva")}"
            class="partidas-transito-preview-click"
            data-src="${escapar(img.preview || "")}"
          >

          <div class="partidas_transito_preview_meta" title="${escapar(
            img.nombre || "Imagen nueva",
          )}">
            ${escapar(img.nombre || "Nueva")}
          </div>
        </div>
      `;
    });

    previewGrid.innerHTML = html;
    previewGrid.classList.remove("d-none");
    previewVacio.classList.add("d-none");

    const btnsRemoveExistentes = previewGrid.querySelectorAll(
      ".partidas-transito-btn-remove-img-existente",
    );
    btnsRemoveExistentes.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const idImagen = Number(this.getAttribute("data-id-imagen") || 0);
        quitarImagenExistente(idImagen);
      });
    });

    const btnsRemoveNuevas = previewGrid.querySelectorAll(
      ".partidas-transito-btn-remove-img-nueva",
    );
    btnsRemoveNuevas.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const idx = Number(this.getAttribute("data-index-nueva") || -1);
        quitarImagenNueva(idx);
      });
    });

    const previewsClick = previewGrid.querySelectorAll(
      ".partidas-transito-preview-click",
    );
    previewsClick.forEach(function (img) {
      img.addEventListener("click", function () {
        abrirPreviewImagen(this.getAttribute("data-src") || "");
      });
    });

    actualizarResumenModal();
  }

  function manejarSeleccionImagenes(fileList) {
    limpiarErrorImagenes();

    if (!fileList || !fileList.length) return;

    const archivos = Array.prototype.slice.call(fileList);
    const totalActual = contarImagenesActivasVisuales();

    if (totalActual + archivos.length > IMG_MAX) {
      mostrarErrorImagenes(
        "Solo puedes tener un máximo de " + IMG_MAX + " imágenes en el envío.",
      );
      if (inputImagenes) inputImagenes.value = "";
      return;
    }

    const permitidos = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    const errores = [];
    const nuevasValidas = [];

    archivos.forEach(function (file, idx) {
      const mime = String(file.type || "").toLowerCase();

      if (!permitidos.includes(mime)) {
        errores.push(
          "La imagen #" +
            (idx + 1) +
            " no tiene un formato permitido (JPG, JPEG, PNG, WEBP).",
        );
        return;
      }

      nuevasValidas.push({
        file: file,
        nombre: file.name || "imagen",
        size: Number(file.size || 0),
        preview: URL.createObjectURL(file),
      });
    });

    if (errores.length) {
      mostrarErrorImagenes(errores.join(" "));
    }

    if (nuevasValidas.length) {
      imagenesNuevas = imagenesNuevas.concat(nuevasValidas);
      renderImagenesEnvio();
    }

    if (inputImagenes) inputImagenes.value = "";
  }

  function quitarImagenExistente(idImagen) {
    idImagen = Number(idImagen || 0);
    if (idImagen <= 0) return;

    const existe = imagenesExistentes.some(function (img) {
      return Number(img.id_imagen) === idImagen;
    });

    if (!existe) return;

    imagenesExistentes = imagenesExistentes.filter(function (img) {
      return Number(img.id_imagen) !== idImagen;
    });

    if (!imagenesEliminadas.includes(idImagen)) {
      imagenesEliminadas.push(idImagen);
    }

    limpiarErrorImagenes();
    renderImagenesEnvio();
  }

  function quitarImagenNueva(index) {
    index = Number(index);
    if (index < 0 || index >= imagenesNuevas.length) return;

    const item = imagenesNuevas[index];
    if (item && item.preview) {
      URL.revokeObjectURL(item.preview);
    }

    imagenesNuevas.splice(index, 1);
    limpiarErrorImagenes();
    renderImagenesEnvio();
  }

  function validarImagenesEdicion() {
    const total = contarImagenesActivasVisuales();

    if (total < IMG_MIN || total > IMG_MAX) {
      return (
        "El envío debe conservar entre " +
        IMG_MIN +
        " y " +
        IMG_MAX +
        " imágenes."
      );
    }

    return "";
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
    const totalImagenes = contarImagenesActivasVisuales();

    if (txtResumenProductos) {
      txtResumenProductos.textContent = totalProductos;
    }
    if (txtResumenFacturas) {
      txtResumenFacturas.textContent = totalFacturas;
    }
    if (txtResumenCajas) {
      txtResumenCajas.textContent = totalCajas;
    }
    if (txtResumenImagenes) {
      txtResumenImagenes.textContent = totalImagenes;
    }
    if (txtEvidenciasCount) {
      txtEvidenciasCount.textContent = totalImagenes;
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
        ? "Puedes actualizar el estatus, las notas y las evidencias fotográficas del envío"
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
    //setDisabled(inpFechaEnvio, esEdicion);
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

    if (inputImagenes) {
      inputImagenes.disabled = false;
      inputImagenes.readOnly = false;
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
    renderImagenesEnvio();
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

    imagenesExistentes = Array.isArray(envio.imagenes)
      ? envio.imagenes.map(function (img) {
          return {
            id_imagen: Number(img.id_imagen || 0),
            envio_id: Number(img.envio_id || 0),
            nombre_archivo: String(img.nombre_archivo || ""),
            ruta_archivo: String(img.ruta_archivo || ""),
            mime_type: String(img.mime_type || ""),
            tamano_bytes: Number(img.tamano_bytes || 0),
            orden_visual: Number(img.orden_visual || 0),
            fecha_subida: String(img.fecha_subida || ""),
          };
        })
      : [];

    imagenesNuevas.forEach(function (img) {
      if (img && img.preview) URL.revokeObjectURL(img.preview);
    });

    imagenesNuevas = [];
    imagenesEliminadas = [];

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
    if (candado) candado.value = envio.candado || "";

    if (inpFacturaId) inpFacturaId.value = "";
    if (inpBuscarFactura) inpBuscarFactura.value = "";
    if (inpFacturaProveedor) inpFacturaProveedor.value = "";
    if (inpFacturaCajas) inpFacturaCajas.value = "";
    if (inputImagenes) inputImagenes.value = "";
    if (hiddenImagenesEliminadas) hiddenImagenesEliminadas.value = "";

    limpiarErrorImagenes();
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

    imagenesNuevas.forEach(function (img) {
      if (img && img.preview) URL.revokeObjectURL(img.preview);
    });

    imagenesExistentes = [];
    imagenesNuevas = [];
    imagenesEliminadas = [];

    if (hiddenIdEnvio) hiddenIdEnvio.value = "";
    if (inpFerroTxt) inpFerroTxt.value = "";
    if (inpBuscarFactura) inpBuscarFactura.value = "";
    if (inpFacturaId) inpFacturaId.value = "";
    if (selTransportista) selTransportista.value = "";
    if (inpFechaEnvio) inpFechaEnvio.value = "";
    if (selDestino) selDestino.value = "";
    if (selEstatus) selEstatus.value = "En camino";
    if (txtNotas) txtNotas.value = "";
    if (candado) candado.value = "";
    if (inputImagenes) inputImagenes.value = "";
    if (hiddenImagenesEliminadas) hiddenImagenesEliminadas.value = "";

    limpiarErrorImagenes();
    ocultarSugerencias(boxSugFerro);
    ocultarSugerencias(boxSugFacturas);

    renderProductosFactura();
    renderDetalleSeleccionado();
    renderImagenesEnvio();
  }

  // =========================
  // EDICION
  // =========================
  function validarActualizacion() {
    const idEnvio = Number(hiddenIdEnvio ? hiddenIdEnvio.value : 0);
    const estatus = String(selEstatus ? selEstatus.value : "").trim();
    const fechaEnvio = String(inpFechaEnvio ? inpFechaEnvio.value : "").trim();

    if (idEnvio <= 0) {
      return "No se encontró el ID del envío.";
    }

    if (!fechaEnvio) {
      return "La fecha de envío es obligatoria.";
    }

    if (!estatus) {
      return "Debes seleccionar un estatus de envío.";
    }

    const errorImagenesLocal = validarImagenesEdicion();
    if (errorImagenesLocal) {
      return errorImagenesLocal;
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

    limpiarErrorImagenes();

    const error = validarActualizacion();
    if (error) {
      mostrarAlerta("warning", error);
      return;
    }

    const fd = new FormData();
    fd.append("id_envio", hiddenIdEnvio ? hiddenIdEnvio.value : "");
    fd.append("fecha_envio", inpFechaEnvio ? inpFechaEnvio.value : "");
    fd.append("estatus_envio", selEstatus ? selEstatus.value : "");
    fd.append("notas", txtNotas ? txtNotas.value : "");
    fd.append("candado", candado ? candado.value : "");
    fd.append("imagenes_eliminadas", JSON.stringify(imagenesEliminadas));

    imagenesNuevas.forEach(function (img) {
      if (img && img.file) {
        fd.append("imagenes[]", img.file);
      }
    });

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

      if (resp && resp.errores_imagenes && resp.errores_imagenes.length) {
        mostrarErrorImagenes(resp.errores_imagenes.join(" "));
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

    if (inputImagenes) {
      inputImagenes.addEventListener("change", function (e) {
        manejarSeleccionImagenes(e.target.files);
      });
    }

    document.addEventListener("click", function (e) {
      const btnNuevo = e.target.closest("[data-partidas-transito-nuevo]");
      if (btnNuevo) {
        prepararModoCrear();
      }
    });

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

    if (modalPreviewImagen) {
      modalPreviewImagen.addEventListener("hidden.bs.modal", function () {
        if (imgPreviewGrande) {
          imgPreviewGrande.src = "";
        }
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
    obtenerImagenesEstado: function () {
      return {
        existentes: imagenesExistentes.slice(),
        nuevas: imagenesNuevas.slice(),
        eliminadas: imagenesEliminadas.slice(),
      };
    },
  };

  // =========================
  // INIT
  // =========================
  prepararModoCrear();
  bindEventos();
  renderProductosFactura();
  renderDetalleSeleccionado();
  renderImagenesEnvio();
  cargarListado();

  function normalizarEstatus(valor) {
    const v = String(valor || "")
      .trim()
      .toLowerCase();

    if (v === "en camino") return "En camino";
    if (v === "entregado") return "Entregado";
    if (v === "programado") return "Programado";
    if (v === "disponible en destino") return "Disponible en destino";
    if (v === "cancelado") return "Cancelado";
    if (v === "detenido") return "Detenido";

    return "";
  }
})();
