<style>
    .modal-xxl-wide{
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
                    <option value="">XDock (Todos)</option>
                    <?php if (!empty($data['xdocks'])): ?>
                    <?php foreach ($data['xdocks'] as $xd): ?>
                    <option value="<?= htmlspecialchars($xd, ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($xd, ENT_QUOTES, 'UTF-8'); ?>
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

                    <input type="date" id="operaciones_partida_fechaInicio" class="form-control"
                        style="max-width: 165px;">
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
                            <th style="width:120px;">Código</th>
                            <th style="width:150px;">XDock</th>
                            <th style="width:200px;">Costo revisión + costo</th>
                            <th style="width:160px;">Número de factura</th>
                            <th style="min-width:220px;">Proveedor</th>
                            <th style="width:160px;">Fecha recibido</th>
                            <th style="width:140px;"># Productos</th>
                            <th style="width:150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="operaciones_partida_facturasBody">
                        <tr class="text-center">
                            <td class="fw-semibold">FAC-00043</td>
                            <td>San Diego</td>
                            <td>20</td>
                            <td>43</td>
                            <td class="text-start">PLATINUM</td>
                            <td>22-abr-25</td>
                            <td><span class="badge bg-light text-dark border">5</span></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                                    <button type="button" class="btn btn-outline-primary btn-sm btnVerProductosFactura"
                                        data-bs-toggle="modal" data-bs-target="#modalProductosFactura" data-invoice="43"
                                        data-vendor="PLATINUM" data-xdock="San Diego" data-recibido="22-abr-25"
                                        data-revision="20" title="Ver productos">
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

<!-- MODAL UNICO: Encabezado + Productos (2 pasos) -->
<div class="modal fade" id="modalOperacionesPartida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-xxl-wide">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i data-feather="plus-square"></i>
                    <span id="operaciones_partida_tituloModal">Nueva Factura</span>
                    <span class="badge bg-light text-dark ms-2" id="op_partida_badgePaso">Paso 1/2</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">

                <!-- ===================== PASO 1: ENCABEZADO ===================== -->
                <div id="op_partida_stepEncabezado">
                    <form id="formOperacionesPartida" autocomplete="off">
                        <input type="hidden" id="operaciones_partida_id" name="operaciones_partida_id" value="">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">XDock</label>
                                <input type="text" id="operaciones_partida_xdock" name="xdock" class="form-control"
                                    placeholder="Ej. San Diego" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Costo revisión + costo</label>
                                <input type="text" id="operaciones_partida_revision" name="revision"
                                    class="form-control" placeholder="Ej. 20">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Número de factura</label>
                                <input type="text" id="operaciones_partida_factura" name="invoice_number"
                                    class="form-control" placeholder="Ej. 43" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Proveedor</label>
                                <input type="text" id="operaciones_partida_proveedor" name="vendor_name"
                                    class="form-control" placeholder="Ej. PLATINUM" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Fecha de recibido</label>
                                <input type="date" id="operaciones_partida_fechaRecibido" name="received_date"
                                    class="form-control" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Notas</label>
                                <input type="text" id="operaciones_partida_notas" name="comentarios"
                                    class="form-control" placeholder="Opcional">
                            </div>
                        </div>
                    </form>

                    <div class="alert alert-light border d-flex align-items-start gap-2 mt-3 mb-0">
                        <i data-feather="info" class="mt-1"></i>
                        <div class="small">
                            Guarda primero el encabezado de la factura para habilitar el registro de productos (Paso 2).
                        </div>
                    </div>
                </div>

                <!-- ===================== PASO 2: PRODUCTOS (OCULTO AL INICIO) ===================== -->
                <div id="op_partida_stepProductos" class="d-none mt-4">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <i data-feather="list" class="me-1"></i> Productos de la factura
                            </h6>
                            <div class="small text-muted">
                                Factura: <span id="op_partida_lblFactura" class="fw-semibold"></span>
                                &nbsp;|&nbsp; Proveedor: <span id="op_partida_lblProveedor" class="fw-semibold"></span>
                            </div>
                        </div>

                        <!-- Botón + a la derecha (agrega filas) -->
                        <button type="button" class="btn btn-success btn-sm" id="op_partida_btnAgregarLinea"
                            title="Agregar producto">
                            <i data-feather="plus"></i>
                        </button>
                    </div>

                    <div class="alert alert-light border d-flex align-items-start gap-2" role="alert">
                        <i data-feather="info" class="mt-1"></i>
                        <div class="small">
                            Una fila = un producto (renglón del Excel). Pallets/cajas/piezas son por producto.
                            UPC, marca y expiración pueden ser nulos.
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table  align-middle" id="op_partida_tablaDetalle">
                            <thead class="table-dark">
                                <tr class="text-center">
                                    <th style="min-width:280px;">Descripción</th>
                                    <th style="width:160px;">UPC</th>
                                    <th style="width:140px;">Marca</th>
                                    <th style="width:170px;">Expiración</th>
                                    <th style="width:130px;">Inner Pack</th>
                                    <th style="width:120px;">Case Pack</th>

                                    <th style="width:120px;">Pallets RCV</th>
                                    <th style="width:120px;">Pallets INV</th>

                                    <th style="width:120px;"># Cajas</th>
                                    <th style="width:120px;"># Piezas</th>

                                    <th style="width:110px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="op_partida_detalleBody"></tbody>
                        </table>
                    </div>

                    <!-- Template de fila -->
                    <template id="op_partida_tplFilaProducto">
                        <tr class="text-center">

                            <td class="text-start">
                                <input type="text" class="form-control form-control-sm op_det_descripcion"
                                    placeholder="Ej. LOREAL MATTE SIGNATURE LIQUID EYELINER">
                            </td>

                            <td>
                                <input type="text" class="form-control form-control-sm op_det_upc"
                                    placeholder="Opcional">
                            </td>

                            <td>
                                <input type="text" class="form-control form-control-sm op_det_marca"
                                    placeholder="Opcional / NA">
                            </td>

                            <td>
                                <input type="date" class="form-control form-control-sm op_det_expiracion">
                            </td>

                            <td>
                                <input type="text" class="form-control form-control-sm op_det_inner"
                                    placeholder="Opcional">
                            </td>

                            <td>
                                <input type="text" class="form-control form-control-sm op_det_case"
                                    placeholder="Opcional">
                            </td>

                            <td>
                                <input type="number" min="0" step="1"
                                    class="form-control form-control-sm op_det_pallets_rcv" placeholder="0">
                            </td>

                            <td>
                                <input type="number" min="0" step="1"
                                    class="form-control form-control-sm op_det_pallets_inv" placeholder="0">
                            </td>

                            <td>
                                <input type="number" min="0" step="1" class="form-control form-control-sm op_det_cajas"
                                    placeholder="0">
                            </td>

                            <td>
                                <input type="number" min="0" step="1" class="form-control form-control-sm op_det_piezas"
                                    placeholder="0">
                            </td>

                            <td>
                                <button type="button" class="btn btn-outline-danger btn-sm op_det_btnEliminar"
                                    title="Eliminar fila">
                                    <i data-feather="trash-2"></i>
                                </button>
                            </td>

                        </tr>
                    </template>

                </div>

            </div>

            <div class="modal-footer d-flex justify-content-between">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x-circle" class="me-1"></i> Cerrar
                </button>

                <div class="d-flex gap-2">
                    <!-- Paso 1 -->
                    <button type="button" id="operaciones_partida_btnGuardarEncabezado" class="btn btn-success">
                        <i data-feather="save" class="me-1"></i> Guardar encabezado
                    </button>

                    <!-- Paso 2 -->
                    <button type="button" id="operaciones_partida_btnGuardarProductos" class="btn btn-success d-none">
                        <i data-feather="save" class="me-1"></i> Guardar productos
                    </button>
                </div>

            </div>

        </div>
    </div>
</div>
<!-- ===================== DEMO SCRIPT (SIN BD) =====================
Objetivo:
- Ver el modal completo funcionando como “Paso 1 (encabezado) -> Paso 2 (productos)”
- Cargar datos de ejemplo (Factura 43) para visualizar
- Agregar N filas con botón "+"
- “Guardar encabezado” solo desbloquea Paso 2
- “Guardar productos” NO inserta; solo muestra un JSON en pantalla
=============================================================== -->
<!-- ===================== MODAL: PRODUCTOS DE FACTURA ===================== -->
<div class="modal fade" id="modalProductosFactura" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <div class="d-flex flex-column">
                    <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
                        <i data-feather="list"></i>
                        <span>Productos de Factura</span>
                        <span class="badge bg-light text-dark" id="pf_badgeCount">0</span>
                    </h5>
                    <div class="small text-white-50 mt-1">
                        <span class="me-2">Factura: <span class="fw-semibold text-white"
                                id="pf_lblFactura">—</span></span>
                        <span class="me-2">Proveedor: <span class="fw-semibold text-white"
                                id="pf_lblProveedor">—</span></span>
                        <span class="me-2">XDock: <span class="fw-semibold text-white" id="pf_lblXdock">—</span></span>
                        <span class="me-2">Recibido: <span class="fw-semibold text-white"
                                id="pf_lblRecibido">—</span></span>
                        <span class="me-2">Revisión: <span class="fw-semibold text-white"
                                id="pf_lblRevision">—</span></span>
                    </div>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">

                <!-- Barra superior: búsqueda + métricas -->
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <input type="text" id="pf_buscar" class="form-control" style="max-width:320px;"
                        placeholder="Buscar por descripción, UPC o marca..." autocomplete="off">

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="small text-muted">Totales (demo):</span>
                        <span class="badge bg-success" id="pf_totalCajas">Cajas: 0</span>
                        <span class="badge bg-primary" id="pf_totalPiezas">Piezas: 0</span>
                        <span class="badge bg-secondary" id="pf_totalPallets">Pallets RCV: 0</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table  align-middle" id="pf_tablaExportar">
                        <thead class="table-dark">
                            <tr class="text-center">
                                <th style="min-width:300px;">Descripción</th>
                                <th style="width:160px;">UPC</th>
                                <th style="width:160px;">Marca</th>
                                <th style="width:160px;">Expiración</th>
                                <th style="width:120px;">Inner</th>
                                <th style="width:120px;">Case</th>
                                <th style="width:120px;">Pallets RCV</th>
                                <th style="width:120px;">Pallets INV</th>
                                <th style="width:120px;"># Cajas</th>
                                <th style="width:120px;"># Piezas</th>
                            </tr>
                        </thead>
                        <tbody id="pf_tbody"></tbody>
                    </table>
                </div>

                <div id="pf_empty" class="alert alert-light border d-none">
                    No hay productos para mostrar.
                </div>

            </div>

            <div class="modal-footer d-flex justify-content-between">
                <div class="small text-muted" id="pf_meta">
                    Mostrando 0 de 0
                </div>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x-circle" class="me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ===================== SCRIPT DEMO (SIN BD) ===================== -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  const modalEl = document.getElementById("modalProductosFactura");
  if (!modalEl) return;

  // Labels header
  const lblFactura  = document.getElementById("pf_lblFactura");
  const lblProv     = document.getElementById("pf_lblProveedor");
  const lblXdock    = document.getElementById("pf_lblXdock");
  const lblRecibido = document.getElementById("pf_lblRecibido");
  const lblRevision = document.getElementById("pf_lblRevision");
  const badgeCount  = document.getElementById("pf_badgeCount");

  // Totales demo
  const elTotalCajas   = document.getElementById("pf_totalCajas");
  const elTotalPiezas  = document.getElementById("pf_totalPiezas");
  const elTotalPallets = document.getElementById("pf_totalPallets");

  // Table
  const tbody = document.getElementById("pf_tbody");
  const empty = document.getElementById("pf_empty");
  const meta  = document.getElementById("pf_meta");
  const inpBuscar = document.getElementById("pf_buscar");

  // Dataset DEMO por factura (aquí puedes agregar más facturas si quieres)
  const DEMO = {
    "43": [
      { desc: "LOREAL MATTE SIGNATURE LIQUID EYELINER", upc: "", brand: "", exp: "", inner: "", casep: "", prcv: 26, pinv: 0, cajas: 170, piezas: 0 },
      { desc: "LOREAL PURE SUGAR SCRUB-MINI",           upc: "", brand: "", exp: "", inner: "", casep: "", prcv: 0,  pinv: 0, cajas: 75,  piezas: 0 },
      { desc: "DUNION - SHOES",                         upc: "", brand: "", exp: "", inner: "", casep: "", prcv: 0,  pinv: 0, cajas: 221, piezas: 0 },
      { desc: "DISNEY - BASKET",                        upc: "", brand: "", exp: "", inner: "", casep: "", prcv: 0,  pinv: 0, cajas: 0,   piezas: 0 },
      { desc: "SPARTAN T-SHIRTS MEN+WOMEN",             upc: "", brand: "", exp: "", inner: "", casep: "", prcv: 0,  pinv: 0, cajas: 0,   piezas: 169 }
    ]
  };

  let currentRows = [];

  const fmt = (v) => (v === null || v === undefined || v === "") ? "—" : String(v);

  const calcTotales = (rows) => {
    let cajas = 0, piezas = 0, pallets = 0;
    rows.forEach(r => {
      cajas += (parseInt(r.cajas, 10) || 0);
      piezas += (parseInt(r.piezas, 10) || 0);
      pallets += (parseInt(r.prcv, 10) || 0);
    });
    elTotalCajas.textContent = "Cajas: " + cajas;
    elTotalPiezas.textContent = "Piezas: " + piezas;
    elTotalPallets.textContent = "Pallets RCV: " + pallets;
  };

  const render = (rows) => {
    tbody.innerHTML = "";

    if (!rows.length) {
      empty.classList.remove("d-none");
      meta.textContent = "Mostrando 0 de 0";
      badgeCount.textContent = "0";
      calcTotales([]);
      return;
    }

    empty.classList.add("d-none");
    badgeCount.textContent = String(rows.length);
    meta.textContent = "Mostrando " + rows.length + " de " + rows.length;

    rows.forEach(r => {
      const tr = document.createElement("tr");
      tr.className = "text-center";
      tr.innerHTML = `
        <td class="text-start">${fmt(r.desc)}</td>
        <td>${fmt(r.upc)}</td>
        <td>${fmt(r.brand)}</td>
        <td>${fmt(r.exp)}</td>
        <td>${fmt(r.inner)}</td>
        <td>${fmt(r.casep)}</td>
        <td>${fmt(r.prcv)}</td>
        <td>${fmt(r.pinv)}</td>
        <td class="fw-semibold">${fmt(r.cajas)}</td>
        <td class="fw-semibold">${fmt(r.piezas)}</td>
      `;
      tbody.appendChild(tr);
    });

    calcTotales(rows);
  };

  const applySearch = () => {
    const term = (inpBuscar.value || "").trim().toLowerCase();
    if (!term) return render(currentRows);

    const filtered = currentRows.filter(r => {
      return (
        (r.desc || "").toLowerCase().includes(term) ||
        (r.upc || "").toLowerCase().includes(term) ||
        (r.brand || "").toLowerCase().includes(term)
      );
    });

    render(filtered);
  };

  // Cuando el modal se va a abrir, Bootstrap indica "relatedTarget" (el botón que lo abrió)
  modalEl.addEventListener("show.bs.modal", (ev) => {
    const btn = ev.relatedTarget;
    const invoice = btn?.getAttribute("data-invoice") || "";
    const vendor  = btn?.getAttribute("data-vendor") || "";
    const xdock   = btn?.getAttribute("data-xdock") || "";
    const recibido= btn?.getAttribute("data-recibido") || "";
    const revision= btn?.getAttribute("data-revision") || "";

    lblFactura.textContent  = invoice || "—";
    lblProv.textContent     = vendor || "—";
    lblXdock.textContent    = xdock || "—";
    lblRecibido.textContent = recibido || "—";
    lblRevision.textContent = revision || "—";

    // Cargar dataset demo por factura
    currentRows = (DEMO[invoice] || []).slice();

    // Reset search
    inpBuscar.value = "";

    render(currentRows);

    if (window.feather) window.feather.replace();
  });

  // Búsqueda en vivo
  inpBuscar.addEventListener("input", applySearch);

  // Feather initial
  if (window.feather) window.feather.replace();
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  // ====== Refs Modal ======
  const modalEl = document.getElementById("modalOperacionesPartida");
  if (!modalEl) return;

  // Paso 1
  const formEncabezado = document.getElementById("formOperacionesPartida");
  const inpIdFactura   = document.getElementById("operaciones_partida_id");
  const inpXdock       = document.getElementById("operaciones_partida_xdock");
  const inpRevision    = document.getElementById("operaciones_partida_revision");
  const inpFactura     = document.getElementById("operaciones_partida_factura");
  const inpProveedor   = document.getElementById("operaciones_partida_proveedor");
  const inpRecibido    = document.getElementById("operaciones_partida_fechaRecibido");
  const inpNotas       = document.getElementById("operaciones_partida_notas");

  const btnGuardarEnc  = document.getElementById("operaciones_partida_btnGuardarEncabezado");
  const btnGuardarProd = document.getElementById("operaciones_partida_btnGuardarProductos");

  // Paso 2
  const stepEncabezado = document.getElementById("op_partida_stepEncabezado");
  const stepProductos  = document.getElementById("op_partida_stepProductos");
  const badgePaso      = document.getElementById("op_partida_badgePaso");

  const lblFactura     = document.getElementById("op_partida_lblFactura");
  const lblProveedor   = document.getElementById("op_partida_lblProveedor");

  const btnAgregarLinea = document.getElementById("op_partida_btnAgregarLinea");
  const detalleBody     = document.getElementById("op_partida_detalleBody");
  const tplFila         = document.getElementById("op_partida_tplFilaProducto");

  // Preview JSON (se crea dinámicamente si no existe)
  let previewWrap = document.getElementById("op_partida_previewWrap");
  if (!previewWrap) {
    previewWrap = document.createElement("div");
    previewWrap.id = "op_partida_previewWrap";
    previewWrap.className = "d-none mt-3";
    previewWrap.innerHTML = `
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
          <span class="small"><i data-feather="code" class="me-1"></i> Vista previa (JSON) - DEMO</span>
          <button type="button" class="btn btn-light btn-sm" id="op_partida_btnOcultarPreview">
            Ocultar
          </button>
        </div>
        <div class="card-body">
          <pre class="mb-0" style="white-space:pre-wrap;" id="op_partida_previewJson"></pre>
        </div>
      </div>
    `;
    // lo colocamos al final del Step 2
    stepProductos?.appendChild(previewWrap);
  }
  const preJson = document.getElementById("op_partida_previewJson");
  const btnOcultarPreview = document.getElementById("op_partida_btnOcultarPreview");

  // ====== Botón DEMO (se agrega al footer) ======
  const footer = modalEl.querySelector(".modal-footer .d-flex.gap-2");
  let btnCargarDemo = document.getElementById("operaciones_partida_btnCargarDemo");
  if (!btnCargarDemo && footer) {
    btnCargarDemo = document.createElement("button");
    btnCargarDemo.type = "button";
    btnCargarDemo.id = "operaciones_partida_btnCargarDemo";
    btnCargarDemo.className = "btn btn-outline-light";
    btnCargarDemo.innerHTML = `<i data-feather="zap" class="me-1"></i> Cargar demo`;
    footer.prepend(btnCargarDemo);
  }

  // ====== Utilidades ======
  const setPaso = (paso) => {
    // paso: 1 o 2
    if (badgePaso) badgePaso.textContent = paso === 1 ? "Paso 1/2" : "Paso 2/2";

    if (paso === 1) {
      stepEncabezado?.classList.remove("opacity-50");
      stepEncabezado?.classList.remove("pe-none");
      stepProductos?.classList.add("d-none");
      btnGuardarProd?.classList.add("d-none");
      previewWrap?.classList.add("d-none");
    } else {
      stepProductos?.classList.remove("d-none");
      btnGuardarProd?.classList.remove("d-none");
    }
  };

  const resetModal = () => {
    // Encabezado
    if (formEncabezado) formEncabezado.reset();
    if (inpIdFactura) inpIdFactura.value = "";
    // Detalle
    if (detalleBody) detalleBody.innerHTML = "";
    // Labels
    if (lblFactura) lblFactura.textContent = "";
    if (lblProveedor) lblProveedor.textContent = "";
    // Paso 1
    setPaso(1);
    // Feather refresh
    if (window.feather) window.feather.replace();
  };

  const validarEncabezadoMinimo = () => {
    // Validación simple demo
    const errores = [];
    if (!inpXdock?.value.trim()) errores.push("XDock es requerido.");
    if (!inpFactura?.value.trim()) errores.push("Número de factura es requerido.");
    if (!inpProveedor?.value.trim()) errores.push("Proveedor es requerido.");
    if (!inpRecibido?.value) errores.push("Fecha de recibido es requerida.");
    return errores;
  };

  const crearFila = (data = {}) => {
    if (!tplFila || !detalleBody) return;

    const frag = tplFila.content.cloneNode(true);
    const tr = frag.querySelector("tr");

    // Set values
    tr.querySelector(".op_det_descripcion").value = data.descripcion ?? "";
    tr.querySelector(".op_det_upc").value         = data.upc ?? "";
    tr.querySelector(".op_det_marca").value       = data.marca ?? "";
    tr.querySelector(".op_det_expiracion").value  = data.expiracion ?? "";
    tr.querySelector(".op_det_inner").value       = data.inner ?? "";
    tr.querySelector(".op_det_case").value        = data.case ?? "";
    tr.querySelector(".op_det_pallets_rcv").value = (data.pallets_rcv ?? "") + "";
    tr.querySelector(".op_det_pallets_inv").value = (data.pallets_inv ?? "") + "";
    tr.querySelector(".op_det_cajas").value       = (data.cajas ?? "") + "";
    tr.querySelector(".op_det_piezas").value      = (data.piezas ?? "") + "";

    // Delete handler
    const btnDel = tr.querySelector(".op_det_btnEliminar");
    btnDel.addEventListener("click", () => tr.remove());

    detalleBody.appendChild(frag);
    if (window.feather) window.feather.replace();
  };

  const obtenerPayloadDemo = () => {
    const encabezado = {
      id_factura: inpIdFactura?.value || null,
      xdock: inpXdock?.value?.trim() || "",
      revision: inpRevision?.value?.trim() || "",
      invoice_number: inpFactura?.value?.trim() || "",
      vendor_name: inpProveedor?.value?.trim() || "",
      received_date: inpRecibido?.value || "",
      notas: inpNotas?.value?.trim() || ""
    };

    const productos = [];
    detalleBody?.querySelectorAll("tr")?.forEach((tr) => {
      productos.push({
        descripcion: tr.querySelector(".op_det_descripcion")?.value?.trim() || "",
        upc: tr.querySelector(".op_det_upc")?.value?.trim() || "",
        marca: tr.querySelector(".op_det_marca")?.value?.trim() || "",
        expiracion: tr.querySelector(".op_det_expiracion")?.value || "",
        inner_pack: tr.querySelector(".op_det_inner")?.value?.trim() || "",
        case_pack: tr.querySelector(".op_det_case")?.value?.trim() || "",
        pallets_rcv: parseInt(tr.querySelector(".op_det_pallets_rcv")?.value || "0", 10) || 0,
        pallets_inv: parseInt(tr.querySelector(".op_det_pallets_inv")?.value || "0", 10) || 0,
        numero_cajas: parseInt(tr.querySelector(".op_det_cajas")?.value || "0", 10) || 0,
        numero_piezas: parseInt(tr.querySelector(".op_det_piezas")?.value || "0", 10) || 0
      });
    });

    return { encabezado, productos };
  };

  const mostrarPreview = (obj) => {
    if (!previewWrap || !preJson) return;
    preJson.textContent = JSON.stringify(obj, null, 2);
    previewWrap.classList.remove("d-none");
    if (window.feather) window.feather.replace();
  };

  // ====== Eventos ======

  // Reset al abrir (Bootstrap event)
  modalEl.addEventListener("shown.bs.modal", () => {
    // Si quieres que NO se resetee en edición futura, aquí podrás condicionar.
    resetModal();
  });

  // Guardar Encabezado (DEMO: solo desbloquea)
  btnGuardarEnc?.addEventListener("click", () => {
    const errs = validarEncabezadoMinimo();
    if (errs.length) {
      alert("Revisa el encabezado:\n\n- " + errs.join("\n- "));
      return;
    }

    // Simulamos que “se guardó” y el backend regresó un ID
    if (!inpIdFactura.value) {
      inpIdFactura.value = "DEMO-" + String(Date.now()).slice(-6);
    }

    // Mostrar labels
    if (lblFactura) lblFactura.textContent = inpFactura.value.trim();
    if (lblProveedor) lblProveedor.textContent = inpProveedor.value.trim();

    // Paso 2 visible
    setPaso(2);

    // Opción: “bloquear” encabezado para evitar cambios accidentales
    // (puedes quitar esto si quieres editable)
    stepEncabezado?.classList.add("opacity-50");
    stepEncabezado?.classList.add("pe-none");

    // Si no hay filas, crea 1 para que se vea
    if (detalleBody && detalleBody.children.length === 0) {
      crearFila();
    }

    if (badgePaso) badgePaso.textContent = "Paso 2/2";
  });

  // Agregar línea
  btnAgregarLinea?.addEventListener("click", () => crearFila());

  // Guardar productos (DEMO: solo preview JSON)
  btnGuardarProd?.addEventListener("click", () => {
    const payload = obtenerPayloadDemo();

    // Validación demo rápida: al menos 1 producto con descripción
    const tieneAlgo = payload.productos.some(p => (p.descripcion || "").length > 0);
    if (!tieneAlgo) {
      alert("Agrega al menos un producto con descripción.");
      return;
    }

    mostrarPreview(payload);
  });

  // Ocultar preview
  btnOcultarPreview?.addEventListener("click", () => {
    previewWrap?.classList.add("d-none");
  });

  // Cargar DEMO (Factura 43 con productos)
  btnCargarDemo?.addEventListener("click", () => {
    // Paso 1 values
    inpXdock.value    = "San Diego";
    inpRevision.value = "20";
    inpFactura.value  = "43";
    inpProveedor.value = "PLATINUM";
    inpRecibido.value = "2025-04-22";
    inpNotas.value    = "Demo de captura: factura con múltiples renglones";

    // Simulamos guardar encabezado para abrir Paso 2
    btnGuardarEnc.click();

    // Limpia y carga filas demo (según tu ejemplo)
    if (detalleBody) detalleBody.innerHTML = "";

    const demoRows = [
      { descripcion: "LOREAL MATTE SIGNATURE LIQUID EYELINER", marca: "", expiracion: "", pallets_rcv: 26, pallets_inv: 0, cajas: 170, piezas: 0 },
      { descripcion: "LOREAL PURE SUGAR SCRUB-MINI",           marca: "", expiracion: "", pallets_rcv: 0,  pallets_inv: 0, cajas: 75,  piezas: 0 },
      { descripcion: "DUNION - SHOES",                         marca: "", expiracion: "", pallets_rcv: 0,  pallets_inv: 0, cajas: 221, piezas: 0 },
      { descripcion: "DISNEY - BASKET",                        marca: "", expiracion: "", pallets_rcv: 0,  pallets_inv: 0, cajas: 0,   piezas: 0 },
      { descripcion: "SPARTAN T-SHIRTS MEN+WOMEN",             marca: "", expiracion: "", pallets_rcv: 0,  pallets_inv: 0, cajas: 0,   piezas: 169 }
    ];

    demoRows.forEach(r => crearFila(r));
  });

  // Feather initial
  if (window.feather) window.feather.replace();
});
</script>
