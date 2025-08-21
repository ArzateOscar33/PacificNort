<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary ">
          <h3 class="card-title mt-3 mb-3 text-white">Tipo De Movimiento</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div class="row g-3 mb-3 align-items-end">
            <!-- Buscar -->
            <div class="col-md-3 position-relative">
              <label for="buscarMovimiento" class="form-label">Buscar</label>
              <input type="text" class="form-control" id="buscarMovimiento" name="buscarMovimiento"
                placeholder="Buscar Tipo de Movimiento">
              <!-- Sugerencias dinámicas -->
              <div id="sugerenciasMovimiento" class="list-group position-absolute w-100 z-3" style="z-index:999;"></div>
            </div>

            <!-- Tipo -->
            <div class="col-md-2">
              <label for="tipoMovimiento" class="form-label">Tipo</label>
              <select id="tipoMovimiento" class="form-control" name="tipoMovimiento">
                <option value="">Tipo de Movimiento</option>
                <option value="gasto">Gasto</option>
                <option value="abono">Abono</option>
              </select>
            </div>

            <!-- Moneda -->
            <div class="col-md-2">
              <label for="monedaMovimiento" class="form-label">Moneda</label>
              <select class="form-control" id="monedaMovimiento" name="monedaMovimiento">
                <option value="">Moneda</option>
                <option value="PESOS">Pesos</option>
                <option value="DLLS">Dólares</option>
              </select>
            </div>

            <!-- Categoría -->
            <div class="col-md-3">
              <label for="categoriaMovimiento" class="form-label">Categoría</label>
              <select id="categoriaMovimiento" class="form-control" name="categoriaMovimiento">
                <option value="">Categoría</option>
                <?php foreach (($data['tipos_operacion'] ?? []) as $op): ?>
                  <option value="<?= $op['id_tipo_operacion'] ?>">
                    <?= htmlspecialchars($op['nombre_operacion']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Botón -->
            <div class="col-md-2 text-end">
              <button id="btnAgregarTipoMovimiento" class="btn btn-primary w-100" data-bs-toggle="modal"
                data-bs-target="#modalRegistrarTipoMovimiento">
                <i class="fas fa-plus"></i> Agregar
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
                <th>Categoría</th>
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
            <label for="tipo" class="form-label">Tipo</label>
            <select name="tipo" id="tipo" class="form-control" required>
              <option value="">Seleccione</option>
              <option value="gasto">Gasto</option>
              <option value="abono">Abono</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="nombre_movimiento" class="form-label">Moneda</label>
            <select name="moneda" id="moneda" class="form-control" required>
              <option value="">Moneda</option>
              <option value="PESOS">Pesos</option>
              <option value="DLLS">Dólares</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="tipo_operacion_id" class="form-label">Categoría (Tipo de operación)</label>
            <select name="tipo_operacion_id" id="tipo_operacion_id" class="form-control">
              <option value="">(Opcional) Seleccione</option>
              <?php foreach (($data['tipos_operacion'] ?? []) as $op): ?>
                <option value="<?= $op['id_tipo_operacion'] ?>">
                  <?= htmlspecialchars($op['nombre_operacion']) ?>
                </option>
              <?php endforeach; ?>
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
<script src='<?php echo BASE_URL; ?>assets/js/modulosAdmin/tipos_movimiento.js'></script>