<div class="container py-4 col-md-12">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i data-feather="box" class="me-2"></i>Operaciones Ferroviarias</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFerroOP">
      <i data-feather="plus"></i>Nueva Operación Ferroviaria
    </button>
  </div>

  <!-- Filtros -->
  <div class="row g-3 align-items-end mb-4">
    <div class="col-12 col-md-4">
      <label for="buscarFerroOP" class="form-label">Buscar (Operación Ferro / Cliente / Ferro / Marítimo / Transportista)</label>
      <input type="text" id="buscarFerroOP" class="form-control" placeholder="Ej. FO-01, Juan, FX001, MG001">
    </div>

    <div class="col-12 col-md-5">
      <label class="form-label d-flex justify-content-between">
        <span>Rango de fechas</span>
      </label>
      <div class="d-flex gap-2 flex-wrap">
        <input type="date" id="fechaDesdeFerroOP" class="form-control w-50" aria-label="Desde">
        <input type="date" id="fechaHastaFerroOP" class="form-control w-50" aria-label="Hasta">
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="d-flex flex-wrap justify-content-between justify-content-md-end align-items-center gap-2">
        <div class="btn-group" role="group" aria-label="Exportaciones">
          <button class="btn btn-sm btn-outline-success" id="btnExcelFerroOP">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning" id="btnPdfFerroOP">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <div class="d-flex align-items-center ms-md-2">
          <label for="perPageFerroOP" class="mb-0 small text-muted me-2">Mostrar</label>
          <select id="perPageFerroOP" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered align-middle" id="tablaFerroOP">
      <thead class="table-light">
        <tr>
          <th>Operación Ferroviaria</th>
          <th>Cliente</th>
          <th>Ferro/Caja</th>
          <th>Contenedores Marítimos</th>
          <th>Bultos Totales</th>
          <th>Transportista</th>
          <th>Destino</th>
          <th>Fecha</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbodyFerroOP"></tbody>
    </table>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
      <div class="small text-muted">
        <span id="metaResumenFerroOP">Mostrando 0-0 de 0</span>
      </div>
      <nav aria-label="Paginación Operaciones Ferroviarias">
        <ul id="paginacionFerroOP" class="pagination pagination-sm mb-0"></ul>
      </nav>
    </div>
  </div>

  <!-- Modal: Nueva Operación Ferroviaria -->
  <div class="modal fade" id="modalFerroOP" tabindex="-1" aria-labelledby="modalFerroOPLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalFerroOPLabel">
            <i data-feather="plus-circle" class="me-1"></i> Nueva Operación Ferroviaria
          </h5>
        </div>

        <div class="modal-body">
          <form id="formFerroOP" autocomplete="off">
            <input type="hidden" id="rowIdFerroOP" name="rowIdFerroOP">
            <input type="hidden" id="asignacionesHidden" name="asignaciones">
            <!-- Información básica de la operación ferroviaria -->
            <div class="card mb-3">
              <div class="card-header">
                <h6 class="mb-0"><i data-feather="info" class="me-2"></i>Información General</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-3">
                    <label class="form-label">Número de Operación</label>
                    <input type="text" id="operacionNombreFerroOP" name="operacionNombreFerroOP"
                           class="form-control" placeholder="Se genera automáticamente" readonly>
                    <input type="hidden" id="operacionIdFerroOP" name="operacionIdFerroOP">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select id="estatusId_f" name="estatus_id_f" class="form-control" required>
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

                  <div class="col-md-3">
                    <label for="fechaFerroOP" class="form-label">Fecha</label>
                    <input type="date" id="fechaFerroOP" name="fechaFerroOP" class="form-control" required>
                  </div>

                  <div class="col-md-3 position-relative">
                    <label class="form-label">Ferro/Caja</label>
                    <input type="hidden" id="contenedorFerroIdFerroOP" name="contenedorFerroIdFerroOP">
                    <input type="text" id="contenedorFerroNombreFerroOP" class="form-control"
                           placeholder="Buscar ferro/caja..." required>
                    <div id="sugFerrosFerroOP" class="list-group"
                         style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                  </div>
                </div>

                <div class="row mt-3">
            

                  <div class="col-md-4 position-relative">
                    <label class="form-label">Transportista</label>
                    <input type="hidden" id="transportistaIdFerroOP" name="transportistaIdFerroOP">
                    <input type="text" id="transportistaNombreFerroOP" name="transportistaNombreFerroOP"
                           class="form-control" placeholder="Buscar transportista..." required>
                    <div id="sugTransportistasFerroOP" class="list-group"
                         style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                  </div>

                  <div class="col-md-4 position-relative">
                    <label class="form-label">Destino</label>
                    <input type="hidden" id="destinoIdFerroOP" name="destinoIdFerroOP">
                    <input type="text" id="destinoNombreFerroOP" name="destinoNombreFerroOP"
                           class="form-control" placeholder="Buscar destino..." required>
                    <div id="destinoFerroOP" class="list-group"
                         style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contenedores marítimos -->
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i data-feather="package" class="me-2"></i>Contenedores Marítimos</h6>
                <button type="button" class="btn btn-sm btn-success" id="btnAgregarMaritimoFerroOP">
                  <i data-feather="plus" class="me-1"></i>Agregar Marítimo
                </button>
              </div>

              <div class="card-body">
                <!-- Selector para agregar marítimo -->
                 
                <div id="selectorMaritimoFerroOP" class="row mb-3 d-none"   >
                  <!-- Operación Marítima (único editable de texto) -->
                  <div class="col-md-4 position-relative">
                    <label class="form-label">Operación Marítima</label>
                    <input type="hidden" id="operacionMaritimaIdFerroOP" name="operacionMaritimaIdFerroOP">
                    <input type="hidden" id="contMaritimoOperacionIdFerroOP" name="contMaritimoOperacionIdFerroOP">
                    <input type="text" id="operacionMaritimaNombreFerroOP" class="form-control"
                           placeholder="Ej. LB-01 (escribe para buscar)">
                    <div id="sugOperacionesMaritimasFerroOP" class="list-group"
                         style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                  </div>



                  <!-- Contenedor Marítimo (auto) -->
                  <div class="col-md-4">
                    <label class="form-label">Contenedor Marítimo</label>
                    <input type="hidden" id="contenedorMaritimoIdFerroOP" name="contenedorMaritimoIdFerroOP">
                    <input type="text" id="contenedorMaritimoNombreFerroOP" class="form-control" readonly
                           placeholder="Se llena automáticamente">
                  </div>
                  <!-- Cliente (auto) -->
                  <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <input type="text" id="clienteNombreMaritimoFerroOP" class="form-control" readonly
                           placeholder="Se llena desde la operación marítima">
                  </div>
                  <!-- Bultos (auto) y Asignar (editable) -->
                  <div class="col-md-2 mt-3">
                    <label class="form-label">Bultos Disponibles</label>
                    <input type="number" id="bultosMaritimoFerroOP" class="form-control" readonly>
                  </div>
                  <div class="col-md-2 mt-3">
                    <label class="form-label">Bultos Restantes</label>
                    <input type="number" id="bultosRestantesFerroOP" class="form-control" readonly>
                  </div>
                  <div class="col-md-2 mt-3">
                    <label class="form-label">Asignar al Ferro</label>
                    <input type="number" id="bultosAsignadosFerroOP" name="bultosAsignadosFerroOP"
                           class="form-control" min="1" placeholder="0">
                  </div>

                  <div class="col-md-2 mt-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-success btn-sm" id="btnConfirmarMaritimoFerroOP" title="Agregar a la lista">
                        <i data-feather="check"></i>
                      </button>
                      <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarMaritimoFerroOP" title="Cancelar selección">
                        <i data-feather="x"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Lista de marítimos agregados -->
                <div class="table-responsive">
                  <table class="table table-sm" id="tablaMaritimosSeleccionados">
                    <thead class="table-light">
                      <tr>
                        <th>Operación Marítima</th>
                        <th>Contenedor Marítimo</th>
                        <th>Bultos Asignados</th>
                        <th>Comentarios</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyMaritimosSeleccionados">
                      <tr id="noMaritimosMessage">
                        <td colspan="5" class="text-center text-muted">
                          <i data-feather="package" class="me-2"></i>
                          No hay contenedores marítimos agregados
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Resumen -->
                <div class="row mt-3">
                  <div class="col-md-8"></div>
                  <div class="col-md-4">
                    <div class="card bg-light">
                      <div class="card-body p-3">
                        <div class="d-flex justify-content-between">
                          <strong>Total de Bultos:</strong>
                          <span id="totalBultosFerroOP" class="badge bg-primary">0</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                          <strong>Marítimos:</strong>
                          <span id="totalMaritimosFerroOP" class="badge bg-info">0</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div><!-- /card-body -->
            </div><!-- /card -->
            
            <!-- Comentarios generales -->
            <div class="row mt-3">
              <div class="col-md-12">
                <label for="comentariosFerroOP" class="form-label">Comentarios</label>
                <textarea id="comentariosFerroOP" name="comentariosFerroOP" class="form-control" rows="3"></textarea>
              </div>
            </div>

          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i data-feather="x"></i> Cancelar
          </button>
          <button type="submit" form="formFerroOP" class="btn btn-primary">
            <i data-feather="save"></i> Crear Operación Ferroviaria
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  feather.replace();
  const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>assets/js/modulosAdmin/operaciones_maritimoferro/ferroscatalogo.js"></script>
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/operaciones_maritimoferro/ferrosOperacion.js"></script> 
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/operaciones_maritimoferro/editarFerros.js"></script>



