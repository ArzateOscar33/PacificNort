(function(){
  "use strict";
  if (!document.getElementById("documentosRoot")) return; // ← no ejecutes nada fuera de la vista
  // … el resto de tu JS …
})();
// =============== Gestión de Documentos (LISTAR) ===============
(function(){
  "use strict";
  if (!document.getElementById("documentosRoot")) return;

  const docBaseDocumentos = base_url + "operaciones_maritimas_documentos/";

  // Refs vista (con sufijo)
  const opIdInputDocumentos       = document.getElementById("documentosFiltroOpId");
  const opNombreInputDocumentos   = document.getElementById("documentosFiltroOpNombre");
  const opSugBoxDocumentos        = document.getElementById("documentosFiltroOpSugerencias");
  const opMetaDocumentos          = document.getElementById("documentosFiltroOpMeta");

  const contIdInputDocumentos     = document.getElementById("documentosFiltroContendorId");
  const contNombreInputDocumentos = document.getElementById("documentosFiltroContendorNombre");
  const contSugBoxDocumentos      = document.getElementById("documentosFiltroContenedorSugerencias");

  const tbodyDocumentos           = document.getElementById("tablaDocumentos");
  const listaSubidosDocumentos    = document.getElementById("listaDocumentos");

  // Helpers con sufijo
  const clearDocumentos = el => { if (el) el.innerHTML = ""; };
  const showDocumentos  = (el, v)=> { if (el) el.style.display = v ? "block" : "none"; };
  const liEmptyDocumentos = cols => `<tr><td colspan="${cols}" class="text-center text-muted py-3">Sin resultados</td></tr>`;
  const safeDocumentos  = v => (v==null ? "" : String(v));
  const fmtFechaDocumentos = val => {
    if (!val) return "";
    const d = new Date(String(val).replace(" ", "T"));
    return isNaN(d) ? safeDocumentos(val) : d.toLocaleString();
  };

  // Autocomplete operaciones (usa rutas del controlador correcto)
  opNombreInputDocumentos?.addEventListener("keyup", function(){
    const term = this.value.trim();
    contIdInputDocumentos.value = "";
    contNombreInputDocumentos.value = "";
    clearDocumentos(contSugBoxDocumentos); showDocumentos(contSugBoxDocumentos, false);

    clearDocumentos(opSugBoxDocumentos);
    if (term === "") { showDocumentos(opSugBoxDocumentos, false); return; }

    const http = new XMLHttpRequest();
    http.open("GET", docBaseDocumentos + "buscarOperaciones?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("Error buscarOperaciones:", this.status, this.responseText); return; }
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}
      if (data.length === 0) { showDocumentos(opSugBoxDocumentos, false); return; }

      data.slice(0, 10).forEach(o=>{
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${o.label} — ${o.contenedores} contenedores`;
        btn.onclick = ()=> seleccionarOperacionDocumentos(o.id, o.label);
        opSugBoxDocumentos.appendChild(btn);
      });
      showDocumentos(opSugBoxDocumentos, true);
    };
  });

  function seleccionarOperacionDocumentos(id, label){
    opIdInputDocumentos.value     = String(id);
    opNombreInputDocumentos.value = label;
    clearDocumentos(opSugBoxDocumentos); showDocumentos(opSugBoxDocumentos, false);

    // cargar contenedores mixto
    const http = new XMLHttpRequest();
    http.open("GET", docBaseDocumentos + "contenedoresPorOperacion/" + encodeURIComponent(id), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("Error contenedoresPorOperacion:", this.status, this.responseText); return; }

      let conts = [];
      try { conts = JSON.parse(this.responseText) || []; } catch {}
      opMetaDocumentos.textContent = `Operación ${label} — ${conts.length} contenedores (Físicos y Marítimos)`;
      renderSugerenciasContenedorDocumentos(conts);
      listarDocumentos(); // sigue abajo
    };
  }

  function renderSugerenciasContenedorDocumentos(arr){
    clearDocumentos(contSugBoxDocumentos);
    if (!arr || arr.length === 0) { showDocumentos(contSugBoxDocumentos, false); return; }
    arr.forEach(c=>{
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      const badge = c.tipo === "M" ? "Marítimo" : "Físico";
      btn.textContent = `[${badge}] ${c.label} — ${c.cliente || "Sin cliente"}`;
      btn.onclick = ()=>{
        contIdInputDocumentos.value         = String(c.id);
        contIdInputDocumentos.dataset.tipo  = c.tipo;
        contNombreInputDocumentos.value     = c.label;
        showDocumentos(contSugBoxDocumentos, false);
        listarDocumentos();
      };
      contSugBoxDocumentos.appendChild(btn);
    });
    showDocumentos(contSugBoxDocumentos, true);
  }

  contNombreInputDocumentos?.addEventListener("input", function(){
    const term = this.value.trim().toLowerCase();
    const items = Array.from(contSugBoxDocumentos?.children || []);
    let visible = 0;
    items.forEach(li=>{
      const ok = li.textContent.toLowerCase().includes(term);
      li.style.display = ok ? "" : "none";
      if (ok) visible++;
    });
    showDocumentos(contSugBoxDocumentos, visible > 0);
  });

  document.addEventListener("click", function(e){
    if (opSugBoxDocumentos   && !opSugBoxDocumentos.contains(e.target)   && !opNombreInputDocumentos.contains(e.target))   showDocumentos(opSugBoxDocumentos, false);
    if (contSugBoxDocumentos && !contSugBoxDocumentos.contains(e.target) && !contNombreInputDocumentos.contains(e.target)) showDocumentos(contSugBoxDocumentos, false);
  });

  // LISTAR
  function listarDocumentos(){
    const opId  = opIdInputDocumentos.value.trim();
    const contId= contIdInputDocumentos.value.trim();
    const tipo  = contIdInputDocumentos.dataset.tipo || "";

    if (!opId) {
      tbodyDocumentos.innerHTML = liEmptyDocumentos(9);
      listaSubidosDocumentos.innerHTML = "";
      return;
    }

    let url = docBaseDocumentos + "listar?operacion_id=" + encodeURIComponent(opId);
    if (contId) {
      url += "&contenedor_id=" + encodeURIComponent(contId);
      if (tipo) url += "&tipo=" + encodeURIComponent(tipo);
    }

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("Error listar:", this.status, this.responseText); return; }
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}

      renderTablaDocumentos(data);
      renderSubidosDocumentos(data);
      feather.replace();
    };
  }

  function renderTablaDocumentos(rows){
    if (!Array.isArray(rows) || rows.length === 0) {
      tbodyDocumentos.innerHTML = liEmptyDocumentos(9);
      return;
    }
    const html = rows.map(r=>`
      <tr>
        <td>${safeDocumentos(r.numero_operacion)}</td>
        <td>${safeDocumentos(r.contenedor)}</td>
        <td>${safeDocumentos(r.cliente)}</td>
        <td class="text-uppercase">${safeDocumentos(r.tipo)}</td>
        <td>${safeDocumentos(r.nombre_archivo)}</td>
        <td>${fmtFechaDocumentos(r.fecha_subida)}</td>
        <td>${safeDocumentos(r.subido_por)}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" title="Ver" onclick="documentosVerDocumento(${r.id_documento})">
            <i data-feather="eye"></i>
          </button>
        </td>
        <td>
          <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="documentosEliminarDocumento(${r.id_documento})">
            <i data-feather="trash-2"></i>
          </button>
        </td>
      </tr>
    `).join("");
    tbodyDocumentos.innerHTML = html;
  }

  function renderSubidosDocumentos(rows){
    if (!listaSubidosDocumentos) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      listaSubidosDocumentos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
      return;
    }
    listaSubidosDocumentos.innerHTML = rows.map(r=>{
      const fecha = fmtFechaDocumentos(r.fecha_subida);
      return `<li class="list-group-item">${safeDocumentos(r.tipo).toUpperCase()} — ${safeDocumentos(r.nombre_archivo)} <span class="text-muted">(${fecha})</span></li>`;
    }).join("");
  }

  // Evita colisión con otros módulos:
  window.documentosVerDocumento = function(id){
    Swal.fire("Por implementar", "Ver documento ID " + id, "info");
  };
  window.documentosEliminarDocumento = function(id){
    Swal.fire("Por implementar", "Eliminar documento ID " + id, "warning");
  };

})();
;
// ================== MODAL: Agregar Documento (Documentos) ==================
(function(){
  "use strict";
  const docBaseDocumentos = base_url + "operaciones_maritimas_documentos/";

  // Refs del modal
  const modalElDocumentos      = document.getElementById("modalAgregarDocumentoDocumentos");
  const formDocumentos         = document.getElementById("formAgregarDocumentoDocumentos");

  const mdOpIdDocumentos       = document.getElementById("modalDocumentosOpId");
  const mdOpNombreDocumentos   = document.getElementById("modalDocumentosOpNombre");
  const mdOpSugDocumentos      = document.getElementById("modalDocumentosOpSugerencias");
  const mdOpMetaDocumentos     = document.getElementById("modalDocumentosOpMeta");

  const mdContIdDocumentos     = document.getElementById("modalDocumentosContId");
  const mdContTipoDocumentos   = document.getElementById("modalDocumentosContTipo"); // 'F' o 'M'
  const mdContNombreDocumentos = document.getElementById("modalDocumentosContNombre");
  const mdContSugDocumentos    = document.getElementById("modalDocumentosContSugerencias");

  // Refs de los filtros de la vista (para prellenar)
  const vwOpIdDocumentos       = document.getElementById("documentosFiltroOpId");
  const vwOpNombreDocumentos   = document.getElementById("documentosFiltroOpNombre");
  const vwContIdDocumentos     = document.getElementById("documentosFiltroContendorId");
  const vwContNombreDocumentos = document.getElementById("documentosFiltroContendorNombre");

  // Helpers
  function clearDocumentos(el){ if(el) el.innerHTML=""; }
  function showDocumentos(el, show){ if(el) el.style.display = show ? "block" : "none"; }
  function safeDocumentos(v){ return (v==null) ? "" : String(v); }

  // Al abrir modal: prefill desde filtros de la vista
  modalElDocumentos?.addEventListener("show.bs.modal", ()=>{
    mdOpIdDocumentos.value       = safeDocumentos(vwOpIdDocumentos?.value);
    mdOpNombreDocumentos.value   = safeDocumentos(vwOpNombreDocumentos?.value);

    mdContIdDocumentos.value     = safeDocumentos(vwContIdDocumentos?.value);
    mdContNombreDocumentos.value = safeDocumentos(vwContNombreDocumentos?.value);

    const tipoVista = vwContIdDocumentos?.dataset?.tipo || "";
    mdContTipoDocumentos.value = tipoVista;

    if (mdOpIdDocumentos.value) {
      cargarContenedoresModalDocumentos(mdOpIdDocumentos.value, mdOpNombreDocumentos.value);
    } else {
      clearDocumentos(mdOpSugDocumentos);  showDocumentos(mdOpSugDocumentos,false);
      clearDocumentos(mdContSugDocumentos);showDocumentos(mdContSugDocumentos,false);
      mdOpMetaDocumentos.textContent = "";
    }
  });

  // Autocomplete Operación
  mdOpNombreDocumentos?.addEventListener("keyup", function(){
    const term = this.value.trim();

    // Reset contenedor al cambiar operación
    mdContIdDocumentos.value = "";
    mdContNombreDocumentos.value = "";
    mdContTipoDocumentos.value = "";
    clearDocumentos(mdContSugDocumentos); showDocumentos(mdContSugDocumentos,false);

    clearDocumentos(mdOpSugDocumentos);
    if (term === "") { showDocumentos(mdOpSugDocumentos,false); return; }

    const http = new XMLHttpRequest();
    http.open("GET", docBaseDocumentos + "buscarOperaciones?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("buscarOperaciones (modal):", this.status, this.responseText); return; }
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}

      if (data.length === 0) { showDocumentos(mdOpSugDocumentos,false); return; }

      data.slice(0,10).forEach(o=>{
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = `${o.label} — ${o.contenedores} contenedores`;
        btn.onclick = ()=> seleccionarOperacionModalDocumentos(o.id, o.label);
        mdOpSugDocumentos.appendChild(btn);
      });
      showDocumentos(mdOpSugDocumentos,true);
    };
  });

  function seleccionarOperacionModalDocumentos(id, label){
    mdOpIdDocumentos.value     = String(id);
    mdOpNombreDocumentos.value = label;
    clearDocumentos(mdOpSugDocumentos); showDocumentos(mdOpSugDocumentos,false);

    mdContIdDocumentos.value = "";
    mdContNombreDocumentos.value = "";
    mdContTipoDocumentos.value = "";
    clearDocumentos(mdContSugDocumentos); showDocumentos(mdContSugDocumentos,false);

    cargarContenedoresModalDocumentos(id, label);
  }

  function cargarContenedoresModalDocumentos(opId, label){
    const http = new XMLHttpRequest();
    http.open("GET", docBaseDocumentos + "contenedoresPorOperacion/" + encodeURIComponent(opId), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("contenedoresPorOperacion (modal):", this.status, this.responseText); return; }
      let conts = [];
      try { conts = JSON.parse(this.responseText) || []; } catch {}
      mdOpMetaDocumentos.textContent = `Operación ${label} — ${conts.length} contenedores (Físicos y Marítimos)`;
      renderContenedoresModalDocumentos(conts);
    };
  }

  // Autocomplete Contenedor
  function renderContenedoresModalDocumentos(arr){
    clearDocumentos(mdContSugDocumentos);
    if (!arr || arr.length === 0) { showDocumentos(mdContSugDocumentos,false); return; }
    arr.forEach(c=>{
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      const badge = c.tipo === "M" ? "Marítimo" : "Físico";
      btn.textContent = `[${badge}] ${c.label} — ${c.cliente || "Sin cliente"}`;
      btn.onclick = ()=>{
        mdContIdDocumentos.value     = String(c.id);
        mdContTipoDocumentos.value   = c.tipo; // 'F' o 'M'
        mdContNombreDocumentos.value = c.label;
        showDocumentos(mdContSugDocumentos,false);
      };
      mdContSugDocumentos.appendChild(btn);
    });
    showDocumentos(mdContSugDocumentos,true);
  }

  // Filtro en vivo de contenedores del modal
  mdContNombreDocumentos?.addEventListener("input", function(){
    const term = this.value.trim().toLowerCase();
    const items = Array.from(mdContSugDocumentos?.children || []);
    let visible = 0;
    items.forEach(li=>{
      const ok = li.textContent.toLowerCase().includes(term);
      li.style.display = ok ? "" : "none";
      if (ok) visible++;
    });
    showDocumentos(mdContSugDocumentos, visible>0);
  });

  // Cerrar sugerencias con click fuera
  document.addEventListener("click", function(e){
    if (mdOpSugDocumentos   && !mdOpSugDocumentos.contains(e.target)   && !mdOpNombreDocumentos.contains(e.target))   showDocumentos(mdOpSugDocumentos,false);
    if (mdContSugDocumentos && !mdContSugDocumentos.contains(e.target) && !mdContNombreDocumentos.contains(e.target)) showDocumentos(mdContSugDocumentos,false);
  });

  // Validación mínima al enviar
  formDocumentos?.addEventListener("submit", function(ev){
    const opOk   = mdOpIdDocumentos.value.trim() !== "";
    const contOk = mdContIdDocumentos.value.trim() !== "" && mdContTipoDocumentos.value.trim() !== "";
    if (!opOk || !contOk) {
      ev.preventDefault();
      Swal.fire("Faltan datos", "Selecciona operación y contenedor (F o M).", "warning");
      return false;
    }
  });

})();

