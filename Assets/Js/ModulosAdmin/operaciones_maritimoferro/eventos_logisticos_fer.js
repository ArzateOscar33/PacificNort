/* ===============================================================
   Listado FER con columnas dinámicas (pivot por evento)
   - Construye THEAD con eventos TERRESTRES (ferro)
   - Pivotea filas: (operación + contenedor) => fechas por evento
   - "Sin registrar" cuando no hay fecha
   - Endpoints:
       - columnas: Operaciones_maritimo_ferro_eventos_fer/eventos_ferro_columnas
       - listar:   Operaciones_maritimo_ferro_eventos_fer/listar
   =============================================================== */
(function evFERListPivot() {
  "use strict";

  // ---- Refs UI (FER) ----
  const theadRow   = document.getElementById("theadEventosFer");
  const tbody      = document.getElementById("tbodyEventosFer");
  const pagBox     = document.getElementById("evFerPaginacion");
  const metaBox    = document.getElementById("evFerMetaResumen");
  const perPageSel = document.getElementById("evFerPerPage");

  // Filtros (según tu vista actual solo hay por operación)
  const buscarEl   = document.getElementById("buscarEventosFer");         // opcional (por si luego lo agregas)
  const filtroOpId = document.getElementById("eventosFerFiltroOpId");     // hidden con id operación
  const filtroCont = document.getElementById("eventosFerFiltroContId");   // opcional (no está en tu vista, tolerante)

  // ---- Estado ----
  let COLS = []; // [{id, nombre, key}]
  let currentPage = 1;
  let perPage = parseInt(perPageSel?.value || "10", 10);
  let totalRows = 0;

  // ---- Utils ----
  function xhrGet(url, ok, err) {
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status === 200) {
        try { ok && ok(JSON.parse(this.responseText)); }
        catch { err && err("JSON inválido"); }
      } else {
        err && err(this.responseText || "HTTP error");
      }
    };
    http.send();
  }

  // THEAD: 2 fijas + dinámicas por evento
  function buildHead() {
    if (!theadRow) return;
    theadRow.innerHTML = `
      <th style="min-width:140px">Operación</th>
      <th style="min-width:180px">Contenedor ferroviario</th>
    `;
    for (const c of COLS) {
      const th = document.createElement("th");
      th.setAttribute("data-evt-id", c.id);
      th.textContent = c.nombre;
      th.className = "text-center";
      theadRow.appendChild(th);
    }
  }

  // Pivot: filas por evento -> filas por (op + contenedor)
  function pivotRows(rows) {
    const byEvtId = new Map(COLS.map(c => [String(c.id), c]));
    // key = opId||cfoId
    const groups = new Map();

    (rows || []).forEach(r => {
      const opId = r.operacion_id;
      const cfoId = r.cont_ferro_operacion_id; // <<< clave ferro
      const key = `${opId}||${cfoId}`;

      if (!groups.has(key)) {
        const cells = {};
        for (const c of COLS) cells[c.key] = "Sin registrar";
        groups.set(key, {
          operacion: r.operacion || "",
          contenedor: r.contenedor || "",
          operacion_id: opId,
          cfo_id: cfoId,
          cells
        });
      }
      const g = groups.get(key);
      const c = byEvtId.get(String(r.tipo_evento_id));
      if (c) {
        const prev = g.cells[c.key];
        const val  = r.fecha || "Sin registrar";
        if (prev === "Sin registrar" || String(val) > String(prev)) {
          g.cells[c.key] = val;
        }
      }
    });

    return Array.from(groups.values()).sort((a, b) => {
      const ao = (a.operacion || "").localeCompare(b.operacion || "");
      if (ao !== 0) return ao;
      return (a.contenedor || "").localeCompare(b.contenedor || "");
    });
  }

  // TBODY
  function renderBody(pivoted) {
    if (!tbody) return;
    tbody.innerHTML = "";

    if (!Array.isArray(pivoted) || pivoted.length === 0) {
      tbody.innerHTML = `<tr><td colspan="${2 + COLS.length}" class="text-center text-muted py-3">No hay registros</td></tr>`;
      return;
    }

    for (const row of pivoted) {
      const tr = document.createElement("tr");
      tr.dataset.oplabel  = row.operacion || "";
      tr.dataset.ctnlabel = row.contenedor || "";
      let html = `
        <td class="text-center">${row.operacion}</td>
        <td class="text-center">${row.contenedor}</td>
      `;
      for (const c of COLS) {
        const val = row.cells[c.key] || "Sin registrar";
        html += `
          <td class="text-center evfer-cell"
              data-op="${row.operacion_id}"
              data-cfo="${row.cfo_id}"
              data-evt="${c.id}"
              data-evtname="${c.nombre}">
            ${val}
          </td>`;
      }
      tr.innerHTML = html;
      tbody.appendChild(tr);
    }
    if (window.feather) feather.replace();
  }

  function renderPagination(page, total, perPage) {
    if (!pagBox) return;
    pagBox.innerHTML = "";
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (totalPages <= 1) return;

    const mk = (p, label, disabled = false, active = false) => {
      const li = document.createElement("li");
      li.className = `page-item${disabled ? " disabled" : ""}${active ? " active" : ""}`;
      const a = document.createElement("a");
      a.className = "page-link";
      a.href = "#";
      a.innerHTML = label;
      if (!disabled && !active) {
        a.addEventListener("click", e => { e.preventDefault(); currentPage = p; listar(); });
      }
      li.appendChild(a);
      pagBox.appendChild(li);
    };

    mk(Math.max(1, page - 1), "&laquo;", page === 1, false);
    const win = 5;
    let s = Math.max(1, page - Math.floor(win / 2));
    let e = Math.min(totalPages, s + win - 1);
    if (e - s + 1 < win) s = Math.max(1, e - win + 1);
    for (let p = s; p <= e; p++) mk(p, String(p), false, p === page);
    mk(Math.min(totalPages, page + 1), "&raquo;", page === totalPages, false);
  }

  function renderMeta(page, total, perPage) {
    if (!metaBox) return;
    if (total === 0) { metaBox.textContent = "Mostrando 0 de 0"; return; }
    const start = (page - 1) * perPage + 1;
    const end   = Math.min(page * perPage, total);
    metaBox.textContent = `Mostrando ${start}–${end} de ${total}`;
  }

  // ---- Core: pedir datos y pintar ----
  function listar() {
    const q      = buscarEl?.value?.trim() || "";
    const opId   = (filtroOpId?.value || "").trim();
    const contId = (filtroCont?.value || "").trim();

    const url = `${base_url}Operaciones_maritimo_ferro_eventos_fer/listar?page=${currentPage}&per_page=${perPage}`
              + (opId   ? `&op_id=${encodeURIComponent(opId)}`   : "")
              + (contId ? `&cont_id=${encodeURIComponent(contId)}`: "")
              + (q      ? `&q=${encodeURIComponent(q)}`           : "");

    xhrGet(url, (res) => {
      const pivoted = pivotRows(res.data || []);
      renderBody(pivoted);
      totalRows = res.total || pivoted.length || 0;
      renderPagination(currentPage, totalRows, perPage);
      renderMeta(currentPage, totalRows, perPage);
    }, (err) => {
      console.error("Listar FER:", err);
      tbody.innerHTML = `<tr><td colspan="${2 + COLS.length}" class="text-center text-danger py-3">Error al obtener datos</td></tr>`;
    });
  }

  // Exponer para refrescar desde fuera (p.ej. al cambiar filtro)
  window.refreshEventosFER = function (opts = { keepPage: true }) {
    if (!opts.keepPage) currentPage = 1;
    listar();
  };

  // ---- Cargar columnas y luego listar ----
  function init() {
    if (Array.isArray(window.__evFerCols) && window.__evFerCols.length > 0) {
      COLS = window.__evFerCols;
      buildHead();
      listar();
      return;
    }
    xhrGet(
      `${base_url}Operaciones_maritimo_ferro_eventos_fer/eventos_ferro_columnas`,
      (json) => {
        const cols = (json && json.columns) ? json.columns : [];
        COLS = Array.isArray(cols) ? cols : [];
        window.__evFerCols = COLS; // cache global
        buildHead();
        listar();
      },
      () => {
        COLS = [];
        buildHead();
        listar();
      }
    );
  }

  // ---- Eventos UI ----
  perPageSel?.addEventListener("change", () => {
    perPage = parseInt(perPageSel.value || "10", 10);
    currentPage = 1;
    listar();
  });

  let tmr;
  buscarEl?.addEventListener("input", () => {
    clearTimeout(tmr);
    tmr = setTimeout(() => { currentPage = 1; listar(); }, 300);
  });

  // cuando cambie el filtro de operación (de tu buscador superior), recarga:
  document.getElementById("eventosFerFiltroOpNombre")
    ?.addEventListener("change", () => { currentPage = 1; listar(); });

  // ---- Init ----
  init();
})();
