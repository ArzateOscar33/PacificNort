 
// ===============================
// Ferros en Operación (LISTAR)
// Archivo sugerido: assets/js/modulosAdmin/ferros_en_operacion.js
// ===============================
(function(){
  "use strict";

  // --------- Refs DOM (con sufijo FerroOP) ---------
  const buscarFerroOP      = document.getElementById('buscarFerroOP');
  const fechaDesdeFerroOP  = document.getElementById('fechaDesdeFerroOP');
  const fechaHastaFerroOP  = document.getElementById('fechaHastaFerroOP');
  const perPageSelFerroOP  = document.getElementById('perPageFerroOP');

  const tbodyFerroOP       = document.getElementById('tbodyFerroOP');
  const paginacionFerroOP  = document.getElementById('paginacionFerroOP');
  const metaResumenFerroOP = document.getElementById('metaResumenFerroOP');

  const btnExcelFerroOP    = document.getElementById('btnExcelFerroOP');
  const btnPdfFerroOP      = document.getElementById('btnPdfFerroOP');

  // Modal (para futuro guardar/editar; hoy solo listar)
  const formFerroOP                 = document.getElementById('formFerroOP');
  const rowIdFerroOP                = document.getElementById('rowIdFerroOP');
  const operacionIdFerroOP          = document.getElementById('operacionIdFerroOP');
  const operacionNombreFerroOP      = document.getElementById('operacionNombreFerroOP');
  const clienteIdFerroOP            = document.getElementById('clienteIdFerroOP');
  const clienteNombreFerroOP        = document.getElementById('clienteNombreFerroOP');
  const contenedorMaritimoIdFerroOP = document.getElementById('contenedorMaritimoIdFerroOP');
  const contenedorMaritimoNombreFerroOP = document.getElementById('contenedorMaritimoNombreFerroOP');
  const bultosMaritimoFerroOP       = document.getElementById('bultosMaritimoFerroOP');
  const bultosRestantesFerroOP      = document.getElementById('bultosRestantesFerroOP');
  const contenedorFerroIdFerroOP    = document.getElementById('contenedorFerroIdFerroOP');
  const contenedorFerroNombreFerroOP= document.getElementById('contenedorFerroNombreFerroOP');
  const bultosAsignadosFerroOP      = document.getElementById('bultosAsignadosFerroOP');
  const badgeSaldoFerroOP           = document.getElementById('badgeSaldoFerroOP');
  const comentariosFerroOP          = document.getElementById('comentariosFerroOP');

  // --------- Estado ---------
  let currentPageFerroOP = 1;
  let currentXHRFerroOP  = null;
  let debounceIdFerroOP  = null;

  // --------- Utils ---------
  function debounceFerroOP(fn, wait=350){
    if (debounceIdFerroOP) clearTimeout(debounceIdFerroOP);
    debounceIdFerroOP = setTimeout(fn, wait);
  }

  function setLoadingFerroOP(isLoading){
    if (isLoading) {
      tbodyFerroOP.innerHTML = `
        <tr>
          <td colspan="9" class="text-center text-muted py-4">
            Cargando…
          </td>
        </tr>`;
    }
  }

  function safeTextFerroOP(v){ return (v === null || v === undefined) ? '' : String(v); }
  function safeIntFerroOP(v){ return (v === null || v === undefined || v === '') ? '' : Number(v); }

  // --------- Render ---------
function renderRowsFerroOP(rows){
  if (!rows || rows.length === 0) {
    tbodyFerroOP.innerHTML = `
      <tr>
        <td colspan="9" class="text-center text-muted py-4">
          Sin resultados.
        </td>
      </tr>`;
    feather.replace();
    return;
  }

  const html = rows.map(r => {
    // el modelo ahora devuelve estos nombres
    const idRow            = Number(r.id_row ?? r.id ?? 0);
    const numeroOperacion  = (r.numero_operacion ?? '');
    const contMaritimos    = (r.contenedores_maritimos ?? r.contenedor_maritimo ?? '');
    const bultosMaritimo   = (r.bultos_maritimo ?? '');
    const cliente          = (r.cliente ?? '');
    const transportista    = (r.transportista ?? '');   
    const ferro            = (r.ferro ?? '');
    const divisionBultos   = (r.division_bultos ?? ''); 
    const destino          = (r.destino ?? '');         

    return `
      <tr>
        <td>${numeroOperacion}</td>
        <td>${contMaritimos}</td>
        <td class="text-end">${bultosMaritimo}</td>
        <td>${cliente}</td>
        <td>${transportista}</td>
        <td>${ferro}</td>
        <td>${divisionBultos}</td>
        <td>${destino}</td>
        <td>
          <div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-outline-primary" data-id="${idRow}" onclick="editarFerroOP(${idRow})" title="Editar">
              <i data-feather="edit-2"></i>
            </button>
            <button class="btn btn-outline-danger" data-id="${idRow}" onclick="eliminarFerroOP(${idRow})" title="Eliminar">
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  tbodyFerroOP.innerHTML = html;
  feather.replace();
}


  function renderMetaFerroOP(from, to, total){
    metaResumenFerroOP.textContent = `Mostrando ${from}-${to} de ${total}`;
  }

  function bindPaginationFerroOP(ul, totalPages, currentPage){
    // El endpoint ya regresa pagination_html. Aquí escuchamos sus clicks.
    ul.addEventListener('click', function(e){
      const a = e.target.closest('a.page-link');
      if (!a) return;
      e.preventDefault();
      const page = parseInt(a.getAttribute('data-page'), 10);
      if (!isNaN(page) && page !== currentPageFerroOP) {
        currentPageFerroOP = page;
        cargarTablaFerroOP();
      }
    }, { once: true }); // se re-adjunta en cada render
  }

  // --------- Cargar tabla ---------
  function cargarTablaFerroOP(){
    // Aborta solicitud previa si existe
    if (currentXHRFerroOP && currentXHRFerroOP.readyState !== 4) {
      currentXHRFerroOP.abort();
    }

    const params = new URLSearchParams({
      q: (buscarFerroOP.value || '').trim(),
      desde: fechaDesdeFerroOP.value || '',
      hasta: fechaHastaFerroOP.value || '',
      perPage: perPageSelFerroOP.value || '10',
      page: String(currentPageFerroOP)
    });

    currentXHRFerroOP = new XMLHttpRequest();
    currentXHRFerroOP.open('GET', BASE_URL + 'Operaciones_maritimo_ferro_contenedores/listar?' + params.toString(), true);

    setLoadingFerroOP(true);

    currentXHRFerroOP.onload = function(){
      let res = null;
      try { res = JSON.parse(currentXHRFerroOP.responseText); } catch(_){ res = null; }
      if (!res || !Array.isArray(res.data)) {
        tbodyFerroOP.innerHTML = `
          <tr><td colspan="9" class="text-center text-danger">Error al cargar datos.</td></tr>`;
        renderMetaFerroOP(0,0,0);
        paginacionFerroOP.innerHTML = '';
        return;
      }
      renderRowsFerroOP(res.data);
      renderMetaFerroOP(res.from || 0, res.to || 0, res.total || 0);
      paginacionFerroOP.innerHTML = res.pagination_html || '';
      bindPaginationFerroOP(paginacionFerroOP, res.total_pages || 1, res.page || 1);
    };

    currentXHRFerroOP.onerror = function(){
      tbodyFerroOP.innerHTML = `
        <tr><td colspan="9" class="text-center text-danger">No se pudo conectar con el servidor.</td></tr>`;
      renderMetaFerroOP(0,0,0);
      paginacionFerroOP.innerHTML = '';
    };

    currentXHRFerroOP.send();
  }

  // --------- Listeners ---------
  buscarFerroOP.addEventListener('input', function(){
    currentPageFerroOP = 1;
    debounceFerroOP(cargarTablaFerroOP, 350);
  });

  fechaDesdeFerroOP.addEventListener('change', function(){
    currentPageFerroOP = 1;
    cargarTablaFerroOP();
  });

  fechaHastaFerroOP.addEventListener('change', function(){
    currentPageFerroOP = 1;
    cargarTablaFerroOP();
  });

  perPageSelFerroOP.addEventListener('change', function(){
    currentPageFerroOP = 1;
    cargarTablaFerroOP();
  });

  

  // --------- Modal (hooks mínimos por ahora) ---------
  window.editarFerroOP = function(idRow){
    // TODO: abrir modal en modo editar, pedir detalle por idRow si lo deseas
    console.log('editarFerroOP', idRow);
  };

  window.eliminarFerroOP = function(idRow){
    // TODO: confirmar y eliminar (endpoint delete)
    console.log('eliminarFerroOP', idRow);
  };

  // Validación rápida de saldo en el modal (cuando lo uses)
  bultosAsignadosFerroOP?.addEventListener('input', function(){
    const rest = Number(bultosRestantesFerroOP?.value || 0);
    const asig = Number(bultosAsignadosFerroOP?.value || 0);
    const saldo = rest - asig;
    if (badgeSaldoFerroOP){
      badgeSaldoFerroOP.textContent = `Saldo: ${saldo}`;
      badgeSaldoFerroOP.className = 'badge ' + (saldo < 0 ? 'bg-danger' : 'bg-success');
    }
  });

  // --------- Init ---------
  cargarTablaFerroOP();
})();
 
// Excel
  document.getElementById('btnExcelFerroOP')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: 'tablaFerroOP',       // "#tablaEventos" o el elemento también funciona
      formato: 'xlsx',
      nombre: 'FerrosEnOperacion.xlsx',
      columnasOcultas: [6],      // oculta columna ID
      soloVisibles: true,
      sheetName: 'Contenedores En Operacion'
    });
  });

  // PDF
  document.getElementById('btnPdfFerroOP')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: '#tablaFerroOP',
      formato: 'pdf',
      nombre: 'FerrosEnOperacion.pdf',
      titulo: 'Ferros En Operacion',
      orientacion: 'landscape',  // o 'portrait'
      formatoPagina: 'letter',   // o 'a4'
      columnasOcultas: [6],
      soloVisibles: true
    });
  });
 
// === REGISTRAR ASIGNACIÓN MG→FX ===
(function(){
  "use strict";

  const form   = document.getElementById('formFerroOP');
  if (!form) return;

  const operacionIdFerroOP          = document.getElementById('operacionIdFerroOP');
  const contenedorMaritimoIdFerroOP = document.getElementById('contenedorMaritimoIdFerroOP');
  const bultosMaritimoFerroOP       = document.getElementById('bultosMaritimoFerroOP');
  const bultosRestantesFerroOP      = document.getElementById('bultosRestantesFerroOP');
  const contenedorFerroIdFerroOP    = document.getElementById('contenedorFerroIdFerroOP');
  const bultosAsignadosFerroOP      = document.getElementById('bultosAsignadosFerroOP');
  const transportistaIdFerroOP      = document.getElementById('transportistaIdFerroOP');
  const destinoIdFerroOP            = document.getElementById('destinoIdFerroOP');
  const comentariosFerroOP          = document.getElementById('comentariosFerroOP');
  const badgeSaldoFerroOP           = document.getElementById('badgeSaldoFerroOP');

  function setBadgeSaldo(val){
    if (!badgeSaldoFerroOP) return;
    const v = Number(val||0);
    badgeSaldoFerroOP.textContent = `Saldo: ${v}`;
    badgeSaldoFerroOP.className   = 'badge ' + (v < 0 ? 'bg-danger text-white' : 'bg-success text-white');
  }

  function toast(msg, ok=true){
    if (window.Swal) {
      Swal.fire({ icon: ok?'success':'error', title: ok?'Listo':'Aviso', text: msg, timer: 1800, showConfirmButton:false });
    } else {
      alert(msg);
    }
  }

form.addEventListener('submit', function(e){
  e.preventDefault();

  // === Refs y valores ===
  const btn   = form.querySelector('button[type="submit"]');

  const opId  = Number(operacionIdFerroOP.value||0);
  const mgId  = Number(contenedorMaritimoIdFerroOP.value||0);

  const fxIdInput   = document.getElementById('contenedorFerroIdFerroOP');
  const fxNameInput = document.getElementById('contenedorFerroNombreFerroOP');
  const fxId        = Number((fxIdInput?.value)||0);
  const fxName      = (fxNameInput?.value||'').trim();

  const trans = Number(transportistaIdFerroOP.value||0);
  const dest  = Number(destinoIdFerroOP.value||0);
  const asig  = Number(bultosAsignadosFerroOP.value||0);
  const rest  = Number(bultosRestantesFerroOP.value||0);

  // === Validaciones front (el back también valida) ===
  if (!opId)          return toast('Selecciona una operación.', false);
  if (!mgId)          return toast('Selecciona un contenedor marítimo.', false);
  if (!fxId && !fxName) return toast('Selecciona la caja/ferro o escribe el número para crearlo.', false);
  if (!trans)         return toast('Selecciona un transportista.', false);
  if (!dest)          return toast('Selecciona un destino.', false);
  if (asig <= 0)      return toast('Los bultos asignados deben ser > 0.', false);
  if (asig > rest)    return toast(`No hay saldo suficiente. Disponible: ${rest}.`, false);

  // ---- función que realiza el POST de la asignación (tu lógica original) ----
  function postAsignacion() {
    const fd = new FormData(form);
    btn && (btn.disabled = true);

    const x = new XMLHttpRequest();
    x.open('POST', BASE_URL + 'Operaciones_maritimo_ferro_contenedores/guardar_asignacion', true);

    x.onload = function(){
      btn && (btn.disabled = false);
      let res = null;
      try { res = JSON.parse(x.responseText||'{}'); } catch(_){}

      if (!res || res.ok !== true) {
        const msg = (res && res.msg) ? res.msg : 'No se pudo registrar la asignación.';
        setBadgeSaldo(rest); // sin cambios
        return toast(msg, false);
      }

      // OK: actualizar saldo con el que regresa el backend (o calcular rápido)
      const saldo = (res.data && typeof res.data.saldo !== 'undefined') ? Number(res.data.saldo) : (rest - asig);
      bultosRestantesFerroOP.value = String(saldo);
      setBadgeSaldo(saldo);

      // Reset mínimo para capturar otra línea (dejamos op + MG fijos)
      if (fxIdInput)   fxIdInput.value = '';
      if (fxNameInput) fxNameInput.value = '';
      bultosAsignadosFerroOP.value = '';
      comentariosFerroOP.value = '';

      // Refrescar tabla
      if (typeof cargarTablaFerroOP === 'function') cargarTablaFerroOP();

      const folioFx = res.data?.numero_operacion_ferro || '';
      toast(folioFx ? `Asignación registrada (${folioFx}).` : 'Asignación registrada.', true);
      feather.replace();
    };

    x.onerror = function(){
      btn && (btn.disabled = false);
      toast('No se pudo conectar con el servidor.', false);
    };

    x.send(fd);
  }

  // ---- Si no hay ID pero sí nombre: crear ferro al vuelo y luego asignar ----
  if (!fxId && fxName !== '') {
    btn && (btn.disabled = true);

    const fdMk = new FormData();
    fdMk.append('numero_ferro', fxName);

    const xMk = new XMLHttpRequest();
    xMk.open('POST', BASE_URL + 'Operaciones_maritimo_ferro_contenedores/crear_ferro', true);

    xMk.onload = function(){
      let r = null; 
      try { r = JSON.parse(xMk.responseText||'{}'); } catch(_){}

      if (!r || r.ok !== true || !r.id) {
        btn && (btn.disabled = false);
        return toast((r && r.msg) ? r.msg : 'No se pudo crear la caja/ferro.', false);
      }

      // Setear el hidden con el nuevo ID y continuar con el flujo normal
      if (fxIdInput) fxIdInput.value = String(r.id);
      // (opcional) normaliza el texto visible con la etiqueta devuelta
      if (fxNameInput && r.label) fxNameInput.value = r.label;

      // Ahora sí, post de la asignación
      postAsignacion();
    };

    xMk.onerror = function(){
      btn && (btn.disabled = false);
      toast('No se pudo conectar para crear la caja/ferro.', false);
    };

    xMk.send(fdMk);
    return; // salimos; el resto lo hace postAsignacion()
  }

  // ---- Si ya hay fxId, seguimos directo a guardar asignación ----
  postAsignacion();
});


 
})();
 
