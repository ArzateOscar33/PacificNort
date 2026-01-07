<?php include 'Views/Template/admin_header.php'; ?>
<div class="container col-md-12 mt-3">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Puertos</h3>
        </div>

        <div class="card-body">
          <!-- Barra superior: buscador + filtro ciudad + botón -->
          <div class="row g-2 align-items-center mb-3">
            <!-- Buscador -->
            <div class="col-12 col-lg-6 position-relative">
              <input type="text" id="buscarPuerto" class="form-control" placeholder="Buscar Puerto" autocomplete="off">
              <div id="sugerenciasPuerto"
                   class="list-group position-absolute top-100 start-0 w-100"
                   style="display:none; z-index:999;"></div>
            </div>

            <!-- Filtro por Ciudad -->
            <div class="col-12 col-lg-4">
              <select name="ciudades_filtro" id="ciudades_filtro" class="form-control">
                <option value="">Todas las ciudades</option>
                <?php foreach ($data['ciudades'] as $ciudad): ?>
                  <option value="<?= $ciudad['id_ciudad'] ?>"><?= $ciudad['nombre_ciudad'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Botón Agregar -->
            <div class="col-12 col-lg-2 text-lg-end">
              <button id="btnAgregarPuerto"
                      class="btn btn-primary w-100 w-lg-auto"
                      data-bs-toggle="modal"
                      data-bs-target="#modalRegistrarPuerto">
                <i class="fas fa-plus"></i> Agregar Puerto
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
                  <th>Puerto</th>
                  <th>Ciudad a la que Pertenece</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaPuertos"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <nav aria-label="Paginación de puertos" class="mt-3">
            <ul class="pagination justify-content-end" id="paginacion"></ul>
          </nav>
        </div><!-- /card-body -->
      </div><!-- /card -->
    </div><!-- /col-12 -->
  </div><!-- /row -->
</div><!-- /container -->


<!-- Modal Crear/Editar -->
<div class="modal fade" id="modalRegistrarPuerto" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalRegistrarPuertoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarPuertoLabel">
          <i data-feather="anchor" class="me-2"></i> Registrar Puerto
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formPuerto" method="POST" action="#">
          <input type="hidden" name="id_puerto" id="id_puerto" value="">

          <div class="mb-3">
            <label for="nombre_puerto" class="form-label">Nombre del Puerto</label>
            <input type="text" name="nombre_puerto" id="nombre_puerto" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="ciudad_id" class="form-label">Ciudad</label>
            <select name="ciudad_id" id="ciudad_id" class="form-control" required>
              <option value="">Selecciona una ciudad</option>
              <?php foreach ($data['ciudades'] as $ciudad): ?>
                <option value="<?= $ciudad['id_ciudad'] ?>"><?= $ciudad['nombre_ciudad'] ?></option>
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
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/puertos.js"></script>
