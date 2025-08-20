// ===============================
//  Contenedores en Operación - JS
// ===============================
console.log(window.CAT_OPERACIONES?.[0]); 
// Variables específicas del módulo Contenedores
const tablaContenedores       = document.querySelector("#tablaContenedores tbody");
const selectTipoContenedores  = document.getElementById("filtro_tipo");
const inputBuscarContenedores = document.getElementById("buscar");

let currentListXHRContenedores = null;

// Helpers
function safeCont(v){ return (v === undefined || v === null) ? "" : v; }

// Render cargando
function renderCargandoCont(){
  tablaContenedores.innerHTML = `
    <tr>
      <td colspan="11" class="text-center text-muted py-4">
        Cargando resultados...
      </td>
    </tr>`;
}

// Render tabla
function renderTablaCont(data){
  tablaContenedores.innerHTML = "";
  if (!Array.isArray(data) || data.length === 0){
    tablaContenedores.innerHTML = "<tr><td colspan='11' class='text-center'>No se encontraron resultados</td></tr>";
    return;
  }

  data.forEach(item=>{
    const tr = document.createElement("tr");
    tr.classList.add("text-center");
    tr.innerHTML = ` 
      <td>
        ${item.tipo === "maritimo"
          ? `<i data-feather="anchor" class="text-primary"></i> Marítimo`
          : `<i data-feather="truck" class="text-warning"></i> Terrestre`}
      </td>
      <td>${safeCont(item.operacion)}</td>
      <td>${safeCont(item.contenedor)}</td>
      <td>${safeCont(item.cliente)}</td>
      <td>${safeCont(item.bultos)}</td>
      
      <td>${safeCont(item.eta)}</td>
      <td>${safeCont(item.etd)}</td>
      <td>${safeCont(item.arribo_sd)}</td>
      <td>${safeCont(item.shipper)}</td>
      <td>
        <button 
          class="btn btn-sm btn-outline-secondary btn-edit-contenedor" 
          title="Editar"
          data-row-id="${safeCont(item.row_id)}"
          data-tipo="${safeCont(item.tipo)}"
          data-operacion-id="${safeCont(item.id_operacion)}">
          <i data-feather="edit"></i>
       </button>
        <button class="btn btn-sm btn-outline-danger" title="Eliminar">
          <i data-feather="x"></i>
        </button>
      </td>
    `;
    tablaContenedores.appendChild(tr);
  });

  feather.replace();
}

// Listar
function listarContenedores() {
  const params = new URLSearchParams();
  const tipo   = (selectTipoContenedores?.value || "").trim();
  const term   = (inputBuscarContenedores?.value || "").trim();

  if (tipo !== "") params.append("tipo", tipo); 
  if (term !== "") params.append("term", term);

  const url = base_url + "Operaciones_maritimas_contenedores/listar" + 
              (params.toString() ? ("?" + params.toString()) : "");

  if (currentListXHRContenedores && currentListXHRContenedores.readyState !== 4){
    currentListXHRContenedores.abort();
  }

  renderCargandoCont();
  const x = new XMLHttpRequest();
  currentListXHRContenedores = x;
  x.open("GET", url, true);
  x.send();
  x.onreadystatechange = function(){
    if (x.readyState === 4){
      if (currentListXHRContenedores !== x) return;
      if (x.status !== 200){
        console.error("Error listar contenedores:", x.responseText);
        renderTablaCont([]);
        return;
      }
      let data;
      try { data = JSON.parse(x.responseText); } catch(e){ data = []; }
      renderTablaCont(data);
    }
  };
}

// Eventos de filtros
selectTipoContenedores.addEventListener("change", listarContenedores);
inputBuscarContenedores.addEventListener("keyup", function(e){
  if (e.key === "Enter" || this.value.length >= 3 || this.value.length === 0){
    listarContenedores();
  }
});

// Cargar al inicio
window.addEventListener("DOMContentLoaded", listarContenedores);

// ===============================
//  Autocompletados modal
// ===============================
// ===============================
//  Autocompletados modal
// ===============================
(function(){
  const modalEl = document.getElementById('modalAgregarContenedor');

  // ⚠️ ¡Todas las referencias scoped al modal!
  const hidOp   = modalEl?.querySelector('#operacion_id');
  const inpOp   = modalEl?.querySelector('#operacionNombre');
  const boxOp   = modalEl?.querySelector('#sugOperaciones');

  const hidCli  = modalEl?.querySelector('#cliente_id');
  const inpCli  = modalEl?.querySelector('#clienteNombreContenedores'); // readonly

  const hidCf   = modalEl?.querySelector('#contenedor_id');
  const inpCf   = modalEl?.querySelector('#contenedorNombre');
  const boxCf   = modalEl?.querySelector('#sugContenedores');

  const hidSh   = modalEl?.querySelector('#shipper_id');
  const inpSh   = modalEl?.querySelector('#shipperNombre');
  const boxSh   = modalEl?.querySelector('#sugShippers');

  function show(box){ box && (box.style.display='block'); }
  function hide(box){ if (box){ box.style.display='none'; box.innerHTML=''; } }
  function pick(hid, inp, box, id, label){ if(hid) hid.value = id; if(inp) inp.value = label; hide(box); }

  function filterList(list, q){
    q = (q || '').toLowerCase();
    if (!q) return [];
    return list
      .filter(x => (x.label || '').toLowerCase().includes(q) || (x.cliente || '').toLowerCase().includes(q))
      .slice(0,10);
  }

  function renderSug(box, list, onPick){
    box.innerHTML = '';
    if (!list.length){ hide(box); return; }
    list.forEach(x=>{
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action d-flex justify-content-between';
      btn.innerHTML = `<span>${x.label || ''}</span><small class="text-muted ms-3">${x.cliente || ''}</small>`;
      const handler = (ev)=>{ ev.preventDefault(); onPick(x); };
      btn.addEventListener('pointerdown', handler);
      btn.addEventListener('click', handler);
      box.appendChild(btn);
    });
    show(box);
  }

  // Operaciones
  inpOp?.addEventListener('keyup', (e) => {
    if (e.key === 'Escape'){ hide(boxOp); return; }

    // Si el usuario vuelve a teclear, limpia selección previa
    if (hidOp) hidOp.value = '';
    if (hidCli) hidCli.value = '';
    if (inpCli) inpCli.value = '';

    const q = (inpOp.value || '').trim();
    if (q.length < 2){ hide(boxOp); return; }

    const list = (window.CAT_OPERACIONES || []);
    const res  = filterList(list, q);

    renderSug(boxOp, res, (it)=> {
      // set operación
      pick(hidOp, inpOp, boxOp, it.id, it.label);

      // set cliente (ID + nombre visual)
      if (hidCli) hidCli.value = (it.cliente_id || 0);
      if (inpCli) {
        inpCli.value = (it.cliente || '');
        // Refuerzos para evitar que algún estilo/framework “no pinte” el value
        inpCli.setAttribute('value', inpCli.value);
        inpCli.dispatchEvent(new Event('input', { bubbles: true }));
        inpCli.dispatchEvent(new Event('change', { bubbles: true }));
      }

      console.log('OP seleccionada:', it, '-> cliente_id:', hidCli?.value, 'clienteNombreContenedores:', inpCli?.value);
    });
  });

  inpOp?.addEventListener('blur', ()=> setTimeout(()=> hide(boxOp), 150));

  // Contenedores Físicos
  inpCf?.addEventListener('keyup', (e) => {
    if (e.key === 'Escape'){ hide(boxCf); return; }
    if (hidCf) hidCf.value = '';
    const q = (inpCf.value || '').trim();
    if (q.length < 2){ hide(boxCf); return; }
    const res = filterList(window.CAT_FISICOS || [], q);
    renderSug(boxCf, res, (it)=> pick(hidCf, inpCf, boxCf, it.id, it.label));
  });
  inpCf?.addEventListener('blur', ()=> setTimeout(()=> hide(boxCf), 150));

  // Shippers
  inpSh?.addEventListener('keyup', (e) => {
    if (e.key === 'Escape'){ hide(boxSh); return; }
    if (hidSh) hidSh.value = '';
    const q = (inpSh.value || '').trim();
    if (q.length < 2){ hide(boxSh); return; }
    const res = filterList(window.CAT_SHIPPERS || [], q);
    renderSug(boxSh, res, (it)=> pick(hidSh, inpSh, boxSh, it.id, it.label));
  });
  inpSh?.addEventListener('blur', ()=> setTimeout(()=> hide(boxSh), 150));
})();


// ===============================
//  Submit del formulario (ALTA)
// ===============================
(function () {
  const form = document.getElementById('formAgregarContenedor');
  const modalEl = document.getElementById('modalAgregarContenedor');
  const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

  form?.addEventListener('submit', function (e) {
    e.preventDefault(); // evita refresh
    const modo          = form.dataset.mode || 'create';
    const row_id        = document.getElementById('row_id')?.value?.trim() || '';
    const operacion_id   = document.getElementById('operacion_id')?.value?.trim() || '';
    const numero_ferro   = document.getElementById('contenedorNombre')?.value?.trim() || ''; // número visible
    const bultos         = document.getElementById('bultos')?.value?.trim() || '';
    const cliente_id     = document.getElementById('cliente_id')?.value?.trim() || '';
    const comentarios   = document.getElementById('comentarios')?.value?.trim() || '';

    // Validaciones mínimas
    if (!operacion_id) {
      Swal.fire('Aviso', 'Selecciona una operación', 'warning'); return;
    }
    if (!cliente_id) {
      Swal.fire('Aviso', 'La operación no tiene cliente asignado', 'warning'); return;
    }
    if (!numero_ferro) {
      Swal.fire('Aviso', 'Captura o selecciona el contenedor físico', 'warning'); return;
    }

    const fd = new FormData();
    fd.append('operacion_id', operacion_id);
    fd.append('numero_ferro', numero_ferro);
    fd.append('bultos', bultos);
    fd.append('cliente_id', cliente_id); // 👉 se envía, aunque el backend lo vuelve a forzar
    let url = '';
   // Si estamos editando (hay row_id) → actualizar
  if ((modo === 'edit' || row_id) && row_id) {
  url = base_url + 'Operaciones_maritimas_contenedores/actualizarFisico';
  fd.append('row_id', row_id);
  fd.append('operacion_id', operacion_id);
  fd.append('numero_ferro', numero_ferro);
  fd.append('bultos', bultos);
  fd.append('comentarios', comentarios);
  } else {
  // Alta normal
  url = base_url + 'Operaciones_maritimas_contenedores/registrarFisico';
  fd.append('operacion_id', operacion_id);
  fd.append('numero_ferro', numero_ferro);
  fd.append('bultos', bultos);
  fd.append('cliente_id', cliente_id); // el backend fuerza el de la operación
    fd.append('comentarios', comentarios); // por si luego decides guardarlo
    }

    const http = new XMLHttpRequest();
    http.open('POST', base_url + 'Operaciones_maritimas_contenedores/registrarFisico', true);
    http.send(fd);

    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) {
          Swal.fire('Error', 'Error HTTP ' + this.status, 'error');
          return;
        }
        let res;
        try { res = JSON.parse(this.responseText); }
        catch(e){ Swal.fire('Error', 'Respuesta inválida del servidor', 'error'); return; }

        Swal.fire('Aviso', (res.msg || '').toUpperCase(), res.status || 'info');

        if (res.status === 'success') {
          // reset form y limpiar hiddens
          form.reset();
          ['operacion_id','cliente_id','contenedor_id','shipper_id'].forEach(id=>{
            const el = document.getElementById(id);
            if (el) el.value = '';
          });
          // limpiar visibles
          ['operacionNombre','clienteNombreContenedores','contenedorNombre','shipperNombre','bultos','comentarios'].forEach(id=>{
            const el = document.getElementById(id);
            if (el) el.value = '';
          });

          modal?.hide?.();
          listarContenedores();
        }
      }
    };
  });
})();
// ===============================
//  Editar: delegación de clic en la tabla
// ===============================
tablaContenedores.addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-edit-contenedor');
  if (!btn) return;

  const tipo = (btn.dataset.tipo || '').toLowerCase();
  const rowId = parseInt(btn.dataset.rowId || '0', 10);

  if (!rowId || !tipo) {
    Swal.fire('Aviso', 'No se pudo identificar el contenedor a editar', 'warning');
    return;
  }

  // 1) Si es marítimo → alerta y fuera
  if (tipo === 'maritimo') {
    Swal.fire('Aviso', 'Contenedor Marítimo se edita en Módulo de Operaciones', 'warning');
    return;
  }

  // 2) Si es terrestre → pedir detalle y prellenar modal
  const url = base_url + 'Operaciones_maritimas_contenedores/detalle?tipo=' 
            + encodeURIComponent(tipo) + '&row_id=' + encodeURIComponent(rowId);

  const x = new XMLHttpRequest();
  x.open('GET', url, true);
  x.send();
  x.onreadystatechange = function () {
    if (x.readyState !== 4) return;
    if (x.status !== 200) {
      Swal.fire('Error', 'Error HTTP ' + x.status, 'error');
      return;
    }
    let res;
    try { res = JSON.parse(x.responseText); } catch (e) {
      Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
      return;
    }

    if (res.status === 'warning' && res.data?.editable === false) {
      Swal.fire('Aviso', (res.msg || 'Este contenedor se edita en el módulo de Operaciones'), 'warning');
      return;
    }
    if (res.status !== 'success' || !res.data) {
      Swal.fire('Aviso', res.msg || 'No se pudo obtener el detalle', 'warning');
      return;
    }

    // Prefill modal (modo edición)
    const d = res.data;
    const modalEl = document.getElementById('modalAgregarContenedor');
    const modal   = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    const form    = document.getElementById('formAgregarContenedor');

    // Asegúrate de tener este hidden en el HTML del form:
    // <input type="hidden" id="row_id" name="row_id">
    document.getElementById('row_id')?.setAttribute('value', d.row_id);
    document.getElementById('row_id').value = d.row_id;

    // Seteamos modo edición en el form
    form.dataset.mode = 'edit';

    // Operación y cliente (vienen de la operación)
    document.getElementById('operacion_id').value    = d.id_operacion || '';
    document.getElementById('operacionNombre').value = d.numero_operacion || '';
    document.getElementById('cliente_id').value    = d.cliente_id || '';

    const cliInp = document.getElementById('clienteNombreContenedores');
    cliInp.value = d.cliente || '';

    // refuerzos para que “pinte” sí o sí
    cliInp.setAttribute('value', cliInp.value);
    cliInp.dispatchEvent(new Event('input',  { bubbles: true }));
    cliInp.dispatchEvent(new Event('change', { bubbles: true }));

    // Contenedor físico y demás campos editables
    document.getElementById('contenedor_id').value   = d.id_fisico || ''; // (opcional)
    document.getElementById('contenedorNombre').value= d.numero_ferro || '';
    document.getElementById('bultos').value          = (d.bultos ?? '');
    document.getElementById('comentarios').value     = (d.comentarios ?? '');

    // Título del modal y botón
    const title = document.getElementById('modalAgregarContenedorLabel');
    if (title) title.innerHTML = '<i data-feather="edit" class="me-1"></i> Editar Contenedor (Terrestre)';
    const btnGuardar = document.querySelector('button[form="formAgregarContenedor"]');
    if (btnGuardar) btnGuardar.innerHTML = '<i data-feather="save"></i> Actualizar';

    feather.replace();
    modal?.show();
  };
});
