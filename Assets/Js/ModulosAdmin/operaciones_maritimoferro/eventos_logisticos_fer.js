/* ===========================================================
   MODAL - Registrar/Editar Evento (Operación FO + Evento TER)
   Versión FER: usa IDs *Fer* y endpoints del controlador FER.
   Requiere "base_url" global.
   =========================================================== */
(function evFERModal() {
  "use strict";

  // ---------- Refs del modal ----------
  const modalEl = document.getElementById("modalDetallesLogisticosFer");
  const formEl = document.getElementById("formEventosLogisticosFer");
  const tituloEl = document.getElementById("modalTituloDetallesFer");
  const btnSubmit = document.getElementById("btnSubmitEventoLogisticoFer");

  // Campos formulario
  const fldIdEvento = document.getElementById("idEventoFer"); // hidden (para editar desde modal)
  const inpOpNombre = document.getElementById("eventoOperacionNombreFer");
  const hidOpId = document.getElementById("eventoOperacionIdFer");
  const opSugBox = document.getElementById("eventoOperacionSugerenciasFer");
  const opMeta = document.getElementById("eventoOperacionMetaFer");

  const inpContNom = document.getElementById("eventoContenedorNombreFer");
  const hidContOpId = document.getElementById("eventoContenedorOperacionIdFer"); // id_fisico (ferro/caja)
  const contSugBox = document.getElementById("eventoContenedorSugerenciasFer"); // (no se usa: readonly)
  const hidContTipo = document.getElementById("eventoContenedorTipoFer"); // 'FERRO' (opcional informativo)

  const selTipoEvt = document.getElementById("tipoEventoIdFer");
  const inpFecha = document.getElementById("fechaEventoLogisticoFer");
  const inpComentario = document.getElementById("comentarioEventoLogisticoFer");

  // ---------- Utils ----------
  function xhrGet(url, cbOk, cbErr) {
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status === 200) {
        let json;
        try {
          json = JSON.parse(this.responseText);
        } catch {
          cbErr && cbErr("JSON inválido");
          return;
        }
        cbOk && cbOk(json);
      } else {
        cbErr && cbErr(this.responseText || "HTTP error");
      }
    };
    http.send();
  }

  function xhrPost(url, formData, cbOk, cbErr) {
    const http = new XMLHttpRequest();
    http.open("POST", url, true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status === 200) {
        let json;
        try {
          json = JSON.parse(this.responseText);
        } catch {
          cbErr && cbErr("JSON inválido");
          return;
        }
        cbOk && cbOk(json);
      } else {
        cbErr && cbErr(this.responseText || "HTTP error");
      }
    };
    http.send(formData);
  }

  function renderSugerencias(listEl, items, onPick) {
    listEl.innerHTML = "";
    if (!Array.isArray(items) || items.length === 0) {
      listEl.style.display = "none";
      return;
    }
    items.forEach((it) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className =
        "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
      // Para FO mostramos número de operación y ferro si viene
      btn.innerHTML =
        `<span>${it.label || ""}</span>` +
        (it.ferro ? `<small class="text-muted">${it.ferro}</small>` : "");
      btn.addEventListener("click", () => onPick(it));
      listEl.appendChild(btn);
    });
    listEl.style.display = "block";
  }

  function fillTiposEvento(lista, preselectId = null) {
    if (!selTipoEvt) return;
    let html = '<option value="">Selecciona...</option>';
    if (Array.isArray(lista)) {
      for (const r of lista) {
        const id = r.id ?? r.id_tipo_evento;
        const nom = r.nombre ?? "";
        const sel =
          preselectId && String(preselectId) === String(id) ? " selected" : "";
        html += `<option value="${id}"${sel}>${nom}</option>`;
      }
    }
    selTipoEvt.innerHTML = html;
  }

  function cargarCatalogoTiposEvento(preselectId = null) {
    xhrGet(
      base_url + "Operaciones_maritimo_ferro_eventos_fer/tipos_evento",
      (rows) => fillTiposEvento(rows, preselectId),
      () => fillTiposEvento([]),
    );
  }

  function limpiarContenedor() {
    if (inpContNom) inpContNom.value = "";
    if (hidContOpId) hidContOpId.value = "";
    if (hidContTipo) hidContTipo.value = "";
  }

  function setBtnSubmitting(flag) {
    if (!btnSubmit) return;
    btnSubmit.disabled = !!flag;
    btnSubmit.innerHTML = flag
      ? '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...'
      : '<i data-feather="save" class="me-1"></i> Guardar';
    if (window.feather) feather.replace();
  }

  // ---------- Estado inicial del input de contenedor ----------
  if (inpContNom) {
    inpContNom.readOnly = true; // FO: se autollenará por la operación
    inpContNom.placeholder = "Se autollenará al elegir la operación";
  }

  // ---------- Autocomplete: Operación FO ----------
  // (Se mantiene: aunque FO ya no sea “operación separada” en tu negocio,
  //  el evento sigue guardándose contra operacion_ferro_id en backend.)
  if (inpOpNombre && opSugBox) {
    let tmrOp = null,
      lastTermOp = "";

    inpOpNombre.addEventListener("input", () => {
      const term = inpOpNombre.value.trim();

      // Reset selección y contenedor dependiente
      hidOpId.value = "";
      if (opMeta) opMeta.textContent = "";
      limpiarContenedor();
      fillTiposEvento([]); // limpiar catálogo mientras no haya contenedor

      if (term.length === 0) {
        opSugBox.style.display = "none";
        return;
      }

      clearTimeout(tmrOp);
      tmrOp = setTimeout(() => {
        if (term === lastTermOp) return;
        lastTermOp = term;

        const url =
          base_url +
          "Operaciones_maritimo_ferro_eventos_fer/sugerir_operaciones?term=" +
          encodeURIComponent(term);
        xhrGet(
          url,
          (rows) => {
            renderSugerencias(opSugBox, rows, (it) => {
              // Selección de operación
              inpOpNombre.value = it.label || "";
              hidOpId.value = it.id || "";
              opSugBox.style.display = "none";
              if (opMeta)
                opMeta.textContent = it.ferro ? "Ferro: " + it.ferro : "";

              // Autollenar ferro 1:1 de esa operación
              const urlFerro =
                base_url +
                "Operaciones_maritimo_ferro_eventos_fer/ferro_de_operacion?operacion_id=" +
                encodeURIComponent(it.id);
              xhrGet(
                urlFerro,
                (fer) => {
                  if (Array.isArray(fer)) fer = fer[0] || null; // tolerante

                  // asegurar que no esté disabled
                  if (inpContNom) inpContNom.disabled = false;

                  if (fer && fer.id) {
                    inpContNom.value = fer.label || ""; // número ferro/caja
                    hidContOpId.value = fer.id; // id_fisico
                    hidContTipo.value = "FERRO";
                    // Cargar catálogo de eventos terrestres
                    cargarCatalogoTiposEvento();
                  } else {
                    limpiarContenedor();
                    fillTiposEvento([]);
                  }
                },
                () => {
                  limpiarContenedor();
                  fillTiposEvento([]);
                },
              );
            });
          },
          () => {
            opSugBox.style.display = "none";
          },
        );
      }, 250);
    });

    // Cierra la caja de sugerencias si haces click fuera
    document.addEventListener("click", (e) => {
      if (!opSugBox.contains(e.target) && e.target !== inpOpNombre) {
        opSugBox.style.display = "none";
      }
    });
  }

  // ---------- Submit (Registrar / Actualizar si quisieras desde este modal) ----------
  if (formEl) {
    formEl.addEventListener("submit", function (e) {
      e.preventDefault();

      const opId = (hidOpId.value || "").trim(); // operacion_ferro_id (legacy)
      const ferroId = (hidContOpId.value || "").trim(); // id_fisico
      const tipoEvtId = (selTipoEvt.value || "").trim();
      const fecha = (inpFecha.value || "").trim();
      const comentario = (inpComentario.value || "").trim();

      if (!opId) {
        return Swal?.fire(
          "Campos requeridos",
          "Selecciona una operación (FO).",
          "warning",
        );
      }
      if (!ferroId) {
        return Swal?.fire(
          "Campos requeridos",
          "No hay ferro/caja ligado a la operación.",
          "warning",
        );
      }
      if (!tipoEvtId) {
        return Swal?.fire(
          "Campos requeridos",
          "Selecciona un tipo de evento terrestre.",
          "warning",
        );
      }
      if (!fecha) {
        return Swal?.fire(
          "Campos requeridos",
          "Indica la fecha del evento.",
          "warning",
        );
      }

      const fd = new FormData();
      fd.append("operacion_ferro_id", opId);
      fd.append("contenedor_fisico_id", ferroId);
      fd.append("tipo_evento_id", tipoEvtId);
      fd.append("fecha", fecha);
      fd.append("comentario", comentario);

      setBtnSubmitting(true);
      xhrPost(
        base_url + "Operaciones_maritimo_ferro_eventos_fer/registrar",
        fd,
        (res) => {
          setBtnSubmitting(false);
          const icon =
            res.status === "success"
              ? "success"
              : res.status === "warning"
                ? "warning"
                : "error";
          Swal?.fire(
            res.status === "success" ? "Éxito" : "Atención",
            res.msg || "Respuesta recibida.",
            icon,
          );

          if (res.status === "success") {
            // Reset modal y cierre
            formEl.reset();
            limpiarContenedor();
            fillTiposEvento([]);
            if (tituloEl) tituloEl.textContent = "Registrar Evento";
            if (window.feather) feather.replace();

            const modal =
              bootstrap.Modal.getInstance(modalEl) ||
              new bootstrap.Modal(modalEl);
            modal.hide();

            // Refresca tabla principal (FER)
            window.refreshEventosFER &&
              window.refreshEventosFER({ keepPage: true });
          }
        },
        (err) => {
          setBtnSubmitting(false);
          console.error("Registrar evento FER (err):", err);
          Swal?.fire("Error", "No fue posible registrar el evento.", "error");
        },
      );
    });
  }

  // ---------- Limpieza al abrir desde el botón ----------
  document
    .getElementById("btnAbrirModalDetallesFer")
    ?.addEventListener("click", () => {
      if (formEl) formEl.reset();
      if (tituloEl) tituloEl.textContent = "Registrar Evento";
      limpiarContenedor();
      fillTiposEvento([]);
      if (inpContNom) {
        inpContNom.readOnly = true;
        inpContNom.disabled = false; // por si alguien lo deshabilitó
        inpContNom.placeholder = "Se autollenará al elegir la operación";
      }
      if (window.feather) feather.replace();
    });

  if (window.feather) feather.replace();
})();

/* ===============================================================
   Listado FER con columnas dinámicas (pivot por evento)
   ✅ ACTUALIZADO:
   - Ahora pinta columnas extra: Cliente, Estatus Marítima, Destino, Transportista
   - Toma estos datos desde el backend (joins a marítima)
   - Mantiene pivot por (operacion_ferro_id + contenedor_fisico_id)
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
      } else err && err(this.responseText || "HTTP error");
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

  // Construye THEAD con columnas fijas + dinámicas
  function buildHead() {
    if (!theadRow) return;

    theadRow.innerHTML = `
    <th style="min-width:160px" class="text-center">Operación Marítima</th>
    <th style="min-width:190px" class="text-center">Contenedor Marítimo</th>
    <th style="min-width:220px" class="text-center">Cliente</th>
    <!--<th style="min-width:180px" class="text-center">Ubicación Actual</th>-->
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

  // Agrupa y pivotea: de filas por evento -> filas por (op + ferro)
  function pivotRows(rows) {
    const byEvtId = new Map(COLS.map((c) => [String(c.id), c]));
    const groups = new Map(); // key => {maritima, cliente, estatus, destino, transportista, ferro, op_id, ferro_id, cells{}}

    (rows || []).forEach((r) => {
      // Backend (actualizado) puede devolver:
      // operacion_maritima, cliente, estatus_maritima, destino, transportista
      // y mantiene: operacion (FO/segmento), ferro, operacion_ferro_id, contenedor_fisico_id, tipo_evento_id, fecha
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
          //ubicacion_actual: r.ubicacion_actual || "SIN REGISTRAR",
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

      // En caso de que unas filas traigan vacío y otras sí, mantenemos el “mejor” valor
      if (!g.operacion_maritima && (r.operacion_maritima || r.operacion)) {
        g.operacion_maritima = r.operacion_maritima || r.operacion;
      }
      if (!g.cliente && r.cliente) g.cliente = r.cliente;
      if (!g.contenedor_maritimo && r.contenedor_maritimo)
        g.contenedor_maritimo = r.contenedor_maritimo;
      /*if (
        (!g.ubicacion_actual || g.ubicacion_actual === "SIN REGISTRAR") &&
        r.ubicacion_actual
      )
        g.ubicacion_actual = r.ubicacion_actual;*/
      if (!g.destino && r.destino) g.destino = r.destino;
      if (!g.transportista && r.transportista)
        g.transportista = r.transportista;
      if (!g.ferro && r.ferro) g.ferro = r.ferro;
    });

    // Orden: Marítima -> Ferro
    return Array.from(groups.values()).sort((a, b) => {
      const ao = (a.operacion_maritima || "").localeCompare(
        b.operacion_maritima || "",
      );
      if (ao !== 0) return ao;
      return (a.ferro || "").localeCompare(b.ferro || "");
    });
  }

  // Renderiza TBODY con las filas pivoteadas
  function renderBody(pivoted) {
    if (!tbody) return;
    tbody.innerHTML = "";

    const fixedCols = 7; // ✅ ahora son 7 fijas
    if (!Array.isArray(pivoted) || pivoted.length === 0) {
      tbody.innerHTML = `<tr><td colspan="${fixedCols + COLS.length}" class="text-center text-muted py-3">No hay registros</td></tr>`;
      return;
    }

    for (const row of pivoted) {
      const tr = document.createElement("tr");

      // ✅ labels para el modal (operación y contenedor)
      tr.dataset.oplabel = row.operacion_maritima || "";
      //tr.dataset.ctnlabel = row.ferro || "";

      tr.dataset.ctnlabel =
        `${row.ferro || ""} / ${row.contenedor_maritimo || ""}`.trim();

      let html = `
  <td class="text-center">${esc(row.operacion_maritima)}</td>
  <td class="text-center">${esc(row.contenedor_maritimo)}</td>
  <td>${esc(row.cliente)}</td>
 <!-- <td class="text-center">${esc(row.ubicacion_actual)}</td>-->
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
    for (let p = s; p <= e; p++) mk(p, String(p), false, p === page);
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

  // ---- Core: pedir datos y pintar ----
  function listar() {
    const opId = (filtroOpId?.value || "").trim();

    const url =
      `${base_url}Operaciones_maritimo_ferro_eventos_fer/listar?page=${currentPage}&per_page=${perPage}` +
      (opId ? `&op_id=${encodeURIComponent(opId)}` : "");

    xhrGet(
      url,
      (res) => {
        const pivoted = pivotRows(res.data || []);
        renderBody(pivoted);

        // ✅ si el backend ya devuelve total de “pares” úsalo; si no, cae a pivoted.length
        totalRows =
          res && typeof res.total !== "undefined"
            ? res.total || 0
            : pivoted.length || 0;

        renderPagination(currentPage, totalRows, perPage);
        renderMeta(currentPage, totalRows, perPage);
      },
      (err) => {
        console.error("Listar FER:", err);
        const fixedCols = 6;
        tbody.innerHTML = `<tr><td colspan="${fixedCols + COLS.length}" class="text-center text-danger py-3">Error al obtener datos</td></tr>`;
      },
    );
  }

  // Exponer refresco público
  window.refreshEventosFER = function (opts = { keepPage: true }) {
    if (!opts.keepPage) currentPage = 1;
    listar();
  };

  // ---- Cargar columnas (con keys) y luego listar ----
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
        window.__evFerCols = COLS; // cache global
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
        btn.innerHTML =
          `<span>${it.label || ""}</span>` +
          (it.ferro ? `<small class="text-muted">${it.ferro}</small>` : "");
        btn.addEventListener("click", () => onPick(it));
        filtroOpBox.appendChild(btn);
      });
      filtroOpBox.style.display = "block";
    }

    function pickOperacion(it) {
      filtroOpNom.value = it.label || "";
      filtroOpId.value = it.id || "";
      if (filtroOpMeta)
        filtroOpMeta.textContent = it.ferro ? "Ferro: " + it.ferro : "";
      filtroOpBox.style.display = "none";
      currentPage = 1;
      listar();
    }

    function clearSeleccion() {
      filtroOpId.value = "";
      if (filtroOpMeta) filtroOpMeta.textContent = "";
    }

    let tmr = null,
      lastTerm = "";
    filtroOpNom.addEventListener("input", () => {
      const term = (filtroOpNom.value || "").trim();
      clearSeleccion();
      if (term.length === 0) {
        filtroOpBox.style.display = "none";
        currentPage = 1;
        listar();
        return;
      }
      clearTimeout(tmr);
      tmr = setTimeout(() => {
        if (term === lastTerm) return;
        lastTerm = term;
        const url = `${base_url}Operaciones_maritimo_ferro_eventos_fer/sugerir_operaciones?term=${encodeURIComponent(
          term,
        )}&limit=10`;
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
    const fldCfoId = document.getElementById("cellCfoIdFer"); // id_fisico
    const fldEvtId = document.getElementById("cellEvtIdFer");
    const fldIdEv = document.getElementById("cellIdEventoFer");

    const opTxt = document.getElementById("cellOpTxtFer");
    const ctnTxt = document.getElementById("cellCtnTxtFer");
    const evtTxt = document.getElementById("cellEvtTxtFer");
    const fecha = document.getElementById("cellFechaFer");
    const comenta = document.getElementById("cellComentarioFer");
    const btnDel = document.getElementById("btnCellDeleteFer");

    // 1) Abrir modal desde una celda
    document.addEventListener("click", (e) => {
      const td = e.target.closest(".evfer-cell");
      if (!td) return;

      const opId = +td.dataset.op; // operacion_ferro_id (se mantiene)
      const cfoId = +td.dataset.cfo; // id_fisico
      const evtId = +td.dataset.evt;
      const evtName = td.dataset.evtname || "";

      document.getElementById("modalEvtCellTitleFer").textContent = evtName;

      const tr = td.parentElement;
      opTxt.value = tr.dataset.oplabel || ""; // ahora muestra marítima
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

    // 2) Guardar (crear/actualizar)
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

    // 3) Eliminar (baja lógica)
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

  // ---- Init ----
  init();
})();

// ===============================
// Exportaciones
// ===============================
document
  .getElementById("btnExportarExcelEventosLogisticosFer")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "tablaEventosFer",
      formato: "xlsx",
      nombre: "EventosFerro.xlsx",
      columnasOcultas: [],
      soloVisibles: true,
      sheetName: "Contenedores En Operacion",
    });
  });

document
  .getElementById("btnExportarPDFEventosLogisticosFer")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "#tablaEventosFer",
      formato: "pdf",
      nombre: "EventosFerro.pdf",
      titulo: "Eventos Logísticos Ferro",
      orientacion: "landscape",
      formatoPagina: "letter",
      columnasOcultas: [],
      soloVisibles: true,
    });
  });
