<div class="container py-4 col-md-12">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i   class="me-2"></i>Operaciones Ferroviarias</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOperacionFerro">
      <i  ></i> Nueva Operación Ferro
    </button>
  </div>

  <!-- Filtros -->
  <div class="row g-3 align-items-end mb-4">
    <div class="col-12 col-md-4">
      <label for="buscarOperacionFerro" class="form-label">Buscar (FX / Contenedor / Transportista / Destino)</label>
      <input type="text" id="buscarOperacionFerro" class="form-control" placeholder="Ej. FX001, FX-2025-09">
    </div>

    <div class="col-12 col-md-5">
      <label class="form-label d-flex justify-content-between">
        <span>Rango de fechas</span>
      </label>
      <div class="d-flex gap-2 flex-wrap">
        <input type="date" id="fechaDesdeOperacionFerro" class="form-control w-50" aria-label="Desde">
        <input type="date" id="fechaHastaOperacionFerro" class="form-control w-50" aria-label="Hasta">
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="d-flex flex-wrap justify-content-between justify-content-md-end align-items-center gap-2">
        <div class="btn-group" role="group" aria-label="Exportaciones">
          <button class="btn btn-sm btn-outline-success" id="btnExcelOperacionesFerro">
            <i   class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning" id="btnPdfOperacionesFerro">
            <i   class="me-1"></i> PDF
          </button>
        </div>

        <div class="d-flex align-items-center ms-md-2">
          <label for="perPageOperacionesFerro" class="mb-0 small text-muted me-2">Mostrar</label>
          <select id="perPageOperacionesFerro" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-bordered align-middle" id="tablaOperacionesFerro">
      <thead class="table-light">
        <tr>
          <th>Número FX</th>
          <th>Caja / Ferro</th>
          <th>Transportista</th>
          <th>Destino</th>
          <th>Fecha de inicio</th>
          <th>Fecha estimada</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbodyOperacionesFerro"></tbody>
    </table>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
      <div class="small text-muted">
        <span id="metaResumenOperacionesFerro">Mostrando 0-0 de 0</span>
      </div>
      <nav aria-label="Paginación Operaciones Ferro">
        <ul id="paginacionOperacionesFerro" class="pagination pagination-sm mb-0"></ul>
      </nav>
    </div>
  </div>
</div>

<!-- Modal: Crear/Editar Operación Ferroviaria -->
<div class="modal fade" id="modalOperacionFerro" tabindex="-1" aria-labelledby="modalOperacionFerroLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalOperacionFerroLabel">
          <i   class="me-1"></i> Nueva Operación Ferro
        </h5>
      </div>

      <div class="modal-body">
        <form id="formOperacionFerro" autocomplete="off">
          <input type="hidden" id="operacionFerroId" name="operacionFerroId">

          <!-- Datos principales de la operación FX -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Número de Operación (FX)</label>
              <input type="text" id="numeroOperacionFerro" name="numeroOperacionFerro" class="form-control" placeholder="Ej. FX001, FX-2025-09">
              <small class="text-muted">Puede ser autogenerado por backend.</small>
            </div>

            <div class="col-md-4 position-relative">
              <label class="form-label">Caja / Ferro</label>
              <input type="hidden" id="contenedorFerroId" name="contenedorFerroId">
              <input type="text" id="contenedorFerroNombre" name="contenedorFerroNombre" class="form-control" placeholder="Escribe para buscar ferro/caja…">
              <div id="sugFerrosFisicos" class="list-group" style="position:absolute; z-index:1055; width:100%; display:none;"></div>
              <small class="text-muted">Ej. FX001… (de contenedores_fisicos)</small>
            </div>

            <div class="col-md-4 position-relative">
              <label class="form-label">Transportista</label>
              <input type="hidden" id="transportistaIdFerro" name="transportistaIdFerro">
              <input type="text" id="transportistaNombreFerro" name="transportistaNombreFerro" class="form-control" placeholder="Escribe para buscar transportista…">
              <div id="sugTransportistasFerro" class="list-group" style="position:absolute; z-index:1055; width:100%; display:none;"></div>
              <small class="text-muted">Usa transportistas con tipo ferroviario.</small>
            </div>
          </div>

          <!-- Destino y Fechas -->
          <div class="row g-3 mb-3">
            <div class="col-md-6 position-relative">
              <label class="form-label">Destino</label>
              <input type="hidden" id="destinoIdFerro" name="destinoIdFerro">
              <input type="text" id="destinoNombreFerro" name="destinoNombreFerro" class="form-control" placeholder="Escribe para buscar destino…">
              <div id="sugDestinosFerro" class="list-group" style="position:absolute; z-index:1055; width:100%; display:none;"></div>
              <small class="text-muted">Catálogo de ciudades.</small>
            </div>

            <div class="col-md-3">
              <label class="form-label">Fecha de inicio</label>
              <input type="date" id="fechaInicioFerro" name="fechaInicioFerro" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Fecha estimada</label>
              <input type="date" id="fechaEstimadaFerro" name="fechaEstimadaFerro" class="form-control">
            </div>
          </div>

          <!-- Estatus + Observaciones -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Estatus</label>
              <select id="estatusFerro" name="estatusFerro" class="form-control">
                <option value="1" selected>Activa</option>
                <option value="0">Cerrada</option>
              </select>
              <small class="text-muted">Al cerrar, el FX queda disponible para futuras operaciones.</small>
            </div>

            <div class="col-md-8">
              <label class="form-label">Comentarios</label>
              <textarea id="comentariosFerro" name="comentariosFerro" class="form-control" rows="3"></textarea>
            </div>
          </div>

          <!-- (Opcional) Info rápida -->
          <div class="alert alert-light border d-flex align-items-center" role="alert">
            <i  class="me-2"></i>
            Primero crea/guarda la operación ferro. Luego, desde el módulo MF↔FX, asigna los bultos de los marítimos a este FX.
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i ></i> Cancelar
        </button>
        <button type="submit" form="formOperacionFerro" class="btn btn-primary">
          <i  ></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  const BASE_URL = "<?= BASE_URL ?>";
  feather.replace();
</script>
