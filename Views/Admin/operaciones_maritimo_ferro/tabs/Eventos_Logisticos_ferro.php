<style>
  /* =========================================================
   EVENTOS TERRESTRES (FER) — ANCHOS / LEGIBILIDAD / OBSERVACIONES
   ========================================================= */

  #tablaEventosFer {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
  }

  #tablaEventosFer th,
  #tablaEventosFer td {
    padding: .55rem .75rem;
    white-space: nowrap;
    vertical-align: middle;
  }

  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  :root {
    --evfer-col-op-w: 170px;
    /* Operación Marítima */

    --evfer-col-ctn-w: 190px;
    /* Contenedor Marítimo */

    --evfer-col-cli-w: 240px;
    /* Cliente */

    --evfer-col-des-w: 190px;
    /* Destino */

    --evfer-col-tra-w: 200px;
    /* Transportista */

    --evfer-col-fer-w: 180px;
    /* Caja/Ferro */

    --evfer-col-obs-w: 220px;
    /* Observaciones */

    --evfer-col-evt-w: 140px;
    /* Cada evento dinámico */
  }

  /* Fijas */
  #tablaEventosFer th:nth-child(1),
  #tablaEventosFer td:nth-child(1) {
    min-width: var(--evfer-col-op-w);
    width: var(--evfer-col-op-w);
  }

  #tablaEventosFer th:nth-child(2),
  #tablaEventosFer td:nth-child(2) {
    min-width: var(--evfer-col-ctn-w);
    width: var(--evfer-col-ctn-w);
  }

  #tablaEventosFer th:nth-child(3),
  #tablaEventosFer td:nth-child(3) {
    min-width: var(--evfer-col-cli-w);
    width: var(--evfer-col-cli-w);
  }

  #tablaEventosFer th:nth-child(4),
  #tablaEventosFer td:nth-child(4) {
    min-width: var(--evfer-col-des-w);
    width: var(--evfer-col-des-w);
  }

  #tablaEventosFer th:nth-child(5),
  #tablaEventosFer td:nth-child(5) {
    min-width: var(--evfer-col-tra-w);
    width: var(--evfer-col-tra-w);
  }

  #tablaEventosFer th:nth-child(6),
  #tablaEventosFer td:nth-child(6) {
    min-width: var(--evfer-col-fer-w);
    width: var(--evfer-col-fer-w);
  }

  #tablaEventosFer th:nth-child(7),
  #tablaEventosFer td:nth-child(7) {
    min-width: var(--evfer-col-obs-w);
    width: var(--evfer-col-obs-w);
    max-width: var(--evfer-col-obs-w);
  }

  /* Dinámicas (eventos): desde la columna 8 en adelante */
  #tablaEventosFer thead th:nth-child(n+8),
  #tablaEventosFer tbody td:nth-child(n+8) {
    min-width: var(--evfer-col-evt-w);
    width: var(--evfer-col-evt-w);
    text-align: center;
  }

  /* Celda de observaciones */
  .evfer-observacion-cell {
    white-space: normal !important;
    line-height: 1.25;
    font-size: .82rem;
  }

  .evfer-observacion-text {
    display: block;
    max-width: 190px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .evfer-observacion-vacia {
    color: #6c757d;
    font-style: italic;
  }

  .evfer-observacion-click {
    cursor: pointer;
    transition: background-color .15s ease, box-shadow .15s ease;
  }



  .evfer-observacion-click:hover .evfer-observacion-text {
    text-decoration: underline;
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="file-text" class="me-1"></i> Eventos Terrestres
      </h5>
      <button class="btn btn-light btn-sm d-none" data-bs-toggle="modal" data-bs-target="#modalDetallesLogisticosFer"
        id="btnAbrirModalDetallesFer">
        <i data-feather="plus-circle" class="me-1"></i> Añadir / Editar Evento
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros superiores -->
      <div class="row g-3 align-items-end mb-3">
        <!-- Operación con sugerencias -->
        <div class="col-md-3 d-none">
          <label for="eventosFerFiltroOpNombre" class="form-label mb-1">Operación</label>
          <div class="position-relative">
            <input type="hidden" id="eventosFerFiltroOpId">
            <input type="text" id="eventosFerFiltroOpNombre" class="form-control"
              placeholder="Escribe para buscar" autocomplete="off">
            <div id="eventosFerFiltroOpSugerencias" class="list-group"
              style="position:absolute; z-index:1061; width:100%; display:none;"></div>
          </div>
          <div class="form-text" id="eventosFerFiltroOpMeta"></div>
        </div>

        <div class="col-md-2">
          <label for="eventosFerFiltroContenedor">Contendor maritimo </label>
          <input type="text" id="eventosFerFiltroContenedor" name="eventosFerFiltroContenedor" class="form-control"
            placeholder="Escribe para buscar" autocomplete="off">

        </div>
        <div class="col-md-2">
          <label for="eventosFerFiltroFerro">Ferro / Caja</label>
          <input type="text" id="eventosFerFiltroFerro" name="eventosFerFiltroFerro" class="form-control"
            placeholder="FXEU..." autocomplete="off">
        </div>
        <div class="col-md-2">
          <label for="">Transportista</label>
          <select class="form-control" id="eventosFerFiltroTransportista" name="eventosFerFiltroTransportista">
            <option value="">Transportista (Todos)</option>
            <?php if (!empty($data['transportistas'])): ?>
              <?php foreach ($data['transportistas'] as $st): ?>
                <option value="<?= (int)$st['id_transportista']; ?>">
                  <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label for="">Cliente</label>
          <select class="form-control" id="eventosFerFiltroCliente" name="eventosFerFiltroCliente">
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

        <div class="col-md-3">
          <label for="">Destino</label>
          <select class="form-control" id="eventosFerFiltroDestino" name="eventosFerFiltroDestino">
            <option value="">Destino (Todos)</option>
            <?php if (!empty($data['ciudades'])): ?>
              <?php foreach ($data['ciudades'] as $c): ?>
                <option value="<?= (int)$c['id_ciudad']; ?>">
                  <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- Exportaciones -->
        <div class="row d-flex col-md-12 align-items-end justify-content-end">
          <div class="col-md-1 d-flex">
            <button class="btn btn-sm btn-outline-success " id="btnExportarExcelEventosLogisticosFer">
              <i data-feather="file-text" class="me-1"></i> Excel
            </button>
            <button class="btn btn-sm btn-outline-warning " id="btnExportarPDFEventosLogisticosFer">
              <i data-feather="file" class="me-1"></i> PDF
            </button>
          </div>
        </div>

        <!-- perPage -->
        <div class="col-12 d-flex align-items-center justify-content-end gap-2">
          <label for="evFerPerPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="evFerPerPage" class="form-control" style="width: 90px;">
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

      <!-- Tabla de eventos por contenedor ferroviario -->
      <div class="table-responsive">
        <table class="table table-hover table-bordered-pacific  align-middle" id="tablaEventosFer">
          <thead class="table-primary">
            <tr id="theadEventosFer" class="text-center">
              <!-- Fijos iniciales. El JS reconstruye este encabezado al cargar columnas. -->
              <th class="text-center">Operación Marítima</th>
              <th class="text-center">Contenedor Marítimo</th>
              <th class="text-center">Cliente</th>
              <th class="text-center">Destino</th>
              <th class="text-center">Transportista</th>
              <th class="text-center">Caja / Ferro</th>
              <th class="text-center">Observaciones</th>
              <!-- Dinámicos (JS): una <th> por cada tipo de evento terrestre -->
            </tr>
          </thead>
          <tbody id="tbodyEventosFer">
            <!-- JS: filas dinámicas -->
          </tbody>
        </table>

        <!-- Paginación + resumen -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="evFerMetaResumen">Mostrando 0–0 de 0</span>
          </div>
          <nav aria-label="Paginación de eventos ferroviarios">
            <ul id="evFerPaginacion" class="pagination pagination-sm mb-0">
              <!-- JS -->
            </ul>
          </nav>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- MODAL: Crear / Editar Evento Logístico (Ferroviario) -->
<div class="modal fade" id="modalDetallesLogisticosFer" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">
          <i data-feather="plus-square" class="me-2"></i>
          <span id="modalTituloDetallesFer">Registrar Evento</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formEventosLogisticosFer" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" id="idEventoFer" name="idEventoFer" value="">
          <input type="hidden" id="eventoContenedorTipoFer">
          <div class="row g-3 mb-2">
            <!-- Operación con sugerencias -->
            <div class="col-md-6 d-none">
              <label for="eventoOperacionNombreFer" class="form-label">Operación</label>
              <div class="position-relative">
                <input type="hidden" id="eventoOperacionIdFer" name="eventoOperacionIdFer">
                <input type="text" id="eventoOperacionNombreFer" class="form-control"
                  placeholder="Escribe para buscar (ej. JL-FER-05)" autocomplete="off" required>
                <div id="eventoOperacionSugerenciasFer" class="list-group"
                  style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text" id="eventoOperacionMetaFer"></div>
            </div>

            <!-- Contenedor físico (Caja/Ferro) con sugerencias -->
            <div class="col-md-6">
              <label for="eventoContenedorNombreFer" class="form-label">Contenedor</label>
              <div class="position-relative">
                <!-- Guardaremos directamente el contenedor_ferro_operacion_id -->
                <input type="hidden" id="eventoContenedorOperacionIdFer" name="eventoContenedorOperacionIdFer">
                <input type="text" id="eventoContenedorNombreFer" class="form-control"
                  placeholder="Escribe para buscar (ej. FXEU..., MGU...)" autocomplete="off" readonly>
                <div id="eventoContenedorSugerenciasFer" class="list-group"
                  style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text">Se listan los contenedores físicos de la operación seleccionada.</div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label for="tipoEventoIdFer" class="form-label">Tipo de evento</label>
              <select id="tipoEventoIdFer" name="tipoEventoIdFer" class="form-control">
                <option value="">Selecciona...</option>
              </select>
            </div>

            <div class="col-md-4">
              <label for="fechaEventoLogisticoFer" class="form-label">Fecha</label>
              <input type="date" class="form-control" id="fechaEventoLogisticoFer"
                name="fechaEventoLogisticoFer" required>
            </div>

            <div class="col-md-4">
              <label for="comentarioEventoLogisticoFer" class="form-label">Comentarios</label>
              <input type="text" id="comentarioEventoLogisticoFer" name="comentarioEventoLogisticoFer"
                class="form-control" placeholder="Opcional">
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>
            <button type="submit" id="btnSubmitEventoLogisticoFer" class="btn btn-primary">
              <i data-feather="save" class="me-1"></i> Guardar
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- MODAL celda -->
<div class="modal fade" id="modalEvtCellFer" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="modalEvtCellTitleFer">Evento</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEvtCellFer">
        <div class="modal-body">
          <input type="hidden" id="cellOpIdFer">
          <input type="hidden" id="cellCfoIdFer"> <!-- contenedor_ferro_operacion.id -->
          <input type="hidden" id="cellEvtIdFer">
          <input type="hidden" id="cellIdEventoFer"> <!-- si existe -->

          <div class="mb-2">
            <label class="form-label">Operación</label>
            <input id="cellOpTxtFer" class="form-control" readonly>
          </div>
          <div class="mb-2">
            <label class="form-label">Ferro/Caja / Maritimo</label>
            <input id="cellCtnTxtFer" class="form-control" readonly>
          </div>
          <div class="mb-2">
            <label class="form-label">Tipo de evento</label>
            <input id="cellEvtTxtFer" class="form-control" readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Fecha</label>
            <input type="date" id="cellFechaFer" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Comentario</label>
            <input id="cellComentarioFer" class="form-control" placeholder="Opcional">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="btnCellDeleteFer" class="btn btn-outline-danger d-none">Eliminar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- MODAL observación por renglón -->
<div class="modal fade" id="modalObsRenglonFer" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h6 class="modal-title">
          <i data-feather="message-square" class="me-2"></i>
          Observaciones del renglón
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formObsRenglonFer" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" id="obsFerOperacionId">
          <input type="hidden" id="obsFerOperacionFerroId">
          <input type="hidden" id="obsFerContenedorFisicoId">

          <div class="mb-2">
            <label class="form-label">Operación marítima</label>
            <input type="text" id="obsFerOperacionTxt" class="form-control" readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Contenedor marítimo</label>
            <input type="text" id="obsFerContenedorMaritimoTxt" class="form-control" readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Ferro / Caja</label>
            <input type="text" id="obsFerFerroTxt" class="form-control" readonly>
          </div>

          <div class="mb-2">
            <label for="obsFerTexto" class="form-label">Observación</label>
            <textarea
              id="obsFerTexto"
              class="form-control"
              rows="5"
              placeholder="Escribe una observación general para este renglón..."></textarea>
            <div class="form-text">
              Observacion general.
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <button type="button" id="btnLimpiarObsFer" class="btn btn-outline-danger">
            <i data-feather="trash-2" class="me-1"></i> Limpiar
          </button>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>

            <button type="submit" id="btnGuardarObsFer" class="btn btn-primary">
              <i data-feather="save" class="me-1"></i> Guardar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  feather.replace();
</script>
<!-- JS específico ferroviario -->
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/eventos_logisticos_fer.js"></script>
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
  forzarMayusculas("eventoOperacionNombreFer");
</script>