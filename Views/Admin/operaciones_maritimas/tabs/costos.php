<div class="container py-4 col-md-12">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Costos por Contenedor</h4>
    <button class="btn btn-success" data-bs-toggle="modal" id="btnNuevoCostoContenedor"
      data-bs-target="#modalAgregarCosto">
      <i data-feather="plus"></i> Añadir Costo
    </button>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <label for="buscar" class="form-label">Buscar por cliente o costo</label>
      <input type="text" id="buscarCostoContenedor" class="form-control" placeholder="Buscar...">
    </div>
    <div class="col-md-4">
      <label for="filtro_moneda" class="form-label">Tipo de Moneda</label>
      <select id="filtroMonedaCostoContenedor" name="filtroMonedaCostoContenedor" class="form-control">
        <option value="">Todas</option>
        <option value="Pesos">Pesos</option>
        <option value="Dólares">Dólares</option>

      </select>
    </div>
    <div class="col-md-4">
      <label for="filtro_tipo" class="form-label">Tipo de Costo</label>
      <select id="filtroTipoCostoContenedor" name="filtroTipoCostoContenedor" class="form-control">
        <!-- Se llena desde JS -->
      </select>
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
      <div class="d-flex align-items-center gap-2">
        <label class="mb-0">Por página:</label>
        <select id="perPageCostos" class="form-control form-control-sm" style="width: 90px;">
          <option>10</option>
          <option>20</option>
          <option>50</option>
        </select>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered align-middle" id="tablaCostosContenedores">
        <thead class="table-light">
          <tr>
            <th>Operación</th>
            <th>Contenedor</th>
            <th>Tipo de Costo</th>
            <th>Monto</th>
            <th>Moneda</th>
            <th>Comentarios</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyCostosContenedores">
          <!-- Se llena desde JS -->
        </tbody>
      </table>

      <div id="metaResumenCostos" class="text-muted small">Mostrando 0–0 de 0</div>

      <nav>
        <ul id="paginacionCostos" class="pagination pagination-sm mb-0"></ul>
      </nav>
    </div>
  </div>
</div>

<!-- Modal: Agregar Costo -->
<div class="modal fade" id="modalAgregarCosto" tabindex="-1" aria-labelledby="modalAgregarCostoLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalAgregarCostoLabel">
          <i data-feather="plus-circle" class="me-1"></i> Añadir Costo al Contenedor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formAgregarCostoContenedores">
        <div class="modal-body">
          <input type="hidden" id="row_id" name="row_id">

          <div class="mb-3">
            <div class="position-relative">
              <!-- Operación -->
              <label class="form-label">Operación</label>
              <input type="hidden" id="costosOperacionid" name="costosOperacionid">
              <input type="text" id="costosOperacionNombre" name="costosOperacionNombre"
                     class="form-control" placeholder="Escribe para buscar operación..." autocomplete="off">
              <div id="costosSugerenciasOperaciones" class="list-group"
                   style="position:absolute; z-index:1061; width:100%; display:none;"></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="position-relative">
              <!-- Contenedor físico -->
              <label class="form-label">Contenedor Físico</label>
              <input type="hidden" id="costosContenedorContenedorId" name="costosContenedorContenedorId">
              <input type="text" id="costosContenedorContenedorNombre" name="costosContenedorContenedorNombre"
                     class="form-control" placeholder="Escribe para buscar contenedor..." autocomplete="off">
              <div id="sugerenciasCostosContenedor" class="list-group"
                   style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              <small class="text-muted">Sugerencia: escribe parte del número (ej. FXE...).</small>
            </div>
          </div>

          <div class="mb-3">
            <label for="costosContenedoresTipoCosto" class="form-label">Tipo de Costo</label>
            <select id="costosContenedoresTipoCosto" name="costosContenedoresTipoCosto" class="form-control" required>
              <option value="">Seleccione un tipo</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="costosContenedoresMonto" class="form-label">Monto</label>
            <input type="number" id="costosContenedoresMonto" name="costosContenedoresMonto"
                   class="form-control" required placeholder="Ej: 500">
          </div>

          <div class="mb-3">
            <label for="costosContenedoresMoneda" class="form-label">Moneda</label>
            <select id="costosContenedoresMoneda" name="costosContenedoresMoneda"
                    class="form-control" readonly>
              <option value="">Seleccione</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="costosContenedoresComentarios" class="form-label">Comentarios (opcional)</label>
            <textarea id="costosContenedoresComentarios" name="costosContenedoresComentarios"
                      rows="2" class="form-control"></textarea>
          </div>
        </div>

        <!-- OJO: modal-footer es HERMANO de modal-body -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="btnCancelarCostoContenedor" data-bs-dismiss="modal">
            <i data-feather="x"></i> Cancelar
          </button>
          <button type="submit" id="btnSubmitCostoContenedor" class="btn btn-success">
            <i data-feather="save"></i> Guardar
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

 
<script>
  feather.replace();
</script>