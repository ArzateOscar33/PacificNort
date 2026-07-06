// Assets/Js/ModulosAdmin/operaciones_maritimoferro/asignacion_ferro_modal_listar.js
(function () {
  "use strict";

  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  // === ENDPOINTS (controlador) ===
  const EP_LISTAR_FERROS =
    BASE_URL +
    "Operaciones_maritimo_ferro_asignacion_ferro/listarFerrosOperacion";

  const EP_LISTAR_OPS =
    BASE_URL + "Operaciones_maritimo_ferro_asignacion_ferro/listarOpsEnFerro";

  const EP_REGISTRAR =
    BASE_URL +
    "Operaciones_maritimo_ferro_asignacion_ferro/registrarVinculacion";
  const EP_RESUMEN_BULTOS =
    BASE_URL +
    "Operaciones_maritimo_ferro_asignacion_ferro/obtenerResumenBultosOperacion";
  // === Refs modal (IDs reales de tu HTML) ===
  const modalEl = document.getElementById("modalAsignarFerroCaja");
  if (!modalEl) return;

  const hidOperacionId = document.getElementById("asigFerro_operacionId");
  const hidOperacionCodigo = document.getElementById(
    "asigFerro_operacionCodigo",
  );

  const badgeCodigo = document.getElementById("asigFerro_badgeCodigo");
  const badgeFerroSel = document.getElementById("asigFerro_badgeFerroSel");

  const tbFerrosOperacion = document.getElementById(
    "asigFerro_tbFerrosOperacion",
  );
  const tbOpsEnFerro = document.getElementById("asigFerro_tbOpsEnFerro");

  const elCountFerros = document.getElementById("asigFerro_countFerros");

  // === Form inputs (lado izquierdo) ===
  const inpNumero = document.getElementById("asigFerro_inputNumero");
  const inpBultos = document.getElementById("asigFerro_inputBultos");
  const selTransportista = document.getElementById(
    "asigFerro_empresaTransportista",
  );
  const selDestino = document.getElementById("asigFerro_destino");
  const inpFechaSalida = document.getElementById("asigFerro_inputFechaSalida");
  const inpFechaCarga = document.getElementById("asigFerro_inputFechaCarga");
  const inpNotas = document.getElementById("asigFerro_inputNotas");

  const btnVincular = document.getElementById("asigFerro_btnVincular");
  const btnLimpiar = document.getElementById("asigFerro_btnLimpiar");
  const elBultosRestantesOperacion = document.getElementById(
    "bultosRestantesOperacion",
  );

  let currentBultosRestantes = 0;
  let currentBultosTotales = 0;
  let currentBultosAsignados = 0;
  let currentOperacionId = 0;
  let currentOperacionCodigo = "";

  // ===== Estado de edición =====
  const editState = {
    isEdit: false,
    foId: 0,
    numeroFerro: "",
    fechaSalida: "",
    rowEl: null,
  };

  // ===== Helpers =====
  const safe = (v) => (v === undefined || v === null ? "" : String(v));

  function setBtnText(el, html) {
    if (!el) return;
    el.innerHTML = html;
  }

  function setInputsLockForEdit(lock) {
    if (inpNumero) inpNumero.readOnly = !!lock;
    if (inpFechaSalida) inpFechaSalida.disabled = !!lock;
    if (selDestino) selDestino.disabled = !!lock;
  }

  function enterEditModeFromRow(row) {
    if (!row) return;

    const numeroFerro = safe(row.dataset.numeroFerro).trim();
    const fechaSalida = safe(row.dataset.fecha).trim();
    const fechaCarga = safe(row.dataset.fechaCarga).trim();
    const bultos = safe(row.dataset.bultos).trim();
    const destinoId = safe(row.dataset.destinoId).trim();
    const transportistaId = safe(row.dataset.transportistaId).trim();
    const foId = Number(row.dataset.foId || 0);
    const notas = safe(row.dataset.notas).trim();

    if (inpNumero) inpNumero.value = numeroFerro;
    if (inpFechaSalida) inpFechaSalida.value = fechaSalida;
    if (inpFechaCarga) inpFechaCarga.value = fechaCarga || "";
    if (inpBultos) inpBultos.value = bultos !== "" ? bultos : "0";
    if (selDestino) selDestino.value = destinoId || "";
    if (selTransportista) selTransportista.value = transportistaId || "";
    if (inpNotas) inpNotas.value = notas || "";

    editState.isEdit = true;
    editState.foId = foId;
    editState.numeroFerro = numeroFerro;
    editState.fechaSalida = fechaSalida;
    editState.rowEl = row;

    setInputsLockForEdit(true);

    setBtnText(
      btnVincular,
      `<i data-feather="save" class="me-1"></i> Actualizar`,
    );
    btnVincular?.classList.remove("btn-success");
    btnVincular?.classList.add("btn-primary");

    Array.from(tbFerrosOperacion?.querySelectorAll("tr") || []).forEach((tr) =>
      tr.classList.remove("table-active"),
    );
    row.classList.add("table-active");

    inpBultos?.focus();
    if (window.feather?.replace) window.feather.replace();
  }

  function exitEditMode() {
    editState.isEdit = false;
    editState.foId = 0;
    editState.numeroFerro = "";
    editState.fechaSalida = "";
    editState.rowEl = null;

    setInputsLockForEdit(false);

    setBtnText(
      btnVincular,
      `<i data-feather="check-circle" class="me-1"></i> Vincular`,
    );
    btnVincular?.classList.remove("btn-primary");
    btnVincular?.classList.add("btn-success");

    if (window.feather?.replace) window.feather.replace();
  }

  function xhrGET(url, cb) {
    const x = new XMLHttpRequest();
    x.open("GET", url, true);
    x.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;
      let payload = null;
      try {
        payload = JSON.parse(x.responseText || "{}");
      } catch (e) {}
      cb(x.status, payload, x.responseText);
    };
    x.send();
  }

  function xhrPOST(url, fd, cb) {
    const x = new XMLHttpRequest();
    x.open("POST", url, true);
    x.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;
      let payload = null;
      try {
        payload = JSON.parse(x.responseText || "{}");
      } catch (e) {}
      cb(x.status, payload, x.responseText);
    };
    x.send(fd);
  }

  function renderLoadingLeft() {
    if (!tbFerrosOperacion) return;
    tbFerrosOperacion.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">Cargando...</td></tr>`;
    if (elCountFerros) elCountFerros.textContent = "0";
  }

  function renderEmptyLeft(msg) {
    if (!tbFerrosOperacion) return;
    tbFerrosOperacion.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">${msg || "Sin vínculos todavía."}</td></tr>`;
    if (elCountFerros) elCountFerros.textContent = "0";
  }

  function renderLoadingRight() {
    if (!tbOpsEnFerro) return;
    tbOpsEnFerro.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Cargando...</td></tr>`;
  }

  function renderEmptyRight(msg) {
    if (!tbOpsEnFerro) return;
    tbOpsEnFerro.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">${msg || "Selecciona un Ferro/Caja de la lista izquierda."}</td></tr>`;
  }

  function setBultosRestantesBadge(restantes, asignados, totales) {
    if (!elBultosRestantesOperacion) return;

    const r = Number(restantes || 0);
    const a = Number(asignados || 0);
    const t = Number(totales || 0);
    currentBultosRestantes = r;
    currentBultosAsignados = a;
    currentBultosTotales = t;

    elBultosRestantesOperacion.textContent = `${r}`;

    elBultosRestantesOperacion.classList.remove(
      "bg-primary",
      "bg-success",
      "bg-warning",
      "bg-danger",
    );

    if (r <= 0) {
      elBultosRestantesOperacion.classList.add("bg-danger");
    } else if (r < t) {
      elBultosRestantesOperacion.classList.add("bg-warning");
    } else {
      elBultosRestantesOperacion.classList.add("bg-primary");
    }

    elBultosRestantesOperacion.title = `Totales: ${t} | Asignados: ${a} | Restantes: ${r}`;
  }

  function resetBultosRestantesBadge() {
    currentBultosRestantes = 0;
    currentBultosTotales = 0;
    currentBultosAsignados = 0;

    if (!elBultosRestantesOperacion) return;
    elBultosRestantesOperacion.textContent = "—";
    elBultosRestantesOperacion.classList.remove(
      "bg-success",
      "bg-warning",
      "bg-danger",
    );
    elBultosRestantesOperacion.classList.add("bg-primary");
    elBultosRestantesOperacion.removeAttribute("title");
  }
  function cargarResumenBultosOperacion(operacionId) {
    if (!operacionId) {
      resetBultosRestantesBadge();
      return;
    }

    const url =
      EP_RESUMEN_BULTOS + "?operacion_id=" + encodeURIComponent(operacionId);

    xhrGET(url, (status, res, raw) => {
      if (status !== 200 || !res) {
        console.error("obtenerResumenBultosOperacion error:", raw);
        resetBultosRestantesBadge();
        return;
      }

      if (res.status !== "success" || !res.data) {
        resetBultosRestantesBadge();
        return;
      }

      const data = res.data || {};
      setBultosRestantesBadge(
        data.bultos_restantes || 0,
        data.bultos_asignados || 0,
        data.bultos_totales || 0,
      );
    });
  }

  function limpiarFormulario() {
    if (inpNumero) inpNumero.value = "";
    if (inpBultos) inpBultos.value = "";
    if (selTransportista) selTransportista.value = "";
    if (selDestino) selDestino.value = "";
    if (inpFechaSalida) inpFechaSalida.value = "";
    if (inpFechaCarga) inpFechaCarga.value = "";
    if (inpNotas) inpNotas.value = "";
  }

  // ===== API: listar ferros de operación (izquierda) =====
  function cargarFerrosDeOperacion(operacionId) {
    if (!operacionId) return;

    renderLoadingLeft();
    renderEmptyRight("Selecciona un Ferro/Caja de la lista izquierda.");
    if (badgeFerroSel) badgeFerroSel.textContent = "—";

    // 👇 al cambiar operación, también resetea trazabilidad
    window.MFTrazabilidad?.reset?.();

    const url =
      EP_LISTAR_FERROS + "?operacion_id=" + encodeURIComponent(operacionId);

    xhrGET(url, (status, res, raw) => {
      if (status !== 200 || !res) {
        console.error("listarFerrosOperacion error:", raw);
        renderEmptyLeft("Error al cargar vínculos.");
        return;
      }
      if (res.status !== "success") {
        renderEmptyLeft(res.msg || "No se pudieron cargar vínculos.");
        return;
      }

      const rows = Array.isArray(res.rows) ? res.rows : [];
      if (rows.length === 0) {
        renderEmptyLeft("Sin vínculos todavía.");
        return;
      }

      if (elCountFerros) elCountFerros.textContent = String(rows.length);
      tbFerrosOperacion.innerHTML = "";

      rows.forEach((r) => {
        const foId = Number(r.fo_id || 0);
        const numero = safe(r.numero_ferro);
        const transportista = safe(r.transportista_nombre) || "—";
        const fecha = safe(r.fecha); // salida
        const fechaCarga = safe(r.fecha_carga); // carga
        const bultos = safe(r.bultos);
        const destino = safe(r.destino_nombre) || "—";
        const destinoId = safe(r.destino_id || "");
        const transportistaId = safe(r.transportista_id || "");
        const notas = safe(r.notas || r.comentarios || "");
        const fisicoId = safe(r.contenedor_fisico_id || ""); // ✅ CLAVE

        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.style.cursor = "pointer";

        tr.dataset.numeroFerro = numero;
        tr.dataset.fecha = fecha;
        tr.dataset.foId = String(foId);
        tr.dataset.destinoId = destinoId;
        tr.dataset.transportistaId = transportistaId;
        tr.dataset.fechaCarga = fechaCarga;
        tr.dataset.bultos = bultos;
        tr.dataset.notas = notas;
        tr.dataset.fisicoId = fisicoId;

        tr.innerHTML = `
          <td class="text-start">
            <div class="fw-semibold">${numero}</div>
            <div class="small text-muted">Destino: ${destino}</div>
          </td>
          <td>${transportista}</td>
          <td>${bultos}</td>
          <td class="text-nowrap">${fechaCarga || "—"}</td>
          <td class="text-nowrap">${fecha}</td>
          <td class="text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-primary asigFerro_btnVerOps">Ver</button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1 asigFerro_btnEdit">Editar</button>
          </td>
        `;
        tbFerrosOperacion.appendChild(tr);
      });

      if (window.feather?.replace) window.feather.replace();
    });
  }

  // ===== API: listar operaciones en ferro+fecha (derecha) =====
  function cargarOperacionesEnFerro(numeroFerro, fecha) {
    numeroFerro = (numeroFerro || "").trim();
    fecha = (fecha || "").trim();

    if (!numeroFerro || !fecha) {
      renderEmptyRight("Ferro/fecha inválidos.");
      return;
    }

    if (badgeFerroSel) badgeFerroSel.textContent = `${numeroFerro} • ${fecha}`;
    renderLoadingRight();

    const url =
      EP_LISTAR_OPS +
      "?numero_ferro=" +
      encodeURIComponent(numeroFerro) +
      "&fecha=" +
      encodeURIComponent(fecha);

    xhrGET(url, (status, res, raw) => {
      if (status !== 200 || !res) {
        console.error("listarOpsEnFerro error:", raw);
        renderEmptyRight("Error al cargar operaciones.");
        return;
      }
      if (res.status !== "success") {
        renderEmptyRight(res.msg || "No se pudieron cargar operaciones.");
        return;
      }

      const rows = Array.isArray(res.rows) ? res.rows : [];
      if (rows.length === 0) {
        renderEmptyRight("No hay operaciones en este ferro/fecha.");
        return;
      }

      tbOpsEnFerro.innerHTML = "";
      rows.forEach((r) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td class="text-nowrap">${safe(r.codigo)}</td>
          <td class="text-start">${safe(r.cliente) || "—"}</td>
          <td class="text-nowrap">${safe(r.contenedor_maritimo) || "—"}</td>
          <td class="text-nowrap">${safe(r.bultos_totales) || "—"}</td>
          <td class="text-nowrap">${safe(r.bultos_asignados) || "—"}</td>
        `;
        tbOpsEnFerro.appendChild(tr);
      });
    });
  }

  // ===== Registrar / Actualizar =====
  function registrarVinculacion() {
    if (!currentOperacionId) {
      Swal?.fire("Error", "Falta operacion_id.", "error");
      return;
    }

    const numeroFerro = (inpNumero?.value || "").trim();
    const bultos = Number(inpBultos?.value || 0);
    const destinoId = Number(selDestino?.value || 0);
    const transportistaId = Number(selTransportista?.value || 0);
    const fechaSalida = (inpFechaSalida?.value || "").trim();
    const fechaCarga = (inpFechaCarga?.value || "").trim();
    const notas = (inpNotas?.value || "").trim();

    if (!numeroFerro) {
      Swal?.fire("Faltan datos", "Captura el Ferro/Caja.", "warning");
      inpNumero?.focus();
      return;
    }
    if (!fechaSalida) {
      Swal?.fire("Faltan datos", "Selecciona fecha de salida.", "warning");
      inpFechaSalida?.focus();
      return;
    }
    if (!destinoId) {
      Swal?.fire("Faltan datos", "Selecciona destino.", "warning");
      selDestino?.focus();
      return;
    }
    if (!Number.isFinite(bultos) || bultos <= 0) {
      Swal?.fire("Dato inválido", "Bultos debe ser mayor a 0.", "warning");
      inpBultos?.focus();
      return;
    }

    if (editState.isEdit) {
      if (
        numeroFerro !== editState.numeroFerro ||
        fechaSalida !== editState.fechaSalida
      ) {
        Swal?.fire(
          "No permitido",
          "En edición no puedes cambiar Ferro/Caja ni Fecha salida.",
          "warning",
        );
        return;
      }
    }

    const fd = new FormData();
    fd.append("operacion_id", String(currentOperacionId));
    fd.append("numero_ferro", numeroFerro);
    fd.append("bultos", String(bultos));
    fd.append("destino_id", String(destinoId));
    fd.append("transportista_id", String(transportistaId || 0));
    fd.append("fecha_salida", fechaSalida);
    fd.append("fecha_carga", fechaCarga);
    fd.append("notas", notas);
    if (!editState.isEdit && bultos > currentBultosRestantes) {
      Swal?.fire(
        "Bultos insuficientes",
        `Solo quedan ${currentBultosRestantes} bultos restantes para esta operación.`,
        "warning",
      );
      inpBultos?.focus();
      return;
    }
    btnVincular && (btnVincular.disabled = true);

    xhrPOST(EP_REGISTRAR, fd, (status, res, raw) => {
      btnVincular && (btnVincular.disabled = false);

      if (status !== 200 || !res) {
        console.error("registrarVinculacion error:", raw);
        Swal?.fire("Error", "No se pudo guardar.", "error");
        return;
      }

      if (res.status === "success") {
        Swal?.fire(
          "Listo",
          res.msg ||
            (editState.isEdit ? "Actualizado." : "Vinculación registrada."),
          "success",
        );

        exitEditMode();
        limpiarFormulario();
        cargarResumenBultosOperacion(currentOperacionId);
        cargarFerrosDeOperacion(currentOperacionId);

        // o si prefieres un evento genérico:
        document.dispatchEvent(
          new CustomEvent("mf:refresh-list", {
            detail: { operacion_id: currentOperacionId },
          }),
        );

        // opcional: refrescar trazabilidad si estabas parado en ese viaje
        window.MFTrazabilidad?.refresh?.();
      } else {
        Swal?.fire("Error", res.msg || "No se pudo guardar.", "error");
      }
    });
  }

  // ===== Eventos =====

  modalEl.addEventListener("show.bs.modal", (ev) => {
    const btn = ev.relatedTarget;
    currentOperacionId = Number(btn?.getAttribute("data-id") || 0);
    currentOperacionCodigo = String(btn?.getAttribute("data-codigo") || "");

    if (hidOperacionId) hidOperacionId.value = String(currentOperacionId || "");
    if (hidOperacionCodigo)
      hidOperacionCodigo.value = String(currentOperacionCodigo || "");
    if (badgeCodigo) badgeCodigo.textContent = currentOperacionCodigo || "—";

    exitEditMode();
    limpiarFormulario();
    resetBultosRestantesBadge();
    cargarResumenBultosOperacion(currentOperacionId);
    cargarFerrosDeOperacion(currentOperacionId);
  });

  tbFerrosOperacion?.addEventListener("click", (e) => {
    const btnEdit = e.target.closest(".asigFerro_btnEdit");
    const btnVer = e.target.closest(".asigFerro_btnVerOps");
    const row = e.target.closest("tr");
    if (!row) return;

    if (btnEdit) {
      e.preventDefault();
      e.stopPropagation();
      enterEditModeFromRow(row);
      return;
    }

    // VER o click en fila
    const numeroFerro = row.dataset.numeroFerro || "";
    const fecha = row.dataset.fecha || "";
    const fisicoId = row.dataset.fisicoId || "";

    Array.from(tbFerrosOperacion.querySelectorAll("tr")).forEach((tr) =>
      tr.classList.remove("table-active"),
    );
    row.classList.add("table-active");

    cargarOperacionesEnFerro(numeroFerro, fecha);

    // ✅ Aquí SIEMPRE sincronizas la trazabilidad
    window.MFTrazabilidad?.select?.({
      fisicoId: fisicoId,
      fechaSalida: fecha,
    });
  });

  btnVincular?.addEventListener("click", (e) => {
    e.preventDefault();
    registrarVinculacion();
  });

  btnLimpiar?.addEventListener("click", (e) => {
    e.preventDefault();
    exitEditMode();
    limpiarFormulario();
    inpNumero?.focus();
  });

  window.MFAsignacionFerroModal = {
    refresh: () => cargarFerrosDeOperacion(currentOperacionId),
    getOperacionId: () => currentOperacionId,
    isEditing: () => editState.isEdit,
  };
})();
