// Refs del modal (no re-declaramos si ya existen; sólo las usamos)
const formOp   = document.getElementById('formOperacionMaritima');
const btnSave  = document.getElementById('btnGuardarOperacion');
//const modalEl  = document.getElementById('modalOperacionMaritima');
const modal    = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
const inpBL = document.getElementById('numeroBL');
 // ASCII estricto (A–Z, a–z, 0–9). Si quisieras permitir espacios: /[^A-Za-z0-9 ]/g
const REGEX_ASCII_BL = /[^A-Za-z0-9]/g;
function limpiarBL() {
  if (!inpBL) return;
  const antes  = inpBL.value || '';
  const limpio = antes.replace(REGEX_ASCII_BL, '').toUpperCase(); // normaliza a MAYÚSCULAS (opcional)
  if (limpio !== antes) inpBL.value = limpio;
}

function validarBL() {
  if (!inpBL) return true;
  const v = (inpBL.value || '').trim();
  // válido si no está vacío y no contiene caracteres fuera del rango permitido
  const esValido = v.length > 0 && !REGEX_ASCII_BL.test(v);
  // feedback visual con Bootstrap
  inpBL.classList.toggle('is-invalid', !esValido);
  // integra con validación HTML5
  inpBL.setCustomValidity(esValido ? '' : 'Solo letras y números');
  return esValido;
}

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
  return new Promise((resolve) => {   // ⬅️ devuelve Promise
    if (!formOp || !btnSave) { resolve(false); return; }

    // (ya validaste BL afuera, pero dejarlo no estorba)
    if (!validarBL()) {
      Swal?.fire('BL inválido', 'El BL solo debe contener letras y números.', 'warning');
      inpBL?.focus();
      resolve(false);
      return;
    }

    const fd = new FormData(formOp);
    fd.append('contenedores', JSON.stringify(collectContenedores()));
    if (inpNumeroOp?.hasAttribute('readonly')) fd.set('numero_operacion', '');

    const x = new XMLHttpRequest();
    x.open('POST', base_url + 'Operaciones_maritimas/registrar', true);
    x.timeout = 20000; // 20s
    x.onerror = x.onabort = x.ontimeout = () => {
      Swal?.fire('Error de red','No se pudo registrar la operación.','error');
      resolve(false);
    };

    x.onreadystatechange = function(){
      if (x.readyState !== 4) return;

      let res = null;
      try { res = JSON.parse(x.responseText); } catch(e){}

      if (x.status !== 200 || !res){
        Swal?.fire('Error','No se pudo registrar la operación.','error');
        resolve(false);
        return;
      }

      if (res.status === 'success'){
        const folioFinal = res.numero_operacion || '';
        if (inpNumeroOp){
          inpNumeroOp.value = folioFinal;
          inpNumeroOp.setAttribute('readonly','readonly');
        }
        const help = document.getElementById('folioHelp');
        if (help){
          help.classList.remove('text-muted');
          help.classList.add('text-success');
          help.textContent = `Folio definitivo asignado: ${folioFinal}`;
        }
        Swal?.fire('¡Éxito!', `Operación creada (${folioFinal})`, 'success');
        modal?.hide();
        formOp.reset();
        resetRepeater?.();
        listar?.();
        resolve(true);
      } else if (res.status === 'warning'){
        Swal?.fire('Atención', res.msg || 'Revisa los datos.', 'warning');
        resolve(false);
      } else {
        Swal?.fire('Error', res.msg || 'No se pudo registrar.', 'error');
        resolve(false);
      }
    };

    x.send(fd);
  });
}


// ---- Wire-up ----

btnGuardarOp?.addEventListener('click', (e) => {
  e.preventDefault();
  if (btnGuardarOp.disabled) return;

  const id = (document.getElementById('id_operacion')?.value || '').trim();

  // ✅ valida antes (lo mínimo imprescindible)
  if (!validarBL()) { inpBL?.focus(); return; }
  if (!validarCamposObligatorios()) { return; }
  if (!validarClienteSeleccionado()) { return; }

  btnGuardarOp.disabled = true;

  const done = () => { btnGuardarOp.disabled = false; };

  // Usa las versiones *promisificadas* (abajo)
  const p = id ? actualizarOperacion() : guardarOperacion();
  p.finally(done); // Siempre se re-habilita pase lo que pase
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
  inpNumeroOp.value = d.codigo;                 // Folio preliminar
  inpNumeroOp.setAttribute('readonly','readonly');
  const help = document.getElementById('folioHelp');
  if (help){
    help.classList.remove('text-success');
    help.classList.add('text-muted');
    help.textContent = `Folio preliminar: ${d.codigo}. El definitivo se asigna al guardar.`;
  }
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
  // En edición NO tocamos inpNumeroOp; debe quedar readonly
  if (!isEdit && inpNumeroOp) {
    // Solo en creación seguimos dejando readonly (modo auto) hasta guardar
    inpNumeroOp.setAttribute('readonly','readonly');
  }
});

// Excel
  document.getElementById('btnExportarExcelOperaciones')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: 'tablaOperacionesMaritimasExportar',       // "#tablaEventos" o el elemento también funciona
      formato: 'xlsx',
      nombre: 'OperacionesMaritimas.xlsx',
      columnasOcultas: [10],      // oculta columna ID
      soloVisibles: true,
      sheetName: 'Operaciones'
    });
  });

  // PDF
  document.getElementById('btnExportarPDFOperaciones')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: '#tablaOperacionesMaritimasExportar',
      formato: 'pdf',
      nombre: 'OperacionesMaritimas.pdf',
      titulo: 'Operaciones Maritimas',
      orientacion: 'landscape',  // o 'portrait'
      formatoPagina: 'letter',   // o 'a4'
      columnasOcultas: [10],
      soloVisibles: true
    });
  });
// Filtra mientras escribe / pega y valida al salir
inpBL?.addEventListener('input', limpiarBL);
inpBL?.addEventListener('blur', () => { limpiarBL(); validarBL(); });
