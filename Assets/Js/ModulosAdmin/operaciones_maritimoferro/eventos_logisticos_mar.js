/* ===============================================================
   Eventos Marítimos MAR - Marítimo Ferro
   NUEVA LÓGICA TIPO EXCEL

   Funcionalidad:
   - Columnas dinámicas por tipo de evento marítimo.
   - Cada celda de evento se puede editar directamente.
   - Enter: guarda la celda.
   - Tab: guarda y avanza a la celda derecha.
   - Shift + Tab: guarda y regresa a la celda izquierda.
   - Escape: cancela edición.
   - Fecha vacía: limpia la celda.
   - Conserva filtros, paginación y exportaciones.
   =============================================================== */

(function evMARListPivotExcel() {
  "use strict";

  // =============================================================
  // Referencias UI
  // =============================================================
  const theadRow = document.getElementById("theadEventosMar");
  const tbody = document.getElementById("tbodyEventosMar");
  const pagBox = document.getElementById("evMarPaginacion");
  const metaBox = document.getElementById("evMarMetaResumen");
  const perPageSel = document.getElementById("evMarPerPage");

  const filtroOpId = document.getElementById("eventosFiltroOpId");
  const filtroOpNom = document.getElementById("eventosFiltroOpNombre");
  const filtroOpBox = document.getElementById("eventosFiltroOpSugerencias");
  const filtroOpMeta = document.getElementById("eventosFiltroOpMeta");

  const filtroContId = document.getElementById("eventosFiltroContMarId");
  const filtroContenedor = document.getElementById("eventosFiltroFerro");
  const filtroCliente = document.getElementById("eventosFiltroCliente");

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
  // Utilidades base
  // =============================================================
  function xhrGet(url, ok, err) {
    const http = new XMLHttpRequest();
    http.open("GET", url, true);

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status >= 200 && this.status < 300) {
        try {
          ok && ok(JSON.parse(this.responseText));
        } catch (e) {
          console.error("JSON inválido:", this.responseText);
          err && err("JSON inválido");
        }
      } else {
        err && err(this.responseText || "HTTP error");
      }
    };

    http.send();
  }

  function xhrPost(url, formData, ok, err) {
    const http = new XMLHttpRequest();
    http.open("POST", url, true);

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let res = null;

      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        res = null;
      }

      if (this.status >= 200 && this.status < 300) {
        ok && ok(res);
      } else {
        err && err(res || this.responseText || "HTTP error");
      }
    };

    http.send(formData);
  }

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
  // Fechas
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
      <th style="min-width:160px" class="text-center">Operación Marítima</th>
      <th style="min-width:190px" class="text-center">Contenedor Marítimo</th>
      <th style="min-width:220px" class="text-center">Cliente</th>
      <th style="min-width:180px" class="text-center">Arribo al Puerto</th>
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
      const operacionId = parseInt(r.operacion_id || 0, 10);
      const cmoId = parseInt(r.cont_maritimo_operacion_id || 0, 10);

      if (!operacionId || !cmoId) return;

      const key = `${operacionId}||${cmoId}`;

      if (!groups.has(key)) {
        const cells = {};

        for (const c of COLS) {
          cells[c.key] = {
            id_evento: null,
            fecha: "",
            comentario: "",
            tipo_evento_id: c.id,
            evento: c.nombre,
          };
        }

        groups.set(key, {
          operacion_id: operacionId,
          cont_maritimo_operacion_id: cmoId,

          operacion: r.operacion || r.operacion_maritima || "",
          contenedor: r.contenedor || r.contenedor_maritimo || "",
          cliente: r.cliente || "",
          arribo_puerto: r.arribo_puerto || "",

          cells,
        });
      }

      const g = groups.get(key);
      const c = byEvtId.get(String(r.tipo_evento_id || ""));

      if (c) {
        const currentCell = g.cells[c.key];
        const newFecha = r.fecha || "";

        if (
          !currentCell.fecha ||
          String(newFecha) >= String(currentCell.fecha)
        ) {
          g.cells[c.key] = {
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

      if (!g.contenedor && (r.contenedor || r.contenedor_maritimo)) {
        g.contenedor = r.contenedor || r.contenedor_maritimo;
      }

      if (!g.cliente && r.cliente) g.cliente = r.cliente;
      if (!g.arribo_puerto && r.arribo_puerto)
        g.arribo_puerto = r.arribo_puerto;
    });

    return Array.from(groups.values()).sort((a, b) => {
      const ao = (a.operacion || "").localeCompare(b.operacion || "");

      if (ao !== 0) return ao;

      return (a.contenedor || "").localeCompare(b.contenedor || "");
    });
  }

  // =============================================================
  // Render cuerpo
  // =============================================================
  function renderBody(pivoted) {
    if (!tbody) return;

    tbody.innerHTML = "";

    const fixedCols = 4;

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
      tr.dataset.ctnlabel = row.contenedor || "";

      const arriboLabel = row.arribo_puerto
        ? formatDateForDisplay(row.arribo_puerto)
        : "";

      let html = `
        <td class="text-center">${esc(row.operacion)}</td>
        <td class="text-center">${esc(row.contenedor)}</td>
        <td>${esc(row.cliente)}</td>
        <td class="text-center">${esc(arriboLabel)}</td>
      `;

      for (const c of COLS) {
        const cell = row.cells[c.key] || {};
        const fechaSQL = cell.fecha || "";
        const fechaLabel = fechaSQL ? formatDateForDisplay(fechaSQL) : "";
        const emptyClass = fechaSQL ? "" : "evmar-empty";
        const idEvento = cell.id_evento || "";

        html += `
          <td class="text-center evmar-date-cell ${emptyClass}"
              tabindex="0"
              title="Clic para escribir fecha. Enter guarda. Tab guarda y avanza."
              data-operacion-id="${esc(row.operacion_id)}"
              data-cont-maritimo-operacion-id="${esc(row.cont_maritimo_operacion_id)}"
              data-tipo-evento-id="${esc(c.id)}"
              data-id-evento="${esc(idEvento)}"
              data-fecha="${esc(fechaSQL)}"
              data-comentario="${esc(cell.comentario || "")}"
              data-evento-nombre="${esc(c.nombre)}">
            ${fechaLabel ? esc(fechaLabel) : '<span class="text-muted">-</span>'}
          </td>`;
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
      li.className = `page-item${disabled ? " disabled" : ""}${active ? " active" : ""}`;

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
    const opNom = (filtroOpNom?.value || "").trim();
    const contId = (filtroContId?.value || "").trim();
    const clienteId = (filtroCliente?.value || "").trim();
    const contenedor = (filtroContenedor?.value || "").trim();

    if (opId) {
      params.append("op_id", opId);
    } else if (opNom) {
      params.append("q", opNom);
    }

    if (contId) params.append("cont_id", contId);
    if (clienteId) params.append("cliente_id", clienteId);
    if (contenedor) params.append("contenedor", contenedor);

    return params.toString();
  }

  function listar() {
    cancelarEdicionActiva(false);

    const url =
      `${base_url}Operaciones_maritimo_ferro_eventos_mar/listar?` +
      buildQueryString();

    xhrGet(
      url,
      (res) => {
        const pivoted = pivotRows(res.data || []);
        renderBody(pivoted);

        totalRows =
          res && typeof res.total !== "undefined"
            ? parseInt(res.total || 0, 10)
            : pivoted.length || 0;

        renderPagination(currentPage, totalRows, perPage);
        renderMeta(currentPage, totalRows, perPage);
      },
      (err) => {
        console.error("Listar eventos marítimos MAR:", err);

        const fixedCols = 4;

        if (tbody) {
          tbody.innerHTML = `<tr>
            <td colspan="${fixedCols + COLS.length}" class="text-center text-danger py-3">
              Error al obtener datos
            </td>
          </tr>`;
        }
      },
    );
  }

  window.refreshEventosMAR = function (opts = { keepPage: true }) {
    if (!opts.keepPage) currentPage = 1;
    listar();
  };

  window.refreshEventosMF = window.refreshEventosMAR;

  // =============================================================
  // Edición tipo Excel
  // =============================================================
  function getCellTextValue(td) {
    return formatDateForInput(td?.dataset?.fecha || "");
  }

  function getAllEditableCells() {
    return Array.from(
      document.querySelectorAll("#tbodyEventosMar .evmar-date-cell"),
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

    td.classList.remove("evmar-saving", "evmar-saved", "evmar-error");

    const oldStatus = td.querySelector(".evmar-cell-status");
    if (oldStatus) oldStatus.remove();

    if (!status) return;

    if (status === "saving") td.classList.add("evmar-saving");
    if (status === "saved") td.classList.add("evmar-saved");
    if (status === "error") td.classList.add("evmar-error");

    if (text) {
      const span = document.createElement("span");
      span.className = "evmar-cell-status";
      span.textContent = text;
      td.appendChild(span);
    }

    if (status === "saved") {
      setTimeout(() => {
        td.classList.remove("evmar-saved");
        const s = td.querySelector(".evmar-cell-status");
        if (s) s.remove();
      }, 900);
    }
  }

  function renderCellValue(td, fechaSQL) {
    const label = fechaSQL ? formatDateForDisplay(fechaSQL) : "";

    td.dataset.fecha = fechaSQL || "";

    if (fechaSQL) {
      td.classList.remove("evmar-empty");
      td.innerHTML = esc(label);
    } else {
      td.classList.add("evmar-empty");
      td.innerHTML = '<span class="text-muted">-</span>';
    }
  }

  function cancelarEdicionActiva(restoreOriginal = true) {
    if (!activeEditCell || !activeEditInput) {
      activeEditCell = null;
      activeEditInput = null;
      activeEditOriginalValue = "";
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
    if (!td || td.classList.contains("evmar-saving")) return;

    if (activeEditCell && activeEditCell !== td) {
      cancelarEdicionActiva(true);
    }

    if (activeEditCell === td) return;

    activeEditCell = td;
    activeEditOriginalValue = td.dataset.fecha || "";

    const inputValue = getCellTextValue(td);

    td.classList.remove("evmar-error", "evmar-saved");
    td.innerHTML = "";

    const input = document.createElement("input");
    input.type = "text";
    input.className = "evmar-date-input";
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
        if (next) startEditCell(next, true);
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
    const operacionId = td.dataset.operacionId || "";
    const contMaritimoOperacionId = td.dataset.contMaritimoOperacionId || "";
    const tipoEventoId = td.dataset.tipoEventoId || "";
    const comentario = td.dataset.comentario || "";

    const fd = new FormData();
    fd.append("operacion_id", operacionId);
    fd.append("cont_maritimo_operacion_id", contMaritimoOperacionId);
    fd.append("tipo_evento_id", tipoEventoId);
    fd.append("fecha", fechaSQL || "");
    fd.append("comentario", comentario || "");

    setCellStatus(td, "saving", "…");

    xhrPost(
      `${base_url}Operaciones_maritimo_ferro_eventos_mar/guardar_celda`,
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
        console.error("guardar_celda eventos marítimos MAR:", err);

        callbacks.onError &&
          callbacks.onError(err?.msg || "Error interno al guardar la celda.");
      },
    );
  }

  document.addEventListener("click", function (e) {
    const td = e.target.closest(".evmar-date-cell");

    if (!td) return;

    e.preventDefault();
    startEditCell(td, true);
  });

  document.addEventListener("dblclick", function (e) {
    const td = e.target.closest(".evmar-date-cell");

    if (!td) return;

    e.preventDefault();
    startEditCell(td, true);
  });

  document.addEventListener("keydown", function (e) {
    const td = e.target.closest?.(".evmar-date-cell");

    if (!td) return;

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

  filtroCliente?.addEventListener("change", () => {
    currentPage = 1;
    listar();
  });

  const onTextoFiltro = debounce(() => {
    currentPage = 1;
    listar();
  }, 300);

  filtroContenedor?.addEventListener("input", onTextoFiltro);

  filtroContenedor?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      currentPage = 1;
      listar();
    }
  });

  // =============================================================
  // Autocomplete operación oculto / compatibilidad
  // =============================================================
  (function evMARFilterOp() {
    if (!filtroOpNom || !filtroOpId || !filtroOpBox) return;

    function renderSugerencias(items, onPick) {
      filtroOpBox.innerHTML = "";

      if (!Array.isArray(items) || items.length === 0) {
        filtroOpBox.style.display = "none";
        return;
      }

      items.forEach((it) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className =
          "list-group-item list-group-item-action d-flex justify-content-between align-items-center";

        btn.innerHTML = `<div class="d-flex flex-column text-start">
          <span>${esc(it.label || "")}</span>
          <small class="text-muted">${esc(it.meta || "")}</small>
        </div>`;

        btn.addEventListener("click", () => onPick(it));
        filtroOpBox.appendChild(btn);
      });

      filtroOpBox.style.display = "block";
    }

    function pickOperacion(it) {
      filtroOpNom.value = it.label || "";
      filtroOpId.value = it.id || "";

      if (filtroOpMeta) {
        filtroOpMeta.textContent = it.meta || "";
      }

      filtroOpBox.style.display = "none";
      currentPage = 1;
      listar();
    }

    function clearSeleccion() {
      filtroOpId.value = "";
      if (filtroOpMeta) filtroOpMeta.textContent = "";
    }

    let tmr = null;
    let lastTerm = "";

    filtroOpNom.addEventListener("input", () => {
      const term = (filtroOpNom.value || "").trim();

      clearSeleccion();

      if (term.length === 0) {
        filtroOpBox.style.display = "none";
        currentPage = 1;
        listar();
        return;
      }

      currentPage = 1;
      listar();

      clearTimeout(tmr);

      tmr = setTimeout(() => {
        if (term === lastTerm) return;
        lastTerm = term;

        const url =
          `${base_url}Operaciones_maritimo_ferro_eventos_mar/sugerir_operaciones?term=` +
          encodeURIComponent(term) +
          `&limit=10`;

        xhrGet(
          url,
          (rows) => renderSugerencias(rows, pickOperacion),
          () => {
            filtroOpBox.style.display = "none";
          },
        );
      }, 250);
    });

    filtroOpNom.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();

        const first = filtroOpBox.querySelector(".list-group-item");

        if (first) {
          first.click();
        } else {
          clearSeleccion();
          currentPage = 1;
          listar();
        }
      } else if (e.key === "Escape") {
        filtroOpBox.style.display = "none";
      }
    });

    document.addEventListener("click", (ev) => {
      if (!filtroOpBox.contains(ev.target) && ev.target !== filtroOpNom) {
        filtroOpBox.style.display = "none";
      }
    });
  })();

  // =============================================================
  // Inicialización
  // =============================================================
  function init() {
    if (Array.isArray(window.__evMarCols) && window.__evMarCols.length > 0) {
      COLS = window.__evMarCols;
      buildHead();
      listar();
      return;
    }

    xhrGet(
      `${base_url}Operaciones_maritimo_ferro_eventos_mar/eventos_maritimos_columnas`,
      (json) => {
        const cols = json && json.columns ? json.columns : [];
        COLS = Array.isArray(cols) ? cols : [];
        window.__evMarCols = COLS;

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
  .getElementById("btnExportarExcelEventosLogisticosMar")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "tablaEventosMar",
      formato: "xlsx",
      nombre: `Eventos Logisticos Maritimos.xlsx`,
      columnasOcultas: [],
      soloVisibles: true,
      sheetName: `Eventos Logisticos Maritimos`,
    });
  });

document
  .getElementById("btnExportarPDFEventosLogisticosMar")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "#tablaEventosMar",
      formato: "pdf",
      nombre: `Eventos Logisticos Maritimos.pdf`,
      titulo: `Eventos Logisticos Maritimos`,
      orientacion: "landscape",
      formatoPagina: "letter",
      columnasOcultas: [],
      soloVisibles: true,
    });
  });
