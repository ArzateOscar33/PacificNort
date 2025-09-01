  
const tabla         = document.getElementById("tablaOperacionesMaritimas");
const inputBuscar   = document.getElementById("buscarOperacion");
const selectSubtipo = document.getElementById("filtroSubtipo");
const selectPerPage = document.getElementById("perPage");
const ulPaginacion  = document.getElementById("paginacion");
const metaResumen   = document.getElementById("metaResumen");

 
let currentPage = 1;
let perPage     = parseInt(selectPerPage?.value || "10", 10);
let currentListXHR = null;   // para abortar solicitudes previas
let debounceId     = null;



// ====== Refs del modal (ya tienes varias) ======
const modalEl         = document.getElementById('modalOperacionMaritima');
const tituloModal     = document.getElementById('tituloModalOperacion');
const inpIdOperacion  = document.getElementById('id_operacion');
const selSubtipoEd    = document.getElementById('subtipoOperacion');
const inpNumeroOp     = document.getElementById('numeroOperacion');
const selEstatus      = document.getElementById('estatusId');
const inpETD          = document.getElementById('etd');
const inpETA          = document.getElementById('eta');
const inpBL           = document.getElementById('numeroBL');
const selPuerto       = document.getElementById('puertoArribo');     // readonly/disabled
const selNavieraEd    = document.getElementById('navieraId');
const selForwarderEd  = document.getElementById('forwarderId');
const inpClienteNom   = document.getElementById('clienteNombre');
const hidCliente      = document.getElementById('clienteId');
const selShipper      = document.getElementById('shipperId');

// Bootstrap modal instance (si usas Bootstrap 5)
let modalInstance = null;
if (modalEl && window.bootstrap) {
  modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
}

// Set select by value (si existe opción)
function setSelectValue(sel, val){
  if (!sel) return;
  const s = String(val ?? '');
  const has = Array.from(sel.options).some(o => String(o.value) === s);
  if (has) sel.value = s;
}

// Limpia modal a estado base (1 fila en repeater, etc.)
/*
function resetModalEdicion(){
  if (tituloModal) tituloModal.textContent = 'Editar Operación Marítima';
  if (inpIdOperacion) inpIdOperacion.value = '';
  if (inpNumeroOp)    inpNumeroOp.value = '';
  setSelectValue(selSubtipoEd, '');
  setSelectValue(selEstatus,   '');
  if (inpETD) inpETD.value = '';
  if (inpETA) inpETA.value = '';
  if (inpBL)  inpBL.value  = '';
  if (hidCliente)    hidCliente.value = '';
  if (inpClienteNom) inpClienteNom.value = '';
  setSelectValue(selNavieraEd,   '');
  setSelectValue(selForwarderEd, '');
  setSelectValue(selShipper,     '');
  // Puerto: solo mostrar (disabled/readonly)
  setSelectValue(selPuerto, '');
  // Repeater: deja 1 fila vacía
  if (repeater) {
    repeater.innerHTML = '';
    const first = addRow(); // usa tu helper existente
    // limpia valores
    first.querySelector('.contenedor-id').value = '';
    first.querySelector('.contenedor-input').value = '';
  }
}*/

// ========== 1) Reset duro del modal a modo "crear" ==========
function resetModalOperacion(mode = 'create'){
  // Título
  if (tituloModal) {
    tituloModal.textContent = (mode === 'edit')
      ? 'Editar Operación Marítima'
      : 'Nueva Operación Marítima';
  }

  // Modo en el form (opcional pero útil)
  if (formOp) formOp.dataset.mode = mode;

  // Limpiar IDs y campos base
  if (inpIdOperacion) inpIdOperacion.value = '';
  if (inpNumeroOp)    inpNumeroOp.value = '';
  setSelectValue(selSubtipoEd,   '');
  setSelectValue(selEstatus,     '');
  if (inpETD)         inpETD.value = '';
  if (inpETA)         inpETA.value = '';
  if (inpBL)          inpBL.value  = '';
  if (hidCliente)     hidCliente.value = '';
  if (inpClienteNom)  inpClienteNom.value = '';
  if (typeof hideSugCliente === 'function') hideSugCliente(); // cierra sugerencias cliente

  // Selects dependientes
  setSelectValue(selNavieraEd,   '');
  setSelectValue(selForwarderEd, '');
  setSelectValue(selShipper,     '');

  // Puerto (solo display)
  setSelectValue(selPuerto, '');

  // Notas
  const txtNotas = document.getElementById('notas');
  if (txtNotas) txtNotas.value = '';

  // Repeater: queda 1 fila vacía
  if (typeof resetRepeater === 'function') {
    resetRepeater();
  } else if (repeater) {
    repeater.innerHTML = '';
    // fallback mínimo si no tienes resetRepeater:
    const node = tplContenedor?.content?.cloneNode(true);
    const item = node ? node.querySelector('.contenedor-item') : null;
    if (item) repeater.appendChild(item);
  }

  // Botón guardar deshabilitado hasta que cumpla validaciones
  if (btnGuardarOp) btnGuardarOp.setAttribute('disabled','disabled');

  // Si usas validación por subtipo, recalcula para estado limpio
  if (typeof validarCamposObligatorios === 'function') {
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
    else btnGuardarOp?.setAttribute('disabled','disabled');
  }
}

// ========== 2) Al pulsar “Nueva Operación”: limpiar SIEMPRE antes de mostrar ==========
document.getElementById('btnNuevaOperacion')?.addEventListener('click', (e) => {
  // Fuerza modo creación y limpia todo
  resetModalOperacion('create');
  // Si quieres aplicar el puerto default del subtipo cuando el usuario elija,
  // no selecciones nada aquí; se aplicará con tu lógica existente onchange.
});

// ========== 3) Al cerrar el modal: deja listo para la próxima “Nueva Operación” ==========
modalEl?.addEventListener('hidden.bs.modal', () => {
  resetModalOperacion('create');
});


// Helpers
function safe(v){ return (v===undefined || v===null) ? "" : v; }

function renderCargando(){
  tabla.innerHTML = `
    <tr>
      <td colspan="12" class="text-center text-muted py-4">
        Cargando resultados...
      </td>
    </tr>`;
}

function renderTabla(data){
  tabla.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0){
    tabla.innerHTML = "<tr><td colspan='12' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }
  data.forEach(item=>{
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
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
        <button class="btn btn-sm btn-outline-secondary me-1 btn-edit" data-id="${safe(item.id_operacion)}" title="Editar"><i data-feather="edit"></i></button>
      </td> 
    `;
    tabla.appendChild(tr);
  });
}
 
function renderResumen(meta){
  if (!metaResumen || !meta) return;
  const { total=0, page=1, per_page=perPage, total_pages=1 } = meta;
  if (total === 0){
    metaResumen.textContent = "Mostrando 0–0 de 0";
    return;
  }
  const start = (page - 1) * per_page + 1;
  const end   = Math.min(total, page * per_page);
  metaResumen.textContent = `Mostrando ${start}–${end} de ${total} | pág ${page} de ${total_pages}`;
}

// NUEVO: paginación Bootstrap (ventana de 5)
function renderPaginacion(meta){
  if (!ulPaginacion || !meta) return;

  const { page=1, total_pages=1 } = meta;
  ulPaginacion.innerHTML = "";

  // Prev
  const liPrev = document.createElement("li");
  liPrev.className = "page-item" + (page <= 1 ? " disabled" : "");
  liPrev.innerHTML = `<a class="page-link" href="#" aria-label="Anterior">&laquo;</a>`;
  liPrev.onclick = (e) => {
    e.preventDefault();
    if (page > 1) {
      currentPage = page - 1;
      listar(); // MOD: recarga con página anterior
    }
  };
  ulPaginacion.appendChild(liPrev);

  // Números (máx 5)
  const windowSize = 5;
  let start = Math.max(1, page - Math.floor(windowSize/2));
  let end   = Math.min(total_pages, start + windowSize - 1);
  if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

  for (let p = start; p <= end; p++){
    const li = document.createElement("li");
    li.className = "page-item" + (p === page ? " active" : "");
    li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
    li.onclick = (e) => {
      e.preventDefault();
      if (p !== page){
        currentPage = p;
        listar(); // MOD: recarga con página elegida
      }
    };
    ulPaginacion.appendChild(li);
  }

  // Next
  const liNext = document.createElement("li");
  liNext.className = "page-item" + (page >= total_pages ? " disabled" : "");
  liNext.innerHTML = `<a class="page-link" href="#" aria-label="Siguiente">&raquo;</a>`;
  liNext.onclick = (e) => {
    e.preventDefault();
    if (page < total_pages) {
      currentPage = page + 1;
      listar(); // MOD: recarga con página siguiente
    }
  };
  ulPaginacion.appendChild(liNext);
}
 
// Listar con filtros (server-side) + paginación
function listar() {
  const params = new URLSearchParams();
  const subtipo = (selectSubtipo?.value || "").trim();
  const term    = (inputBuscar?.value || "").trim();

  if (subtipo !== "") params.append("subtipo_id", subtipo);
  if (term !== "")    params.append("term", term);

  // NUEVO: paginación
  params.append("page", String(currentPage));
  params.append("per_page", String(perPage));

  const url = base_url + "Operaciones_maritimas/listar" + "?" + params.toString();

  // Abortamos petición en curso (si la hay)
  if (currentListXHR && currentListXHR.readyState !== 4){
    currentListXHR.abort();
  }

  renderCargando();
  const x = new XMLHttpRequest();
  currentListXHR = x;
  x.open("GET", url, true);
  x.send();
  x.onreadystatechange = function(){
    if (x.readyState === 4){
      if (currentListXHR !== x) return;

      if (x.status !== 200){
        console.error("Error listar:", x.responseText);
        renderTabla([]);
        renderPaginacion({ page:1, total_pages:1 });
        renderResumen({ total:0, page:1, per_page:perPage, total_pages:1 });
        return;
      }

      let payload;
      try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }

      if (payload.status !== "success"){
        renderTabla([]);
        renderPaginacion({ page:1, total_pages:1 });
        renderResumen({ total:0, page:1, per_page:perPage, total_pages:1 });
        return;
      }

      renderTabla(payload.data || []);
      renderPaginacion(payload.meta || { page:1, total_pages:1 });
      renderResumen(payload.meta || { total:0, page:1, per_page:perPage, total_pages:1 });
    }
  };
}


// Eventos reactivos
selectSubtipo?.addEventListener("change", () => {
  currentPage = 1;
  listar();
});

inputBuscar?.addEventListener("keyup", () => {
  clearTimeout(debounceId);
  debounceId = setTimeout(() => {
    currentPage = 1;
    listar();
  }, 250);
});
selectPerPage?.addEventListener("change", () => {
  perPage = parseInt(selectPerPage.value, 10) || 10;
  currentPage = 1;
  listar();
});
// Primer load
window.addEventListener("DOMContentLoaded", () => {
  perPage = parseInt(selectPerPage?.value || "10", 10);
  listar();
  if (window.feather) feather.replace();
});


const selSubtipoModal = document.getElementById('subtipoOperacion');
const selPuertoArribo = document.getElementById('puertoArribo');

function setPuertoDefaultFromSubtipo(){
  if (!selSubtipoModal || !selPuertoArribo) return;

  const opt = selSubtipoModal.options[selSubtipoModal.selectedIndex];
  if (!opt) return;

  const puertoDefault = parseInt(opt.getAttribute('data-puerto-default') || '0', 10);
  if (!puertoDefault) return; // sin default: no tocar

  // Si existe en el catálogo, lo preseleccionamos (el usuario aún puede cambiarlo)
  const found = Array.from(selPuertoArribo.options)
    .some(o => parseInt(o.value || '0', 10) === puertoDefault);

  if (found) selPuertoArribo.value = String(puertoDefault);
}

// Cuando cambie subtipo, aplicamos default (y mantenemos la lógica previa de naviera/forwarder si ya la tienes)
selSubtipoModal?.addEventListener('change', () => {
  setPuertoDefaultFromSubtipo();
  // ... aquí también llamas tu lógica de mostrar/ocultar naviera/forwarder si ya la tenías
});

// Si abres el modal en edición con subtipo precargado, aplica también:
document.getElementById('modalOperacionMaritima')?.addEventListener('shown.bs.modal', () => {
  // Si estás creando, puertoArribo podría estar vacío: aplica default del subtipo
  if (!selPuertoArribo.value) setPuertoDefaultFromSubtipo();
});


// Refs 
const groupNaviera    = document.getElementById('campoNaviera');
const selNaviera      = document.getElementById('navieraId');
const btnGuardarOp    = document.getElementById('btnGuardarOperacion'); 

// Helpers UI
function show(el){ el?.classList.remove('d-none'); }
function hide(el){ el?.classList.add('d-none'); }
function clearSelect(sel){ if(!sel) return; sel.value = ''; }
function enable(el){ el?.removeAttribute('disabled'); }
function disable(el){ el?.setAttribute('disabled','disabled'); }

// Preselección de puerto (si ya lo tienes implementado)
function setPuertoDefaultFromSubtipo(){
  if (!selSubtipoModal || !selPuertoArribo) return;
  const opt = selSubtipoModal.options[selSubtipoModal.selectedIndex];
  if (!opt) return;
  const puertoDefault = parseInt(opt.getAttribute('data-puerto-default') || '0', 10);
  if (!puertoDefault) return;
  const has = Array.from(selPuertoArribo.options).some(o => parseInt(o.value||'0',10) === puertoDefault);
  if (has) selPuertoArribo.value = String(puertoDefault);
}

 

// Valida requisitos mínimos antes de guardar
function validarCamposObligatorios(){
  const opt = selSubtipoModal?.options[selSubtipoModal.selectedIndex];
  const reqNaviera = opt ? parseInt(opt.getAttribute('data-req-naviera') || '0', 10) : 0;

  // Reglas mínimas
  if (!selSubtipoModal?.value) return false;
  if (reqNaviera === 1 && !selNaviera?.value) return false;

  return true;
}

// Enlazar a cambios del Subtipo
selSubtipoModal?.addEventListener('change', () => {
  setPuertoDefaultFromSubtipo(); // si ya lo usas
   

  // Habilitar/Deshabilitar Guardar según validación
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

// Revalidar cuando el usuario cambie naviera
selNaviera?.addEventListener('change', () => {
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

// Al abrir el modal: aplicar reglas si viene en edición
document.getElementById('modalOperacionMaritima')?.addEventListener('shown.bs.modal', () => {
 
  if (!selPuertoArribo.value) setPuertoDefaultFromSubtipo();
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
});
 
 

const groupForwarder  = document.getElementById('campoForwarder');
const selForwarder    = document.getElementById('forwarderId');

function show(el){ el?.classList.remove('d-none'); }
function hide(el){ el?.classList.add('d-none'); }
function clearSelect(sel){ if(!sel) return; sel.value = ''; }
function enable(el){ el?.removeAttribute('disabled'); }
function disable(el){ el?.setAttribute('disabled','disabled'); }

 

// ✅ Validación mínima antes de guardar (Naviera + Forwarder)
function validarCamposObligatorios(){
  const opt = selSubtipoModal?.options[selSubtipoModal.selectedIndex];
  if (!opt) return false;

  const reqNaviera   = parseInt(opt.getAttribute('data-req-naviera') || '0', 10);
  const reqForwarder = parseInt(opt.getAttribute('data-req-forwarder') || '0', 10);

  if (!selSubtipoModal?.value) return false;
  if (reqNaviera   === 1 && !selNaviera?.value)   return false;
  if (reqForwarder === 1 && !selForwarder?.value) return false;

  return true;
}

// Eventos
selSubtipoModal?.addEventListener('change', () => {
 

  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

selForwarder?.addEventListener('change', () => {
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

// Al abrir el modal (edición/alta)
document.getElementById('modalOperacionMaritima')?.addEventListener('shown.bs.modal', () => {
  
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
});

// ===== Autocomplete de Clientes =====
const inpClienteNombre   = document.getElementById('clienteNombre');
const hidClienteId       = document.getElementById('clienteId');
const boxSugCliente      = document.getElementById('sugerenciasCliente');

let xhrCliente = null;
let debounceCliente = null;

// Helpers básicos
function hideSugCliente(){ if (boxSugCliente){ boxSugCliente.style.display = 'none'; boxSugCliente.innerHTML = ''; } }
function showSugCliente(){ if (boxSugCliente){ boxSugCliente.style.display = 'block'; } }
function setCliente(id, nombre){
  if (hidClienteId)     hidClienteId.value = String(id || '');
  if (inpClienteNombre) inpClienteNombre.value = nombre || '';
  hideSugCliente();
}

// Render de sugerencias
function renderSugClientes(list){
  boxSugCliente.innerHTML = '';
  if (!Array.isArray(list) || list.length === 0){
    hideSugCliente();
    return;
  }
  list.forEach(cli => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'list-group-item list-group-item-action';
    btn.textContent = cli.nombre;
    // usar mousedown para capturar antes del blur del input
    btn.addEventListener('mousedown', (e) => {
      e.preventDefault();
      setCliente(cli.id_cliente, cli.nombre);
    });
    boxSugCliente.appendChild(btn);
  });
  showSugCliente();
}

// Petición XHR con debounce
function buscarClientesAjax(q){
  // cancelar en curso
  if (xhrCliente && xhrCliente.readyState !== 4) { xhrCliente.abort(); }

  xhrCliente = new XMLHttpRequest();
  xhrCliente.open('GET', base_url + 'Operaciones_maritimas/buscar_clientes?term=' + encodeURIComponent(q), true);
  xhrCliente.send();
  xhrCliente.onreadystatechange = function(){
    if (xhrCliente.readyState === 4){
      if (xhrCliente.status !== 200) { hideSugCliente(); return; }
      let data = [];
      try { data = JSON.parse(xhrCliente.responseText); } catch(e){ data = []; }
      renderSugClientes(data);
    }
  };
}

// Eventos del input
inpClienteNombre?.addEventListener('keyup', (e) => {
  const q = (inpClienteNombre.value || '').trim();

  // si el usuario vuelve a escribir después de elegir, limpiar el hidden
  if (hidClienteId && q.length >= 0) hidClienteId.value = '';

  // ESC para cerrar
  if (e.key === 'Escape'){ hideSugCliente(); return; }

  // mínimo 2 chars para consultar
  if (q.length < 2){ hideSugCliente(); return; }

  clearTimeout(debounceCliente);
  debounceCliente = setTimeout(() => buscarClientesAjax(q), 220);
});

// Ocultar lista al perder foco (con pequeño delay para permitir click)
inpClienteNombre?.addEventListener('blur', () => {
  setTimeout(hideSugCliente, 150);
});

// También cerrar si clic fuera del contenedor
document.addEventListener('click', (ev) => {
  if (!boxSugCliente) return;
  const inside = boxSugCliente.contains(ev.target) || inpClienteNombre === ev.target;
  if (!inside) hideSugCliente();
});


// ===== Refs del repetidor =====
const repeater        = document.getElementById('contenedoresRepeater');
const tplContenedor   = document.getElementById('contenedorTemplate'); 

// Mapas por input para manejar debounce y XHR independientes
const debounceMap = new WeakMap();
const xhrMap      = new WeakMap();

// Helpers UI
function hideBox(box){ if (box){ box.style.display='none'; box.innerHTML=''; } }
function showBox(box){ if (box){ box.style.display='block'; } }
function setContenedor($item, id, numero){
  const hid = $item.querySelector('.contenedor-id');
  const inp = $item.querySelector('.contenedor-input');
  const box = $item.querySelector('.sugerencias-contenedor');
  if (hid) hid.value = String(id || '');
  if (inp) inp.value = numero || '';
  hideBox(box);
}

// Crear una nueva fila (usando el template)
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

// Quitar una fila (dejar al menos 1)
function removeRow(item){
  const items = repeater.querySelectorAll('.contenedor-item');
  if (items.length <= 1) {
    // limpiar en lugar de eliminar la última
    const hid = item.querySelector('.contenedor-id');
    const inp = item.querySelector('.contenedor-input');
    if (hid) hid.value = '';
    if (inp) inp.value = '';
    const box = item.querySelector('.sugerencias-contenedor');
    hideBox(box);
    return;
  }
  item.remove();
}

// Buscar contenedores (XHR con control por input)
function buscarContenedoresAjax(inputEl, q){
  // cancelar XHR previo de este input si existe
  const prev = xhrMap.get(inputEl);
  if (prev && prev.readyState !== 4) prev.abort();

  const x = new XMLHttpRequest();
  xhrMap.set(inputEl, x);

  x.open('GET', base_url + 'Operaciones_maritimas/buscar_contenedores?term=' + encodeURIComponent(q), true);
  x.send();
  x.onreadystatechange = function(){
    if (x.readyState === 4){
      if (xhrMap.get(inputEl) !== x) return; // respuesta vieja
      const item = inputEl.closest('.contenedor-item');
      const box  = item?.querySelector('.sugerencias-contenedor');
      if (x.status !== 200){ hideBox(box); return; }

      let data = [];
      try { data = JSON.parse(x.responseText); } catch(e){ data = []; }

      // Render
      box.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0){ hideBox(box); return; }

      data.forEach(row => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action';
        btn.textContent = row.numero_contenedor;
        btn.addEventListener('mousedown', (ev) => {
          ev.preventDefault();
          const contItem = inputEl.closest('.contenedor-item');
          setContenedor(contItem, row.id_contenedor_maritimo, row.numero_contenedor);
        });
        box.appendChild(btn);
      });
      showBox(box);
    }
  };
}

// Delegación de eventos del repetidor
repeater?.addEventListener('click', (ev) => {
  const target = ev.target.closest('button');
  if (!target) return;

  // Agregar una fila (botón +)
  if (target.classList.contains('btnContAddOne')){
    const item = target.closest('.contenedor-item');
    addRow(item);
    return;
  }

  // Quitar una fila (botón -)
  if (target.classList.contains('btnContRemoveOne')){
    const item = target.closest('.contenedor-item');
    removeRow(item);
    return;
  }
});

// Autocomplete por input (keyup + blur) con delegación
repeater?.addEventListener('keyup', (ev) => {
  const inp = ev.target.closest('.contenedor-input');
  if (!inp) return;

  const item = inp.closest('.contenedor-item');
  const box  = item?.querySelector('.sugerencias-contenedor');

  // si el usuario escribe, invalidamos el hidden id
  const hid = item.querySelector('.contenedor-id');
  if (hid) hid.value = '';

  // cerrar con ESC
  if (ev.key === 'Escape'){ hideBox(box); return; }

  const q = (inp.value || '').trim();
  if (q.length < 3){ hideBox(box); return; } // mínimo 3 chars para contenedores

  // debounce por input
  const prevTO = debounceMap.get(inp);
  if (prevTO) clearTimeout(prevTO);
  const to = setTimeout(() => buscarContenedoresAjax(inp, q), 220);
  debounceMap.set(inp, to);
});

repeater?.addEventListener('blur', (ev) => {
  const inp = ev.target.closest('.contenedor-input');
  if (!inp) return;
  const item = inp.closest('.contenedor-item');
  const box  = item?.querySelector('.sugerencias-contenedor');
  setTimeout(() => hideBox(box), 150); // permite click en sugerencia
}, true); // usar capture para blur delegado

// Cerrar sugerencias si clic fuera
document.addEventListener('click', (ev) => {
  const anyBox = document.querySelectorAll('#contenedoresRepeater .sugerencias-contenedor');
  anyBox.forEach(box => {
    const input = box.closest('.contenedor-item')?.querySelector('.contenedor-input');
    const inside = box.contains(ev.target) || input === ev.target;
    if (!inside) hideBox(box);
  });
});

// (Opcional) función para obtener los contenedores seleccionados al guardar
function getContenedoresSeleccionados(){
  const items = repeater.querySelectorAll('.contenedor-item');
  const res = [];
  items.forEach(it => {
    const id  = (it.querySelector('.contenedor-id')?.value || '').trim();
    const num = (it.querySelector('.contenedor-input')?.value || '').trim();
    if (num !== '') res.push({ id, numero: num });
  });
  return res;
}

// Delegación: click en botón Editar
tabla?.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-edit');
  if (!btn) return;
  const id = parseInt(btn.getAttribute('data-id') || '0', 10);
  if (!id) return;
  cargarOperacionParaEditar(id);
});

function cargarOperacionParaEditar(id){
  resetModalOperacion('edit');
  const txtNotas = document.getElementById('notas');

  const x = new XMLHttpRequest();
  x.open('GET', base_url + 'Operaciones_maritimas/obtener?id=' + encodeURIComponent(id), true);
  x.send();

  x.onreadystatechange = function(){
    if (x.readyState === 4){
      if (x.status !== 200){
        console.error('obtener error:', x.responseText);
        if (window.Swal) Swal.fire('Error', 'No se pudo obtener la operación', 'error');
        return;
      }

      let payload = {};
      try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }

      if (payload.status !== 'success' || !payload.operacion){
        if (window.Swal) Swal.fire('Aviso', payload.msg || 'Operación no encontrada', 'warning');
        return;
      }

      // PRIMERO declara op
      const op = payload.operacion;

      // shipper (select)
      if (typeof setSelectValue === 'function' && typeof selShipper !== 'undefined'){
        setSelectValue(selShipper, op.shipper_id);
      }

      // resto de campos
      if (inpIdOperacion) inpIdOperacion.value = op.id_operacion || '';
      if (inpNumeroOp)    inpNumeroOp.value    = op.numero_operacion || '';
      setSelectValue(selSubtipoEd,  op.subtipo_operacion_id);
      setSelectValue(selEstatus,    op.estatus_id);
      if (inpETD) inpETD.value = op.etd || '';
      if (inpETA) inpETA.value = op.eta || '';
      if (inpBL)  inpBL.value  = op.numero_bl || '';
      if (hidCliente)    hidCliente.value = op.cliente_id || '';
      if (inpClienteNom) inpClienteNom.value = op.cliente_nombre || '';
      if (txtNotas) txtNotas.value = op.notas || '';
      setSelectValue(selNavieraEd,   op.naviera_id);
      setSelectValue(selForwarderEd, op.forwarder_id);
      if (selPuerto) setSelectValue(selPuerto, op.puerto_arribo_id_prefill);

      // OJO: aquí había un typo (contenores). Debe ser contenedores.
      if (Array.isArray(payload.contenedores) && payload.contenedores.length){
        repeater.innerHTML = '';
        payload.contenedores.forEach(c => {
          const row = addRow();
          row.querySelector('.contenedor-id').value    = c.id_contenedor_maritimo || '';
          row.querySelector('.contenedor-input').value = c.numero_contenedor || '';
        });
      }

      if (modalInstance) modalInstance.show();
      if (window.feather) feather.replace();
    }
  };
}


function actualizarOperacion(){
   
  // Validaciones básicas
  if (!inpIdOperacion?.value){
    if (window.Swal) Swal.fire('Aviso', 'Falta el ID de la operación', 'warning');
    return;
  }
  if (!selSubtipoEd?.value){
    if (window.Swal) Swal.fire('Aviso', 'Selecciona el subtipo', 'warning');
    return;
  }
  if (!selEstatus?.value){
    if (window.Swal) Swal.fire('Aviso', 'Selecciona el estatus', 'warning');
    return;
  }
  if (!inpNumeroOp?.value.trim()){
    if (window.Swal) Swal.fire('Aviso', 'Ingresa el número de operación', 'warning');
    return;
  }
  const txtNotas = document.getElementById('notas');
  const fd = new FormData();
  fd.append('id_operacion',         inpIdOperacion.value);
  fd.append('subtipo_operacion_id', selSubtipoEd.value);
  fd.append('numero_operacion',     inpNumeroOp.value.trim());
  fd.append('estatus_id',           selEstatus.value);
  fd.append('etd',                  inpETD?.value || '');
  fd.append('eta',                  inpETA?.value || '');
  fd.append('numero_bl',            inpBL?.value || '');
  fd.append('cliente_id',           hidCliente?.value || '');
  fd.append('naviera_id',           selNavieraEd?.value || '');
  fd.append('forwarder_id',         selForwarderEd?.value || '');
  fd.append('shipper_id', selShipper?.value || '');
  fd.append('notas', (txtNotas?.value || '').trim());

  // (Por ahora NO enviamos contenedores porque tu modelo actualizarOperacion() actual no los procesa)
  // Si luego agregas ese manejo, aquí puedes serializarlos:
  // fd.append('contenedores_json', JSON.stringify(getContenedoresSeleccionados()));

  const x = new XMLHttpRequest();
  x.open('POST', base_url + 'Operaciones_maritimas/actualizar', true);
  x.send(fd);
  x.onreadystatechange = function(){
    if (x.readyState === 4){
      if (x.status !== 200){
        console.error('actualizar error:', x.responseText);
        if (window.Swal) Swal.fire('Error', 'No se pudo actualizar la operación', 'error');
        return;
      }
      let payload = {};
      try { payload = JSON.parse(x.responseText); } catch(e){ payload = {}; }

      if (payload.status !== 'success'){
        if (window.Swal) Swal.fire('Error', payload.msg || 'Error al actualizar', 'error');
        return;
      }

      // OK: alerta, cerrar modal, recargar la tabla (manteniendo página/filtros)
      if (window.Swal) Swal.fire('Operación actualizada', '', 'success');
      if (modalInstance) modalInstance.hide();

      // repintar la tabla con la misma página/estado actual
      listar();
    }
  };
} 


const inpShipperNom = document.getElementById('shipperNombre');
const hidShipperId  = document.getElementById('shipper_id');
const boxSugShip    = document.getElementById('sugShippers');

let xhrShipper = null;
let debounceShipper = null;

function hideSugShip(){ if (boxSugShip){ boxSugShip.style.display='none'; boxSugShip.innerHTML=''; } }
function showSugShip(){ if (boxSugShip){ boxSugShip.style.display='block'; } }
function setShipper(id, nombre){
  if (hidShipperId)   hidShipperId.value = String(id || '');
  if (inpShipperNom)  inpShipperNom.value = nombre || '';
  hideSugShip();
}
function renderSugShippers(list){
  boxSugShip.innerHTML = '';
  if (!Array.isArray(list) || list.length === 0){ hideSugShip(); return; }
  list.forEach(s => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'list-group-item list-group-item-action';
    btn.textContent = s.nombre;
    btn.addEventListener('mousedown', (e) => {
      e.preventDefault();
      setShipper(s.id_shipper, s.nombre);
    });
    boxSugShip.appendChild(btn);
  });
  showSugShip();
}
 
 

 