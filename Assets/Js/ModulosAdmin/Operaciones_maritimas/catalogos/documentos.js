// =============== Gestión de Documentos SOLO POR OPERACIÓN ===============
(function(){
  "use strict";
  const root = document.getElementById("documentosRoot");
  if (!root) return;

  const docBaseDocumentos = base_url + "operaciones_maritimas_documentos/";

  // -------- Refs (solo operación) --------
  const opIdInput       = document.getElementById("documentosFiltroOpId");
  const opNombreInput   = document.getElementById("documentosFiltroOpNombre");
  const opSugBox        = document.getElementById("documentosFiltroOpSugerencias");
  const opMeta          = document.getElementById("documentosFiltroOpMeta");

  const tbody           = document.getElementById("tablaDocumentos");
  const listaSubidos    = document.getElementById("listaDocumentos");
  const listaFaltantes  = document.getElementById("listaFaltantesDocumentos");
  const btnNotificar    = document.getElementById("btnNotificarFaltantes");

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

  // -------- Autocomplete de Operación --------
  opNombreInput?.addEventListener("keyup", function(){
    const term = this.value.trim();
    clear(opSugBox);
    if (term === "") { show(opSugBox, false); return; }

    const http = new XMLHttpRequest();
    http.open("GET", docBaseDocumentos + "buscarOperaciones?term=" + encodeURIComponent(term), true);
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
        // intenta mostrar cliente si viene en la respuesta; de lo contrario solo la etiqueta
        btn.textContent = o.cliente ? `${o.label} — ${o.cliente}` : `${o.label}`;
        btn.onclick = ()=> seleccionarOperacion(o.id, o.label, o.cliente);
        opSugBox.appendChild(btn);
      });
      show(opSugBox, true);
    };
  });

  function seleccionarOperacion(id, label, cliente){
    opIdInput.value     = String(id);
    opNombreInput.value = label;
    clear(opSugBox); show(opSugBox, false);

    opMeta.textContent = cliente ? `Operación ${label} — ${cliente}` : `Operación ${label}`;
    listarDocumentos();
    cargarFaltantes(id);
  }

  // Cerrar sugerencias con clic fuera
  document.addEventListener("click", (e)=>{
    if (opSugBox && !opSugBox.contains(e.target) && !opNombreInput.contains(e.target)) {
      show(opSugBox, false);
    }
  });

  // -------- Listado --------
  function listarDocumentos(){
    const opId = (opIdInput.value || "").trim();
    if (!opId){
      tbody.innerHTML = TD_EMPTY(8);
      if (listaSubidos) listaSubidos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
      renderFaltantes([]); // limpia
      if (btnNotificar) btnNotificar.style.display = 'none';
      return;
    }

    const url = docBaseDocumentos + "listar?operacion_id=" + encodeURIComponent(opId);
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
      cargarFaltantes(opId);
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
    http.open("GET", docBaseDocumentos + "faltantes?operacion_id=" + encodeURIComponent(opId), true);
    http.send();
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      let data = [];
      try { data = JSON.parse(this.responseText) || []; } catch {}
      renderFaltantes(data);
    };
  }

  btnNotificar?.addEventListener("click", function(){
    const opId = (opIdInput.value || "").trim();
    if (!opId) { Swal.fire('Aviso','Selecciona una operación','info'); return; }

    Swal.fire({
      title: 'Enviar correo al cliente',
      text: 'Se enviará un correo con la lista de documentos faltantes de la operación.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Enviar',
      cancelButtonText: 'Cancelar'
    }).then((r)=>{
      if (!r.isConfirmed) return;

      const fd = new FormData();
      fd.append('operacion_id', opId);

      const http = new XMLHttpRequest();
      http.open("POST", docBaseDocumentos + "notificarFaltantes", true);
      http.onreadystatechange = function(){
        if (this.readyState !== 4) return;
        let j = {};
        try { j = JSON.parse(this.responseText) || {}; } catch {}
        Swal.fire(j.status === 'success' ? 'Enviado' : 'Aviso', j.msg || '(sin mensaje)', j.status || 'info');
      };
      http.send(fd);
    });
  });

  // Exponer refresco para otras acciones (eliminar, subir)
  window.listarDocumentosDocumentos = listarDocumentos;
  window.documentosRefrescarListado = ()=> listarDocumentos();

  // -------- Ver / Eliminar (global) --------
  window.documentosVerDocumento = function(id, btn){
    const url     = base_url + "operaciones_maritimas_documentos/ver/" + encodeURIComponent(id);
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
      http.open("POST", docBaseDocumentos + "eliminar/" + encodeURIComponent(id), true);
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

  // Si ya hay operación seteada al cargar, listar
  if ((opIdInput.value || "").trim()) listarDocumentos();
})();

// ================== MODAL: Agregar Documento (solo operación) ==================
(function(){
  "use strict";
  const docBaseDocumentos = base_url + "operaciones_maritimas_documentos/";
  const modalEl = document.getElementById("modalAgregarDocumentoDocumentos");
  const form    = document.getElementById("formAgregarDocumentoDocumentos");

  const mdOpId     = document.getElementById("modalDocumentosOpId");
  const mdOpNombre = document.getElementById("modalDocumentosOpNombre");
  const mdOpSug    = document.getElementById("modalDocumentosOpSugerencias");
  const mdOpMeta   = document.getElementById("modalDocumentosOpMeta");
  const selTipo    = document.getElementById("tipo_documentoDocumentos");

  const vwOpId     = document.getElementById("documentosFiltroOpId");
  const vwOpNombre = document.getElementById("documentosFiltroOpNombre");

  const clear = el => { if(el) el.innerHTML=""; };
  const show  = (el, v)=> { if(el) el.style.display = v ? "block" : "none"; };
  const safe  = v => (v==null ? "" : String(v));

  // Al abrir, prellenar operación desde la vista y cargar tipos
  modalEl?.addEventListener("show.bs.modal", ()=>{
    mdOpId.value     = safe(vwOpId?.value);
    mdOpNombre.value = safe(vwOpNombre?.value);
    mdOpMeta.textContent = mdOpNombre.value ? `Operación ${mdOpNombre.value}` : "";

    cargarTipos(); // tipos válidos para operación
  });

  // Autocomplete de operación en modal
  mdOpNombre?.addEventListener("keyup", function(){
    const term = this.value.trim();
    clear(mdOpSug);
    if (term === "") { show(mdOpSug,false); return; }

    const http = new XMLHttpRequest();
    http.open("GET", docBaseDocumentos + "buscarOperaciones?term=" + encodeURIComponent(term), true);
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
  }

  // Cerrar sugerencias con clic fuera
  document.addEventListener("click", (e)=>{
    if (mdOpSug && !mdOpSug.contains(e.target) && !mdOpNombre.contains(e.target)) show(mdOpSug,false);
  });

  // Cargar tipos de documento para operación
  function cargarTipos(){
    if (!selTipo) return;
    selTipo.disabled = true;
    selTipo.innerHTML = '<option value="">Cargando…</option>';

    const http = new XMLHttpRequest();
    // Endpoint sin F/M: debe responder los tipos válidos a nivel operación
    http.open('GET', docBaseDocumentos + 'tipos', true);
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

  // Enviar formulario (solo operacion_id, tipo_documento_id, archivo)
  form?.addEventListener("submit", function(e){
    e.preventDefault();
    if (!mdOpId.value) { Swal.fire('Aviso','Selecciona una operación','info'); return; }
    if (!selTipo?.value) { Swal.fire('Aviso','Selecciona el tipo de documento','info'); return; }

    const fd = new FormData(form);
    const http = new XMLHttpRequest();
    http.open("POST", docBaseDocumentos + "registrar", true);
    http.send(fd);
    http.onreadystatechange = function(){
      if (this.readyState !== 4) return;
      let res = {};
      try { res = JSON.parse(this.responseText) || {}; } catch {}

      Swal.fire(res.status === 'success' ? 'Éxito' : 'Aviso', res.msg || '(sin mensaje)', res.status || 'info');

      if (res.status === 'success') {
        const inst = bootstrap.Modal.getInstance(modalEl);
        inst?.hide();
        form.reset();
        if (window.documentosRefrescarListado) window.documentosRefrescarListado();
        if (window.feather) feather.replace();
      }
    };
  });
})();
