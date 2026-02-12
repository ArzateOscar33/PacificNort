<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PacificNort Suite | Portal Cliente</title>

  <!-- Bootstrap 5.3.x -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f6f7fb;
    }

    .pn-topnav {
      background: #0f172a;
      /* slate-900 */
      color: #e2e8f0;
      border-bottom: 1px solid rgba(226, 232, 240, .12);
    }

    .pn-brand {
      display: flex;
      align-items: center;
      gap: .65rem;
      color: #e2e8f0;
      text-decoration: none;
    }

    .pn-brand .kpi-icon {
      width: 42px;
      height: 42px;
      border-radius: .9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(226, 232, 240, .12);
      background: rgba(148, 163, 184, .10);
      color: #38bdf8;
    }

    .pn-muted {
      color: #64748b;
    }

    .pn-muted-inv {
      color: rgba(226, 232, 240, .75);
    }

    .pn-topbar {
      background: #ffffff;
      border-bottom: 1px solid rgba(15, 23, 42, .08);
    }

    .kpi-card {
      border: 1px solid rgba(15, 23, 42, .08);
      border-radius: 1rem;
      background: #fff;
    }

    .kpi-icon {
      width: 42px;
      height: 42px;
      border-radius: .9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(15, 23, 42, .08);
      background: #f8fafc;
    }

    .table thead th {
      background: #f8fafc;
      border-bottom: 1px solid rgba(15, 23, 42, .08);
      color: #0f172a;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-soft {
      background: rgba(15, 23, 42, .06);
      color: #0f172a;
      border: 1px solid rgba(15, 23, 42, .10);
      font-weight: 600;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .15rem .55rem;
      border-radius: 999px;
      background: rgba(2, 132, 199, .08);
      color: #075985;
      border: 1px solid rgba(2, 132, 199, .18);
      font-size: .825rem;
      font-weight: 600;
    }

    .modal-xxl-wide {
      max-width: min(1400px, calc(100vw - 2rem));
    }

    .search-hint {
      font-size: .9rem;
      color: #64748b;
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
              Consulta y filtra tus operaciones. Puedes <b>subir documentos</b> por operación.
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

      <!-- KPIs -->
      <div class="row g-3 mb-3" id="kpiRow">
        <div class="col-12 col-md-4">
          <div class="kpi-card p-3" id="kpiCardActivas">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="pn-muted small">Operaciones activas</div>
                <div class="h4 mb-0" id="kpiOpsActivas">0</div>
              </div>
              <div class="kpi-icon"><i data-feather="activity"></i></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="kpi-card p-3" id="kpiCardETA">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="pn-muted small">Arribos (ETA próximos)</div>
                <div class="h4 mb-0" id="kpiArribosETA">0</div>
              </div>
              <div class="kpi-icon"><i data-feather="calendar"></i></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="kpi-card p-3" id="kpiCardDocs">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="pn-muted small">Docs pendientes</div>
                <div class="h4 mb-0" id="kpiDocsPendientes">0</div>
              </div>
              <div class="kpi-icon"><i data-feather="file-text"></i></div>
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
              </select>

              <div class="btn-group" role="group" aria-label="Exportaciones">
                <button class="btn btn-sm btn-outline-success" id="btnExcelOpMar">
                  <i data-feather="file-text" class="me-1"></i> Excel
                </button>
                <button class="btn btn-sm btn-outline-warning" id="btnPdfOpMar">
                  <i data-feather="file" class="me-1"></i> PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body px-4 pb-4">
          <div class="table-responsive">
            <table class="table align-middle mb-0" id="tblOpsMar">
              <thead>
                <tr>
                  <th>Operación</th>
                  <th>Contenedor Martimo</th>
                  <th>BL</th>
                  <th>ETD</th>
                  <th>ETA</th>
                  <th>Estatus</th>
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
      <div class="card shadow-sm border-0 rounded-4 mb-3" id="cardFiltrosFO">
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
      <div class="card shadow-sm border-0 rounded-4 mt-3" id="cardTablaFO">
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

            <!-- Resumen -->
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

            <!-- Información -->
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

                </div><!-- /card-body -->
              </div><!-- /card -->
            </div>

          </div><!-- /row -->
        </div><!-- /modal-body -->

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cerrar</button>
        </div>
      </div>
    </div>
  </div>




</body>

</html>
<!-- JS del portal -->
<script src="<?php echo BASE_URL; ?>Assets/Js/PortalClientes/OperacionesMaritimas.js"></script>
<script src="<?php echo BASE_URL; ?>Assets/Js/PortalClientes/OperacionesFerro.js"></script>
<script src="<?php echo BASE_URL; ?>Assets/Js/PortalClientes/DocumentosTerrestres.js"></script>

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