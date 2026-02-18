<style>
  .form-check-input {
    width: 1.3em;
    height: 1.3em;
    accent-color: var(--bs-primary);
    cursor: pointer;
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="anchor" class="me-1"></i> Operaciones Marítimas-Ferroviarias
      </h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalMaritimoFerro"
        id="maritimo_ferro_btnNuevaOperacion">
        <i data-feather="plus-circle" class="me-1"></i> Nueva Operación
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <select id="maritimo_ferro_filtroSubtipo" name="maritimo_ferro_filtroSubtipo" class="form-control"
          style="max-width:240px;">
          <option value="">Subtipo (Todos)</option>
          <?php if (!empty($data['subtipos'])): ?>
            <?php foreach ($data['subtipos'] as $st): ?>
              <option value="<?= (int)$st['id_subtipo']; ?>">
                <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>

        <input id="maritimo_ferro_buscarOperacion" class="form-control" style="max-width:260px;"
          placeholder="Buscar por código, BL o contenedor">

        <div class="col-md-2">
          <button class="btn btn-sm btn-outline-success" id="operaciones_mar_ExportarExcel">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning" id="operaciones_mar_ExportarPDF">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <!-- Filtro: Rango de fechas -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="d-flex align-items-center gap-2">
            <i data-feather="calendar"></i>
            <span class="small text-muted">Rango:</span>
          </div>

          <input type="date" id="maritimo_ferro_fechaInicio" name="maritimo_ferro_fechaInicio" class="form-control"
            style="max-width: 165px;" aria-label="Fecha inicio" />

          <input type="date" id="maritimo_ferro_fechaFin" name="maritimo_ferro_fechaFin" class="form-control"
            style="max-width: 165px;" aria-label="Fecha fin" />
        </div>

        <!-- Paginación -->
        <div class="ms-auto d-flex align-items-center gap-2">
          <label for="maritimo_ferro_perPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="maritimo_ferro_perPage" name="maritimo_ferro_perPage" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="small text-muted">por página</span>
        </div>
      </div>

      <!-- Tabla -->
      <div class="table-responsive table-sm">
        <table class="table table-hover align-middle  " id="operaciones_mar_TablaExportar">
          <thead class="table-success mt-1 p-2 mb-2">
            <tr class="text-center">
              <th> Código</th>
              <th>Subtipo</th>
              <th>ETD</th>
              <th>ETA</th>
              <th>Contenedor</th>
              <th>Naviera</th>
              <th>Forwarder</th>
              <th>Shipper</th>
              <th>Peso</th>
              <th>Bultos</th>
              <th>Tipo Contenedor</th>
              <th style="width:180px;">Transportista</th>
              <th>Broker</th>
              <th style="width:180px;">BL</th>
              <th>Puerto</th>
              <th>Cliente</th>
              <th>Forwarder</th>
              <th>Estatus</th>
              <th>ISF </th>
              <th>Cita en Puerto </th>
              <th>Caja/Ferro</th>
              <th>Destino</th>
              <th>Fecha Salida Ferro/Caja</th>
              <th>Ubicacion Actual</th>

              <th style="width:120px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="maritimo_ferro_tablaBody"></tbody>
        </table>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="maritimo_ferro_metaResumen">Mostrando 0–0 de 0</span>
          </div>
          <nav aria-label="Paginación de operaciones">
            <ul id="maritimo_ferro_paginacion" class="pagination pagination-sm mb-0"></ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: Operación Marítimo-Ferroviaria -->
<div class="modal fade" id="modalMaritimoFerro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i data-feather="plus-square" class="me-2"></i>
          <span id="tituloModalOperacion_mf">Nueva Operación Marítimo-Ferroviaria</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formOperacionMaritimoFerro" autocomplete="off">
          <input type="hidden" id="id_operacion_mf" name="id_operacion_mf" value="">

          <div class="row g-3">

            <!-- SUBTIPO -->
            <div class="col-md-4">
              <label class="form-label">Subtipo</label>
              <select id="subtipoOperacion_mf" name="subtipo_operacion_id_mf" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($data['subtipos'])): ?>
                  <?php foreach ($data['subtipos'] as $st): ?>
                    <option value="<?= (int)$st['id_subtipo']; ?>">
                      <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Número de Operación -->
            <div class="col-md-4">
              <label class="form-label">Número de Operación</label>
              <input type="text" id="numeroOperacion_mf" name="numero_operacion_mf" class="form-control" placeholder="JL-61">
              <small id="folioHelp_mf" class="text-muted d-block mt-1">Folio Preliminar</small>
            </div>

            <!-- Estado -->
            <div class="col-md-4">
              <label class="form-label">Estado</label>
              <select id="estatusId_mf" name="estatus_id_mf" class="form-control" required>
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

            <!-- Fechas -->
            <div class="col-md-3">
              <label class="form-label">ETD</label>
              <input type="date" id="etd_mf" name="etd_mf" class="form-control form-control-lg">
            </div>

            <div class="col-md-3">
              <label class="form-label">ETA</label>
              <input type="date" id="eta_mf" name="eta_mf" class="form-control form-control-lg">
            </div>

            <div class="col-md-3">
              <label class="form-label">Cita en Puerto</label>
              <input type="date" id="cita_puerto" name="cita_puerto" class="form-control form-control-lg">
            </div>

            <div class="col-md-3 d-flex flex-column">
              <label class="form-label">ISF</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="chkIsf" name="isf" value="1">
              </div>
            </div>

            <!-- BL -->
            <div class="col-md-3">
              <label class="form-label">BL</label>
              <input type="text" id="numeroBL_mf" name="numero_bl_mf" class="form-control" autocomplete="off"
                maxlength="40" pattern="[A-Za-z0-9]+"
                title="Solo letras y números, sin espacios ni caracteres especiales.">
            </div>

            <!-- Puerto -->
            <div class="col-md-3">
              <label class="form-label">Puerto de Arribo</label>
              <select id="puertoArribo_mf" name="puerto_arribo_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['puertos'])): ?>
                  <?php foreach ($data['puertos'] as $p): ?>
                    <option value="<?= (int)$p['id_puerto']; ?>">
                      <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Cliente -->
            <div class="col-md-6 position-relative">
              <label class="form-label">Cliente</label>
              <input type="hidden" id="clienteId_mf" name="cliente_id_mf">
              <input type="text" id="clienteNombre_mf" class="form-control" placeholder="Escribe para buscar cliente...">
              <div id="sugerenciasCliente_mf" class="list-group"
                style="position:absolute; z-index:1055; width:100%; display:none;"></div>
            </div>



            <!-- CONTENEDOR ÚNICO -->
            <div class="col-12">
              <div id="contenedoresRepeater_mf" class="vstack gap-2">
                <div class="contenedor-item position-relative border rounded p-2">
                  <input type="hidden" class="contenedor-id_mf">

                  <div class="row g-2 align-items-end">

                    <div class="col-md-5 position-relative">
                      <label class="form-label">Contenedor Maritimo</label>
                      <input type="text" class="form-control contenedor-input_mf" placeholder="Ej. MSKU1234567">
                      <div class="list-group sugerencias-contenedor_mf"
                        style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                    </div>

                    <div class="col-md-2">
                      <label class="form-label">Bultos</label>
                      <input type="number" min="0" step="1" class="form-control contenedor-bultos_mf" placeholder="0">
                    </div>

                    <!-- PESO a un lado del tipo contenedor 
                    <div class="col-md-3">
                      <label class="form-label">Peso Total (Kg)</label>
                      <input type="number"

                        id="pesoOperacion_mf"
                        class="form-control"
                        placeholder="0.00"
                        name="peso_operacion_mf">
                    </div>-->


                  </div>
                </div>
              </div>

              <!-- Template (compatibilidad si tu JS lo referencia) --->
              <template id="contenedorTemplate_mf">
                <div class="contenedor-item position-relative border rounded p-2">
                  <input type="hidden" class="contenedor-id_mf">
                  <div class="row g-2 align-items-end">
                    <div class="col-md-5 position-relative">
                      <label class="form-label">Contenedor Maritimo</label>
                      <input type="text" class="form-control contenedor-input_mf" placeholder="Ej. MSKU1234567">
                      <div class="list-group sugerencias-contenedor_mf"
                        style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Bultos</label>
                      <input type="number" min="0" step="1" class="form-control contenedor-bultos_mf" placeholder="0">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tipo Contenedor</label>
                      <select class="form-control contenedor-tipo_mf" name="tipo_contenedor_mf[]">

                        <option value="">Seleccione...</option>
                        <option value="20GP">20GP</option>
                        <option value="40GP">40GP</option>
                        <option value="40HC">40HC</option>
                        <option value="45HC">45HC</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Peso (Kg)</label>
                      <input type="number"
                        min="0"

                        class="form-control pesoOperacion_mf"
                        name="peso_operacion_mf"
                        placeholder="0.00">

                    </div>
                  </div>
                </div>
              </template>
            </div>
            <!-- Naviera -->
            <div class="col-md-2" id="campoNaviera_mf">
              <label class="form-label">Naviera</label>
              <select id="navieraId_mf" name="naviera_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['navieras'])): ?>
                  <?php foreach ($data['navieras'] as $n): ?>
                    <option value="<?= (int)$n['id_naviera']; ?>">
                      <?= htmlspecialchars($n['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <!-- FORWARDER + SHIPPER + BROKER + TRANSPORTISTA en el MISMO RENGLÓN -->
            <div class="col-md-2" id="campoForwarder_mf">
              <label class="form-label">Forwarder</label>
              <select id="forwarderId_mf" name="forwarder_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['forwarders'])): ?>
                  <?php foreach ($data['forwarders'] as $fw): ?>
                    <option value="<?= (int)$fw['id_forwarder']; ?>">
                      <?= htmlspecialchars($fw['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Shipper</label>
              <select id="shipperId_mf" name="shipper_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['shippers'])): ?>
                  <?php foreach ($data['shippers'] as $s): ?>
                    <option value="<?= (int)$s['id_shipper']; ?>">
                      <?= htmlspecialchars($s['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Broker</label>
              <select id="brokerId_mf" name="broker_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['brokers'])): ?>
                  <?php foreach ($data['brokers'] as $b): ?>
                    <option value="<?= (int)$b['id_broker']; ?>">
                      <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Transportista</label>
              <select id="transportistaId_mf" name="transportista_id_mf" class="form-control">
                <option value="">Seleccione...</option>
                <?php if (!empty($data['transportistas'])): ?>
                  <?php foreach ($data['transportistas'] as $t): ?>
                    <option value="<?= (int)$t['id_transportista']; ?>">
                      <?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Notas -->
            <div class="col-md-12">
              <label class="form-label">Notas</label>
              <textarea id="notas_mf" name="notas_mf" class="form-control" rows="2"
                placeholder="Observaciones generales"></textarea>
            </div>

          </div><!-- row -->
        </form>
      </div><!-- modal-body -->

      <div class="modal-footer d-flex justify-content-between">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i data-feather="x-circle" class="me-1"></i> Cancelar
          </button>
          <button type="button" id="btnGuardarOperacion_mf" class="btn btn-success" disabled>
            <i data-feather="save" class="me-1"></i> Guardar
          </button>
        </div>
      </div>

    </div>
  </div>
</div>



<!-- =========================================================
     MODAL: Asignar Caja/Ferro a Operación Marítima (N↔N)
     - NO muestra FO-id (interno)
     - Identificador visible: numero_ferro / caja (contenedor físico)
========================================================= -->

<!-- Modal: Asignar Ferro/Caja -->
<div class="modal fade" id="modalAsignarFerroCaja" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <!-- ===== Header ===== -->
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="truck"></i>
          <span>Asignar Caja/Ferro</span>
          <span class="badge bg-light text-dark ms-2" id="asigFerro_badgeCodigo">—</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- ===== Body ===== -->
      <div class="modal-body">

        <!-- Hidden refs (los setea el JS cuando abres el modal) -->
        <input type="hidden" id="asigFerro_operacionId" name="asigFerro_operacionId" value="">
        <input type="hidden" id="asigFerro_operacionCodigo" name="asigFerro_operacionCodigo" value="">

        <div class="row g-3">

          <!-- =======================
               Izquierda: Vincular
          ======================== -->
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">

                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                  <div class="fw-semibold">
                    <i data-feather="link" class="me-1"></i>
                    Vincular esta operación a un Ferro/Caja
                  </div>
                </div>

                <!-- Form de captura -->
                <form id="asigFerro_formVincular" autocomplete="off">
                  <div class="row g-2">

                    <!-- Ferro/Caja -->
                    <div class="col-md-6 position-relative">
                      <label class="form-label">Ferro/Caja</label>
                      <input
                        type="text"
                        class="form-control"
                        id="asigFerro_inputNumero"
                        name="asigFerro_numero"
                        placeholder="Ej. FXEU12345 / CAJA-001"
                        autocomplete="off">
                      <div
                        id="asigFerro_sugerencias"
                        class="list-group shadow-sm"
                        style="position:absolute; z-index:1060; width:100%; display:none;"></div>
                    </div>
                    <!-- Empresa transportista -->
                    <div class="col-md-6">
                      <label class="form-label">Empresa transportista</label>
                      <select
                        id="asigFerro_empresaTransportista"
                        name="asigFerro_empresaTransportista"
                        class="form-control">
                        <option value="">Seleccione...</option>
                        <?php if (!empty($data['transportistas'])): ?>
                          <?php foreach ($data['transportistas'] as $et): ?>
                            <option value="<?= (int)$et['id_transportista']; ?>">
                              <?= htmlspecialchars($et['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>
                    <!-- Bultos -->
                    <div class="col-md-6">
                      <label class="form-label">Bultos asignados</label>
                      <input
                        type="number"
                        min="0"
                        step="1"
                        class="form-control"
                        id="asigFerro_inputBultos"
                        name="asigFerro_bultos"
                        placeholder="0">
                    </div>



                    <!-- Destino -->
                    <div class="col-md-6">
                      <label class="form-label">Destino</label>
                      <?php if (empty($data['ciudades'])): ?>
                        <div class="alert alert-warning py-2">No llegaron ciudades (data['ciudades'] vacío).</div>
                      <?php endif; ?>

                      <select id="asigFerro_destino" name="asigFerro_destino" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php if (!empty($data['ciudades'])): ?>
                          <?php foreach ($data['ciudades'] as $c): ?>
                            <option value="<?= (int)$c['id_ciudad']; ?>">
                              <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>
                    <!-- Fechas -->
                    <div class="col-md-6">
                      <label class="form-label">Fecha carga</label>
                      <input
                        type="date"
                        class="form-control"
                        id="asigFerro_inputFechaCarga"
                        name="asigFerro_fecha_carga">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Fecha salida</label>
                      <input
                        type="date"
                        class="form-control"
                        id="asigFerro_inputFechaSalida"
                        name="asigFerro_fecha_salida">
                    </div>


                    <!-- Notas -->
                    <div class="col-12">
                      <label class="form-label">Notas (opcional)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="asigFerro_inputNotas"
                        name="asigFerro_notas"
                        maxlength="255"
                        placeholder="Ej. Sale por patio 3 / Transfer a SD">
                    </div>

                    <!-- Acciones -->
                    <div class="col-12 d-flex gap-2 pt-2">
                      <button type="button" class="btn btn-success" id="asigFerro_btnVincular">
                        <i data-feather="check-circle" class="me-1"></i> Vincular
                      </button>
                      <button type="button" class="btn btn-outline-secondary" id="asigFerro_btnLimpiar">
                        <i data-feather="x" class="me-1"></i> Limpiar
                      </button>
                    </div>

                  </div>
                </form>

                <hr class="my-3">

                <!-- Lista de ferros/cajas vinculados a esta operación -->
                <div class="d-flex justify-content-between align-items-center mb-2 ">
                  <div class="fw-semibold">
                    <i data-feather="list" class="me-1"></i> Ferros/Cajas de esta operación
                  </div>
                  <span class="text-muted small" id="asigFerro_countFerros">0</span>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle mb-0 p-3">
                    <thead class="table-light">
                      <tr class="text-center">
                        <th class="text-start">Ferro/Caja</th>
                        <th style="width:120px;">Transportista</th>
                        <th style="width:120px;">Bultos</th>
                        <th style="width:140px;">F. carga</th>
                        <th style="width:140px;">F. salida</th>
                        <th style="width:90px;">Acción</th>
                      </tr>
                    </thead>
                    <tbody id="asigFerro_tbFerrosOperacion">
                      <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                          Sin vínculos todavía.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>

          <!-- =======================
               Derecha: Ops del Ferro/Caja
          ======================== -->
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">

                <div class="d-flex justify-content-between align-items-center gap-2">
                  <div class="fw-semibold">
                    <i data-feather="layers" class="me-1"></i> Operaciones en el Ferro/Caja seleccionado
                  </div>
                  <span class="badge bg-light text-dark" id="asigFerro_badgeFerroSel">—</span>
                </div>

                <div class="text-muted small mt-1">
                  Selecciona un Ferro/Caja desde la lista de la izquierda para ver su consolidado.
                </div>

                <hr class="my-3">

                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr class="text-center">
                        <th style="width:140px;">Código</th>
                        <th class="text-start">Cliente</th>
                        <th style="width:120px;">Contenedor</th>
                        <th style="width:110px;">Bultos/Cartones/Rollos Totales</th>
                        <th style="width:110px;">Bultos/Cartones/Rollos Enviados</th>
                      </tr>
                    </thead>
                    <tbody id="asigFerro_tbOpsEnFerro">
                      <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                          Selecciona un Ferro/Caja de la lista izquierda.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>

        </div><!-- row -->
      </div><!-- modal-body -->

      <!-- ===== Footer ===== -->
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cerrar
        </button>
      </div>

    </div>
  </div>
</div>


<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/Operaciones_Maritimas/operaciones_llenado_catalogo.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/Operaciones_Maritimas/operaciones_registrar.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/AsignacionFerros/asignacion_ferro_catalogo.js"></script>
<script>
  // Mayúsculas automáticas (MF) para contenedores (incluye filas agregadas por template)
  document.getElementById("contenedoresRepeater_mf").addEventListener("input", function(e) {
    if (e.target.classList.contains("contenedor-input_mf")) {
      e.target.value = e.target.value.toUpperCase();
    }
  });


  document.getElementById("contenedoresRepeater_mf").addEventListener("input", function(e) {
    if (e.target.classList.contains("contenedor-bultos_mf")) {
      // Enteros >= 0
      let v = e.target.value === "" ? "" : String(parseInt(e.target.value, 10));
      if (v === "NaN") v = "";
      if (v !== "" && parseInt(v, 10) < 0) v = "0";
      e.target.value = v;
    }
  });
</script>