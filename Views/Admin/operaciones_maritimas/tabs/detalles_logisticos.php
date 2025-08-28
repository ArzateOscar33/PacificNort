<div class="container py-4 col-md-12">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="file-text" class="me-1"></i> Eventos Logísticos
      </h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetallesLogisticos"
              id="btnAbrirModalDetalles">
        <i data-feather="plus-circle" class="me-1"></i> Añadir / Editar Evento
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros superiores -->
      <div class="row g-3 align-items-end mb-3">
        <!-- Operación con sugerencias -->
        <div class="col-md-4">
          <label for="eventosFiltroOpNombre" class="form-label mb-1">Operación</label>
          <div class="position-relative">
            <input type="hidden" id="eventosFiltroOpId">
            <input type="text" id="eventosFiltroOpNombre" class="form-control"
                   placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off">
            <div id="eventosFiltroOpSugerencias" class="list-group"
                 style="position:absolute; z-index:1061; width:100%; display:none;"></div>
          </div>
          <div class="form-text" id="eventosFiltroOpMeta"></div>
        </div>

        <!-- Contenedor FÍSICO (Caja/Ferro) con sugerencias -->
        <div class=" col-md-4">
          <label for="eventosFiltroContenedorNombre" class="form-label mb-1">Contenedor físico (Caja/Ferro)</label>
          <div class="position-relative">
            <!-- Aquí guardamos el contenedor_operacion_id para filtrar -->
            <input type="hidden" id="eventosFiltroContenedorId">
            <input type="text" id="eventosFiltroContenedorNombre" class="form-control"
                   placeholder="Escribe para buscar (ej. FXEU..., MGU...)" autocomplete="off">
            <div id="eventosFiltroContenedorSugerencias" class="list-group"
                 style="position:absolute; z-index:1061; width:100%; display:none;"></div>
          </div>
         
        </div>

        <!-- Buscador libre -->
        <div class=" col-md-2">
          <label for="buscarDetalles" class="form-label mb-1">Buscar</label>
          <input id="buscarDetalles" class="form-control" placeholder="Buscar texto libre…">
        </div>

        <!-- perPage (IDs existentes) -->
        <div class="col-12 d-flex align-items-center justify-content-end gap-2">
          <label for="detPerPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="detPerPage" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="small text-muted">por página</span>
        </div>
      </div>

      <!-- Tabla de eventos (por fila) -->
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaDetallesLogisticos">
          <thead class="table-primary">
            <tr class="text-center">
              <th>Evento</th>
              <th>Fecha</th>
              <th>Operación</th>
              <th>Contenedor (Caja/Ferro)</th>
              <th>Comentarios</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyDetallesLogisticos"><!-- filas dinámicas --></tbody>
        </table>

        <!-- Paginación + resumen -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="detMetaResumen">Mostrando 0–0 de 0</span>
          </div>
          <nav aria-label="Paginación de detalles">
            <ul id="paginacionDetalles" class="pagination pagination-sm mb-0">
              <!-- Se llena desde JS -->
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

      <form id="formDetallesLogisticos" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" id="id_detalles" name="id_detalles"><!-- id_evento -->

          <div class="row g-3 mb-2">
            <!-- Operación con sugerencias -->
            <div class="col-md-6">
              <label for="eventoOperacionNombre" class="form-label">Operación</label>
              <div class="position-relative">
                <input type="hidden" id="eventoOperacionId" name="operacion_id">
                <input type="text" id="eventoOperacionNombre" class="form-control"
                       placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off" required>
                <div id="eventoOperacionSugerencias" class="list-group"
                     style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text" id="eventoOperacionMeta"></div>
            </div>

            <!-- Contenedor físico (Caja/Ferro) con sugerencias -->
            <div class="col-md-6">
              <label for="eventoContenedorNombre" class="form-label">Contenedor físico (Caja/Ferro)</label>
              <div class="position-relative">
                <!-- Guardaremos directamente el contenedor_operacion_id -->
                <input type="hidden" id="eventoContenedorOperacionId" name="contenedor_operacion_id">
                <input type="text" id="eventoContenedorNombre" class="form-control"
                       placeholder="Escribe para buscar (ej. FXEU..., MGU...)" autocomplete="off">
                <div id="eventoContenedorSugerencias" class="list-group"
                     style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text">Se listan los contenedores físicos de la operación seleccionada.</div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label for="tipo_evento_id" class="form-label">Tipo de evento</label>
              <select id="tipo_evento_id" name="tipo_evento_id" class="form-control" required>
                <option value="">Selecciona...</option>
                <!-- se llena dinámicamente (globales + de tipo marítimo) -->
              </select>
            </div>

            <div class="col-md-4">
              <label for="fecha_evento" class="form-label">Fecha</label>
              <input type="date" class="form-control" id="fecha_evento" name="fecha" required>
            </div>

            <div class="col-md-4">
              <label for="comentarios" class="form-label">Comentarios</label>
              <input type="text" id="comentarios" name="comentario" class="form-control" placeholder="Opcional">
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>
            <button type="submit" class="btn btn-primary" id="btnGuardarDetalles">
              <i data-feather="save" class="me-1"></i> Guardar
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<script>feather.replace();</script>
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/operaciones_maritimas/detalles_logisticos.js"></script>
