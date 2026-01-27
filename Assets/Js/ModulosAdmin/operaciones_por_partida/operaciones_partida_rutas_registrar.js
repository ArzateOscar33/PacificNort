// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_rutas_registrar.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const EP_GUARDAR_ENVIOS = "Operaciones_por_partida_rutas/guardarEnviosRutas";
  const EP_LISTAR_ENVIOS  = "Operaciones_por_partida_rutas/listarEnviosProductoRutas";
  const EP_BAJA_ENVIO     = "Operaciones_por_partida_rutas/bajaEnvioRutas";

  // ===== Refs DOM (Modal) =====
  const modalEnvioEl = document.getElementById("modalPartidasTransitoEnvio");
  const btnGuardar = document.getElementById("partidas_transito_btnGuardarEnvio");

  const hidProductoId = document.getElementById("partidas_transito_idProducto");
  const hidFacturaId2 = document.getElementById("partidas_transito_factura");

  const badgeDisponibles = document.getElementById("partidas_transito_badgeDisponibles");
  const hidDisponibles   = document.getElementById("partidas_transito_cajasDisponibles");

  const lblTotalAsignado = document.getElementById("partidas_transito_lblTotalAsignado");
  const lblRestantes     = document.getElementById("partidas_transito_lblRestantes");

  const tbodyEnvios = document.getElementById("partidas_transito_tbodyEnvios");
  const btnAddRow   = document.getElementById("partidas_transito_btnAddRow");

  // Refrescar tabla de productos (lo usa tu catálogo)
  const btnRefrescar = document.getElementById("partidas_transito_btnRefrescar");

  let xhrGuardar = null;

  // =========================
  // Helpers
  // =========================
  function nint(v) {
    const x = parseInt(String(v ?? "").trim(), 10);
    return Number.isFinite(x) ? x : 0;
  }

  function escHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function safeFire(opts) {
    if (typeof Swal !== "undefined" && Swal && typeof Swal.fire === "function") {
      return Swal.fire(opts);
    }
    alert((opts.title ? opts.title + "\n" : "") + (opts.text || opts.html || ""));
    return Promise.resolve({ isConfirmed: true });
  }

  function abortXHR(x) {
    try { if (x && x.readyState !== 4) x.abort(); } catch (_) {}
  }

  function buildUrl(ep) {
    if (!base_url) return ep;
    return base_url.replace(/\/+$/, "") + "/" + String(ep).replace(/^\/+/, "");
  }

  function getDisponiblesOriginal() {
    const d = nint(hidDisponibles?.value);
    return d > 0 ? d : 0;
  }

  function setDisponiblesOriginal(v) {
    const val = Math.max(0, nint(v));
    if (hidDisponibles) hidDisponibles.value = String(val);
    if (badgeDisponibles) badgeDisponibles.textContent = String(val);
  }

  function getRows() {
    return Array.from(tbodyEnvios?.querySelectorAll(".partidas_transito_row") || []);
  }

  function reindexRows() {
    const rows = getRows();
    rows.forEach((row, i) => row.setAttribute("data-index", String(i)));
  }

  // =========================
  // EDICIÓN POR RENGLÓN
  // =========================
  function setRowEditable(tr, editable) {
    if (!tr) return;
    const isEdit = !!editable;

    tr.classList.toggle("table-warning", isEdit);

    // Inputs
    const destinoTxt = tr.querySelector(".pt_destino_txt");
    const fechaInp   = tr.querySelector(".pt_fecha_envio");
    const fisicoTxt  = tr.querySelector(".pt_fisico_txt");
    const cajasInp   = tr.querySelector(".pt_cajas");
    const estSel     = tr.querySelector(".pt_estatus");
    const notaInp    = tr.querySelector(".pt_nota");

    if (destinoTxt) destinoTxt.toggleAttribute("readonly", !isEdit);
    if (fechaInp)   fechaInp.toggleAttribute("readonly", !isEdit);
    if (fisicoTxt)  fisicoTxt.toggleAttribute("readonly", !isEdit);
    if (cajasInp)   cajasInp.toggleAttribute("readonly", !isEdit);
    if (notaInp)    notaInp.toggleAttribute("readonly", !isEdit);

    if (estSel) estSel.disabled = !isEdit;

    tr.setAttribute("data-editing", isEdit ? "1" : "0");
  }

  // =========================
  // RESUMEN (DELTA)
  // disponiblesOriginal = "restantes" al abrir modal
  // delta = nuevos + (editadosNow - editadosOrig)
  // =========================
  function calcDeltaAsignado() {
    let delta = 0;

    getRows().forEach((row) => {
      const envioId = nint(row.getAttribute("data-envio-id"));
      const cajasNow = nint(row.querySelector(".pt_cajas")?.value);
      const estNow = nint(row.querySelector(".pt_estatus")?.value) || 1;

      const countsNow = (estNow === 1 || estNow === 2);

      if (envioId > 0) {
        const editing = row.getAttribute("data-editing") === "1";
        if (!editing) return;

        const cajasOrig = nint(row.getAttribute("data-orig-cajas"));
        const estOrig   = nint(row.getAttribute("data-orig-estatus")) || 1;
        const countsOrig = (estOrig === 1 || estOrig === 2);

        const nowVal  = countsNow ? cajasNow : 0;
        const origVal = countsOrig ? cajasOrig : 0;

        delta += (nowVal - origVal);
        return;
      }

      // nuevo
      delta += countsNow ? cajasNow : 0;
    });

    return delta;
  }

  function updateResumenUI() {
    const disponibles = getDisponiblesOriginal();
    const delta = calcDeltaAsignado();
    const restantes = Math.max(0, disponibles - delta);

    if (lblTotalAsignado) lblTotalAsignado.textContent = String(Math.max(0, delta));
    if (lblRestantes) lblRestantes.textContent = String(restantes);
  }

  // =========================
  // Plantillas de renglón
  // =========================
  function rowHtmlBase(data) {
    const envioId   = nint(data.envioId);
    const destinoId = nint(data.destinoId);
    const fisicoId  = nint(data.fisicoId);
    const cajas     = data.cajas !== undefined && data.cajas !== "" ? nint(data.cajas) : "";
    const estatus   = nint(data.estatus) || 1;

    const destinoTxt = escHtml(data.destinoTxt || "");
    const fisicoTxt  = escHtml(data.fisicoTxt || "");
    const fecha      = escHtml((data.fecha || "").toString().slice(0, 10));
    const nota       = escHtml(data.nota || "");

    const esExistente = !!data.esExistente;
    const trClass = esExistente ? "table-light" : "";

    // Acciones
    const btnAccion = esExistente
      ? `
        <button type="button" class="btn btn-outline-warning btn-sm pt_btnEditarEnvio" data-id="${envioId}" title="Editar">
          <i data-feather="edit-2"></i>
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm pt_btnBajaEnvio" data-id="${envioId}" title="Dar de baja">
          <i data-feather="trash-2"></i>
        </button>
      `
      : `
        <button type="button" class="btn btn-outline-danger btn-sm pt_btnRemoveRow" title="Quitar renglón">
          <i data-feather="trash-2"></i>
        </button>
      `;

    // Existente: inicia NO editable (readonly/disabled)
    const ro = esExistente ? "readonly" : "";
    const dis = esExistente ? "disabled" : "";

    return `
      <tr class="partidas_transito_row ${trClass}"
          data-index="0"
          data-envio-id="${envioId}"
          data-editing="0"
          data-orig-cajas="${nint(data.cajas)}"
          data-orig-estatus="${estatus}">
        <td class="text-start">
          <input type="hidden" class="pt_destino_id" value="${destinoId}">
          <div class="position-relative">
            <input type="text"
              class="form-control form-control-sm pt_destino_txt"
              placeholder="Escribe ciudad... (Ej. TIJ)"
              autocomplete="off"
              value="${destinoTxt}"
              ${ro}
              required>
            <div class="list-group position-absolute w-100 z-3 pt_destino_sug"
              style="z-index:999; display:none;"></div>
          </div>
        </td>

        <td class="text-center">
          <input type="date" class="form-control form-control-sm pt_fecha_envio" value="${fecha}" ${ro} required>
        </td>

        <td class="text-start">
          <input type="hidden" class="pt_fisico_id" value="${fisicoId}">
          <div class="position-relative">
            <input type="text"
              class="form-control form-control-sm pt_fisico_txt"
              placeholder="Buscar Caja/Ferro"
              autocomplete="off"
              value="${fisicoTxt}"
              ${ro}
              required>
            <div class="list-group position-absolute w-100 z-3 pt_fisico_sug"
              style="z-index:999; display:none;"></div>
          </div>
        </td>

        <td class="text-center">
          <div class="input-group input-group-sm">
            <input type="number" min="1" step="1"
              class="form-control pt_cajas"
              placeholder="0"
              value="${cajas}"
              ${ro}
              required>
          </div>
        </td>

        <td class="text-center">
          <select class="form-select form-control pt_estatus" ${dis} required>
            <option value="1" ${estatus === 1 ? "selected" : ""}>En camino</option>
            <option value="2" ${estatus === 2 ? "selected" : ""}>Entregado</option>
          </select>
        </td>

        <td class="text-start">
          <input type="text"
            class="form-control form-control-sm pt_nota"
            placeholder="Nota (opcional)"
            maxlength="255"
            value="${nota}"
            ${ro}>
        </td>

        <td class="text-center">
          ${btnAccion}
        </td>
      </tr>
    `;
  }

  function renderTablaEnviosMixta(enviosExistentes) {
    if (!tbodyEnvios) return;

    let html = "";

    (enviosExistentes || []).forEach((e) => {
      html += rowHtmlBase({
        envioId: e.id_envio,
        destinoId: e.ciudad_destino_id,
        destinoTxt: e.destino || "",
        fecha: e.fecha_envio,
        fisicoId: e.id_fisico,
        fisicoTxt: e.ferro || "",
        cajas: e.cajas_enviadas,
        estatus: e.estatus,
        nota: e.notas,
        esExistente: true,
      });
    });

    // 1 renglón vacío para alta
    html += rowHtmlBase({
      envioId: 0,
      destinoId: "",
      destinoTxt: "",
      fecha: "",
      fisicoId: "",
      fisicoTxt: "",
      cajas: "",
      estatus: 1,
      nota: "",
      esExistente: false,
    });

    tbodyEnvios.innerHTML = html;

    try { if (typeof feather !== "undefined") feather.replace(); } catch (_) {}

    reindexRows();
    updateResumenUI();
  }

  function listarEnviosExistentesModal() {
    const facturaId = nint(hidFacturaId2?.value);
    const productoId = nint(hidProductoId?.value);

    if (facturaId <= 0 || productoId <= 0) {
      renderTablaEnviosMixta([]);
      return;
    }

    const url =
      buildUrl(EP_LISTAR_ENVIOS) +
      "?factura_id=" + encodeURIComponent(facturaId) +
      "&producto_id=" + encodeURIComponent(productoId);

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status < 200 || xhr.status >= 300) {
        renderTablaEnviosMixta([]);
        return;
      }

      let json = null;
      try { json = JSON.parse(xhr.responseText || "{}"); } catch (_) {}

      if (!json || json.ok !== true) {
        renderTablaEnviosMixta([]);
        return;
      }

      renderTablaEnviosMixta(json.data || []);
    };
    xhr.send();
  }

  // =========================
  // Agregar renglón nuevo
  // =========================
  function addEnvioRow() {
    if (!tbodyEnvios) return;

    const html = rowHtmlBase({
      envioId: 0,
      destinoId: "",
      destinoTxt: "",
      fecha: "",
      fisicoId: "",
      fisicoTxt: "",
      cajas: "",
      estatus: 1,
      nota: "",
      esExistente: false,
    });

    tbodyEnvios.insertAdjacentHTML("beforeend", html);

    reindexRows();

    try { if (typeof feather !== "undefined") feather.replace(); } catch (_) {}

    const rows = getRows();
    const last = rows[rows.length - 1];
    last?.querySelector(".pt_destino_txt")?.focus();

    updateResumenUI();
  }

  // =========================
  // Guardar: NUEVOS + EDITADOS (UPSERT)
  // =========================
  function collectPayloadOrThrow() {
    const facturaId = nint(hidFacturaId2?.value);
    const productoId = nint(hidProductoId?.value);

    if (facturaId <= 0 || productoId <= 0) {
      throw new Error("Factura/Producto inválidos. Abre el modal desde un producto válido.");
    }

    const envios = [];
    const rows = getRows();
    if (rows.length === 0) throw new Error("No hay renglones para guardar.");

    rows.forEach((row, idx) => {
      const envioId = nint(row.getAttribute("data-envio-id"));
      const isExistente = envioId > 0;
      const isEditing = row.getAttribute("data-editing") === "1";

      // existente no editado => no mandar
      if (isExistente && !isEditing) return;

      const destinoId = nint(row.querySelector(".pt_destino_id")?.value);
      const destinoTxt = String(row.querySelector(".pt_destino_txt")?.value || "").trim();

      const fecha = String(row.querySelector(".pt_fecha_envio")?.value || "").trim();

      const fisicoId = nint(row.querySelector(".pt_fisico_id")?.value);
      const fisicoTxt = String(row.querySelector(".pt_fisico_txt")?.value || "").trim();

      const cajas = nint(row.querySelector(".pt_cajas")?.value);
      const estatus = nint(row.querySelector(".pt_estatus")?.value) || 1;
      const nota = String(row.querySelector(".pt_nota")?.value || "").trim();

      // nuevo: permitir renglón completamente vacío
      const rowIsBlank =
        !isExistente &&
        !destinoId &&
        !destinoTxt &&
        !fecha &&
        !fisicoId &&
        !fisicoTxt &&
        !cajas &&
        !nota;

      if (rowIsBlank) return;

      // Validaciones (aplican a nuevo y a editado)
      if (destinoId <= 0) {
        throw new Error(`Renglón #${idx + 1}: selecciona un destino (ciudad) de las sugerencias.`);
      }
      if (!fecha) {
        throw new Error(`Renglón #${idx + 1}: fecha de envío requerida.`);
      }
      if (cajas <= 0) {
        throw new Error(`Renglón #${idx + 1}: cajas a enviar inválidas.`);
      }
      if (fisicoId <= 0 && !fisicoTxt) {
        throw new Error(`Renglón #${idx + 1}: Caja/Ferro requerido.`);
      }

      envios.push({
        id_envio: envioId, // 0 = nuevo, >0 = update
        destino_id: destinoId,
        fecha_envio: fecha,
        id_fisico: fisicoId,
        fisico_txt: fisicoTxt,
        cajas: cajas,
        estatus: estatus === 2 ? 2 : 1,
        notas: nota,
      });
    });

    if (envios.length === 0) {
      throw new Error("No hay renglones (nuevos o editados) para guardar.");
    }

    // Validación por DELTA (nuevos + editados)
    const disponibles = getDisponiblesOriginal();
    const delta = calcDeltaAsignado();
    if (delta > disponibles) {
      throw new Error(`No puedes asignar ${delta} cajas. Disponibles: ${disponibles}.`);
    }

    return { factura_id: facturaId, producto_id: productoId, envios };
  }

  function guardar() {
    let payload;
    try {
      payload = collectPayloadOrThrow();
    } catch (err) {
      safeFire({ icon: "warning", title: "Validación", text: err.message });
      return;
    }

    abortXHR(xhrGuardar);
    xhrGuardar = new XMLHttpRequest();

    const url = buildUrl(EP_GUARDAR_ENVIOS);
    xhrGuardar.open("POST", url, true);
    xhrGuardar.setRequestHeader("Content-Type", "application/json; charset=utf-8");

    const oldHtml = btnGuardar ? btnGuardar.innerHTML : "";
    if (btnGuardar) {
      btnGuardar.disabled = true;
      btnGuardar.innerHTML = "Guardando...";
    }

    xhrGuardar.onreadystatechange = function () {
      if (xhrGuardar.readyState !== 4) return;

      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = oldHtml;
      }

      if (xhrGuardar.status < 200 || xhrGuardar.status >= 300) {
        safeFire({
          icon: "error",
          title: "Error",
          text: "No se pudo guardar (HTTP " + xhrGuardar.status + ").",
        });
        return;
      }

      let json = null;
      try { json = JSON.parse(xhrGuardar.responseText || "{}"); } catch (_) {}

      if (!json || json.ok !== true) {
        safeFire({
          icon: "warning",
          title: "No se guardó",
          text: json && json.msg ? json.msg : "Respuesta inválida.",
        });
        return;
      }

      safeFire({
        icon: "success",
        title: "Guardado",
        text: json.msg || "Envíos guardados correctamente.",
      }).then(() => {
        // Re-lista existentes (ya incluye updates + inserts)
        listarEnviosExistentesModal();

        // refresca tabla de productos (restantes/enviadas)
        if (btnRefrescar) btnRefrescar.click();
      });
    };

    xhrGuardar.send(JSON.stringify(payload));
  }

  // =========================
  // Baja lógica (existente)
  // =========================
  function bajaEnvio(tr) {
    const envioId = nint(tr?.getAttribute("data-envio-id"));
    if (envioId <= 0) return;

    const facturaId = nint(hidFacturaId2?.value);
    const productoId = nint(hidProductoId?.value);

    safeFire({
      icon: "warning",
      title: "¿Dar de baja este envío?",
      text: "Se marcará como inactivo (no se borra).",
      showCancelButton: true,
      confirmButtonText: "Sí, dar de baja",
      cancelButtonText: "Cancelar",
    }).then((r) => {
      if (!r.isConfirmed) return;

      const xhr = new XMLHttpRequest();
      xhr.open("POST", buildUrl(EP_BAJA_ENVIO), true);
      xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8");

      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;

        let json = null;
        try { json = JSON.parse(xhr.responseText || "{}"); } catch (_) {}

        if (xhr.status >= 200 && xhr.status < 300 && json && json.ok === true) {
          tr.remove();
          reindexRows();
          updateResumenUI();

          safeFire({ icon: "success", title: "Listo", text: json.msg || "Envío dado de baja." })
            .then(() => { if (btnRefrescar) btnRefrescar.click(); });

        } else {
          safeFire({ icon: "error", title: "Error", text: (json && json.msg) ? json.msg : "No se pudo dar de baja." });
        }
      };

      xhr.send(JSON.stringify({ id_envio: envioId, factura_id: facturaId, producto_id: productoId }));
    });
  }

  // =========================
  // Eventos
  // =========================
  function bind() {
    if (!modalEnvioEl || !tbodyEnvios) return;

    modalEnvioEl.addEventListener("shown.bs.modal", function () {
      // El catálogo setea lblRestantes cuando abre modal
      const rest = nint(lblRestantes?.textContent);
      setDisponiblesOriginal(rest);

      // Listar existentes y armar tabla mixta
      listarEnviosExistentesModal();
    });

    modalEnvioEl.addEventListener("input", function (e) {
      const t = e.target;
      if (!t) return;

      // si cambia cajas/estatus en renglón nuevo o editable => recalcula
      if (t.classList.contains("pt_cajas") || t.classList.contains("pt_estatus")) {
        updateResumenUI();
      }
    });

    modalEnvioEl.addEventListener("click", function (e) {
      // Quitar renglón (solo NUEVO)
      const btnRemove = e.target?.closest(".pt_btnRemoveRow");
      if (btnRemove) {
        const tr = btnRemove.closest("tr");
        if (!tr) return;

        const envioId = nint(tr.getAttribute("data-envio-id"));
        if (envioId > 0) return; // existente -> no aplica

        tr.remove();

        const rows = getRows();
        if (rows.length === 0) {
          addEnvioRow();
          return;
        }

        reindexRows();
        updateResumenUI();
        return;
      }

      // Editar existente (toggle)
      const btnEdit = e.target?.closest(".pt_btnEditarEnvio");
      if (btnEdit) {
        const tr = btnEdit.closest("tr");
        if (!tr) return;

        const cur = tr.getAttribute("data-editing") === "1";
        setRowEditable(tr, !cur);

        if (!cur) tr.querySelector(".pt_destino_txt")?.focus();

        updateResumenUI();
        return;
      }

      // Baja existente
      const btnBaja = e.target?.closest(".pt_btnBajaEnvio");
      if (btnBaja) {
        const tr = btnBaja.closest("tr");
        if (!tr) return;
        bajaEnvio(tr);
        return;
      }
    });

    if (btnGuardar) {
      btnGuardar.addEventListener("click", guardar);
    }

    if (btnAddRow) {
      btnAddRow.addEventListener("click", function () {
        addEnvioRow();
      });
    }

    modalEnvioEl.addEventListener("hidden.bs.modal", function () {
      setDisponiblesOriginal(0);
      if (lblTotalAsignado) lblTotalAsignado.textContent = "0";
      // Re-render siempre al abrir, así que no ocupas reset manual de renglones
    });
  }

  bind();
})();
