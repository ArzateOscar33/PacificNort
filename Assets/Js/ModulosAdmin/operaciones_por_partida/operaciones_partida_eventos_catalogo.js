/* ===============================================================
   Eventos Operaciones por Partida
   NUEVA LÓGICA TIPO EXCEL - MISMO ESTILO QUE EVENTOS TERRESTRES

   Funcionalidad:
   - Columnas dinámicas por tipo de evento terrestre.
   - Cada celda de evento se edita directamente.
   - Se ve igual que Eventos Terrestres usando:
     .evfer-date-cell
     .evfer-date-input
     .evfer-saving
     .evfer-saved
     .evfer-error
     .evfer-empty
   - Enter: guarda.
   - Tab: guarda y avanza a la derecha.
   - Shift + Tab: guarda y regresa a la izquierda.
   - Escape: cancela edición.
   - Delete / Backspace: limpia la celda.
   - Guarda en backend con Operaciones_por_partida_eventos/guardar_celda.
   =============================================================== */

(function evOpPartidaListPivotExcel() {
  "use strict";

  // =============================================================
  // Base URL
  // =============================================================
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // =============================================================
  // Endpoints
  // =============================================================
  const URL_LISTAR = base_url + "Operaciones_por_partida_eventos/listar";

  const URL_COLUMNAS =
    base_url + "Operaciones_por_partida_eventos/eventos_ferro_columnas";

  const URL_GUARDAR_CELDA =
    base_url + "Operaciones_por_partida_eventos/guardar_celda";

  // =============================================================
  // Referencias UI
  // =============================================================
  const theadRow = document.getElementById("theadEventosOpPartida");
  const tbody = document.getElementById("tbodyEventosOpPartida");
  const pagBox = document.getElementById("evOpPartidaPaginacion");
  const metaBox = document.getElementById("evOpPartidaMetaResumen");
  const perPageSel = document.getElementById("evOpPartidaPerPage");

  const filtroOpId = document.getElementById("eventosOpPartidaFiltroOpId");

  const filtroFactura = document.getElementById(
    "eventosOpPartidaFiltroFactura",
  );

  const filtroFerro = document.getElementById("eventosOpPartidaFiltroFerro");

  const filtroTransportista = document.getElementById(
    "eventosOpPartidaFiltroTransportista",
  );

  const filtroDestino = document.getElementById(
    "eventosOpPartidaFiltroDestino",
  );

  // =============================================================
  // Estado
  // =============================================================
  let COLS = [];
  let currentPage = 1;
  let perPage = parseInt(perPageSel?.value || "10", 10);
  let totalRows = 0;

  let activeEditCell = null;
  let activeEditInput = null;
  let activeEditOriginalValue = "";
  let isCommittingCell = false;

  // =============================================================
  // Utilidades HTTP
  // =============================================================
  function xhrGet(url, ok, err) {
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status >= 200 && this.status < 300) {
        try {
          ok && ok(JSON.parse(this.responseText || "null"));
        } catch (e) {
          console.error("JSON inválido GET:", this.responseText);
          err && err("JSON inválido");
        }
      } else {
        console.error("GET error:", this.status, this.responseText);
        err && err(this.responseText || "HTTP error");
      }
    };

    http.send();
  }

  function xhrPost(url, formData, ok, err) {
    const http = new XMLHttpRequest();
    http.open("POST", url, true);
    http.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let res = null;

      try {
        res = JSON.parse(this.responseText || "null");
      } catch (e) {
        res = null;
      }

      if (this.status >= 200 && this.status < 300) {
        ok && ok(res);
      } else {
        console.error("POST error:", this.status, this.responseText);
        err && err(res || this.responseText || "HTTP error");
      }
    };

    http.send(formData);
  }

  // =============================================================
  // Utilidades base
  // =============================================================
  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function debounce(fn, wait = 300) {
    let t = null;

    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function swalMsg(title, text, icon) {
    if (window.Swal) {
      Swal.fire(title, text, icon);
    } else {
      alert(text || title || "Listo");
    }
  }

  function toastMsg(title, icon = "success") {
    if (window.Swal) {
      Swal.fire({
        toast: true,
        position: "top-end",
        icon,
        title,
        showConfirmButton: false,
        timer: 1400,
        timerProgressBar: true,
      });
    }
  }

  // =============================================================
  // Fechas - igual que Eventos Terrestres
  // =============================================================
  function pad2(n) {
    return String(n).padStart(2, "0");
  }

  function isValidDateParts(y, m, d) {
    if (!y || !m || !d) return false;
    if (y < 1900 || y > 2100) return false;
    if (m < 1 || m > 12) return false;
    if (d < 1 || d > 31) return false;

    const dt = new Date(y, m - 1, d);

    return (
      dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d
    );
  }

  function normalizeDateToSQL(value) {
    let v = String(value || "").trim();

    if (!v || v === "-") return "";

    v = v.replace(/\s+/g, "");

    // yyyy-mm-dd
    let m = v.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) {
      const y = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10);
      const d = parseInt(m[3], 10);

      if (!isValidDateParts(y, mo, d)) return null;

      return `${y}-${pad2(mo)}-${pad2(d)}`;
    }

    // yyyy/mm/dd
    m = v.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
    if (m) {
      const y = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10);
      const d = parseInt(m[3], 10);

      if (!isValidDateParts(y, mo, d)) return null;

      return `${y}-${pad2(mo)}-${pad2(d)}`;
    }

    // dd/mm/yyyy o dd-mm-yyyy
    m = v.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/);
    if (m) {
      const d = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10);
      const y = parseInt(m[3], 10);

      if (!isValidDateParts(y, mo, d)) return null;

      return `${y}-${pad2(mo)}-${pad2(d)}`;
    }

    // ddmmyyyy
    m = v.match(/^(\d{2})(\d{2})(\d{4})$/);
    if (m) {
      const d = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10);
      const y = parseInt(m[3], 10);

      if (!isValidDateParts(y, mo, d)) return null;

      return `${y}-${pad2(mo)}-${pad2(d)}`;
    }

    // yyyymmdd
    m = v.match(/^(\d{4})(\d{2})(\d{2})$/);
    if (m) {
      const y = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10);
      const d = parseInt(m[3], 10);

      if (!isValidDateParts(y, mo, d)) return null;

      return `${y}-${pad2(mo)}-${pad2(d)}`;
    }

    return null;
  }

  function formatDateForDisplay(value) {
    const sql = normalizeDateToSQL(value);

    if (!sql) return "";

    const parts = sql.split("-");
    if (parts.length !== 3) return "";

    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }

  function formatDateForInput(value) {
    const sql = normalizeDateToSQL(value);

    if (!sql) return "";

    const parts = sql.split("-");
    if (parts.length !== 3) return "";

    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }

  // =============================================================
  // Encabezado dinámico
  // =============================================================
  function buildHead() {
    if (!theadRow) return;

    theadRow.innerHTML = `
      <th class="text-center">Operación</th>
      <th class="text-center">Factura</th>
      <th class="text-center">Cliente</th>
      <th class="text-center">Destino</th>
      <th class="text-center">Transportista</th>
      <th class="text-center">Caja / Ferro</th>
    `;

    for (const c of COLS) {
      const th = document.createElement("th");
      th.setAttribute("data-evt-id", c.id);
      th.textContent = c.nombre;
      th.className = "text-center";
      theadRow.appendChild(th);
    }
  }

  // =============================================================
  // Pivot de registros
  // =============================================================
  function pivotRows(rows) {
    const byEvtId = new Map(COLS.map((c) => [String(c.id), c]));
    const groups = new Map();

    (rows || []).forEach((r) => {
      const envioId = parseInt(
        r.envio_partida_id || r.operacion_ferro_id || r.op_id || 0,
        10,
      );

      const ferId = parseInt(r.contenedor_fisico_id || r.ferro_id || 0, 10);

      if (!envioId || !ferId) return;

      const key = `${envioId}||${ferId}`;

      if (!groups.has(key)) {
        const cells = {};

        for (const c of COLS) {
          cells[c.key || String(c.id)] = {
            id_evento: null,
            fecha: "",
            comentario: "",
            tipo_evento_id: c.id,
            evento: c.nombre,
          };
        }

        groups.set(key, {
          envio_partida_id: envioId,
          operacion_ferro_id: envioId, // alias para compatibilidad con controlador/modelo
          contenedor_fisico_id: ferId,

          operacion:
            r.operacion ||
            r.operacion_maritima ||
            r.numero_operacion ||
            `ENV-${envioId}`,

          factura:
            r.factura ||
            r.facturas ||
            r.numero_factura ||
            r.factura_folio ||
            "",

          cliente: r.cliente || r.nombre_cliente || "",
          destino: r.destino || r.nombre_destino || "",
          transportista: r.transportista || "",
          ferro: r.ferro || r.numero_ferro || "",

          cells,
        });
      }

      const g = groups.get(key);
      const c = byEvtId.get(String(r.tipo_evento_id || ""));

      if (c) {
        const cellKey = c.key || String(c.id);
        const currentCell = g.cells[cellKey];
        const newFecha = r.fecha || "";

        if (
          !currentCell.fecha ||
          String(newFecha) >= String(currentCell.fecha)
        ) {
          g.cells[cellKey] = {
            id_evento: r.id_evento || null,
            fecha: newFecha,
            comentario: r.comentario || "",
            tipo_evento_id: c.id,
            evento: c.nombre,
          };
        }
      }

      if (!g.operacion && (r.operacion || r.operacion_maritima)) {
        g.operacion = r.operacion || r.operacion_maritima;
      }

      if (!g.factura) {
        g.factura =
          r.factura || r.facturas || r.numero_factura || r.factura_folio || "";
      }

      if (!g.cliente && (r.cliente || r.nombre_cliente)) {
        g.cliente = r.cliente || r.nombre_cliente;
      }

      if (!g.destino && (r.destino || r.nombre_destino)) {
        g.destino = r.destino || r.nombre_destino;
      }

      if (!g.transportista && r.transportista) {
        g.transportista = r.transportista;
      }

      if (!g.ferro && (r.ferro || r.numero_ferro)) {
        g.ferro = r.ferro || r.numero_ferro;
      }
    });

    return Array.from(groups.values()).sort((a, b) => {
      const ao = String(a.operacion || "").localeCompare(
        String(b.operacion || ""),
      );

      if (ao !== 0) return ao;

      return String(a.ferro || "").localeCompare(String(b.ferro || ""));
    });
  }

  // =============================================================
  // Render cuerpo
  // =============================================================
  function renderBody(pivoted) {
    if (!tbody) return;

    tbody.innerHTML = "";

    const fixedCols = 6;

    if (!Array.isArray(pivoted) || pivoted.length === 0) {
      tbody.innerHTML = `<tr>
        <td colspan="${fixedCols + COLS.length}" class="text-center text-muted py-3">
          No hay registros
        </td>
      </tr>`;
      return;
    }

    for (const row of pivoted) {
      const tr = document.createElement("tr");

      tr.dataset.oplabel = row.operacion || "";
      tr.dataset.ctnlabel = row.ferro || "";

      let html = `
        <td class="text-center">${esc(row.operacion || "-")}</td>
        <td class="text-center">${esc(row.factura || "-")}</td>
        <td>${esc(row.cliente || "-")}</td>
        <td>${esc(row.destino || "-")}</td>
        <td>${esc(row.transportista || "-")}</td>
        <td class="text-center">${esc(row.ferro || "-")}</td>
      `;

      for (const c of COLS) {
        const cellKey = c.key || String(c.id);
        const cell = row.cells[cellKey] || {};
        const fechaSQL = cell.fecha || "";
        const fechaLabel = fechaSQL ? formatDateForDisplay(fechaSQL) : "";
        const emptyClass = fechaSQL ? "" : "evfer-empty";
        const idEvento = cell.id_evento || "";

        html += `
          <td class="text-center evfer-date-cell ${emptyClass}"
              tabindex="0"
              title="Clic para escribir fecha. Enter guarda. Tab guarda y avanza."
              data-envio-partida-id="${esc(row.envio_partida_id)}"
              data-operacion-ferro-id="${esc(row.operacion_ferro_id)}"
              data-contenedor-fisico-id="${esc(row.contenedor_fisico_id)}"
              data-tipo-evento-id="${esc(c.id)}"
              data-id-evento="${esc(idEvento)}"
              data-fecha="${esc(fechaSQL)}"
              data-comentario="${esc(cell.comentario || "")}"
              data-evento-nombre="${esc(c.nombre)}">
            ${fechaLabel ? esc(fechaLabel) : '<span class="text-muted">-</span>'}
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    }

    if (window.feather) feather.replace();
  }

  // =============================================================
  // Paginación / meta
  // =============================================================
  function renderPagination(page, total, perPageValue) {
    if (!pagBox) return;

    pagBox.innerHTML = "";

    const totalPages = Math.max(1, Math.ceil(total / perPageValue));

    if (totalPages <= 1) return;

    const mk = (p, label, disabled = false, active = false) => {
      const li = document.createElement("li");

      li.className = `page-item${disabled ? " disabled" : ""}${
        active ? " active" : ""
      }`;

      const a = document.createElement("a");
      a.className = "page-link";
      a.href = "#";
      a.innerHTML = label;

      if (!disabled && !active) {
        a.addEventListener("click", (e) => {
          e.preventDefault();
          currentPage = p;
          listar();
        });
      }

      li.appendChild(a);
      pagBox.appendChild(li);
    };

    mk(Math.max(1, page - 1), "&laquo;", page === 1, false);

    const win = 5;
    let s = Math.max(1, page - Math.floor(win / 2));
    let e = Math.min(totalPages, s + win - 1);

    if (e - s + 1 < win) {
      s = Math.max(1, e - win + 1);
    }

    for (let p = s; p <= e; p++) {
      mk(p, String(p), false, p === page);
    }

    mk(Math.min(totalPages, page + 1), "&raquo;", page === totalPages, false);
  }

  function renderMeta(page, total, perPageValue) {
    if (!metaBox) return;

    if (total === 0) {
      metaBox.textContent = "Mostrando 0–0 de 0";
      return;
    }

    const start = (page - 1) * perPageValue + 1;
    const end = Math.min(page * perPageValue, total);

    metaBox.textContent = `Mostrando ${start}–${end} de ${total}`;
  }

  // =============================================================
  // Filtros / query string
  // =============================================================
  function buildQueryString() {
    const params = new URLSearchParams();

    params.append("page", String(currentPage));
    params.append("per_page", String(perPage));

    const opId = (filtroOpId?.value || "").trim();
    const factura = (filtroFactura?.value || "").trim();
    const ferro = (filtroFerro?.value || "").trim();
    const transportistaId = (filtroTransportista?.value || "").trim();
    const destinoId = (filtroDestino?.value || "").trim();

    if (opId) params.append("op_id", opId);
    if (factura) params.append("factura", factura);
    if (ferro) params.append("ferro", ferro);
    if (transportistaId) params.append("transportista_id", transportistaId);
    if (destinoId) params.append("destino_id", destinoId);

    return params.toString();
  }

  function listar() {
    cancelarEdicionActiva(false);

    const url = `${URL_LISTAR}?${buildQueryString()}`;

    xhrGet(
      url,
      (res) => {
        const rows = Array.isArray(res?.rows)
          ? res.rows
          : Array.isArray(res?.data)
            ? res.data
            : [];

        const pivoted = pivotRows(rows);

        renderBody(pivoted);

        totalRows =
          res && typeof res.total !== "undefined"
            ? parseInt(res.total || 0, 10)
            : pivoted.length || 0;

        renderPagination(currentPage, totalRows, perPage);
        renderMeta(currentPage, totalRows, perPage);
      },
      (err) => {
        console.error("Listar eventos operación por partida:", err);

        const fixedCols = 6;

        if (tbody) {
          tbody.innerHTML = `<tr>
            <td colspan="${fixedCols + COLS.length}" class="text-center text-danger py-3">
              Error al obtener datos
            </td>
          </tr>`;
        }

        renderPagination(currentPage, 0, perPage);
        renderMeta(currentPage, 0, perPage);
      },
    );
  }

  window.refreshEventosOpPartida = function (opts = { keepPage: true }) {
    if (!opts.keepPage) currentPage = 1;
    listar();
  };

  // =============================================================
  // Edición tipo Excel - misma lógica que Eventos Terrestres
  // =============================================================
  function getCellTextValue(td) {
    return formatDateForInput(td?.dataset?.fecha || "");
  }

  function getAllEditableCells() {
    return Array.from(
      document.querySelectorAll("#tbodyEventosOpPartida .evfer-date-cell"),
    );
  }

  function getNextEditableCell(td, direction = 1) {
    const cells = getAllEditableCells();
    const idx = cells.indexOf(td);

    if (idx === -1) return null;

    const nextIdx = idx + direction;

    if (nextIdx < 0 || nextIdx >= cells.length) return null;

    return cells[nextIdx];
  }

  function setCellStatus(td, status, text = "") {
    if (!td) return;

    td.classList.remove("evfer-saving", "evfer-saved", "evfer-error");

    const oldStatus = td.querySelector(".evfer-cell-status");
    if (oldStatus) oldStatus.remove();

    if (!status) return;

    if (status === "saving") td.classList.add("evfer-saving");
    if (status === "saved") td.classList.add("evfer-saved");
    if (status === "error") td.classList.add("evfer-error");

    if (text) {
      const span = document.createElement("span");
      span.className = "evfer-cell-status";
      span.textContent = text;
      td.appendChild(span);
    }

    if (status === "saved") {
      setTimeout(() => {
        td.classList.remove("evfer-saved");

        const s = td.querySelector(".evfer-cell-status");
        if (s) s.remove();
      }, 900);
    }
  }

  function renderCellValue(td, fechaSQL) {
    const label = fechaSQL ? formatDateForDisplay(fechaSQL) : "";

    td.dataset.fecha = fechaSQL || "";

    if (fechaSQL) {
      td.classList.remove("evfer-empty");
      td.innerHTML = esc(label);
    } else {
      td.classList.add("evfer-empty");
      td.innerHTML = '<span class="text-muted">-</span>';
    }
  }

  function cancelarEdicionActiva(restoreOriginal = true) {
    if (!activeEditCell || !activeEditInput) {
      activeEditCell = null;
      activeEditInput = null;
      activeEditOriginalValue = "";
      isCommittingCell = false;
      return;
    }

    const td = activeEditCell;

    if (restoreOriginal) {
      renderCellValue(td, td.dataset.fecha || "");
    }

    activeEditCell = null;
    activeEditInput = null;
    activeEditOriginalValue = "";
    isCommittingCell = false;
  }

  function startEditCell(td, selectText = true) {
    if (!td || td.classList.contains("evfer-saving")) return;

    if (activeEditCell && activeEditCell !== td) {
      cancelarEdicionActiva(true);
    }

    if (activeEditCell === td) return;

    activeEditCell = td;
    activeEditOriginalValue = td.dataset.fecha || "";

    const inputValue = getCellTextValue(td);

    td.classList.remove("evfer-error", "evfer-saved");
    td.innerHTML = "";

    const input = document.createElement("input");
    input.type = "text";
    input.className = "evfer-date-input";
    input.value = inputValue;
    input.placeholder = "dd/mm/aaaa";
    input.autocomplete = "off";

    td.appendChild(input);

    activeEditInput = input;

    setTimeout(() => {
      input.focus();

      if (selectText) {
        input.select();
      }
    }, 0);

    input.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        commitActiveCell({ move: 0 });
        return;
      }

      if (e.key === "Tab") {
        e.preventDefault();
        commitActiveCell({ move: e.shiftKey ? -1 : 1 });
        return;
      }

      if (e.key === "Escape") {
        e.preventDefault();
        cancelarEdicionActiva(true);
        td.focus();
        return;
      }
    });

    input.addEventListener("blur", function () {
      setTimeout(() => {
        if (!activeEditInput || isCommittingCell) return;
        commitActiveCell({ move: 0, silentIfSame: true });
      }, 120);
    });
  }

  function commitActiveCell(options = {}) {
    if (!activeEditCell || !activeEditInput || isCommittingCell) return;

    const td = activeEditCell;
    const input = activeEditInput;

    const move = Number(options.move || 0);
    const silentIfSame = !!options.silentIfSame;

    const rawValue = String(input.value || "").trim();
    const normalized = normalizeDateToSQL(rawValue);

    if (normalized === null) {
      setCellStatus(td, "error", "!");
      input.focus();
      input.select();

      if (!silentIfSame) {
        toastMsg("Fecha inválida", "error");
      }

      return;
    }

    const oldValue = td.dataset.fecha || "";
    const newValue = normalized || "";

    if (oldValue === newValue) {
      renderCellValue(td, oldValue);

      activeEditCell = null;
      activeEditInput = null;
      activeEditOriginalValue = "";
      isCommittingCell = false;

      if (move !== 0) {
        const next = getNextEditableCell(td, move);

        if (next) {
          startEditCell(next, true);
        }
      } else {
        td.focus();
      }

      return;
    }

    isCommittingCell = true;

    guardarCeldaEnBackend(td, newValue, {
      onSuccess: (res) => {
        const fechaRespuesta = String(res?.fecha ?? newValue).trim();
        const fechaFinal = normalizeDateToSQL(fechaRespuesta) || "";

        td.dataset.idEvento = res?.id_evento || "";
        td.dataset.comentario = "";

        renderCellValue(td, fechaFinal);
        setCellStatus(td, "saved", "✓");

        activeEditCell = null;
        activeEditInput = null;
        activeEditOriginalValue = "";
        isCommittingCell = false;

        if (move !== 0) {
          const next = getNextEditableCell(td, move);

          if (next) {
            startEditCell(next, true);
          } else {
            td.focus();
          }
        } else {
          td.focus();
        }
      },
      onError: (msg) => {
        isCommittingCell = false;
        setCellStatus(td, "error", "!");

        td.innerHTML = "";
        td.appendChild(input);

        activeEditCell = td;
        activeEditInput = input;

        input.focus();
        input.select();

        toastMsg(msg || "No fue posible guardar la celda", "error");
      },
    });
  }

  function guardarCeldaEnBackend(td, fechaSQL, callbacks = {}) {
    const envioPartidaId =
      td.dataset.envioPartidaId || td.dataset.operacionFerroId || "";

    const opFerroId = td.dataset.operacionFerroId || envioPartidaId || "";

    const contenedorFisicoId = td.dataset.contenedorFisicoId || "";
    const tipoEventoId = td.dataset.tipoEventoId || "";
    const comentario = td.dataset.comentario || "";

    const fd = new FormData();

    /*
      Mandamos los dos:
      - envio_partida_id: nombre correcto del módulo por partida.
      - operacion_ferro_id: alias de compatibilidad que ya dejamos en controlador/modelo.
    */
    fd.append("envio_partida_id", envioPartidaId);
    fd.append("operacion_ferro_id", opFerroId);
    fd.append("contenedor_fisico_id", contenedorFisicoId);
    fd.append("tipo_evento_id", tipoEventoId);
    fd.append("fecha", fechaSQL || "");
    fd.append("comentario", comentario || "");

    setCellStatus(td, "saving", "…");

    xhrPost(
      URL_GUARDAR_CELDA,
      fd,
      (res) => {
        if (res && res.status === "success") {
          callbacks.onSuccess && callbacks.onSuccess(res);
          return;
        }

        callbacks.onError &&
          callbacks.onError(res?.msg || "No fue posible guardar la celda.");
      },
      (err) => {
        console.error("guardar_celda eventos op partida:", err);

        callbacks.onError &&
          callbacks.onError(err?.msg || "Error interno al guardar la celda.");
      },
    );
  }

  // =============================================================
  // Eventos globales de celdas
  // =============================================================
  document.addEventListener("click", function (e) {
    const td = e.target.closest(".evfer-date-cell");

    if (!td || !tbody || !tbody.contains(td)) return;

    e.preventDefault();
    startEditCell(td, true);
  });

  document.addEventListener("dblclick", function (e) {
    const td = e.target.closest(".evfer-date-cell");

    if (!td || !tbody || !tbody.contains(td)) return;

    e.preventDefault();
    startEditCell(td, true);
  });

  document.addEventListener("keydown", function (e) {
    const td = e.target.closest?.(".evfer-date-cell");

    if (!td || !tbody || !tbody.contains(td)) return;

    if (e.key === "Enter") {
      e.preventDefault();
      startEditCell(td, true);
      return;
    }

    if (e.key === "Delete" || e.key === "Backspace") {
      e.preventDefault();
      startEditCell(td, true);

      setTimeout(() => {
        if (activeEditInput) {
          activeEditInput.value = "";
          commitActiveCell({ move: 0 });
        }
      }, 0);
    }
  });

  // =============================================================
  // Filtros
  // =============================================================
  perPageSel?.addEventListener("change", () => {
    perPage = parseInt(perPageSel.value || "10", 10);
    currentPage = 1;
    listar();
  });

  filtroTransportista?.addEventListener("change", () => {
    currentPage = 1;
    listar();
  });

  filtroDestino?.addEventListener("change", () => {
    currentPage = 1;
    listar();
  });

  const onTextoFiltro = debounce(() => {
    currentPage = 1;
    listar();
  }, 300);

  filtroFactura?.addEventListener("input", onTextoFiltro);
  filtroFerro?.addEventListener("input", onTextoFiltro);

  filtroFactura?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      currentPage = 1;
      listar();
    }
  });

  filtroFerro?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      currentPage = 1;
      listar();
    }
  });

  // =============================================================
  // Inicialización
  // =============================================================
  function init() {
    if (
      Array.isArray(window.__evOpPartidaCols) &&
      window.__evOpPartidaCols.length > 0
    ) {
      COLS = window.__evOpPartidaCols;
      buildHead();
      listar();
      return;
    }

    xhrGet(
      URL_COLUMNAS,
      (json) => {
        const cols =
          json && Array.isArray(json.columns)
            ? json.columns
            : json && Array.isArray(json.data)
              ? json.data
              : [];

        COLS = cols.map((c) => {
          const id = c.id || c.id_tipo_evento || 0;
          const nombre = c.nombre || "";
          const key = c.key || String(id);

          return {
            id,
            nombre,
            key,
          };
        });

        window.__evOpPartidaCols = COLS;

        buildHead();
        listar();
      },
      () => {
        COLS = [];
        buildHead();
        listar();
      },
    );
  }

  init();
})();

// ===============================================================
// Exportación
// ===============================================================
document
  .getElementById("btnExportarExcelEventosLogisticosOpPartida")
  ?.addEventListener("click", () => {
    if (window.ExportarTablas) {
      ExportarTablas.exportar({
        ref: "tablaEventosOpPartida",
        formato: "xlsx",
        nombre: "Eventos Operaciones por Partida.xlsx",
        columnasOcultas: [],
        soloVisibles: true,
        sheetName: "Eventos Op Partida",
      });
      return;
    }

    const table = document.getElementById("tablaEventosOpPartida");

    if (!table) {
      if (window.Swal) {
        Swal.fire(
          "Atención",
          "No se encontró la tabla para exportar.",
          "warning",
        );
      }
      return;
    }

    const html =
      "<html><head><meta charset='UTF-8'></head><body>" +
      table.outerHTML +
      "</body></html>";

    const blob = new Blob([html], {
      type: "application/vnd.ms-excel",
    });

    const a = document.createElement("a");
    const now = new Date();

    const name =
      "eventos_operaciones_partida_" +
      now.getFullYear() +
      String(now.getMonth() + 1).padStart(2, "0") +
      String(now.getDate()).padStart(2, "0") +
      ".xls";

    a.href = URL.createObjectURL(blob);
    a.download = name;

    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(() => {
      URL.revokeObjectURL(a.href);
    }, 1000);
  });
