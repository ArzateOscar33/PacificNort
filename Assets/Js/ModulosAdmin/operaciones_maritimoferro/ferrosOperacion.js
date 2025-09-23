 
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
          <td colspan="7" class="text-center text-muted py-4">
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
          <td colspan="7" class="text-center text-muted py-4">
            Sin resultados.
          </td>
        </tr>`;
      feather.replace();
      return;
    }

    const html = rows.map(r => {
      const numeroOperacion   = safeTextFerroOP(r.numero_operacion);
      const contMaritimo      = safeTextFerroOP(r.contenedor_maritimo);
      const bultosMaritimo    = safeTextFerroOP(r.bultos_maritimo ?? '');
      const cliente           = safeTextFerroOP(r.cliente);
      const ferro             = safeTextFerroOP(r.ferro);
      const bultosAsignados   = safeTextFerroOP(r.bultos_asignados ?? 0);
      const idRow             = Number(r.id || 0);

      return `
        <tr>
          <td>${numeroOperacion}</td>
          <td>${contMaritimo}</td>
          <td class="text-end">${bultosMaritimo}</td>
          <td>${cliente}</td>
          <td>${ferro}</td>
          <td class="text-end">${bultosAsignados}</td>
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
          <tr><td colspan="7" class="text-center text-danger">Error al cargar datos.</td></tr>`;
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
        <tr><td colspan="7" class="text-center text-danger">No se pudo conectar con el servidor.</td></tr>`;
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
