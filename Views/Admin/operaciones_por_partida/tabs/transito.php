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
          <input
            type="text"
            id="partidas_transito_buscarFactura"
            class="form-control"
            placeholder="Buscar factura..."
            autocomplete="off">
          <div
            id="partidas_transito_sugerenciasFacturas"
            class="list-group position-absolute w-100 z-3"
            style="z-index:999;">
          </div>
        </div>

        <!-- QUITAMOS PROVEEDOR (SE TOMARÁ DE LA FACTURA SELECCIONADA) -->

        <input id="partidas_transito_buscarProducto" class="form-control" style="max-width:320px;"
          placeholder="Buscar producto por descripción / UPC / marca" autocomplete="off">

        <div class="ms-auto d-flex align-items-center gap-2">
          <span class="small text-muted">Resumen:</span>
          <span class="badge bg-secondary text-white" id="partidas_transito_badgeProductos">Productos: 0</span>
          <span class="badge bg-success text-white" id="partidas_transito_badgeCajasTotal">Cajas: 0</span>
          <span class="badge bg-warning text-dark" id="partidas_transito_badgeCajasRestantes">Restantes: 0</span>
        </div>
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
              <th style="min-width:420px;">Destinos / Envíos</th>
              <th style="width:130px;">Caja/Ferro</th>

              <th style="width:150px;">Restantes (Bodega)</th>
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

<!-- ===================== MODAL: REGISTRAR ENVÍO POR PRODUCTO ===================== -->
<div class="modal fade" id="modalPartidasTransitoEnvio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="send"></i>
          <span>Registrar Envío</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="partidas_transito_formEnvio" autocomplete="off">
          <input type="hidden" id="partidas_transito_idProducto" value="">
          <input type="hidden" id="partidas_transito_factura" value="">

          <div class="alert alert-light border mb-3">
            <div class="small">
              <div><span class="text-muted">Factura:</span> <span class="fw-semibold" id="partidas_transito_lblFactura">—</span></div>
              <div><span class="text-muted">Producto:</span> <span class="fw-semibold" id="partidas_transito_lblProducto">—</span></div>
              <div><span class="text-muted">Cajas restantes:</span> <span class="fw-semibold" id="partidas_transito_lblRestantes">0</span></div>
            </div>
          </div>

          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Destino</label>
              <select id="partidas_transito_destino" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="TJ">Tijuana</option>
                <option value="LERMA">Lerma</option>
                <option value="GDL">Guadalajara</option>
                <option value="SB">San Bartolo</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fecha de envío</label>
              <input type="date" id="partidas_transito_fechaEnvio" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Caja / Ferro</label>
              <input type="text" id="partidas_transito_cajaFerro" class="form-control"
                placeholder="Ej. Caja 102 / FO-22 / Ferro 17" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Cajas enviadas</label>
              <input type="number" min="1" step="1" id="partidas_transito_cajasEnviadas" class="form-control"
                placeholder="0" required>
              <small class="text-muted d-block mt-1">No debe exceder las cajas restantes.</small>
            </div>

            <div class="col-md-12">
              <label class="form-label">Notas</label>
              <textarea id="partidas_transito_notasEnvio" class="form-control" rows="2"
                placeholder="Opcional"></textarea>
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
