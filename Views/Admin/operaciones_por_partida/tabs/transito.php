<style>
  .modal-xxl-wide {
    max-width: min(1600px, calc(100vw - 2rem));
  }
</style>

<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="truck" class="me-1"></i> Revisión de Ferros y Envíos
      </h5>

      <button class="btn btn-light btn-sm" id="ferros_envios_btnRefrescar" type="button">
        <i data-feather="refresh-cw" class="me-1"></i> Refrescar
      </button>
    </div>

    <div class="card-body">

      <!-- ===================== FILTROS ===================== -->
      <div class="d-flex flex-wrap align-items-end gap-2 mb-3">

        <!-- FERRO: INPUT + SUGERENCIAS -->
        <div class="position-relative" style="max-width:280px; width:100%;">
          <label class="form-label small mb-1 text-muted">Ferro</label>
          <input type="hidden" id="ferros_envios_ferroId" value="">
          <input type="text" id="ferros_envios_buscarFerro" class="form-control"
                 placeholder="Buscar ferro... (Ej. Ferro 17 / FO-22)"
                 autocomplete="off">
          <div id="ferros_envios_sugerenciasFerros"
               class="list-group position-absolute w-100 z-3"
               style="z-index:999;">
          </div>
        </div>

        <!-- FECHAS -->
        <div style="max-width:170px; width:100%;">
          <label class="form-label small mb-1 text-muted">Fecha inicio</label>
          <input type="date" class="form-control" id="ferros_envios_fi">
        </div>

        <div style="max-width:170px; width:100%;">
          <label class="form-label small mb-1 text-muted">Fecha fin</label>
          <input type="date" class="form-control" id="ferros_envios_ff">
        </div>

        <!-- (Opcional) Búsqueda rápida de producto -->
        <div style="max-width:360px; width:100%;">
          <label class="form-label small mb-1 text-muted">Producto</label>
          <input type="text" id="ferros_envios_buscarProducto" class="form-control"
                 placeholder="Buscar producto por descripción / UPC / marca"
                 autocomplete="off">
        </div>

        <!-- Botón aplicar filtros (opcional, pero útil si no quieres recargar en cada tecla) -->
        <button class="btn btn-primary" id="ferros_envios_btnFiltrar" type="button">
          <i data-feather="filter" class="me-1"></i> Filtrar
        </button>

        <!-- Botón limpiar -->
        <button class="btn btn-outline-secondary" id="ferros_envios_btnLimpiar" type="button">
          <i data-feather="x" class="me-1"></i> Limpiar
        </button>
        
        <div class="ms-auto text-end p-2">
            <span class="badge bg-success text-white d-none gap-4" id="ferros_envios_badgeTotalCajas">0 cajas</span>
            </label>
        </div>



      </div>

      <!-- ===================== TABLA (SOLO LECTURA) ===================== -->
      <div class="table-responsive">
        <table class="table align-middle" id="ferros_envios_tabla">
          <thead class="table-dark">
            <tr class="text-center">
              <th   class="text-start">Ferro</th>
              <th   class="text-start">Producto</th>
              <th >Cajas</th>
              <th   class="text-start">Factura</th>
              <th  >Estatus</th>

              <!-- Recomendado: para que el filtro de fechas sea visible -->
              <th  >Fecha envío</th>
            </tr>
          </thead>
          <tbody id="ferros_envios_tbody"></tbody>
        </table>
      </div>

      <div id="ferros_envios_empty" class="alert alert-light border d-none mb-0">
        No hay registros para los filtros seleccionados.
      </div>

    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_ferros_catalogo.js"></script>
