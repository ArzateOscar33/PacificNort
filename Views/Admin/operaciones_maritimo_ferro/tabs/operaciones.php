<div class="container py-4 col-md-12">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="truck" class="me-1"></i> Operaciones Ferroviarias
      </h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#OperacionesFModal"
        id="OperacionesFBtnNuevaOperacion">
        <i data-feather="plus-circle" class="me-1"></i> Nueva Operación
      </button>
    </div>

    <div class="card-body">
      <!-- Filtros -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <!-- Subtipo opcional (LBF u otros prefijos ferroviarios si aplican) -->
        <select id="OperacionesFFiltroSubtipo" name="OperacionesFFiltroSubtipo" class="form-control" style="max-width:240px;">
          <option value="">Subtipo (Todos)</option>
          <?php if (!empty($data['subtiposF'])): foreach ($data['subtiposF'] as $st): ?>
              <option value="<?= (int)$st['id_subtipo']; ?>">
                <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
          <?php endforeach;
          endif; ?>
        </select>

        <input id="OperacionesFBuscar" class="form-control" style="max-width:260px;"
          placeholder="Buscar por código, ferro o contenedor marítimo (y cliente derivado)">

        <div class="col-md-2">
          <button class="btn btn-sm btn-outline-success" id="OperacionesFBtnExportarExcel">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning" id="OperacionesFBtnExportarPDF">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <!-- Rango de fechas -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <i data-feather="calendar"></i>
          <span class="small text-muted">Rango:</span>
          <input type="date" id="OperacionesFFechaIni" name="OperacionesFFechaIni" class="form-control" style="max-width:165px;" />
          <input type="date" id="OperacionesFFechaFin" name="OperacionesFFechaFin" class="form-control" style="max-width:165px;" />
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
          <label for="OperacionesFPerPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="OperacionesFPerPage" class="form-control" style="width:90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="small text-muted">por página</span>
        </div>
      </div>

      <!-- Tabla -->
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="OperacionesFTablaExportar">
          <thead class="table-success">
            <tr class="text-center">
              <th style="width:140px;">Código</th>
              <th style="width:120px;">Fecha</th>
              <th style="width:180px;">No. Ferro</th>
              <th style="min-width:220px;">Contenedores Marítimos (este FERRO contiene…)</th>
              <th>Cliente(s) (derivado)</th>
              <th>Origen</th>
              <th>Destino</th>
              <th>Estatus</th>
              <th style="width:120px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="OperacionesFTabla"></tbody>
        </table>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="OperacionesFMetaResumen">Mostrando 0–0 de 0</span>
          </div>
          <nav aria-label="Paginación de operaciones">
            <ul id="OperacionesFPaginacion" class="pagination pagination-sm mb-0"></ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: Crear / Editar Operación Ferroviaria -->
<div class="modal fade" id="OperacionesFModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">
          <i data-feather="plus-square" class="me-2"></i>
          <span id="OperacionesFTituloModal">Nueva Operación Ferroviaria</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="OperacionesFForm" autocomplete="off">
          <input type="hidden" id="OperacionesFId" name="OperacionesFId" value="">

          <div class="row g-3">
            <!-- Cabecera de la operación ferroviaria -->
            <div class="col-md-4">
              <label class="form-label">Número de Operación</label>
              <input type="text" id="OperacionesFNumero" name="OperacionesFNumero" class="form-control" placeholder="LBF-01">
              <small id="OperacionesFFolioHelp" class="text-muted d-block mt-1">Folio preliminar</small>
            </div>

            <div class="col-md-4">
              <label class="form-label">Estatus</label>
              <select id="OperacionesFEstatusId" name="OperacionesFEstatusId" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($data['estatusF'])): foreach ($data['estatusF'] as $es): ?>
                    <option value="<?= (int)$es['id_estatus']; ?>">
                      <?= htmlspecialchars($es['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach;
                endif; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Fecha</label>
              <input type="date" id="OperacionesFFecha" name="OperacionesFFecha" class="form-control">
            </div>

            <!-- El FERRO que contiene a los marítimos -->
            <div class="col-12">
              <div class="alert alert-secondary py-2 mb-2">
                <strong>Este FERRO contendrá los siguientes contenedores marítimos.</strong>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">No. Económico (Ferro)</label>
              <select id="OperacionesFNoFerro" name="OperacionesFNoFerro" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['ferros'])): foreach ($data['ferros'] as $fx): ?>
                    <option value="<?= (int)$fx['id_fisico']; ?>">
                      <?= htmlspecialchars($fx['numero_ferro'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach;
                endif; ?>
              </select>
              <small class="text-muted">Primero selecciona el ferro; luego añade los contenedores marítimos.</small>
            </div>

            <!-- Origen / Destino del trayecto ferroviario -->
            <div class="col-md-3">
              <label class="form-label">Origen</label>
              <select id="OperacionesFOrigen" name="OperacionesFOrigen" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['ciudades'])): foreach ($data['ciudades'] as $c): ?>
                    <option value="<?= (int)$c['id_ciudad']; ?>">
                      <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach;
                endif; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Destino</label>
              <select id="OperacionesFDestino" name="OperacionesFDestino" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['ciudades'])): foreach ($data['ciudades'] as $c): ?>
                    <option value="<?= (int)$c['id_ciudad']; ?>">
                      <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach;
                endif; ?>
              </select>
            </div>

            <!-- Repetidor: CONTENEDORES MARÍTIMOS que van dentro del FERRO -->
            <div class="col-8">
              <label class="form-label d-flex align-items-center justify-content-between">
                <span>Agregar contenedores marítimos (en operación) a este FERRO</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="OperacionesFBtnAgregarCM">
                  <i data-feather="plus"></i> Agregar
                </button>
              </label>

              <div id="OperacionesFRepeaterCM" class="vstack gap-3">
                <!-- Item ejemplo -->
                <div class="OperacionesFItemCM border rounded p-2 position-relative">
                  <input type="hidden" name="OperacionesFContMarIds[]" class="OperacionesFContMarId">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <input type="text" class="form-control OperacionesFContMarInput"
                        placeholder="Escribe # contenedor marítimo (solo 'en operación')">
                      <div class="list-group OperacionesFSugCM"
                        style="position:absolute; z-index:1055; width:calc(50% - .5rem); display:none;"></div>
                      <small class="text-muted">Ej.: MG001 (LB-01)</small>
                    </div>
                    <div class="col-md-3">
                      <input type="number" class="form-control OperacionesFBultosInput"
                        name="OperacionesFBultosAsignados[]" placeholder="Bultos asignados" min="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                      <button type="button" class="btn btn-sm btn-outline-danger OperacionesFBtnQuitarCM">
                        <i data-feather="trash-2"></i>
                      </button>
                    </div>
                  </div>
                  <!-- Etiquetas derivadas (solo lectura) -->
                  <div class="d-flex flex-wrap gap-2 mt-2 small">
                    <span class="badge bg-light text-dark">Op. Marítima: <strong class="OperacionesFTagOpM">—</strong></span>
                    <span class="badge bg-light text-dark">Cliente: <strong class="OperacionesFTagCliente">—</strong></span>
                    <span class="badge bg-light text-dark">Bultos disp.: <strong class="OperacionesFTagDisp">—</strong></span>
                  </div>
                </div>
              </div>

              <!-- Template oculto -->
              <template id="OperacionesFTemplateCM">
                <div class="OperacionesFItemCM border rounded p-2 position-relative">
                  <input type="hidden" name="OperacionesFContMarIds[]" class="OperacionesFContMarId">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <input type="text" class="form-control OperacionesFContMarInput"
                        placeholder="Escribe # contenedor marítimo (solo 'en operación')">
                      <div class="list-group OperacionesFSugCM"
                        style="position:absolute; z-index:1055; width:calc(50% - .5rem); display:none;"></div>
                      <small class="text-muted">Ej.: MG001 (LB-01)</small>
                    </div>
                    <div class="col-md-3">
                      <input type="number" class="form-control OperacionesFBultosInput"
                        name="OperacionesFBultosAsignados[]" placeholder="Bultos asignados" min="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                      <button type="button" class="btn btn-sm btn-outline-danger OperacionesFBtnQuitarCM">
                        <i data-feather="trash-2"></i>
                      </button>
                    </div>
                  </div>
                  <div class="d-flex flex-wrap gap-2 mt-2 small">
                    <span class="badge bg-light text-dark">Op. Marítima: <strong class="OperacionesFTagOpM">—</strong></span>
                    <span class="badge bg-light text-dark">Cliente: <strong class="OperacionesFTagCliente">—</strong></span>
                    <span class="badge bg-light text-dark">Bultos disp.: <strong class="OperacionesFTagDisp">—</strong></span>
                  </div>
                </div>
              </template>
            </div>

            <!-- Panel lateral: Clientes detectados + Totales -->
            <div class="col-4">
              <div class="card h-100">
                <div class="card-header py-2">
                  <strong><i data-feather="users" class="me-1"></i> Clientes detectados (derivados)</strong>
                </div>
                <div class="card-body">
                  <ul id="OperacionesFClientesDetectados" class="list-unstyled mb-3 small">
                    <!-- se llena desde JS con clientes únicos derivados de los CM -->
                  </ul>
                  <hr>
                  <div class="small">
                    <div class="d-flex justify-content-between">
                      <span>Bultos totales asignados al FERRO:</span>
                      <strong id="OperacionesFTotalBultosAsignados">0</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span>Contenedores marítimos (dentro del FERRO):</span>
                      <strong id="OperacionesFTotalCM">0</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notas -->
            <div class="col-md-12">
              <label class="form-label">Notas</label>
              <textarea id="OperacionesFNotas" name="OperacionesFNotas" class="form-control" rows="2"
                placeholder="Observaciones generales"></textarea>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cancelar
        </button>
        <button type="button" id="OperacionesFBtnGuardar" class="btn btn-primary">
          <i data-feather="save" class="me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  feather.replace();
</script>
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
  forzarMayusculas("costosOperacionFiltroOpNombre");
</script>