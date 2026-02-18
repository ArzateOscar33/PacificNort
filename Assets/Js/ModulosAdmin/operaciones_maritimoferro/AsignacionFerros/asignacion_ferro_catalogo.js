// Assets/Js/ModulosAdmin/operaciones_maritimoferro/asignacion_ferro_modal_listar.js
(function () {
  "use strict";

  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  // === ENDPOINTS (los que ya tienes en tu controlador) ===
  const EP_LISTAR_FERROS =
    BASE_URL +
    "Operaciones_maritimo_ferro_asignacion_ferro/listarFerrosOperacion";
  const EP_LISTAR_OPS =
    BASE_URL + "Operaciones_maritimo_ferro_asignacion_ferro/listarOpsEnFerro";

  // === Refs modal (IDs reales de tu HTML) ===
  const modalEl = document.getElementById("modalAsignarFerroCaja");
  if (!modalEl) return;

  const hidOperacionId = document.getElementById("asigFerro_operacionId");
  const hidOperacionCodigo = document.getElementById(
  );

  const badgeCodigo = document.getElementById("asigFerro_badgeCodigo");
  const badgeFerroSel = document.getElementById("asigFerro_badgeFerroSel");

  const tbFerrosOperacion = document.getElementById(
    "asigFerro_tbFerrosOperacion",
  );
  const tbOpsEnFerro = document.getElementById("asigFerro_tbOpsEnFerro");

  const elCountFerros = document.getElementById("asigFerro_countFerros");

  let currentOperacionId = 0;
  let currentOperacionCodigo = "";

  // ===== Helpers =====
  const safe = (v) => (v === undefined || v === null ? "" : String(v));

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

  function renderLoadingLeft() {
    if (!tbFerrosOperacion) return;
    tbFerrosOperacion.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Cargando...</td></tr>`;
    if (elCountFerros) elCountFerros.textContent = "0";
  }

  function renderEmptyLeft(msg) {
    if (!tbFerrosOperacion) return;
    tbFerrosOperacion.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">${msg || "Sin vínculos todavía."}</td></tr>`;
    if (elCountFerros) elCountFerros.textContent = "0";
  }

  function renderLoadingRight() {
    if (!tbOpsEnFerro) return;
    tbOpsEnFerro.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Cargando...</td></tr>`;
  }

  function renderEmptyRight(msg) {
    if (!tbOpsEnFerro) return;
    tbOpsEnFerro.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">${msg || "Selecciona un Ferro/Caja de la lista izquierda."}</td></tr>`;
  }

  // ===== API: listar ferros de operación (izquierda) =====
  function cargarFerrosDeOperacion(operacionId) {
    if (!operacionId) return;

    renderLoadingLeft();
    renderEmptyRight("Selecciona un Ferro/Caja de la lista izquierda.");
    if (badgeFerroSel) badgeFerroSel.textContent = "—";

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

      // Tu query devuelve:
      // fo_id, numero_ferro, fecha, destino_nombre, transportista_nombre, bultos
      tbFerrosOperacion.innerHTML = "";

      rows.forEach((r) => {
        const numero = safe(r.numero_ferro);
        const fecha = safe(r.fecha);
        const bultos = safe(r.bultos);
        const destino = safe(r.destino_nombre) || "—";

        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.style.cursor = "pointer";

        // Guardamos ferro+fecha en dataset para cargar el lado derecho
        tr.dataset.numeroFerro = numero;
        tr.dataset.fecha = fecha;

        tr.innerHTML = `
          <td class="text-start">
            <div class="fw-semibold">${numero}</div>
            <div class="small text-muted">Destino: ${destino}</div>
          </td>
          <td>${bultos}</td>
          <td class="text-nowrap">${fecha}</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary asigFerro_btnVerOps">
              Ver
            </button>
          </td>
        `;

        tbFerrosOperacion.appendChild(tr);
      });
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

      // Tu query devuelve:
      // id_operacion, codigo, cliente, eta, subtipo
      tbOpsEnFerro.innerHTML = "";
      rows.forEach((r) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td class="text-nowrap">${safe(r.codigo)}</td>
          <td class="text-start">${safe(r.cliente) || "—"}</td>
          <td class="text-nowrap">${safe(r.eta) || "—"}</td>
          <td class="text-nowrap">${safe(r.subtipo) || "—"}</td>
        `;
        tbOpsEnFerro.appendChild(tr);
      });
    });
  }

  // ===== Eventos =====

  // 1) Al abrir modal: toma id/código desde el botón que lo abrió
  modalEl.addEventListener("show.bs.modal", (ev) => {
    const btn = ev.relatedTarget; // botón "Caja/Ferro" desde la tabla MF
    currentOperacionId = Number(btn?.getAttribute("data-id") || 0);
    currentOperacionCodigo = String(btn?.getAttribute("data-codigo") || "");

    if (hidOperacionId) hidOperacionId.value = String(currentOperacionId || "");
    if (hidOperacionCodigo)
      hidOperacionCodigo.value = String(currentOperacionCodigo || "");

    if (badgeCodigo) badgeCodigo.textContent = currentOperacionCodigo || "—";

    // Carga izquierda
    cargarFerrosDeOperacion(currentOperacionId);
  });

  // 2) Click en una fila izquierda o botón "Ver": carga derecha
  tbFerrosOperacion?.addEventListener("click", (e) => {
    const row = e.target.closest("tr");
    if (!row) return;

    const numeroFerro = row.dataset.numeroFerro || "";
    const fecha = row.dataset.fecha || "";

    // resalta seleccionado (visual)
    Array.from(tbFerrosOperacion.querySelectorAll("tr")).forEach((tr) =>
      tr.classList.remove("table-active"),
    );
    row.classList.add("table-active");

    cargarOperacionesEnFerro(numeroFerro, fecha);
  });

  // Exponer para debug
  window.MFAsignacionFerroModal = {
    refresh: () => cargarFerrosDeOperacion(currentOperacionId),
    getOperacionId: () => currentOperacionId,
  };
})();
