<?php include 'Views/Template/admin_header.php'; ?>
<div class="container col-md-12 mt-3">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Ciudades</h3>
        </div>

        <div class="card-body">
          <!-- Barra superior: buscador + filtro estado + botón -->
          <div class="row g-2 align-items-center mb-3">
            <!-- Buscador -->
            <div class="col-12 col-lg-6 position-relative">
              <input type="text" id="buscarCiudad" class="form-control" placeholder="Buscar Ciudad" autocomplete="off">
              <div id="sugerenciasCiudad"
                   class="list-group position-absolute top-100 start-0 w-100"
                   style="display:none; z-index:999;"></div>
            </div>

            <!-- Filtro por Estado -->
            <div class="col-12 col-lg-4">
              <select name="estado_id_filtro" id="estado_id_filtro" class="form-control">
                <option value="">Todos los estados</option>
                <?php foreach ($data['estados'] as $estado): ?>
                  <option value="<?= $estado['id_estado'] ?>"><?= $estado['nombre_estado'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Botón Agregar -->
            <div class="col-12 col-lg-2 text-lg-end">
              <button id="btnAgregarCiudad"
                      class="btn btn-primary w-100 w-lg-auto"
                      data-bs-toggle="modal"
                      data-bs-target="#modalRegistrarCiudad">
                <i class="fas fa-plus"></i> Agregar Ciudad
              </button>
            </div>
          </div>

          <!-- Selector 25/50 -->
          <div class="d-flex flex-wrap justify-content-end align-items-center mb-2 gap-2">
            <label for="perPageSelect" class="mb-0 small text-muted">Mostrar</label>
            <select id="perPageSelect" class="form-control form-control-sm" style="width:auto">
              <option value="25" selected>25</option>
              <option value="50">50</option>
            </select>
            <span class="small text-muted">por página</span>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-primary text-center">
                <tr>
                  <th>Ciudad</th>
                  <th>Estado al que Pertenece</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaCiudades"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <nav aria-label="Paginación de ciudades" class="mt-3">
            <ul class="pagination justify-content-end" id="paginacion"></ul>
          </nav>
        </div> <!-- /card-body -->
      </div> <!-- /card -->
    </div> <!-- /col-12 -->
  </div> <!-- /row -->
</div> <!-- /container -->


<!-- Modal Crear/Editar -->
<div class="modal fade" id="modalRegistrarCiudad" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalRegistrarCiudadLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarCiudadLabel">
          <i data-feather="map" class="me-2"></i> Registrar Ciudad
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formCiudad" method="POST" action="#">
          <input type="hidden" name="id_ciudad" id="id_ciudad" value="">

          <div class="mb-3">
            <label for="nombre_ciudad" class="form-label">Nombre de la Ciudad</label>
            <input type="text" name="nombre_ciudad" id="nombre_ciudad" class="form-control"
                   placeholder="Ej. Guadalajara" required>
          </div>

          <div class="mb-3">
            <label for="estado_id" class="form-label">Estado</label>
            <select name="estado_id" id="estado_id" class="form-control" required>
              <option value="">Seleccione un estado</option>
              <?php foreach ($data['estados'] as $estado): ?>
                <option value="<?= $estado['id_estado'] ?>"><?= $estado['nombre_estado'] ?></option>
              <?php endforeach; ?>
            </select>
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

<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/ciudades.js"></script>
