<?php include 'Views/Template/admin_header.php'; ?>
<div class="contain col-md-12 mt-3">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Estados</h3>
        </div>

        <div class="card-body">

          <!-- Buscador y botón -->
          <div class="d-flex justify-content-between mb-3">
            <div class="col-md-10">
              <input type="text" class="form-control" id="buscarEstado" name="buscarEstado" placeholder="Buscar estado...">
              <!-- Sugerencias dinámicas -->
              <div id="sugerenciasEstado" class="list-group position-absolute w-100 z-3" style="z-index:999;"></div>
            </div>
            <div class="d-flex justify-content-end col-md-2">
              <button class="btn btn-primary" id="btnAgregarEstado" data-bs-toggle="modal" data-bs-target="#modalRegistrarEstado">
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
              <tbody id="tablaEstados">
                <!-- Datos de ejemplo (reemplazar con foreach PHP) -->
 
              </tbody>
            </table>
          </div>

        </div> <!-- /.card-body -->
      </div> <!-- /.card -->
    </div>
  </div>
</div>

<!-- Modal: Registrar Estado -->
<div class="modal fade" id="modalRegistrarEstado" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="modalRegistrarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarEstadoLabel">
          <i data-feather="map" class="me-2"></i> Registrar Estado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formEstado" method="POST" action="#">
          <input type="hidden" name="id_estado" id="id_estado" value="" >
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Estado</label>
            <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej. Baja California" required>
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
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/estados.js"></script>