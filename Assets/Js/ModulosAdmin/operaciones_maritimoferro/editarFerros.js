 // Archivo: assets/js/modulosAdmin/operaciones_maritimoferro/ferrosOperacion.editar.js
(function(){
  "use strict";

  const MODAL_ID         = 'modalFerroOP';
  const FORM_ID          = 'formFerroOP';
  const BTN_FOOTER_SEL   = 'button[form="formFerroOP"][type="submit"]'; // tu botón de crear por defecto
  const ENDPT_GET        = 'Operaciones_maritimo_ferro_contenedores/obtener_operacion';
  const ENDPT_UPDATE     = 'Operaciones_maritimo_ferro_contenedores/actualizar_operacion';

  const modalEl          = document.getElementById(MODAL_ID);
  const form             = document.getElementById(FORM_ID);
  const btnFooter        = document.querySelector(BTN_FOOTER_SEL);

  if (!modalEl || !form || !btnFooter) return; // no está la vista

  // Refs que ya existen en tu HTML
  const folioInp         = document.getElementById('operacionNombreFerroOP');
  const fechaInp         = document.getElementById('fechaFerroOP');
  const estatusSel       = document.getElementById('estatusId_f');

  const ferroIdHid       = document.getElementById('contenedorFerroIdFerroOP');
  const ferroNomInp      = document.getElementById('contenedorFerroNombreFerroOP');

  const transIdHid       = document.getElementById('transportistaIdFerroOP');
  const transNomInp      = document.getElementById('transportistaNombreFerroOP');

  const destIdHid        = document.getElementById('destinoIdFerroOP');
  const destNomInp       = document.getElementById('destinoNombreFerroOP');

  const comentariosTA    = document.getElementById('comentariosFerroOP');

  // Bloque de selector (agregar marítimos)
  const selectorMar      = document.getElementById('selectorMaritimoFerroOP');
  const btnAgregar       = document.getElementById('btnAgregarMaritimoFerroOP');

  // Tabla del carrito
  const tbodySel         = document.getElementById('tbodyMaritimosSeleccionados');

  // Usaremos un hidden que ya tienes para guardar el ID de la FO
  const rowIdHid         = document.getElementById('rowIdFerroOP');

  // Guardar attrs originales del botón footer para restaurarlos luego
  const btnFooterOriginal = {
    type: btnFooter.getAttribute('type') || 'submit',
    text: btnFooter.innerHTML
  };

  function toast(msg, ok = true){
    if (window.Swal) {
      Swal.fire({icon: ok?'success':'error', title: ok?'Listo':'Aviso', text: msg, timer: 1800, showConfirmButton: false});
    } else { alert(msg); }
  }

  // ========= Helpers de UI (reusamos lo que ya tienes) =========
  function setSoloLecturaHeader(readonly){
    // Estos no se editan en modo edición
    [fechaInp, ferroNomInp, transNomInp, destNomInp].forEach(inp=>{
      if (!inp) return;
      inp.readOnly = readonly;
      inp.disabled = readonly;
    });
    // Select de estatus SÍ es editable
    if (estatusSel) estatusSel.disabled = false;
    // Comentarios SÍ es editable
    if (comentariosTA){
      comentariosTA.readOnly = false;
      comentariosTA.disabled = false;
    }
  }

  function ocultarAutocomplete(){
    ['sugFerrosFerroOP','sugTransportistasFerroOP','destinoFerroOP','sugOperacionesMaritimasFerroOP'].forEach(id=>{
      const el = document.getElementById(id);
      if (el){ el.style.display='none'; el.innerHTML=''; }
    });
  }

  function toggleSelectorMaritimo(show){
    if (selectorMar) selectorMar.classList.toggle('d-none', !show);
    if (btnAgregar)  btnAgregar.style.display = show ? 'none' : '';
    const btnConf = document.getElementById('btnConfirmarMaritimoFerroOP');
    if (btnConf) btnConf.disabled = !show;
    if (show){
      const opInp = document.getElementById('operacionMaritimaNombreFerroOP');
      setTimeout(()=>{ opInp?.focus(); opInp?.select(); }, 0);
    }
  }

  // ========= Carga de datos para editar =========
  async function fetchJSON(url){
    return new Promise((resolve)=>{
      const x = new XMLHttpRequest();
      x.open('GET', url, true);
      x.onload = ()=> {
        let r=null; try{ r = JSON.parse(x.responseText||'{}'); }catch{}
        resolve(r);
      };
      x.onerror = ()=> resolve({ok:false, msg:'Error de red'});
      x.send();
    });
  }

  async function cargarOperacionParaEditar(id){
    // Limpieza total (tu helper global ya existe en el create)
    if (typeof window.resetModalFerroOP === 'function') window.resetModalFerroOP();

    // Marcar modo edición
    form.dataset.mode = 'edit';
    if (rowIdHid) rowIdHid.value = String(id);

    // Botón del footer -> Guardar cambios (type=button para no disparar submit de crear)
    btnFooter.type = 'button';
    btnFooter.innerHTML = '<i data-feather="save"></i> Guardar cambios';
    if (!btnFooter.dataset.boundEdit){
      btnFooter.dataset.boundEdit = '1';
      btnFooter.addEventListener('click', onGuardarCambios);
    }
    if (window.feather) feather.replace();

    // Abrir modal
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();

    // Deshabilitar lo que no se edita
    setSoloLecturaHeader(true);
    ocultarAutocomplete();
    toggleSelectorMaritimo(false); // el usuario podrá abrirlo con “Agregar Marítimo”

    // GET al backend
    const url = BASE_URL + ENDPT_GET + '?id=' + encodeURIComponent(id);
    const res = await fetchJSON(url);
    if (!res || res.ok !== true){
      toast(res?.msg || 'No se pudo cargar la operación.', false);
      return;
    }


    const h  = res.header || {};
const ls = Array.isArray(res.lineas) ? res.lineas : [];

// Header (como ya corregiste las llaves)
if (folioInp)       folioInp.value       = h.numero || '';
if (fechaInp)       fechaInp.value       = h.fecha || '';
if (estatusSel)     estatusSel.value     = h.estatus_id ? String(h.estatus_id) : '';
if (comentariosTA)  comentariosTA.value  = h.comentarios || '';

if (ferroIdHid)     ferroIdHid.value     = h.contenedor_fisico_id || '';
if (ferroNomInp)    ferroNomInp.value    = h.numero_ferro || '';

if (transIdHid)     transIdHid.value     = h.transportista_id || '';
if (transNomInp)    transNomInp.value    = h.transportista || '';

if (destIdHid)      destIdHid.value      = h.destino_id || '';
if (destNomInp)     destNomInp.value     = h.destino || '';

// Mapear líneas al formato del carrito
const itemsCarrito = ls.map(it => ({
  cmo_id:           Number(it.cmo_id || 0),
  bultos_asignados: Number(it.bultos_asignados || 0),
  comentario:       it.comentario || null,
  numero_operacion: it.numero_operacion_maritima || it.numero_operacion || '',
  operacion_id:     Number(it.operacion_id || 0),
  numero_contenedor:it.numero_contenedor || '',
  cliente:          it.cliente || ''
}));

// ¡Aquí llenas el carrito REAL que usa tu render!
if (typeof window.setCarritoFerroOP === 'function') {
  window.setCarritoFerroOP(itemsCarrito);
} else {
  // fallback extremo (no debería ser necesario si hiciste el paso 1)
  console.warn('setCarritoFerroOP no está disponible');
}

// En edición, mantenlo visible
toggleSelectorMaritimo(true);

  }

  // Tomar referencia al array "carrito" que declaraste en el archivo de creación.
  // Como está en un IIFE, lo buscamos colgándonos del tbody (hack controlado):
  function getCarritoArray(){
    // Lo expusiste solo dentro de tu IIFE; entonces lo “descubrimos” así:
    // Creamos una propiedad global si ya la dejaste expuesta; si no, la fabricamos.
    // Mejor: guardémosla en window si no existe.
    if (!window.__carritoFerroOP){
      // Si tu IIFE no lo expuso, creamos un espejo mínimo y adaptamos los helpers existentes
      window.__carritoFerroOP = [];
      // Reemplazamos renderCarrito/actualizarTotales para usar __carritoFerroOP si no encuentran la interna
      if (typeof window.renderCarrito !== 'function') {
        window.renderCarrito = function(){
          const tbodySel = document.getElementById('tbodyMaritimosSeleccionados');
          const noMsg    = document.getElementById('noMaritimosMessage');
          const carrito  = window.__carritoFerroOP;
          if (!tbodySel) return;
          tbodySel.innerHTML = '';
          if (!carrito.length){
            if (noMsg) noMsg.style.display = '';
            return;
          }
          if (noMsg) noMsg.style.display = 'none';
          carrito.forEach((it, idx)=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${it.numero_operacion || ''}</td>
              <td>${it.numero_contenedor || ''}</td>
              <td class="text-end">${it.bultos_asignados}</td>
              <td>${it.comentario || ''}</td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" data-idx="${idx}">
                  <i data-feather="trash-2"></i>
                </button>
              </td>`;
            tbodySel.appendChild(tr);
          });
          if (window.feather) feather.replace();
          tbodySel.querySelectorAll('button[data-idx]').forEach(btn=>{
            btn.addEventListener('click', ()=>{
              const i = Number(btn.getAttribute('data-idx'));
              if (!isNaN(i)) { carrito.splice(i,1); window.renderCarrito(); window.actualizarTotales(); }
            });
          });
        };
      }
      if (typeof window.actualizarTotales !== 'function'){
        window.actualizarTotales = function(){
          const carrito = window.__carritoFerroOP;
          const totB = carrito.reduce((a,b)=> a + Number(b.bultos_asignados||0), 0);
          const totM = carrito.length;
          const bB = document.getElementById('totalBultosFerroOP');
          const bM = document.getElementById('totalMaritimosFerroOP');
          if (bB){ bB.textContent = String(totB); bB.style.display = totB>0 ? '' : 'none'; bB.className='badge ' + (totB>0?'bg-success text-white':'bg-secondary text-white'); }
          if (bM){ bM.textContent = String(totM); bM.style.display = totM>0 ? '' : 'none'; bM.className='badge ' + (totM>0?'bg-success text-white':'bg-secondary text-white'); }
        };
      }
    }
    // Si tu IIFE sí creó `carrito` real y lo usa `renderCarrito`, perfecto: no necesitamos nada más.
    // Para efectos del envío, leeremos lo que pinta la UI:
    // Preferimos usar la fuente que tu add/confirm está usando. Usaremos un getter desde la tabla:
    return window.__carritoFerroOP.length ? window.__carritoFerroOP : (typeof getCarritoFromTable === 'function' ? getCarritoFromTable() : window.__carritoFerroOP);
  }

  // Extrae el carrito leyendo la tabla (fallback seguro si no pudimos acceder al array real)
  function getCarritoFromTable(){
    const rows = tbodySel ? Array.from(tbodySel.querySelectorAll('tr')) : [];
    const out = [];
    rows.forEach(tr=>{
      const tds = tr.querySelectorAll('td');
      if (tds.length >= 4){
        out.push({
          numero_operacion:  tds[0].textContent.trim(),
          numero_contenedor: tds[1].textContent.trim(),
          bultos_asignados:  Number((tds[2].textContent||'0').replace(/[^\d.-]/g,''))||0,
          comentario:        tds[3].textContent.trim() || null
          // cmo_id/operacion_id no vienen en la tabla; por eso preferimos el array verdadero.
        });
      }
    });
    return out;
  }

  // ========= Guardar cambios (POST actualizar_operacion) =========
function onGuardarCambios(){
  if (form.dataset.mode !== 'edit') return;

  const foId = Number(rowIdHid?.value || 0);
  if (!foId) return toast('ID de operación no válido.', false);

  // Lee SIEMPRE del carrito real que expusimos
  const carrito = (typeof window.getCarritoFerroOP === 'function')
    ? window.getCarritoFerroOP()
    : [];

  const totalBultos = carrito.reduce((a,b)=> a + Number(b.bultos_asignados||0), 0);
  if (totalBultos <= 0){
    return toast('Debes tener al menos 1 bulto asignado en total.', false);
  }

  const lineas = carrito.map(it => ({
    cmo_id:           Number(it.cmo_id || 0),
    bultos_asignados: Number(it.bultos_asignados || 0),
    comentario:       it.comentario ?? null
  })).filter(it => it.cmo_id > 0 && it.bultos_asignados > 0);

  const fd = new FormData();
  fd.append('operacion_ferro_id', String(foId));
  if (estatusSel)     fd.append('estatus_id_f', estatusSel.value || '');
  if (comentariosTA)  fd.append('comentariosFerroOP', comentariosTA.value || '');
  fd.append('lineas', JSON.stringify(lineas));
    if (estatusSel)   fd.append('estatus_id_f', estatusSel.value || '');
    if (comentariosTA)fd.append('comentariosFerroOP', comentariosTA.value || '');
    fd.append('lineas', JSON.stringify(lineas));

    btnFooter.disabled = true;

    const x = new XMLHttpRequest();
    x.open('POST', BASE_URL + ENDPT_UPDATE, true);
    x.onload = function(){
      btnFooter.disabled = false;
      let r=null; try{ r = JSON.parse(x.responseText||'{}'); }catch{}
      if (!r || r.ok !== true){
        return toast((r && r.msg) ? r.msg : 'No se pudieron guardar los cambios.', false);
      }

      // Recargar la tabla
      if (typeof window.cargarTablaFerroOP === 'function') window.cargarTablaFerroOP();

      // Cerrar y restaurar modal
      const bs = bootstrap.Modal.getOrCreateInstance(modalEl);
      bs.hide();
      toast('Cambios guardados.', true);
    };
    x.onerror = function(){
      btnFooter.disabled = false;
      toast('No se pudo conectar con el servidor.', false);
    };
    x.send(fd);
  }

  // ========= Exponer hook global para el botón de lápiz en la tabla =========
  // Sobrescribimos el placeholder que tenías en ferrosOperacion.js
  window.editarFerroOP = function(idRow){
    if (!idRow || isNaN(Number(idRow))) {
      toast('Identificador inválido.', false);
      return;
    }
    cargarOperacionParaEditar(Number(idRow));
  };

  // ========= Restaurar a modo CREAR al cerrar el modal =========
  modalEl.addEventListener('hidden.bs.modal', function(){
    // Restaurar botón footer
    btnFooter.type = btnFooterOriginal.type;
    btnFooter.innerHTML = btnFooterOriginal.text;
    btnFooter.disabled = false;

    // Modo
    delete form.dataset.mode;
    if (rowIdHid) rowIdHid.value = '';

    // Rehabilitar inputs de header para crear
    [fechaInp, ferroNomInp, transNomInp, destNomInp].forEach(inp=>{
      if (!inp) return;
      inp.readOnly = false; inp.disabled = false;
    });

    // Limpieza integral del modal (ya tienes el helper)
    if (typeof window.resetModalFerroOP === 'function') window.resetModalFerroOP();

    if (window.feather) feather.replace();
  });

})();
