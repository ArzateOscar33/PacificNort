/* ===============================================================
   Listado FER con columnas dinámicas (pivot por evento)
   ACTUALIZADO:
   - Mantiene eventos por celda.
   - Agrega observaciones por renglón.
   - Soporta filtros por:
     * operación
     * transportista
     * cliente
     * destino
     * contenedor marítimo
     * ferro
   - Manda al backend:
     * op_id
     * transportista_id
     * cliente_id
     * destino_id
     * operacion
     * contenedor
     * ferro
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

  const filtroContenedor = document.getElementById(
    "eventosFerFiltroContenedor",
  );
  const filtroFerro = document.getElementById("eventosFerFiltroFerro");

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

  function swalMsg(title, text, icon) {
    if (window.Swal) {
      Swal.fire(title, text, icon);
    } else {
      alert(text || title || "Listo");
    }
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
      <th style="min-width:220px" class="text-center">Observaciones</th>
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
      const operacionId = parseInt(r.operacion_id || 0, 10);
      const opFerroId = parseInt(r.operacion_ferro_id || 0, 10);
      const ferId = parseInt(r.contenedor_fisico_id || 0, 10);

      const key = `${opFerroId}||${ferId}`;

      if (!groups.has(key)) {
        const cells = {};
        for (const c of COLS) cells[c.key] = "-";

        groups.set(key, {
          operacion_id: operacionId,
          operacion_ferro_id: opFerroId,
          contenedor_fisico_id: ferId,

          operacion_maritima: r.operacion_maritima || r.operacion || "",
          contenedor_maritimo: r.contenedor_maritimo || "",
          cliente: r.cliente || "",
          destino: r.destino || "",
          transportista: r.transportista || "",
          ferro: r.ferro || "",
          observacion_renglon: r.observacion_renglon || "",

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

      if (!g.operacion_id && r.operacion_id) {
        g.operacion_id = parseInt(r.operacion_id || 0, 10);
      }

      if (!g.operacion_maritima && (r.operacion_maritima || r.operacion)) {
        g.operacion_maritima = r.operacion_maritima || r.operacion;
      }

      if (!g.cliente && r.cliente) g.cliente = r.cliente;

      if (!g.contenedor_maritimo && r.contenedor_maritimo) {
        g.contenedor_maritimo = r.contenedor_maritimo;
      }

      if (!g.destino && r.destino) g.destino = r.destino;

      if (!g.transportista && r.transportista) {
        g.transportista = r.transportista;
      }

      if (!g.ferro && r.ferro) g.ferro = r.ferro;

      if (!g.observacion_renglon && r.observacion_renglon) {
        g.observacion_renglon = r.observacion_renglon;
      }
    });

    return Array.from(groups.values()).sort((a, b) => {
      const ao = (a.operacion_maritima || "").localeCompare(
        b.operacion_maritima || "",
      );

      if (ao !== 0) return ao;

      return (a.ferro || "").localeCompare(b.ferro || "");
    });
  }

  function renderObsCell(row) {
    const obs = String(row.observacion_renglon || "").trim();
    const obsLabel = obs ? esc(obs) : "Sin observación";
    const obsClass = obs ? "" : "evfer-observacion-vacia";

    return `
    <td class="evfer-observacion-cell evfer-observacion-click"
        title="Clic para agregar o editar observación"
        data-operacion-id="${esc(row.operacion_id)}"
        data-operacion-ferro-id="${esc(row.operacion_ferro_id)}"
        data-contenedor-fisico-id="${esc(row.contenedor_fisico_id)}"
        data-operacion-txt="${esc(row.operacion_maritima)}"
        data-contenedor-maritimo-txt="${esc(row.contenedor_maritimo)}"
        data-ferro-txt="${esc(row.ferro)}"
        data-observacion="${esc(obs)}">
      <span class="evfer-observacion-text ${obsClass}" title="${esc(obs)}">
        ${obsLabel}
      </span>
    </td>
  `;
  }

  function renderBody(pivoted) {
    if (!tbody) return;

    tbody.innerHTML = "";

    const fixedCols = 7;

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
        ${renderObsCell(row)}
      `;

      for (const c of COLS) {
        const val = row.cells[c.key] || "-";

        html += `
          <td class="text-center evfer-cell"
              data-op="${esc(row.operacion_ferro_id)}"
              data-cfo="${esc(row.contenedor_fisico_id)}"
              data-evt="${esc(c.id)}"
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
    const contenedor = (filtroContenedor?.value || "").trim();
    const ferro = (filtroFerro?.value || "").trim();

    if (opId) {
      params.append("op_id", opId);
    } else if (opNom) {
      params.append("operacion", opNom);
    }

    if (transportistaId) params.append("transportista_id", transportistaId);
    if (clienteId) params.append("cliente_id", clienteId);
    if (destinoId) params.append("destino_id", destinoId);

    if (contenedor) params.append("contenedor", contenedor);
    if (ferro) params.append("ferro", ferro);

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

        const fixedCols = 7;

        if (tbody) {
          tbody.innerHTML = `<tr>
            <td colspan="${
              fixedCols + COLS.length
            }" class="text-center text-danger py-3">
              Error al obtener datos
            </td>
          </tr>`;
        }
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

  // ---- Filtros texto ----
  const onTextoFiltro = debounce(() => {
    currentPage = 1;
    listar();
  }, 300);

  filtroContenedor?.addEventListener("input", onTextoFiltro);
  filtroFerro?.addEventListener("input", onTextoFiltro);

  filtroContenedor?.addEventListener("keydown", (e) => {
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
          <span>${esc(it.label || "")}</span>
          <small class="text-muted">
            ${esc(it.ferro || "")}${it.contenedor ? " · " + esc(it.contenedor) : ""}
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

  // ---- Modal de observación por renglón ----
  (function evFERObsModal() {
    const modalEl = document.getElementById("modalObsRenglonFer");
    const formEl = document.getElementById("formObsRenglonFer");

    if (!modalEl || !formEl) return;

    const fldOperacionId = document.getElementById("obsFerOperacionId");
    const fldOperacionFerroId = document.getElementById(
      "obsFerOperacionFerroId",
    );
    const fldContenedorFisicoId = document.getElementById(
      "obsFerContenedorFisicoId",
    );

    const txtOperacion = document.getElementById("obsFerOperacionTxt");
    const txtContenedorMaritimo = document.getElementById(
      "obsFerContenedorMaritimoTxt",
    );
    const txtFerro = document.getElementById("obsFerFerroTxt");
    const txtObs = document.getElementById("obsFerTexto");
    const btnLimpiar = document.getElementById("btnLimpiarObsFer");
    const btnGuardar = document.getElementById("btnGuardarObsFer");

    function abrirModalObservacionDesdeCelda(cell) {
      fldOperacionId.value = cell.dataset.operacionId || "";
      fldOperacionFerroId.value = cell.dataset.operacionFerroId || "";
      fldContenedorFisicoId.value = cell.dataset.contenedorFisicoId || "";

      txtOperacion.value = cell.dataset.operacionTxt || "";
      txtContenedorMaritimo.value = cell.dataset.contenedorMaritimoTxt || "";
      txtFerro.value = cell.dataset.ferroTxt || "";
      txtObs.value = cell.dataset.observacion || "";

      new bootstrap.Modal(modalEl).show();
    }

    function guardarObservacion(valor) {
      const fd = new FormData();

      fd.append("operacion_id", (fldOperacionId.value || "").trim());
      fd.append("operacion_ferro_id", (fldOperacionFerroId.value || "").trim());
      fd.append(
        "contenedor_fisico_id",
        (fldContenedorFisicoId.value || "").trim(),
      );
      fd.append("observacion", String(valor || "").trim());

      if (btnGuardar) btnGuardar.disabled = true;
      if (btnLimpiar) btnLimpiar.disabled = true;

      xhrPost(
        `${base_url}Operaciones_maritimo_ferro_eventos_fer/guardar_observacion_renglon`,
        fd,
        (res) => {
          const ok = res && res.status === "success";

          swalMsg(
            ok ? "Éxito" : "Atención",
            res?.msg ||
              (ok ? "Observación guardada" : "No fue posible guardar"),
            ok ? "success" : "warning",
          );

          if (ok) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
            window.refreshEventosFER &&
              window.refreshEventosFER({ keepPage: true });
          }
        },
        (err) => {
          console.error("guardar_observacion_renglon:", err);

          swalMsg(
            "Error",
            err?.msg || "Error interno al guardar la observación.",
            "error",
          );
        },
      );

      setTimeout(() => {
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnLimpiar) btnLimpiar.disabled = false;
      }, 500);
    }

    document.addEventListener("click", (e) => {
      const cell = e.target.closest(".evfer-observacion-click");
      if (!cell) return;

      e.preventDefault();
      abrirModalObservacionDesdeCelda(cell);
    });

    formEl.addEventListener("submit", function (e) {
      e.preventDefault();
      guardarObservacion(txtObs.value || "");
    });

    btnLimpiar?.addEventListener("click", function () {
      const actual = (txtObs.value || "").trim();

      if (!actual) {
        txtObs.value = "";
        guardarObservacion("");
        return;
      }

      if (window.Swal) {
        Swal.fire({
          title: "¿Limpiar observación?",
          text: "La observación quedará vacía para este renglón.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, limpiar",
          cancelButtonText: "Cancelar",
        }).then((r) => {
          if (!r.isConfirmed) return;
          txtObs.value = "";
          guardarObservacion("");
        });
      } else if (confirm("¿Limpiar observación?")) {
        txtObs.value = "";
        guardarObservacion("");
      }
    });
  })();

  // ---- Modal de celda (crear/editar/eliminar desde la tabla) ----
  (function evFERCellModal() {
    let cellDirty = false;

    const modalEl = document.getElementById("modalEvtCellFer");
    const formEl = document.getElementById("formEvtCellFer");

    if (!modalEl || !formEl) return;

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

      const opId = parseInt(td.dataset.op || "0", 10);
      const cfoId = parseInt(td.dataset.cfo || "0", 10);
      const evtId = parseInt(td.dataset.evt || "0", 10);
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
          } catch (e) {
            data = null;
          }

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

      xhrPost(
        url,
        fd,
        (res) => {
          swalMsg(
            res?.status === "success" ? "Éxito" : "Atención",
            res?.msg || "Listo",
            res?.status === "success" ? "success" : "warning",
          );

          cellDirty = true;
          bootstrap.Modal.getInstance(modalEl)?.hide();

          window.refreshEventosFER &&
            window.refreshEventosFER({ keepPage: true });
        },
        (err) => {
          console.error("Guardar evento FER:", err);

          swalMsg(
            "Error",
            err?.msg || "No fue posible guardar el evento.",
            "error",
          );
        },
      );
    });

    btnDel?.addEventListener("click", function () {
      const idEv = (fldIdEv.value || "").trim();
      if (!idEv) return;

      if (window.Swal) {
        Swal.fire({
          title: "¿Eliminar evento?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, eliminar",
          cancelButtonText: "Cancelar",
        }).then((r) => {
          if (!r.isConfirmed) return;
          eliminarEvento(idEv);
        });
      } else if (confirm("¿Eliminar evento?")) {
        eliminarEvento(idEv);
      }
    });

    function eliminarEvento(idEv) {
      const fd = new FormData();
      fd.append("id_evento", idEv);

      xhrPost(
        base_url + "Operaciones_maritimo_ferro_eventos_fer/eliminar",
        fd,
        (res) => {
          swalMsg(
            res?.status === "success" ? "Eliminado" : "Atención",
            res?.msg || "Listo",
            res?.status === "success" ? "success" : "warning",
          );

          bootstrap.Modal.getInstance(modalEl)?.hide();

          window.refreshEventosFER &&
            window.refreshEventosFER({ keepPage: true });
        },
        (err) => {
          console.error("Eliminar evento FER:", err);

          swalMsg(
            "Error",
            err?.msg || "No fue posible eliminar el evento.",
            "error",
          );
        },
      );
    }

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

// ---------------------- Exportación (solo lectura) ----------------------
document
  .getElementById("btnExportarExcelEventosLogisticosFer")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "tablaEventosFer",
      formato: "xlsx",
      nombre: `Eventos Logisticos.xlsx`,
      columnasOcultas: [],
      soloVisibles: true,
      sheetName: `Eventos Logisticos`,
    });
  });

document
  .getElementById("btnExportarPDFEventosLogisticosFer")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "#tablaEventosFer",
      formato: "pdf",
      nombre: `Eventos Logisticos.pdf`,
      titulo: `Eventos Logisticos`,
      orientacion: "landscape",
      formatoPagina: "letter",
      columnasOcultas: [],
      soloVisibles: true,
    });
  });
