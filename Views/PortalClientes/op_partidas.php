<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PacificNort Suite | Portal Cliente | Operaciones por Partida</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>Assets/Css/PortalClientes/PortalClientes.css" rel="stylesheet">

    <style>
        /* =========================
       BASE
       ========================= */
        .table-responsive {
            overflow-x: auto;
            max-width: 100%;
            padding-bottom: .5rem;
        }

        td,
        th {
            text-transform: uppercase;
        }


        /* =========================
       EMPTY STATE
       ========================= */

        .pn-empty {
            border: 1px dashed #ced4da;
            border-radius: 1rem;
            padding: 2rem 1rem;
            text-align: center;
            color: #6c757d;
            background: #fcfcfd;
        }

        /* =========================
       META INFO
       ========================= */

        .pn-meta-label {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #6c757d;
            margin-bottom: .15rem;
        }

        .pn-meta-value {
            font-weight: 600;
            color: #212529;
            word-break: break-word;
        }

        /* =========================
       THUMBS (IMÁGENES)
       ========================= */

        .pn-thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }

        .pn-thumb {
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            overflow: hidden;
            background: #fff;
            cursor: pointer;
            transition: .18s ease;
            height: 100%;
        }

        .pn-thumb:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08);
        }

        .pn-thumb img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            display: block;
            background: #f8f9fa;
        }

        .pn-thumb .pn-thumb-caption {
            padding: .55rem .65rem;
            font-size: .75rem;
            color: #6c757d;
            text-align: center;
            border-top: 1px solid #f1f3f5;
        }

        /* =========================
       BADGES ESTATUS
       ========================= */

        .pn-badge-status {
            font-size: .74rem;
            padding: .45rem .65rem;
            border-radius: 999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .pn-badge-status.is-programado {
            background: #fff3cd;
            color: #856404;
        }

        .pn-badge-status.is-camino {
            background: #cff4fc;
            color: #055160;
        }

        .pn-badge-status.is-entregado {
            background: #d1e7dd;
            color: #0f5132;
        }

        .pn-badge-status.is-destino {
            background: #e2e3ff;
            color: #3d3f8f;
        }

        .pn-badge-status.is-cancelado {
            background: #f8d7da;
            color: #842029;
        }

        .pn-badge-status.is-default {
            background: #e9ecef;
            color: #495057;
        }

        /* =========================
       MODALES
       ========================= */

        .modal-xxl-wide {
            max-width: min(1680px, calc(100vw - 2rem));
        }

        .modal-xl-soft {
            max-width: min(1360px, calc(100vw - 2rem));
        }

        /* =========================
       TABLA PRINCIPAL
       ========================= */

        #tblOpsPartida {
            table-layout: auto;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1800px;
        }

        #tblOpsPartida th,
        #tblOpsPartida td {
            padding: .9rem 1rem;
            vertical-align: middle;
        }

        #tblOpsPartida thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 600;
            color: #6c757d;
            background: #f8f9fa;
            white-space: nowrap;
        }

        #tblOpsPartida tbody tr:hover {
            background-color: #f7faff;
        }

        /* Columnas controladas */
        #tblOpsPartida td:nth-child(1),
        #tblOpsPartida td:nth-child(2),
        #tblOpsPartida td:nth-child(4),
        #tblOpsPartida td:nth-child(6),
        #tblOpsPartida td:nth-child(7),
        #tblOpsPartida td:nth-child(8),
        #tblOpsPartida td:nth-child(9),
        #tblOpsPartida td:nth-child(10),
        #tblOpsPartida td:nth-child(12) {
            white-space: nowrap;
        }

        #tblOpsPartida td:nth-child(3),
        #tblOpsPartida td:nth-child(5),
        #tblOpsPartida td:nth-child(11) {
            white-space: normal;
            min-width: 180px;
            max-width: 260px;
        }

        #tblOpsPartida td:nth-child(13) {
            white-space: nowrap;
            min-width: 280px;
        }

        /* =========================
       UI EXTRAS
       ========================= */

        .btn-icon-text {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .factura-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .6rem;
            border-radius: 999px;
            background: #f1f3f5;
            color: #495057;
            font-size: .75rem;
            font-weight: 600;
            margin: .12rem;
        }

        .sticky-summary {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #fff;
        }
    </style>
</head>


<body>

    <!-- NAV SUPERIOR -->
    <?php include 'Views/PortalClientes/header.php';
    ?>

    <main class="pn-content" id="pnContent">

        <!-- TOPBAR -->
        <div class="pn-topbar" id="pnTopbar">
            <div class="container-fluid py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-0" id="pageTitle">Operaciones por Partida</h4>
                        <div class="search-hint" id="pageHint">
                            Consulta facturas, productos, envíos e imágenes vinculadas a tus operaciones por partida.
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary" type="button" id="btnRefrescarPartida">
                            <i data-feather="refresh-cw" class="me-1"></i> Refrescar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid py-4" id="pnContainer">

            <!-- KPI -->
            <div class="row g-3 mb-3" id="kpiRow">

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="kpi-card p-3 pn-kpi-pro kpi-mar-agua" id="kpiCardFacturas" role="button" tabindex="0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="min-w-0">
                                <div class="pn-muted small text-truncate">Facturas</div>
                                <div class="h4 mb-0" id="kpiPartidaFacturas">
                                    <?php echo (int)($data['kpis']['facturas'] ?? 0); ?>
                                </div>
                                <div class="small pn-muted text-truncate">Facturas visibles del cliente</div>
                            </div>
                            <div class="kpi-icon pn-kpi-ic">
                                <i data-feather="file-text"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="kpi-card p-3 pn-kpi-pro kpi-ter-camino" id="kpiCardFerros" role="button" tabindex="0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="min-w-0">
                                <div class="pn-muted small text-truncate">Ferros / cajas</div>
                                <div class="h4 mb-0" id="kpiPartidaFerros">
                                    <?php echo (int)($data['kpis']['ferros'] ?? 0); ?>
                                </div>
                                <div class="small pn-muted text-truncate">Envíos vinculados</div>
                            </div>
                            <div class="kpi-icon pn-kpi-ic">
                                <i data-feather="truck"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="kpi-card p-3 pn-kpi-pro kpi-mar-puerto" id="kpiCardProductos" role="button" tabindex="0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="min-w-0">
                                <div class="pn-muted small text-truncate">Productos</div>
                                <div class="h4 mb-0" id="kpiPartidaProductos">
                                    <?php echo (int)($data['kpis']['productos'] ?? 0); ?>
                                </div>
                                <div class="small pn-muted text-truncate">Productos registrados</div>
                            </div>
                            <div class="kpi-icon pn-kpi-ic">
                                <i data-feather="box"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="kpi-card p-3 pn-kpi-pro kpi-entregadas" id="kpiCardCajas" role="button" tabindex="0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="min-w-0">
                                <div class="pn-muted small text-truncate">Cajas enviadas</div>
                                <div class="h4 mb-0" id="kpiPartidaCajas">
                                    <?php echo (int)($data['kpis']['cajas'] ?? 0); ?>
                                </div>
                                <div class="small pn-muted text-truncate">Total del detalle de envíos</div>
                            </div>
                            <div class="kpi-icon pn-kpi-ic">
                                <i data-feather="archive"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- FILTROS -->
            <div class="card shadow-sm border-0 rounded-4 mb-3" id="cardFiltrosPartida">
                <div class="card-body">
                    <div class="row g-3 align-items-end">

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="partidaSearch">Buscar factura</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="search"></i></span>
                                <input class="form-control" id="partidaSearch" placeholder="Factura, proveedor, ferro/caja..." />
                            </div>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="partidaEstatus">Estatus</label>
                            <select class="form-select" id="partidaEstatus" name="estatus_envio">
                                <option value="">Todos</option>
                                <option value="PROGRAMADO">Programado</option>
                                <option value="EN CAMINO">En camino</option>
                                <option value="ENTREGADO">Entregado</option>
                                <option value="DISPONIBLE EN DESTINO">Disponible en destino</option>
                                <option value="CANCELADO">Cancelado</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="partidaDestino">Destino</label>
                            <select class="form-select" id="partidaDestino" name="destino_id">
                                <option value="">Todos</option>
                                <?php if (!empty($data['ciudades'])): ?>
                                    <?php foreach ($data['ciudades'] as $ciudad): ?>
                                        <option value="<?php echo (int)$ciudad['id_ciudad']; ?>">
                                            <?php echo htmlspecialchars($ciudad['nombre_ciudad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="partidaFechaIni">Fecha inicial</label>
                            <input type="date" class="form-control" id="partidaFechaIni" />
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="partidaFechaFin">Fecha final</label>
                            <input type="date" class="form-control" id="partidaFechaFin" />
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="partidaTransportista">Transportista</label>
                            <select class="form-select" id="partidaTransportista" name="transportista_id">
                                <option value="">Todos</option>
                                <?php if (!empty($data['transportistas'])): ?>
                                    <?php foreach ($data['transportistas'] as $transportista): ?>
                                        <option value="<?php echo (int)$transportista['id_transportista']; ?>">
                                            <?php echo htmlspecialchars($transportista['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="d-flex align-items-end justify-content-end ">
                            <div class="col-12 col-md-1 d-grid ">
                                <button class="btn btn-dark" type="button" id="btnPartidaFiltrar">
                                    <i data-feather="filter" class="me-1"></i> Filtrar
                                </button>
                            </div>

                            <div class="col-12 col-md-1 d-grid ">
                                <button class="btn btn-outline-secondary" type="button" id="btnPartidaLimpiar">
                                    <i data-feather="x-circle" class="me-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex flex-wrap align-items-center gap-2" id="partidaFiltrosActivosBar">
                        <!-- chips dinámicos -->
                    </div>
                </div>
            </div>

            <!-- TABLA -->
            <div class="card shadow-sm border-0 rounded-4" id="cardTablaPartida">
                <div class="card-header bg-white border-0 pt-4 px-4 sticky-summary">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">Listado de operaciones por partida</div>
                            <div class="small pn-muted">
                                Consulta facturas, productos, asignaciones de envío e imágenes relacionadas.
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="partidaPageSize" style="width:auto;">
                                <option value="15">15 / pág</option>
                                <option value="30">30 / pág</option>
                                <option value="50">50 / pág</option>
                                <option value="100">100 / pág</option>
                                <option value="10000000">Todos</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-spacious align-middle mb-0" id="tblOpsPartida">
                            <thead class="text-center">
                                <tr>
                                    <th>No. Factura</th>
                                    <th>Pallets Factura</th>
                                    <th style="width:400px;">Proveedor</th>
                                    <th>Fecha recibido</th>
                                    <th>Productos</th>
                                    <th>Caja / Ferro</th>
                                    <!-- <th>Transportista</th> -->
                                    <th>Fecha envío</th>
                                    <th>Destino</th>
                                    <th>Estatus del ferro</th>
                                    <th>Productos enviados</th>
                                    <th>Cajas totales</th>
                                    <th>Cajas Enviadas</th>
                                    <th>Cajas Restantes</th>
                                    <th>Notas / Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbOpsPartida" class="text-center text-muted">
                                <tr>
                                    <td colspan="12">
                                        <div class="pn-empty my-2">
                                            <div class="fw-semibold mb-1">Sin información cargada</div>
                                            <div>Conecta aquí tu endpoint para listar las operaciones por partida del cliente.</div>
                                        </div>
                                    </td>
                                </tr>

                                <!--
                EJEMPLO DE FILA ESPERADA

                <tr>
                  <td>FAC-001254</td>
                  <td>12</td>
                  <td>ACME SUPPLIER</td>
                  <td>2026-03-10</td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" type="button">
                      <i data-feather="box" class="me-1"></i> Ver productos
                    </button>
                  </td>
                  <td>FERRO-001</td>
                  <td>TRANSPORTES DEL NORTE</td>
                  <td>2026-03-14</td>
                  <td>TIJUANA</td>
                  <td><span class="pn-badge-status is-camino">En camino</span></td>
                  <td>5</td>
                  <td>80</td>
                  <td>
                    <div class="d-flex flex-wrap gap-2">
                      <button class="btn btn-sm btn-outline-primary" type="button">Factura</button>
                      <button class="btn btn-sm btn-outline-secondary" type="button">Envío</button>
                      <button class="btn btn-sm btn-outline-dark" type="button">Mercancía</button>
                    </div>
                  </td>
                </tr>
                -->

                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3" id="partidaPagingWrap">
                        <div class="small pn-muted" id="partidaPagingLbl">Mostrando 0–0 de 0</div>

                        <nav aria-label="Paginación Operaciones por Partida">
                            <ul class="pagination pagination-sm mb-0" id="partidaPaging">
                                <!-- render dinámico -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- =========================================================
       MODAL FACTURA
       ========================================================= -->
    <div class="modal fade" id="modalPartidaFactura" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-xxl-wide">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">
                            <i data-feather="file-text" class="me-1"></i>
                            Detalle de factura
                        </h5>
                        <div class="small text-muted">
                            Encabezado de la factura, productos y fotos de mercancía.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">

                    <!-- ENCABEZADO FACTURA -->
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <div class="pn-meta-label">Número de factura</div>
                                    <div class="pn-meta-value" id="mf_numeroFactura">--</div>
                                </div>

                                <div class="col-12 col-md-3">
                                    <div class="pn-meta-label">Proveedor</div>
                                    <div class="pn-meta-value" id="mf_proveedor">--</div>
                                </div>

                                <div class="col-12 col-md-2">
                                    <div class="pn-meta-label">Pallets factura</div>
                                    <div class="pn-meta-value" id="mf_palletsInv">0</div>
                                </div>

                                <div class="col-12 col-md-2">
                                    <div class="pn-meta-label">Fecha recibido</div>
                                    <div class="pn-meta-value" id="mf_fechaRecibido">--</div>
                                </div>

                                <div class="col-12 col-md-2">
                                    <div class="pn-meta-label">Estatus revisión</div>
                                    <div class="pn-meta-value" id="mf_revisionEstatus">--</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PRODUCTOS DE FACTURA -->
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-header bg-white border-0 pt-3 px-3">
                            <div class="fw-semibold">Productos de la factura</div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="tblModalFacturaProductos">
                                    <thead>
                                        <tr>
                                            <th>Descripción</th>
                                            <th>Item</th>
                                            <th>UPC</th>
                                            <th>Marca</th>
                                            <th>Expiración</th>
                                            <th>Inner</th>
                                            <th>Case</th>
                                            <th>Pallets RCV</th>
                                            <th>Cajas</th>
                                            <th>Piezas</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mf_tbodyProductos">
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                Sin productos para mostrar.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- FOTOS MERCANCÍA -->
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-3 px-3">
                            <div class="fw-semibold">Fotos de mercancía</div>
                        </div>
                        <div class="card-body">
                            <div class="pn-thumb-grid" id="mf_gridFotosMercancia">

                                <!-- ejemplo -->
                                <!--
                <div class="pn-thumb" data-src="RUTA_IMAGEN">
                  <img src="RUTA_IMAGEN" alt="Foto mercancía">
                  <div class="pn-thumb-caption">Producto 1</div>
                </div>
                -->

                                <div class="pn-empty">
                                    Sin fotos para mostrar.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
       MODAL ENVÍO
       ========================================================= -->
    <div class="modal fade" id="modalPartidaEnvio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-xl-soft">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">
                            <i data-feather="truck" class="me-1"></i>
                            Detalle de envío
                        </h5>
                        <div class="small text-muted">
                            Información del ferro/caja, transportista, facturas, productos, cajas e imágenes.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">

                    <!-- CABECERA ENVÍO -->
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-12 col-md-4">
                                    <div class="pn-meta-label">Caja / Ferro</div>
                                    <div class="pn-meta-value" id="me_numeroFerro">--</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="pn-meta-label">Transportista</div>
                                    <div class="pn-meta-value" id="me_transportista">--</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="pn-meta-label">Fecha envío</div>
                                    <div class="pn-meta-value" id="me_fechaEnvio">--</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="pn-meta-label">Destino</div>
                                    <div class="pn-meta-value" id="me_destino">--</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="pn-meta-label">Estatus</div>
                                    <div class="pn-meta-value" id="me_estatus">--</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="pn-meta-label">Candado / sello</div>
                                    <div class="pn-meta-value" id="me_candado">--</div>
                                </div>

                                <div class="col-12">
                                    <div class="pn-meta-label">Notas</div>
                                    <div class="pn-meta-value" id="me_notas">Sin notas</div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- FACTURAS DEL ENVÍO -->
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-header bg-white border-0 pt-3 px-3">
                            <div class="fw-semibold">Facturas incluidas en el envío</div>
                        </div>
                        <div class="card-body">
                            <div id="me_facturasWrap">
                                <span class="factura-chip">Sin facturas</span>
                            </div>
                        </div>
                    </div>

                    <!-- DETALLE ENVÍO -->
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-header bg-white border-0 pt-3 px-3">
                            <div class="fw-semibold">Productos, cajas y notas</div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="tblModalEnvioDetalle">
                                    <thead>
                                        <tr>
                                            <th>Factura</th>
                                            <th>Producto</th>
                                            <th>Cajas enviadas</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody id="me_tbodyDetalle">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                Sin detalle para mostrar.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- IMÁGENES ENVÍO -->
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-3 px-3">
                            <div class="fw-semibold">Imágenes del envío</div>
                        </div>
                        <div class="card-body">
                            <div class="pn-thumb-grid" id="me_gridImagenesEnvio">
                                <div class="pn-empty">
                                    Sin imágenes para mostrar.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
       MODAL SOLO IMAGEN
       ========================================================= -->
    <div class="modal fade" id="modalVisorImagen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i data-feather="image" class="me-1"></i>
                        Vista previa de imagen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img id="visorImagenFull" src="" alt="Vista previa" class="img-fluid rounded-3 shadow-sm">
                </div>
            </div>
        </div>
    </div>


    <script>
        feather.replace();

        // =========================================================
        // VISOR BÁSICO DE IMÁGENES
        // =========================================================
        (function() {
            const modalVisorEl = document.getElementById('modalVisorImagen');
            const visorImagenFull = document.getElementById('visorImagenFull');
            const visorModal = modalVisorEl ? new bootstrap.Modal(modalVisorEl) : null;

            document.addEventListener('click', function(e) {
                const thumb = e.target.closest('.pn-thumb');
                if (!thumb || !visorModal || !visorImagenFull) return;

                const img = thumb.querySelector('img');
                const src = thumb.getAttribute('data-src') || (img ? img.getAttribute('src') : '');

                if (!src) return;

                visorImagenFull.setAttribute('src', src);
                visorModal.show();
            });
        })();
    </script>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>Assets/Js/PortalClientes/OperacionesPartida.js"></script>

</body>

</html>