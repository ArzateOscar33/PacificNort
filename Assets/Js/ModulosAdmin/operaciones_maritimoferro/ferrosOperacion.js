
// ===============================
// Ferros en Operación (LISTAR)
// Archivo: assets/js/modulosAdmin/ferrosOperacion.js
// ===============================
(function () {
  "use strict";

  // --------- Refs DOM (con sufijo FerroOP) ---------
  const buscarFerroOP = document.getElementById("buscarFerroOP");
  const fechaDesdeFerroOP = document.getElementById("fechaDesdeFerroOP");
  const fechaHastaFerroOP = document.getElementById("fechaHastaFerroOP");
  const perPageSelFerroOP = document.getElementById("perPageFerroOP");
  const form = document.getElementById("formFerroOP");
  const tbodyFerroOP = document.getElementById("tbodyFerroOP");
  const paginacionFerroOP = document.getElementById("paginacionFerroOP");
  const metaResumenFerroOP = document.getElementById("metaResumenFerroOP");

  const btnExcelFerroOP = document.getElementById("btnExcelFerroOP");
  const btnPdfFerroOP = document.getElementById("btnPdfFerroOP");

  // Modal (para futuro guardar/editar; hoy solo listar)
  const rowIdFerroOP = document.getElementById("rowIdFerroOP");
  const operacionIdFerroOP = document.getElementById("operacionIdFerroOP");
  const operacionNombreFerroOP = document.getElementById("operacionNombreFerroOP");
  const clienteIdFerroOP = document.getElementById("clienteIdFerroOP");
  const clienteNombreFerroOP = document.getElementById("clienteNombreFerroOP");
  const contenedorMaritimoIdFerroOP = document.getElementById("contenedorMaritimoIdFerroOP");
  const contenedorMaritimoNombreFerroOP = document.getElementById("contenedorMaritimoNombreFerroOP");
  const bultosMaritimoFerroOP = document.getElementById("bultosMaritimoFerroOP");
  const bultosRestantesFerroOP = document.getElementById("bultosRestantesFerroOP");
  const contenedorFerroIdFerroOP = document.getElementById("contenedorFerroIdFerroOP");
  const contenedorFerroNombreFerroOP = document.getElementById("contenedorFerroNombreFerroOP");
  const bultosAsignadosFerroOP = document.getElementById("bultosAsignadosFerroOP");
  const badgeSaldoFerroOP = document.getElementById("badgeSaldoFerroOP");
  const comentariosFerroOP = document.getElementById("comentariosFerroOP");

  // --------- Estado ---------
  let currentPageFerroOP = 1;
  let currentXHRFerroOP = null;
  let debounceIdFerroOP = null;

  // --------- Utils ---------
  function debounceFerroOP(fn, wait = 350) {
    if (debounceIdFerroOP) clearTimeout(debounceIdFerroOP);
    debounceIdFerroOP = setTimeout(fn, wait);
  }

  function setLoadingFerroOP(isLoading) {
    if (isLoading) {
      tbodyFerroOP.innerHTML = `
        <tr>
          <td colspan="10" class="text-center text-muted py-4">
            Cargando…
          </td>
        </tr>`;
    }
  }

  function safeTextFerroOP(v) { return v == null ? "" : String(v); }

  // --------- Render ---------
  function renderRowsFerroOP(rows) {
    if (!rows || rows.length === 0) {
      tbodyFerroOP.innerHTML = `
      <tr>
        <td colspan="10" class="text-center text-muted py-4">
          Sin resultados.
        </td>
      </tr>`;
      feather.replace();
      return;
    }

    const html = rows.map((r) => {
      const idRow = Number(r.id_row ?? r.id ?? 0);
      return `
        <tr>
          <td>${safeTextFerroOP(r.numero_operacion)}</td>
          <td>${safeTextFerroOP(r.cliente)}</td>
          <td>${safeTextFerroOP(r.ferro)}</td>
          <td>${safeTextFerroOP(r.contenedores_maritimos ?? r.contenedor_maritimo)}</td>
          <td class="text-end">${safeTextFerroOP(r.bultos_maritimo)}</td>
          <td>${safeTextFerroOP(r.transportista)}</td>
          <td>${safeTextFerroOP(r.destino)}</td>
          <td>${safeTextFerroOP(r.fecha_header || '')}</td>
          <td>${safeTextFerroOP(r.estatus || '')}</td>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-primary" onclick="editarFerroOP(${idRow})" title="Editar">
                <i data-feather="edit-2"></i>
              </button> 
            </div>
          </td>
        </tr>`;
    }).join("");

    tbodyFerroOP.innerHTML = html;
    feather.replace();
  }

  function renderMetaFerroOP(from, to, total) {
    metaResumenFerroOP.textContent = `Mostrando ${from}-${to} de ${total}`;
  }

  function bindPaginationFerroOP(ul) {
    ul.addEventListener(
      "click",
      function (e) {
        const a = e.target.closest("a.page-link");
        if (!a) return;
        e.preventDefault();
        const page = parseInt(a.getAttribute("data-page"), 10);
        if (!isNaN(page) && page !== currentPageFerroOP) {
          currentPageFerroOP = page;
          cargarTablaFerroOP();
        }
      },
      { once: true }
    );
  }

  // --------- Cargar tabla ---------
  function cargarTablaFerroOP() {
    if (currentXHRFerroOP && currentXHRFerroOP.readyState !== 4) {
      currentXHRFerroOP.abort();
    }

    const params = new URLSearchParams({
      q: (buscarFerroOP?.value || "").trim(),
      desde: fechaDesdeFerroOP?.value || "",
      hasta: fechaHastaFerroOP?.value || "",
      perPage: perPageSelFerroOP?.value || "10",
      page: String(currentPageFerroOP),
    });

    currentXHRFerroOP = new XMLHttpRequest();
    currentXHRFerroOP.open(
      "GET",
      BASE_URL + "Operaciones_maritimo_ferro_contenedores/listar?" + params.toString(),
      true
    );

    setLoadingFerroOP(true);

    currentXHRFerroOP.onload = function () {
      let res = null;
      try { res = JSON.parse(currentXHRFerroOP.responseText); } catch (_) { res = null; }
      if (!res || !Array.isArray(res.data)) {
        tbodyFerroOP.innerHTML = `
          <tr><td colspan="10" class="text-center text-danger">Error al cargar datos.</td></tr>`;
        renderMetaFerroOP(0, 0, 0);
        paginacionFerroOP.innerHTML = "";
        return;
      }
      renderRowsFerroOP(res.data);
      renderMetaFerroOP(res.from || 0, res.to || 0, res.total || 0);
      paginacionFerroOP.innerHTML = res.pagination_html || "";
      bindPaginationFerroOP(paginacionFerroOP);
    };

    currentXHRFerroOP.onerror = function () {
      tbodyFerroOP.innerHTML = `
        <tr><td colspan="10" class="text-center text-danger">No se pudo conectar con el servidor.</td></tr>`;
      renderMetaFerroOP(0, 0, 0);
      paginacionFerroOP.innerHTML = "";
    };

    currentXHRFerroOP.send();
  }

  // --------- Listeners tabla ---------
  buscarFerroOP?.addEventListener("input", function () {
    currentPageFerroOP = 1; debounceFerroOP(cargarTablaFerroOP, 350);
  });
  fechaDesdeFerroOP?.addEventListener("change", function () { currentPageFerroOP = 1; cargarTablaFerroOP(); });
  fechaHastaFerroOP?.addEventListener("change", function () { currentPageFerroOP = 1; cargarTablaFerroOP(); });
  perPageSelFerroOP?.addEventListener("change", function () { currentPageFerroOP = 1; cargarTablaFerroOP(); });

  // Hooks placeholder (editar/eliminar)
  // Hooks placeholder (editar/eliminar)
window.editarFerroOP = function (idRow) {
  const form = document.getElementById('formFerroOP');
  if (!form) return;

  // 1) Modo edición
  form.dataset.mode = 'edit';

  // 2) Limpiar visuales mínimos de selector (opcional, según tu flujo)
  //    No resetees todo con resetModalFerroOP() porque eso te volvería a "create".
  //    Si quieres ocultar el selector aquí, puedes:
  const selectorMar = document.getElementById('selectorMaritimoFerroOP');
  const btnAgregar  = document.getElementById('btnAgregarMaritimoFerroOP');
  if (selectorMar) selectorMar.classList.remove('d-none');
  if (btnAgregar)  btnAgregar.classList.add('d-none');

  // 3) Traer datos del registro para llenar el modal
  const x = new XMLHttpRequest();
  x.open('GET', BASE_URL + 'Operaciones_maritimo_ferro_contenedores/obtener?id=' + encodeURIComponent(idRow), true);
  x.onload = function(){
    if (x.status !== 200) { console.error('No se pudo cargar el detalle'); return; }
    let res = {};
    try { res = JSON.parse(x.responseText || '{}'); } catch(_) {}

    if (!res || res.ok !== true || !res.data) {
      console.error('Respuesta inválida al obtener detalle');
      return;
    }

    const d = res.data;

    // ----- Campos del HEADER -----
    // Número FO existente (NO debe cambiar en edición)
    const inpNumeroFO = document.getElementById('operacionNombreFerroOP');
    if (inpNumeroFO && d.numero_operacion_ferro) {
      inpNumeroFO.value = d.numero_operacion_ferro;  // p.ej. "FO-12"
    }

    // Otros campos típicos del header (ajusta nombres según tu payload):
    const fechaFerroOP = document.getElementById('fechaFerroOP');
    if (fechaFerroOP && d.fecha_header) fechaFerroOP.value = d.fecha_header;

    const contFerroId  = document.getElementById('contenedorFerroIdFerroOP');
    const contFerroNom = document.getElementById('contenedorFerroNombreFerroOP');
    if (contFerroId)  contFerroId.value  = d.ferro_id || '';
    if (contFerroNom) contFerroNom.value = d.ferro || ''; // "FX-001" por ejemplo

    const transportistaId = document.getElementById('transportistaIdFerroOP');
    const transportistaNom= document.getElementById('transportistaNombreFerroOP');
    if (transportistaId)  transportistaId.value  = d.transportista_id || '';
    if (transportistaNom) transportistaNom.value = d.transportista || '';

    const destinoId = document.getElementById('destinoIdFerroOP');
    const destinoNom= document.getElementById('destinoNombreFerroOP');
    if (destinoId)  destinoId.value  = d.destino_id || '';
    if (destinoNom) destinoNom.value = d.destino || '';

    // Si tu vista de edición también muestra líneas/“carrito”, aquí puedes setearlo:
    if (Array.isArray(d.lineas) && typeof window.setCarritoFerroOP === 'function') {
      // cada línea debería traer: cmo_id, bultos_asignados, numero_operacion, numero_contenedor, etc.
      window.setCarritoFerroOP(d.lineas);
    }

    // 4) Abrir modal (IMPORTANTE: después de setear data-mode="edit")
    const modalEl = document.getElementById('modalFerroOP');
    if (modalEl && window.bootstrap?.Modal) {
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
  };
  x.onerror = function(){ console.error('Error de red al obtener detalle'); };
  x.send();
};

  window.eliminarFerroOP = function (idRow) { console.log("eliminarFerroOP", idRow); };

  // === LIMITADOR EN TIEMPO REAL DEL SALDO ===
  bultosAsignadosFerroOP?.setAttribute('min','1');
  bultosAsignadosFerroOP?.setAttribute('step','1');
  bultosAsignadosFerroOP?.addEventListener("input", function () {
    const rest = Number(bultosRestantesFerroOP?.value || 0);
    let asig  = parseInt((bultosAsignadosFerroOP?.value || '').trim(), 10);
    if (!Number.isFinite(asig) || asig < 0) asig = 0;
    if (asig > rest) {
      asig = rest; bultosAsignadosFerroOP.value = String(rest);
    }
    const saldo = rest - asig;
    if (badgeSaldoFerroOP) {
      badgeSaldoFerroOP.textContent = `Saldo: ${saldo}`;
      badgeSaldoFerroOP.className = "badge " + (saldo < 0 ? "bg-danger text-white" : "bg-success text-white");
    }
    const btn = form?.querySelector('button[type="submit"]');
    if (btn) btn.disabled = asig <= 0 || saldo < 0;
  });

  // --------- Init tabla ---------
  cargarTablaFerroOP();
  window.cargarTablaFerroOP = cargarTablaFerroOP;

})();

// ===============================
// REGISTRAR ASIGNACIÓN MG→FX (único submit aquí)
// ===============================
(function(){
  "use strict";

  const form = document.getElementById("formFerroOP");
  if (!form) return;

  const transportistaIdFerroOP = document.getElementById("transportistaIdFerroOP");
  const destinoIdFerroOP       = document.getElementById("destinoIdFerroOP");
  const bultosAsignadosFerroOP = document.getElementById("bultosAsignadosFerroOP");
  const bultosRestantesFerroOP = document.getElementById("bultosRestantesFerroOP");
  const badgeSaldoFerroOP      = document.getElementById("badgeSaldoFerroOP");

  // Selector (multiple)
  const tbodySel   = document.getElementById('tbodyMaritimosSeleccionados');
  const noMsg      = document.getElementById('noMaritimosMessage');
  const asigHidden = document.getElementById('asignacionesHidden');

  const opMarInp   = document.getElementById('operacionMaritimaNombreFerroOP');
  const opMarIdHid = document.getElementById('operacionMaritimaIdFerroOP');
  const cmoIdHid   = document.getElementById('contMaritimoOperacionIdFerroOP');
  const contNameInp= document.getElementById('contenedorMaritimoNombreFerroOP');
  const restInp    = document.getElementById('bultosRestantesFerroOP');
  //const asigInp    = document.getElementById('bultosAsignadosFerroOP');
  const comentarioLineaInp = document.getElementById('comentarioLineaFerroOP');

  let carrito = [];  

  function toast(msg, ok = true) {
    if (window.Swal) {
      Swal.fire({ icon: ok ? "success" : "error", title: ok ? "Listo" : "Aviso", text: msg, timer: 1800, showConfirmButton: false });
    } else { alert(msg); }
  }
  // Dentro del mismo IIFE donde declaras let carrito = [];
window.getCarritoFerroOP = function(){ return carrito; };
window.setCarritoFerroOP = function(items){
  carrito.length = 0;
  (items || []).forEach(it => carrito.push(it));
  if (typeof renderCarrito === 'function') renderCarrito();
  if (typeof actualizarTotales === 'function') actualizarTotales();
};


  function renderCarrito(){
        console.log('=== RENDER CARRITO ===');
    console.log('carrito.length:', carrito.length);
    console.log('carrito:', carrito);
    
    if (!tbodySel) {
        console.log('tbodySel no encontrado');
        return;
    }
    tbodySel.innerHTML = '';
    if (!carrito.length){ if (noMsg) noMsg.style.display = ''; return; }
    if (noMsg) noMsg.style.display = 'none';

    carrito.forEach((it, idx)=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${it.numero_operacion || ''}</td>
        <td>${it.numero_contenedor || ''}</td>
        <td class="text-end">${it.bultos_asignados}</td>
        
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger" data-idx="${idx}">
            <i data-feather="trash-2"></i>
          </button>
        </td>`;
      tbodySel.appendChild(tr);
    });
    feather.replace();

    tbodySel.querySelectorAll('button[data-idx]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const i = Number(btn.getAttribute('data-idx'));
        if (!isNaN(i)) { carrito.splice(i,1); renderCarrito(); actualizarTotales(); }
      });
    });
  }

  function actualizarTotales(){
    const totBultos = carrito.reduce((a,b)=> a + Number(b.bultos_asignados||0), 0);
    const totMar    = carrito.length;
    const badgeTotB = document.getElementById('totalBultosFerroOP');
    const badgeTotM = document.getElementById('totalMaritimosFerroOP');
    if (badgeTotB) { badgeTotB.textContent = String(totBultos); badgeTotB.style.display = totBultos>0 ? '' : 'none'; badgeTotB.className = 'badge ' + (totBultos>0 ? 'bg-success text-white' : 'bg-secondary text-white'); }
    if (badgeTotM) { badgeTotM.textContent = String(totMar);    badgeTotM.style.display = totMar>0 ? '' : 'none';    badgeTotM.className = 'badge ' + (totMar>0 ? 'bg-success text-white' : 'bg-secondary text-white'); }
  }
  function limpiarLinea() {

  const opInp        = document.getElementById('operacionMaritimaNombreFerroOP');
  const opIdHid      = document.getElementById('operacionMaritimaIdFerroOP');
  const cmoIdHid     = document.getElementById('contMaritimoOperacionIdFerroOP');
  const contIdHid    = document.getElementById('contenedorMaritimoIdFerroOP');
  const contNameInp  = document.getElementById('contenedorMaritimoNombreFerroOP');
  const cliNameInp   = document.getElementById('clienteNombreMaritimoFerroOP');
  const bultosTotInp = document.getElementById('bultosMaritimoFerroOP');
  const restInp      = document.getElementById('bultosRestantesFerroOP');
  const asigInp      = document.getElementById('bultosAsignadosFerroOP');
  const comentInp    = document.getElementById('comentarioLineaFerroOP');
  const sugBox       = document.getElementById('sugOperacionesMaritimasFerroOP');
  const badgeSaldo   = document.getElementById('badgeSaldoFerroOP');

  
  if (opIdHid)  opIdHid.value = '';
  if (cmoIdHid) cmoIdHid.value = '';
  if (contIdHid) contIdHid.value = '';
  if (contNameInp) contNameInp.value = '';
  if (cliNameInp)  cliNameInp.value = '';
  if (bultosTotInp) bultosTotInp.value = '';
  if (restInp)      restInp.value = '';
  if (asigInp)      asigInp.value = '';
  if (comentInp)    comentInp.value = '';

  // Estado visual
  if (badgeSaldo) { badgeSaldo.textContent = 'Saldo: 0'; badgeSaldo.className = 'badge bg-secondary text-white'; }
  if (opInp)      { opInp.dataset.lastPick = '0'; } // marcar que no hay pick activo

  // Ocultar sugerencias si quedaron abiertas
  if (sugBox) { sugBox.style.display = 'none'; sugBox.innerHTML = ''; }

  // Recalcular habilitado del botón confirmar
  if (typeof window.toggleAsignBtn === 'function') window.toggleAsignBtn();

  // Dejar el foco listo para escribir el siguiente contenedor
  if (opInp) { opInp.focus(); opInp.select(); }
}

// --- Toggle del bloque "Agregar Marítimo" ---
const selectorMar = document.getElementById('selectorMaritimoFerroOP');
const btnAgregar  = document.getElementById('btnAgregarMaritimoFerroOP');
const btnCancel   = document.getElementById('btnCancelarMaritimoFerroOP');

// Mostrar/ocultar bloque y botón Agregar (sin crear nuevos ids)
function toggleSelectorMaritimo(show){
  if (selectorMar){
    selectorMar.classList.toggle('d-none', !show);
    // elimina cualquier inline style viejo que pueda quedar
    selectorMar.style.removeProperty('display');
  }
  if (btnAgregar){
    // el botón “Añadir” debe ocultarse cuando el selector está visible
    btnAgregar.classList.toggle('d-none', !!show);
    btnAgregar.style.removeProperty('display');
  }
  // habilita/deshabilita Confirmar acorde al estado
  const btnConf = document.getElementById('btnConfirmarMaritimoFerroOP');
  if (btnConf) btnConf.disabled = !show;

  // al abrir, enfoca el input
  if (show) {
    const opInp = document.getElementById('operacionMaritimaNombreFerroOP');
    setTimeout(()=>{ opInp?.focus(); opInp?.select(); }, 0);
  }
}


// Al abrir modal: dejar el selector oculto y el botón Agregar visible
(function initToggleEnModal(){
  const modal = document.getElementById('modalFerroOP');
  if (!modal) return;
  modal.addEventListener('shown.bs.modal', ()=> {
    const form = document.getElementById('formFerroOP');
    const enEdicion = form && form.dataset.mode === 'edit';
    // crear -> oculto; editar -> visible
    toggleSelectorMaritimo(!!enEdicion);
  });
})();



btnAgregar?.addEventListener('click', ()=> { toggleSelectorMaritimo(true); });
btnCancel?.addEventListener('click', ()=> {
  if (typeof limpiarLinea === 'function') limpiarLinea();
  toggleSelectorMaritimo(false);
});


 
  // Botón confirmar del selector (agrega línea al carrito)
// 1) Evitar doble binding del botón confirmar
const btnConfirmar = document.getElementById('btnConfirmarMaritimoFerroOP');
if (btnConfirmar && !btnConfirmar.dataset.bound) {
  btnConfirmar.dataset.bound = '1';
  btnConfirmar.addEventListener('click', function(){
    if (opMarInp?.dataset.lastPick !== '1') return toast('Busca y elige una operación de la lista.', false);

    const cmoId   = Number(cmoIdHid?.value || 0);
    const rest    = Number(restInp?.value || 0);
    const rawAsig = (bultosAsignadosFerroOP?.value ?? '').toString();
    const asign   = Math.trunc(Number(rawAsig.replace(/[^\d.-]/g, '')));

    if (!(asign > 0)) return toast('Bultos a asignar debe ser > 0.', false);
    if (asign > rest) return toast(`No hay saldo suficiente. Disponible: ${rest}.`, false);
    if (!cmoId)       return toast('Selecciona una operación/CMO válido.', false);

    // 2) DEFINIR variables usadas en el push
    const coment   = (comentarioLineaInp?.value || '').trim() || null;
    const opNumero = opMarInp?.value || '';
    const opId     = Number(opMarIdHid?.value || 0);
    const contNm   = contNameInp?.value || '';
    const cliente  = document.getElementById('clienteNombreMaritimoFerroOP')?.value || '';

    const ix = carrito.findIndex(x => Number(x.cmo_id) === cmoId);
    if (ix >= 0) carrito[ix].bultos_asignados = Number(carrito[ix].bultos_asignados) + asign;
    else carrito.push({ cmo_id: cmoId, bultos_asignados: asign, comentario: coment, numero_operacion: opNumero, operacion_id: opId, numero_contenedor: contNm, cliente });

    renderCarrito();
    actualizarTotales();
    bultosAsignadosFerroOP.value = '';
      // Limpia la línea para capturar el siguiente contenedor
    limpiarLinea();
  });
}


  function setBadgeSaldo(val){
    if (!badgeSaldoFerroOP) return;
    const v = Number(val || 0);
    badgeSaldoFerroOP.textContent = `Saldo: ${v}`;
    badgeSaldoFerroOP.className = 'badge ' + (v < 0 ? 'bg-danger text-white' : 'bg-success text-white');
  }

  function onSubmitFerro(e){
    e.preventDefault();

        e.preventDefault();
    
    // LOGS DE DEBUG - AGREGAR ESTAS LÍNEAS
    console.log('=== DEBUG SUBMIT ===');
    console.log('carrito:', carrito);
    console.log('carrito.length:', carrito.length);
    
    const wasMultiple = (carrito.length > 0);
    console.log('wasMultiple:', wasMultiple);
    
    if (wasMultiple) {
        const totalBultos = carrito.reduce((sum, item) => sum + Number(item.bultos_asignados || 0), 0);
        console.log('totalBultos calculado:', totalBultos);
        console.log('Items en carrito:');
        carrito.forEach((item, idx) => {
            console.log(`  [${idx}]:`, item);
            console.log(`  bultos_asignados:`, item.bultos_asignados, typeof item.bultos_asignados);
        });
    }
    console.log('===================');
    const btn = form.querySelector('button[type="submit"]');

    // Validación del header (común para ambos flujos)
    const fecha = (document.getElementById('fechaFerroOP')?.value || '').trim();
    const fxId  = Number(document.getElementById('contenedorFerroIdFerroOP')?.value || 0);
    const fxNm  = (document.getElementById('contenedorFerroNombreFerroOP')?.value || '').trim();
    const trans = Number(transportistaIdFerroOP?.value || 0);
    const dest  = Number(destinoIdFerroOP?.value || 0);

    if (!fecha) return toast('La fecha es requerida.', false);
    if (!fxId && !fxNm) return toast('Selecciona la caja/ferro o escribe el número.', false);
    if (!trans) return toast('Selecciona un transportista.', false);
    if (!dest)  return toast('Selecciona un destino.', false);

    

    if (!wasMultiple) {
        // FLUJO INDIVIDUAL: validar campos individuales
        const cmoIdLocal = Number(document.getElementById('contMaritimoOperacionIdFerroOP')?.value || 0);
        const asigLocal  = parseInt((bultosAsignadosFerroOP?.value || '').trim(), 10);
        const restLocal  = Number(bultosRestantesFerroOP?.value || 0);

        if (!cmoIdLocal) return toast('Selecciona una operación/CMO válido.', false);
        if (!Number.isFinite(asigLocal) || asigLocal <= 0) return toast('Los bultos asignados deben ser > 0.', false);
        if (asigLocal > restLocal) return toast(`No hay saldo suficiente. Disponible: ${restLocal}.`, false);
        
    } else {
        // FLUJO MÚLTIPLE: validar carrito
        if (carrito.length === 0) {
            return toast('Agrega al menos un contenedor marítimo al carrito.', false);
        }
        
        const totalBultos = carrito.reduce((sum, item) => sum + Number(item.bultos_asignados || 0), 0);
        if (totalBultos <= 0) {
            return toast('El total de bultos en el carrito debe ser > 0.', false);
        }
        
        // Serializar carrito para envío
        if (asigHidden) {
            asigHidden.value = JSON.stringify(carrito);
        }
    }

    // Función para realizar el POST
    function postAsignacion(){
        const fd = new FormData(form);
        if (btn) btn.disabled = true;
        
        const x = new XMLHttpRequest();
        x.open('POST', BASE_URL + 'Operaciones_maritimo_ferro_contenedores/guardar_asignacion', true);
        
x.onload = function(){
  if (btn) btn.disabled = false;
  let res = null;
  try { res = JSON.parse(x.responseText||'{}'); } catch(e){}

  if (!res || res.ok !== true){
    const errorMsg = (res && res.msg) ? res.msg : 'No se pudo registrar la asignación.';
    return toast(errorMsg, false);
  }

  // NO limpies aquí nada. Solo recarga tabla y cierra modal.
  if (typeof window.cargarTablaFerroOP === 'function') window.cargarTablaFerroOP();

  const modalEl = document.getElementById('modalFerroOP');
  if (modalEl && window.bootstrap?.Modal){
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
  }

  // Éxito
  const folioFx = res.data?.numero_operacion_ferro || '';
  toast(folioFx ? `Asignación registrada (${folioFx}).` : 'Asignación registrada.', true);
};

        
        x.onerror = function(){
            if (btn) btn.disabled = false;
            toast('No se pudo conectar con el servidor.', false);
        };
        
        x.send(fd);
    }

    // Crear ferro al vuelo si solo hay nombre
    const fxIdInput = document.getElementById('contenedorFerroIdFerroOP');
    const fxNmInput = document.getElementById('contenedorFerroNombreFerroOP');

    if (!fxId && fxNm !== ""){
        if (btn) btn.disabled = true;
        
        const fdMk = new FormData(); 
        fdMk.append('numero_ferro', fxNm);
        
        const xMk = new XMLHttpRequest();
        xMk.open('POST', BASE_URL + 'Operaciones_maritimo_ferro_contenedores/crear_ferro', true);
        
        xMk.onload = function(){
            let r = null; 
            try { 
                r = JSON.parse(xMk.responseText || '{}'); 
            } catch(e) {
                console.error('Error creating ferro:', e);
            }
            
            if (!r || r.ok !== true || !r.id){ 
                if (btn) btn.disabled = false; 
                const errorMsg = (r && r.msg) ? r.msg : 'No se pudo crear la caja/ferro.';
                return toast(errorMsg, false); 
            }
            
            if (fxIdInput) fxIdInput.value = String(r.id);
            if (fxNmInput && r.label) fxNmInput.value = r.label;
            
            postAsignacion();
        };
        
        xMk.onerror = function(){ 
            if (btn) btn.disabled = false; 
            toast('No se pudo conectar para crear la caja/ferro.', false); 
        };
        
        xMk.send(fdMk);
        return;
    }

    // Guardar directo
    postAsignacion();
}

  // === ÚNICO registro de submit ===
  if (!form.dataset.submitBound){
    form.dataset.submitBound = '1';
    form.addEventListener('submit', onSubmitFerro);
  }

  // Exponer para que el autocomplete pueda habilitar botón confirmar
  function toggleBtn(){
    const asign = parseInt((bultosAsignadosFerroOP?.value || '').trim(), 10);
    const rest  = Number(bultosRestantesFerroOP?.value || 0);
    const opMarInp = document.getElementById('operacionMaritimaNombreFerroOP');
    const picked = opMarInp?.dataset.lastPick === '1';
    const btn = document.getElementById('btnConfirmarMaritimoFerroOP');
    if (btn) btn.disabled = !(Number.isFinite(asign) && asign > 0 && asign <= rest && picked);
  }
  window.toggleAsignBtn = toggleBtn;
  ['input','change'].forEach(ev => {
    bultosAsignadosFerroOP?.addEventListener(ev, toggleBtn);
    bultosRestantesFerroOP?.addEventListener(ev, toggleBtn);
    document.getElementById('operacionMaritimaNombreFerroOP')?.addEventListener(ev, toggleBtn);
  });
  toggleBtn();



// === Reset integral del modal Ferro OP ===
// Debe ir dentro del IIFE donde está "carrito"
window.resetModalFerroOP = function resetModalFerroOP(){
  const form = document.getElementById('formFerroOP');
  const selectorMar = document.getElementById('selectorMaritimoFerroOP');
  const btnAgregar  = document.getElementById('btnAgregarMaritimoFerroOP');
  const btnConfirm  = document.getElementById('btnConfirmarMaritimoFerroOP');

  // 1) Vaciar carrito y sus visuales
  if (typeof carrito !== 'undefined') {
    carrito.length = 0;
    if (typeof renderCarrito === 'function') renderCarrito();
    if (typeof actualizarTotales === 'function') actualizarTotales();
  }

  // 2) Limpiar SOLO la línea marítima (tu helper actual)
  if (typeof limpiarLinea === 'function') limpiarLinea();

  // 3) Ocultar selector (solo d-none) y mostrar botón “Añadir”
  if (selectorMar){
    selectorMar.classList.add('d-none');
    selectorMar.style.removeProperty('display');
  }
  if (btnAgregar){
    btnAgregar.classList.remove('d-none');
    btnAgregar.style.removeProperty('display');
  }
  if (btnConfirm) btnConfirm.disabled = true;


// 4) Limpiar campos generales del form
if (form) {
  form.reset();
  form.dataset.mode = 'create';   // <- AÑADE ESTO para que la próxima apertura sea "crear"
}


  // 5) Reponer la fila "noMaritimosMessage" en la tabla
  const tbodySel = document.getElementById('tbodyMaritimosSeleccionados');
  if (tbodySel) {
    tbodySel.innerHTML = `
      <tr id="noMaritimosMessage">
        <td colspan="5" class="text-center text-muted">
          <i data-feather="package" class="me-2"></i>
          No hay contenedores marítimos agregados
        </td>
      </tr>`;
  }

  // 6) Ocultar y vaciar cajas de sugerencias
  ['sugOperacionesMaritimasFerroOP','sugFerrosFerroOP','sugTransportistasFerroOP','destinoFerroOP']
    .forEach(id => { const box = document.getElementById(id); if (box){ box.style.display='none'; box.innerHTML=''; } });

  // 7) Badges y estados
  const badgeB = document.getElementById('totalBultosFerroOP');
  const badgeM = document.getElementById('totalMaritimosFerroOP');
  const badgeSaldo = document.getElementById('badgeSaldoFerroOP');
  if (badgeB){ badgeB.textContent = '0'; badgeB.className = 'badge bg-secondary text-white'; }
  if (badgeM){ badgeM.textContent = '0'; badgeM.className = 'badge bg-secondary text-white'; }
  if (badgeSaldo){ badgeSaldo.textContent = 'Saldo: 0'; badgeSaldo.className = 'badge bg-secondary text-white'; }

  // 8) Marcar que no hay pick activo
  const opInp = document.getElementById('operacionMaritimaNombreFerroOP');
  if (opInp) opInp.dataset.lastPick = '0';

  // 9) Re-render de íconos
  if (window.feather) feather.replace();
};


})();

// ===============================
// Exportaciones
// ===============================
document.getElementById("btnExcelFerroOP")?.addEventListener("click", () => {
  ExportarTablas.exportar({
    ref: "tablaFerroOP",
    formato: "xlsx",
    nombre: "FerrosEnOperacion.xlsx",
    columnasOcultas: [6],
    soloVisibles: true,
    sheetName: "Contenedores En Operacion",
  });
});

document.getElementById("btnPdfFerroOP")?.addEventListener("click", () => {
  ExportarTablas.exportar({
    ref: "#tablaFerroOP",
    formato: "pdf",
    nombre: "FerrosEnOperacion.pdf",
    titulo: "Ferros En Operacion",
    orientacion: "landscape",
    formatoPagina: "letter",
    columnasOcultas: [6],
    soloVisibles: true,
  });
});
 
