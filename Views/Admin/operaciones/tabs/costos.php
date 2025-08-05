
<div class="container py-4 col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Costos por Contenedor</h4>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarCosto">
            <i data-feather="plus"></i> Añadir Costo
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <label for="buscar" class="form-label">Buscar por cliente o costo</label>
            <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
        </div>
        <div class="col-md-4">
            <label for="filtro_moneda" class="form-label">Tipo de Moneda</label>
            <select id="filtro_moneda" class="form-control">
                <option value="">Todas</option>
                <option value="Pesos">Pesos</option>
                <option value="Dólares">Dólares</option>
                <option value="Euros">Euros</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="filtro_tipo" class="form-label">Tipo de Costo</label>
            <select id="filtro_tipo" class="form-control">
                <option value="">Todos</option>
                <option value="Transbordo">Transbordo</option>
                <option value="Flete">Flete</option>
                <option value="Ganancia">Ganancia</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Contenedor</th>
                    <th>Tipo de Costo</th>
                    <th>Monto</th>
                    <th>Moneda</th>
                    <th>Comentarios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>CMAU9196054</td>
                    <td>Flete</td>
                    <td>$1,200</td>
                    <td>Dólares</td>
                    <td>Flete principal internacional</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i data-feather="edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i data-feather="x"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>WHUS6796036</td>
                    <td>Transbordo</td>
                    <td>$850</td>
                    <td>Pesos</td>
                    <td>Maniobra en patio logístico</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i data-feather="edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i data-feather="x"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Agregar Costo -->
<div class="modal fade" id="modalAgregarCosto" tabindex="-1" aria-labelledby="modalAgregarCostoLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalAgregarCostoLabel">
          <i data-feather="plus-circle" class="me-1"></i> Añadir Costo al Contenedor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formAgregarCosto">
        <div class="modal-body">
          <div class="mb-3">
            <label for="contenedor_id" class="form-label">Contenedor</label>
            <select id="contenedor_id" name="contenedor_id" class="form-control" required>
              <option value="">Seleccione un contenedor</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="tipo_costo" class="form-label">Tipo de Costo</label>
            <select id="tipo_costo" name="tipo_costo" class="form-control" required>
              <option value="">Seleccione tipo</option>
              <option value="Transbordo">Transbordo</option>
              <option value="Flete">Flete</option>
              <option value="Ganancia">Ganancia</option>
              <option value="Otro">Otro</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="monto" class="form-label">Monto</label>
            <input type="number" id="monto" name="monto" class="form-control" required placeholder="Ej: 500">
          </div>

          <div class="mb-3">
            <label for="moneda" class="form-label">Moneda</label>
            <select id="moneda" name="moneda" class="form-control" required>
              <option value="">Seleccione</option>
              <option value="Pesos">Pesos</option>
              <option value="Dólares">Dólares</option>
              <option value="Euros">Euros</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="comentarios" class="form-label">Comentarios (opcional)</label>
            <textarea id="comentarios" name="comentarios" rows="2" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i data-feather="x"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-success">
            <i data-feather="save"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>feather.replace();</script>
