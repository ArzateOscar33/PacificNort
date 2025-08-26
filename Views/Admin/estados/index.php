<?php include 'Views/Template/admin_header.php'; ?>

<div class="container col-md-12 mt-3">
  <div class="row">
    <div class="col-12">
      <div class="card mt-3">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Estados</h3>
        </div>

        <div class="card-body">
          <!-- Barra superior: búsqueda + selector 25/50 + botón agregar -->
          <div class="row g-2 align-items-center mb-3">
            <!-- Buscador con contenedor relativo para las sugerencias -->
            <div class="col-12 col-md-8 position-relative">
              <input
                type="text"
                class="form-control"
                id="buscarEstado"
                name="buscarEstado"
                placeholder="Buscar Estado"
                autocomplete="off">
              <div
                id="sugerenciasEstado"
                class="list-group position-absolute top-100 start-0 w-100"
                style="display:none; z-index:1000;">
              </div>
            </div>

            <!-- Selector 25/50 y botón Agregar -->
            <div class="col-12 col-md-4 d-flex flex-wrap justify-content-md-end align-items-center gap-2">
              <label for="perPageSelect" class="mb-0 small text-muted">Mostrar</label>
              <select id="perPageSelect" class="form-control form-control-sm" style="width:auto">
                <option value="25" selected>25</option>
                <option value="50">50</option>
              </select>
              <span class="small text-muted">por página</span>

              <button
                id="btnAgregarEstado"
                class="btn btn-primary ms-md-2 w-100 w-md-auto"
                data-bs-toggle="modal"
                data-bs-target="#modalRegistrarEstado">
                <i class="fas fa-plus"></i> Agregar Estado
              </button>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-primary text-center">
                <tr>
                  <th>Nombre</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaEstados"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <nav aria-label="Paginación de estados" class="mt-3">
            <ul class="pagination justify-content-end" id="paginacion"></ul>
          </nav>
        </div> <!-- /card-body -->
      </div> <!-- /card -->
    </div> <!-- /col-12 -->
  </div> <!-- /row -->
</div> <!-- /container -->


<?php include 'Views/Template/admin_footer.php'; ?>

<!-- Modal: Crear / Editar -->
<div class="modal fade" id="modalRegistrarEstado" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalRegistrarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarEstadoLabel">
          <i data-feather="plus-circle" class="me-2"></i> Registrar Estado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formEstado" method="POST" action="#">
          <input type="hidden" id="id_estado" name="id_estado" value="">

          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Estado</label>
            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Baja California">
          </div>

          <div class="modal-footer px-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>
            <button type="submit" id="btnSubmit" class="btn btn-primary">
              <i data-feather="check-circle" class="me-1"></i> Agregar
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/estados.js"></script>
