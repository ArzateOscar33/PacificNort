// =============== Gestión de Documentos POR OPERACIÓN Y CONTENEDOR ===============
(function(){
  "use strict";
  const root = document.getElementById("documentosRoot");
  if (!root) return;

  const docBase = base_url + "Operaciones_maritimas_documentos/";

  // -------- Refs (operación + contenedor) --------
  // Filtro (vista)
  const opIdInput        = document.getElementById("documentosFiltroOpId");
  const opNombreInput    = document.getElementById("documentosFiltroOpNombre");
  const opSugBox         = document.getElementById("documentosFiltroOpSugerencias");
  const opMeta           = document.getElementById("documentosFiltroOpMeta");

  const cmoIdInput       = document.getElementById("documentosFiltroCMOId");
  const cmoNombreInput   = document.getElementById("documentosFiltroCMONombre");
  const cmoSugBox        = document.getElementById("documentosFiltroCMOSugerencias");

  // Tabla + listas
  const tbody            = document.getElementById("tablaDocumentos");
  const listaSubidos     = document.getElementById("listaDocumentos");
  const listaFaltantes   = document.getElementById("listaFaltantesDocumentos");
  const btnNotificar     = document.getElementById("btnNotificarFaltantes");

  // -------- Helpers --------
  const clear = el => { if (el) el.innerHTML = ""; };
  const show  = (el, v)=> { if (el) el.style.display = v ? "block" : "none"; };
  const TD_EMPTY = (cols)=> `<tr><td colspan="${cols}" class="text-center text-muted py-3">Sin resultados</td></tr>`;
  const safe = v => (v==null ? "" : String(v));
  const fmtFecha = val => {
    if (!val) return "";
    const d = new Date(String(val).replace(" ", "T"));
    return isNaN(d) ? safe(val) : d.toLocaleString();
  };
  const escAttr = s => String(s ?? '').replace(/&/g,'&amp;')
                                      .replace(/"/g,'&quot;')
                                      .replace(/</g,'&lt;')
                                      .replace(/>/g,'&gt;')
                                      .replace(/'/g,'&#39;');

  function getJSON(url){
    return new Promise((resolve)=>{
      const x = new XMLHttpRequest();
      x.open("GET", url, true);
      x.onload = ()=> {
        try { resolve(JSON.parse(x.responseText||'[]')); } catch { resolve([]); }
      };
      x.onerror = ()=> resolve([]);
      x.send();
    });
  }

  // -------- Autocomplete de Operación --------
  opNombreInput?.addEventListener("keyup", function(){
    const term = this.value.trim();
    clear(opSugBox);
    if (term === "") { show(opSugBox, false); return; }

    const http = new XMLHttpRequest();
    http.open("GET", docBase + "buscarOperaciones?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("Error buscarOperaciones:", this.status, this.responseText); return; }
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}

      if (!Array.isArray(data) || data.length === 0) { show(opSugBox, false); return; }

      data.slice(0, 10).forEach(o=>{
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = o.cliente ? `${o.label} — ${o.cliente}` : `${o.label}`;
        btn.onclick = ()=> seleccionarOperacion(o.id, o.label, o.cliente);
        opSugBox.appendChild(btn);
      });
      show(opSugBox, true);
    };
  });

  async function seleccionarOperacion(id, label, cliente){
    opIdInput.value     = String(id);
    opNombreInput.value = label;
    clear(opSugBox); show(opSugBox, false);

    // limpiar contenedor y auto-cargar por operación
    await autollenarContenedorPorOperacion(id);

    opMeta.textContent = cliente ? `Operación ${label} — ${cliente}` : `Operación ${label}`;
    listarDocumentos();
    cargarFaltantes(id);
  }

  // Cerrar sugerencias con clic fuera
  document.addEventListener("click", (e)=>{
    if (opSugBox && !opSugBox.contains(e.target) && !opNombreInput.contains(e.target)) {
      show(opSugBox, false);
    }
    if (cmoSugBox && !cmoSugBox.contains(e.target) && !cmoNombreInput.contains(e.target)) {
      show(cmoSugBox, false);
    }
  });

  // -------- Autocomplete Contenedor (limitado por operación seleccionada) --------
  cmoNombreInput?.addEventListener("keyup", async function(){
    const opId = (opIdInput.value||"").trim();
    if (!opId){ clear(cmoSugBox); show(cmoSugBox,false); return; }

    const term = this.value.trim();
    const url  = docBase + "contenedores_por_operacion?operacion_id=" + encodeURIComponent(opId) +
                 (term ? "&term=" + encodeURIComponent(term) : "");
    const data = await getJSON(url);

    clear(cmoSugBox);
    if (!Array.isArray(data) || data.length === 0){ show(cmoSugBox,false); return; }

    data.slice(0, 10).forEach(it=>{
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = it.numero_contenedor || it.label || ("CMO " + it.id);
      btn.onclick = ()=> seleccionarCMO(it.id, it.numero_contenedor || it.label || it.id);
      cmoSugBox.appendChild(btn);
    });
    show(cmoSugBox, true);
    clearCMOView();
  });
  opNombreInput?.addEventListener('input', ()=>{
  const val = (opNombreInput.value||'').trim();
  if (val === ''){
    opIdInput.value = '';
    clearCMOView();              // ← limpia y bloquea contenedor
    tbody.innerHTML = TD_EMPTY(8);
    if (listaSubidos) listaSubidos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
    renderFaltantes([]);
    if (btnNotificar) btnNotificar.style.display = 'none';
  }
});


  function seleccionarCMO(cmoId, cmoNombre){
    cmoIdInput.value     = String(cmoId);            // contenedor_maritimo_id
    cmoNombreInput.value = String(cmoNombre);
    clear(cmoSugBox); show(cmoSugBox, false);
    listarDocumentos(); // refrescar listado acotado al contenedor
  }

  // Cuando cambie manualmente el campo de operación (perdió foco), intentar autollenar CMO
  opNombreInput?.addEventListener('change', ()=>{
    const opId = (opIdInput.value||"").trim();
    if (opId) autollenarContenedorPorOperacion(Number(opId));
  });

async function autollenarContenedorPorOperacion(opId){
  // limpia y bloquea por default
  clearCMOView();

  const data = await getJSON(docBase + "contenedores_por_operacion?operacion_id=" + encodeURIComponent(opId));
  if (Array.isArray(data) && data.length >= 1){
    const it = data[0]; // ← siempre el primero
    cmoIdInput.value     = String(it.id); // contenedor_maritimo_id
    cmoNombreInput.value = it.numero_contenedor || it.label || ("CMO " + it.id);
    cmoNombreInput.setAttribute('readonly','readonly');
    cmoNombreInput.placeholder = '';
  } else {
    // Si por alguna razón no hay, lo dejamos bloqueado y con placeholder
    clearCMOView();
  }
}


  // -------- Listado --------
  function listarDocumentos(){
    const opId  = (opIdInput.value || "").trim();
    const cmoId = (cmoIdInput.value || "").trim();

    if (!opId){
      tbody.innerHTML = TD_EMPTY(8);
      if (listaSubidos) listaSubidos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
      renderFaltantes([]); // limpia
      if (btnNotificar) btnNotificar.style.display = 'none';
      return;
    }

    let url = docBase + "listar?operacion_id=" + encodeURIComponent(opId);
    if (cmoId) url += "&contenedor_maritimo_id=" + encodeURIComponent(cmoId);

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("Error listar:", this.status, this.responseText); return; }
      let rows = [];
      try { rows = JSON.parse(this.responseText) || []; } catch {}

      renderTabla(rows);
      renderSubidos(rows);
      cargarFaltantes(opId); // faltantes siguen calculados por operación
      if (window.feather) feather.replace();
    };
  }

  function renderTabla(rows){
    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = TD_EMPTY(8);
      return;
    }
    const html = rows.map(r=>`
      <tr>
        <td>${safe(r.numero_operacion)}</td>
        <td>${safe(r.cliente)}</td>
        <td class="text-uppercase">${safe(r.tipo_nombre || r.tipo_documento || r.tipo)}</td>
        <td>${safe(r.nombre_archivo || r.nombre_original)}</td>
        <td>${fmtFecha(r.fecha_subida || r.fecha)}</td>
        <td>${safe(r.subido_por)}</td>
        <td>
          ${r.url_archivo
              ? `<a href="${escAttr(r.url_archivo)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                   Ver/Descargar
                 </a>`
              : `<button class="btn btn-sm btn-outline-primary"
                         title="Ver"
                         data-nombre="${escAttr(r.nombre_archivo || r.nombre_original || '')}"
                         data-mime="${escAttr(r.mime_type || '')}"
                         onclick="documentosVerDocumento(${r.id_documento || r.id}, this)">
                   <i data-feather="eye"></i>
                 </button>`}
        </td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-danger"
                  title="Eliminar"
                  onclick="documentosEliminarDocumento(${r.id_documento || r.id})">
            <i data-feather="trash-2"></i>
          </button>
        </td>
      </tr>
    `).join("");
    tbody.innerHTML = html;
  }

  function renderSubidos(rows){
    if (!listaSubidos) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      listaSubidos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
      return;
    }
    listaSubidos.innerHTML = rows.map(r=>{
      const fecha = fmtFecha(r.fecha_subida || r.fecha);
      const tipo  = r.tipo_nombre || r.tipo_documento || r.tipo || '';
      const nombre= r.nombre_archivo || r.nombre_original || '';
      return `<li class="list-group-item">${safe(tipo).toUpperCase()} — ${safe(nombre)} <span class="text-muted">(${fecha})</span></li>`;
    }).join("");
  }

  // -------- Faltantes (por OPERACIÓN) --------
  function renderFaltantes(items){
    if (!listaFaltantes) return;
    if (!Array.isArray(items) || items.length === 0) {
      listaFaltantes.innerHTML = '<li class="list-group-item text-muted">Sin faltantes</li>';
      if (btnNotificar) btnNotificar.style.display = 'none';
      if (window.feather) feather.replace();
      return;
    }
    listaFaltantes.innerHTML = items.map(it=>{
      const nom  = (it.nombre ?? '').toString();
      const clave= (it.clave  ?? '').toString();
      return `<li class="list-group-item"><strong>${nom}</strong> ${clave ? `<span class="text-muted">(${clave})</span>` : ''}</li>`;
    }).join('');
    if (btnNotificar) btnNotificar.style.display = '';
    if (window.feather) feather.replace();
  }

  function cargarFaltantes(opId){
    if (!opId) { renderFaltantes([]); return; }
    const http = new XMLHttpRequest();
    http.open("GET", docBase + "faltantes?operacion_id=" + encodeURIComponent(opId), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}
      renderFaltantes(data);
    };
  }

btnNotificar?.addEventListener("click", function(){
  const opId  = (opIdInput.value || "").trim();
  const cmoId = (cmoIdInput?.value || "").trim(); // ← NUEVO

  if (!opId) { Swal.fire('Aviso','Selecciona una operación','info'); return; }

  Swal.fire({
    title: 'Enviar correo al cliente',
    text: cmoId ? 'Se enviará la lista de faltantes de ESTE CONTENEDOR.' 
                : 'Se enviará la lista de faltantes de la OPERACIÓN.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Cancelar'
  }).then((r)=>{
    if (!r.isConfirmed) return;

    const fd = new FormData();
    fd.append('operacion_id', opId);
    if (cmoId) fd.append('contenedor_maritimo_id', cmoId); // ← para modo por contenedor

    const http = new XMLHttpRequest();
    http.open("POST", docBase + "notificarFaltantes", true);
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      let j = {};
      try { j = JSON.parse(this.responseText) || {}; } catch {}

      // j.status: success | info | warning | error | need_email
      // j.msg:    mensaje legible
      // j.data:   info adicional (scope, operacion, contenedor, count)
      Swal.fire(
        j.status === 'success' ? 'Enviado' : (j.status === 'info' ? 'Aviso' : 'Error'),
        (j.msg || '(sin mensaje)') + (j?.data?.count!=null ? ` (faltantes: ${j.data.count})` : ''),
        j.status === 'success' ? 'success' : (j.status === 'info' ? 'info' : 'error')
      );
    };
    http.send(fd);
  });
});


  // Exponer refresco para otras acciones (eliminar, subir)
  window.listarDocumentosDocumentos = listarDocumentos;
  window.documentosRefrescarListado = ()=> listarDocumentos();

  // -------- Ver / Eliminar (global) --------
  window.documentosVerDocumento = function(id, btn){
    const url     = base_url + "Operaciones_maritimas_documentos/ver/" + encodeURIComponent(id);
    const iframe  = document.getElementById('previewFrameDocumentos');
    const aDown   = document.getElementById('previewDownloadLinkDocumentos');
    const msg     = document.getElementById('previewUnavailableDocumentos');
    const titleEl = document.getElementById('previewTitleDocumentos');

    const nombre  = (btn?.dataset?.nombre || '').trim();
    const mime    = (btn?.dataset?.mime   || '').trim();

    if (titleEl) titleEl.textContent = nombre ? `Vista previa — ${nombre}` : 'Vista previa';

    const esPreview = (m, file) => {
      const ext = (file.split('.').pop() || '').toLowerCase();
      const extsOK  = ['pdf','jpg','jpeg','png','gif','webp','txt','csv'];
      const mimesOK = ['application/pdf','image/jpeg','image/png','image/gif','image/webp','text/plain','text/csv'];
      if (m && mimesOK.includes(m.toLowerCase())) return true;
      return extsOK.includes(ext);
    };
    const previewable = esPreview(mime, nombre);

    if (aDown) aDown.href = url + "?dl=1";

    if (previewable) {
      if (msg)    msg.style.display = 'none';
      if (iframe) { iframe.style.display = 'block'; iframe.src = url; }
    } else {
      if (iframe) { iframe.style.display = 'none'; iframe.src = 'about:blank'; }
      if (msg)    msg.style.display = 'block';
    }

    const modalEl = document.getElementById('modalPreviewDocumentoDocumentos');
    const m = new bootstrap.Modal(modalEl);
    m.show();

    modalEl.addEventListener('hidden.bs.modal', function _cleanup(){
      if (iframe) iframe.src = 'about:blank';
      modalEl.removeEventListener('hidden.bs.modal', _cleanup);
    }, {once:true});
  };

  window.documentosEliminarDocumento = function(id){
    Swal.fire({
      title: '¿Eliminar documento?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (!result.isConfirmed) return;

      const http = new XMLHttpRequest();
      http.open("POST", docBase + "eliminar/" + encodeURIComponent(id), true);
      http.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      http.send();

      http.onreadystatechange = function(){
        if (this.readyState !== 4) return;
        let res = {};
        try { res = JSON.parse(this.responseText) || {}; } catch {}

        Swal.fire(
          res.status === 'success' ? 'Eliminado' : 'Aviso',
          res.msg || '(sin mensaje)',
          res.status || 'info'
        );

        if (res.status === 'success') listarDocumentos();
      };
    });
  };

  // Si ya hay operación seteada al cargar, preparar contenedor y listar
  (async ()=>{
    const opIdBoot = (opIdInput.value || "").trim();
    if (opIdBoot){
      await autollenarContenedorPorOperacion(Number(opIdBoot));
      listarDocumentos();
    }
  })();

function clearCMOView(){
  if (!cmoIdInput || !cmoNombreInput) return;
  cmoIdInput.value = '';
  cmoNombreInput.value = '';
  cmoNombreInput.setAttribute('readonly','readonly');
  cmoNombreInput.placeholder = 'Selecciona una operación primero';
  clear(cmoSugBox); show(cmoSugBox,false);
}

function unlockCMOView(){
  if (!cmoNombreInput) return;
  cmoNombreInput.removeAttribute('readonly');
  cmoNombreInput.placeholder = 'Escribe para buscar el contenedor';
}


})();

// ================== MODAL: Agregar Documento (operación + contenedor) ==================
(function(){
  "use strict";
  const docBase = base_url + "Operaciones_maritimas_documentos/";
  const modalEl = document.getElementById("modalAgregarDocumentoDocumentos");
  const form    = document.getElementById("formAgregarDocumentoDocumentos");

  // Operación (modal)
  const mdOpId     = document.getElementById("modalDocumentosOpId");
  const mdOpNombre = document.getElementById("modalDocumentosOpNombre");
  const mdOpSug    = document.getElementById("modalDocumentosOpSugerencias");
  const mdOpMeta   = document.getElementById("modalDocumentosOpMeta");

  // Contenedor (modal)
  const mdCmoId     = document.getElementById("modalDocumentosCMOId");
  const mdCmoNombre = document.getElementById("modalDocumentosCMONombre");
  const mdCmoSug    = document.getElementById("modalDocumentosCMOSugerencias");
  const mdCmoMeta   = document.getElementById("modalDocumentosCMOMeta");

  // Tipos
  const selTipo    = document.getElementById("tipo_documentoDocumentos");

  // Operación desde vista (para prefill)
  const vwOpId     = document.getElementById("documentosFiltroOpId");
  const vwOpNombre = document.getElementById("documentosFiltroOpNombre");

  // Contenedor desde vista (para prefill)
  const vwCmoId     = document.getElementById("documentosFiltroCMOId");
  const vwCmoNombre = document.getElementById("documentosFiltroCMONombre");

  const clear = el => { if(el) el.innerHTML=""; };
  const show  = (el, v)=> { if(el) el.style.display = v ? "block" : "none"; };
  const safe  = v => (v==null ? "" : String(v));

  function getJSON(url){
    return new Promise((resolve)=>{
      const x = new XMLHttpRequest();
      x.open("GET", url, true);
      x.onload = ()=> { try { resolve(JSON.parse(x.responseText||'[]')); } catch { resolve([]); } };
      x.onerror = ()=> resolve([]);
      x.send();
    });
  }

  // Al abrir, prellenar operación/CMO desde la vista y cargar tipos
modalEl?.addEventListener("show.bs.modal", async ()=>{
  // Prefill Operación desde la vista
  mdOpId.value     = safe(vwOpId?.value);
  mdOpNombre.value = safe(vwOpNombre?.value);
  mdOpMeta.textContent = mdOpNombre.value ? `Operación ${mdOpNombre.value}` : "";

  // Requisito: solo elegir operación; el contenedor se autollenará y quedará readonly
  if (mdOpId.value){
    await autoFillCMOForModal(mdOpId.value); // ← AQUÍ
  } else {
    // Si no hay operación aún, mostrar instrucción
    mdCmoId.value = '';
    mdCmoNombre.value = '';
    mdCmoNombre.setAttribute('readonly','readonly');
    if (mdCmoMeta) mdCmoMeta.textContent = 'Selecciona primero la operación.';
  }

  cargarTipos();
});
mdOpNombre?.addEventListener('input', ()=>{
  const val = (mdOpNombre.value||'').trim();
  if (val === ''){
    mdOpId.value = '';
    mdCmoId.value = '';
    mdCmoNombre.value = '';
    mdCmoNombre.setAttribute('readonly','readonly');
    if (mdCmoMeta) mdCmoMeta.textContent = 'Selecciona primero la operación.';
    clear(mdCmoSug); show(mdCmoSug,false);
  }
});
modalEl?.addEventListener('hidden.bs.modal', ()=>{
  // Limpieza integral del modal
  form?.reset();

  // Limpiar operación/CMO y estados
  mdOpId.value = '';
  mdOpNombre.value = '';
  mdCmoId.value = '';
  mdCmoNombre.value = '';
  mdCmoNombre.removeAttribute('readonly');
  if (mdCmoMeta) mdCmoMeta.textContent = 'Escribe para buscar el contenedor (después de seleccionar operación).';

  clear(mdOpSug);  show(mdOpSug,false);
  clear(mdCmoSug); show(mdCmoSug,false);
});


  // Autocomplete de operación en modal
  mdOpNombre?.addEventListener("keyup", function(){
    const term = this.value.trim();
    clear(mdOpSug);
    if (term === "") { show(mdOpSug,false); return; }

    const http = new XMLHttpRequest();
    http.open("GET", docBase + "buscarOperaciones?term=" + encodeURIComponent(term), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) { console.warn("buscarOperaciones (modal):", this.status, this.responseText); return; }
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}

      if (!Array.isArray(data) || data.length === 0) { show(mdOpSug,false); return; }

      data.slice(0,10).forEach(o=>{
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = o.cliente ? `${o.label} — ${o.cliente}` : `${o.label}`;
        btn.onclick = ()=> seleccionarOperacionModal(o.id, o.label, o.cliente);
        mdOpSug.appendChild(btn);
      });
      show(mdOpSug,true);
    };
  });

function seleccionarOperacionModal(id, label, cliente){
  mdOpId.value     = String(id);
  mdOpNombre.value = label;
  mdOpMeta.textContent = cliente ? `Operación ${label} — ${cliente}` : `Operación ${label}`;
  clear(mdOpSug); show(mdOpSug,false);

  // Al elegir operación en modal, autollenar contenedor y dejar readonly
  autoFillCMOForModal(id); // ← AQUÍ
}


  // Autocomplete de CMO en modal (limitado por operación elegida)
  mdCmoNombre?.addEventListener("keyup", async function(){
    const opId = (mdOpId.value||"").trim();
    if (!opId){ clear(mdCmoSug); show(mdCmoSug,false); return; }
    const term = this.value.trim();
    const url  = docBase + "contenedores_por_operacion?operacion_id=" + encodeURIComponent(opId) +
                 (term ? "&term=" + encodeURIComponent(term) : "");
    const data = await getJSON(url);

    clear(mdCmoSug);
    if (!Array.isArray(data) || data.length === 0){ show(mdCmoSug,false); return; }

    data.slice(0,10).forEach(it=>{
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = it.numero_contenedor || it.label || ("CMO " + it.id);
      btn.onclick = ()=> seleccionarCMOModal(it.id, it.numero_contenedor || it.label || it.id);
      mdCmoSug.appendChild(btn);
    });
    show(mdCmoSug,true);
  });

  function seleccionarCMOModal(id, nombre){
    mdCmoId.value     = String(id);
    mdCmoNombre.value = String(nombre);
    clear(mdCmoSug); show(mdCmoSug,false);
  }

  // Cerrar sugerencias con clic fuera
  document.addEventListener("click", (e)=>{
    if (mdOpSug && !mdOpSug.contains(e.target) && !mdOpNombre.contains(e.target)) show(mdOpSug,false);
    if (mdCmoSug && !mdCmoSug.contains(e.target) && !mdCmoNombre.contains(e.target)) show(mdCmoSug,false);
  });

  // Cargar tipos de documento (operación/contenedor) 
  function cargarTipos(){
    if (!selTipo) return;
    selTipo.disabled = true;
    selTipo.innerHTML = '<option value="">Cargando…</option>';

    const http = new XMLHttpRequest();
    http.open('GET', docBase + 'tipos', true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      if (this.status !== 200) {
        selTipo.innerHTML = '<option value="">(Error al cargar tipos)</option>';
        selTipo.disabled = false;
        return;
      }
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}

      if (!Array.isArray(data) || data.length === 0) {
        selTipo.innerHTML = '<option value="">(Sin tipos disponibles)</option>';
        selTipo.disabled = false;
        return;
      }
      selTipo.innerHTML = '<option value="">-- Selecciona tipo --</option>' +
        data.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
      selTipo.disabled = false;
    };
  }

  // Enviar formulario (requiere operacion_id, contenedor_maritimo_id, tipo_documento_id, archivo)
  form?.addEventListener("submit", function(e){
    e.preventDefault();
    if (!mdOpId.value)      { Swal.fire('Aviso','Selecciona una operación','info'); return; }
    if (!mdCmoId.value)     { Swal.fire('Aviso','Selecciona el contenedor marítimo','info'); return; }
    if (!selTipo?.value)    { Swal.fire('Aviso','Selecciona el tipo de documento','info'); return; }

    const fd = new FormData(form);
    // Asegurar que mandamos los campos claves (por si el name no existiera en HTML)
    fd.set('operacion_id', mdOpId.value);
    fd.set('contenedor_maritimo_id', mdCmoId.value);

    const http = new XMLHttpRequest();
    http.open("POST", docBase + "registrar", true);
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      let res = {};
      try { res = JSON.parse(this.responseText) || {}; } catch {}

      Swal.fire(res.status === 'success' ? 'Éxito' : 'Aviso', res.msg || '(sin mensaje)', res.status || 'info');

      if (res.status === 'success') {
        const inst = bootstrap.Modal.getInstance(modalEl);
        inst?.hide();
        form.reset();

        // Si en la vista hay una operación/CMO seleccionados, mantenerlos
        if (vwOpId?.value) mdOpId.value = vwOpId.value;
        if (vwOpNombre?.value) mdOpNombre.value = vwOpNombre.value;
        if (vwCmoId?.value) { mdCmoId.value = vwCmoId.value; mdCmoNombre.value = vwCmoNombre?.value || ''; }

        if (window.documentosRefrescarListado) window.documentosRefrescarListado();
        if (window.feather) feather.replace();
      }
    };
    http.send(fd);
  });

  async function autoFillCMOForModal(opId){
  // limpia
  mdCmoId.value = '';
  mdCmoNombre.value = '';
  mdCmoNombre.setAttribute('readonly','readonly'); // bloqueo adelantado
  if (mdCmoMeta) mdCmoMeta.textContent = 'Detectando contenedor…';

  const data = await getJSON(docBase + "contenedores_por_operacion?operacion_id=" + encodeURIComponent(opId));
  if (Array.isArray(data) && data.length >= 1){
    const it = data[0]; // ← primero
    mdCmoId.value     = String(it.id);
    mdCmoNombre.value = it.numero_contenedor || it.label || ("CMO " + it.id);
    mdCmoNombre.setAttribute('readonly','readonly');
    if (mdCmoMeta) mdCmoMeta.textContent = 'Contenedor detectado y bloqueado.';
  } else {
    // No se encontró contenedor: dejar limpio y readonly
    mdCmoId.value = '';
    mdCmoNombre.value = '';
    mdCmoNombre.setAttribute('readonly','readonly');
    if (mdCmoMeta) mdCmoMeta.textContent = 'No se encontró contenedor para la operación.';
  }
}

})();
