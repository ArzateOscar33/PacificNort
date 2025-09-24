 // == Operaciones Marítimo-Ferroviarias (MF) ==
// Encapsulamos para evitar colisiones con el módulo marítimo
(function(){
  "use strict";

  // ===== Refs de la tabla / filtros =====
  const tablaBody      = document.getElementById("maritimo_ferro_tablaBody");
  const inputBuscar    = document.getElementById("maritimo_ferro_buscarOperacion");
  const selectSubtipo  = document.getElementById("maritimo_ferro_filtroSubtipo");
  const selectPerPage  = document.getElementById("maritimo_ferro_perPage");
  const ulPaginacion   = document.getElementById("maritimo_ferro_paginacion");
  const metaResumen    = document.getElementById("maritimo_ferro_metaResumen");
  const inpFechaIni    = document.getElementById("maritimo_ferro_fechaInicio");
  const inpFechaFin    = document.getElementById("maritimo_ferro_fechaFin");

  let currentPage   = 1;
  let perPage       = parseInt(selectPerPage?.value || "10", 10);
  let currentListXHR = null;
  let debounceId     = null;

  // ===== Refs del modal =====
  const modalEl         = document.getElementById('modalMaritimoFerro');
  const tituloModal     = document.getElementById('tituloModalOperacion_mf');
  const formOp          = document.getElementById('formOperacionMaritimoFerro');
  const btnNuevaOp      = document.getElementById('maritimo_ferro_btnNuevaOperacion');
  const btnGuardarOp    = document.getElementById('btnGuardarOperacion_mf');

  const inpIdOperacion  = document.getElementById('id_operacion_mf');
  const selSubtipo      = document.getElementById('subtipoOperacion_mf');
  const inpNumeroOp     = document.getElementById('numeroOperacion_mf');
  const selEstatus      = document.getElementById('estatusId_mf');
  const inpETD          = document.getElementById('etd_mf');
  const inpETA          = document.getElementById('eta_mf');
  const inpBL           = document.getElementById('numeroBL_mf');

  const selPuerto       = document.getElementById('puertoArribo_mf'); // disabled/readonly
  const selNaviera      = document.getElementById('navieraId_mf');
  const selForwarder    = document.getElementById('forwarderId_mf');
  const selShipper      = document.getElementById('shipperId_mf');

  const inpClienteNom   = document.getElementById('clienteNombre_mf');
  const hidCliente      = document.getElementById('clienteId_mf');
  const boxSugCliente   = document.getElementById('sugerenciasCliente_mf');

  const txtNotas        = document.getElementById('notas_mf');

  // Repeater (contenedores marítimos)
  const repeater        = document.getElementById('contenedoresRepeater_mf');
  const tplContenedor   = document.getElementById('contenedorTemplate_mf');

  // Bootstrap modal
  let modalInstance = null;
  if (modalEl && window.bootstrap) {
    modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  // ===== Helpers UI/DOM =====
  const safe = (v)=> (v===undefined || v===null) ? "" : v;
  const show = (el)=> el?.classList.remove('d-none');
  const hide = (el)=> el?.classList.add('d-none');
  const enable = (el)=> el?.removeAttribute('disabled');
  const disable = (el)=> el?.setAttribute('disabled','disabled');
  const clearSelect = (sel)=> { if (sel) sel.value = ''; };

  function setSelectValue(sel, val){
    if (!sel) return;
    const s = String(val ?? '');
    const has = Array.from(sel.options).some(o => String(o.value) === s);
    if (has) sel.value = s;
  }

  function renderCargando(){
    if (!tablaBody) return;
    tablaBody.innerHTML = `
      <tr>
        <td colspan="11" class="text-center text-muted py-4">Cargando resultados...</td>
      </tr>`;
  }

  function renderTabla(rows){
    if (!tablaBody) return;
    tablaBody.innerHTML = '';
    if (!Array.isArray(rows) || rows.length === 0){
      tablaBody.innerHTML = "<tr><td colspan='11' class='text-center'>No se encontraron resultados</td></tr>";
      return;
    }
    rows.forEach(item=>{
      const tr = document.createElement('tr');
      tr.classList.add('text-center');
      tr.innerHTML = `
        <td>${safe(item.numero_operacion)}</td>
        <td>${safe(item.subtipo || item.subtipo_operacion)}</td>
        <td>${safe(item.eta)}</td>
        <td>${safe(item.contenedores)}</td>
        <td>${safe(item.numero_bl)}</td>
        <td>${safe(item.puerto_arribo)}</td>
        <td>${safe(item.cliente)}</td>
        <td>${safe(item.naviera)}</td>
        <td>${safe(item.forwarder)}</td>
        <td>${safe(item.estatus)}</td>
        <td>
          <button class="btn btn-sm btn-outline-secondary me-1 btn-edit-mf" data-id="${safe(item.id_operacion)}" title="Editar">
            <i data-feather="edit"></i>
          </button>
        </td>
      `;
      tablaBody.appendChild(tr);
    });
    if (window.feather) feather.replace();
  }

  function renderResumen(meta){
    if (!metaResumen) return;
    const { total=0, page=1, per_page=perPage, total_pages=1 } = meta;
    if (total === 0){
      metaResumen.textContent = "Mostrando 0–0 de 0";
      return;
    }
    const start = (page - 1) * per_page + 1;
    const end   = Math.min(total, page * per_page);
    metaResumen.textContent = `Mostrando ${start}–${end} de ${total} | pág ${page} de ${total_pages}`;
  }

  function renderPaginacion(meta){
    if (!ulPaginacion) return;
    const { page=1, total_pages=1 } = meta;
    ulPaginacion.innerHTML = "";

    const liPrev = document.createElement("li");
    liPrev.className = "page-item" + (page <= 1 ? " disabled" : "");
    liPrev.innerHTML = `<a class="page-link" href="#" aria-label="Anterior">&laquo;</a>`;
    liPrev.onclick = (e)=>{ e.preventDefault(); if (page>1){ currentPage = page-1; listar(); } };
    ulPaginacion.appendChild(liPrev);

    const windowSize = 5;
    let start = Math.max(1, page - Math.floor(windowSize/2));
    let end   = Math.min(total_pages, start + windowSize - 1);
    if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

    for (let p = start; p <= end; p++){
      const li = document.createElement("li");
      li.className = "page-item" + (p === page ? " active" : "");
      li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
      li.onclick = (e)=>{ e.preventDefault(); if (p!==page){ currentPage=p; listar(); } };
      ulPaginacion.appendChild(li);
    }

    const liNext = document.createElement("li");
    liNext.className = "page-item" + (page >= total_pages ? " disabled" : "");
    liNext.innerHTML = `<a class="page-link" href="#" aria-label="Siguiente">&raquo;</a>`;
    liNext.onclick = (e)=>{ e.preventDefault(); if (page<total_pages){ currentPage=page+1; listar(); } };
    ulPaginacion.appendChild(liNext);
  }

  // ===== Listar (usa tu endpoint MF) =====
  function listar(){
    const params = new URLSearchParams();
    const subtipo = (selectSubtipo?.value || "").trim();
    const term    = (inputBuscar?.value || "").trim();
    const fi = (inpFechaIni?.value || "").trim();
    const ff = (inpFechaFin?.value || "").trim();

    if (subtipo !== "") params.append("maritimo_ferro_filtroSubtipo", subtipo);
    if (term   !== "")  params.append("maritimo_ferro_buscarOperacion", term);
    if (fi     !== "")  params.append("maritimo_ferro_fechaInicio", fi);
    if (ff     !== "")  params.append("maritimo_ferro_fechaFin", ff);

    params.append("page", String(currentPage));
    params.append("perPage", String(perPage));

    const url = base_url + "Operaciones_maritimo_ferro/listar_operaciones?" + params.toString();

    if (currentListXHR && currentListXHR.readyState !== 4){
      currentListXHR.abort();
    }

    renderCargando();
    const x = new XMLHttpRequest();
    currentListXHR = x;
    x.open("GET", url, true);
    x.send();
    x.onreadystatechange = function(){
      if (x.readyState !== 4) return;

      if (currentListXHR !== x) return; // descartar respuestas viejas

      if (x.status !== 200){
        console.error("listar_operaciones error:", x.responseText);
        renderTabla([]);
        renderPaginacion({ page:1, total_pages:1 });
        renderResumen({ total:0, page:1, per_page:perPage, total_pages:1 });
        return;
      }

      let payload = {};
      try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }

      // Este endpoint NO devuelve {status, meta}. Devuelve:
      // { data, from, to, total, page, per_page, total_pages, pagination_html }
      const rows = payload.data || [];
      renderTabla(rows);

      const meta = {
        total:       Number(payload.total || 0),
        page:        Number(payload.page || 1),
        per_page:    Number(payload.per_page || perPage),
        total_pages: Number(payload.total_pages || 1)
      };
      renderPaginacion(meta);
      renderResumen(meta);
    };
  }

  // ===== Validaciones simples =====
  function validarRangoFechas(){
    const fi = (inpFechaIni?.value || "").trim();
    const ff = (inpFechaFin?.value || "").trim();
    if (fi && ff && fi > ff){
      if (inpFechaIni) inpFechaIni.value = ff;
      if (inpFechaFin) inpFechaFin.value = fi;
    }
  }

  function validarBL(){
    const v = (inpBL?.value || '').trim();
    if (!v) return true;
    return /^[A-Za-z0-9]+$/.test(v);
  }

  // ===== Modal: estado de campos/repeater =====
  function mf_setContenedoresReadonly(isReadonly){
    if (!repeater) return;
    const items = repeater.querySelectorAll('.contenedor-item');
    items.forEach(it => {
      const inp = it.querySelector('.contenedor-input_mf');
      const btnAdd = it.querySelector('.btnContAddOne');
      const btnRem = it.querySelector('.btnContRemoveOne');
      if (inp){
        if (isReadonly){
          inp.setAttribute('readonly', 'readonly');
          inp.classList.add('bg-light');
        } else {
          inp.removeAttribute('readonly');
          inp.classList.remove('bg-light');
        }
      }
      if (btnAdd) btnAdd.disabled = !!isReadonly;
      if (btnRem) btnRem.disabled = !!isReadonly;
    });
  }

  function resetRepeater(){
    if (!repeater) return;
    repeater.innerHTML = '';
    const node = tplContenedor?.content?.cloneNode(true);
    const item = node ? node.querySelector('.contenedor-item') : null;
    if (item) repeater.appendChild(item);
  }

  function resetModal(mode='create'){
    if (tituloModal){
      tituloModal.textContent = (mode==='edit')
        ? 'Editar Operación Marítimo-Ferroviaria'
        : 'Nueva Operación Marítimo-Ferroviaria';
    }
    if (formOp) formOp.dataset.mode = mode;

    if (inpIdOperacion) inpIdOperacion.value = '';
    if (inpNumeroOp)    inpNumeroOp.value = '';
    setSelectValue(selSubtipo, '');
    setSelectValue(selEstatus, '');
    if (inpETD) inpETD.value = '';
    if (inpETA) inpETA.value = '';
    if (inpBL)  inpBL.value  = '';
    if (hidCliente)    hidCliente.value = '';
    if (inpClienteNom) inpClienteNom.value = '';
    if (txtNotas) txtNotas.value = '';
    setSelectValue(selNaviera,   '');
    setSelectValue(selForwarder, '');
    setSelectValue(selShipper,   '');
    setSelectValue(selPuerto,    '');

    resetRepeater();
    if (btnGuardarOp) btnGuardarOp.setAttribute('disabled','disabled');

    if (selPuerto){
      selPuerto.setAttribute('disabled','disabled'); // regla
      selPuerto.classList.add('bg-light');
    }

    if (mode==='edit'){
      if (selSubtipo){ selSubtipo.setAttribute('disabled','disabled'); selSubtipo.classList.add('bg-light'); }
      if (inpNumeroOp){ inpNumeroOp.setAttribute('readonly','readonly'); inpNumeroOp.classList.add('bg-light'); }
      mf_setContenedoresReadonly(true);
    } else {
      if (selSubtipo){ selSubtipo.removeAttribute('disabled'); selSubtipo.classList.remove('bg-light'); }
      if (inpNumeroOp){ inpNumeroOp.setAttribute('readonly','readonly'); inpNumeroOp.classList.remove('bg-light'); }
      if (selNaviera) enable(selNaviera);
      if (selForwarder) enable(selForwarder);
      mf_setContenedoresReadonly(true);
    }
  }

  // ===== Subtipo: pedir info (req naviera/forwarder + puerto default) =====
  let subtipoReq = { requiere_naviera:0, requiere_forwarder:0, puerto_default:null };
  function fetchSubtipoInfo(subtipoId){
    return new Promise((resolve)=>{
      if (!subtipoId){ subtipoReq = { requiere_naviera:0, requiere_forwarder:0, puerto_default:null }; resolve(subtipoReq); return; }
      const x = new XMLHttpRequest();
      x.open('GET', base_url + 'Operaciones_maritimo_ferro/subtipo_info?id=' + encodeURIComponent(subtipoId), true);
      x.send();
      x.onreadystatechange = function(){
        if (x.readyState !== 4) return;
        if (x.status !== 200){ resolve(subtipoReq); return; }
        let payload = {};
        try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }
        if (payload.status === 'success' && payload.data){
          const d = payload.data;
          subtipoReq = {
            requiere_naviera:   Number(d.requiere_naviera || 0),
            requiere_forwarder: Number(d.requiere_forwarder || 0),
            puerto_default:     (d.puerto_arribo_default_id ?? null)
          };
        }
        resolve(subtipoReq);
      };
    });
  }

  function applyPuertoDefault(){
    if (!selPuerto) return;
    const def = Number(subtipoReq.puerto_default || 0);
    if (!def) return;
    const has = Array.from(selPuerto.options).some(o => Number(o.value||0) === def);
    if (has) selPuerto.value = String(def);
  }

  function validarCamposObligatorios(){
    if (!selSubtipo?.value) return false;
    if (Number(subtipoReq.requiere_naviera)   === 1 && !selNaviera?.value)   return false;
    if (Number(subtipoReq.requiere_forwarder) === 1 && !selForwarder?.value) return false;
    return true;
  }

  // ===== Eventos de filtros/listado =====
  selectSubtipo?.addEventListener("change", ()=>{ currentPage=1; listar(); });
  inputBuscar?.addEventListener("keyup", ()=>{
    clearTimeout(debounceId);
    debounceId = setTimeout(()=>{ currentPage=1; listar(); }, 250);
  });
  selectPerPage?.addEventListener("change", ()=>{
    perPage = parseInt(selectPerPage.value, 10) || 10;
    currentPage = 1;
    listar();
  });
  inpFechaIni?.addEventListener("change", ()=>{ validarRangoFechas(); currentPage=1; listar(); });
  inpFechaFin?.addEventListener("change", ()=>{ validarRangoFechas(); currentPage=1; listar(); });

  window.addEventListener("DOMContentLoaded", ()=>{
    perPage = parseInt(selectPerPage?.value || "10", 10);
    listar();
    if (window.feather) feather.replace();
  });

  // ===== Abrir modal: Nueva =====
  btnNuevaOp?.addEventListener('click', ()=>{
    resetModal('create');
  });

  modalEl?.addEventListener('hidden.bs.modal', ()=>{
    resetModal('create');
  });

  // ===== Cambio de Subtipo en modal: trae reglas + puerto default =====
selSubtipo?.addEventListener('change', async ()=>{
  const sid = Number(selSubtipo.value || 0);
  await fetchSubtipoInfo(sid);
  applyPuertoDefault();
 // Si estás en modo crear (no edición), también rellena folio preliminar
 const idEdit = (document.getElementById('id_operacion_mf')?.value || '').trim() !== '';
 if (!idEdit && typeof prefillNumeroPorSubtipoMF === 'function') {
   prefillNumeroPorSubtipoMF();
 }
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

  selNaviera?.addEventListener('change', ()=>{
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
    else btnGuardarOp?.setAttribute('disabled','disabled');
  });
  selForwarder?.addEventListener('change', ()=>{
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
    else btnGuardarOp?.setAttribute('disabled','disabled');
  });

  // ===== Autocomplete de clientes (endpoint MF) =====
  let xhrCliente = null, debounceCliente = null;
  function hideSugCliente(){ if (boxSugCliente){ boxSugCliente.style.display='none'; boxSugCliente.innerHTML=''; } }
  function showSugCliente(){ if (boxSugCliente){ boxSugCliente.style.display='block'; } }
  function setCliente(id, nombre){
    if (hidCliente)     hidCliente.value = String(id||'');
    if (inpClienteNom)  inpClienteNom.value = nombre || '';
    hideSugCliente();
  }
  function renderSugClientes(list){
    boxSugCliente.innerHTML = '';
    if (!Array.isArray(list) || list.length === 0){ hideSugCliente(); return; }
    list.forEach(cli=>{
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action';
      btn.textContent = cli.nombre;
      btn.addEventListener('mousedown', (e)=>{
        e.preventDefault();
        setCliente(cli.id_cliente, cli.nombre);
      });
      boxSugCliente.appendChild(btn);
    });
    showSugCliente();
  }
  function buscarClientesAjax(q){
    if (xhrCliente && xhrCliente.readyState !== 4) xhrCliente.abort();
    xhrCliente = new XMLHttpRequest();
    xhrCliente.open('GET', base_url + 'Operaciones_maritimo_ferro/autocomplete_clientes?q=' + encodeURIComponent(q), true);
    xhrCliente.send();
    xhrCliente.onreadystatechange = function(){
      if (xhrCliente.readyState !== 4) return;
      if (xhrCliente.status !== 200){ hideSugCliente(); return; }
      let payload = {};
      try { payload = JSON.parse(xhrCliente.responseText); } catch(e){ payload={}; }
      const list = (payload.status === 'success' ? (payload.data||[]) : []);
      renderSugClientes(list);
    };
  }
  inpClienteNom?.addEventListener('keyup', (e)=>{
    const q = (inpClienteNom.value || '').trim();
    if (hidCliente && q.length >= 0) hidCliente.value = '';
    if (e.key === 'Escape'){ hideSugCliente(); return; }
    if (q.length < 2){ hideSugCliente(); return; }
    clearTimeout(debounceCliente);
    debounceCliente = setTimeout(()=> buscarClientesAjax(q), 220);
  });
  inpClienteNom?.addEventListener('blur', ()=> setTimeout(hideSugCliente, 150));
  document.addEventListener('click', (ev)=>{
    if (!boxSugCliente) return;
    const inside = boxSugCliente.contains(ev.target) || inpClienteNom === ev.target;
    if (!inside) hideSugCliente();
  });

  // ===== Repeater contenedores (buscar contenedores marítimos)
  const debounceMap = new WeakMap();
  const xhrMap      = new WeakMap();

  function hideBox(box){ if (box){ box.style.display='none'; box.innerHTML=''; } }
  function showBox(box){ if (box){ box.style.display='block'; } }
  function setContenedor($item, id, numero){
    const hid = $item.querySelector('.contenedor-id_mf');
    const inp = $item.querySelector('.contenedor-input_mf');
    const box = $item.querySelector('.sugerencias-contenedor_mf');
    if (hid) hid.value = String(id || '');
    if (inp) inp.value = numero || '';
    hideBox(box);
  }

  function addRow(afterItem=null){
    const node = tplContenedor.content.cloneNode(true);
    const newItem = node.querySelector('.contenedor-item');
    if (afterItem && afterItem.parentNode === repeater){
      afterItem.insertAdjacentElement('afterend', newItem);
    } else {
      repeater.appendChild(newItem);
    }
    if (window.feather) feather.replace();
    return newItem;
  }

  function removeRow(item){
    const items = repeater.querySelectorAll('.contenedor-item');
    if (items.length <= 1){
      const hid = item.querySelector('.contenedor-id_mf');
      const inp = item.querySelector('.contenedor-input_mf');
      if (hid) hid.value = '';
      if (inp) inp.value = '';
      const box = item.querySelector('.sugerencias-contenedor_mf');
      hideBox(box);
      return;
    }
    item.remove();
  }

  function buscarContenedoresAjax(inputEl, q){
    const prev = xhrMap.get(inputEl);
    if (prev && prev.readyState !== 4) prev.abort();

    const x = new XMLHttpRequest();
    xhrMap.set(inputEl, x);
    x.open('GET', base_url + 'Operaciones_maritimo_ferro/buscar_contenedores_mar?q=' + encodeURIComponent(q), true);
    x.send();
    x.onreadystatechange = function(){
      if (x.readyState !== 4) return;
      if (xhrMap.get(inputEl) !== x) return;

      const item = inputEl.closest('.contenedor-item');
      const box  = item?.querySelector('.sugerencias-contenedor_mf');
      if (x.status !== 200){ hideBox(box); return; }

      let payload = {};
      try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }
      const data = (payload.status === 'success' ? (payload.data||[]) : []);

      box.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0){ hideBox(box); return; }

      data.forEach(row=>{
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action';
        btn.textContent = row.numero_contenedor;
        btn.addEventListener('mousedown', (ev)=>{
          ev.preventDefault();
          const contItem = inputEl.closest('.contenedor-item');
          setContenedor(contItem, row.id_contenedor_maritimo, row.numero_contenedor);
        });
        box.appendChild(btn);
      });
      showBox(box);
    };
  }

  repeater?.addEventListener('click', (ev)=>{
    const target = ev.target.closest('button');
    if (!target) return;
    if (target.classList.contains('btnContAddOne')){ addRow(target.closest('.contenedor-item')); return; }
    if (target.classList.contains('btnContRemoveOne')){ removeRow(target.closest('.contenedor-item')); return; }
  });

  repeater?.addEventListener('keyup', (ev)=>{
    const inp = ev.target.closest('.contenedor-input_mf');
    if (!inp) return;
    const item = inp.closest('.contenedor-item');
    const box  = item?.querySelector('.sugerencias-contenedor_mf');

    const hid = item.querySelector('.contenedor-id_mf');
    if (hid) hid.value = '';

    if (ev.key === 'Escape'){ hideBox(box); return; }

    const q = (inp.value || '').trim();
    if (q.length < 3){ hideBox(box); return; }

    const prevTO = debounceMap.get(inp);
    if (prevTO) clearTimeout(prevTO);
    const to = setTimeout(()=> buscarContenedoresAjax(inp, q), 220);
    debounceMap.set(inp, to);
  });

  repeater?.addEventListener('blur', (ev)=>{
    const inp = ev.target.closest('.contenedor-input_mf');
    if (!inp) return;
    const item = inp.closest('.contenedor-item');
    const box  = item?.querySelector('.sugerencias-contenedor_mf');
    setTimeout(()=> hideBox(box), 150);
  }, true);

  document.addEventListener('click', (ev)=>{
    const anyBox = document.querySelectorAll('#contenedoresRepeater_mf .sugerencias-contenedor_mf');
    anyBox.forEach(box=>{
      const input = box.closest('.contenedor-item')?.querySelector('.contenedor-input_mf');
      const inside = box.contains(ev.target) || input === ev.target;
      if (!inside) hideBox(box);
    });
  });

  // Solo números no negativos para bultos
  repeater?.addEventListener('input', (e)=>{
    const el = e.target.closest('.contenedor-bultos_mf');
    if (!el) return;
    const val = el.value.trim();
    if (val !== '' && (!/^\d+$/.test(val) || Number(val) < 0)) el.value = '';
  });

  // ===== Cargar operación para editar =====
  function cargarOperacionParaEditar(id){
    resetModal('edit');
    const x = new XMLHttpRequest();
    x.open('GET', base_url + 'Operaciones_maritimo_ferro/obtener_operacion?id=' + encodeURIComponent(id), true);
    x.send();
    x.onreadystatechange = async function(){
      if (x.readyState !== 4) return;
      if (x.status !== 200){
        console.error('obtener_operacion error:', x.responseText);
        Swal?.fire('Error', 'No se pudo obtener la operación', 'error');
        return;
      }
      let payload = {};
      try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }
      if (payload.status !== 'success' || !payload.data){
        Swal?.fire('Aviso', payload.msg || 'Operación no encontrada', 'warning');
        return;
      }
      const op = payload.data;

      // Cargar reglas de subtipo y puerto default
      await fetchSubtipoInfo(Number(op.subtipo_operacion_id||0));

      if (inpIdOperacion) inpIdOperacion.value = op.id_operacion || '';
      if (inpNumeroOp)    inpNumeroOp.value    = op.numero_operacion || '';
      setSelectValue(selSubtipo,  op.subtipo_operacion_id);
      setSelectValue(selEstatus,  op.estatus_id);
      if (inpETD) inpETD.value = op.etd || '';
      if (inpETA) inpETA.value = op.eta || '';
      if (inpBL)  inpBL.value  = op.numero_bl || '';
      if (hidCliente)    hidCliente.value = op.cliente_id || '';
      if (inpClienteNom) inpClienteNom.value = op.cliente_nombre || '';
      if (txtNotas) txtNotas.value = op.notas || '';
      setSelectValue(selNaviera,   op.naviera_id);
      setSelectValue(selForwarder, op.forwarder_id);
      setSelectValue(selShipper,   op.shipper_id);

      // Puerto: si viene prefill úsalo; si no, aplica default
      if (selPuerto){
        if (op.puerto_arribo_id_prefill) setSelectValue(selPuerto, op.puerto_arribo_id_prefill);
        else applyPuertoDefault();
      }

      // Contenedores (si el backend manda un arreglo)
      if (Array.isArray(op.contenedores) && op.contenedores.length){
        repeater.innerHTML = '';
        op.contenedores.forEach(c=>{
          const row = addRow();
          row.querySelector('.contenedor-id_mf').value    = c.id_contenedor_maritimo || '';
          row.querySelector('.contenedor-input_mf').value = c.numero_contenedor || '';
          const inpBul = row.querySelector('.contenedor-bultos_mf');
          if (inpBul) inpBul.value = (c.bultos ?? '');
        });
      }
      mf_setContenedoresReadonly(true);
      modalInstance?.show();
      const el = document.getElementById('modalMaritimoFerro');
const modal = (el && window.bootstrap) ? bootstrap.Modal.getOrCreateInstance(el) : null;
modal?.show();

if (window.feather) feather.replace();
    };
  }

  // Delegación: click en botón Editar de la tabla
  tablaBody?.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btn-edit-mf');
    if (!btn) return;
    const id = parseInt(btn.getAttribute('data-id') || '0', 10);
    if (!id) return;
    cargarOperacionParaEditar(id);
  });


function guardarEdicionMF(){
  const id = parseInt(inpIdOperacion?.value || '0', 10);
  if (!id){
    Swal?.fire('Error','Falta id de la operación.','error');
    return;
  }

  const fd = new FormData();
  fd.append('id_operacion_mf', String(id));
  fd.append('maritimo_ferro_subtipo',        selSubtipo?.value || '');
  const bl = (inpBL?.value || '').replace(/[^A-Za-z0-9]/g,'').toUpperCase();
  fd.append('maritimo_ferro_numeroBL',       bl);
  fd.append('maritimo_ferro_estatus',        selEstatus?.value || '');
  fd.append('maritimo_ferro_etd',            inpETD?.value || '');
  fd.append('maritimo_ferro_eta',            inpETA?.value || '');
  fd.append('maritimo_ferro_clienteId',      hidCliente?.value || '');
  fd.append('maritimo_ferro_navieraId',      selNaviera?.value || '');
  fd.append('maritimo_ferro_forwarderId',    selForwarder?.value || '');
  fd.append('maritimo_ferro_shipperId',      selShipper?.value || '');
  fd.append('maritimo_ferro_notas',          (txtNotas?.value || '').trim());

  // ===== NUEVO: empujar ids + bultos del repeater =====
  if (repeater){
    const rows = repeater.querySelectorAll('.contenedor-item');
    rows.forEach(row=>{
      const idInp   = row.querySelector('.contenedor-id_mf');
      const bInp    = row.querySelector('.contenedor-bultos_mf');
      const cid     = (idInp?.value || '').trim();
      const bultos  = (bInp?.value || '').trim();
      if (cid){ // solo si existe vínculo
        fd.append('maritimo_ferro_contenedores_ids[]', cid);
        fd.append('maritimo_ferro_contenedores_bultos[]', bultos); // '' => NULL
      }
    });
  }

  const x = new XMLHttpRequest();
  x.open('POST', base_url + 'Operaciones_maritimo_ferro/actualizar', true);
  x.timeout = 20000;
  x.onerror = x.onabort = x.ontimeout = ()=> Swal?.fire('Error de red','No se pudo actualizar la operación.','error');
  x.onreadystatechange = function(){
    if (x.readyState !== 4) return;
    if (x.status !== 200){
      console.error('actualizar error:', x.responseText);
      Swal?.fire('Error','No se pudo actualizar la operación.','error');
      return;
    }
    let res = {};
    try { res = JSON.parse(x.responseText); } catch(e){ res = {}; }
    if (res.status !== 'success'){
      Swal?.fire('Aviso', res.msg || 'No se pudo actualizar','warning');
      return;
    }
    Swal?.fire('Actualizada', res.data?.msg || 'Operación actualizada','success');
    (window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl).hide() : null);
    listar();
  };
  x.send(fd);
}

  // Habilitar/deshabilitar Guardar cuando cambian campos clave
  [selSubtipo, selNaviera, selForwarder].forEach(el=>{
    el?.addEventListener('change', ()=>{
      if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
      else btnGuardarOp?.setAttribute('disabled','disabled');
    });
  });

  // Al mostrar el modal, si hay subtipo seleccionado aplica default de puerto
  modalEl?.addEventListener('shown.bs.modal', ()=>{
    if (!selSubtipo?.value) return;
    applyPuertoDefault();
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  });

btnGuardarOp?.addEventListener('click', (e)=>{
  e.preventDefault();

  // Validaciones básicas que ya tienes
  if (!validarBL()){
    Swal?.fire('BL inválido','El BL solo debe contener letras y números.','warning');
    inpBL?.focus();
    return;
  }
  if (!validarCamposObligatorios()){
    Swal?.fire('Faltan datos','Completa los campos obligatorios.','warning');
    return;
  }

  // ¿Crear o Editar? -> lo define resetModal('create'|'edit')
  const mode = (formOp?.dataset?.mode || 'create');

  if (mode === 'edit'){
    // Usará tu función que POSTea a /actualizar
    guardarEdicionMF();
  } else {
    // Crear: si tu función global de alta está en el otro JS, úsala
    if (typeof window.guardarOperacionMF === 'function'){
      // tu guardarOperacionMF() devuelve una Promise<boolean>
      window.guardarOperacionMF().then(ok=>{
        if (ok){
          // Cierra el modal y refresca la tabla del listado
          (window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl).hide() : null);
          listar();
        }
      });
    } else {
      console.warn('No se encontró window.guardarOperacionMF para crear.');
    }
  }
});

})();
