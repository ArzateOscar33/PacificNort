<!-- =========================
     RUTAS FERRO/CAJA (VISTA)
     ========================= -->


<!-- ========== RESUMEN DE RUTAS FERRO/CAJA (COMPACTO) ========== -->
<div class="card mt-4" id="rutasFerroResumen">
  <div class="card-header">
    <div class="row align-items-center gx-2 gy-2">
      <!-- Título -->
      <div class="col-12 col-lg-3 d-flex align-items-center gap-2">
        <i data-feather="map"></i>
        <strong class="mb-0">Resumen de Rutas Ferro/Caja</strong>
      </div>

      <!-- Buscador -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i data-feather="search"></i></span>
          <input
            type="text"
            id="rutasBuscar"
            class="form-control"
            placeholder="Buscar (operación / ferro / cliente / destino)" />
        </div>
      </div>

      <!-- Fechas (compactas) -->
      <div class="col-6 col-sm-3 col-lg-2">
        <input
          type="date"
          class="form-control form-control-sm"
          id="rutasFechaIni"
          title="Desde" />
      </div>
      <div class="col-6 col-sm-3 col-lg-2">
        <input
          type="date"
          class="form-control form-control-sm"
          id="rutasFechaFin"
          title="Hasta" />
      </div>

      <!-- Per page -->
      <div class="col-12 col-lg-1 d-flex justify-content-lg-end">
        <select id="rutasPerPage" class="form-control form-control-sm">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="500">500</option>
          <option value="1000">1000</option>
          <option value="10000000">Todos</option>
        </select>
      </div>



      <!-- ✅ NUEVOS FILTROS (2da fila) -->
      <div class="col-12">
        <div class="row gx-2 gy-2">
          <div class="col-12 col-sm-6 col-lg-3">
            <select id="rutasFiltroCliente" class="form-control form-control-sm" title="Cliente">
              <option value="">Cliente: Todos</option>
              <?php if (!empty($data['clientes'])): foreach ($data['clientes'] as $c): ?>
                  <option value="<?= (int)$c['id_cliente']; ?>">
                    <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
              <?php endforeach;
              endif; ?>
            </select>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <select id="rutasFiltroOrigen" class="form-control form-control-sm" title="Origen">
              <option value="">Origen: Todos</option>
              <?php if (!empty($data['puertos'])): foreach ($data['puertos'] as $o): ?>
                  <option value="<?= (int)$o['id_puerto']; ?>">
                    <?= htmlspecialchars($o['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
              <?php endforeach;
              endif; ?>
            </select>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <select id="rutasFiltroUbicacion" class="form-control form-control-sm" title="Ubicación actual">
              <option value="">Ubicación actual: Todas</option>
              <?php if (!empty($data['ciudades'])): foreach ($data['ciudades'] as $u): ?>
                  <option value="<?= (int)$u['id_ciudad']; ?>">
                    <?= htmlspecialchars($u['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
              <?php endforeach;
              endif; ?>
            </select>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <select id="rutasFiltroDestino" class="form-control form-control-sm" title="Destino">
              <option value="">Destino: Todos</option>
              <?php if (!empty($data['ciudades'])): foreach ($data['ciudades'] as $d): ?>
                  <option value="<?= (int)$d['id_ciudad']; ?>">
                    <?= htmlspecialchars($d['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
              <?php endforeach;
              endif; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Export -->
      <div class="col-12 col-lg-12 d-flex flex-wrap justify-content-lg-end gap-2">
        <button class="btn btn-sm btn-outline-success" id="rutasExcel">
          <i data-feather="file-text" class="me-1"></i>Excel
        </button>
        <button class="btn btn-sm btn-outline-warning" id="rutasPdf">
          <i data-feather="file" class="me-1"></i>PDF
        </button>
      </div>
    </div>
  </div>

  <div class="card-body pt-2">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0 text-center" id="tablaRutas">
        <thead class="table-light">
          <tr>
            <th style="min-width:140px;">Operación Maritma</th>
            <th style="min-width:120px;">Contenedor Maritimo</th>
            <th style="min-width:120px;">Ferro/Caja</th>
            <th style="min-width:120px;">Transportista</th>
            <th>Cliente</th>
            <th>Origen</th>
            <th>Ubicación actual</th>
            <th>Destino</th>
            <th style="min-width:130px;">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyRutasFerro">
          <tr id="rutasEmptyRow">
            <td colspan="8" class="text-center text-muted">
              <i data-feather="info" class="me-1"></i> No hay rutas para mostrar.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
      <div class="small text-muted">
        <span id="rutasMeta">Mostrando 0-0 de 0</span>
      </div>
      <nav aria-label="Paginación Rutas Ferro/Caja">
        <ul id="rutasPaginacion" class="pagination pagination-sm mb-0"></ul>
      </nav>
    </div>
  </div>
</div>

<!-- =========================
     MODAL: HISTORIAL DE RUTA (TRAZABILIDAD FERRO/CAJA)
     ========================= -->
<style>
  /* ===== Timeline Ruta (Bootstrap friendly) ===== */
  .ruta-timeline {
    position: relative;
    padding-left: 1.25rem;
  }

  .ruta-timeline::before {
    content: "";
    position: absolute;
    left: .45rem;
    top: .25rem;
    bottom: .25rem;
    width: 2px;
    background: rgba(0, 0, 0, .12);
  }

  .ruta-step {
    position: relative;
    padding: .75rem .75rem .75rem 1.25rem;
    border-radius: .75rem;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, .08);
    margin-bottom: .75rem;
  }

  .ruta-step::before {
    content: "";
    position: absolute;
    left: -1.02rem;
    top: 1.05rem;
    width: .9rem;
    height: .9rem;
    border-radius: 50%;
    background: #0d6efd;
    /* primary */
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, .25);
  }

  .ruta-step.is-origin::before {
    background: #198754;
    /* success */
    box-shadow: 0 0 0 2px rgba(25, 135, 84, .25);
  }

  .ruta-step.is-destino::before {
    background: #dc3545;
    /* danger */
    box-shadow: 0 0 0 2px rgba(220, 53, 69, .25);
  }

  .ruta-step .ruta-fecha {
    font-size: .78rem;
    color: rgba(0, 0, 0, .6);
    white-space: nowrap;
  }

  .ruta-step .ruta-lugar {
    font-weight: 600;
    line-height: 1.15;
  }

  .ruta-step .ruta-chip {
    font-size: .72rem;
  }

  .ruta-step:hover {
    border-color: rgba(13, 110, 253, .25);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
  }

  .ruta-step.active {
    border-color: rgba(25, 135, 84, .35);
    box-shadow: 0 0 0 .25rem rgba(25, 135, 84, .12);
  }
</style>

<div class="modal fade" id="modalRutaHistorial" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header bg-success text-white">
        <div class="d-flex align-items-center gap-2">
          <i data-feather="map-pin"></i>
          <div class="lh-1">
            <h5 class="modal-title mb-1">Historial de Ruta</h5>
            <div class="small opacity-90">
              <span class="me-2">Operación:</span>
              <span class="badge bg-light text-dark" id="rutaHist_badgeOperacion">—</span>

              <span class="ms-3 me-2">Contenedor:</span>
              <span class="badge bg-light text-dark" id="rutaHist_badgeContenedor">—</span>

              <span class="ms-3 me-2">Ferro/Caja:</span>
              <span class="badge bg-light text-dark" id="rutaHist_badgeFerro">—</span>
            </div>
          </div>
        </div>

        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- Hidden refs (para la lógica después) -->
        <input type="hidden" id="rutaHist_operacionId" value="">
        <input type="hidden" id="rutaHist_operacionFerroId" value="">
        <input type="hidden" id="rutaHist_asignacionId" value="">
        <input type="hidden" id="rutaHist_contenedorFisicoId" value="">
        <input type="hidden" id="rutaHist_destinoNombre" value="">
        <input type="hidden" id="rutaHist_llegoDestino" value="0">

        <!-- Meta / acciones -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div class="small text-muted" id="rutaHist_meta">
            Selecciona una ruta para ver el historial.
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" id="rutaHist_btnExcel">
              <i data-feather="file-text" class="me-1"></i>Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning" id="rutaHist_btnPdf">
              <i data-feather="file" class="me-1"></i>PDF
            </button>
          </div>
        </div>

        <div class="row g-3">
          <!-- Timeline -->
          <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <i data-feather="git-commit"></i>
                  <strong class="mb-0">Ruta (Origen → Paradas → Destino)</strong>
                </div>

                <span class="badge bg-primary text-white p-2" id="rutaHist_badgeTotalParadas">0 paradas</span>
              </div>

              <div class="card-body">
                <!-- Contenedor donde después pintaremos los steps -->
                <div id="rutaHist_timeline" class="ruta-timeline">

                  <!-- Placeholder -->
                  <div class="ruta-step is-origin">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <div>
                        <div class="ruta-lugar">Origen —</div>
                        <div class="small text-muted">Puerto/Origen de salida</div>
                      </div>
                      <div class="ruta-fecha">—</div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                      <span class="badge bg-success ruta-chip">ORIGEN</span>
                      <span class="badge bg-light text-dark ruta-chip">Sin datos aún</span>
                    </div>
                  </div>

                  <div class="ruta-step">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <div>
                        <div class="ruta-lugar">Ubicación registrada —</div>
                        <div class="small text-muted">Parada / checkpoint</div>
                      </div>
                      <div class="ruta-fecha">—</div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                      <span class="badge bg-primary ruta-chip">EVENTO</span>
                      <span class="badge bg-light text-dark ruta-chip">Aquí verás cada movimiento</span>
                    </div>
                  </div>

                  <div class="ruta-step is-destino">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <div>
                        <div class="ruta-lugar">Destino —</div>
                        <div class="small text-muted">Ciudad destino final</div>
                      </div>
                      <div class="ruta-fecha">—</div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                      <span class="badge bg-danger ruta-chip">DESTINO</span>
                      <span class="badge bg-light text-dark ruta-chip">Sin datos aún</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Panel detalle (opcional, bonito para notas/usuario) -->
          <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-light d-flex align-items-center gap-2">
                <i data-feather="file-text"></i>
                <strong class="mb-0">Detalle del evento</strong>
              </div>

              <div class="card-body">
                <div class="text-muted small mb-2">
                  Haz click en un punto de la ruta para ver notas y responsable.
                </div>

                <div class="mb-3">
                  <div class="small text-muted">Fecha</div>
                  <div class="fw-semibold" id="rutaHist_det_fecha">—</div>
                </div>

                <div class="mb-3">
                  <div class="small text-muted">Ubicación</div>
                  <div class="fw-semibold" id="rutaHist_det_ubicacion">—</div>
                </div>

                <div class="mb-3">
                  <div class="small text-muted">Notas</div>
                  <div class="border rounded-3 p-2 bg-light" id="rutaHist_det_notas">—</div>
                </div>

                <div class="mb-0">
                  <div class="small text-muted">Usuario</div>
                  <div class="fw-semibold" id="rutaHist_det_usuario">—</div>
                </div>
              </div>

              <div class="card-footer bg-white">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="rutaHist_btnLimpiarDetalle">
                  Limpiar selección
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cerrar
        </button>
      </div>

    </div>
  </div>
</div>



<script src="<?php echo BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/trazabilidad_catalogo.js"></script>
<script>
  // Si no lo tienes en otro lado:
  // const BASE_URL = "<?= BASE_URL ?>";
  feather.replace();
</script>


<script>
  function forzarMayusculas(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener("input", function() {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toUpperCase();
      this.setSelectionRange(start, end);
    });
  }


  // Uso
  forzarMayusculas("rutaOperacionFerroNombre");
</script>