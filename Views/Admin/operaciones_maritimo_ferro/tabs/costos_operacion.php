<div class="container py-4 col-md-12">
  <div class="card shadow-sm border-0">
    <div class="card-body">

      <!-- Encabezado + botón -->
      <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
        <div>
          <h3 class="mb-1">Costos por Operación</h3>
          <small class="text-muted">Consulta y administra los costos a nivel operación.</small>
        </div>

        <div class="ms-auto col-md-12 d-flex justify-content-end mb-3">
          <button class="btn btn-primary" id="costosOperacionBtnNuevo" data-bs-toggle="modal" data-bs-target="#modalCostoOperacion">
            <i data-feather="plus"></i> Añadir Costo
          </button>
        </div>

        <div class="container col-md-12">

          <!-- Filtros -->
          <div class="row justify-content-end align-items-center mb-2">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
              <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" id="costosOperacionBuscar" placeholder="Buscar concepto o comentario…">
                <!-- ELIMINADO: costosOperacionFiltroOrigen -->
                <select id="costosOperacionFiltroMoneda" class="form-control form-control-sm" style="max-width:140px;">
                  <option value="">Moneda: Todas</option>
                  <option value="PESOS">PESOS</option>
                  <option value="DLLS">DLLS</option>
                </select>
              </div>

              <div class="d-flex align-items-center gap-2">
                <label class="small mb-0">Por página:</label>
                <select id="costosOperacionPerPage" class="form-control form-control-sm" style="width:90px;">
                  <option>10</option>
                  <option>20</option>
                  <option>50</option>
                </select>
              </div>

              <div class="row">
                <div class="gap-2 col-md-12 d-flex align-items-center justify-content-end">
                  <button class="btn btn-sm btn-outline-success" id="btnExportarExcelCostosOperacion">
                    <i data-feather="file-text" class="me-1"></i> Excel
                  </button>
                  <button class="btn btn-sm btn-outline-warning" id="btnExportarPDFCostosOperacion">
                    <i data-feather="file" class="me-1"></i> PDF
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sugerencia de operación -->
          <div class="row flex-wrap gap-2 align-items-center mb-2">
            <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
              <label for="costosOperacionFiltroOpNombre" class="form-label mb-1">Operación</label>
              <div class="position-relative">
                <input type="hidden" id="costosOperacionFiltroOpId">
                <input type="text" id="costosOperacionFiltroOpNombre" class="form-control" placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off">
                <div id="costosOperacionFiltroOpSugerencias" class="list-group" style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text" id="costosOperacionFiltroOpMeta"></div>
            </div>
          </div>
          <!-- Sugerencia de Ferro/Caja (opcional, filtrará por ferro si tu backend lo soporta) -->
<div class="row flex-wrap gap-2 align-items-center mb-2">
  <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
    <label for="costosOperacionFiltroFerroNombre" class="form-label mb-1">Contenedor</label>
    <div class="position-relative">
      <input type="hidden" id="costosOperacionFiltroFerroId">
      <input type="text" id="costosOperacionFiltroFerroNombre" class="form-control"
             placeholder="Escribe para buscar (ej. CAJA-1234) — sugiere por operación"
             autocomplete="off">
      <div id="costosOperacionFiltroFerroSugerencias"
           class="list-group"
           style="position:absolute; z-index:1061; width:100%; display:none;"></div>
    </div>
    <div class="form-text" id="costosOperacionFiltroFerroMeta"></div>
  </div>
</div>


          <!-- Configuración de vista de totales -->
          <div class="row flex-wrap gap-2 justify-content-end align-items-center mb-2">
            <div class="d-flex flex-wrap align-items-end mb-2">
              <div>
                <label class="form-label small mb-1">Mostrar totales en</label>
                <select id="costosOperacionMonedaVista" class="form-control form-control-sm" style="width:140px;">
                  <option value="MXN">MXN (pesos)</option>
                  <option value="USD">USD (dólares)</option>
                </select>
              </div>
              <div>
                <label class="form-label small mb-1">Tipo de cambio</label>
                <div class="input-group input-group-sm" style="width:160px;">
                  <span class="input-group-text">$</span>
                  <input type="number" step="0.0001" min="0" id="costosOperacionTipoCambio" class="form-control mt-1" value="17.00">
                </div>
              </div>
            </div>
          </div>

        </div> <!-- /container filtros -->
      </div> <!-- /header -->

      <!-- Totales -->
      <div class="row g-3 mb-4" id="costosOperacionCards">
        <!-- 1) Operación -->
        <div class="col-12 col-md-6">
          <div class="bg-primary text-white p-3 rounded shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="mb-0 text-uppercase small">Total operación</h6>
              <i data-feather="dollar-sign"></i>
            </div>

            <div class="mt-2 h3 mb-1" id="costosOperacionTotalOperacion">$ 0.00</div>
            <small class="opacity-75 d-block mb-2">Costos registrados a la operación</small>

            <div class="d-flex justify-content-between small">
              <span class="opacity-75">Abonos</span>
              <strong id="costosOperacionAbonosOperacion">$ 0.00</strong>
            </div>
            <div class="d-flex justify-content-between small">
              <span class="opacity-75">Balance</span>
              <span><span id="costosOperacionBalanceOperacion" class="badge bg-light text-dark">$ 0.00</span></span>
            </div>
          </div>
        </div>

        <!-- 2) Total General (solo operación) -->
        <div class="col-12 col-md-6">
          <div class="bg-success text-white p-3 rounded shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="mb-0 text-uppercase small">Total general</h6>
              <i data-feather="trending-up"></i>
            </div>

            <div class="mt-2 h3 mb-1" id="costosOperacionTotalGeneral">$ 0.00</div>
            <small class="opacity-75 d-block mb-2">Ganancia neta (solo operación)</small>

            <div class="d-flex justify-content-between small">
              <span class="opacity-75">Abonos totales</span>
              <strong id="costosOperacionTotalAbonosGeneral">$ 0.00</strong>
            </div>
            <div class="d-flex justify-content-between small">
              <span class="opacity-75">Costos totales</span>
              <strong id="costosOperacionTotalCostosGeneral">$ 0.00</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla simplificada (solo operación) -->
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" id="tablaCostosOperacionExportar">
          <thead class="table-light">
            <tr>
              <th style="width:110px;">Fecha</th>
              <!-- ELIMINADO: Origen -->
              <!-- ELIMINADO: Contenedor -->
              <th>Concepto</th>
              <th style="width:120px;">Moneda</th>
              <th class="text-end" style="width:140px;">Monto</th>
              <th>Comentario</th>
              <th class="text-center" style="width:120px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyCostosOperacionCombined">
            <tr><td colspan="6" class="text-center text-muted py-4">Selecciona una operación para ver sus costos.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small" id="costosOperacionMeta">Mostrando 0-0 de 0</div>
        <nav><ul id="costosOperacionPaginacion" class="pagination pagination-sm mb-0"></ul></nav>
      </div>

    </div>
  </div>
</div>

<!-- Modal: Agregar / Editar Costo por Operación (sin cambios) -->
<div class="modal fade" id="modalCostoOperacion" tabindex="-1" aria-labelledby="modalCostoOperacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCostoOperacionLabel">
          <i data-feather="plus-circle" class="me-1"></i> Añadir Costo a Operación
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
              <input type="text" id="costosOperacionNombre" name="costosOperacionNombre" class="form-control"
                placeholder="Escribe para buscar operación..." autocomplete="off">
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
            <input type="number" id="costosContenedoresMonto" name="costosContenedoresMonto" class="form-control"
              required placeholder="Ej: 500">
          </div>

          <div class="mb-3">
            <label for="costosContenedoresMoneda" class="form-label">Modneda</label>
            <select id="costosContenedoresMoneda" name="costosContenedoresMoneda" class="form-control">
              <option value="">Seleccione</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="costosContenedoresComentarios" class="form-label">Comentarios (opcional)</label>
            <textarea id="costosContenedoresComentarios" name="costosContenedoresComentarios" rows="2"
              class="form-control"></textarea>
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

<script>feather.replace();</script>
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/operaciones_maritimoferro/costos_operacion_ferro_catalogo.js"></script>
