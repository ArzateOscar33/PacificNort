(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const URL_REGISTRAR = base_url + "Operaciones_por_partida_envios/registrar";

  // =========================
  // ELEMENTOS MODAL REGISTRO
  // =========================
  const modalEl = document.getElementById("modalPartidasTransitoEnvio");
  const formEl = document.getElementById("partidas_transito_formEnvio");
  const btnGuardar = document.getElementById(
    "partidas_transito_btnGuardarEnvio",
  );

  const inpFisicoId = document.getElementById("partidas_transito_fisico_id");
  const inpFisicoTxt = document.getElementById("partidas_transito_fisico_txt");
  const selTransportista = document.getElementById(
    "partidas_transito_transportista_id",
  );
  const inpFechaEnvio = document.getElementById(
    "partidas_transito_fecha_envio",
  );
  const selDestino = document.getElementById("partidas_transito_destino_id");
  const selEstatus = document.getElementById("partidas_transito_estatus");
  const txtNotas = document.getElementById("partidas_transito_nota");
  const inpCandado = document.getElementById("partidas_transito_candado");

  // INPUT DE IMÁGENES
  const inpImagenes =
    document.getElementById("partidas_transito_imagenes") ||
    document.getElementById("partidas_transito_imagenes_envio") ||
    document.querySelector('input[name="imagenes[]"]') ||
    document.querySelector('input[name="imagenes"]');

  const IMG_MIN = 3;
  const IMG_MAX = 5;

  let enviando = false;

  // =========================
  // HELPERS
  // =========================
  function trimValue(el) {
    return el ? String(el.value || "").trim() : "";
  }

  function intValue(el) {
    const n = parseInt(trimValue(el), 10);
    return Number.isFinite(n) ? n : 0;
  }

  function getDetalleActual() {
    if (
      window.partidasTransitoListado &&
      typeof window.partidasTransitoListado.obtenerDetalle === "function"
    ) {
      const detalle = window.partidasTransitoListado.obtenerDetalle();
      return Array.isArray(detalle) ? detalle : [];
    }
    return [];
  }

  function getImagenesSeleccionadas() {
    if (
      window.partidasTransitoListado &&
      typeof window.partidasTransitoListado.obtenerImagenesEstado === "function"
    ) {
      const estado = window.partidasTransitoListado.obtenerImagenesEstado();

      if (estado && Array.isArray(estado.nuevas)) {
        return estado.nuevas
          .map(function (img) {
            return img && img.file ? img.file : null;
          })
          .filter(function (file) {
            return !!file;
          });
      }
    }

    if (!inpImagenes || !inpImagenes.files) return [];
    return Array.from(inpImagenes.files);
  }

  function mostrarAlerta(tipo, mensaje) {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: tipo,
        html: String(mensaje || "").replace(/\n/g, "<br>"),
        confirmButtonText: "Aceptar",
      });
      return;
    }

    alert(mensaje);
  }

  function bloquearBotonGuardar(bloquear) {
    if (!btnGuardar) return;

    btnGuardar.disabled = !!bloquear;

    if (bloquear) {
      if (!btnGuardar.dataset.textoOriginal) {
        btnGuardar.dataset.textoOriginal = btnGuardar.innerHTML;
      }
      btnGuardar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';
    } else if (btnGuardar.dataset.textoOriginal) {
      btnGuardar.innerHTML = btnGuardar.dataset.textoOriginal;
    }
  }

  function cerrarModalBootstrap() {
    if (!modalEl || !window.bootstrap) return;

    const instancia =
      window.bootstrap.Modal.getInstance(modalEl) ||
      new window.bootstrap.Modal(modalEl);

    instancia.hide();
  }

  function esImagenPermitida(file) {
    if (!file) return false;

    const tiposPermitidos = [
      "image/jpeg",
      "image/jpg",
      "image/png",
      "image/webp",
    ];

    const nombre = String(file.name || "").toLowerCase();
    const ext = nombre.includes(".") ? nombre.split(".").pop() : "";

    const extensionesPermitidas = ["jpg", "jpeg", "png", "webp"];

    return (
      tiposPermitidos.indexOf(String(file.type || "").toLowerCase()) !== -1 &&
      extensionesPermitidas.indexOf(ext) !== -1
    );
  }

  function validarFormulario(data, detalle, imagenes) {
    if (
      (!data.contenedor_fisico_id || data.contenedor_fisico_id <= 0) &&
      !data.numero_ferro
    ) {
      return "Debes seleccionar o escribir un ferro/caja válido.";
    }

    if (!data.fecha_envio) {
      return "La fecha de envío es obligatoria.";
    }

    if (!data.estatus_envio) {
      return "Debes seleccionar un estatus de envío.";
    }

    if (!data.transportista_id || data.transportista_id <= 0) {
      return "Debes seleccionar un transportista válido.";
    }

    if (!Array.isArray(detalle) || detalle.length === 0) {
      return "Debes agregar al menos un producto al envío.";
    }

    let hayDetalleValido = false;

    for (let i = 0; i < detalle.length; i++) {
      const item = detalle[i];

      const facturaId = Number(item.factura_id || 0);
      const productoId = Number(item.producto_id || 0);
      const cajasEnviadas = Number(item.cajas_enviadas || 0);
      const cajasDisponibles = Number(item.cajas_disponibles || 0);

      if (facturaId > 0 && productoId > 0 && cajasEnviadas > 0) {
        hayDetalleValido = true;
      }

      if (cajasEnviadas < 0) {
        return "No se permiten cantidades negativas en el detalle.";
      }

      if (cajasDisponibles > 0 && cajasEnviadas > cajasDisponibles) {
        return (
          "La cantidad enviada no puede ser mayor a las cajas disponibles del producto: " +
          (item.descripcion || "Sin descripción")
        );
      }
    }

    if (!hayDetalleValido) {
      return "No hay productos válidos para guardar en el envío.";
    }

    if (!inpImagenes) {
      return "No se encontró el input de imágenes del formulario.";
    }

    if (
      !Array.isArray(imagenes) ||
      imagenes.length < IMG_MIN ||
      imagenes.length > IMG_MAX
    ) {
      return "Debes adjuntar entre " + IMG_MIN + " y " + IMG_MAX + " imágenes.";
    }

    for (let j = 0; j < imagenes.length; j++) {
      if (!esImagenPermitida(imagenes[j])) {
        return (
          "La imagen #" +
          (j + 1) +
          " no tiene un formato permitido. Solo se aceptan JPG, JPEG, PNG y WEBP."
        );
      }

      if (!imagenes[j].size || Number(imagenes[j].size) <= 0) {
        return "La imagen #" + (j + 1) + " está vacía o es inválida.";
      }
    }

    return "";
  }

  function construirPayload() {
    const detalleBruto = getDetalleActual();

    const detalle = detalleBruto
      .map(function (item) {
        return {
          factura_id: Number(item.factura_id || 0),
          producto_id: Number(item.producto_id || 0),
          cajas_enviadas: Number(item.cajas_enviadas || 0),
          notas_detalle: String(item.notas_detalle || "").trim(),
        };
      })
      .filter(function (item) {
        return (
          item.factura_id > 0 && item.producto_id > 0 && item.cajas_enviadas > 0
        );
      });

    return {
      contenedor_fisico_id: intValue(inpFisicoId),
      numero_ferro: trimValue(inpFisicoTxt),
      destino_ciudad_id: intValue(selDestino) || "",
      fecha_envio: trimValue(inpFechaEnvio),
      estatus_envio: trimValue(selEstatus),
      transportista_id: intValue(selTransportista),
      candado: trimValue(inpCandado),
      notas: trimValue(txtNotas),
      detalle: detalle,
    };
  }

  function construirFormData(payload, imagenes) {
    const fd = new FormData();

    fd.append("contenedor_fisico_id", payload.contenedor_fisico_id);
    fd.append("numero_ferro", payload.numero_ferro);
    fd.append("destino_ciudad_id", payload.destino_ciudad_id);
    fd.append("fecha_envio", payload.fecha_envio);
    fd.append("estatus_envio", payload.estatus_envio);
    fd.append("transportista_id", payload.transportista_id);
    fd.append("notas", payload.notas);
    //fd.append("candado", payload.candado);
    fd.append("detalle", JSON.stringify(payload.detalle));

    imagenes.forEach(function (file) {
      fd.append("imagenes[]", file, file.name);
    });

    return fd;
  }

  function construirMensajeError(resp) {
    let mensaje = (resp && resp.msg) || "No fue posible registrar el envío.";

    if (resp && Array.isArray(resp.errores) && resp.errores.length) {
      mensaje += "\n\n" + resp.errores.join("\n");
    }

    if (
      resp &&
      Array.isArray(resp.errores_imagenes) &&
      resp.errores_imagenes.length
    ) {
      mensaje += "\n\n" + resp.errores_imagenes.join("\n");
    }

    if (
      resp &&
      Array.isArray(resp.errores_detalle) &&
      resp.errores_detalle.length
    ) {
      mensaje += "\n\n" + resp.errores_detalle.join("\n");
    }

    return mensaje;
  }

  function enviarRegistro() {
    if (estaEnModoEditar()) {
      return;
    }

    if (enviando) return;

    const payload = construirPayload();
    const detalleActual = getDetalleActual();
    const imagenes = getImagenesSeleccionadas();

    const error = validarFormulario(payload, detalleActual, imagenes);
    if (error) {
      mostrarAlerta("warning", error);
      return;
    }

    const fd = construirFormData(payload, imagenes);

    const xhr = new XMLHttpRequest();
    xhr.open("POST", URL_REGISTRAR, true);

    enviando = true;
    bloquearBotonGuardar(true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      enviando = false;
      bloquearBotonGuardar(false);

      let resp = null;

      try {
        resp = JSON.parse(xhr.responseText);
      } catch (e) {
        console.error(
          "Respuesta no válida al registrar envío:",
          xhr.responseText,
        );
        mostrarAlerta("error", "La respuesta del servidor no es JSON válido.");
        return;
      }

      if (xhr.status >= 200 && xhr.status < 300 && resp && resp.ok) {
        mostrarAlerta("success", resp.msg || "Envío registrado correctamente.");

        if (
          window.partidasTransitoListado &&
          typeof window.partidasTransitoListado.limpiarModalEnvio === "function"
        ) {
          window.partidasTransitoListado.limpiarModalEnvio();
        } else if (formEl) {
          formEl.reset();
        }

        if (inpImagenes) {
          inpImagenes.value = "";
        }

        cerrarModalBootstrap();

        if (
          window.partidasTransitoListado &&
          typeof window.partidasTransitoListado.recargar === "function"
        ) {
          window.partidasTransitoListado.recargar();
        }

        return;
      }

      mostrarAlerta("error", construirMensajeError(resp));
    };

    xhr.onerror = function () {
      enviando = false;
      bloquearBotonGuardar(false);
      mostrarAlerta("error", "Ocurrió un error de red al registrar el envío.");
    };

    xhr.send(fd);
  }

  function setFechaHoySiEstaVacia() {
    if (!inpFechaEnvio) return;
    if (trimValue(inpFechaEnvio) !== "") return;

    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, "0");
    const dd = String(hoy.getDate()).padStart(2, "0");

    inpFechaEnvio.value = yyyy + "-" + mm + "-" + dd;
  }

  function bindEventos() {
    if (btnGuardar) {
      btnGuardar.addEventListener("click", function (e) {
        e.preventDefault();
        if (estaEnModoEditar()) return;
        enviarRegistro();
      });
    }

    if (formEl) {
      formEl.addEventListener("submit", function (e) {
        e.preventDefault();
        if (estaEnModoEditar()) return;
        enviarRegistro();
      });
    }

    if (modalEl) {
      modalEl.addEventListener("shown.bs.modal", function () {
        setFechaHoySiEstaVacia();
      });
    }
  }

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

  function estaEnModoEditar() {
    if (
      window.partidasTransitoListado &&
      typeof window.partidasTransitoListado.obtenerModoFormulario === "function"
    ) {
      return (
        window.partidasTransitoListado.obtenerModoFormulario() === "editar"
      );
    }
    return false;
  }

  bindEventos();
})();
