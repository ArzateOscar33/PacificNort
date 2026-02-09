<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PacificNort Suite | Portal Cliente</title>

  <!-- Bootstrap 5.3.x -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --pn-sidebar-w: 280px;
    }
    body{
      background: #f6f7fb;
    }
    .pn-sidebar{
      width: var(--pn-sidebar-w);
      min-height: 100vh;
      position: sticky;
      top: 0;
      background: #0f172a; /* slate-900 */
      color: #e2e8f0;      /* slate-200 */
    }
    .pn-sidebar .brand{
      padding: 1rem 1.25rem;
      border-bottom: 1px solid rgba(226,232,240,.12);
    }
    .pn-sidebar .nav-link{
      color: #cbd5e1;
      border-radius: .75rem;
      padding: .6rem .9rem;
      display:flex;
      align-items:center;
      gap:.55rem;
      margin:.15rem .5rem;
    }
    .pn-sidebar .nav-link:hover{
      background: rgba(148,163,184,.12);
      color:#fff;
    }
    .pn-sidebar .nav-link.active{
      background: rgba(56,189,248,.15);
      color:#e0f2fe;
      border: 1px solid rgba(56,189,248,.25);
    }
    .pn-content{
      min-height: 100vh;
    }
    .pn-topbar{
      background: #ffffff;
      border-bottom: 1px solid rgba(15,23,42,.08);
    }
    .kpi-card{
      border: 1px solid rgba(15,23,42,.08);
      border-radius: 1rem;
      background:#fff;
    }
    .kpi-icon{
      width: 42px; height: 42px;
      border-radius: .9rem;
      display:flex;
      align-items:center;
      justify-content:center;
      border: 1px solid rgba(15,23,42,.08);
      background: #f8fafc;
    }
    .table thead th{
      background: #f8fafc;
      border-bottom: 1px solid rgba(15,23,42,.08);
      color:#0f172a;
      font-weight: 600;
      white-space: nowrap;
    }
    .badge-soft{
      background: rgba(15,23,42,.06);
      color:#0f172a;
      border: 1px solid rgba(15,23,42,.10);
      font-weight: 600;
    }
    .chip{
      display:inline-flex;
      align-items:center;
      gap:.35rem;
      padding:.15rem .55rem;
      border-radius: 999px;
      background: rgba(2,132,199,.08);
      color:#075985;
      border: 1px solid rgba(2,132,199,.18);
      font-size:.825rem;
      font-weight:600;
    }
    .modal-xxl-wide{
      max-width: min(1400px, calc(100vw - 2rem));
    }
    .pn-muted{
      color:#64748b;
    }
    .search-hint{
      font-size:.9rem;
      color:#64748b;
    }
    @media (max-width: 992px){
      .pn-sidebar{
        position: fixed;
        left: -100%;
        z-index: 1040;
        transition: left .2s ease;
      }
      .pn-sidebar.show{
        left: 0;
      }
      .pn-overlay{
        display:none;
      }
      .pn-overlay.show{
        display:block;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 1039;
      }
    }
  </style>
</head>

<body>
<div class="d-flex">

  <!-- Sidebar -->
  <aside class="pn-sidebar" id="pnSidebar">
    <div class="brand d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <div class="kpi-icon text-info">
          <i data-feather="anchor"></i>
        </div>
        <div>
          <div class="fw-bold" style="line-height:1.1;">PacificNort Suite</div>
          <div class="small pn-muted">Portal Cliente</div>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-light d-lg-none" id="btnCloseSidebar" type="button" aria-label="Cerrar">
        <i data-feather="x"></i>
      </button>
    </div>

    <div class="px-3 pt-3 pb-2">
      <div class="small pn-muted mb-2">MENÚ</div>
      <nav class="nav flex-column">
        <a class="nav-link active" href="#">
          <i data-feather="layers"></i> Operaciones
        </a>
        <a class="nav-link" href="#">
          <i data-feather="folder"></i> Documentos
        </a>
        <a class="nav-link" href="#">
          <i data-feather="user"></i> Mi cuenta
        </a>
      </nav>
    </div>

    <div class="mt-auto px-3 pb-3">
      <div class="p-3 rounded-4" style="background: rgba(148,163,184,.10); border: 1px solid rgba(148,163,184,.15);">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-semibold">Cliente: Andrea/Tommer</div>
            <div class="small pn-muted">Usuario: Andrea</div>
          </div>
          <span class="badge rounded-pill text-bg-info">Solo lectura</span>
        </div>
        <hr class="my-3" style="border-color: rgba(226,232,240,.18);">
        <button class="btn btn-outline-light w-100" type="button">
          <i data-feather="log-out" class="me-1"></i> Cerrar sesión
        </button>
      </div>
    </div>
  </aside>

  <div class="pn-overlay" id="pnOverlay"></div>

  <!-- Main -->
  <main class="pn-content flex-grow-1">

    <!-- Topbar -->
    <div class="pn-topbar">
      <div class="container-fluid py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary d-lg-none" id="btnOpenSidebar" type="button">
              <i data-feather="menu"></i>
            </button>
            <div>
              <h4 class="mb-0">Operaciones</h4>
              <div class="search-hint">Consulta y filtra tus operaciones. Puedes <b>subir documentos</b> por operación.</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary" type="button">
              <i data-feather="refresh-cw" class="me-1"></i> Refrescar
            </button>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalAyuda">
              <i data-feather="help-circle" class="me-1"></i> Ayuda
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid py-4">

      <!-- KPIs (opcionales para cliente) -->
      <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
          <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="pn-muted small">Operaciones activas</div>
                <div class="h4 mb-0">12</div>
              </div>
              <div class="kpi-icon">
                <i data-feather="activity"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="pn-muted small">Arribos (ETA próximos)</div>
                <div class="h4 mb-0">3</div>
              </div>
              <div class="kpi-icon">
                <i data-feather="calendar"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="pn-muted small">Docs pendientes</div>
                <div class="h4 mb-0">5</div>
              </div>
              <div class="kpi-icon">
                <i data-feather="file-text"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="card shadow-sm border-0 rounded-4 mb-3">
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label">Buscar</label>
              <div class="input-group">
                <span class="input-group-text"><i data-feather="search"></i></span>
                <input class="form-control" placeholder="Operación, BL, contenedor..." />
              </div>
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label">Tipo</label>
              <select class="form-select">
                <option value="">Todos</option>
                <option>Marítimo</option>
                <option>Ferroviario (FO)</option>
                <option>Mixto (LBMF)</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label">Estatus</label>
              <select class="form-select">
                <option value="">Todos</option>
                <option>Pendiente</option>
                <option>En revisión</option>
                <option>Abierta</option>
                <option>Cerrada</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label">Rango (ETA)</label>
              <input type="date" class="form-control" />
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label">&nbsp;</label>
              <input type="date" class="form-control" />
            </div>
            <div class="col-12 col-md-1 d-grid">
              <button class="btn btn-dark" type="button">
                <i data-feather="filter" class="me-1"></i> Filtrar
              </button>
            </div>
          </div>

          <hr class="my-3">

          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="pn-muted small">Filtros activos:</span>
            <span class="chip"><i data-feather="user" style="width:16px;height:16px;"></i> Andrea/Tommer</span>
            <span class="chip"><i data-feather="tag" style="width:16px;height:16px;"></i> Abiertas</span>
            <button class="btn btn-sm btn-outline-secondary ms-auto" type="button">
              <i data-feather="x-circle" class="me-1"></i> Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
              <div class="fw-semibold">Listado de operaciones</div>
              <div class="small pn-muted">Solo lectura. Acciones disponibles: <b>ver detalle</b> y <b>documentos</b>.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <select class="form-select form-select-sm" style="width:auto;">
                <option>15 / pág</option>
                <option>30 / pág</option>
                <option>50 / pág</option>
              </select>
              <button class="btn btn-sm btn-outline-secondary" type="button">
                <i data-feather="download" class="me-1"></i> Exportar
              </button>
            </div>
          </div>
        </div>

        <div class="card-body px-4 pb-4">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Operación</th>
                  <th>Tipo</th>
                  <th>BL</th>
                  <th>ETD</th>
                  <th>ETA</th>
                  <th>Estatus</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="fw-semibold">PN-OP-2026-001</div>
                    <div class="small pn-muted">Lázaro Cárdenas</div>
                  </td>
                  <td><span class="badge badge-soft">Marítimo</span></td>
                  <td>BL-883120</td>
                  <td>2026-02-05</td>
                  <td>2026-02-12</td>
                  <td><span class="badge text-bg-success">Abierta</span></td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#modalDetalleOp">
                      <i data-feather="eye" class="me-1"></i> Ver
                    </button>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalDocs">
                      <i data-feather="folder" class="me-1"></i> Docs
                    </button>
                  </td>
                </tr>

                <tr>
                  <td>
                    <div class="fw-semibold">PN-FO-2026-014</div>
                    <div class="small pn-muted">FO - Nogales</div>
                  </td>
                  <td><span class="badge badge-soft">Ferroviario (FO)</span></td>
                  <td>—</td>
                  <td>2026-02-01</td>
                  <td>2026-02-09</td>
                  <td><span class="badge text-bg-warning">En revisión</span></td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#modalDetalleOp">
                      <i data-feather="eye" class="me-1"></i> Ver
                    </button>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalDocs">
                      <i data-feather="folder" class="me-1"></i> Docs
                    </button>
                  </td>
                </tr>

                <tr>
                  <td>
                    <div class="fw-semibold">PN-LBMF-2026-003</div>
                    <div class="small pn-muted">Mixto</div>
                  </td>
                  <td><span class="badge badge-soft">Mixto (LBMF)</span></td>
                  <td>BL-990011</td>
                  <td>2026-02-03</td>
                  <td>2026-02-17</td>
                  <td><span class="badge text-bg-success">Abierta</span></td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#modalDetalleOp">
                      <i data-feather="eye" class="me-1"></i> Ver
                    </button>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalDocs">
                      <i data-feather="folder" class="me-1"></i> Docs
                    </button>
                  </td>
                </tr>

                <tr>
                  <td colspan="7" class="text-center py-4 pn-muted">
                    <i data-feather="info" class="me-1"></i> (Mockup) Aquí irían más registros...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
            <div class="small pn-muted">Mostrando 1–15 de 87</div>
            <nav aria-label="Paginación">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">«</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">»</a></li>
              </ul>
            </nav>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<!-- MODAL: Detalle Operación -->
<div class="modal fade" id="modalDetalleOp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-xxl-wide">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Detalle de operación</h5>
          <div class="small pn-muted">Solo lectura — PN-OP-2026-001</div>
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
                    <span class="pn-muted">Cliente</span> <span class="fw-semibold">Andrea/Tommer</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span class="pn-muted">Tipo</span> <span class="fw-semibold">Marítimo</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span class="pn-muted">Estatus</span> <span class="badge text-bg-success">Abierta</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span class="pn-muted">ETD</span> <span class="fw-semibold">2026-02-05</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span class="pn-muted">ETA</span> <span class="fw-semibold">2026-02-12</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span class="pn-muted">BL</span> <span class="fw-semibold">BL-883120</span>
                  </li>
                </ul>
                <div class="mt-3 d-grid">
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalDocs">
                    <i data-feather="folder" class="me-1"></i> Ver/Subir documentos
                  </button>
                </div>
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
                    <input class="form-control" value="PN-OP-2026-001" readonly>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label pn-muted">Puerto / Origen</label>
                    <input class="form-control" value="Lázaro Cárdenas" readonly>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label pn-muted">Naviera</label>
                    <input class="form-control" value="(Mock) Naviera X" readonly>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label pn-muted">Contenedor</label>
                    <input class="form-control" value="MSCU1234567" readonly>
                  </div>
                  <div class="col-12">
                    <label class="form-label pn-muted">Comentario</label>
                    <textarea class="form-control" rows="3" readonly>(Mock) Comentario o notas visibles para cliente.</textarea>
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
                    <tbody>
                      <tr>
                        <td>2026-02-06</td>
                        <td>Arribo</td>
                        <td class="pn-muted">(Mock) Arribo registrado</td>
                      </tr>
                      <tr>
                        <td>2026-02-07</td>
                        <td>Revisión documental</td>
                        <td class="pn-muted">(Mock) En proceso</td>
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

<!-- MODAL: Documentos -->
<div class="modal fade" id="modalDocs" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Documentos</h5>
          <div class="small pn-muted">PN-OP-2026-001 — Puedes subir documentos</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info d-flex align-items-start gap-2">
          <i data-feather="shield" class="mt-1"></i>
          <div>
            <div class="fw-semibold">Acceso restringido</div>
            <div class="small mb-0">
              Solo puedes ver/subir documentos de operaciones que pertenezcan a tu cuenta.
            </div>
          </div>
        </div>

        <!-- Upload -->
        <div class="card rounded-4 border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="fw-semibold mb-2">Subir documento</div>
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-5">
                <label class="form-label pn-muted">Tipo</label>
                <select class="form-select">
                  <option>Factura</option>
                  <option>BL</option>
                  <option>ISF</option>
                  <option>Otro</option>
                </select>
              </div>
              <div class="col-12 col-md-7">
                <label class="form-label pn-muted">Archivo</label>
                <input type="file" class="form-control">
              </div>
              <div class="col-12 d-grid mt-2">
                <button class="btn btn-primary" type="button">
                  <i data-feather="upload" class="me-1"></i> Subir
                </button>
              </div>
            </div>
            <div class="small pn-muted mt-2">
              Recomendado: PDF/JPG/PNG. Tamaño máximo (definir en servidor).
            </div>
          </div>
        </div>

        <!-- Lista de docs -->
        <div class="fw-semibold mb-2">Documentos cargados</div>
        <div class="list-group">
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <i data-feather="file-text"></i>
              <div>
                <div class="fw-semibold">BL_883120.pdf</div>
                <div class="small pn-muted">Tipo: BL · Subido: 2026-02-06</div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-outline-secondary" type="button">
                <i data-feather="eye" class="me-1"></i> Ver
              </button>
              <button class="btn btn-sm btn-outline-secondary" type="button">
                <i data-feather="download" class="me-1"></i> Descargar
              </button>
            </div>
          </div>

          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <i data-feather="file"></i>
              <div>
                <div class="fw-semibold">Factura_001.pdf</div>
                <div class="small pn-muted">Tipo: Factura · Subido: 2026-02-07</div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-outline-secondary" type="button">
                <i data-feather="eye" class="me-1"></i> Ver
              </button>
              <button class="btn btn-sm btn-outline-secondary" type="button">
                <i data-feather="download" class="me-1"></i> Descargar
              </button>
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

<!-- MODAL: Ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header">
        <h5 class="modal-title">Ayuda</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <ul class="mb-0">
          <li>Este portal es <b>solo lectura</b>: no puedes editar ni eliminar operaciones.</li>
          <li>Puedes <b>subir documentos</b> por operación desde “Docs”.</li>
          <li>Usa filtros (tipo/estatus/ETA) para encontrar más rápido.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" data-bs-dismiss="modal" type="button">Entendido</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap + Feather -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

<script>
  feather.replace();

  // Sidebar mobile toggle
  const sidebar = document.getElementById('pnSidebar');
  const overlay = document.getElementById('pnOverlay');
  const btnOpen = document.getElementById('btnOpenSidebar');
  const btnClose = document.getElementById('btnCloseSidebar');

  function openSidebar(){
    sidebar.classList.add('show');
    overlay.classList.add('show');
  }
  function closeSidebar(){
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
  }

  if(btnOpen) btnOpen.addEventListener('click', openSidebar);
  if(btnClose) btnClose.addEventListener('click', closeSidebar);
  if(overlay) overlay.addEventListener('click', closeSidebar);
</script>
</body>
</html>
