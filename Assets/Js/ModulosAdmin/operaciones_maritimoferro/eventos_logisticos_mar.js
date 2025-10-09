/* ===========================================================
   MODAL - Registrar Evento (Operación MF + Evento Marítimo)
   Aísla todo en un IIFE para evitar colisiones globales.
   Requiere que exista "base_url" disponible en la página.
   =========================================================== */
(function evMFModal() {
  "use strict";

  // ---------- Refs del modal ----------
  const modalEl     = document.getElementById("modalDetallesLogisticos");
  const formEl      = document.getElementById("formEventosLogisticos");
  const tituloEl    = document.getElementById("modalTituloDetalles");
  const btnSubmit   = document.getElementById("btnSubmitEventoLogistico");

  // Campos formulario
  const fldIdEvento       = document.getElementById("idEvento"); // hidden (para editar; aquí solo registrar)
  const inpOpNombre       = document.getElementById("eventoOperacionNombre");
  const hidOpId           = document.getElementById("eventoOperacionId");
  const opSugBox          = document.getElementById("eventoOperacionSugerencias");
  const opMeta            = document.getElementById("eventoOperacionMeta");

  const inpContNom        = document.getElementById("eventoContenedorNombre");
  const hidContOpId       = document.getElementById("eventoContenedorOperacionId"); // cmo.id
  const contSugBox        = document.getElementById("eventoContenedorSugerencias");
  const hidContTipo       = document.getElementById("eventoContenedorTipo"); // 'MARITIMO'

  const selTipoEvt        = document.getElementById("tipoEventoId");
  const inpFecha          = document.getElementById("fechaEventoLogistico");
  const inpComentario     = document.getElementById("comentarioEventoLogistico");

  // ---------- Utils ----------
  function xhrGet(url, cbOk, cbErr) {
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status === 200) {
        let json;
        try { json = JSON.parse(this.responseText); }
        catch { cbErr && cbErr("JSON inválido"); return; }
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
        try { json = JSON.parse(this.responseText); }
        catch { cbErr && cbErr("JSON inválido"); return; }
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
    items.forEach(it => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
      btn.innerHTML =
        `<span>${it.label || ""}</span>` +
        (it.meta ? `<small class="text-muted">${it.meta}</small>`
                  : (it.tipo ? `<small class="text-muted">${it.tipo}</small>` : ""));
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
        const id  = r.id ?? r.id_tipo_evento;
        const nom = r.nombre ?? "";
        const sel = preselectId && String(preselectId) === String(id) ? ' selected' : '';
        html += `<option value="${id}"${sel}>${nom}</option>`;
      }
    }
    selTipoEvt.innerHTML = html;
  }

  function cargarCatalogoTiposEvento(preselectId = null) {
    xhrGet(
      base_url + "Operaciones_maritimo_ferro_eventos_mar/tipos_evento",
      (rows) => fillTiposEvento(rows, preselectId),
      () => fillTiposEvento([])
    );
  }

  function limpiarContenedor() {
    if (inpContNom)  inpContNom.value = "";
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
    inpContNom.readOnly = true; // bloqueado
    inpContNom.placeholder = "Se autollenará al elegir la operación";
  }

  // ---------- Autocomplete: Operación MF (11) ----------
  if (inpOpNombre && opSugBox) {
    let tmrOp = null, lastTermOp = "";

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

        const url = base_url + "Operaciones_maritimo_ferro_eventos_mar/buscar_operaciones?term=" + encodeURIComponent(term);
        xhrGet(url, (rows) => {
          renderSugerencias(opSugBox, rows, (it) => {
            // Selección de operación
            inpOpNombre.value = it.label || "";
            hidOpId.value     = it.id || "";
            opSugBox.style.display = "none";
            if (opMeta) opMeta.textContent = it.meta || "";

            // Autollenar contenedor marítimo de esa operación
            const urlCont = base_url + "Operaciones_maritimo_ferro_eventos_mar/contenedor_maritimo_de_operacion?operacion_id=" + encodeURIComponent(it.id);
            xhrGet(urlCont, (cont) => {
              if (Array.isArray(cont)) cont = cont[0] || null; // tolerante

              // asegurar que no esté disabled
              if (inpContNom) inpContNom.disabled = false;

              if (cont && cont.id) {
                inpContNom.value  = cont.label || "";  // número contenedor
                hidContOpId.value = cont.id;           // cmo.id
                hidContTipo.value = "MARITIMO";

                // Catálogo marítimo
                cargarCatalogoTiposEvento();
              } else {
                limpiarContenedor();
                fillTiposEvento([]);
              }
            }, () => {
              limpiarContenedor();
              fillTiposEvento([]);
            });
          });
        }, () => { opSugBox.style.display = "none"; });
      }, 250);
    });

    // Cierra la caja de sugerencias si haces click fuera
    document.addEventListener("click", (e) => {
      if (!opSugBox.contains(e.target) && e.target !== inpOpNombre) {
        opSugBox.style.display = "none";
      }
    });
  }

  // ---------- Desactivar autocomplete del contenedor (está readOnly) ----------
  // Si en el futuro decides permitir búsqueda manual, quita el readOnly y activa esto.
  if (inpContNom && contSugBox && hidOpId && !inpContNom.readOnly) {
    let tmrCont = null, lastTermCont = "";
    inpContNom.addEventListener("input", () => {
      const term = inpContNom.value.trim();
      const opId = parseInt(hidOpId.value || "0", 10);

      // reset selección al tipear
      if (hidContOpId) hidContOpId.value = "";
      if (hidContTipo) hidContTipo.value = "";

      if (!opId) { contSugBox.style.display = "none"; return; }
      if (term.length === 0) { contSugBox.style.display = "none"; return; }

      clearTimeout(tmrCont);
      tmrCont = setTimeout(() => {
        if (term === lastTermCont) return;
        lastTermCont = term;

        const url = base_url + "Operaciones_maritimo_ferro_eventos_mar/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
        xhrGet(url, (rows) => {
          renderSugerencias(contSugBox, rows, (it) => {
            inpContNom.value  = it.label || "";
            hidContOpId.value = it.id || "";
            hidContTipo.value = it.tipo || "MARITIMO";
            contSugBox.style.display = "none";
            cargarCatalogoTiposEvento();
          });
        }, () => { contSugBox.style.display = "none"; });
      }, 250);
    });

    document.addEventListener("click", (e) => {
      if (!contSugBox.contains(e.target) && e.target !== inpContNom) {
        contSugBox.style.display = "none";
      }
    });
  }

  // ---------- Submit (Registrar) ----------
  if (formEl) {
    formEl.addEventListener("submit", function (e) {
      e.preventDefault();

      const operacionId = (hidOpId.value || "").trim();
      const cmoId       = (hidContOpId.value || "").trim();
      const tipoEvtId   = (selTipoEvt.value || "").trim();
      const fecha       = (inpFecha.value || "").trim();
      const comentario  = (inpComentario.value || "").trim();

      // Validaciones mínimas (coinciden con el controlador)
      if (!operacionId) { return Swal?.fire("Campos requeridos", "Selecciona una operación (MF).", "warning"); }
      if (!cmoId)       { return Swal?.fire("Campos requeridos", "No hay contenedor marítimo para la operación.", "warning"); }
      if (!tipoEvtId)   { return Swal?.fire("Campos requeridos", "Selecciona un tipo de evento marítimo.", "warning"); }
      if (!fecha)       { return Swal?.fire("Campos requeridos", "Indica la fecha del evento.", "warning"); }

      const fd = new FormData();
      fd.append("operacion_id", operacionId);
      fd.append("cont_maritimo_operacion_id", cmoId);
      fd.append("tipo_evento_id", tipoEvtId);
      fd.append("fecha", fecha);
      fd.append("comentario", comentario);

      setBtnSubmitting(true);
      xhrPost(
        base_url + "Operaciones_maritimo_ferro_eventos_mar/registrar",
        fd,
        (res) => {
          setBtnSubmitting(false);
          // Esperamos status: success | warning | error (como dejamos en controlador)
          const icon = res.status === "success" ? "success" : (res.status === "warning" ? "warning" : "error");
          Swal?.fire(res.status === "success" ? "Éxito" : "Atención", res.msg || "Respuesta recibida.", icon);

          if (res.status === "success") {
            // Reset modal y cierre
            formEl.reset();
            limpiarContenedor();
            fillTiposEvento([]);
            if (tituloEl) tituloEl.textContent = "Registrar Evento";
            if (window.feather) feather.replace();

            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();

            // Si tienes una función global para refrescar la tabla principal, llámala aquí:
            // listarEventosMF();  // <- por ejemplo
          }
        },
        (err) => {
          setBtnSubmitting(false);
          console.error("Registrar evento (err):", err);
          Swal?.fire("Error", "No fue posible registrar el evento.", "error");
        }
      );
    });
  }

  // ---------- Ajustes de UI ----------
  if (window.feather) feather.replace();

  // Si quieres inicializar el modal “limpio” al abrir con el botón principal:
  document.getElementById("btnAbrirModalDetalles")?.addEventListener("click", () => {
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

})();

/* ===============================================================
   Listado MF con columnas dinámicas (pivot por evento)
   - Construye thead con eventos marítimos
   - Pivotea filas: (operación + contenedor) => fechas por evento
   - "Sin registrar" cuando no hay fecha
   =============================================================== */
(function evMFListPivot() {
  "use strict";

  // ---- Refs UI ----
  const theadRow = document.getElementById("theadEventosMar");
  const tbody    = document.getElementById("tbodyEventosMar");
  const pagBox   = document.getElementById("evMarPaginacion");
  const metaBox  = document.getElementById("evMarMetaResumen");
  const perPageSel = document.getElementById("evMarPerPage");
  const buscarEl   = document.getElementById("buscarEventosMar");
  const filtroOpId   = document.getElementById("eventosFiltroOpId");
  const filtroContId = document.getElementById("eventosFiltroContMarId");

  // ---- Estado ----
  let COLS = [];               // [{id, nombre, key}]
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
      } else err && err(this.responseText || "HTTP error");
    };
    http.send();
  }

  // Construye THEAD con columnas fijas + dinámicas + Acciones
  function buildHead() {
    if (!theadRow) return;
    // Limpia y agrega dos fijas
    theadRow.innerHTML = `
      <th style="min-width:140px">Operación</th>
      <th style="min-width:180px">Contenedor marítimo</th>
    `;
    // Dinámicas por cada evento
    for (const c of COLS) {
      const th = document.createElement("th");
      th.setAttribute("data-evt-id", c.id);
      th.textContent = c.nombre;
      th.className = "text-center";
      theadRow.appendChild(th);
    }
 
  }

  // Agrupa y pivotea: de filas por evento -> filas por (op + contenedor)
  function pivotRows(rows) {
    const byEvtId = new Map(COLS.map(c => [String(c.id), c])); // para ubicar key
    const groups = new Map(); // key => {operacion, contenedor, operacion_id, cmo_id, cells{}}

    (rows || []).forEach(r => {
      const opId  = r.operacion_id;
      const cmoId = r.cont_maritimo_operacion_id;
      const key = `${opId}||${cmoId}`;
      if (!groups.has(key)) {
        // inicializa con "Sin registrar"
        const cells = {};
        for (const c of COLS) cells[c.key] = "Sin registrar";
        groups.set(key, {
          operacion: r.operacion || "",
          contenedor: r.contenedor || "",
          operacion_id: opId,
          cmo_id: cmoId,
          cells
        });
      }
      // coloca la fecha en la columna del evento
      const g = groups.get(key);
      const c = byEvtId.get(String(r.tipo_evento_id));
      if (c) {
        // si llega el mismo evento más de una vez, deja la más reciente (comparación simple)
        const prev = g.cells[c.key];
        const val  = r.fecha || "Sin registrar";
        if (prev === "Sin registrar" || String(val) > String(prev)) {
          g.cells[c.key] = val;
        }
      }
    });

    // Devuelve array en orden alfabético por operación y contenedor
    return Array.from(groups.values()).sort((a, b) => {
      const ao = (a.operacion || "").localeCompare(b.operacion || "");
      if (ao !== 0) return ao;
      return (a.contenedor || "").localeCompare(b.contenedor || "");
    });
  }

  // Renderiza TBODY con las filas pivoteadas
  function renderBody(pivoted) {
    if (!tbody) return;
    tbody.innerHTML = "";

    if (!Array.isArray(pivoted) || pivoted.length === 0) {
      tbody.innerHTML = `<tr><td colspan="${2 + COLS.length}" class="text-center text-muted py-3">No hay registros</td></tr>`;
      return;
    }

    for (const row of pivoted) {
      const tr = document.createElement("tr");
      let html = `
        <td class="text-center">${row.operacion}</td>
        <td class="text-center">${row.contenedor}</td>
      `;
// dentro de renderBody(), al pintar cada columna dinámica:
for (const c of COLS) {
  const val = row.cells[c.key] || "Sin registrar";
  html += `
    <td class="text-center evmf-cell"
        data-op="${row.operacion_id}"
        data-cmo="${row.cmo_id}"
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
    const q = buscarEl?.value?.trim() || "";
    const opId   = filtroOpId?.value || "";
    const contId = filtroContId?.value || "";

    const url = `${base_url}Operaciones_maritimo_ferro_eventos_mar/listar?page=${currentPage}&per_page=${perPage}`
              + (opId   ? `&op_id=${encodeURIComponent(opId)}`   : "")
              + (contId ? `&cont_id=${encodeURIComponent(contId)}`: "")
              + (q      ? `&q=${encodeURIComponent(q)}`           : "");

    xhrGet(url, (res) => {
      // res.data = filas por evento
      const pivoted = pivotRows(res.data || []);
      renderBody(pivoted);
      totalRows = res.total || pivoted.length || 0; // usa total del backend para paginar
      renderPagination(currentPage, totalRows, perPage);
      renderMeta(currentPage, totalRows, perPage);
    }, (err) => {
      console.error("Listar MF:", err);
      tbody.innerHTML = `<tr><td colspan="${2 + COLS.length }" class="text-center text-danger py-3">Error al obtener datos</td></tr>`;
    });
  }

  // ---- Cargar columnas (si no existen) y luego listar ----
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
        const cols = (json && json.columns) ? json.columns : [];
        COLS = Array.isArray(cols) ? cols : [];
        // guarda global por si otros módulos la quieren reutilizar
        window.__evMarCols = COLS;
        buildHead();
        listar();
      },
      () => {
        COLS = [];
        buildHead(); // solo fijas + acciones
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

  // Si cambias filtros externos (operación/cont), dispara listar()
  document.getElementById("eventosFiltroOpNombre")?.addEventListener("change", () => { currentPage = 1; listar(); });
  document.getElementById("eventosFiltroContMarNombre")?.addEventListener("change", () => { currentPage = 1; listar(); });

 

  // ---- Init ----
  init();



  (function evMFCellModal(){
  const modalEl = document.getElementById('modalEvtCell');
  const formEl  = document.getElementById('formEvtCell');

  const fldOpId = document.getElementById('cellOpId');
  const fldCmoId= document.getElementById('cellCmoId');
  const fldEvtId= document.getElementById('cellEvtId');
  const fldIdEv = document.getElementById('cellIdEvento');

  const opTxt   = document.getElementById('cellOpTxt');
  const ctnTxt  = document.getElementById('cellCtnTxt');
  const evtTxt  = document.getElementById('cellEvtTxt');
  const fecha   = document.getElementById('cellFecha');
  const comenta = document.getElementById('cellComentario');
  const btnDel  = document.getElementById('btnCellDelete');

  // 1) Abrir modal desde una celda
  document.addEventListener('click', (e) => {
    const td = e.target.closest('.evmf-cell');
    if (!td) return;

    const opId  = +td.dataset.op;
    const cmoId = +td.dataset.cmo;
    const evtId = +td.dataset.evt;
    const evtName = td.dataset.evtname || '';

    // Mostrar cabecera
    document.getElementById('modalEvtCellTitle').textContent = evtName;

    // Cargar textos de fila (ya están en el DOM de la misma fila)
    const tr = td.parentElement;
    opTxt.value  = tr.children[0].textContent.trim(); // Operación
    ctnTxt.value = tr.children[1].textContent.trim(); // Contenedor
    evtTxt.value = evtName;

    // Set hidden
    fldOpId.value = opId;
    fldCmoId.value= cmoId;
    fldEvtId.value= evtId;
    fldIdEv.value = '';

    // Consultar si existe evento
    const url = `${base_url}Operaciones_maritimo_ferro_eventos_mar/obtener_por_clave?operacion_id=${opId}&cont_maritimo_operacion_id=${cmoId}&tipo_evento_id=${evtId}`;
    const http = new XMLHttpRequest();
    http.open('GET', url, true);
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status === 200){
        let data = null; try { data = JSON.parse(this.responseText); } catch {}
        if (data && data.id_evento){
          // modo editar
          fldIdEv.value = data.id_evento;
          fecha.value   = (data.fecha || '').substring(0,10);
          comenta.value = data.comentario || '';
          btnDel.classList.remove('d-none');
        } else {
          // modo crear
          fecha.value = '';
          comenta.value = '';
          btnDel.classList.add('d-none');
        }
        new bootstrap.Modal(modalEl).show();
      }
    };
    http.send();
  });

  // 2) Guardar (crear/actualizar)
  formEl.addEventListener('submit', function(e){
    e.preventDefault();

    const idEv = fldIdEv.value.trim();
    const fd = new FormData();
    if (idEv) fd.append('id_evento', idEv);
    fd.append('operacion_id', fldOpId.value);
    fd.append('cont_maritimo_operacion_id', fldCmoId.value);
    fd.append('tipo_evento_id', fldEvtId.value);
    fd.append('fecha', fecha.value);
    fd.append('comentario', comenta.value);

    const url = base_url + 'Operaciones_maritimo_ferro_eventos_mar/' + (idEv ? 'actualizar' : 'registrar');
    const http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status === 200){
        let res=null; try{res=JSON.parse(this.responseText);}catch{}
        Swal?.fire(res?.status==='success'?'Éxito':'Atención', res?.msg||'Listo', res?.status||'success');
        bootstrap.Modal.getInstance(modalEl)?.hide();
        // refresca listado
        window.listarEventosMF && window.listarEventosMF();
      }
    };
    http.send(fd);
  });

  // 3) Eliminar (baja lógica)
  btnDel.addEventListener('click', function(){
    const idEv = fldIdEv.value.trim();
    if (!idEv) return;
    Swal?.fire({title:'¿Eliminar evento?', icon:'warning', showCancelButton:true}).then(r=>{
      if (!r.isConfirmed) return;
      const fd = new FormData();
      fd.append('id_evento', idEv);
      const http = new XMLHttpRequest();
      http.open('POST', base_url+'Operaciones_maritimo_ferro_eventos_mar/eliminar', true);
      http.onreadystatechange = function(){
        if (this.readyState !== 4) return;
        let res=null; try{res=JSON.parse(this.responseText);}catch{}
        Swal?.fire(res?.status==='success'?'Eliminado':'Atención', res?.msg||'Listo', res?.status==='success'?'success':'warning');
        bootstrap.Modal.getInstance(modalEl)?.hide();
        window.listarEventosMF && window.listarEventosMF();
      };
      http.send(fd);
    });
  });

})();

})();
