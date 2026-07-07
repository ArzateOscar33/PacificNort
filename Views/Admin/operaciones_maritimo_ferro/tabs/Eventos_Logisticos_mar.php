<style>
  /* =========================================================
     EVENTOS MARÍTIMOS (MAR)
     ANCHOS / LEGIBILIDAD / EDICIÓN TIPO EXCEL
     ========================================================= */

  #tablaEventosMar {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    width: 100%;
  }

  #tablaEventosMar th,
  #tablaEventosMar td {
    padding: .55rem .75rem;
    white-space: nowrap;
    vertical-align: middle;
  }

  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  :root {
    --evmar-col-op-w: 170px;
    --evmar-col-ctn-w: 200px;
    --evmar-col-cli-w: 240px;
    --evmar-col-eta-w: 150px;
    --evmar-col-evt-w: 145px;
  }

  /* Columnas fijas */
  #tablaEventosMar th:nth-child(1),
  #tablaEventosMar td:nth-child(1) {
    min-width: var(--evmar-col-op-w);
    width: var(--evmar-col-op-w);
  }

  #tablaEventosMar th:nth-child(2),
  #tablaEventosMar td:nth-child(2) {
    min-width: var(--evmar-col-ctn-w);
    width: var(--evmar-col-ctn-w);
  }

  #tablaEventosMar th:nth-child(3),
  #tablaEventosMar td:nth-child(3) {
    min-width: var(--evmar-col-cli-w);
    width: var(--evmar-col-cli-w);
  }

  #tablaEventosMar th:nth-child(4),
  #tablaEventosMar td:nth-child(4) {
    min-width: var(--evmar-col-eta-w);
    width: var(--evmar-col-eta-w);
  }

  /* Dinámicas: eventos marítimos desde columna 5 */
  #tablaEventosMar thead th:nth-child(n+5),
  #tablaEventosMar tbody td:nth-child(n+5) {
    min-width: var(--evmar-col-evt-w);
    width: var(--evmar-col-evt-w);
    text-align: center;
  }

  /* =========================================================
     CELDAS EDITABLES TIPO EXCEL
     Estas clases las usará el JS nuevo.
     ========================================================= */

  .evmar-date-cell {
    cursor: cell;
    position: relative;
    transition:
      background-color .15s ease,
      box-shadow .15s ease,
      color .15s ease;
  }

  .evmar-date-cell:hover {
    background-color: rgba(13, 110, 253, .08);
    box-shadow: inset 0 0 0 1px rgba(13, 110, 253, .35);
  }

  .evmar-date-cell:focus {
    outline: none;
    background-color: rgba(13, 110, 253, .10);
    box-shadow: inset 0 0 0 2px rgba(13, 110, 253, .65);
  }

  .evmar-date-cell.evmar-empty {
    color: #6c757d;
    font-style: italic;
  }

  .evmar-date-cell.evmar-saving {
    background-color: rgba(255, 193, 7, .18);
    box-shadow: inset 0 0 0 2px rgba(255, 193, 7, .55);
  }

  .evmar-date-cell.evmar-saved {
    background-color: rgba(25, 135, 84, .12);
    box-shadow: inset 0 0 0 2px rgba(25, 135, 84, .45);
  }

  .evmar-date-cell.evmar-error {
    background-color: rgba(220, 53, 69, .12);
    box-shadow: inset 0 0 0 2px rgba(220, 53, 69, .55);
  }

  .evmar-date-input {
    width: 100%;
    min-width: 110px;
    height: 30px;
    border: 1px solid #0d6efd;
    border-radius: .375rem;
    padding: .15rem .35rem;
    font-size: .85rem;
    text-align: center;
    outline: none;
  }

  .evmar-date-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .18);
  }

  .evmar-cell-status {
    position: absolute;
    right: 4px;
    bottom: 2px;
    font-size: .65rem;
    line-height: 1;
    opacity: .75;
    pointer-events: none;
  }

  .evmar-help-box {
    border: 1px solid rgba(13, 110, 253, .18);
    background: rgba(13, 110, 253, .04);
    border-radius: .5rem;
    padding: .65rem .85rem;
    color: #495057;
  }

  .evmar-help-box strong {
    color: #0d6efd;
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="file-text" class="me-1"></i> Eventos Marítimos
      </h5>
    </div>

    <div class="card-body">



      <!-- Filtros superiores -->
      <div class="row g-3 align-items-end mb-3">

        <div class="col-md-3">
          <label for="eventosFiltroFerro" class="form-label mb-1">Contenedor marítimo</label>
          <input
            type="text"
            id="eventosFiltroFerro"
            class="form-control"
            placeholder="Escribe para buscar"
            autocomplete="off">
        </div>

        <div class="col-md-3">
          <label for="eventosFiltroCliente" class="form-label mb-1">Cliente</label>
          <select class="form-control" id="eventosFiltroCliente" name="eventosFiltroCliente">
            <option value="">Cliente (Todos)</option>
            <?php if (!empty($data['clientes'])): ?>
              <?php foreach ($data['clientes'] as $cl): ?>
                <option value="<?= (int)$cl['id_cliente']; ?>">
                  <?= htmlspecialchars($cl['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="col-md-3 d-none">
          <label for="eventosFiltroOpNombre" class="form-label mb-1">Operación</label>
          <div class="position-relative">
            <input type="hidden" id="eventosFiltroOpId">
            <input
              type="text"
              id="eventosFiltroOpNombre"
              class="form-control"
              placeholder="Escribe para buscar operación"
              autocomplete="off">
            <div
              id="eventosFiltroOpSugerencias"
              class="list-group"
              style="position:absolute; z-index:1061; width:100%; display:none;">
            </div>
          </div>
          <div class="form-text" id="eventosFiltroOpMeta"></div>
        </div>

        <input type="hidden" id="eventosFiltroContMarId">

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-sm btn-outline-success w-100" id="btnExportarExcelEventosLogisticosMar">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning w-100" id="btnExportarPDFEventosLogisticosMar">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <div class="col-12 d-flex align-items-center justify-content-end gap-2">
          <label for="evMarPerPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="evMarPerPage" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="200">200</option>
            <option value="500">500</option>
            <option value="1000">1000</option>
            <option value="10000000">Todos</option>
          </select>
          <span class="small text-muted">por página</span>
        </div>
      </div>

      <!-- Tabla de eventos marítimos -->
      <div class="table-responsive">
        <table class="table table-hover table-bordered-pacific align-middle" id="tablaEventosMar">
          <thead class="table-primary">
            <tr id="theadEventosMar" class="text-center">
              <th>Operación</th>
              <th>Contenedor marítimo</th>
              <th>Cliente</th>
              <th>Arribo al puerto</th>
              <!-- JS: columnas dinámicas de eventos marítimos -->
            </tr>
          </thead>

          <tbody id="tbodyEventosMar">
            <tr>
              <td colspan="4" class="text-center text-muted py-3">
                Cargando eventos marítimos...
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginación + resumen -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="evMarMetaResumen">Mostrando 0–0 de 0</span>
          </div>

          <nav aria-label="Paginación de eventos marítimos">
            <ul id="evMarPaginacion" class="pagination pagination-sm mb-0">
              <!-- JS -->
            </ul>
          </nav>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  if (window.feather) {
    feather.replace();
  }
</script>

<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/eventos_logisticos_mar.js"></script>