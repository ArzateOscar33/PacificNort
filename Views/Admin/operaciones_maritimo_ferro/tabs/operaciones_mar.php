<style>
  /* ===== Sticky header (scroll vertical = página) ===== */
  :root {
    --mf-sticky-top: 0px;
    /* AJUSTA a tu navbar real */
    --mf-col1-w: 200px;
    /* ancho real Código */
    --mf-col2-w: 200px;
    /* ancho real Contenedor */
  }

  .form-check-input {
    width: 1.3em;
    height: 1.3em;
    accent-color: var(--bs-primary);
    cursor: pointer;
  }

  /* Tabla: importante para sticky + fondos */
  .mf-table {
    font-size: .875rem;
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    /* clave: evita “encogimientos” raros con overflow-x */
  }

  /* ===== Color por estatus en renglones ===== */
  .mf-table tbody tr.row-estatus-color>td {
    background-color: var(--estatus-bg) !important;
    color: var(--estatus-text, #000) !important;
  }

  /* Importante: las columnas sticky también deben respetar el color */
  .mf-table tbody tr.row-estatus-color>td.sticky-col {
    background-color: var(--estatus-bg) !important;
    color: var(--estatus-text, #000) !important;
  }

  /* Mantener bordes visibles aunque el fondo cambie */
  .mf-table tbody tr.row-estatus-color>td {
    border: 1px solid rgba(0, 0, 0, .25) !important;
  }

  /* Hover suave sin perder el color base */
  .mf-table tbody tr.row-estatus-color:hover>td {
    filter: brightness(0.96);
  }

  /* Base cells */
  .mf-table th,
  .mf-table td {
    padding: .55rem .75rem;
    vertical-align: middle;
    white-space: nowrap;
  }

  /* ===== Header sticky ===== */
  .mf-table thead th {
    position: sticky;
    top: var(--mf-sticky-top);
    z-index: 40;
    /* base header */
    background: var(--bs-success-bg-subtle, #d1e7dd);
    box-shadow: 0 2px 0 rgba(0, 0, 0, .06);
  }

  /* ===== Sticky columns base ===== */
  .mf-table .sticky-col {
    position: sticky;
    background: #fff;
    /* SOLIDO: evita transparencia */
    background-clip: padding-box;
    /* evita “bleed” por bordes */
    z-index: 20;
    /* base sticky cols */
  }

  /* Col 1 (Código) */
  .mf-table .sticky-col-1 {
    left: 0;
    min-width: var(--mf-col1-w);
    width: var(--mf-col1-w);
    z-index: 30;
    /* encima de celdas normales */
    box-shadow: 2px 0 0 rgba(0, 0, 0, .06);
    background: #f8f9fa;
    /* sólido */
  }

  /* Col 2 (Contenedor) */
  .mf-table .sticky-col-2 {
    left: var(--mf-col1-w);
    min-width: var(--mf-col2-w);
    width: var(--mf-col2-w);
    z-index: 30;
    border-right: 2px solid rgba(0, 0, 0, .08);
    background: #f8f9fa;
    /* sólido */
  }

  /* ===== Intersección: header + sticky cols (lo más arriba) ===== */
  .mf-table thead th.sticky-col {
    z-index: 60;
    /* encima del resto del header */
    background: var(--bs-success-bg-subtle, #d1e7dd);
  }

  /* Zebra: asegura fondo también en sticky cols */
  .mf-table tbody tr:nth-child(odd) {
    background-color: rgba(0, 0, 0, .015);
  }

  .mf-table tbody tr:nth-child(odd) td.sticky-col {
    background-color: #eef1f4;
    /* sólido (no rgba) */
  }

  /* Wrapper: SOLO scroll horizontal */
  .mf-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .badge-asignacion.is-linked {
    outline: 2px solid rgba(255, 255, 255, .95);
    box-shadow: 0 0 0 2px rgba(13, 110, 253, .55);
    /* azul bootstrap */
    transform: scaleY(1.2) scaleX(1.2) translateY(-1px);
    transition: 0.2s ease-in;
  }

  .input-uppercase {
    text-transform: uppercase;
  }

  td,
  th {
    text-transform: uppercase;
  }

  /* ===== Scroll horizontal superior ===== */
  .mf-top-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    height: 14px;
    position: sticky;
    top: var(--mf-sticky-top);
    z-index: 80;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 8px;
    margin-bottom: .5rem;
  }

  /* El “relleno” que crea el ancho scrolleable */
  .mf-top-scroll-inner {
    height: 1px;
    width: 0px;
  }

  /**/
  .filtro-estatus-wrapper .dropdown-toggle::after {
    display: none;
  }

  .filtro-estatus-menu {
    width: 260px;
    max-height: 320px;
    overflow-y: auto;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, .08);
  }

  .filtro-estatus-item {
    border-radius: 8px;
    padding: 8px 10px;
    cursor: pointer;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .filtro-estatus-item .form-check-input {
    margin-right: 6px !important;
    flex: 0 0 auto;
  }

  .filtro-estatus-item .estatus-dot {
    margin-left: 2px;
    margin-right: 4px;
  }

  .filtro-estatus-item span:last-child {
    padding-left: 4px;
  }

  .filtro-estatus-item:hover {
    background-color: #f1f5f9;
  }

  .estatus-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex: 0 0 10px;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, .05);
  }

  #btnFiltroEstatus,
  #btnFiltroTransportista,
  #btnFiltroCliente,
  #btnFiltroBroker {
    min-height: 38px;
    border-radius: 8px;
    background-color: #fff;
    font-size: 0.875rem;
  }

  #btnFiltroEstatus:hover,
  #btnFiltroTransportista:hover,
  #btnFiltroCliente:hover,
  #btnFiltroBroker:hover {
    background-color: #f8fafc;
  }

  #txtFiltroEstatus,
  #txtFiltroTransportista,
  #txtFiltroCliente,
  #txtFiltroBroker {
    font-weight: 500;
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="anchor" class="me-1"></i> Operaciones
      </h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalMaritimoFerro"
        id="maritimo_ferro_btnNuevaOperacion">
        <i data-feather="plus-circle" class="me-1"></i> Nueva Operación
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros -->

      <div class="mb-3 col-md-12 ">


        <!-- ===== FILA 1: Logística + rango de fechas ===== -->
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-2 ">


          <!-- Búsqueda global (DEJAR AL FINAL, sin cambiar id/name) -->
          <input id="maritimo_ferro_buscarOperacion" class="form-control col-md-9"
            placeholder="Buscar por código, BL o contenedor">
          <!-- Rango de fechas -->
          <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
            <div class="d-flex align-items-end gap-2">
              <i data-feather="calendar"></i>
              <span class="small text-muted">Rango:</span>
            </div>

            <input type="date" id="maritimo_ferro_fechaInicio" name="maritimo_ferro_fechaInicio" class="form-control"
              style="max-width:165px;" aria-label="Fecha inicio" />

            <input type="date" id="maritimo_ferro_fechaFin" name="maritimo_ferro_fechaFin" class="form-control"
              style="max-width:165px;" aria-label="Fecha fin" />
          </div>
        </div>

        <!-- ===== FILA 2: Catálogos principales ===== -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">

          <!-- Subtipo -->
          <select id="maritimo_ferro_filtroSubtipo" name="maritimo_ferro_filtroSubtipo" class="form-control"
            style="max-width:240px;">
            <option value="">Subtipo (Todos)</option>
            <?php if (!empty($data['subtipos'])): ?>
              <?php foreach ($data['subtipos'] as $st): ?>
                <option value="<?= (int)$st['id_subtipo']; ?>">
                  <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>

          <!-- Filtro Estatus múltiple -->
          <div class="dropdown filtro-estatus-wrapper" style="max-width: 260px;">
            <button
              class="btn btn-light border w-100 d-flex justify-content-between align-items-center"
              type="button"
              id="btnFiltroEstatus"
              data-bs-toggle="dropdown"
              aria-expanded="false">
              <span>
                <i data-feather="flag" class="me-1" style="width:16px;height:16px;"></i>
                <span id="txtFiltroEstatus">Estatus</span>
              </span>
              <i data-feather="chevron-down" style="width:16px;height:16px;"></i>
            </button>

            <div class="dropdown-menu p-2 shadow filtro-estatus-menu" aria-labelledby="btnFiltroEstatus">
              <div class="px-2 pb-2 border-bottom mb-2">
                <strong class="small text-muted">Filtrar por estatus</strong>
                <div class="small text-muted">Puedes seleccionar uno o varios</div>
              </div>

              <?php if (!empty($data['estatus'])): ?>
                <?php foreach ($data['estatus'] as $st): ?>
                  <label class="dropdown-item d-flex align-items-center gap-2 filtro-estatus-item">
                    <input
                      type="checkbox"
                      class="form-check-input m-0 chkFiltroEstatus"
                      name="maritimo_ferro_filtroEstatus[]"
                      value="<?= (int)$st['id_estatus']; ?>">

                    <span
                      class="estatus-dot"
                      style="background-color: <?= !empty($st['color_hex']) ? htmlspecialchars($st['color_hex'], ENT_QUOTES, 'UTF-8') : '#6c757d'; ?>;">
                    </span>

                    <span>
                      <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              <?php endif; ?>

              <div class="border-top mt-2 pt-2 px-2">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnLimpiarFiltroEstatus">
                  Limpiar estatus
                </button>
              </div>
            </div>
          </div>

          <!-- Filtro Cliente múltiple -->
          <div class="dropdown filtro-estatus-wrapper" style="max-width: 260px;">
            <button
              class="btn btn-light border w-100 d-flex justify-content-between align-items-center"
              type="button"
              id="btnFiltroCliente"
              data-bs-toggle="dropdown"
              aria-expanded="false">
              <span>
                <i data-feather="users" class="me-1" style="width:16px;height:16px;"></i>
                <span id="txtFiltroCliente">Cliente</span>
              </span>
              <i data-feather="chevron-down" style="width:16px;height:16px;"></i>
            </button>

            <div class="dropdown-menu p-2 shadow filtro-estatus-menu" aria-labelledby="btnFiltroCliente">
              <div class="px-2 pb-2 border-bottom mb-2">
                <strong class="small text-muted">Filtrar por cliente</strong>
                <div class="small text-muted">Puedes seleccionar uno o varios</div>
              </div>

              <?php if (!empty($data['clientes'])): ?>
                <?php foreach ($data['clientes'] as $c): ?>
                  <label class="dropdown-item d-flex align-items-center gap-2 filtro-estatus-item">
                    <input
                      type="checkbox"
                      class="form-check-input m-0 chkFiltroCliente"
                      name="maritimo_ferro_filtroCliente[]"
                      value="<?= (int)$c['id_cliente']; ?>">

                    <span class="ml-4">
                      <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              <?php endif; ?>

              <div class="border-top mt-2 pt-2 px-2">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnLimpiarFiltroCliente">
                  Limpiar cliente
                </button>
              </div>
            </div>
          </div>
          <!-- Filtro Transportista múltiple -->
          <div class="dropdown filtro-estatus-wrapper" style="max-width: 260px;">
            <button
              class="btn btn-light border w-100 d-flex justify-content-between align-items-center"
              type="button"
              id="btnFiltroTransportista"
              data-bs-toggle="dropdown"
              aria-expanded="false">
              <span>
                <i data-feather="truck" class="me-1" style="width:16px;height:16px;"></i>
                <span id="txtFiltroTransportista">Transportista</span>
              </span>
              <i data-feather="chevron-down" style="width:16px;height:16px;"></i>
            </button>

            <div class="dropdown-menu p-2 shadow filtro-estatus-menu" aria-labelledby="btnFiltroTransportista">
              <div class="px-2 pb-2 border-bottom mb-2">
                <strong class="small text-muted">Filtrar por transportista</strong>
                <div class="small text-muted">Puedes seleccionar uno o varios</div>
              </div>

              <?php if (!empty($data['transportistas'])): ?>
                <?php foreach ($data['transportistas'] as $t): ?>
                  <label class="dropdown-item d-flex align-items-center gap-2  filtro-estatus-item">
                    <input
                      type="checkbox"
                      class="form-check-input m-0 chkFiltroTransportista"
                      name="maritimo_ferro_filtroTransportista[]"
                      value="<?= (int)$t['id_transportista']; ?>">

                    <span class="ml-4">
                      <?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              <?php endif; ?>

              <div class="border-top mt-2 pt-2 px-2">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnLimpiarFiltroTransportista">
                  Limpiar transportista
                </button>
              </div>
            </div>
          </div>

          <!-- Filtro Broker múltiple -->
          <div class="dropdown filtro-estatus-wrapper" style="max-width: 260px;">
            <button
              class="btn btn-light border w-100 d-flex justify-content-between align-items-center"
              type="button"
              id="btnFiltroBroker"
              data-bs-toggle="dropdown"
              aria-expanded="false">
              <span>
                <i data-feather="briefcase" class="me-1" style="width:16px;height:16px;"></i>
                <span id="txtFiltroBroker">Broker</span>
              </span>
              <i data-feather="chevron-down" style="width:16px;height:16px;"></i>
            </button>

            <div class="dropdown-menu p-2 shadow filtro-estatus-menu" aria-labelledby="btnFiltroBroker">
              <div class="px-2 pb-2 border-bottom mb-2">
                <strong class="small text-muted">Filtrar por broker</strong>
                <div class="small text-muted">Puedes seleccionar uno o varios</div>
              </div>

              <?php if (!empty($data['brokers'])): ?>
                <?php foreach ($data['brokers'] as $b): ?>
                  <label class="dropdown-item d-flex align-items-center gap-2 filtro-estatus-item">
                    <input
                      type="checkbox"
                      class="form-check-input m-0 chkFiltroBroker"
                      name="maritimo_ferro_filtroBroker[]"
                      value="<?= (int)$b['id_broker']; ?>">

                    <span class="ml-4">
                      <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              <?php endif; ?>

              <div class="border-top mt-2 pt-2 px-2">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnLimpiarFiltroBroker">
                  Limpiar broker
                </button>
              </div>
            </div>
          </div>
          <!-- Medida contenedor -->
          <select id="maritimo_ferro_filtroMedidaContenedor" name="maritimo_ferro_filtroMedidaContenedor"
            class="form-control" style="max-width:240px;">
            <option value="">Medida del Contenedor (Todas)</option>
            <option value="20GP">20GP</option>
            <option value="20HQ">20HQ</option>
            <option value="40GP">40GP</option>
            <option value="40HC">40HC</option>
            <option value="40HQ">40HQ</option>
            <option value="45HC">45HC</option>
            <option value="45HQ">45HQ</option>
          </select>

        </div>



        <!-- ===== FILA 3: Paginación + export + búsqueda global (último) ===== -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">


          <!-- Export -->
          <div class="d-flex align-items-center gap-2 ms-auto">
            <button class="btn btn-sm btn-outline-success" id="operaciones_mar_ExportarExcel" type="button">
              <i data-feather="file-text" class="me-1"></i> Excel
            </button>
            <button class="btn btn-sm btn-outline-warning" id="operaciones_mar_ExportarPDF" type="button">
              <i data-feather="file" class="me-1"></i> PDF
            </button>
          </div>




          <!-- Paginación -->
          <div class="d-flex align-items-end   gap-2">
            <label for="maritimo_ferro_perPage" class="mb-0 small text-muted">Mostrar</label>
            <select id="maritimo_ferro_perPage" name="maritimo_ferro_perPage" class="form-control" style="width:90px;">

              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
              <option value="500">500</option>
              <option value="1000">1000</option>
              <option value="10000000">Todos</option>

            </select>
            <span class="small text-muted">por página</span>
          </div>
        </div>

      </div>

      <!-- Tabla -->
      <!-- Scroll horizontal superior (sincronizado con la tabla) -->
      <div class="mf-top-scroll" id="mf_topScroll">
        <div class="mf-top-scroll-inner" id="mf_topScrollInner"></div>
      </div>
      <div class="mf-table-wrap position-relative">

        <table class="table align-middle mf-table " id="operaciones_mar_TablaExportar">
          <thead class="table-success">
            <tr class="text-center">
              <th class="col-md sticky-col sticky-col-1">Código</th>
              <th class="col-md sticky-col sticky-col-2">Contenedor</th>
              <th class="col-md">Subtipo</th>
              <th>ETD</th>
              <th>ETA</th>



              <th>Peso</th>
              <th>Bultos</th>
              <th>Medida</th>
              <th>Mercancia</th>

              <th class="col-ellipsis col-lg">Transportista</th>
              <th class="col-ellipsis col-lg">Broker</th>

              <th class="col-md">BL</th>
              <th class="col-ellipsis col-lg">Puerto</th>

              <th class="col-wrap col-xl">Cliente</th>

              <th>Estatus</th>
              <th>ISF</th>
              <th class="col-md">Cita en Puerto</th>

              <th class="col-ellipsis col-lg">Ubicación Actual</th>
              <th class="col-wrap col-xl">Observaciones</th>


              <th class="col-md">Caja/Ferro</th>
              <th class="col-ellipsis col-md">Destino</th>
              <th class="col-md">Fecha Salida</th>
              <th class="col-ellipsis col-md">Ubicación Actual Caja/Ferro</th>
              <th class="col-ellipsis col-md">Transportista Caja/Ferro</th>

              <th class="col-actions">Acciones</th>
            </tr>
          </thead>

          <tbody id="maritimo_ferro_tablaBody"></tbody>
        </table>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="maritimo_ferro_metaResumen">Mostrando 0–0 de 0</span>
          </div>
          <nav aria-label="Paginación de operaciones">
            <ul id="maritimo_ferro_paginacion" class="pagination pagination-sm mb-0"></ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: Operación Marítimo-Ferroviaria -->
<div class="modal fade" id="modalMaritimoFerro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i data-feather="plus-square" class="me-2"></i>
          <span id="tituloModalOperacion_mf">Nueva Operación Marítimo-Ferroviaria</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formOperacionMaritimoFerro" autocomplete="off">
          <input type="hidden" id="id_operacion_mf" name="id_operacion_mf" value="">

          <div class="row g-3">

            <!-- SUBTIPO -->
            <div class="col-md-4">
              <label class="form-label">Subtipo</label>
              <select id="subtipoOperacion_mf" name="subtipo_operacion_id_mf" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($data['subtipos'])): ?>
                  <?php foreach ($data['subtipos'] as $st): ?>
                    <option value="<?= (int)$st['id_subtipo']; ?>">
                      <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Número de Operación -->
            <div class="col-md-4">
              <label class="form-label">Número de Operación</label>
              <input type="text" id="numeroOperacion_mf" name="numero_operacion_mf" class="form-control"
                placeholder="JL-61">
              <small id="folioHelp_mf" class="text-muted d-block mt-1">Folio Preliminar</small>
            </div>

            <!-- Estado -->
            <div class="col-md-4">
              <label class="form-label">Estado</label>
              <select id="estatusId_mf" name="estatus_id_mf" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($data['estatus'])): ?>
                  <?php foreach ($data['estatus'] as $es): ?>
                    <option value="<?= (int)$es['id_estatus']; ?>">
                      <?= htmlspecialchars($es['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Fechas -->
            <div class="col-md-3">
              <label class="form-label">ETD</label>
              <input type="date" id="etd_mf" name="etd_mf" class="form-control form-control-lg">
            </div>

            <div class="col-md-3">
              <label class="form-label">ETA</label>
              <input type="date" id="eta_mf" name="eta_mf" class="form-control form-control-lg">
            </div>



            <div class="col-md-3">
              <label class="form-label">Cita en Puerto</label>
              <input type="date" id="cita_puerto" name="cita_puerto" class="form-control form-control-lg">
            </div>
            <div class="col-md-3 d-flex flex-column">
              <label class="form-label">ISF</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="chkIsf" name="isf" value="1">
              </div>
            </div>

            <!-- BL -->
            <div class="col-md-3">
              <label class="form-label">BL</label>
              <input type="text" id="numeroBL_mf" name="numero_bl_mf" class="form-control" autocomplete="off"
                maxlength="40" pattern="[A-Za-z0-9]+"
                title="Solo letras y números, sin espacios ni caracteres especiales.">
            </div>

            <!-- Puerto -->
            <div class="col-md-3">
              <label class="form-label">Puerto de Arribo</label>
              <select id="puertoArribo_mf" name="puerto_arribo_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['puertos'])): ?>
                  <?php foreach ($data['puertos'] as $p): ?>
                    <option value="<?= (int)$p['id_puerto']; ?>">
                      <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Cliente -->
            <div class="col-md-6 position-relative">
              <label class="form-label">Cliente</label>
              <input type="hidden" id="clienteId_mf" name="cliente_id_mf">
              <input type="text" id="clienteNombre_mf" class="form-control"
                placeholder="Escribe para buscar cliente...">
              <div id="sugerenciasCliente_mf" class="list-group"
                style="position:absolute; z-index:1055; width:100%; display:none;"></div>
            </div>

            <!-- CONTENEDOR ÚNICO -->
            <div class="col-12">
              <div id="contenedoresRepeater_mf" class="vstack gap-2">
                <div class="contenedor-item position-relative border rounded p-2">
                  <input type="hidden" class="contenedor-id_mf">

                  <div class="row g-2 align-items-end">

                    <div class="col-md-5 position-relative">
                      <label class="form-label">Contenedor Maritimo</label>
                      <input type="text" class="form-control contenedor-input_mf" placeholder="Ej. MSKU1234567">
                      <div class="list-group sugerencias-contenedor_mf"
                        style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                    </div>

                    <div class="col-md-2">
                      <label class="form-label">Bultos</label>
                      <input type="number" min="0" step="1" class="form-control contenedor-bultos_mf" placeholder="0">
                    </div>

                    <!-- PESO a un lado del tipo contenedor 
                    <div class="col-md-3">
                      <label class="form-label">Peso Total (Kg)</label>
                      <input type="number"

                        id="pesoOperacion_mf"
                        class="form-control"
                        placeholder="0.00"
                        name="peso_operacion_mf">
                    </div>-->

                  </div>
                </div>
              </div>

              <!-- Template (compatibilidad si tu JS lo referencia) --->
              <template id="contenedorTemplate_mf">
                <div class="contenedor-item position-relative border rounded p-2">
                  <input type="hidden" class="contenedor-id_mf">
                  <div class="row g-2 align-items-end">
                    <div class="col-md-5 position-relative">
                      <label class="form-label">Contenedor Maritimo</label>
                      <input type="text" class="form-control contenedor-input_mf" placeholder="Ej. MSKU1234567">
                      <div class="list-group sugerencias-contenedor_mf"
                        style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Bultos</label>
                      <input type="number" min="0" step="1" class="form-control contenedor-bultos_mf" placeholder="0">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Medida Contenedor</label>
                      <select class="form-control contenedor-tipo_mf" name="tipo_contenedor_mf[]">

                        <option value="">Seleccione...</option>
                        <option value="20GP">20GP</option>
                        <option value="20HQ">20HQ</option>
                        <option value="40GP">40GP</option>
                        <option value="40HC">40HC</option>
                        <option value="40HQ">40HQ</option>
                        <option value="45HC">45HC</option>
                        <option value="45HQ">45HQ</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Peso (Kg)</label>
                      <input type="number" min="0" class="form-control pesoOperacion_mf" name="peso_operacion_mf"
                        placeholder="0.00">

                    </div>
                  </div>
                </div>
              </template>
            </div>
            <!-- Naviera -->
            <div class="col-md-2" id="campoNaviera_mf">
              <label class="form-label">Naviera</label>
              <select id="navieraId_mf" name="naviera_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['navieras'])): ?>
                  <?php foreach ($data['navieras'] as $n): ?>
                    <option value="<?= (int)$n['id_naviera']; ?>">
                      <?= htmlspecialchars($n['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <!-- FORWARDER + SHIPPER + BROKER + TRANSPORTISTA en el MISMO RENGLÓN -->
            <div class="col-md-2" id="campoForwarder_mf">
              <label class="form-label">Forwarder</label>
              <select id="forwarderId_mf" name="forwarder_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['forwarders'])): ?>
                  <?php foreach ($data['forwarders'] as $fw): ?>
                    <option value="<?= (int)$fw['id_forwarder']; ?>">
                      <?= htmlspecialchars($fw['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Shipper</label>
              <select id="shipperId_mf" name="shipper_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['shippers'])): ?>
                  <?php foreach ($data['shippers'] as $s): ?>
                    <option value="<?= (int)$s['id_shipper']; ?>">
                      <?= htmlspecialchars($s['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Broker</label>
              <select id="brokerId_mf" name="broker_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['brokers'])): ?>
                  <?php foreach ($data['brokers'] as $b): ?>
                    <option value="<?= (int)$b['id_broker']; ?>">
                      <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Transportista</label>
              <select id="transportistaId_mf" name="transportista_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['transportistas'])): ?>
                  <?php foreach ($data['transportistas'] as $t): ?>
                    <option value="<?= (int)$t['id_transportista']; ?>">
                      <?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <!-- Descripción Mercancia -->
            <div class="col-md-12">
              <label class="form-label">Descripción Mercancia</label>
              <textarea id="descripcion_mercancia_mf" name="descripcion_mercancia_mf"
                class="form-control input-uppercase" rows="2" placeholder=""></textarea>
            </div>
            <!-- Notas -->
            <div class="col-md-12">
              <label class="form-label">Notas</label>
              <textarea id="notas_mf" name="notas_mf" class="form-control input-uppercase" rows="2"
                placeholder="Observaciones generales"></textarea>
            </div>
            <div class="col-md-12">
              <label class="form-label">Ubicación Actual</label>
              <input
                type="text"
                id="ubicacionActual_mf"
                name="ubicacion_actual_mf"
                class="form-control form-control input-uppercase"
                maxlength="250"
                placeholder="">
            </div>
          </div><!-- row -->
        </form>
      </div><!-- modal-body -->

      <div class="modal-footer d-flex justify-content-between">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i data-feather="x-circle" class="me-1"></i> Cancelar
          </button>
          <button type="button" id="btnGuardarOperacion_mf" class="btn btn-success" disabled>
            <i data-feather="save" class="me-1"></i> Guardar
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- =========================================================
     MODAL: Asignar Caja/Ferro a Operación Marítima (N↔N)
     - NO muestra FO-id (interno)
     - Identificador visible: numero_ferro / caja (contenedor físico)
========================================================= -->

<!-- Modal: Asignar Ferro/Caja -->
<div class="modal fade" id="modalAsignarFerroCaja" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <!-- ===== Header ===== -->
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="truck"></i>
          <span>Asignar Caja/Ferro</span>
          <span class="badge bg-light text-dark ms-2" id="asigFerro_badgeCodigo">—</span>


        </h5>
        <div class="d-flex align-items-center gap-2 ms-3">
          <label class="mb-0 small">Bultos restantes:</label>
          <span class="badge bg-primary" id="bultosRestantesOperacion">—</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- ===== Body ===== -->
      <div class="modal-body">

        <!-- Hidden refs (los setea el JS cuando abres el modal) -->
        <input type="hidden" id="asigFerro_operacionId" name="asigFerro_operacionId" value="">
        <input type="hidden" id="asigFerro_operacionCodigo" name="asigFerro_operacionCodigo" value="">

        <!-- NUEVO: refs del ferro/caja seleccionado (los setea el JS al seleccionar una fila) -->
        <input type="hidden" id="asigFerro_ferroFisicoId" name="asigFerro_ferroFisicoId" value="">
        <input type="hidden" id="asigFerro_asignacionId" name="asigFerro_asignacionId" value="">

        <div class="row g-3">

          <!-- =======================
               Izquierda: Vincular
          ======================== -->
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">

                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                  <div class="fw-semibold">
                    <i data-feather="link" class="me-1"></i>
                    Vincular esta operación a un Ferro/Caja
                  </div>
                </div>

                <!-- Form de captura -->
                <form id="asigFerro_formVincular" name="asigFerro_formVincular" autocomplete="off">
                  <div class="row g-2">

                    <!-- Ferro/Caja -->
                    <div class="col-md-6 position-relative">
                      <label class="form-label">Ferro/Caja</label>
                      <input type="text" class="form-control input-uppercase" id="asigFerro_inputNumero"
                        name="asigFerro_numero" placeholder="Ej. FXEU12345 / CAJA-001" autocomplete="off">
                      <div id="asigFerro_sugerencias" class="list-group shadow-sm"
                        style="position:absolute; z-index:1060; width:100%; display:none;"></div>
                    </div>

                    <!-- Empresa transportista -->
                    <div class="col-md-6">
                      <label class="form-label">Empresa transportista</label>
                      <select id="asigFerro_empresaTransportista" name="asigFerro_empresaTransportista"
                        class="form-control">
                        <option value="">Seleccione...</option>
                        <?php if (!empty($data['transportistas'])): ?>
                          <?php foreach ($data['transportistas'] as $et): ?>
                            <option value="<?= (int)$et['id_transportista']; ?>">
                              <?= htmlspecialchars($et['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>

                    <!-- Bultos -->
                    <div class="col-md-6">
                      <label class="form-label">Bultos asignados</label>
                      <input type="number" min="0" step="1" class="form-control" id="asigFerro_inputBultos"
                        name="asigFerro_bultos" placeholder="0">
                    </div>

                    <!-- Destino -->
                    <div class="col-md-6">
                      <label class="form-label">Destino</label>

                      <select id="asigFerro_destino" name="asigFerro_destino" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php if (!empty($data['ciudades'])): ?>
                          <?php foreach ($data['ciudades'] as $c): ?>
                            <option value="<?= (int)$c['id_ciudad']; ?>">
                              <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>

                    <!-- Fechas -->
                    <div class="col-md-6">
                      <label class="form-label">Fecha carga</label>
                      <input type="date" class="form-control" id="asigFerro_inputFechaCarga"
                        name="asigFerro_fecha_carga">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Fecha salida</label>
                      <input type="date" class="form-control" id="asigFerro_inputFechaSalida"
                        name="asigFerro_fecha_salida">
                    </div>

                    <!-- Notas -->
                    <div class="col-12">
                      <label class="form-label">Notas (opcional)</label>
                      <input type="text" class="form-control input-uppercase" id="asigFerro_inputNotas"
                        name="asigFerro_notas" maxlength="255" placeholder="Ej. Sale por patio 3 / Transfer a SD">
                    </div>

                    <!-- Acciones -->
                    <div class="col-12 d-flex gap-2 pt-2">
                      <button type="button" class="btn btn-success" id="asigFerro_btnVincular">
                        <i data-feather="check-circle" class="me-1"></i> Vincular
                      </button>
                      <button type="button" class="btn btn-outline-secondary" id="asigFerro_btnLimpiar">
                        <i data-feather="x" class="me-1"></i> Limpiar
                      </button>
                    </div>

                  </div>
                </form>

                <hr class="my-3">

                <!-- Lista de ferros/cajas vinculados a esta operación -->
                <div class="d-flex justify-content-between align-items-center mb-2 ">
                  <div class="fw-semibold">
                    <i data-feather="list" class="me-1"></i> Ferros/Cajas de esta operación
                  </div>
                  <span class="text-muted small" id="asigFerro_countFerros">0</span>
                </div>

                <div class="table-responsive ">
                  <table class="table table-sm table-hover  align-middle mb-0 p-3">
                    <thead class="table-light">
                      <tr class="text-center">
                        <th class="text-start">Ferro/Caja</th>
                        <th style="width:120px;">Transportista</th>
                        <th style="width:120px;">Bultos</th>
                        <th style="width:140px;">F. carga</th>
                        <th style="width:140px;">F. salida</th>
                        <th style="width:90px;">Acción</th>
                      </tr>
                    </thead>
                    <tbody id="asigFerro_tbFerrosOperacion">
                      <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                          Sin vínculos todavía.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>

          <!-- =======================
               Derecha: Ops + Trazabilidad (NUEVO)
          ======================== -->
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">

                <div class="d-flex justify-content-between align-items-center gap-2">
                  <div class="fw-semibold">
                    <i data-feather="layers" class="me-1"></i> Operaciones en el Ferro/Caja seleccionado
                  </div>
                  <span class="badge bg-light text-dark" id="asigFerro_badgeFerroSel">—</span>
                </div>

                <div class="text-muted small mt-1">
                  Selecciona un Ferro/Caja desde la lista de la izquierda para ver su consolidado y registrar
                  trazabilidad.
                </div>

                <hr class="my-3">

                <!-- Tabla de operaciones del ferro/caja -->
                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr class="text-center">
                        <th style="width:140px;">Código</th>
                        <th class="text-start">Cliente</th>
                        <th style="width:120px;">Contenedor</th>
                        <th style="width:110px;">Bultos Totales</th>
                        <th style="width:110px;">Bultos Enviados</th>
                      </tr>
                    </thead>
                    <tbody id="asigFerro_tbOpsEnFerro">
                      <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                          Selecciona un Ferro/Caja de la lista izquierda.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- ===== NUEVO: Panel Trazabilidad ===== -->
                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center">
                  <div class="fw-semibold">
                    <i data-feather="map-pin" class="me-1"></i> Trazabilidad del Ferro/Caja
                  </div>
                  <span class="text-muted small" id="asigFerro_trackHint">—</span>
                </div>

                <!-- Resumen Origen/Destino/Actual -->
                <div class="row g-2 mt-2">
                  <div class="col-md-4">
                    <label class="form-label small mb-1">Origen (Puerto)</label>
                    <input type="text" class="form-control form-control-sm" id="asigFerro_trackOrigen"
                      name="asigFerro_trackOrigen" value="" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small mb-1">Ubicación actual</label>
                    <input type="text" class="form-control form-control-sm" id="asigFerro_trackUltima"
                      name="asigFerro_trackUltima" value="" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small mb-1">Destino</label>
                    <input type="text" class="form-control form-control-sm" id="asigFerro_trackDestino"
                      name="asigFerro_trackDestino" value="" readonly>
                  </div>

                </div>

                <!-- Form registro trazabilidad -->
                <form id="asigFerro_formTrazabilidad" name="asigFerro_formTrazabilidad" class="mt-3" autocomplete="off">
                  <div class="row g-2 align-items-end">

                    <!-- Ubicación actual -->
                    <div class="col-md-6">
                      <label class="form-label">Ubicación actual</label>
                      <select id="asigFerro_trackUbicacionId" name="asigFerro_trackUbicacionId" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php if (!empty($data['ciudades'])): ?>
                          <?php foreach ($data['ciudades'] as $c): ?>
                            <option value="<?= (int)$c['id_ciudad']; ?>">
                              <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>

                    </div>

                    <!-- Fecha/Hora -->
                    <div class="col-md-6">
                      <label class="form-label">Fecha</label>
                      <input type="date" class="form-control" id="asigFerro_trackFechaHora"
                        name="asigFerro_trackFechaHora">
                    </div>

                    <!-- Referencia / Guía -->
                    <div class="col-md-12">
                      <label class="form-label">Direccion(opcional)</label>
                      <input type="text" class="form-control" id="asigFerro_trackReferencia"
                        name="asigFerro_trackReferencia" maxlength="80" placeholder="">
                    </div>

                    <!-- Notas -->
                    <div class="col-12">
                      <label class="form-label">Notas</label>
                      <textarea class="form-control" id="asigFerro_trackNotas" name="asigFerro_trackNotas" rows="2"
                        maxlength="255" placeholder=""></textarea>
                    </div>

                    <!-- Acciones trazabilidad -->
                    <div class="col-12 d-flex gap-2 pt-2">
                      <button type="button" class="btn btn-primary" id="asigFerro_btnGuardarTrazabilidad" disabled>
                        <i data-feather="save" class="me-1"></i> Guardar ubicación
                      </button>
                      <button type="button" class="btn btn-outline-secondary" id="asigFerro_btnLimpiarTrazabilidad"
                        disabled>
                        <i data-feather="x" class="me-1"></i> Limpiar
                      </button>
                    </div>
                  </div>
                </form>

              </div>
            </div>
          </div>

        </div><!-- row -->
      </div><!-- modal-body -->

      <!-- ===== Footer ===== -->
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cerrar
        </button>
      </div>

    </div>
  </div>
</div>

<script
  src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/Operaciones_Maritimas/operaciones_llenado_catalogo.js">
</script>
<script
  src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/Operaciones_Maritimas/operaciones_registrar.js">
</script>
<script
  src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/AsignacionFerros/asignacion_ferro_catalogo.js">
</script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/AsignacionFerros/trazabilidad_catalogo.js">
</script>
<script>
  // Mayúsculas automáticas (MF) para contenedores (incluye filas agregadas por template)
  document.getElementById("contenedoresRepeater_mf").addEventListener("input", function(e) {
    if (e.target.classList.contains("contenedor-input_mf")) {
      e.target.value = e.target.value.toUpperCase();
    }
  });
  document.getElementById("contenedoresRepeater_mf").addEventListener("input", function(e) {
    if (e.target.classList.contains("contenedor-bultos_mf")) {
      // Enteros >= 0
      let v = e.target.value === "" ? "" : String(parseInt(e.target.value, 10));
      if (v === "NaN") v = "";
      if (v !== "" && parseInt(v, 10) < 0) v = "0";
      e.target.value = v;
    }
  });
</script>
<script>
  (function() {
    "use strict";

    const topScroll = document.getElementById("mf_topScroll");
    const topInner = document.getElementById("mf_topScrollInner");
    const wrap = document.querySelector(".mf-table-wrap");
    const table = document.getElementById("operaciones_mar_TablaExportar");

    if (!topScroll || !topInner || !wrap || !table) return;

    let syncing = false;

    function syncWidths() {
      // El ancho real scrolleable es el scrollWidth de la tabla (o del wrap)
      const w = Math.max(table.scrollWidth, wrap.scrollWidth);
      topInner.style.width = w + "px";

      // Si no hay overflow horizontal, ocultamos la barra de arriba
      const hasOverflow = (wrap.scrollWidth > wrap.clientWidth);
      topScroll.style.display = hasOverflow ? "block" : "none";
    }

    // scroll top -> wrap
    topScroll.addEventListener("scroll", () => {
      if (syncing) return;
      syncing = true;
      wrap.scrollLeft = topScroll.scrollLeft;
      syncing = false;
    });

    // scroll wrap -> top
    wrap.addEventListener("scroll", () => {
      if (syncing) return;
      syncing = true;
      topScroll.scrollLeft = wrap.scrollLeft;
      syncing = false;
    });

    // Ajustar al cargar
    syncWidths();

    // Ajustar si cambia tamaño de ventana
    window.addEventListener("resize", syncWidths);

    // Ajustar si tu JS vuelve a renderizar la tabla (paginación/filtros)
    // Usa MutationObserver para recalcular el ancho al cambiar tbody
    const tbody = document.getElementById("maritimo_ferro_tablaBody");
    if (tbody) {
      const obs = new MutationObserver(() => syncWidths());
      obs.observe(tbody, {
        childList: true,
        subtree: true
      });
    }
  })();
</script>
<script>
  function forzarMayusculas(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener("input", function() {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toUpperCase();
      this.setSelectionRange(start, end);
    });
  }

  // Uso
  forzarMayusculas("asigFerro_inputNumero");
  forzarMayusculas("ubicacionActual_mf");
</script>