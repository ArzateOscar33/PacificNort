<div class="container py-4 col-md-12">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <!-- Encabezado + buscador de operación -->
      <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
        <div>
          <h3 class="mb-1">Costos por Operación</h3>
          <small class="text-muted">Consulta y administra los costos a nivel operación y por contenedor en una sola
            vista.</small>

        </div>
                    <div class="ms-auto col-md-12 d-flex justify-content-end mb-3">
              <button class="btn btn-primary" id="costosOperacionBtnNuevo" data-bs-toggle="modal"
                data-bs-target="#modalCostoOperacion">
                <i data-feather="plus"></i> Añadir Costo
              </button>
            </div>
        <div class="container col-md-12">
          <!-- Filtros de la tabla unificada -->
          <div class="row justify-content-end align-items-center mb-2"  >
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
              <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" id="costosOperacionBuscar"
                  placeholder="Buscar concepto o comentario…">
                <select id="costosOperacionFiltroOrigen" class="form-control form-control-sm" style="max-width:180px;">
                  <option value="">Origen: Todos</option>
                  <option value="OPERACION">Operación</option>
                  <option value="CONTENEDOR">Contenedor</option>
                </select>
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

          <div class="row flex-wrap gap-2   align-items-center mb-2">
            <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
              <label for="costosOperacionFiltroOpNombre" class="form-label mb-1">Operación</label>
              <div class="position-relative">
                <input type="hidden" id="costosOperacionFiltroOpId">
                <input type="text" id="costosOperacionFiltroOpNombre" class="form-control"
                  placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off">
                <div id="costosOperacionFiltroOpSugerencias" class="list-group"
                  style="position:absolute; z-index:1061; width:100%; display:none;"></div>
              </div>
              <div class="form-text" id="costosOperacionFiltroOpMeta"></div>
            </div>

          </div>

          <div class="row flex-wrap gap-2 justify-content-end align-items-center mb-2">
            <div class="d-flex flex-wrap   align-items-end mb-2">
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
                  <input type="number" step="0.0001" min="0" id="costosOperacionTipoCambio" class="form-control mt-1"
                    value="17.00">

                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
<!-- Totales -->
<div class="row g-3 mb-4" id="costosOperacionCards">
  <!-- 1) Operación -->
  <div class="col-12 col-md-4">
    <div class="bg-primary text-white p-3 rounded shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-uppercase small">Total operación</h6>
        <i data-feather="dollar-sign"></i>
      </div>

      <!-- Monto principal: Costos de la Operación -->
      <div class="mt-2 h3 mb-1" id="costosOperacionTotalOperacion">$ 0.00</div>
      <small class="opacity-75 d-block mb-2">Costos registrados a la operación</small>

      <!-- Abonos y Balance (líneas secundarias) -->
      <div class="d-flex justify-content-between small">
        <span class="opacity-75">Abonos</span>
        <strong id="costosOperacionAbonosOperacion">$ 0.00</strong>
      </div>
      <div class="d-flex justify-content-between small">
        <span class="opacity-75">Balance</span>
        <span>
          <span id="costosOperacionBalanceOperacion" class="badge bg-light text-dark">$ 0.00</span>
        </span>
      </div>
    </div>
  </div>

  <!-- 2) Contenedores -->
  <div class="col-12 col-md-4">
    <div class="bg-info text-dark p-3 rounded shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-uppercase small">Total contenedores</h6>
        <i data-feather="box"></i>
      </div>

      <!-- Monto principal: Costos por Contenedores -->
      <div class="mt-2 h3 mb-1" id="costosOperacionTotalContenedores">$ 0.00</div>
      <small class="text-dark-50 d-block mb-2">Suma de costos por contenedor</small>

      <!-- Abonos y Balance -->
      <div class="d-flex justify-content-between small">
        <span class="text-dark-50">Abonos</span>
        <strong id="costosOperacionAbonosContenedores">$ 0.00</strong>
      </div>
      <div class="d-flex justify-content-between small">
        <span class="text-dark-50">Balance</span>
        <span>
          <span id="costosOperacionBalanceContenedores" class="badge bg-dark-subtle">$ 0.00</span>
        </span>
      </div>
    </div>
  </div>

  <!-- 3) Total General -->
  <div class="col-12 col-md-4">
    <div class="bg-success text-white p-3 rounded shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-uppercase small">Total general</h6>
        <i data-feather="trending-up"></i>
      </div>

      <!-- Monto principal: Balance Neto (Abonos - Costos) -->
      <div class="mt-2 h3 mb-1" id="costosOperacionTotalGeneral">$ 0.00</div>
      <small class="opacity-75 d-block mb-2">Ganancia neta</small>

      <!-- Subdesglose: Abonos y Costos totales -->
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

      <!-- Tabla unificada (operación + contenedores) -->
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" id="tablaCostosOperacionExportar">
          <thead class="table-light">
            <tr>
              <th style="width:110px;">Fecha</th>
              <th style="width:120px;">Origen</th> <!-- OPERACION | CONTENEDOR -->
              <th style="width:160px;">Contenedor</th> <!-- vacío cuando origen=OPERACION -->
              <th>Concepto</th>
              <th style="width:120px;">Moneda</th>
              <th class="text-end" style="width:140px;">Monto</th>
              <th>Comentario</th>
              <th class="text-center" style="width:120px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyCostosOperacionCombined">
            <tr>
              <td colspan="8" class="text-center text-muted py-4">Selecciona una operación para ver sus costos.</td>
            </tr>
          </tbody>
        </table>

      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small" id="costosOperacionMeta">Mostrando 0–0 de 0</div>
        <nav>
          <ul id="costosOperacionPaginacion" class="pagination pagination-sm mb-0"></ul>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Agregar / Editar Costo por Operación -->
<div class="modal fade" id="modalCostoOperacion" tabindex="-1" aria-labelledby="modalCostoOperacionLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCostoOperacionLabel">
          <i data-feather="plus-circle" class="me-1"></i> Añadir Costo a Operación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formCostoOperacion">
        <div class="modal-body">
          <input type="hidden" id="costosOperacionRowId" name="row_id">
          <input type="hidden" id="costosOperacionOpId" name="operacion_id">
          <input type="hidden" id="costosOperacionMonedaHidden" name="moneda">

          <div class="mb-3 position-relative">
            <label class="form-label">Operación</label>
            <input type="text" id="costosOperacionOpNombre" class="form-control" placeholder="Escribe para buscar…"
              autocomplete="off">
            <div id="costosOperacionOpSugerencias" class="list-group"
              style="position:absolute; z-index:1061; width:100%; display:none;"></div>
            <small class="text-muted">Selecciona la operación a la que registrarás el costo.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipo de Costo</label>
            <select id="costosOperacionTipo" name="tipo_movimiento_id" class="form-control" required>
              <option value="">Seleccione un tipo</option>
              <?php foreach ($tiposMovimiento as $t): 
              $id     = (int)($t['id_tipo_movimiento'] ?? 0);
              $nombre = (string)($t['nombre'] ?? '');
              $moneda = strtoupper((string)($t['moneda'] ?? ''));
            ?>
              <option value="<?= $id ?>" data-moneda="<?= htmlspecialchars($moneda) ?>">
                <?= htmlspecialchars($nombre) ?> (<?= htmlspecialchars($moneda) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Monto</label>
              <input type="number" step="0.01" id="costosOperacionMonto" name="monto" class="form-control" required
                placeholder="Ej: 150">
            </div>
            <div class="col-6">
              <label class="form-label">Moneda</label>
              <select id="costosOperacionMoneda" name="moneda" class="form-control" disabled>
                <option value="">Seleccione</option>
                <option value="PESOS">PESOS</option>
                <option value="DLLS">DLLS</option>
              </select>
              <small class="text-muted">Derivada del tipo seleccionado.</small>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Comentario (opcional)</label>
            <textarea id="costosOperacionComentario" name="comentario" rows="2" class="form-control"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i data-feather="x"></i> Cancelar
          </button>
          <button type="submit" id="costosOperacionBtnSubmit" class="btn btn-primary">
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

<script src="<?= BASE_URL ?>assets/js/modulosAdmin/operaciones_maritimas/catalogos/costos_operacion_catalogo.js">
</script>