// Assets/Js/ModulosAdmin/operaciones_maritimoferro/asignacion_ferro_modal_trazabilidad.js
(function () {
  "use strict";

  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  const modalEl = document.getElementById("modalAsignarFerroCaja");
  if (!modalEl) return;

  const hidOperacionId = document.getElementById("asigFerro_operacionId");
  const hidFerroFisicoId = document.getElementById("asigFerro_ferroFisicoId");

  // Panel trazabilidad (resumen)
  const inpOrigen = document.getElementById("asigFerro_trackOrigen");
  const inpUltima = document.getElementById("asigFerro_trackUltima");
  const inpDestino = document.getElementById("asigFerro_trackDestino");
  const hintTrack = document.getElementById("asigFerro_trackHint");

  // Form trazabilidad (registro)
  const selUbicacion = document.getElementById("asigFerro_trackUbicacionId");
  const inpFecha = document.getElementById("asigFerro_trackFechaHora");
  const inpRef = document.getElementById("asigFerro_trackReferencia");
  const txtNotas = document.getElementById("asigFerro_trackNotas");

  const btnGuardarTrack = document.getElementById(
    "asigFerro_btnGuardarTrazabilidad",
  );
  const btnLimpiarTrack = document.getElementById(
    "asigFerro_btnLimpiarTrazabilidad",
  );

  const state = {
    fechaSalidaSel: "",
    operacionFerroId: null,
    fisicoIdSel: "",
  };

  function setDisabledTrack(disabled) {
    const v = !!disabled;
    if (btnGuardarTrack) btnGuardarTrack.disabled = v;
    if (btnLimpiarTrack) btnLimpiarTrack.disabled = v;
    if (selUbicacion) selUbicacion.disabled = v;
    if (inpFecha) inpFecha.disabled = v;
    if (inpRef) inpRef.disabled = v;
    if (txtNotas) txtNotas.disabled = v;
  }

  function resetPanelTrack(msg) {
    if (inpOrigen) inpOrigen.value = "";
    if (inpDestino) inpDestino.value = "";
    if (inpUltima) inpUltima.value = "";
    if (hintTrack) hintTrack.textContent = msg || "—";

    if (selUbicacion) selUbicacion.value = "";
    if (inpFecha) inpFecha.value = "";
    if (inpRef) inpRef.value = "";
    if (txtNotas) txtNotas.value = "";

    state.fechaSalidaSel = "";
    state.operacionFerroId = null;
    state.fisicoIdSel = "";

    if (hidFerroFisicoId) hidFerroFisicoId.value = "";

    setDisabledTrack(true);
  }

  function xhrPost(url, formData) {
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", url, true);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          let json = null;
          try {
            json = JSON.parse(xhr.responseText || "{}");
          } catch (e) {}
          resolve({ status: xhr.status, json });
        }
      };
      xhr.send(formData);
    });
  }

  async function cargarPanelTrazabilidad() {
    const operacionId = (hidOperacionId?.value || "").trim();
    const fisicoId = (state.fisicoIdSel || "").trim();
    const fechaSalida = (state.fechaSalidaSel || "").trim();

    if (!operacionId || !fisicoId || !fechaSalida) {
      resetPanelTrack("Selecciona un Ferro/Caja para ver trazabilidad.");
      return;
    }

    if (hintTrack) hintTrack.textContent = "Cargando…";

    const fd = new FormData();
    fd.append("operacion_id", operacionId);
    fd.append("contenedor_fisico_id", fisicoId);
    fd.append("fecha_salida", fechaSalida);

    const url =
      BASE_URL +
      "Operaciones_maritimo_ferro_trazabilidad_terrestre/cargarPanel";
    const { json } = await xhrPost(url, fd);

    if (!json || json.status !== "success") {
      resetPanelTrack(json?.msg || "No se pudo cargar trazabilidad.");
      return;
    }

    const panel = json.panel || {};

    if (inpOrigen) inpOrigen.value = panel.origen_puerto || "";
    if (inpDestino) inpDestino.value = panel.destino || "";
    if (inpUltima)
      inpUltima.value = panel.ubicacion_actual || "Registre Ubicación Actual";

    state.operacionFerroId = panel.operacion_ferro_id
      ? Number(panel.operacion_ferro_id)
      : null;

    setDisabledTrack(!state.operacionFerroId);
    if (hintTrack)
      hintTrack.textContent = state.operacionFerroId
        ? "—"
        : "Sin viaje terrestre (FO).";
  }

  async function guardarTrazabilidad() {
    const operacionId = (hidOperacionId?.value || "").trim();
    const fisicoId = (state.fisicoIdSel || "").trim();
    const fechaSalida = (state.fechaSalidaSel || "").trim();

    const ubicacionId = (selUbicacion?.value || "").trim();
    const fechaEvento = (inpFecha?.value || "").trim();
    const referencia = (inpRef?.value || "").trim();
    const notas = (txtNotas?.value || "").trim();

    if (!operacionId || !fisicoId || !fechaSalida) {
      Swal?.fire(
        "Falta selección",
        "Selecciona un Ferro/Caja válido.",
        "warning",
      );
      return;
    }
    if (!state.operacionFerroId) {
      Swal?.fire(
        "Sin viaje terrestre",
        "No se encontró FO/viaje para esa fecha de salida.",
        "warning",
      );
      return;
    }
    if (!ubicacionId) {
      Swal?.fire(
        "Ubicación requerida",
        "Selecciona la ubicación actual.",
        "warning",
      );
      selUbicacion?.focus();
      return;
    }
    if (!fechaEvento) {
      Swal?.fire("Fecha requerida", "Selecciona la fecha.", "warning");
      inpFecha?.focus();
      return;
    }

    if (btnGuardarTrack) btnGuardarTrack.disabled = true;

    const fd = new FormData();
    fd.append("operacion_id", operacionId);
    fd.append("contenedor_fisico_id", fisicoId);
    fd.append("fecha_salida", fechaSalida);
    fd.append("ubicacion_id", ubicacionId);
    fd.append("fecha_evento", fechaEvento);
    fd.append("referencia", referencia);
    fd.append("notas", notas);

    const url =
      BASE_URL + "Operaciones_maritimo_ferro_trazabilidad_terrestre/guardar";
    const { json } = await xhrPost(url, fd);

    if (btnGuardarTrack) btnGuardarTrack.disabled = false;

    if (!json || json.status !== "success") {
      Swal?.fire("Error", json?.msg || "No se pudo guardar.", "error");
      return;
    }
    // ✅ aquí ya guardó bien
    document.dispatchEvent(
      new CustomEvent("mf:refresh-list", {
        detail: { operacion_id: operacionId },
      }),
    );
    // refresca resumen
    const panel = json.panel || {};
    if (inpOrigen) inpOrigen.value = panel.origen_puerto || "";
    if (inpDestino) inpDestino.value = panel.destino || "";
    if (inpUltima) inpUltima.value = panel.ubicacion_actual || "";

    if (inpRef) inpRef.value = "";
    if (txtNotas) txtNotas.value = "";

    Swal?.fire("Listo", json.msg || "Ubicación guardada.", "success");
  }

  function limpiarFormTrazabilidad() {
    if (selUbicacion) selUbicacion.value = "";
    if (inpFecha) inpFecha.value = "";
    if (inpRef) inpRef.value = "";
    if (txtNotas) txtNotas.value = "";
  }

  // ✅ API pública para que el archivo 1 controle la selección
  window.MFTrazabilidad = {
    select: ({ fisicoId, fechaSalida }) => {
      state.fisicoIdSel = String(fisicoId || "").trim();
      state.fechaSalidaSel = String(fechaSalida || "").trim();
      if (hidFerroFisicoId) hidFerroFisicoId.value = state.fisicoIdSel;
      cargarPanelTrazabilidad();
    },
    reset: () =>
      resetPanelTrack("Selecciona un Ferro/Caja para ver trazabilidad."),
    refresh: () => cargarPanelTrazabilidad(),
  };

  btnGuardarTrack?.addEventListener("click", function (e) {
    e.preventDefault();
    guardarTrazabilidad();
  });

  btnLimpiarTrack?.addEventListener("click", function (e) {
    e.preventDefault();
    limpiarFormTrazabilidad();
  });

  modalEl.addEventListener("hidden.bs.modal", function () {
    window.MFTrazabilidad.reset();
  });

  modalEl.addEventListener("shown.bs.modal", function () {
    window.MFTrazabilidad.reset();
  });
})();
