// Refs del modal (no re-declaramos si ya existen; sólo las usamos)
const formOp   = document.getElementById('formOperacionMaritima');
const btnSave  = document.getElementById('btnGuardarOperacion');
//const modalEl  = document.getElementById('modalOperacionMaritima');
const modal    = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

// ---- Helpers locales (sin duplicar los tuyos) ----
function valStr(v){ return (v ?? '').toString().trim(); }
function isEmpty(v){ return valStr(v) === ''; }

// Validación de cliente (nombre escrito debe corresponder a un ID seleccionado)
function validarClienteSeleccionado(){
  if (!hidClienteId || !inpClienteNombre) return true;
  const id  = valStr(hidClienteId.value);
  const nom = valStr(inpClienteNombre.value);
  if (nom !== '' && id === '') {
    if (window.Swal) Swal.fire('Cliente no válido','Selecciona un cliente de la lista de sugerencias.','warning');
    else alert('Cliente no válido. Selecciona un cliente de la lista de sugerencias.');
    return false;
  }
  return true;
}

// Validación por subtipo (usa los data-* ya puestos en el <option>)
function validarRequisitosSubtipo(){
  if (!selSubtipoModal) return true;
  const opt = selSubtipoModal.options[selSubtipoModal.selectedIndex];
  if (!opt) return false;

  const reqNaviera   = parseInt(opt.getAttribute('data-req-naviera') || '0', 10);
  const reqForwarder = parseInt(opt.getAttribute('data-req-forwarder') || '0', 10);

  if (isEmpty(selSubtipoModal.value)) return false;
  if (reqNaviera === 1 && (!selNaviera || isEmpty(selNaviera.value))) return false;
  if (reqForwarder === 1 && (!selForwarder || isEmpty(selForwarder.value))) return false;

  return true;
}
 

// Recolecta contenedores del repeater (id/numero)
function collectContenedores(){
  const out = [];
  if (!repeater) return out;
  repeater.querySelectorAll('.contenedor-item').forEach(item => {
    const id  = valStr(item.querySelector('.contenedor-id')?.value || '');
    const num = valStr(item.querySelector('.contenedor-input')?.value || '');
    if (id !== '' || num !== '') out.push({ id: id || 0, numero: num });
  });
  return out;
}

// Resetea el repeater a un solo ítem vacío
function resetRepeater(){
  if (!repeater || !tplContenedor) return;
  repeater.innerHTML = '';
  const node = tplContenedor.content.cloneNode(true);
  const newItem = node.querySelector('.contenedor-item');
  repeater.appendChild(newItem);
  if (window.feather && typeof feather.replace === 'function') feather.replace();
}

// ---- Guardar (POST) ----
function guardarOperacion(){
  if (!formOp || !btnSave) return;

  // 1) Validaciones mínimas
  if (!validarClienteSeleccionado()) return; 
  if (!validarRequisitosSubtipo()){
    if (window.Swal) Swal.fire('Faltan campos','Completa los campos requeridos del subtipo.','warning');
    else alert('Completa los campos requeridos del subtipo.');
    return;
  }

  // 2) FormData + contenedores (NO se envía puerto_arribo_id)
  const fd = new FormData(formOp);
  const contenedores = collectContenedores();
  fd.append('contenedores', JSON.stringify(contenedores));

  // 3) XHR (mantenemos tu estilo)
  const x = new XMLHttpRequest();
  x.open('POST', base_url + 'Operaciones_maritimas/registrar', true);

  // UX: deshabilitar botón y spinner
  btnSave.setAttribute('disabled','disabled');
  btnSave.innerHTML = '<i data-feather="loader" class="me-1"></i> Guardando...';
  if (window.feather && typeof feather.replace === 'function') feather.replace();

  x.onreadystatechange = function(){
    if (x.readyState === 4){
      // Restaurar botón
      btnSave.removeAttribute('disabled');
      btnSave.innerHTML = '<i data-feather="save" class="me-1"></i> Guardar';
      if (window.feather && typeof feather.replace === 'function') feather.replace();

      let res = null;
      try { res = JSON.parse(x.responseText); } catch(e){}

      if (x.status !== 200 || !res){
        if (window.Swal) Swal.fire('Error','No se pudo registrar la operación.','error');
        else alert('No se pudo registrar la operación.');
        return;
      }

      if (res.status === 'success'){
        if (window.Swal) Swal.fire('¡Éxito!', res.msg || 'Operación creada.', 'success');
        if (modal) modal.hide();
        formOp.reset();
        resetRepeater();
        if (typeof listar === 'function') listar();
      } else if (res.status === 'warning'){
        if (window.Swal) Swal.fire('Atención', res.msg || 'Revisa los datos.', 'warning');
        else alert(res.msg || 'Revisa los datos.');
      } else {
        if (window.Swal) Swal.fire('Error', res.msg || 'No se pudo registrar.', 'error');
        else alert(res.msg || 'No se pudo registrar.');
      }
    }
  };

  x.send(fd);
}

// ---- Wire-up ----

btnGuardarOp?.addEventListener('click', (e) => {
  e.preventDefault();
  const id = (document.getElementById('id_operacion')?.value || '').trim();
  if (id) {
    actualizarOperacion();           // edición
  } else if (typeof guardarOperacion === 'function') {
    guardarOperacion();              // alta (definida en el otro archivo)
  }
});
modalEl?.addEventListener('shown.bs.modal', () => {
  if (typeof validarCamposObligatorios === 'function') {
    if (validarCamposObligatorios()) btnSave?.removeAttribute('disabled');
    else btnSave?.setAttribute('disabled','disabled');
  }
});

[selSubtipoModal, selNaviera, selForwarder]?.forEach(el => {
  el?.addEventListener('change', () => {
    if (typeof validarCamposObligatorios === 'function') {
      if (validarCamposObligatorios()) btnSave?.removeAttribute('disabled');
      else btnSave?.setAttribute('disabled','disabled');
    }
  });
});
