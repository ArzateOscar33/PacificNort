<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Contenedores fisicos</h3>
                </div>
                <!-- /.card-header -->
<div class="card-body">
  <!-- Barra superior -->
  <div class="row g-2 align-items-center mb-3">
    <!-- Buscador -->
    <div class="col-12 col-lg-6 position-relative">
      <input type="text" class="form-control" id="buscarContenedorFisico" name="buscarContenedorFisico" placeholder="Buscar Contenedor Físico">
      <div id="sugerenciasContenedorFisico" class="list-group position-absolute w-100" style="z-index:999; display:none;"></div>
    </div>

    <!-- Selector + botón -->
    <div class="col-12 col-lg-6 d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
      <label for="perPageSelect" class="mb-0 small text-muted">Mostrar</label>
      <select id="perPageSelect" class="form-control form-control-sm" style="width:auto">
        <option value="25" selected>25</option>
        <option value="50">50</option>
      </select>
      <span class="small text-muted">por página</span>

      <button id="btnAgregarContenedorFisico" class="btn btn-primary w-100 w-lg-auto ms-lg-2"
              data-bs-toggle="modal" data-bs-target="#modalRegistrarContenedorFisico">
        <i class="fas fa-plus"></i> Agregar Contenedor Físico
      </button>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-hover">
      <thead class="table-primary text-center">
        <tr>
          <th>Número de Ferro / Nombre de Físico</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tablaContenedoresFisicos"></tbody>
    </table>

    <!-- Paginación -->
    <nav aria-label="Paginación de contenedores" class="mt-3">
      <ul class="pagination justify-content-end" id="paginacion"></ul>
    </nav>
  </div>
</div>

                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>

<!-- Views/contenedores_fisicos/crear.php -->  
<div class="modal fade" id="modalRegistrarContenedorFisico" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarContenedorFisicoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarContenedorFisicoLabel">
                    <i data-feather="package" class="me-2"></i> Registrar Contenedor Físico
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form action="#" method="POST" id="formContenedorFisico">

                    <div class="mb-3">
                        <input type="hidden" name="id" id="id" value="">
                        <label for="numero_ferro_fisico"  class="form-label">Número de Ferro / Nombre de Físico</label>
                        <input type="text" class="form-control" id="numero_ferro_fisico" name="numero_ferro_fisico" required>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" id="btnSubmit"class="btn btn-primary">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>



<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/contenedores_fisicos.js"></script>