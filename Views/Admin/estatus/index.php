<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Estatus</h3>
        </div>

        <div class="card-body">
          <!-- Buscador + botón -->
          <div class="row g-2 align-items-start mb-3">
            <div class="col-12 col-md-9 position-relative">
              <input type="text" class="form-control" id="buscarEstatus" name="buscarEstatus"
                placeholder="Buscar Estatus" autocomplete="off">
              <div id="sugerenciasEstatus" class="list-group position-absolute w-100" style="z-index:999;"></div>
            </div>

            <div class="col-12 col-md-3 text-md-end">
              <button id="btnAgregarEstatus" class="btn btn-primary w-100 w-md-auto"
                data-bs-toggle="modal" data-bs-target="#modalRegistrarEstatus">
                <i class="fas fa-plus"></i> Agregar Estatus
              </button>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-primary text-center">
                <tr>
                  <th>Nombre</th>
                  <th>Color</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaEstatus" class="text-center">
                <!-- Aquí se cargarán los datos de los estatus -->
              </tbody>
            </table>
          </div>
        </div> <!-- /card-body -->
      </div> <!-- /card -->
    </div> <!-- /col-12 -->
  </div> <!-- /row -->
</div> <!-- /container -->




<div class="modal fade" id="modalRegistrarEstatus" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="modalRegistrarEstatusLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- Encabezado -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarEstatusLabel">
          <i data-feather="activity" class="me-2"></i> Registrar Estatus
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- Cuerpo -->
      <div class="modal-body">
        <form id="formAgregarEstatus" method="POST" action="#">
          <input type="hidden" name="id_estatus" id="id_estatus">
          <div class="mb-3">
            <label for="descripcion" class="form-label">Nombre del estatus</label>
            <input type="text" name="nombre" id="nombre" class="form-control" required placeholder="Ej. En proceso, Finalizado, En revisión">
          </div>
          <div class="mb-3">
            <label for="color_hex" class="form-label">Color del estatus</label>
            <div class="input-group">
              <input type="color" name="color_hex" id="color_hex" class="form-control form-control-color" value="#807A79">
              <input type="text" id="color_hex_text" class="form-control" value="#807A79" maxlength="7">
            </div>
          </div>

          <!-- Pie del modal -->
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
<script src='<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/estatus.js'></script>