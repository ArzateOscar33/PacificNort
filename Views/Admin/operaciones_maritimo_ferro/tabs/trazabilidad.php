<!-- =========================
     RUTAS FERRO/CAJA (VISTA)
     ========================= -->

<!-- Botón para abrir el modal de Rutas Ferro/Caja -->
<div class="d-flex justify-content-end mb-3">
<button class="btn btn-success" 
        onclick="window.nuevaRutaFerro()" 
        data-bs-toggle="modal" 
        data-bs-target="#modalRutasFerro">
  <i data-feather="map"></i> Nueva Ruta Ferro/Caja
</button>
</div>
<!-- ========== RESUMEN DE RUTAS FERRO/CAJA (COMPACTO) ========== -->
<div class="card mt-4" id="rutasFerroResumen">
  <div class="card-header">
    <div class="row align-items-center gx-2 gy-2">
      <!-- Título -->
      <div class="col-12 col-lg-3 d-flex align-items-center gap-2">
        <i data-feather="map"></i>
        <strong class="mb-0">Resumen de Rutas Ferro/Caja</strong>
      </div>

      <!-- Buscador -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i data-feather="search"></i></span>
          <input type="text" id="rutasBuscar" class="form-control"
                 placeholder="Buscar (operación / ferro / cliente / destino)">
        </div>
      </div>

      <!-- Fechas (compactas) -->
      <div class="col-6 col-sm-3 col-lg-2">
        <input type="date" class="form-control form-control-sm" id="rutasFechaIni" title="Desde">
      </div>
      <div class="col-6 col-sm-3 col-lg-2">
        <input type="date" class="form-control form-control-sm" id="rutasFechaFin" title="Hasta">
      </div>

      <!-- Per page + Export -->
      <div class="col-12 col-lg-1 d-flex justify-content-lg-end">
        <select id="rutasPerPage" class="form-control form-control-sm">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
      </div>

      <div class="col-12 col-lg-12 d-flex flex-wrap justify-content-lg-end gap-2">
        <button class="btn btn-sm btn-outline-success" id="rutasExcel">
          <i data-feather="file-text" class="me-1"></i>Excel
        </button>
        <button class="btn btn-sm btn-outline-warning" id="rutasPdf">
          <i data-feather="file" class="me-1"></i>PDF
        </button>
      </div>
    </div>
  </div>

  <div class="card-body pt-2">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0" id="tablaRutas">
        <thead class="table-light">
          <tr>
            <th style="min-width:140px;">Operación</th>
            <th style="min-width:120px;">Ferro/Caja</th>
            <th>Cliente</th>
            <th>Origen inicial</th>
            <th>Destino actual</th>
            <th class="text-center">Tramos</th>
            <th class="text-end">Costo acumulado</th>
            <th style="min-width:140px;">Última actualización</th>
            <th style="min-width:130px;">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyRutasFerro">
          <tr id="rutasEmptyRow">
            <td colspan="9" class="text-center text-muted">
              <i data-feather="info" class="me-1"></i> No hay rutas para mostrar.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
      <div class="small text-muted">
        <span id="rutasMeta">Mostrando 0-0 de 0</span>
      </div>
      <nav aria-label="Paginación Rutas Ferro/Caja">
        <ul id="rutasPaginacion" class="pagination pagination-sm mb-0"></ul>
      </nav>
    </div>
  </div>
</div>

<!-- Modal: Rutas Ferro/Caja -->
<div class="modal fade" id="modalRutasFerro" tabindex="-1" aria-labelledby="modalRutasFerroLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalRutasFerroLabel">
          <i data-feather="map" class="me-1"></i> Registrar Rutas para Ferro/Caja
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formRutasFerro" autocomplete="off">
          <!-- Hidden para enviar la colección de tramos al backend -->
          <input type="hidden" id="rutasPayload" name="rutasPayload">

          <!-- Selección de Operación Ferroviaria y Ferro/Caja -->
          <div class="card mb-3">
            <div class="card-header">
              <strong><i data-feather="info" class="me-1"></i> Selección</strong>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <!-- Operación Ferroviaria -->
                <div class="col-md-6 position-relative">
                  <label for="rutaOperacionFerroNombre" class="form-label">Operación Ferroviaria</label>
                  <input type="hidden" id="rutaOperacionFerroId" name="operacion_ferro_id">
                  <input type="text" id="rutaOperacionFerroNombre" class="form-control"
                         placeholder="Escribe para buscar (ej. FO-01)" required>
                  <!-- Sugerencias operación ferro -->
                  <div id="sugOperacionesFerroRuta" class="list-group position-absolute w-100"
                       style="z-index:1055; display:none;"></div>
                </div>

                <!-- Ferro/Caja ligado a la operación ferro -->
                <div class="col-md-6 position-relative">
                  <label for="rutaFerroNombre" class="form-label">Ferro / Caja (vinculado a la operación)</label>
                  <input type="hidden" id="rutaFerroId" name="contenedor_fisico_id">
                  <input type="text" id="rutaFerroNombre" class="form-control"
                         placeholder="Escribe para buscar ferro/caja..." required disabled>
                  <!-- Sugerencias ferro/caja -->
                  <div id="sugFerrosRuta" class="list-group position-absolute w-100"
                       style="z-index:1055; display:none;"></div>
                   
                </div>

                <!-- Clientes detectados en esta operación (opcional informativo) -->
                <div class="col-12">
                  <label class="form-label mb-1">Clientes en esta operación</label>
                  <div id="rutaClientesChips" class="d-flex flex-wrap gap-2"> 
                     
                    
                  </div>
                  <div class="form-text"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tramos -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong><i data-feather="shuffle" class="me-1"></i> Tramos</strong>
              <small class="text-muted">El origen del siguiente tramo se toma del destino del tramo anterior.</small>
            </div>

            <div class="card-body">
              <div class="row g-3 align-items-end">
                <!-- Origen (editable con autosuggest) -->
                <div class="col-md-3 position-relative">
                  <label for="tramoOrigenNombre" class="form-label">Origen</label>
                  <input type="hidden" id="tramoOrigenId">
                  <input type="text" id="tramoOrigenNombre" class="form-control"
                         placeholder="Escribe para buscar origen..." required>
                  <div id="sugOrigenesRuta" class="list-group position-absolute w-100"
                       style="z-index:1055; display:none;"></div>
                </div>

                <!-- Destino (editable con autosuggest) -->
                <div class="col-md-3 position-relative">
                  <label for="tramoDestinoNombre" class="form-label">Destino</label>
                  <input type="hidden" id="tramoDestinoId">
                  <input type="text" id="tramoDestinoNombre" class="form-control"
                         placeholder="Escribe para buscar destino..." required>
                  <div id="sugDestinosRuta" class="list-group position-absolute w-100"
                       style="z-index:1055; display:none;"></div>
                </div>

                <!-- Transportista -->
                <div class="col-md-3 position-relative">
                  <label for="tramoTransportistaNombre" class="form-label">Transportista</label>
                  <input type="hidden" id="tramoTransportistaId">
                  <input type="text" id="tramoTransportistaNombre" class="form-control"
                         placeholder="Escribe para buscar transportista..." required>
                  <div id="sugTransportistasRuta" class="list-group position-absolute w-100"
                       style="z-index:1055; display:none;"></div>
                </div>

                <!-- Monto -->
                <div class="col-md-2">
                  <label for="tramoMonto" class="form-label">Monto</label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" id="tramoMonto" class="form-control" min="0" step="0.01" placeholder="0.00" required>
                  </div>
                </div>

                <!-- Botón + -->
                <div class="col-md-1 d-grid">
                  <button type="button" id="btnAgregarTramo" class="btn btn-success" title="Agregar tramo">
                    <i data-feather="plus"></i>
                  </button>
                </div>

                <!-- Comentario -->
                <div class="col-12">
                  <label for="tramoComentario" class="form-label">Comentario</label>
                  <input type="text" id="tramoComentario" class="form-control" maxlength="255"
                         placeholder="Opcional: notas del tramo">
                </div>
              </div>

              <!-- Tabla de tramos agregados -->
              <div class="table-responsive mt-4">
                <table class="table table-sm align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Origen</th>
                      <th>Destino</th>
                      <th>Transportista</th>
                      <th class="text-end">Monto</th>
                      <th>Comentario</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyTramos">
                    <tr id="noTramosRow">
                      <td colspan="7" class="text-center text-muted">
                        <i data-feather="alert-circle" class="me-1"></i> No has agregado tramos.
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="table-light">
                    <tr>
                      <th colspan="4" class="text-end">Total:</th>
                      <th class="text-end">
                        <span id="totalMonto" class="badge bg-success text-white">0.00</span>
                      </th>
                      <th colspan="2"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x"></i> Cerrar
        </button>
        <button type="button" class="btn btn-primary" id="btnGuardarRutas">
          <i data-feather="save"></i> Guardar Rutas
        </button>
      </div>
    </div>
  </div>
</div>



<script src="<?php echo BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/trazabilidad_catalogo.js"></script>
<script src="<?php echo BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/trazabilidad_registrar.js"></script>
<script>
  // Si no lo tienes en otro lado:
  // const BASE_URL = "<?= BASE_URL ?>";
  feather.replace();
</script>

<script>
  /*
(function(){
  "use strict";

  // ===== Refs
  const tbody      = document.getElementById('tbodyRutasFerro');
  const emptyRow   = document.getElementById('rutasEmptyRow');
  const metaSpan   = document.getElementById('rutasMeta');
  const ulPag      = document.getElementById('rutasPaginacion');

  const buscarInp  = document.getElementById('rutasBuscar');
  const fIniInp    = document.getElementById('rutasFechaIni');
  const fFinInp    = document.getElementById('rutasFechaFin');
  const perPageSel = document.getElementById('rutasPerPage');
  const btnXls     = document.getElementById('rutasExcel');
  const btnPdf     = document.getElementById('rutasPdf');

  // ===== Estado
  let page = 1;
  let total = 0;
  let perPage = Number(perPageSel.value || 10);
  let rows = [];

  // ===== Utils
  const f = {
    money(n){ return Number(n || 0).toLocaleString('es-MX', {style:'currency', currency:'MXN'}); },
    fmtDate(iso){ if(!iso) return ''; const d = new Date(iso); return d.toLocaleString(); },
    // Render de paginación simple
    pag(totalRows, per, current){
      const pages = Math.max(1, Math.ceil(totalRows / per));
      ulPag.innerHTML = '';
      const add = (p, txt, disabled=false, active=false) => {
        const li = document.createElement('li');
        li.className = `page-item ${disabled?'disabled':''} ${active?'active':''}`;
        li.innerHTML = `<a class="page-link" href="#" data-page="${p}">${txt}</a>`;
        ulPag.appendChild(li);
      };
      add(Math.max(1, current-1), '«', current===1);
      for(let i=1;i<=pages;i++){
        if(i===1 || i===pages || Math.abs(i-current)<=2){
          add(i, i, false, i===current);
        }else if(Math.abs(i-current)===3){
          const li = document.createElement('li');
          li.className = 'page-item disabled';
          li.innerHTML = `<span class="page-link">…</span>`;
          ulPag.appendChild(li);
        }
      }
      add(Math.min(pages, current+1), '»', current===pages);
    },
    // Render de tabla
    render(){
      tbody.innerHTML = '';
      if(rows.length === 0){
        tbody.appendChild(emptyRow);
        emptyRow.style.display = '';
      }else{
        emptyRow.style.display = 'none';
        rows.forEach(it => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>
              <div class="fw-semibold">${it.operacion_numero || '-'}</div>
              <div class="small text-muted">Marítima #${it.operacion_id}</div>
            </td>
            <td>
              <div class="fw-semibold">${it.ferro_nombre || '-'}</div>
              <div class="small text-muted">ID ${it.ferro_id || ''}</div>
            </td>
            <td>${it.cliente || '-'}</td>
            <td>${it.origen_inicial || '-'}</td>
            <td>
              <span class="badge bg-info">${it.destino_actual || '-'}</span>
            </td>
            <td class="text-center">
              <span class="badge bg-secondary">${it.tramos_count || 0}</span>
            </td>
            <td class="text-end">
              <span class="badge bg-success">${f.money(it.costo_acumulado || 0)}</span>
            </td>
            <td>${f.fmtDate(it.updated_at)}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-outline-primary me-1" data-action="ver" data-id="${it.ferro_ruta_id}">
                <i data-feather="eye"></i>
              </button>
              <button class="btn btn-sm btn-outline-success me-1" data-action="editar" data-id="${it.ferro_ruta_id}">
                <i data-feather="edit-2"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" data-action="eliminar" data-id="${it.ferro_ruta_id}">
                <i data-feather="trash-2"></i>
              </button>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }
      feather.replace();
      // meta
      const from = total === 0 ? 0 : ((page-1)*perPage + 1);
      const to   = Math.min(total, page*perPage);
      metaSpan.textContent = `Mostrando ${from}–${to} de ${total}`;
      // paginación
      f.pag(total, perPage, page);
    }
  };

  // ===== Carga (XHR)
  function cargar(){
    const qs = new URLSearchParams({
      q: (buscarInp.value || '').trim(),
      desde: (fIniInp.value || ''),
      hasta: (fFinInp.value || ''),
      page: String(page),
      perPage: String(perPage)
    });
    const xhr = new XMLHttpRequest();
    xhr.open('GET', BASE_URL + 'operaciones_maritimoferro/rutas_list?' + qs.toString(), true);
    xhr.onload = function(){
      try{
        const res = JSON.parse(xhr.responseText || '{}');
        // Estructura esperada:
        // { ok:true, total: N, data: [ { ferro_ruta_id, operacion_id, operacion_numero, cliente, ferro_id, ferro_nombre, origen_inicial, destino_actual, tramos_count, costo_acumulado, updated_at } ] }
        if(res.ok){
          total = Number(res.total || 0);
          rows = Array.isArray(res.data) ? res.data : [];
          f.render();
        }else{
          total = 0; rows = [];
          f.render();
          if(res.msg) console.warn(res.msg);
        }
      }catch(e){
        total = 0; rows = [];
        f.render();
        console.error('JSON inválido en rutas_list', e);
      }
    };
    xhr.send();
  }

  // ===== Eventos UI
  buscarInp.addEventListener('input', debounce(()=>{ page = 1; cargar(); }, 300));
  fIniInp.addEventListener('change', ()=>{ page = 1; cargar(); });
  fFinInp.addEventListener('change', ()=>{ page = 1; cargar(); });
  perPageSel.addEventListener('change', ()=>{
    perPage = Number(perPageSel.value || 10);
    page = 1; cargar();
  });
  ulPag.addEventListener('click', function(ev){
    const a = ev.target.closest('a[data-page]');
    if(!a) return;
    ev.preventDefault();
    const p = Number(a.getAttribute('data-page'));
    if(Number.isInteger(p) && p>0 && p !== page){
      page = p; cargar();
    }
  });

  // Export (placeholder)
  btnXls.addEventListener('click', function(){
    const qs = new URLSearchParams({
      q: buscarInp.value||'',
      desde: fIniInp.value||'',
      hasta: fFinInp.value||'',
    });
    window.open(BASE_URL + 'operaciones_maritimoferro/rutas_export_excel?' + qs.toString(), '_blank');
  });
  btnPdf.addEventListener('click', function(){
    const qs = new URLSearchParams({
      q: buscarInp.value||'',
      desde: fIniInp.value||'',
      hasta: fFinInp.value||'',
    });
    window.open(BASE_URL + 'operaciones_maritimoferro/rutas_export_pdf?' + qs.toString(), '_blank');
  });

  // Debounce helper (mini)
  function debounce(fn, wait){
    let t; return function(){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, arguments), wait); };
  }

  // ===== Inicio
  cargar();
})();
*/
</script>
 
<script>
  /*
(function(){
  "use strict";

  // ===== Refs =====
  const rutaOperacionId       = document.getElementById('rutaOperacionId');
  const rutaOperacionNombre   = document.getElementById('rutaOperacionNombre');
  const sugOperacionesRuta    = document.getElementById('sugOperacionesRuta');

  const rutaFerroId           = document.getElementById('rutaFerroId');
  const rutaFerroNombre       = document.getElementById('rutaFerroNombre');
  const sugFerrosRuta         = document.getElementById('sugFerrosRuta');

  const rutaOrigenBaseId      = document.getElementById('rutaOrigenBaseId');
  const rutaOrigenBaseNombre  = document.getElementById('rutaOrigenBaseNombre');
  const rutaClienteNombre     = document.getElementById('rutaClienteNombre');

  const tramoOrigenId         = document.getElementById('tramoOrigenId');
  const tramoOrigenNombre     = document.getElementById('tramoOrigenNombre');

  const tramoDestinoId        = document.getElementById('tramoDestinoId');
  const tramoDestinoNombre    = document.getElementById('tramoDestinoNombre');
  const sugDestinosRuta       = document.getElementById('sugDestinosRuta');

  const tramoTransportistaId      = document.getElementById('tramoTransportistaId');
  const tramoTransportistaNombre  = document.getElementById('tramoTransportistaNombre');
  const sugTransportistasRuta     = document.getElementById('sugTransportistasRuta');

  const tramoMonto            = document.getElementById('tramoMonto');
  const tramoComentario       = document.getElementById('tramoComentario');

  const btnAgregarTramo       = document.getElementById('btnAgregarTramo');
  const tbodyTramos           = document.getElementById('tbodyTramos');
  const noTramosRow           = document.getElementById('noTramosRow');
  const totalMontoBadge       = document.getElementById('totalMonto');

  const rutasPayload          = document.getElementById('rutasPayload');
  const btnGuardarRutas       = document.getElementById('btnGuardarRutas');

  const modalEl               = document.getElementById('modalRutasFerro');

  // ===== Estado =====
  let tramos = []; // {origen_id, origen_nombre, destino_id, destino_nombre, transportista_id, transportista_nombre, monto, comentario}

  // ===== Helpers =====
  const f = {
    money(n){ return Number(n || 0).toFixed(2); },
    toast(msg){ if(window.Swal) Swal.fire({icon:'info', text:msg}); else alert(msg); },
    ok(msg){ if(window.Swal) Swal.fire({icon:'success', text:msg}); else alert(msg); },
    err(msg){ if(window.Swal) Swal.fire({icon:'error', text:msg}); else alert(msg); },
    emptySuggestions(...els){ els.forEach(el => { el.innerHTML=''; el.style.display='none'; }); },
    render(){
      // limpiar
      tbodyTramos.innerHTML = '';
      if(tramos.length === 0){
        tbodyTramos.appendChild(noTramosRow);
        noTramosRow.style.display = '';
      } else {
        noTramosRow.style.display = 'none';
        let total = 0;
        tramos.forEach((t, i) => {
          total += Number(t.monto || 0);
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${i+1}</td>
            <td>${t.origen_nombre}</td>
            <td>${t.destino_nombre}</td>
            <td>${t.transportista_nombre}</td>
            <td class="text-end">${f.money(t.monto)}</td>
            <td>${t.comentario ? t.comentario : ''}</td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-danger" data-idx="${i}" title="Eliminar">
                <i data-feather="trash-2"></i>
              </button>
            </td>
          `;
          tbodyTramos.appendChild(tr);
        });
        totalMontoBadge.textContent = f.money(total);
        feather.replace();
      }
      // Sincronizar payload
      rutasPayload.value = JSON.stringify(tramos);
    },
    setNextOriginFromLast(){
      if(tramos.length === 0){
        // primer tramo: origen = puerto origen de la operación marítima
        tramoOrigenId.value = rutaOrigenBaseId.value || '';
        tramoOrigenNombre.value = rutaOrigenBaseNombre.value || '';
      } else {
        const last = tramos[tramos.length - 1];
        tramoOrigenId.value = last.destino_id || '';
        tramoOrigenNombre.value = last.destino_nombre || '';
      }
    },
    resetTramoInputs(keepOrigin = true){
      if(!keepOrigin){ tramoOrigenId.value = ''; tramoOrigenNombre.value = ''; }
      tramoDestinoId.value = '';
      tramoDestinoNombre.value = '';
      tramoTransportistaId.value = '';
      tramoTransportistaNombre.value = '';
      tramoMonto.value = '';
      tramoComentario.value = '';
      f.emptySuggestions(sugDestinosRuta, sugTransportistasRuta);
    }
  };

  // ===== Lógica de selección base =====
  // Cuando se elija la operación, debemos:
  // 1) Autollenar origen base (puerto de la operación marítima)
  // 2) Habilitar el campo Ferro/Caja
  // 3) Cargar cliente (opcional)
  // Nota: Aquí solo muestro el “hook” para que integres tus XHR/JSON.
  rutaOperacionNombre.addEventListener('input', function(){
    // TODO: disparar XHR para sugerir operaciones -> poblar sugOperacionesRuta
    // Por ahora, ocultamos sugerencias cuando no hay texto
    if(this.value.trim().length === 0) f.emptySuggestions(sugOperacionesRuta);
  });

  // Simulación: al “elegir” una operación de la lista (usa tus eventos reales)
  // Debes llamar esta función con los datos reales:
  function onPickOperacion(op){
    // op: { id, numero_operacion, puerto_origen_id, puerto_origen_nombre, cliente_nombre }
    rutaOperacionId.value = op.id;
    rutaOperacionNombre.value = op.numero_operacion;
    rutaOrigenBaseId.value = op.puerto_origen_id;
    rutaOrigenBaseNombre.value = op.puerto_origen_nombre;
    rutaClienteNombre.value = op.cliente_nombre || '';
    rutaFerroNombre.disabled = false;

    // limpiar estado tramos
    tramos = [];
    f.render();
    f.setNextOriginFromLast();
  }

  // Selección de Ferro/Caja (filtrado por operación)
  rutaFerroNombre.addEventListener('input', function(){
    if(this.value.trim().length === 0) f.emptySuggestions(sugFerrosRuta);
    // TODO: disparar XHR para sugerir ferros/cajas ligados a rutaOperacionId.value
  });
  function onPickFerro(ferro){
    // ferro: { id, nombre }
    rutaFerroId.value   = ferro.id;
    rutaFerroNombre.value = ferro.nombre;
  }

  // ===== Tramos =====
  // Autollenar el origen inicial cuando se abra el modal o cambie la operación
  modalEl.addEventListener('shown.bs.modal', function(){
    f.setNextOriginFromLast();
  });

  // Sugerir destinos
  tramoDestinoNombre.addEventListener('input', function(){
    if(this.value.trim().length === 0) f.emptySuggestions(sugDestinosRuta);
    // TODO: XHR para sugerir destinos
  });
  // Sugerir transportistas
  tramoTransportistaNombre.addEventListener('input', function(){
    if(this.value.trim().length === 0) f.emptySuggestions(sugTransportistasRuta);
    // TODO: XHR para sugerir transportistas
  });

  // Agregar tramo
  btnAgregarTramo.addEventListener('click', function(){
    // Validaciones mínimas
    if(!tramoOrigenId.value || !tramoOrigenNombre.value){
      return f.err('El origen no está definido. Elige una operación primero.');
    }
    if(!tramoDestinoNombre.value.trim()){
      return f.err('Debes elegir un destino.');
    }
    if(!tramoTransportistaNombre.value.trim()){
      return f.err('Debes elegir un transportista.');
    }
    const monto = Number(tramoMonto.value || 0);
    if(isNaN(monto) || monto < 0){
      return f.err('El monto debe ser un número válido (>= 0).');
    }

    // Armar objeto tramo
    tramos.push({
      origen_id:            tramoOrigenId.value || null,
      origen_nombre:        tramoOrigenNombre.value.trim(),
      destino_id:           tramoDestinoId.value || null,
      destino_nombre:       tramoDestinoNombre.value.trim(),
      transportista_id:     tramoTransportistaId.value || null,
      transportista_nombre: tramoTransportistaNombre.value.trim(),
      monto:                monto,
      comentario:           (tramoComentario.value || '').trim()
    });

    f.render();
    // Preparar siguiente: origen = destino recién agregado
    f.resetTramoInputs(true);
    f.setNextOriginFromLast();
    tramoDestinoNombre.focus();
  });

  // Eliminar tramo (delegado)
  tbodyTramos.addEventListener('click', function(ev){
    const btn = ev.target.closest('button[data-idx]');
    if(!btn) return;
    const idx = Number(btn.getAttribute('data-idx'));
    if(Number.isInteger(idx) && idx >= 0 && idx < tramos.length){
      tramos.splice(idx, 1);
      f.render();
      f.setNextOriginFromLast();
    }
  });

  // Guardar (envía JSON de tramos + ids base)
  btnGuardarRutas.addEventListener('click', function(){
    if(!rutaOperacionId.value){ return f.err('Primero selecciona la operación marítima.'); }
    if(!rutaFerroId.value){ return f.err('Selecciona el Ferro/Caja vinculado.'); }
    if(tramos.length === 0){ return f.err('Agrega al menos un tramo.'); }

    // Aquí puedes mandar tu XHR al endpoint que corresponda:
    // Ejemplo (XMLHttpRequest, siguiendo tu patrón):
    /*
    const xhr = new XMLHttpRequest();
    xhr.open('POST', BASE_URL + 'operaciones_maritimoferro/guardar_rutas', true);
    xhr.onload = function(){
      try{
        const res = JSON.parse(xhr.responseText || '{}');
        if(res.ok){ f.ok('Rutas guardadas correctamente.'); /* cerrar modal *-/ }
        else { f.err(res.msg || 'No fue posible guardar.'); }
      }catch(e){ f.err('Respuesta inválida del servidor.'); }
    };
    const fd = new FormData();
    fd.append('operacion_id', rutaOperacionId.value);
    fd.append('ferro_id', rutaFerroId.value);
    fd.append('rutas', rutasPayload.value); // JSON de tramos
    xhr.send(fd);
   

    // De momento solo mostramos un OK de ejemplo:
    f.ok('Simulación: se enviaría el JSON de tramos al servidor.');
  });

  // ====== DEMO de cómo “elegir” desde tus sugerencias (borra esto y conecta con tu autosuggest) ======
  // Llama a onPickOperacion() y onPickFerro() con datos reales cuando el usuario seleccione
  // Por ejemplo:
  // onPickOperacion({ id: 123, numero_operacion: 'LB-01', puerto_origen_id: 45, puerto_origen_nombre: 'PUERTO MANZANILLO', cliente_nombre: 'ACME' });
  // onPickFerro({ id: 789, nombre: 'FX-001' });

  // Render inicial
  feather.replace();
  
})(); */
</script>
