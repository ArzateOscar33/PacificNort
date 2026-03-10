<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PacificNort Suite | Portal Cliente</title>

  <!-- Bootstrap 5.3.x -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>Assets/Css/PortalClientes/PortalClientes.css" rel="stylesheet">
  <style>
    .badge-asignacion {
      padding: .45rem .6rem;
      font-size: .75rem;
      border-radius: .35rem;
      font-weight: 600;
      line-height: 1.2;
    }

    .badge-asignacion.is-linked {
      outline: 2px solid rgba(255, 255, 255, .95);
      box-shadow: 0 0 0 2px rgba(13, 110, 253, .55);
      transform: scaleY(1.2) scaleX(1.2) translateY(-1px);
      transition: 0.2s ease-in;
    }

    .table-responsive {
      overflow-x: auto;
      padding-bottom: .5rem;
    }

    /* Tabla amplia pero controlada */
    #tblOpsMar {
      table-layout: auto;
      border-collapse: separate;
      border-spacing: 0;
      min-width: 1100px;
      /* 👈 ajusta: 1300-1700 */
    }

    /* Espaciado general */
    #tblOpsMar th,
    #tblOpsMar td {
      padding: .9rem 1rem;
      vertical-align: middle;
    }

    /* Header estilo */
    #tblOpsMar thead th {
      font-size: .78rem;
      text-transform: uppercase;
      letter-spacing: .5px;
      font-weight: 600;
      color: #6c757d;
      background: #f8f9fa;
      white-space: nowrap;
      /* 👈 header sí puede ser nowrap */
    }

    /* ✅ SOLO estas columnas en nowrap (cortas) */
    #tblOpsMar td:nth-child(1),
    /* Operación */
    #tblOpsMar td:nth-child(2),
    /* Contenedor */
    #tblOpsMar td:nth-child(3),
    /* BL */
    #tblOpsMar td:nth-child(4),
    /* ETD */
    #tblOpsMar td:nth-child(5),
    /* ETA */
    #tblOpsMar td:nth-child(6),
    /* Estatus */
    #tblOpsMar td:nth-child(7),
    /* Peso */
    #tblOpsMar td:nth-child(8),
    /* Medida */
    #tblOpsMar td:nth-child(12),
    /* Cita Puerto */
    #tblOpsMar td:nth-child(18)

    /* Acciones */
      {
      white-space: nowrap;
    }

    /* ✅ Columnas largas: permitir wrap (para que NO se infle la tabla) */
    #tblOpsMar td:nth-child(9),
    /* Mercancía */
    #tblOpsMar td:nth-child(10),
    /* Transportista */
    #tblOpsMar td:nth-child(11)

    /* Broker */
      {
      white-space: normal;
      max-width: 220px;
      /* 👈 controla el ancho */
    }

    /* Hover suave */
    #tblOpsMar tbody tr:hover {
      background-color: #f7faff;
    }

    .table-responsive {
      overflow-x: auto;
      max-width: 100%;
      padding-bottom: .5rem;
    }

    td,
    th {
      text-transform: uppercase;
    }
  </style>

</head>

<body>

  <!-- NAV SUPERIOR (REEMPLAZA SIDEBAR) -->
  <header class="pn-topnav" id="pnTopnav">
    <div class="container-fluid py-2">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

        <!-- Brand -->
        <a class="pn-brand" href="#" id="pnBrand">
          <span class="kpi-icon"><i data-feather="anchor"></i></span>
          <span>
            <span class="fw-bold d-block" style="line-height:1.05;">PacificNort Suite</span>
            <span class="small pn-muted-inv">Portal Cliente</span>
          </span>
        </a>

        <!-- Acciones / Usuario -->
        <div class="d-flex flex-wrap align-items-center gap-2" id="pnUserActions">

          <div class="text-end me-1" id="pnUserInfo">
            <div class="fw-semibold" style="line-height:1.1;" id="lblClienteTop">Cliente:<?php echo $data['nombre_cliente']; ?></div>
            <div class="small pn-muted-inv" id="lblUsuarioTop">Usuario:<?php echo $data['nombre_usuario']; ?></div>
          </div>

          <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>admin/salir">
            <i data-feather="log-out" class="me-1"></i> Cerrar sesión
          </a>

        </div>

      </div>
    </div>
  </header>

  <!-- CONTENIDO -->
  <main class="pn-content" id="pnContent">

    <!-- Topbar blanca (título + acciones) -->
    <div class="pn-topbar" id="pnTopbar">
      <div class="container-fluid py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div>
            <h4 class="mb-0" id="pageTitle">Operaciones</h4>
            <div class="search-hint" id="pageHint">
              Consulta y filtra tus operaciones.
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary" type="button" id="btnRefrescarTodo">
              <i data-feather="refresh-cw" class="me-1"></i> Refrescar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid py-4" id="pnContainer">


      <div class="row g-3 mb-3" id="kpiRow">

        <!-- En agua -->
        <div class="col-12 col-md-6 col-xl-2">
          <div class="kpi-card p-3 pn-kpi-pro kpi-mar-agua" id="kpiCardMarAgua" role="button" tabindex="0">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <div class="pn-muted small text-truncate">En agua</div>
                <div class="h4 mb-0" id="kpiMarEnAgua">0</div>
                <div class="small pn-muted text-truncate" id="kpiMarEnAguaSub">Operaciones en agua</div>
              </div>
              <div class="kpi-icon pn-kpi-ic">
                <i data-feather="droplet"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- En camino a destino -->
        <div class="col-12 col-md-6 col-xl-2">
          <div class="kpi-card p-3 pn-kpi-pro kpi-ter-camino" id="kpiCardTerCamino" role="button" tabindex="0">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <div class="pn-muted small text-truncate">En camino a destino</div>
                <div class="h4 mb-0" id="kpiTerEnCamino">0</div>
                <div class="small pn-muted text-truncate" id="kpiTerEnCaminoSub">Operaciones terrestres en tránsito</div>
              </div>
              <div class="kpi-icon pn-kpi-ic">
                <i data-feather="truck"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- En puerto -->
        <div class="col-12 col-md-6 col-xl-2">
          <div class="kpi-card p-3 pn-kpi-pro kpi-mar-puerto" id="kpiCardMarPuerto" role="button" tabindex="0">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <div class="pn-muted small text-truncate">En puerto</div>
                <div class="h4 mb-0" id="kpiMarEnPuerto">0</div>
                <div class="small pn-muted text-truncate" id="kpiMarEnPuertoSub">Operaciones en puerto</div>
              </div>
              <div class="kpi-icon pn-kpi-ic">
                <i data-feather="anchor"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Bodegas -->
        <div class="col-12 col-md-6 col-xl-2">
          <div class="kpi-card p-3 pn-kpi-pro kpi-bodegas" id="kpiCardBodegas" role="button" tabindex="0">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <div class="pn-muted small text-truncate">Bodegas</div>
                <div class="h4 mb-0" id="kpiBodegas">0</div>
                <div class="small pn-muted text-truncate" id="kpiBodegasSub">Bodega MX + Bodega USA</div>
              </div>
              <div class="kpi-icon pn-kpi-ic">
                <i data-feather="package"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Yardas -->
        <div class="col-12 col-md-6 col-xl-2">
          <div class="kpi-card p-3 pn-kpi-pro kpi-yardas" id="kpiCardYardas" role="button" tabindex="0">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <div class="pn-muted small text-truncate">Yardas</div>
                <div class="h4 mb-0" id="kpiYardas">0</div>
                <div class="small pn-muted text-truncate" id="kpiYardasSub">Yarda MX + Yarda USA</div>
              </div>
              <div class="kpi-icon pn-kpi-ic">
                <i data-feather="map-pin"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Entregadas -->
        <div class="col-12 col-md-6 col-xl-2">
          <div class="kpi-card p-3 pn-kpi-pro kpi-entregadas" id="kpiCardEntregadas" role="button" tabindex="0">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <div class="pn-muted small text-truncate">Entregadas</div>
                <div class="h4 mb-0" id="kpiEntregadas">0</div>
                <div class="small pn-muted text-truncate" id="kpiEntregadasSub">Operaciones entregadas</div>
              </div>
              <div class="kpi-icon pn-kpi-ic">
                <i data-feather="check-circle"></i>
              </div>
            </div>
          </div>
        </div>

      </div>



      <!-- Filtros -->
      <div class="card shadow-sm border-0 rounded-4 mb-3" id="cardFiltros">
        <div class="card-body">
          <div class="row g-3 align-items-end" id="rowFiltros">
            <div class="col-12 col-md-3">
              <label class="form-label" for="marSearch">Buscar</label>
              <div class="input-group">
                <span class="input-group-text"><i data-feather="search"></i></span>
                <input class="form-control" id="marSearch" placeholder="Operación, BL, contenedor..." />
              </div>
            </div>


            <div class="col-12 col-md-2">
              <label class="form-label" for="marEstatus">Estatus</label>
              <select class="form-select" id="marEstatus" name="estatus">
                <option value="0">Todos</option>

                <?php if (!empty($data['estatus_op'])): ?>
                  <?php foreach ($data['estatus_op'] as $estatus): ?>
                    <option value="<?php echo (int)$estatus['id_estatus']; ?>">
                      <?php echo htmlspecialchars($estatus['nombre']); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>

              </select>
            </div>



            <div class="col-12 col-md-2">
              <label class="form-label" for="marEtaIni">Rango (ETA)</label>
              <input type="date" class="form-control" id="marEtaIni" />
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label" for="marEtaFin">&nbsp;</label>
              <input type="date" class="form-control" id="marEtaFin" />
            </div>

            <div class="col-12 col-md-1 d-grid">
              <button class="btn btn-dark" type="button" id="btnMarFiltrar">
                <i data-feather="filter" class="me-1"></i> Filtrar
              </button>

            </div>
            <div class="col-12 col-md-1">

              <button class="btn  btn-outline-secondary ms-auto" type="button" id="btnMarLimpiar">
                <i data-feather="x-circle" class="me-1"></i> Limpiar
              </button>
            </div>
          </div>

          <hr class="my-3">

          <div class="d-flex flex-wrap align-items-center gap-2" id="filtrosActivosBar">


          </div>
        </div>
      </div>

      <!-- Tabla Marítimas/LBMF (misma tabla operaciones) -->
      <div class="card shadow-sm border-0 rounded-4" id="cardTablaMar">
        <div class="card-header bg-white border-0 pt-4 px-4">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
              <div class="fw-semibold" id="lblListadoMar">Listado de operaciones</div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <select class="form-select form-select-sm" id="marPageSize" style="width:auto;">
                <option value="15">15 / pág</option>
                <option value="30">30 / pág</option>
                <option value="50">50 / pág</option>

                <option value="100">100 / pág</option>
                <option value="200">200 / pág</option>
                <option value="1000">1000 / pág</option>
                <option value="10000000">Todos</option>
              </select>

              <div class="btn-group" role="group" aria-label="Exportaciones">
                <button class="btn btn-sm btn-outline-success" id="btnExcelOpMar">
                  <i data-feather="file-text" class="me-1"></i> Excel
                </button>
                <!--  <button class="btn btn-sm btn-outline-warning" id="btnPdfOpMar">
                  <i data-feather="file" class="me-1"></i> PDF
                </button> -->
              </div>
            </div>
          </div>
        </div>

        <div class="card-body px-4 pb-4">
          <table class="table table-spacious align-middle mb-0" id="tblOpsMar">
            <thead>
              <tr>
                <th>Operación</th>
                <th>Contenedor Martimo</th>
                <th>BL</th>
                <th>ETD</th>
                <th>ETA</th>
                <th>Estatus</th>
                <th>Peso</th>
                <th>Medida</th>
                <th>Mercancia</th>
                <th>Cita Puerto</th>
                <th>Caja/Ferro</th>
                <th>Destino</th>
                <th>Fecha salida</th>
                <th>Ubicacion Actual</th>
                <th> Transportista Caja/Ferro</th>


                <th class="text-end">Acciones</th>
              </tr>
            </thead>

            <!-- ✅ tbody con ID para render dinámico -->
            <tbody id="tbOpsMar">
              <!-- Render JS -->
            </tbody>
          </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3" id="marPagingWrap">
          <div class="small pn-muted" id="marPagingLbl">Mostrando 0–0 de 0</div>

          <nav aria-label="Paginación Marítimas/LBMF" id="marPagingNav">
            <ul class="pagination pagination-sm mb-0" id="marPaging">
              <!-- Render JS -->
            </ul>
          </nav>
        </div>
      </div>
    </div>


    <!-- Filtros FO -->
    <div class="card shadow-sm border-0 rounded-4 mb-3 d-none" id="cardFiltrosFO">
      <div class="card-body">
        <div class="row g-3 align-items-end" id="rowFiltrosFO">

          <!-- Buscar -->
          <div class="col-12 col-md-4">
            <label class="form-label" for="foSearch">Buscar</label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="search"></i></span>
              <input class="form-control" id="foSearch"
                placeholder="Operación FO, Ferro/Caja, Origen, Destino..." />
            </div>
          </div>

          <!-- Estatus -->
          <div class="col-12 col-md-2">
            <label class="form-label" for="foEstatus">Estatus</label>
            <select class="form-select" id="foEstatus" name="fo_estatus">
              <option value="0">Todos</option>

              <?php if (!empty($data['estatus_op'])): ?>
                <?php foreach ($data['estatus_op'] as $estatus): ?>
                  <option value="<?php echo (int)$estatus['id_estatus']; ?>">
                    <?php echo htmlspecialchars($estatus['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>

            </select>
          </div>

          <!-- Rango fecha (FO) -->
          <div class="col-12 col-md-2">
            <label class="form-label" for="foFechaIni">Rango (Fecha)</label>
            <input type="date" class="form-control" id="foFechaIni" />
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label" for="foFechaFin">&nbsp;</label>
            <input type="date" class="form-control" id="foFechaFin" />
          </div>

          <!-- Botones -->
          <div class="col-12 col-md-1 d-grid">
            <button class="btn btn-dark" type="button" id="btnFOFiltrar">
              <i data-feather="filter" class="me-1"></i> Filtrar
            </button>
          </div>

          <div class="col-12 col-md-1 d-grid">
            <button class="btn btn-outline-secondary" type="button" id="btnFOLimpiar">
              <i data-feather="x-circle" class="me-1"></i> Limpiar
            </button>
          </div>

        </div>

        <hr class="my-3">

        <!-- Chips / filtros activos FO -->
        <div class="d-flex flex-wrap align-items-center gap-2" id="foFiltrosActivosBar">

        </div>

      </div>
    </div>

    <!-- Tabla FO -->
    <div class="card shadow-sm border-0 rounded-4 mt-3 d-none" id="cardTablaFO">
      <div class="card-header bg-white border-0 pt-4 px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <div class="fw-semibold" id="lblListadoFO">Operaciones FO (Ferroviarias)</div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" id="foPageSize" style="width:auto;">
              <option value="15">15 / pág</option>
              <option value="30">30 / pág</option>
              <option value="50">50 / pág</option>

              <option value="100">100 / pág</option>
              <option value="200">200 / pág</option>
              <option value="1000">1000 / pág</option>
              <option value="10000000">Todos</option>
            </select>
            <div class="btn-group" role="group" aria-label="Exportaciones">
              <button class="btn btn-sm btn-outline-success" id="btnExcelOpFO">
                <i data-feather="file-text" class="me-1"></i> Excel
              </button>
              <button class="btn btn-sm btn-outline-warning" id="btnPdfOpFO">
                <i data-feather="file" class="me-1"></i> PDF
              </button>
            </div>

          </div>
        </div>
      </div>

      <div class="card-body px-4 pb-4">
        <div class="table-responsive">
          <table class="table align-middle mb-0" id="tblOpsFO">
            <thead>
              <tr>
                <th>Operación</th>
                <th>Ferro/Caja</th>
                <th>Destino</th>
                <th>Contenedores Maritimos</th>
                <th>Fecha</th>
                <th>Estatus</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>

            <tbody id="tbOpsFO">
              <!-- Render JS -->
            </tbody>
          </table>
        </div>

        <!-- Paginación FO -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3" id="foPagingWrap">
          <div class="small pn-muted" id="foPagingLbl">Mostrando 0–0 de 0</div>
          <nav aria-label="Paginación FO" id="foPagingNav">
            <ul class="pagination pagination-sm mb-0" id="foPaging">
              <!-- Render JS -->
            </ul>
          </nav>
        </div>

      </div>
    </div>

    </div>
  </main>

  <!-- Bootstrap + Feather -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

  <script>
    feather.replace();
  </script>

  <script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";
  </script>






  <!-- MODAL: Documentos -->
  <div class="modal fade" id="modalDocs" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4 border-0">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">Documentos</h5>
            <div class="small pn-muted">
              <span id="docsOperacionNumero">PN-OP-2026-001</span> — Puedes subir documentos
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">


          <!-- Upload -->
          <div class="card rounded-4 border-0 shadow-sm mb-3" id="docsUploadCard">
            <div class="card-body">
              <div class="fw-semibold mb-2">Subir documento</div>

              <!-- ✅ hidden refs para JS -->
              <input type="hidden" id="docsOperacionId" value="0">
              <input type="hidden" id="docsTipoOperacion" value="MAR"><!-- MAR | FO | LBMF -->
              <input type="hidden" id="docsContenedorId" value="0">
              <input type="hidden" id="docsContenedorTipo" value="">


              <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                  <label class="form-label pn-muted" for="docsTipo">Tipo</label>
                  <select class="form-select" id="docsTipo">
                    <option value="FACTURA">Factura</option>
                    <option value="BL">BL</option>
                    <option value="ISF">ISF</option>
                    <option value="OTRO">Otro</option>
                  </select>
                </div>

                <div class="col-12 col-md-7">
                  <label class="form-label pn-muted" for="docsArchivo">Archivo</label>
                  <input type="file" class="form-control" id="docsArchivo" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <div class="col-12 d-grid mt-2">
                  <button class="btn btn-primary" type="button" id="btnDocsSubir">
                    <i data-feather="upload" class="me-1"></i> Subir
                  </button>
                </div>
              </div>

              <div class="small pn-muted mt-2" id="docsHint">
                Recomendado: PDF/JPG/PNG. Tamaño máximo (definir en servidor).
              </div>
            </div>
          </div>

          <!-- Lista de docs -->
          <div class="fw-semibold mb-2">Documentos cargados</div>
          <div class="list-group" id="docsList">
            <!-- Render JS -->
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: Detalle Operación Marítima (Solo lectura) -->
  <div class="modal fade" id="modalDetalleMaritima" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-xxl-wide">
      <div class="modal-content rounded-4 border-0">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">Detalle de operación Marítima</h5>
            <div class="small pn-muted">Solo lectura — <span id="mar_numero">PN-OP-2026-001</span></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <div class="col-12 col-lg-4">
              <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="fw-semibold mb-2">Resumen</div>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Cliente</span> <span class="fw-semibold" id="mar_cliente">Andrea/Tommer</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Tipo</span> <span class="fw-semibold" id="mar_tipo">Marítimo</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Estatus</span> <span class="badge text-bg-success" id="mar_estatus">Abierta</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">ETD</span> <span class="fw-semibold" id="mar_etd">2026-02-05</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">ETA</span> <span class="fw-semibold" id="mar_eta">2026-02-12</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">BL</span> <span class="fw-semibold" id="mar_bl">BL-883120</span>
                    </li>
                  </ul>


                </div>
              </div>
            </div>

            <div class="col-12 col-lg-8">
              <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-body">
                  <div class="fw-semibold mb-2">Información</div>

                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Número de operación</label>
                      <input class="form-control" id="mar_numero_input" value="PN-OP-2026-001" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Puerto / Origen</label>
                      <input class="form-control" id="mar_puerto" value="Lázaro Cárdenas" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Naviera</label>
                      <input class="form-control" id="mar_naviera" value="(Mock) Naviera X" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Contenedor</label>
                      <input class="form-control" id="mar_contenedor" value="MSCU1234567" readonly>
                    </div>
                    <div class="col-12">
                      <label class="form-label pn-muted">Comentario</label>
                      <textarea class="form-control" id="mar_comentario" rows="3" readonly>(Mock) Comentario o notas visibles para cliente.</textarea>
                    </div>
                  </div>

                  <hr class="my-3">

                  <div class="fw-semibold mb-2 d-none">Eventos (solo lectura)</div>
                  <div class="table-responsive d-none">
                    <table class="table table-sm align-middle">
                      <thead>
                        <tr>
                          <th>Fecha</th>
                          <th>Evento</th>
                          <th>Comentario</th>
                        </tr>
                      </thead>
                      <tbody id="mar_eventos">
                        <tr>
                          <td>2026-02-06</td>
                          <td>Arribo</td>
                          <td class="pn-muted">(Mock) Arribo registrado</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

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

  <!-- MODAL: Detalle Operación FO (Solo lectura) -->
  <div class="modal fade" id="modalDetalleFO" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-xxl-wide">
      <div class="modal-content rounded-4 border-0">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">Detalle de operación FO</h5>
            <div class="small pn-muted">Solo lectura — <span id="fo_numero">PN-FO-2026-014</span></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">


            <div class="col-12 col-lg-4">
              <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="fw-semibold mb-2">Resumen</div>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Cliente</span>
                      <span class="fw-semibold" id="fo_cliente"><?php echo $data['nombre_cliente']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Ferro/Caja</span>
                      <span class="fw-semibold" id="fo_ferro">FERRO-00123</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Bultos totales</span>
                      <span class="fw-semibold" id="fo_bultos">120</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Transportista</span>
                      <span class="fw-semibold" id="fo_transportista">Transportes X</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Destino</span>
                      <span class="fw-semibold" id="fo_destino">Nogales</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Fecha</span>
                      <span class="fw-semibold" id="fo_fecha">2026-02-09</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span class="pn-muted">Estatus</span>
                      <span class="badge text-bg-warning" id="fo_estatus">En revisión</span>
                    </li>
                  </ul>


                </div>
              </div>
            </div>


            <div class="col-12 col-lg-8">
              <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-body">
                  <div class="fw-semibold mb-2">Información</div>

                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Número de operación</label>
                      <input class="form-control" id="fo_numero_input" value="PN-FO-2026-014" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Ferro/Caja</label>
                      <input class="form-control" id="fo_ferro_input" value="FERRO-00123" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Transportista</label>
                      <input class="form-control" id="fo_transportista_input" value="Transportes X" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Destino</label>
                      <input class="form-control" id="fo_destino_input" value="Nogales" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Bultos Totales</label>
                      <input class="form-control" id="fo_bultos_input" value="120" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label pn-muted">Fecha</label>
                      <input class="form-control" id="fo_fecha_input" value="2026-02-09" readonly>
                    </div>

                    <div class="col-12">
                      <label class="form-label pn-muted">Comentarios</label>
                      <textarea class="form-control" id="fo_comentarios" rows="3" readonly>(Mock) Comentarios visibles para cliente.</textarea>
                    </div>
                  </div>

                  <hr class="my-3">

                  <div class="fw-semibold mb-2">Contenedores marítimos asignados</div>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Operación Marítima</th>
                          <th>Contenedor Marítimo</th>
                          <th class="text-end">Bultos asignados</th>
                        </tr>
                      </thead>
                      <tbody id="fo_asignaciones">
                        <tr>
                          <td>PN-OP-2026-001</td>
                          <td>MSCU1234567</td>
                          <td class="text-end">80</td>
                        </tr>
                        <tr>
                          <td colspan="3" class="text-center pn-muted py-3">
                            (Mock) Aquí se listan las asignaciones reales...
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <hr class="my-3">

                  <div class="fw-semibold mb-2">Eventos (solo lectura)</div>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle">
                      <thead>
                        <tr>
                          <th>Fecha</th>
                          <th>Evento</th>
                          <th>Comentario</th>
                        </tr>
                      </thead>
                      <tbody id="fo_eventos">
                        <tr>
                          <td>2026-02-08</td>
                          <td>Salida</td>
                          <td class="pn-muted">(Mock) Registrado</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

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




</body>

</html>
<!-- JS del portal -->
<script src="<?php echo BASE_URL; ?>Assets/Js/PortalClientes/Kpis.js"></script>
<script src="<?php echo BASE_URL; ?>Assets/Js/PortalClientes/OperacionesMaritimas.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.umd.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/exportarTablas.js"></script>

<script>
  feather.replace();

  // Sidebar mobile toggle
  const sidebar = document.getElementById('pnSidebar');
  const overlay = document.getElementById('pnOverlay');
  const btnOpen = document.getElementById('btnOpenSidebar');
  const btnClose = document.getElementById('btnCloseSidebar');

  function openSidebar() {
    sidebar.classList.add('show');
    overlay.classList.add('show');
  }

  function closeSidebar() {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
  }

  if (btnOpen) btnOpen.addEventListener('click', openSidebar);
  if (btnClose) btnClose.addEventListener('click', closeSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
</script>