// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_rutas_registrar.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const EP_GUARDAR_ENVIOS = "Operaciones_por_partida_rutas/guardarEnviosRutas";
  const EP_LISTAR_ENVIOS =
    "Operaciones_por_partida_rutas/listarEnviosProductoRutas";

  // ===== Refs DOM (Modal) =====
  const modalEnvioEl = document.getElementById("modalPartidasTransitoEnvio");
  const btnGuardar = document.getElementById(
    "partidas_transito_btnGuardarEnvio",
  );

  const hidProductoId = document.getElementById("partidas_transito_idProducto");
  const hidFacturaId2 = document.getElementById("partidas_transito_factura");

  const badgeDisponibles = document.getElementById(
    "partidas_transito_badgeDisponibles",
  );
  const hidDisponibles = document.getElementById(
    "partidas_transito_cajasDisponibles",
  );

  const lblTotalAsignado = document.getElementById(
    "partidas_transito_lblTotalAsignado",
  );
  const lblRestantes = document.getElementById(
    "partidas_transito_lblRestantes",
  );

  const tbodyEnvios = document.getElementById("partidas_transito_tbodyEnvios");
  const btnAddRow = document.getElementById("partidas_transito_btnAddRow");

  // Refrescar tabla de productos (lo usa tu catálogo)
  const btnRefrescar = document.getElementById(
    "partidas_transito_btnRefrescar",
  );

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
    if (
      typeof Swal !== "undefined" &&
      Swal &&
      typeof Swal.fire === "function"
    ) {
      return Swal.fire(opts);
    }
    alert(
      (opts.title ? opts.title + "\n" : "") + (opts.text || opts.html || ""),
    );
    return Promise.resolve();
  }

  function abortXHR(x) {
    try {
      if (x && x.readyState !== 4) x.abort();
    } catch (_) {}
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
    return Array.from(
      tbodyEnvios?.querySelectorAll(".partidas_transito_row") || [],
    );
  }

  // Solo suma CAJAS de renglones NUEVOS (no existentes)
  function calcTotalAsignadoNuevos() {
    let sum = 0;
    getRows().forEach((row) => {
      const envioId = nint(row.getAttribute("data-envio-id"));
      if (envioId > 0) return; // existente -> NO cuenta contra restantes
      const cajas = nint(row.querySelector(".pt_cajas")?.value);
      if (cajas > 0) sum += cajas;
    });
    return sum;
  }

  function updateResumenUI() {
    const disponibles = getDisponiblesOriginal(); // este ya es "restantes en bodega"
    const asignadoNuevos = calcTotalAsignadoNuevos();
    const restantes = Math.max(0, disponibles - asignadoNuevos);

    if (lblTotalAsignado) lblTotalAsignado.textContent = String(asignadoNuevos);
    if (lblRestantes) lblRestantes.textContent = String(restantes);
  }

  function reindexRows() {
    const rows = getRows();
    rows.forEach((row, i) => row.setAttribute("data-index", String(i)));
  }

  function addEnvioRow() {
    if (!tbodyEnvios) return;

    // Crea un renglón NUEVO usando tu plantilla (no existente)
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

    // Re-render feather (para el ícono trash del nuevo row)
    try {
      if (typeof feather !== "undefined") feather.replace();
    } catch (_) {}

    // UX: enfocar el destino del renglón recién agregado
    const rows = getRows();
    const last = rows[rows.length - 1];
    const inp = last?.querySelector(".pt_destino_txt");
    if (inp) inp.focus();

    // Recalcular resumen (por si luego empiezan a capturar)
    updateResumenUI();
  }

  // =========================
  // Plantillas de renglón
  // =========================
  function rowHtmlBase(data) {
    // data: { envioId, destinoId, destinoTxt, fecha, fisicoId, fisicoTxt, cajas, estatus, nota, esExistente }
    const envioId = nint(data.envioId);
    const destinoId = nint(data.destinoId);
    const fisicoId = nint(data.fisicoId);
    const cajas = data.cajas !== undefined ? nint(data.cajas) : "";
    const estatus = nint(data.estatus) || 1;

    const destinoTxt = escHtml(data.destinoTxt || "");
    const fisicoTxt = escHtml(data.fisicoTxt || "");
    const fecha = escHtml((data.fecha || "").toString().slice(0, 10));
    const nota = escHtml(data.nota || "");

    const esExistente = !!data.esExistente;

    // Si es existente, ponemos un badge/estilo suave (congruente visual)
    const trClass = esExistente ? "table-light" : "";

    // Botón: existente -> (por ahora) no lo quitas del DOM, sino que luego será Edit/Baja.
    // nuevo -> sí se puede quitar del DOM.
    const btnAccion = esExistente
      ? `
        <button type="button" class="btn btn-outline-warning btn-sm pt_btnEditarEnvio" data-id="${envioId}" title="Editar (pendiente)">
          <i data-feather="edit-2"></i>
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm pt_btnBajaEnvio" data-id="${envioId}" title="Baja (pendiente)">
          <i data-feather="trash-2"></i>
        </button>
      `
      : `
        <button type="button" class="btn btn-outline-danger btn-sm pt_btnRemoveRow" title="Quitar renglón">
          <i data-feather="trash-2"></i>
        </button>
      `;

    return `
      <tr class="partidas_transito_row ${trClass}" data-index="0" data-envio-id="${envioId}">
        <td class="text-start">
          <input type="hidden" class="pt_destino_id" value="${destinoId}">
          <div class="position-relative">
            <input type="text"
              class="form-control form-control-sm pt_destino_txt"
              placeholder="Escribe ciudad... (Ej. TIJ)"
              autocomplete="off"
              value="${destinoTxt}"
              ${esExistente ? "readonly" : "required"}>
            <div class="list-group position-absolute w-100 z-3 pt_destino_sug"
              style="z-index:999; display:none;"></div>
          </div>
        </td>

        <td class="text-center">
          <input type="date" class="form-control form-control-sm pt_fecha_envio" value="${fecha}" ${esExistente ? "readonly" : "required"}>
        </td>

        <td class="text-start">
          <input type="hidden" class="pt_fisico_id" value="${fisicoId}">
          <div class="position-relative">
            <input type="text"
              class="form-control form-control-sm pt_fisico_txt"
              placeholder="Buscar Caja/Ferro"
              autocomplete="off"
              value="${fisicoTxt}"
              ${esExistente ? "readonly" : "required"}>
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
              ${esExistente ? "readonly" : "required"}>
          </div>
        </td>

        <td class="text-center">
          <select class="form-select form-control pt_estatus" ${esExistente ? "disabled" : "required"}>
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
            ${esExistente ? "readonly" : ""}>
        </td>

        <td class="text-center">
          ${btnAccion}
        </td>
      </tr>
    `;
  }

  function renderTablaEnviosMixta(enviosExistentes) {
    if (!tbodyEnvios) return;

    // 1) Renglones existentes (readonly por ahora)
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

    // 2) Siempre dejar 1 renglón vacío para alta (congruente con tu flujo)
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

    // Feather refresh
    try {
      if (typeof feather !== "undefined") feather.replace();
    } catch (_) {}

    reindexRows();
    updateResumenUI();
  }

  function listarEnviosExistentesModal() {
    const facturaId = nint(hidFacturaId2?.value);
    const productoId = nint(hidProductoId?.value);

    if (facturaId <= 0 || productoId <= 0) {
      renderTablaEnviosMixta([]);
      updateResumenUI();
      return;
    }

    const url =
      buildUrl(EP_LISTAR_ENVIOS) +
      "?factura_id=" +
      encodeURIComponent(facturaId) +
      "&producto_id=" +
      encodeURIComponent(productoId);

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status < 200 || xhr.status >= 300) {
        renderTablaEnviosMixta([]);
        updateResumenUI();
        return;
      }

      let json = null;
      try {
        json = JSON.parse(xhr.responseText || "{}");
      } catch (_) {}

      if (!json || json.ok !== true) {
        renderTablaEnviosMixta([]);
        updateResumenUI();
        return;
      }

      renderTablaEnviosMixta(json.data || []);
      updateResumenUI();
    };
    xhr.send();
  }

  // =========================
  // Guardar: SOLO NUEVOS
  // =========================
  function collectPayloadOrThrow() {
    const facturaId = nint(hidFacturaId2?.value);
    const productoId = nint(hidProductoId?.value);

    if (facturaId <= 0 || productoId <= 0) {
      throw new Error(
        "Factura/Producto inválidos. Abre el modal desde un producto válido.",
      );
    }

    const envios = [];
    const rows = getRows();
    if (rows.length === 0) throw new Error("No hay renglones para guardar.");

    rows.forEach((row, idx) => {
      const envioId = nint(row.getAttribute("data-envio-id"));
      if (envioId > 0) return; // EXISTENTE: no se manda a guardar

      const destinoId = nint(row.querySelector(".pt_destino_id")?.value);
      const destinoTxt = String(
        row.querySelector(".pt_destino_txt")?.value || "",
      ).trim();

      const fecha = String(
        row.querySelector(".pt_fecha_envio")?.value || "",
      ).trim();

      const fisicoId = nint(row.querySelector(".pt_fisico_id")?.value);
      const fisicoTxt = String(
        row.querySelector(".pt_fisico_txt")?.value || "",
      ).trim();

      const cajas = nint(row.querySelector(".pt_cajas")?.value);
      const estatus = nint(row.querySelector(".pt_estatus")?.value) || 1;
      const nota = String(row.querySelector(".pt_nota")?.value || "").trim();

      const rowIsBlank =
        !destinoId &&
        !destinoTxt &&
        !fecha &&
        !fisicoId &&
        !fisicoTxt &&
        !cajas &&
        !nota;

      if (rowIsBlank) return;

      if (destinoId <= 0)
        throw new Error(
          `Renglón #${idx + 1}: selecciona un destino (ciudad) de las sugerencias.`,
        );
      if (!fecha)
        throw new Error(`Renglón #${idx + 1}: fecha de envío requerida.`);
      if (cajas <= 0)
        throw new Error(`Renglón #${idx + 1}: cajas a enviar inválidas.`);
      if (fisicoId <= 0 && !fisicoTxt)
        throw new Error(`Renglón #${idx + 1}: Caja/Ferro requerido.`);

      envios.push({
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
      throw new Error("No hay renglones nuevos para guardar.");
    }

    const disponibles = getDisponiblesOriginal();
    const totalNuevo = envios.reduce((a, r) => a + nint(r.cajas), 0);

    if (totalNuevo > disponibles) {
      throw new Error(
        `No puedes enviar ${totalNuevo} cajas. Disponibles: ${disponibles}.`,
      );
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
    xhrGuardar.setRequestHeader(
      "Content-Type",
      "application/json; charset=utf-8",
    );

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
      try {
        json = JSON.parse(xhrGuardar.responseText || "{}");
      } catch (_) {}

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
        // Refrescar: vuelve a listar existentes (ya incluye lo nuevo) y deja renglón vacío
        listarEnviosExistentesModal();

        // refresca tabla de productos
        if (btnRefrescar) btnRefrescar.click();
      });
    };

    xhrGuardar.send(JSON.stringify(payload));
  }

  // =========================
  // Eventos
  // =========================
  function bind() {
    if (!modalEnvioEl || !tbodyEnvios) return;

    modalEnvioEl.addEventListener("shown.bs.modal", function () {
      // Tu catálogo setea lblRestantes cuando listar productos y abrir modal.
      const rest = nint(lblRestantes?.textContent);
      setDisponiblesOriginal(rest);

      // Ahora listamos existentes y armamos tabla mixta
      listarEnviosExistentesModal();
    });

    modalEnvioEl.addEventListener("input", function (e) {
      const t = e.target;
      if (!t) return;
      if (t.classList.contains("pt_cajas")) updateResumenUI();
    });

modalEnvioEl.addEventListener("click", function (e) {
  const btnRemove = e.target?.closest(".pt_btnRemoveRow");
  if (btnRemove) {
    const tr = btnRemove.closest("tr");
    if (!tr) return;

    // ✅ SOLO aplica a renglones NUEVOS (envio-id = 0)
    const envioId = nint(tr.getAttribute("data-envio-id"));
    if (envioId > 0) return; // existente -> no lo toques con este botón

    // ✅ Elimina el renglón
    tr.remove();

    // ✅ Si ya no quedan renglones, deja 1 vacío (template)
    const rows = getRows();
    if (rows.length === 0) {
      addEnvioRow(); // ya reindexa + updateResumenUI internamente
      return;
    }

    // ✅ Reindex + resumen
    reindexRows();
    updateResumenUI();
    return;
  }

  // (Opcional por ahora) handlers de editar/baja:
  // const btnEdit = e.target?.closest(".pt_btnEditarEnvio");
  // const btnBaja = e.target?.closest(".pt_btnBajaEnvio");
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
      // No hace falta resetRowsUI porque al abrir siempre re-renderizamos.
    });
  }

  bind();
})();
