// === Modal Asignar Ferro/Caja: Ops + Trazabilidad (MF) ===
(function () {
  "use strict";

  // ---------------------------
  // Refs DOM (según tu vista)
  // ---------------------------
  const modalEl = document.getElementById("modalAsignarFerroCaja");

  const hidOperacionId = document.getElementById("asigFerro_operacionId");
  const hidOperacionCodigo = document.getElementById(
    "asigFerro_operacionCodigo",
  );

  const hidFerroFisicoId = document.getElementById("asigFerro_ferroFisicoId");
  const hidAsignacionId = document.getElementById("asigFerro_asignacionId");

  const tbFerrosOperacion = document.getElementById(
    "asigFerro_tbFerrosOperacion",
  );
  const tbOpsEnFerro = document.getElementById("asigFerro_tbOpsEnFerro");

  const badgeCodigo = document.getElementById("asigFerro_badgeCodigo");
  const badgeFerroSel = document.getElementById("asigFerro_badgeFerroSel");

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

  // ---------------------------
  // Estado interno
  // ---------------------------
  const state = {
    fechaSalidaSel: "", // define el viaje (FO) (en tu tabla izquierda es tr.dataset.fecha)
    operacionFerroId: null, // lo devuelve el backend en panel.operacion_ferro_id
    fisicoIdSel: "", // contenedor_fisico_id del ferro/caja seleccionado
    asignacionIdSel: "", // (opcional) id del vínculo
    foIdSel: "", // (opcional) fo_id del row (si lo ocupas para editar)
  };

  // ---------------------------
  // Utils
  // ---------------------------
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
    state.asignacionIdSel = "";
    state.foIdSel = "";

    if (hidFerroFisicoId) hidFerroFisicoId.value = "";
    if (hidAsignacionId) hidAsignacionId.value = "";

    setDisabledTrack(true);
  }

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function xhrPost(url, formData) {
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", url, true);
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

  // ---------------------------
  // Render tabla derecha (ops en ferro)
  // ---------------------------
  function renderOpsEnFerro(rows) {
    if (!tbOpsEnFerro) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      tbOpsEnFerro.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-muted py-3">
            Sin operaciones relacionadas.
          </td>
        </tr>`;
      return;
    }

    tbOpsEnFerro.innerHTML = rows
      .map((r) => {
        const codigo = escapeHtml(r.codigo || r.numero_operacion || "—");
        const cliente = escapeHtml(r.cliente || r.cliente_nombre || "—");
        const cont = escapeHtml(r.contenedor || r.numero_contenedor || "—");
        const bTot = escapeHtml(r.bultos_totales ?? r.bultosTotal ?? "—");
        const bEnv = escapeHtml(r.bultos_enviados ?? r.bultosEnviados ?? "—");
        return `
          <tr class="text-center">
            <td>${codigo}</td>
            <td class="text-start">${cliente}</td>
            <td>${cont}</td>
            <td>${bTot}</td>
            <td>${bEnv}</td>
          </tr>`;
      })
      .join("");
  }

  // ---------------------------
  // Cargar panel trazabilidad (controller::cargarPanel)
  // ---------------------------
  async function cargarPanelTrazabilidad() {
    const operacionId = (hidOperacionId?.value || "").trim();
    const fisicoId = (hidFerroFisicoId?.value || "").trim();
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
      base_url +
      "Operaciones_maritimo_ferro_trazabilidad_terrestre/cargarPanel";
    const { json } = await xhrPost(url, fd);

    if (!json || json.status !== "success") {
      resetPanelTrack(json?.msg || "No se pudo cargar trazabilidad.");
      if (window.Swal) {
        Swal.fire(
          "Error",
          json?.msg || "No se pudo cargar trazabilidad.",
          "error",
        );
      }
      return;
    }

    const panel = json.panel || {};

    // Pintar resumen
    if (inpOrigen) inpOrigen.value = panel.origen_puerto || "";
    if (inpDestino) inpDestino.value = panel.destino || "";
    if (inpUltima) inpUltima.value = panel.ubicacion_actual || "";

    state.operacionFerroId = panel.operacion_ferro_id
      ? Number(panel.operacion_ferro_id)
      : null;

    // Habilitar form si existe FO
    setDisabledTrack(!state.operacionFerroId);
  }

  // ---------------------------
  // Guardar trazabilidad (controller::guardar)
  // ---------------------------
  async function guardarTrazabilidad() {
    const operacionId = (hidOperacionId?.value || "").trim();
    const fisicoId = (hidFerroFisicoId?.value || "").trim();
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
      base_url + "Operaciones_maritimo_ferro_trazabilidad_terrestre/guardar";
    const { json } = await xhrPost(url, fd);

    if (!json || json.status !== "success") {
      if (btnGuardarTrack) btnGuardarTrack.disabled = false;
      Swal?.fire("Error", json?.msg || "No se pudo guardar.", "error");
      return;
    }

    // Refrescar panel con respuesta del backend
    const panel = json.panel || {};
    if (inpOrigen) inpOrigen.value = panel.origen_puerto || "";
    if (inpDestino) inpDestino.value = panel.destino || "";
    if (inpUltima) inpUltima.value = panel.ubicacion_actual || "";

    state.operacionFerroId = panel.operacion_ferro_id
      ? Number(panel.operacion_ferro_id)
      : state.operacionFerroId;

    // Limpieza parcial del form (mantengo ubicación/fecha si quieres capturar otra parada)
    if (inpRef) inpRef.value = "";
    if (txtNotas) txtNotas.value = "";

    if (btnGuardarTrack) btnGuardarTrack.disabled = false;

    Swal?.fire("Listo", json.msg || "Ubicación guardada.", "success");
  }

  // ---------------------------
  // Selección de fila (tabla izquierda)
  // ALINEADO a tu render actual:
  //  tr.dataset.numeroFerro
  //  tr.dataset.fecha (salida)
  //  tr.dataset.foId
  //  NUEVO requerido:
  //  tr.dataset.fisicoId  (contenedor_fisico_id)
  //  tr.dataset.asignacionId (si existe)
  // ---------------------------
  function onClickFilaFerro(e) {
    // Evita que los botones disparen la selección (Ver / Editar)
    if (
      e.target.closest(".asigFerro_btnVerOps") ||
      e.target.closest(".asigFerro_btnEdit")
    ) {
      return;
    }

    const tr = e.target.closest("tr");
    if (!tr || !tr.dataset) return;

    const numero = (tr.dataset.numeroFerro || "—").trim();
    const fechaSalida = (tr.dataset.fecha || "").trim(); // en tu render = salida
    const fisicoId = (tr.dataset.fisicoId || "").trim(); // NUEVO (obligatorio)
    const foId = (tr.dataset.foId || "").trim(); //

    if (!fisicoId || fisicoId === "0") {
      Swal?.fire(
        "Falta dato",
        "Esta fila no trae contenedor_fisico_id (dataset.fisicoId). Agrega tr.dataset.fisicoId al render.",
        "warning",
      );
      return;
    }
    if (!fechaSalida) {
      Swal?.fire(
        "Falta dato",
        "Esta fila no trae fecha de salida (dataset.fecha).",
        "warning",
      );
      return;
    }

    // Guardar selección
    state.fechaSalidaSel = fechaSalida;
    state.fisicoIdSel = fisicoId;
    state.foIdSel = foId;

    if (hidFerroFisicoId) hidFerroFisicoId.value = fisicoId;

    // UI
    if (badgeFerroSel) badgeFerroSel.textContent = numero;

    // Resaltar fila
    tbFerrosOperacion
      ?.querySelectorAll("tr.table-active")
      .forEach((x) => x.classList.remove("table-active"));
    tr.classList.add("table-active");

    // Cargar panel trazabilidad y tabla ops
    cargarPanelTrazabilidad();
    cargarOpsEnFerro(fisicoId, fechaSalida);
  }

  // ---------------------------
  // Cargar Ops en Ferro (tabla derecha)
  // Ajusta la ruta a TU endpoint real
  // ---------------------------
  async function cargarOpsEnFerro(contenedorFisicoId, fechaSalida) {
    if (!tbOpsEnFerro) return;

    tbOpsEnFerro.innerHTML = `
      <tr><td colspan="5" class="text-center text-muted py-3">Cargando…</td></tr>
    `;

    const fd = new FormData();
    fd.append("contenedor_fisico_id", contenedorFisicoId);
    fd.append("fecha_salida", fechaSalida);

    // Si tu endpoint actual usa fo_id, puedes enviar ambos sin problema:
    if (state.foIdSel) fd.append("fo_id", state.foIdSel);

    // CAMBIA esta ruta a la real que uses
    const url =
      base_url + "Operaciones_maritimo_ferro_asignacion_ferro/listarOpsEnFerro";
    const { json } = await xhrPost(url, fd);

    if (!json || json.status !== "success") {
      tbOpsEnFerro.innerHTML = `
        <tr><td colspan="5" class="text-center text-muted py-3">
          ${escapeHtml(json?.msg || "No se pudieron cargar las operaciones.")}
        </td></tr>`;
      return;
    }

    renderOpsEnFerro(json.data || json.ops || []);
  }

  // ---------------------------
  // Botones form trazabilidad
  // ---------------------------
  function limpiarFormTrazabilidad() {
    if (selUbicacion) selUbicacion.value = "";
    if (inpFecha) inpFecha.value = "";
    if (inpRef) inpRef.value = "";
    if (txtNotas) txtNotas.value = "";
  }

  // ---------------------------
  // Bindings
  // ---------------------------
  tbFerrosOperacion?.addEventListener("click", onClickFilaFerro);

  btnGuardarTrack?.addEventListener("click", function () {
    guardarTrazabilidad();
  });

  btnLimpiarTrack?.addEventListener("click", function () {
    limpiarFormTrazabilidad();
  });

  // Reset al cerrar modal
  modalEl?.addEventListener("hidden.bs.modal", function () {
    if (badgeFerroSel) badgeFerroSel.textContent = "—";
    renderOpsEnFerro([]);
    resetPanelTrack("—");
  });

  // Al abrir modal: setear badge del código de operación
  modalEl?.addEventListener("shown.bs.modal", function () {
    const cod = (hidOperacionCodigo?.value || "").trim();
    if (badgeCodigo) badgeCodigo.textContent = cod || "—";
    resetPanelTrack("Selecciona un Ferro/Caja para ver trazabilidad.");
  });
})();
