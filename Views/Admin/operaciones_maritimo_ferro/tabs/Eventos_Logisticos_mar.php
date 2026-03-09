<div class="container py-4 col-md-12">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center d-none">
      <h5 class="mb-0">
        <i data-feather="file-text" class="me-1"></i> Eventos Marítimos
      </h5>
      <button class="btn btn-light btn-sm d-none" data-bs-toggle="modal" data-bs-target="#modalDetallesLogisticos"
        id="btnAbrirModalDetalles">
        <i data-feather="plus-circle" class="me-1"></i> Añadir / Editar Evento
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros superiores -->
      <div class="row g-3 align-items-end mb-3">
        <!-- Operación con sugerencias -->
        <div class="col-md-4 d-none">
          <label for="eventosFiltroOpNombre" class="form-label mb-1">Operación</label>
          <div class="position-relative">
            <input type="hidden" id="eventosFiltroOpId">
            <input type="text" id="eventosFiltroOpNombre" class="form-control"
              placeholder="Escribe para buscar (ej. LBMF-06)" autocomplete="off">
            <div id="eventosFiltroOpSugerencias" class="list-group"
              style="position:absolute; z-index:1061; width:100%; display:none;"></div>
          </div>
          <div class="form-text" id="eventosFiltroOpMeta"></div>
        </div>
        <div class="col-md-2">
          <label for="eventosFiltroFerro">Contenedor Maritimo</label>
          <input type="text" id="eventosFiltroFerro" class="form-control" placeholder="Escribe para buscar" autocomplete="off">
        </div>
        <div class="col-md-2">
          <label for="eventosFiltroCliente">Cliente</label>
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

        <!-- Rango de fechas -->

        <!-- <div class="col-md-4">
          <label class="form-label mb-1">Rango de fechas</label>
          <div class="d-flex align-items-center gap-2">
            <input type="date" id="fechaDesdeEventosMar" class="form-control" style="min-width: 0;" title="Fecha desde">
            <span class="text-muted">—</span>
            <input type="date" id="fechaHastaEventosMar" class="form-control" style="min-width: 0;" title="Fecha hasta">
          </div>

        </div>-->



        <!-- Exportaciones -->
        <div class="col-md-4 d-flex gap-2">
          <button class="btn btn-sm btn-outline-success w-100" id="btnExportarExcelEventosLogisticosMar">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning w-100" id="btnExportarPDFEventosLogisticosMar">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <!-- perPage -->
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

      <!-- Tabla de eventos por contenedor marítimo -->
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaEventosMar">
          <thead class="table-primary">
            <tr id="theadEventosMar" class="text-center">
              <!-- Fijos -->
              <th style="min-width: 140px;">Operación</th>
              <th style="min-width: 180px;">Contenedor marítimo</th>
              <th style="min-width: 180px;">Cliente</th>
              <!-- Dinámicos (JS): una <th> por cada tipo de evento marítimo -->
              <!-- Ejemplo inyectado por JS:
                   <th data-evt-id="8">Arribo A Puerto</th>
                   <th data-evt-id="9">Cargado</th>
                   <th data-evt-id="10">Entrega</th>
                   <th data-evt-id="11">Cita en puerto</th>
                   <th data-evt-id="14">Entrega Programada</th>
              -->
            </tr>
          </thead>
          <tbody id="tbodyEventosMar">
            <!-- JS: filas dinámicas. Cada <td> de evento mostrará la FECHA o vacío -->
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


<!-- MODAL: Crear / Editar Evento Logístico -->
<div class="modal fade" id="modalDetallesLogisticos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">
          <i data-feather="plus-square" class="me-2"></i>
          <span id="modalTituloDetalles">Registrar Evento</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formEventosLogisticos" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" id="idEvento" name="idEvento" value="">
          <input type="hidden" id="eventoContenedorTipo">
          <div class="row g-3 mb-2">
            <!-- Operación con sugerencias -->
            <div class="col-md-6">
              <label for="eventoOperacionNombre" class="form-label">Operación</label>
              <div class="position-relative">
                <input type="hidden" id="eventoOperacionId" name="eventoOperacionId">
                <input type="text" id="eventoOperacionNombre" class="form-control"
                  placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off" required>
                <div id="eventoOperacionSugerencias" class="list-group"
                  style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text" id="eventoOperacionMeta"></div>
            </div>

            <!-- Contenedor físico (Caja/Ferro) con sugerencias -->
            <div class="col-md-6">
              <label for="eventoContenedorNombre" class="form-label">Contenedor</label>
              <div class="position-relative">
                <!-- Guardaremos directamente el contenedor_operacion_id -->
                <input type="hidden" id="eventoContenedorOperacionId" name="eventoContenedorOperacionId">
                <input type="text" id="eventoContenedorNombre" class="form-control"
                  placeholder="Escribe para buscar (ej. FXEU..., MGU...)" autocomplete="off" readonly>
                <div id="eventoContenedorSugerencias" class="list-group"
                  style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text">Se listan los contenedores físicos de la operación seleccionada.</div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label for="tipo_evento_id" class="form-label">Tipo de evento</label>
              <select id="tipoEventoId" name="tipoEventoId" class="form-control">
                <option value="">Selecciona...</option>


              </select>
            </div>

            <div class="col-md-4">
              <label for="fecha_evento" class="form-label">Fecha</label>
              <input type="date" class="form-control" id="fechaEventoLogistico"
                name="fechaEventoLogistico" required>
            </div>

            <div class="col-md-4">
              <label for="comentarios" class="form-label">Comentarios</label>
              <input type="text" id="comentarioEventoLogistico" name="comentarioEventoLogistico"
                class="form-control" placeholder="Opcional">
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>
            <button type="submit" id="btnSubmitEventoLogistico" class="btn btn-primary" id="btnGuardarDetalles">
              <i data-feather="save" class="me-1"></i> Guardar
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>
<div class="modal fade" id="modalEvtCell" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="modalEvtCellTitle">Evento</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEvtCell">
        <div class="modal-body">
          <input type="hidden" id="cellOpId">
          <input type="hidden" id="cellCmoId">
          <input type="hidden" id="cellEvtId">
          <input type="hidden" id="cellIdEvento"> <!-- si existe -->

          <div class="mb-2">
            <label class="form-label">Operación</label>
            <input id="cellOpTxt" class="form-control" readonly>
          </div>
          <div class="mb-2">
            <label class="form-label">Contenedor</label>
            <input id="cellCtnTxt" class="form-control" readonly>
          </div>
          <div class="mb-2">
            <label class="form-label">Tipo de evento</label>
            <input id="cellEvtTxt" class="form-control" readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Fecha</label>
            <input type="date" id="cellFecha" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Comentario</label>
            <input id="cellComentario" class="form-control" placeholder="Opcional">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="btnCellDelete" class="btn btn-outline-danger d-none">Eliminar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  feather.replace();
</script>

<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/eventos_logisticos_mar.js"></script>
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
  forzarMayusculas("eventoOperacionNombre");
</script>