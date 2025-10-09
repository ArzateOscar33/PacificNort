 
/* ===============================
   SOLO HEADERS DE LA TABLA (THEAD)
   Tabla: #tablaEventosMar
   Fila thead: #theadEventosMar
   =============================== */

(function initEventosMarHeaders(){
  "use strict";

  const theadRow = document.getElementById("theadEventosMar");
  if (!theadRow) return;

  // Paso 1: limpiar y poner columnas fijas
  function resetTheadBase() {
    theadRow.innerHTML = `
      <th style="min-width:140px;">Operación</th>
      <th style="min-width:180px;">Contenedor marítimo</th>
    `;
  }

  // Paso 2: pedir catálogo de columnas (eventos marítimos)
  function cargarColumnas() {
    const url = base_url + "Operaciones_maritimo_ferro_eventos_mar/eventos_maritimos_columnas";
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      // En cualquier error, dejamos solo las columnas fijas
      if (this.status !== 200) { resetTheadBase(); return; }

      let json;
      try { json = JSON.parse(this.responseText); }
      catch { resetTheadBase(); return; }

      const cols = Array.isArray(json?.columns) ? json.columns : [];
      construirThead(cols);
    };
    http.send();
  }

  // Paso 3: construir thead (fijas + dinámicas)
  function construirThead(columnas) {
    resetTheadBase();

    if (!Array.isArray(columnas) || columnas.length === 0) {
      // Sin columnas dinámicas, nos quedamos con las fijas
      if (window.feather) feather.replace();
      return;
    }

    columnas.forEach(col => {
      const th = document.createElement("th");
      th.textContent = String(col.nombre || "");
      th.setAttribute("data-evt-id",  String(col.id ?? ""));
      th.setAttribute("data-evt-key", String(col.key ?? ""));
      theadRow.appendChild(th);
    });

    if (window.feather) feather.replace();
  }

  // Init
  resetTheadBase();
  cargarColumnas();

})();
 
 
/* ===========================================================
   MODAL - SUGERENCIAS (Operación MF y Contenedor marítimo)
   IDs tomados de tu vista.
   Endpoints usados:
     - GET  Operaciones_maritimo_ferro_eventos_mar/buscar_operaciones?term=...
     - GET  Operaciones_maritimo_ferro_eventos_mar/buscar_contenedores?operacion_id=...&term=...
     - GET  Operaciones_maritimo_ferro_eventos_mar/tipos_evento  (opcional, para llenar combo)
   =========================================================== */
(function modalEventosMF(){
  "use strict";

  // ---- Refs del modal ----
  const inpOpNombre   = document.getElementById("eventoOperacionNombre");
  const hidOpId       = document.getElementById("eventoOperacionId");
  const opSugBox      = document.getElementById("eventoOperacionSugerencias");
  const opMeta        = document.getElementById("eventoOperacionMeta");

  const inpContNom    = document.getElementById("eventoContenedorNombre");
  const hidContOpId   = document.getElementById("eventoContenedorOperacionId"); // cmo.id
  const contSugBox    = document.getElementById("eventoContenedorSugerencias");
  const hidContTipo   = document.getElementById("eventoContenedorTipo"); // 'MARITIMO'

  const selTipoEvt    = document.getElementById("tipoEventoId"); // opcional: llenar catálogo tras seleccionar contenedor
if (inpContNom) {
  inpContNom.readOnly = true; // bloqueado
  inpContNom.placeholder = "Se autollenará al elegir la operación";
}
  // ----- Utils -----
  function xhrGet(url, cbOk, cbErr){
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status === 200){
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

  function renderSugerencias(listEl, items, onPick){
    listEl.innerHTML = "";
    if (!Array.isArray(items) || items.length === 0){
      listEl.style.display = "none";
      return;
    }
    items.forEach(it => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
      btn.innerHTML =
        `<span>${it.label || ""}</span>` +
        (it.meta ? `<small class="text-muted">${it.meta}</small>` :
         it.tipo ? `<small class="text-muted">${it.tipo}</small>` : "");
      btn.addEventListener("click", () => onPick(it));
      listEl.appendChild(btn);
    });
    listEl.style.display = "block";
  }

  function fillTiposEvento(lista, preselectId = null){
    if (!selTipoEvt) return;
    let html = '<option value="">Selecciona...</option>';
    if (Array.isArray(lista)){
      for (const r of lista){
        const id  = r.id ?? r.id_tipo_evento;
        const nom = r.nombre ?? "";
        const sel = preselectId && String(preselectId) === String(id) ? ' selected' : '';
        html += `<option value="${id}"${sel}>${nom}</option>`;
      }
    }
    selTipoEvt.innerHTML = html;
  }

  function cargarCatalogoTiposEvento(preselectId = null){
    // catálogo MARÍTIMO (id_tipo_operacion=1 en el modelo)
    xhrGet(base_url + "Operaciones_maritimo_ferro_eventos_mar/tipos_evento",
      (rows) => fillTiposEvento(rows, preselectId),
      () => fillTiposEvento([])
    );
  }

  // ----- Autocomplete: Operación (MF=11) -----
  if (inpOpNombre && opSugBox){
    let tmrOp = null, lastTermOp = "";
    inpOpNombre.addEventListener("input", () => {
      const term = inpOpNombre.value.trim();

      // reset selección
      hidOpId.value = "";
      if (opMeta) opMeta.textContent = "";

      // limpiar contenedor dependiente
      if (inpContNom) inpContNom.value = "";
      if (hidContOpId) hidContOpId.value = "";
      if (hidContTipo) hidContTipo.value = "";

      // si vacío, ocultar y salir
      if (term.length === 0){
        opSugBox.style.display = "none";
        return;
      }

      // debounce
      clearTimeout(tmrOp);
      tmrOp = setTimeout(() => {
        if (term === lastTermOp) return;
        lastTermOp = term;

        const url = base_url + "Operaciones_maritimo_ferro_eventos_mar/buscar_operaciones?term=" + encodeURIComponent(term);
        xhrGet(url, (rows) => {
renderSugerencias(opSugBox, rows, (it) => {
  inpOpNombre.value = it.label || "";
  hidOpId.value     = it.id || "";
  opSugBox.style.display = "none";
  if (opMeta) opMeta.textContent = it.meta || "";

  // 👇 AGREGA ESTE BLOQUE: autollenar contenedor
  const urlCont = base_url + "Operaciones_maritimo_ferro_eventos_mar/contenedor_maritimo_de_operacion?operacion_id=" + encodeURIComponent(it.id);
  xhrGet(urlCont, (cont) => {
    // el input debe estar readOnly (no disabled). Si alguien lo dejó disabled, lo habilitamos:
    if (inpContNom) inpContNom.disabled = false;

    if (cont && cont.id) {
      inpContNom.value  = cont.label || "";  // número de contenedor
      hidContOpId.value = cont.id;           // cmo.id
      hidContTipo.value = "MARITIMO";
        // 👇 llena el catálogo de tipos de evento marítimos
  cargarCatalogoTiposEvento();
    } else {
      // sin contenedor: limpiar
      inpContNom.value  = "";
      hidContOpId.value = "";
      hidContTipo.value = "";
    }
  }, () => {
    // error de red/parseo: limpiar
    inpContNom.value  = "";
    hidContOpId.value = "";
    hidContTipo.value = "";
  });
});


        }, () => { opSugBox.style.display = "none"; });
      }, 250);
    });

    // cerrar lista si clic fuera
    document.addEventListener("click", (e) => {
      if (!opSugBox.contains(e.target) && e.target !== inpOpNombre){
        opSugBox.style.display = "none";
      }
    });
  }

  // ----- Autocomplete: Contenedor marítimo (depende de operación) -----
// Solo si NO está en readOnly (en tu flujo actual siempre estará readOnly y no entrará)
if (inpContNom && contSugBox && hidOpId && !inpContNom.readOnly){
    let tmrCont = null, lastTermCont = "";
    inpContNom.addEventListener("input", () => {
      const term = inpContNom.value.trim();
      const opId = parseInt(hidOpId.value || "0", 10);

      // reset selección al tipear
      if (hidContOpId) hidContOpId.value = "";
      if (hidContTipo) hidContTipo.value = "";

      if (!opId){
        // sin operación MF seleccionada, no sugerimos contenedores
        contSugBox.style.display = "none";
        return;
      }

      if (term.length === 0){
        contSugBox.style.display = "none";
        return;
      }

      clearTimeout(tmrCont);
      tmrCont = setTimeout(() => {
        if (term === lastTermCont) return;
        lastTermCont = term;

        const url = base_url + "Operaciones_maritimo_ferro_eventos_mar/buscar_contenedores?operacion_id=" + opId + "&term=" + encodeURIComponent(term);
        xhrGet(url, (rows) => {
          renderSugerencias(contSugBox, rows, (it) => {
            // it: { id: cmo.id, label: numero_contenedor, tipo: 'MARITIMO' }
            inpContNom.value  = it.label || "";
            if (hidContOpId)  hidContOpId.value = it.id || "";
            if (hidContTipo)  hidContTipo.value = it.tipo || "MARITIMO";
            contSugBox.style.display = "none";

            // Cargar catálogo de tipos de evento MARÍTIMOS
            cargarCatalogoTiposEvento();
          });
        }, () => { contSugBox.style.display = "none"; });
      }, 250);
    });

    document.addEventListener("click", (e) => {
      if (!contSugBox.contains(e.target) && e.target !== inpContNom){
        contSugBox.style.display = "none";
      }
    });
  }

  // Si usas Feather icons en el modal
  if (window.feather) feather.replace();

})();
 
