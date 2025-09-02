// Refs del modal (no re-declaramos si ya existen; sólo las usamos)
const formOp   = document.getElementById('formOperacionMaritima');
const btnSave  = document.getElementById('btnGuardarOperacion');
//const modalEl  = document.getElementById('modalOperacionMaritima');
const modal    = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

 

const btnNuevaOp      = document.getElementById('btnNuevaOperacion');

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



  const fd = new FormData(formOp);
  const contenedores = collectContenedores();
  fd.append('contenedores', JSON.stringify(contenedores));

  // 🔴 CLAVE: si está en modo auto (readonly), fuerza vacío para que lo genere el backend
  if (inpNumeroOp?.hasAttribute('readonly')) {
    fd.set('numero_operacion', ''); // el backend asigna el definitivo
  }

  const x = new XMLHttpRequest();
  x.open('POST', base_url + 'Operaciones_maritimas/registrar', true);

  // ...tu UX de spinner...

  x.onreadystatechange = function(){
    if (x.readyState === 4){
      // ...tu restauración de botón...

      let res = null;
      try { res = JSON.parse(x.responseText); } catch(e){}
      console.log(this.responseText);
      if (x.status !== 200 || !res){
        Swal?.fire('Error','No se pudo registrar la operación.','error') ?? alert('No se pudo registrar la operación.');
        return;
      }
 
      if (res.status === 'success'){
        // Muestra el folio final (devuelto por el backend)
        const cod = res.numero_operacion ? ` (${res.numero_operacion})` : '';
        Swal?.fire('¡Éxito!', (res.msg || 'Operación creada.') + cod, 'success');
        modal?.hide();
        formOp.reset();
        resetRepeater?.();
        if (typeof listar === 'function') listar();
      } else if (res.status === 'warning'){
        Swal?.fire('Atención', res.msg || 'Revisa los datos.', 'warning') ?? alert(res.msg || 'Revisa los datos.');
      } else {
        Swal?.fire('Error', res.msg || 'No se pudo registrar.', 'error') ?? alert(res.msg || 'No se pudo registrar.');
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
// Al elegir subtipo
function prefillNumeroPorSubtipo() {
  const subtipoId = (selSubtipoModal?.value || '').trim();
  const isEdit = (document.getElementById('id_operacion')?.value || '').trim() !== '';
  if (!subtipoId || isEdit) return;

  const x = new XMLHttpRequest();
  x.open('GET', base_url + 'Operaciones_maritimas/siguiente_codigo?subtipo_id=' + encodeURIComponent(subtipoId), true);
  x.onreadystatechange = function(){
    if (x.readyState === 4 && x.status === 200){
      let d = {}; try { d = JSON.parse(x.responseText); } catch(e){}
      if (d && d.codigo && inpNumeroOp){
        inpNumeroOp.value = d.codigo;
        inpNumeroOp.setAttribute('readonly','readonly'); // modo auto
      }
    }
  };
  x.send();
}



 // Al abrir "Nueva Operación"
btnNuevaOp?.addEventListener('click', () => {
  // id vacío = modo crear
  const idOp = document.getElementById('id_operacion');
  if (idOp) idOp.value = '';

  if (inpNumeroOp){
    inpNumeroOp.value = '';
    inpNumeroOp.setAttribute('placeholder','Se generará automáticamente');
    inpNumeroOp.setAttribute('readonly','readonly'); // activar modo auto
  }
  prefillNumeroPorSubtipo();
});

// Cuando cambie el subtipo, vuelve a pre-llenar sólo si estás creando
selSubtipoModal?.addEventListener('change', () => {
  prefillNumeroPorSubtipo();

  // tu validación existente
  if (typeof validarCamposObligatorios === 'function') {
    if (validarCamposObligatorios()) btnSave?.removeAttribute('disabled');
    else btnSave?.setAttribute('disabled','disabled');
  }
});

// Al cargar en edición, asegúrate de quitar readonly (tu función cargarOperacionParaEditar ya setea valores)
document.getElementById('modalOperacionMaritima')?.addEventListener('shown.bs.modal', () => {
  const isEdit = (document.getElementById('id_operacion')?.value || '').trim() !== '';
  if (isEdit && inpNumeroOp) inpNumeroOp.removeAttribute('readonly');
});
