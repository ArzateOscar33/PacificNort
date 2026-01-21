<style>
  .modal-xxl-wide {
    max-width: min(1600px, calc(100vw - 2rem));
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="navigation" class="me-1"></i> Operaciones Por Partida (Facturas)
      </h5>

      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalOperacionesPartida"
        id="operaciones_partida_btnNuevaFactura">
        <i data-feather="plus-circle" class="me-1"></i> Nueva Factura
      </button>
    </div>

    <div class="card-body">

      <!-- Filtros -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        <select id="operaciones_partida_filtroXDock" name="operaciones_partida_filtroXDock" class="form-control"
          style="max-width:240px;">
          <option value="">Selecciona bodega</option>
          <?php if (!empty($data['bodegas'])): ?>
          <?php foreach ($data['bodegas'] as $b): ?>
          <option value="<?= (int)$b['id_bodega']; ?>">
            <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>

        <input id="operaciones_partida_buscar" class="form-control" style="max-width:320px;"
          placeholder="Buscar por factura o proveedor" autocomplete="off">

        <div class="col-md-2">
          <button class="btn btn-sm btn-outline-success" id="operaciones_partida_ExportarExcel">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
          <button class="btn btn-sm btn-outline-warning" id="operaciones_partida_ExportarPDF">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="d-flex align-items-center gap-2">
            <i data-feather="calendar"></i>
            <span class="small text-muted">Recibido:</span>
          </div>

          <input type="date" id="operaciones_partida_fechaInicio" class="form-control" style="max-width: 165px;">
          <input type="date" id="operaciones_partida_fechaFin" class="form-control" style="max-width: 165px;">
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
          <label for="operaciones_partida_perPage" class="mb-0 small text-muted">Mostrar</label>
          <select id="operaciones_partida_perPage" class="form-control" style="width: 90px;">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="small text-muted">por página</span>
        </div>
      </div>

      <!-- Tabla de facturas -->
      <div class="table-responsive">
        <table class="table align-middle" id="operaciones_partida_TablaFacturasExportar">
          <thead class="table-dark">
            <tr class="text-center">
              <th style="width:120px;">ID</th>
              <th style="width:150px;">Bodega</th>
              <th style="width:220px;">Revisión</th>
              <th style="width:160px;">Número de factura</th>
              <th style="min-width:220px;">Pallets INV</th>
              <th style="min-width:220px;">Proveedor</th>
              <th style="width:160px;">Fecha recibido</th>
              <th style="width:140px;"># Productos</th>
              <th style="width:150px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="operaciones_partida_facturasBody">

            <!-- Ejemplo -->
            <tr class="text-center">
              <td class="fw-semibold">FAC-00043</td>
              <td>San Diego</td>
              <td>
                <div class="d-flex flex-column gap-1 align-items-center">
                  <span class="badge bg-success text-white">Sí</span>
                </div>
              </td>
              <td>43</td>
              <td>26</td>
              <td class="text-start">PLATINUM</td>
              <td>22-abr-25</td>
              <td><span class="badge bg-light text-dark border">5</span></td>
              <td>
                <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                  <button type="button" class="btn btn-outline-primary btn-sm btnVerProductosFactura"
                    data-bs-toggle="modal" data-bs-target="#modalProductosFactura" data-invoice="43"
                    data-vendor="PLATINUM" data-xdock="San Diego" data-recibido="22-abr-25" data-revision="SI"
                    data-costo="20" data-pallets_rcv="26" title="Ver productos">
                    <i data-feather="list"></i>
                  </button>

                  <button type="button" class="btn btn-outline-warning" title="Editar encabezado">
                    <i data-feather="edit"></i>
                  </button>
                  <button type="button" class="btn btn-outline-danger" title="Eliminar">
                    <i data-feather="trash-2"></i>
                  </button>
                </div>
              </td>
            </tr>
            <!-- /Ejemplo -->

          </tbody>
        </table>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
          <div class="small text-muted">
            <span id="operaciones_partida_metaResumen">Mostrando 0-0 de 0</span>
          </div>
          <nav aria-label="Paginación de facturas">
            <ul id="operaciones_partida_paginacion" class="pagination pagination-sm mb-0"></ul>
          </nav>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ===================== MODAL: SOLO ENCABEZADO DE FACTURA ===================== -->
<div class="modal fade" id="modalOperacionesPartida" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-xxl-wide">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="plus-square"></i>
          <span id="operaciones_partida_tituloModal">Nueva Factura</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <form id="formOperacionesPartida" autocomplete="off">
          <input type="hidden" id="operaciones_partida_id" name="operaciones_partida_id" value="">

          <div class="row g-3">

<div class="col-md-4">
  <label class="form-label">Bodega</label>
  <select id="operaciones_partida_bodega" name="operaciones_partida_bodega" class="form-control">
    <option value="">Selecciona bodega</option>

    <?php if (!empty($data['bodegas'])): ?>
      <?php foreach ($data['bodegas'] as $b): ?>
        <option value="<?= (int)$b['id_bodega']; ?>">
          <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>
        </option>
      <?php endforeach; ?>
    <?php endif; ?>

  </select>
</div>


            <div class="col-md-2">
              <label class="form-label d-block">Revisión</label>
              <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="operaciones_partida_revision" name="revision_pasa">
                <label class="form-check-label" for="operaciones_partida_revision">Sí</label>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">Pallets INV (Factura)</label>
              <input type="number" min="0" step="1" id="operaciones_partida_pallets_inv" name="pallets_inv"
                class="form-control" placeholder="0">
            </div>

            <div class="col-md-4">
              <label class="form-label">Número de factura</label>
              <input type="text" id="operaciones_partida_factura" name="invoice_number" class="form-control"
                placeholder="Ej. 43"
                onkeyup="this.value=this.value.toUpperCase()"
                style="text-transform: uppercase;">
            </div>

            <div class="col-md-6">
              <label class="form-label">Proveedor</label>
              <input type="text" id="operaciones_partida_proveedor" name="vendor_name" class="form-control"
                placeholder="Ej. PLATINUM" onkeyup="this.value=this.value.toUpperCase()"
                style="text-transform: uppercase;">
            </div>

            <div class="col-md-3">
              <label class="form-label">Fecha de recibido</label>
              <input type="date" id="operaciones_partida_fechaRecibido" name="received_date" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Notas</label>
              <input type="text" id="operaciones_partida_notas" name="comentarios" class="form-control"
                placeholder="Opcional">
            </div>

          </div>
        </form>

        <div class="alert alert-light border d-flex align-items-start gap-2 mt-3 mb-0">
          <i data-feather="info" class="mt-1"></i>
          <div class="small">
            Los productos se registran desde el botón <strong>Ver productos</strong> de la factura en el listado.
          </div>
        </div>

      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cerrar
        </button>

        <button type="button" id="operaciones_partida_btnGuardarEncabezado" class="btn btn-success">
          <i data-feather="save" class="me-1"></i> Guardar encabezado
        </button>
      </div>

    </div>
  </div>
</div>
<!-- ===================== MODAL: PRODUCTOS DE FACTURA ===================== -->
<div class="modal fade " id="modalProductosFactura" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-xxl-wide">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <div class="d-flex flex-column">
          <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
            <i data-feather="list"></i>
            <span>Productos de Factura</span>
            <span class="badge bg-light text-dark" id="pf_badgeCount">0</span>
          </h5>

          <div class="small text-white-50 mt-1">
            <span class="me-2">Factura: <span class="fw-semibold text-white" id="pf_lblFactura">—</span></span>
            <span class="me-2"> Proveedor: <span class="fw-semibold text-white" id="pf_lblProveedor">—</span></span>
            <span class="me-2"> Bodega: <span class="fw-semibold text-white" id="pf_lblXdock">—</span></span>
            <span class="me-2"> Recibido: <span class="fw-semibold text-white" id="pf_lblRecibido">—</span></span>
            <span class="me-2"> Revisión: <span class="fw-semibold text-white" id="pf_lblRevision">—</span></span>
            <span class="me-2"> Pallets INV (Factura): <span class="fw-semibold text-white"
                id="pf_lblPalletsRcv">—</span></span>

          </div>

        </div>
            <div class="ms-auto d-flex align-items-end justify-content-end col-md-8" >
            <span class="small text-muted">Totales:</span>
            <span class="badge bg-success text-white" id="pf_totalCajas">Cajas: 0</span>
            <span class="badge bg-primary text-white" id="pf_totalPiezas">Piezas: 0</span>
            <span class="badge bg-secondary text-white" id="pf_totalPalletsRcv">Pallets RCV: 0</span>
          </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <!-- Hidden opcional para ID real de factura -->
        <input type="hidden" id="pf_invoice_id" value="">

 



        <!-- Acciones de tabla ( + arriba a la derecha ) -->
        <div class="d-flex justify-content-end mb-2">
          <!-- MISMO ID, solo reubicado y con icono + -->
          <button type="button" class="btn btn-success btn-sm" id="pf_btnAgregarLinea" title="Agregar producto">
            <i data-feather="plus"></i>
          </button>
        </div>

        <div class="table-responsive">
          <table class="table align-middle" id="pf_tablaExportar">
            <thead class="table-dark">
              <tr class="text-center">
                <th style="min-width:300px;">Descripción</th>
                <th style="width:160px;">UPC</th>
                <th style="width:160px;">Marca</th>
                <th style="width:160px;">Expiración</th>
                <th style="width:120px;">Inner</th>
                <th style="width:120px;">Case</th>
                <th style="width:120px;">Pallets RCV</th>
                <th style="width:120px;"># Cajas</th>
                <th style="width:120px;"># Piezas</th>
                <th style="width:140px;">Acciones</th>
              </tr>
            </thead>

            <!-- Importante: mismo ID -->
            <tbody id="pf_tbody"></tbody>
          </table>
        </div>

        <div id="pf_empty" class="alert alert-light border d-none">
          No hay productos para mostrar.
        </div>

        <!-- Template fila editable -->
        <template id="pf_tplFilaProducto">
          <tr class="text-center">
            <td class="text-start">
              <input type="text" class="form-control form-control-sm pf_descripcion"
                placeholder="Ej. LOREAL MATTE SIGNATURE LIQUID EYELINER">
            </td>

            <td>
              <input type="text" class="form-control form-control-sm pf_upc" placeholder="Código de barras (UPC)">
            </td>

            <td>
              <input type="text" class="form-control form-control-sm pf_marca" placeholder="Opcional / NA">
            </td>

            <td>
              <input type="date" class="form-control form-control-sm pf_expiracion">
            </td>

            <td>
              <input type="text" class="form-control form-control-sm pf_inner" placeholder="Opcional">
            </td>

            <td>
              <input type="text" class="form-control form-control-sm pf_case" placeholder="Opcional">
            </td>

            <td>
              <input type="number" min="0" step="1" class="form-control form-control-sm pf_pallets_rcv" placeholder="0">
            </td>

            <td>
              <input type="number" min="0" step="1" class="form-control form-control-sm pf_cajas" placeholder="0">
            </td>

            <td>
              <input type="number" min="0" step="1" class="form-control form-control-sm pf_piezas" placeholder="0">
            </td>

            <td>
              <div class="btn-group btn-group-sm" role="group">
 
                <button type="button" class="btn btn-outline-danger pf_btnEliminarFila"   title="Eliminar producto">
                  <i data-feather="trash-2"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>

      </div>

      <!-- Footer: Guardar a la derecha junto a Cerrar -->
      <div class="modal-footer d-flex justify-content-between">
        <div class="small text-muted" id="pf_meta">
          Mostrando 0 de 0
        </div>

        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i data-feather="x-circle" class="me-1"></i> Cerrar
          </button>

          <!-- MISMO ID: ahora junto a cerrar -->
          <button type="button" class="btn btn-primary" id="pf_btnGuardarProductos" title="Guardar cambios">
            <i data-feather="save" class="me-1"></i> Guardar
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_factura_catalogo.js"></script>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_factura_registrar.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_catalogo.js"></script>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_registrar.js"></script>