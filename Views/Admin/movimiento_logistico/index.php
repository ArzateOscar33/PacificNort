<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary ">
          <h3 class="card-title mt-3 mb-3 text-white">Tipo De Costo</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div class="row g-3 mb-3 align-items-end">
            <!-- Buscar -->
            <div class="col-md-2 position-relative">
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
                <option value="">Tipo de Costo</option>
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
            <div class="col-md-2">
              <label for="categoriaMovimiento" class="form-label">Categoría</label>
              <select class="form-control" id="categoriaMovimientoFiltro" name="categoria_id">
                <option value="">Categoría</option>
                <?php if (!empty($data['categorias'])): ?>
                  <?php foreach ($data['categorias'] as $c): ?>
                    <option value="<?= (int)$c['id_categoria']; ?>">
                      <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>


            <!-- Botón Agregar Categoria-->
            <div class="col-md-2 text-end">
              <button id="btnAgregarCategoria" class="btn btn-success w-100" data-bs-toggle="modal"
                data-bs-target="#modalRegistrarCategoria">
                <i class="fas fa-plus"></i> Agregar Categoria
              </button>
            </div>
            <!-- Botón Agregar Costo-->
            <div class="col-md-2 text-end">
              <button id="btnAgregarTipoMovimiento" class="btn btn-primary w-100" data-bs-toggle="modal"
                data-bs-target="#modalRegistrarTipoMovimiento">
                <i class="fas fa-plus"></i> Agregar Tipo de Costo
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
                <th>Categoría</th>
                <th>Tipo de Costo</th>
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
          <i data-feather="truck" class="me-2"></i> Registrar Tipo de Costo
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- Cuerpo -->
      <div class="modal-body">
        <form id="formTipoMovimiento" method="POST" action="#">
          <input type="hidden" name="id_movimiento" id="id_movimiento">
          <div class="mb-3">
            <label for="nombre_movimiento" class="form-label">Nombre del Tipo de Costo</label>
            <input type="text" name="nombre_movimiento" class="form-control" placeholder="Ej. Carga, Descarga, Traslado"
              required>
          </div>
          <div class="mb-3">
            <label for="categoria" class="form-label">Categoría</label>
            <select id="categoriaMovimiento" name="categoria_id" class="form-control">

              <option value="">Seleccione</option>
              <?php if (!empty($data['categorias'])): ?>
                <?php foreach ($data['categorias'] as $c): ?>
                  <option value="<?= (int)$c['id_categoria']; ?>">
                    <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
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


<!-- MODAL: Agregar Categoria -->
<div class="modal fade" id="modalRegistrarCategoria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- Encabezado -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarCategoriaLabel">
          <i data-feather="truck" class="me-2"></i> Registrar Categoria
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- Cuerpo -->
      <div class="modal-body">
        <form id="formCategoria" method="POST" class="row g-3">

          <div class="col-md-12">
            <label for="nombre_categoria" class="form-label">Nombre</label>
            <input type="text" name="nombre_categoria" id="nombre_categoria" class="form-control" placeholder="Ej. Broker"
              required>
          </div>



          <!-- Pie del modal -->
          <div class="modal-footer px-0 mt-3 d-flex  col-md-12 justify-content-end">
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