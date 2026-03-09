/* ===============================================================
   Listado FER con columnas dinámicas (pivot por evento)
   ✅ ACTUALIZADO:
   - Soporta filtros por:
     * operación
     * transportista
     * cliente
     * destino
   - Manda al backend:
     * op_id
     * transportista_id
     * cliente_id
     * destino_id
     * operacion
   =============================================================== */
(function evFERListPivot() {
  "use strict";

  // ---- Refs UI ----
  const theadRow = document.getElementById("theadEventosFer");
  const tbody = document.getElementById("tbodyEventosFer");
  const pagBox = document.getElementById("evFerPaginacion");
  const metaBox = document.getElementById("evFerMetaResumen");
  const perPageSel = document.getElementById("evFerPerPage");

  const filtroOpId = document.getElementById("eventosFerFiltroOpId");
  const filtroOpNom = document.getElementById("eventosFerFiltroOpNombre");
  const filtroOpBox = document.getElementById("eventosFerFiltroOpSugerencias");
  const filtroOpMeta = document.getElementById("eventosFerFiltroOpMeta");

  const filtroTransportista = document.getElementById(
    "eventosFerFiltroTransportista",
  );
  const filtroCliente = document.getElementById("eventosFerFiltroCliente");
  const filtroDestino = document.getElementById("eventosFerFiltroDestino");

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
        try {
          ok && ok(JSON.parse(this.responseText));
        } catch {
          err && err("JSON inválido");
        }
      } else {
        err && err(this.responseText || "HTTP error");
      }
    };
    http.send();
  }

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function buildHead() {
    if (!theadRow) return;

    theadRow.innerHTML = `
      <th style="min-width:160px" class="text-center">Operación Marítima</th>
      <th style="min-width:190px" class="text-center">Contenedor Marítimo</th>
      <th style="min-width:220px" class="text-center">Cliente</th>
      <th style="min-width:180px" class="text-center">Destino</th>
      <th style="min-width:180px" class="text-center">Transportista</th>
      <th style="min-width:180px" class="text-center">Caja / Ferro</th>
    `;

    for (const c of COLS) {
      const th = document.createElement("th");
      th.setAttribute("data-evt-id", c.id);
      th.textContent = c.nombre;
      th.className = "text-center";
      theadRow.appendChild(th);
    }
  }

  function pivotRows(rows) {
    const byEvtId = new Map(COLS.map((c) => [String(c.id), c]));
    const groups = new Map();

    (rows || []).forEach((r) => {
      const opId = r.operacion_ferro_id;
      const ferId = r.contenedor_fisico_id;
      const key = `${opId}||${ferId}`;

      if (!groups.has(key)) {
        const cells = {};
        for (const c of COLS) cells[c.key] = "-";

        groups.set(key, {
          operacion_maritima: r.operacion_maritima || r.operacion || "",
          contenedor_maritimo: r.contenedor_maritimo || "",
          cliente: r.cliente || "",
          destino: r.destino || "",
          transportista: r.transportista || "",
          ferro: r.ferro || "",
          op_id: opId,
          ferro_id: ferId,
          cells,
        });
      }

      const g = groups.get(key);
      const c = byEvtId.get(String(r.tipo_evento_id));
      if (c) {
        const prev = g.cells[c.key];
        const val = r.fecha || "-";
        if (prev === "-" || String(val) > String(prev)) {
          g.cells[c.key] = val;
        }
      }

      if (!g.operacion_maritima && (r.operacion_maritima || r.operacion)) {
        g.operacion_maritima = r.operacion_maritima || r.operacion;
      }
      if (!g.cliente && r.cliente) g.cliente = r.cliente;
      if (!g.contenedor_maritimo && r.contenedor_maritimo) {
        g.contenedor_maritimo = r.contenedor_maritimo;
      }
      if (!g.destino && r.destino) g.destino = r.destino;
      if (!g.transportista && r.transportista)
        g.transportista = r.transportista;
      if (!g.ferro && r.ferro) g.ferro = r.ferro;
    });

    return Array.from(groups.values()).sort((a, b) => {
      const ao = (a.operacion_maritima || "").localeCompare(
        b.operacion_maritima || "",
      );
      if (ao !== 0) return ao;
      return (a.ferro || "").localeCompare(b.ferro || "");
    });
  }

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

      tr.dataset.oplabel = row.operacion_maritima || "";
      tr.dataset.ctnlabel =
        `${row.ferro || ""} / ${row.contenedor_maritimo || ""}`.trim();

      let html = `
        <td class="text-center">${esc(row.operacion_maritima)}</td>
        <td class="text-center">${esc(row.contenedor_maritimo)}</td>
        <td>${esc(row.cliente)}</td>
        <td>${esc(row.destino)}</td>
        <td>${esc(row.transportista)}</td>
        <td class="text-center">${esc(row.ferro)}</td>
      `;

      for (const c of COLS) {
        const val = row.cells[c.key] || "-";
        html += `
          <td class="text-center evfer-cell"
              data-op="${row.op_id}"
              data-cfo="${row.ferro_id}"
              data-evt="${c.id}"
              data-evtname="${esc(c.nombre)}">
            ${esc(val)}
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
    if (e - s + 1 < win) s = Math.max(1, e - win + 1);

    for (let p = s; p <= e; p++) {
      mk(p, String(p), false, p === page);
    }

    mk(Math.min(totalPages, page + 1), "&raquo;", page === totalPages, false);
  }

  function renderMeta(page, total, perPage) {
    if (!metaBox) return;

    if (total === 0) {
      metaBox.textContent = "Mostrando 0–0 de 0";
      return;
    }

    const start = (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);
    metaBox.textContent = `Mostrando ${start}–${end} de ${total}`;
  }

  function buildQueryString() {
    const params = new URLSearchParams();

    params.append("page", String(currentPage));
    params.append("per_page", String(perPage));

    const opId = (filtroOpId?.value || "").trim();
    const opNom = (filtroOpNom?.value || "").trim();
    const transportistaId = (filtroTransportista?.value || "").trim();
    const clienteId = (filtroCliente?.value || "").trim();
    const destinoId = (filtroDestino?.value || "").trim();

    if (opId) {
      params.append("op_id", opId);
    } else if (opNom) {
      params.append("operacion", opNom);
    }

    if (transportistaId) params.append("transportista_id", transportistaId);
    if (clienteId) params.append("cliente_id", clienteId);
    if (destinoId) params.append("destino_id", destinoId);

    return params.toString();
  }

  function listar() {
    const url =
      `${base_url}Operaciones_maritimo_ferro_eventos_fer/listar?` +
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
        console.error("Listar FER:", err);
        const fixedCols = 6;
        tbody.innerHTML = `<tr>
          <td colspan="${fixedCols + COLS.length}" class="text-center text-danger py-3">
            Error al obtener datos
          </td>
        </tr>`;
      },
    );
  }

  window.refreshEventosFER = function (opts = { keepPage: true }) {
    if (!opts.keepPage) currentPage = 1;
    listar();
  };

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
        const cols = json && json.columns ? json.columns : [];
        COLS = Array.isArray(cols) ? cols : [];
        window.__evFerCols = COLS;
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

  // ---- perPage ----
  perPageSel?.addEventListener("change", () => {
    perPage = parseInt(perPageSel.value || "10", 10);
    currentPage = 1;
    listar();
  });

  // ---- Filtros select ----
  filtroTransportista?.addEventListener("change", () => {
    currentPage = 1;
    listar();
  });

  filtroCliente?.addEventListener("change", () => {
    currentPage = 1;
    listar();
  });

  filtroDestino?.addEventListener("change", () => {
    currentPage = 1;
    listar();
  });

  // ---- Filtro superior: Operación (autocomplete) ----
  (function evFERFilterOp() {
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
      <span>${it.label || ""}</span>
      <small class="text-muted">
        ${it.ferro || ""}${it.contenedor ? " · " + it.contenedor : ""}
      </small>
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
        filtroOpMeta.textContent = it.ferro ? "Ferro: " + it.ferro : "";
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

      // listar por texto aunque no se seleccione una sugerencia
      currentPage = 1;
      listar();

      clearTimeout(tmr);
      tmr = setTimeout(() => {
        if (term === lastTerm) return;
        lastTerm = term;

        const url =
          `${base_url}Operaciones_maritimo_ferro_eventos_fer/sugerir_operaciones?term=` +
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

  // ---- Modal de celda (crear/editar/eliminar desde la tabla) ----
  (function evFERCellModal() {
    let cellDirty = false;
    const modalEl = document.getElementById("modalEvtCellFer");
    const formEl = document.getElementById("formEvtCellFer");

    const fldOpId = document.getElementById("cellOpIdFer");
    const fldCfoId = document.getElementById("cellCfoIdFer");
    const fldEvtId = document.getElementById("cellEvtIdFer");
    const fldIdEv = document.getElementById("cellIdEventoFer");

    const opTxt = document.getElementById("cellOpTxtFer");
    const ctnTxt = document.getElementById("cellCtnTxtFer");
    const evtTxt = document.getElementById("cellEvtTxtFer");
    const fecha = document.getElementById("cellFechaFer");
    const comenta = document.getElementById("cellComentarioFer");
    const btnDel = document.getElementById("btnCellDeleteFer");

    document.addEventListener("click", (e) => {
      const td = e.target.closest(".evfer-cell");
      if (!td) return;

      const opId = +td.dataset.op;
      const cfoId = +td.dataset.cfo;
      const evtId = +td.dataset.evt;
      const evtName = td.dataset.evtname || "";

      document.getElementById("modalEvtCellTitleFer").textContent = evtName;

      const tr = td.parentElement;
      opTxt.value = tr.dataset.oplabel || "";
      ctnTxt.value = tr.dataset.ctnlabel || "";
      evtTxt.value = evtName;

      fldOpId.value = opId;
      fldCfoId.value = cfoId;
      fldEvtId.value = evtId;
      fldIdEv.value = "";

      const url = `${base_url}Operaciones_maritimo_ferro_eventos_fer/obtener_por_clave?operacion_ferro_id=${opId}&contenedor_fisico_id=${cfoId}&tipo_evento_id=${evtId}`;
      const http = new XMLHttpRequest();
      http.open("GET", url, true);
      http.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        if (this.status === 200) {
          let data = null;
          try {
            data = JSON.parse(this.responseText);
          } catch {}

          if (data && data.id_evento) {
            fldIdEv.value = data.id_evento;
            fecha.value = (data.fecha || "").substring(0, 10);
            comenta.value = data.comentario || "";
            btnDel.classList.remove("d-none");
          } else {
            fecha.value = "";
            comenta.value = "";
            btnDel.classList.add("d-none");
          }

          new bootstrap.Modal(modalEl).show();
        }
      };
      http.send();
    });

    formEl.addEventListener("submit", function (e) {
      e.preventDefault();

      const idEv = (fldIdEv.value || "").trim();
      const fd = new FormData();

      if (idEv) fd.append("id_evento", idEv);
      fd.append("operacion_ferro_id", (fldOpId.value || "").trim());
      fd.append("contenedor_fisico_id", (fldCfoId.value || "").trim());
      fd.append("tipo_evento_id", (fldEvtId.value || "").trim());
      fd.append("fecha", (fecha.value || "").trim());
      fd.append("comentario", (comenta.value || "").trim());

      const url =
        base_url +
        "Operaciones_maritimo_ferro_eventos_fer/" +
        (idEv ? "actualizar" : "registrar");

      const http = new XMLHttpRequest();
      http.open("POST", url, true);
      http.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        if (this.status === 200) {
          let res = null;
          try {
            res = JSON.parse(this.responseText);
          } catch {}

          Swal?.fire(
            res?.status === "success" ? "Éxito" : "Atención",
            res?.msg || "Listo",
            res?.status || "success",
          );

          cellDirty = true;
          bootstrap.Modal.getInstance(modalEl)?.hide();
          window.refreshEventosFER &&
            window.refreshEventosFER({ keepPage: true });
        }
      };
      http.send(fd);
    });

    btnDel.addEventListener("click", function () {
      const idEv = (fldIdEv.value || "").trim();
      if (!idEv) return;

      Swal?.fire({
        title: "¿Eliminar evento?",
        icon: "warning",
        showCancelButton: true,
      }).then((r) => {
        if (!r.isConfirmed) return;

        const fd = new FormData();
        fd.append("id_evento", idEv);

        const http = new XMLHttpRequest();
        http.open(
          "POST",
          base_url + "Operaciones_maritimo_ferro_eventos_fer/eliminar",
          true,
        );

        http.onreadystatechange = function () {
          if (this.readyState !== 4) return;

          let res = null;
          try {
            res = JSON.parse(this.responseText);
          } catch {}

          Swal?.fire(
            res?.status === "success" ? "Eliminado" : "Atención",
            res?.msg || "Listo",
            res?.status === "success" ? "success" : "warning",
          );

          bootstrap.Modal.getInstance(modalEl)?.hide();
          window.refreshEventosFER &&
            window.refreshEventosFER({ keepPage: true });
        };

        http.send(fd);
      });
    });

    modalEl?.addEventListener("hidden.bs.modal", () => {
      if (cellDirty) {
        window.refreshEventosFER &&
          window.refreshEventosFER({ keepPage: true });
        cellDirty = false;
      }
    });
  })();

  init();
})();
