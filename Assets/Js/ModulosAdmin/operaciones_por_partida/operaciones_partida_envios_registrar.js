(function () {
  "use strict";

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

  function bloquearBotonGuardar(bloquear) {
    if (!btnGuardar) return;

    btnGuardar.disabled = !!bloquear;

    if (bloquear) {
      btnGuardar.dataset.textoOriginal = btnGuardar.innerHTML;
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

  function validarFormulario(data, detalle) {
    if (!data.contenedor_fisico_id || data.contenedor_fisico_id <= 0) {
      return "Debes seleccionar un ferro/caja válido.";
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
      destino_ciudad_id: intValue(selDestino) || "",
      fecha_envio: trimValue(inpFechaEnvio),
      estatus_envio: trimValue(selEstatus),
      transportista_id: intValue(selTransportista),
      notas: trimValue(txtNotas),
      detalle: detalle,
    };
  }

  function enviarRegistro() {
    if (enviando) return;

    const payload = construirPayload();
    const detalleActual = getDetalleActual();

    const error = validarFormulario(payload, detalleActual);
    if (error) {
      mostrarAlerta("warning", error);
      return;
    }

    const fd = new FormData();
    fd.append("contenedor_fisico_id", payload.contenedor_fisico_id);
    fd.append("destino_ciudad_id", payload.destino_ciudad_id);
    fd.append("fecha_envio", payload.fecha_envio);
    fd.append("estatus_envio", payload.estatus_envio);
    fd.append("transportista_id", payload.transportista_id);
    fd.append("notas", payload.notas);
    fd.append("detalle", JSON.stringify(payload.detalle));

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

        cerrarModalBootstrap();

        if (
          window.partidasTransitoListado &&
          typeof window.partidasTransitoListado.recargar === "function"
        ) {
          window.partidasTransitoListado.recargar();
        }

        return;
      }

      mostrarAlerta(
        "error",
        (resp && resp.msg) || "No fue posible registrar el envío.",
      );
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
      btnGuardar.addEventListener("click", enviarRegistro);
    }

    if (formEl) {
      formEl.addEventListener("submit", function (e) {
        e.preventDefault();
        enviarRegistro();
      });
    }

    if (modalEl) {
      modalEl.addEventListener("shown.bs.modal", function () {
        setFechaHoySiEstaVacia();
      });
    }
  }

  bindEventos();
})();
