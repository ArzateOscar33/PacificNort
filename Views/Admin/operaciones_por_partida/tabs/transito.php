<style>
  .modal-xxl-wide {
    max-width: min(1600px, calc(100vw - 2rem));
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="truck" class="me-1"></i> Partidas en Tránsito (Envíos por Factura)
      </h5>

      <button class="btn btn-light btn-sm" id="partidas_transito_btnRefrescar" type="button">
        <i data-feather="refresh-cw" class="me-1"></i> Refrescar
      </button>
    </div>

    <div class="card-body">

      <!-- ===================== FILTROS / SELECCIÓN DE FACTURA ===================== -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        <!-- FACTURA: INPUT + SUGERENCIAS (MISMO PATRÓN QUE NAVIERAS) -->
        <div class="position-relative" style="max-width:220px; width:100%;">
          <input type="hidden" id="partidas_transito_facturaId" value="">
          <input type="text" id="partidas_transito_buscarFactura" class="form-control" placeholder="Buscar factura..."
            autocomplete="off">
          <div id="partidas_transito_sugerenciasFacturas" class="list-group position-absolute w-100 z-3"
            style="z-index:999;">
          </div>
        </div>

        <!-- QUITAMOS PROVEEDOR (SE TOMARÁ DE LA FACTURA SELECCIONADA) -->

        <input id="partidas_transito_buscarProducto" class="form-control" style="max-width:320px;"
          placeholder="Buscar producto por descripción / UPC / marca" autocomplete="off">

      </div>

      <!-- ===================== TABLA DE PRODUCTOS DE LA FACTURA ===================== -->
      <div class="table-responsive">
        <table class="table table align-middle" id="partidas_transito_tablaProductos">
          <thead class="table-dark">
            <tr class="text-center">
              <th style="min-width:280px;">Producto</th>
              <th style="width:140px;">UPC</th>
              <th style="width:140px;">Marca</th>

              <th style="width:120px;">Cajas (Total)</th>

              <!-- REEMPLAZA DESTINOS FIJOS POR 1 COLUMNA -->
              <th style="min-width:420px;">Enviados / Pendientes de Envio</th> 
              <th style="width:130px;">Acción</th>
            </tr>
          </thead>
          <tbody id="partidas_transito_tbodyProductos"></tbody>
        </table>
      </div>

      <div id="partidas_transito_empty" class="alert alert-light border d-none mb-0">
        Selecciona una factura para visualizar sus productos.
      </div>

    </div>
  </div>
</div>
<!-- ===================== MODAL: REGISTRAR ENVÍOS (MÚLTIPLES) POR PRODUCTO ===================== -->
<div class="modal fade" id="modalPartidasTransitoEnvio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl  modal-xxl-wide modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <div class="d-flex flex-column">
          <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
            <i data-feather="send"></i>
            <span>Registrar Envíos</span>
          </h5>
          <div class="small text-white-50 mt-1">
            Múltiples destinos por producto (parciales)
          </div>
        </div>

        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="partidas_transito_formEnvio" autocomplete="off">
          <input type="hidden" id="partidas_transito_idProducto" value="">
          <input type="hidden" id="partidas_transito_factura" value="">

          <!-- META / RESUMEN -->
          <div class="alert alert-light border mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
              <div class="small">
                <div><span class="text-muted">Factura:</span> <span class="fw-semibold"
                    id="partidas_transito_lblFactura">—</span></div>
                <div><span class="text-muted">Producto:</span> <span class="fw-semibold"
                    id="partidas_transito_lblProducto">—</span></div>
              </div>

              <div class="text-end">
                <div class="small text-muted">Disponibles para enviar</div>
                <span class="badge bg-success fs-6 text-white" id="partidas_transito_badgeDisponibles">0</span>
                <input type="hidden" id="partidas_transito_cajasDisponibles" value="0">
              </div>
            </div>
          </div>

          <!-- HEADER DE ACCIONES DE RENGLONES -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">
              Detalle de Envíos

            </div>

            <button type="button" class="btn btn-success btn-sm" id="partidas_transito_btnAddRow"
              title="Agregar renglón">
              <i data-feather="plus" class="me-1"></i> Agregar
            </button>
          </div>

          <!-- TABLA DE RENGLONES -->
          <div class="table border rounded">
            <table class="table  align-middle mb-0" id="partidas_transito_tblEnvios">
              <thead class="table-dark">
                <tr class="text-center">
                  <th style="min-width:240px;" class="text-start">Destino (ciudad)</th>
                  <th style="width:160px;">Fecha envío</th>
                  <th style="min-width:260px;" class="text-start">Caja / Ferro</th>
                  <th style="width:170px;">Cajas a enviar</th>
                  <th style="width:160px;">Estatus</th>
                  <th style="min-width:220px;" class="text-start">Notas</th>

                  <th style="width:90px;">Acciones</th>
                </tr>
              </thead>

              <tbody id="partidas_transito_tbodyEnvios">
                <!-- Row template (1 por defecto) -->
                <tr class="partidas_transito_row" data-index="0" data-envio-id="0">
                  <!-- DESTINO con sugerencias -->
                  <td class="text-start">
                    <input type="hidden" class="pt_destino_id" value="">
                    <div class="position-relative">
                      <input type="text" class="form-control form-control-sm pt_destino_txt"
                        placeholder="Escribe ciudad... (Ej. TIJ)" autocomplete="off" required>

                      <div class="list-group position-absolute w-100 z-3 pt_destino_sug"
                        style="z-index:999; display:none;">
                        <!-- sugerencias aquí -->
                      </div>
                    </div>
                  </td>

                  <!-- FECHA -->
                  <td class="text-center">
                    <input type="date" class="form-control form-control-sm pt_fecha_envio" required>
                  </td>

                  <!-- CAJA/FERRO con sugerencias -->
                  <td class="text-start">
                    <input type="hidden" class="pt_fisico_id" value="">
                    <div class="position-relative">
                      <input type="text" class="form-control form-control-sm pt_fisico_txt"
                        placeholder="Buscar Caja/Ferro (Ej. FO-22 / Ferro 17)" autocomplete="off" required>

                      <div class="list-group position-absolute w-100 z-3 pt_fisico_sug"
                        style="z-index:999; display:none;">
                        <!-- sugerencias aquí -->
                      </div>
                    </div>

                  </td>

                  <!-- CAJAS -->
                  <td class="text-center">
                    <div class="input-group input-group-sm">
                      <input type="number" min="1" step="1" class="form-control pt_cajas" placeholder="0" required>

                    </div>

                  </td>

                  <!-- ESTATUS (sin BD) -->
                  <td class="text-center">
                    <select class="form-select form-control pt_estatus" required>
                      <option value="1" selected>En camino</option>
                      <option value="2">Entregado</option>
                    </select>
                  </td>
                  <!-- NOTAS POR RENGLÓN -->
                  <td class="text-start">
                    <input type="text" class="form-control form-control-sm pt_nota" placeholder="Nota (opcional)"
                      maxlength="255">
                  </td>

                  <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm pt_btnRemoveRow" title="Quitar renglón">
                      <i data-feather="trash-2"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- RESUMEN DE ASIGNACIÓN (UI) -->
          <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
            <div class="small text-muted">
              Total asignado en renglones:
              <span class="fw-semibold" id="partidas_transito_lblTotalAsignado">0</span>
            </div>
            <div class="small text-muted">
              Restantes en bodega:
              <span class="fw-semibold" id="partidas_transito_lblRestantes">0</span>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cancelar
        </button>

        <button type="button" class="btn btn-success" id="partidas_transito_btnGuardarEnvio">
          <i data-feather="save" class="me-1"></i> Guardar
        </button>
      </div>

    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_rutas_catalogo.js">
</script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_rutas_registrar.js">
</script>