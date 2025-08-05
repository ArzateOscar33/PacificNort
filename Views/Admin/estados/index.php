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
              <input type="text" class="form-control" placeholder="Buscar estado...">
            </div>
            <div class="d-flex justify-content-end col-md-2">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistrarEstado">
                <i class="fas fa-plus"></i> Agregar Estado
              </button>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-primary text-center">
                <tr>
                  <th>#</th>
                  <th>Nombre</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <!-- Datos de ejemplo (reemplazar con foreach PHP) -->
                <tr>
                  <td>1</td>
                  <td>Baja California</td>
                  <td class="text-center">
                    <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                    <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Eliminar</a>
                  </td>
                </tr>
                <tr>
                  <td>2</td>
                  <td>Jalisco</td>
                  <td class="text-center">
                    <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                    <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Eliminar</a>
                  </td>
                </tr>
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

          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Estado</label>
            <input type="text" name="nombre" class="form-control" placeholder="Ej. Baja California" required>
          </div>

          <div class="modal-footer px-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
              <i data-feather="check-circle" class="me-1"></i> Agregar
            </button>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
