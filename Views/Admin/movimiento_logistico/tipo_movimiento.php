<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary ">
          <h3 class="card-title mt-3 mb-3 text-white">Tipo_movimiento</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div class="d-flex justify-content-between mb-3">
            <div class="col-md-3">
              <input type="text" class="form-control " placeholder="Buscar Tipo_movimiento">
            </div>
            <div class="col-md-3">
              <select class="form-control">
                <option>Tipo de Movimiento</option>
                <option>1</option>
                <option>2</option>
                <option>3</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-control">
                <option>Divisa</option>
                <option>DLLS</option>
                <option>MXN</option>
              </select>
            </div>
            <div class="col-md-3">
            <button href="#" id="btnAgregarTipoMovimiento" class="btn btn-primary" 
                    data-bs-toggle="modal" data-bs-target="#modalRegistrarTipoMovimiento">
              <i class="fas fa-plus"></i> Agregar Tipo de Movimiento
            </button>

            </div>
          </div>

        </div>
        <!-- /.d-flex -->
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-primary text-center">
              <tr>
                <th>Nombre</th>
                <th>Tipo de movimiento</th>
                <th>Moneda</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tablaTiposMovimiento">

            </tbody>
          </table>
        </div>
        <!-- /.table-responsive -->
      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.col -->
</div>
</div>

<div class="modal fade" id="modalRegistrarTipoMovimiento" data-bs-backdrop="static" data-bs-keyboard="false"
  tabindex="-1" aria-labelledby="modalRegistrarTipoMovimientoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- Encabezado -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarTipoMovimientoLabel">
          <i data-feather="truck" class="me-2"></i> Registrar Tipo de Movimiento
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- Cuerpo -->
      <div class="modal-body">
        <form id="formTipoMovimiento" method="POST" action="#">
          <input type="hidden" name="id_movimiento" id="id_movimiento">
          <div class="mb-3">
            <label for="nombre_movimiento" class="form-label">Nombre del Tipo de Movimiento</label>
            <input type="text" name="nombre_movimiento" class="form-control" placeholder="Ej. Carga, Descarga, Traslado"
              required>
          </div>
          <div class="mb-3">
            <label for="nombre_movimiento" class="form-label">Moneda</label>
            <select name="moneda" id="moneda" class="form-control" required>
              <option value="">Seleccione</option>
              <option value="PESOS">Pesos</option>
              <option value="DLLS">Dólares</option>
            </select>
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
<script src='<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/tipos_movimiento.js'></script>