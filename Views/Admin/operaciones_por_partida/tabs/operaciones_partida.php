<style>
  .modal-dialog.modal-xxl-wide {
    --bs-modal-width: 80vw;
    max-width: 80vw;
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
              <th style="width:220px;">Estatus Factura</th>
              <th style="width:160px;">Número de factura</th>
              <th style="width:160px;">Cliente</th>
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
  <div class="modal-dialog modal-dialog-scrollable modal-xl">
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

            <div class="col-md-2">
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
              <label class="form-label">Cliente</label>
              <select name="operaciones_partida_cliente" id="operaciones_partida_cliente" class="form-select">
                <option value="">Selecciona Cliente</option>

                <?php if (!empty($data['clientes'])): ?>
                  <?php foreach ($data['clientes'] as $c): ?>
                    <option value="<?= (int)$c['id_cliente']; ?>">
                      <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>

              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label d-block">Estatus</label>
              <select name="operaciones_partida_revision_select" id="operaciones_partida_revision_select" class="form-select">
                <option value="">Selecciona Revisión</option>
                <option value="0">Factura No Revisada</option>
                <option value="1">Factura Revisada</option>
                <option value="2">Envio sin Revision</option>
                <option value="3">Factura No Cuadrada</option>

              </select>
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
          <h5 class="modal-title d-flex align-items-end gap-2 mb-0">
            <i data-feather="list"></i>
            <span>Productos de Factura</span>
            <span class="badge bg-light text-dark" id="pf_badgeCount">0</span>
          </h5>

          <div class="col-md-12 text-white-50 mt-1 mb-1 m-2 ">
            <span class="me-2">Factura: <span class="fw-semibold text-white" id="pf_lblFactura">—</span></span>
            <span class="me-2"> Proveedor: <span class="fw-semibold text-white" id="pf_lblProveedor">—</span></span>
            <div class="row">
              <span class="me-2"> Bodega: <span class="fw-semibold text-white" id="pf_lblXdock">—</span></span>
              <span class="me-2"> Recibido: <span class="fw-semibold text-white" id="pf_lblRecibido">—</span></span>
            </div>
            <span class="me-2"> Pallets INV (Factura): <span class="fw-semibold text-white"
                id="pf_lblPalletsRcv">—</span></span>

          </div>
          <div class="row">
            <div class="col-md-6">

              <span class="me-2 p-2">
                Revisión:
                <span class="fw-semibold badge p-3 mb-1 mt-1" id="pf_lblRevision">—</span>
              </span>
            </div>
          </div>

        </div>
        <div class="ms-auto d-flex align-items-end justify-content-end col-md-8">
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

          <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-success" id="operaciones_partida_productos_ExportarExcel">
              <i data-feather="file-text" class="me-1"></i> Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning d-none" id="operaciones_partida_productos_ExportarPDF">
              <i data-feather="file" class="me-1"></i> PDF
            </button>
          </div>
          <!--exportar pdf y excel-->

        </div>

        <div class="table-responsive">
          <table class="table align-middle" id="pf_tablaExportar">
            <thead class="table-dark">
              <tr class="text-center">
                <th style="min-width:300px;">Descripción</th>
                <th style="width:120px;">Item</th>
                <th style="width:160px;">UPC</th>
                <th style="width:160px;">Marca</th>
                <th style="width:160px;">Expiración</th>
                <th style="width:120px;">Inner</th>
                <th style="width:120px;">Case</th>
                <th style="width:120px;">Pallets RCV</th>
                <th style="width:120px;"># Cajas</th>
                <th style="width:120px;"># Piezas</th>
                <th style="width:140px;">Observaciones</th>
                <th style="width:140px;">Imagenes</th>
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
              <input type="text" class="form-control form-control-sm pf_item" placeholder="Item" required>
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
              <small class="text-muted fst-italic">Guarda primero</small>
            </td>
            <td>
              <input type="text" class="form-control form-control-sm pf_observaciones">
            </td>
            <td>
              <div class="btn-group btn-group-sm" role="group">

                <button type="button" class="btn btn-outline-danger pf_btnEliminarFila" title="Eliminar producto">
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
<!-- ===================== MINI-MODAL: FOTOS DE PRODUCTO ===================== -->
<div class="modal fade" id="modalFotosProducto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="image"></i>
          <span>Fotos del Producto</span>
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <!-- Info del producto -->
        <p class="text-muted small mb-3">
          <span id="fotoModal_lblDescripcion"></span>
        </p>

        <!-- Inputs ocultos de contexto -->
        <input type="hidden" id="fotoModal_productoId" value="">
        <input type="hidden" id="fotoModal_facturaId" value="">

        <!-- Las 3 posiciones de foto -->
        <div class="row g-3" id="fotoModal_slots">

          <!-- SLOT 1 -->
          <div class="col-md-4 text-center" id="fotoSlot_1">
            <div class="border rounded p-2 position-relative" style="min-height:180px;">
              <span class="badge bg-secondary mb-2">Foto 1</span>
              <div id="fotoPreview_1" class="mb-2">
                <i data-feather="image" style="width:48px;height:48px;color:#ccc;"></i>
              </div>
              <div id="fotoBtns_1">
                <label class="btn btn-outline-primary btn-sm w-100 mb-1" title="Subir foto 1">
                  <i data-feather="upload"></i> Subir
                  <input type="file" class="d-none fotoInput" data-orden="1" accept="image/jpeg,image/png,image/webp">
                </label>
                <button type="button" class="btn btn-outline-danger btn-sm w-100 d-none fotoEliminarBtn"
                  data-orden="1" data-id-foto="">
                  <i data-feather="trash-2"></i> Eliminar
                </button>
              </div>
            </div>
          </div>

          <!-- SLOT 2 -->
          <div class="col-md-4 text-center" id="fotoSlot_2">
            <div class="border rounded p-2 position-relative" style="min-height:180px;">
              <span class="badge bg-secondary mb-2">Foto 2</span>
              <div id="fotoPreview_2" class="mb-2">
                <i data-feather="image" style="width:48px;height:48px;color:#ccc;"></i>
              </div>
              <div id="fotoBtns_2">
                <label class="btn btn-outline-primary btn-sm w-100 mb-1" title="Subir foto 2">
                  <i data-feather="upload"></i> Subir
                  <input type="file" class="d-none fotoInput" data-orden="2" accept="image/jpeg,image/png,image/webp">
                </label>
                <button type="button" class="btn btn-outline-danger btn-sm w-100 d-none fotoEliminarBtn"
                  data-orden="2" data-id-foto="">
                  <i data-feather="trash-2"></i> Eliminar
                </button>
              </div>
            </div>
          </div>

          <!-- SLOT 3 -->
          <div class="col-md-4 text-center" id="fotoSlot_3">
            <div class="border rounded p-2 position-relative" style="min-height:180px;">
              <span class="badge bg-secondary mb-2">Foto 3</span>
              <div id="fotoPreview_3" class="mb-2">
                <i data-feather="image" style="width:48px;height:48px;color:#ccc;"></i>
              </div>
              <div id="fotoBtns_3">
                <label class="btn btn-outline-primary btn-sm w-100 mb-1" title="Subir foto 3">
                  <i data-feather="upload"></i> Subir
                  <input type="file" class="d-none fotoInput" data-orden="3" accept="image/jpeg,image/png,image/webp">
                </label>
                <button type="button" class="btn btn-outline-danger btn-sm w-100 d-none fotoEliminarBtn"
                  data-orden="3" data-id-foto="">
                  <i data-feather="trash-2"></i> Eliminar
                </button>
              </div>
            </div>
          </div>

        </div><!-- /row slots -->

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cerrar
        </button>
      </div>

    </div>
  </div>
</div>
<!-- /MINI-MODAL FOTOS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_factura_catalogo.js"></script>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_factura_registrar.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_catalogo.js"></script>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_registrar.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/exportar_productos.js"></script>
<script>
  // Excel
  document.getElementById('operaciones_partida_ExportarExcel')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: '#operaciones_partida_TablaFacturasExportar', // "#tablaEventos" o el elemento también funciona
      formato: 'xlsx',
      nombre: 'OperacionesPorPartida.xlsx',
      columnasOcultas: [8], // oculta columna ID
      soloVisibles: true,
      sheetName: 'Operaciones por Partida'
    });
  });

  // PDF
  document.getElementById('operaciones_partida_ExportarPDF')?.addEventListener('click', () => {
    ExportarTablas.exportar({
      ref: '#operaciones_partida_TablaFacturasExportar',
      formato: 'pdf',
      nombre: 'OperacionesPorPartida.pdf',
      titulo: 'Operaciones por Partida',
      orientacion: 'landscape', // o 'portrait'
      formatoPagina: 'letter', // o 'a4'
      columnasOcultas: [8],
      soloVisibles: true
    });
  });
</script>
<script>
  document.addEventListener("input", function(e) {
    const el = e.target;
    if (!el || !el.classList.contains("form-control")) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;

    el.value = (el.value || "").toUpperCase();

    // Mantener cursor (si el input lo soporta)
    if (typeof start === "number" && typeof end === "number") {
      el.setSelectionRange(start, end);
    }
  });
</script>