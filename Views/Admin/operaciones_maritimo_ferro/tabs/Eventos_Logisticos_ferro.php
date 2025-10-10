<div class="container py-4 col-md-12">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="file-text" class="me-1"></i> Eventos Logísticos (Ferroviario)
      </h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetallesLogisticosFer"
        id="btnAbrirModalDetallesFer">
        <i data-feather="plus-circle" class="me-1"></i> Añadir / Editar Evento
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros superiores -->
      <div class="row g-3 align-items-end mb-3">
        <!-- Operación con sugerencias -->
        <div class="col-md-8">
          <label for="eventosFerFiltroOpNombre" class="form-label mb-1">Operación</label>
          <div class="position-relative">
            <input type="hidden" id="eventosFerFiltroOpId">
            <input type="text" id="eventosFerFiltroOpNombre" class="form-control"
              placeholder="Escribe para buscar (ej. FO)" autocomplete="off">
            <div id="eventosFerFiltroOpSugerencias" class="list-group"
              style="position:absolute; z-index:1061; width:100%; display:none;"></div>
          </div>
          <div class="form-text" id="eventosFerFiltroOpMeta"></div>
        </div>

        <!-- Exportaciones -->
        <div class="col-md-4 d-flex gap-2">
          <button class="btn btn-sm btn-outline-success w-100" id="btnExportarExcelEventosLogisticosFer">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning w-100" id="btnExportarPDFEventosLogisticosFer">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <!-- perPage -->
        <div class="col-12 d-flex align-items-center justify-content-end gap-2">
          <label for="evFerPerPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="evFerPerPage" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="small text-muted">por página</span>
        </div>
      </div>

      <!-- Tabla de eventos por contenedor ferroviario -->
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaEventosFer">
          <thead class="table-primary">
            <tr id="theadEventosFer" class="text-center">
              <!-- Fijos -->
              <th style="min-width: 140px;">Operación</th>
              <th style="min-width: 180px;">Contenedor ferroviario</th>
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
            <div class="col-md-6">
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
            <label class="form-label">Contenedor</label>
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

<script>
  feather.replace();
</script>
<!-- JS específico ferroviario --> 
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/operaciones_maritimoferro/eventos_logisticos_fer.js"></script>
