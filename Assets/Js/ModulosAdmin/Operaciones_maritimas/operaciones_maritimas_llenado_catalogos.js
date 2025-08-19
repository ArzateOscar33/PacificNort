  
const tabla         = document.getElementById("tablaOperacionesMaritimas");
const inputBuscar   = document.getElementById("buscarOperacion");
const selectSubtipo = document.getElementById("filtroSubtipo");

let currentListXHR = null;   // para abortar solicitudes previas
let debounceId     = null;

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
        <button class="btn btn-sm btn-outline-info"   title="Ver"    ><i class="fas fa-eye"></i></button>
        <button class="btn btn-sm btn-outline-primary" title="Editar" ><i class="fas fa-edit"></i></button>
      </td>
    `;
    tabla.appendChild(tr);
  });
}

// Listar con filtros (server-side)
function listar() {
  const params = new URLSearchParams();
  const subtipo = (selectSubtipo?.value || "").trim();
  const term    = (inputBuscar?.value || "").trim();

  if (subtipo !== "") params.append("subtipo_id", subtipo); 
  if (term !== "")    params.append("term", term);

  const url = base_url + "Operaciones_maritimas/listar" + (params.toString() ? ("?" + params.toString()) : "");

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
      // Si esta respuesta no es la más reciente, ignorar
      if (currentListXHR !== x) return;

      if (x.status !== 200){
        console.error("Error listar:", x.responseText);
        renderTabla([]);
        return;
      }
      let data;
      try { data = JSON.parse(x.responseText); } catch(e){ data = []; }
      renderTabla(data);
    }
  };
}

// Eventos reactivos
selectSubtipo?.addEventListener("change", listar);

inputBuscar?.addEventListener("keyup", () => {
  clearTimeout(debounceId);
  debounceId = setTimeout(listar, 250);
});

// Primer load
window.addEventListener("DOMContentLoaded", () => {
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

// 🔹 Nuevo: aplicar regla de Naviera
function applyNavieraRuleFromSubtipo(){
  if (!selSubtipoModal) return;
  const opt = selSubtipoModal.options[selSubtipoModal.selectedIndex];
  if (!opt) return;
  const reqNaviera = parseInt(opt.getAttribute('data-req-naviera') || '0', 10);

  if (reqNaviera === 1){
    show(groupNaviera);
    enable(selNaviera);
    // Opcional: si quieres forzar selección, NO autoselecciones aquí.
    // Si deseas auto-seleccionar si sólo hay una opción real:
    // const opts = Array.from(selNaviera.options).filter(o => o.value !== '');
    // if (opts.length === 1) selNaviera.value = opts[0].value;
  } else {
    hide(groupNaviera);
    clearSelect(selNaviera); // limpiar si antes se había seleccionado
    disable(selNaviera);     // opcional
  }
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
  applyNavieraRuleFromSubtipo();

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
  applyNavieraRuleFromSubtipo();
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

// 🔹 Regla para Forwarder en base al Subtipo
function applyForwarderRuleFromSubtipo(){
  if (!selSubtipoModal) return;
  const opt = selSubtipoModal.options[selSubtipoModal.selectedIndex];
  if (!opt) return;

  const reqForwarder = parseInt(opt.getAttribute('data-req-forwarder') || '0', 10);

  if (reqForwarder === 1){
    show(groupForwarder);
    enable(selForwarder);
    // (Opcional) autoseleccionar si solo hay 1 opción real
    // const opts = Array.from(selForwarder.options).filter(o => o.value !== '');
    // if (opts.length === 1) selForwarder.value = opts[0].value;
  } else {
    hide(groupForwarder);
    clearSelect(selForwarder);
    disable(selForwarder); // opcional
  }
}

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
  // ya tienes: setPuertoDefaultFromSubtipo(); applyNavieraRuleFromSubtipo();
  applyForwarderRuleFromSubtipo();

  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

selForwarder?.addEventListener('change', () => {
  if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute('disabled');
  else btnGuardarOp?.setAttribute('disabled','disabled');
});

// Al abrir el modal (edición/alta)
document.getElementById('modalOperacionMaritima')?.addEventListener('shown.bs.modal', () => {
  applyForwarderRuleFromSubtipo();
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
