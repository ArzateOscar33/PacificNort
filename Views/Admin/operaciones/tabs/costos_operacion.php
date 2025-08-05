 
<div class="container mt-4 col-md-12">
  <div class="card shadow">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Costos por Operación</h4>
        <select class="form-select w-auto">
          <option selected>JL-46</option>
          <!-- Puedes cargar más operaciones aquí -->
        </select>
      </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i data-feather="dollar-sign"></i> Costos por Operación</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarCostoOperacion">
            <i data-feather="plus"></i> Añadir Costo
        </button>
    </div>
      <div class="row mb-4">
        <div class="col-md-4 mb-2">
          <div class="bg-primary text-white p-3 rounded shadow-sm">
            <h5 class="mb-0">$ 6,000 <i data-feather="clock"></i></h5>
            <small>Total por Servicios</small>
          </div>
        </div>
        <div class="col-md-4 mb-2">
          <div class="bg-info text-dark p-3 rounded shadow-sm">
            <h5 class="mb-0">$ 1,200</h5>
            <small>Total por Costos Variables</small>
          </div>
        </div>
        <div class="col-md-4 mb-2">
          <div class="bg-success text-white p-3 rounded shadow-sm">
            <h5 class="mb-0">$ 4,800</h5>
            <small>Ganancia Neta</small>
          </div>
        </div>
      </div>

      <h5 class="mb-3">Costos por Operación</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Cliente</th>
              <th>Destino</th>
              <th>Contenedor</th>
              <th>Servicio</th>
              <th>Monto</th>
              <th>Moneda</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>CP Danny</td>
              <td>Lázaro</td>
              <td>CMAU916054</td>
              <td>Revisiones</td>
              <td>$ 500</td>
              <td>USD</td>
            </tr>
            <tr>
              <td>CP Danny</td>
              <td>Lázaro</td>
              <td>CMAU916054</td>
              <td>Transbordo</td>
              <td>$ 1,000</td>
              <td>USD</td>
            </tr>
            <tr>
              <td>CP Danny</td>
              <td>Lázaro</td>
              <td>CMAU916054</td>
              <td>Bodega</td>
              <td>$ 800</td>
              <td>USD</td>
            </tr>
            <tr>
              <td>CP Danny</td>
              <td>Lázaro</td>
              <td>CMAU916054</td>
              <td>Comisiones</td>
              <td>$ 500</td>
              <td>USD</td>
            </tr>
            <tr>
              <td>CP Danny</td>
              <td>Lázaro</td>
              <td>CMAU916054 Transporte</td>
              <td>Gastos Extras</td>
              <td>$ 3,000</td>
              <td>USD</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Agregar Costo por Operación -->
<div class="modal fade" id="modalAgregarCostoOperacion" tabindex="-1" aria-labelledby="modalAgregarCostoOperacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAgregarCostoOperacionLabel">
                    <i data-feather="plus-circle"></i> Añadir Costo a Operación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formAgregarCostoOperacion">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="operacion_id" class="form-label">Operación</label>
                            <select id="operacion_id" name="operacion_id" class="form-control" required>
                                <option value="">Selecciona una operación</option>
                                <option value="JL-46">JL-46</option>
                                <option value="JL-47">JL-47</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cliente" class="form-label">Cliente</label>
                            <input type="text" id="cliente" name="cliente" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tipo_costo" class="form-label">Tipo de Costo</label>
                            <input type="text" id="tipo_costo" name="tipo_costo" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label for="monto" class="form-label">Monto</label>
                            <input type="number" step="0.01" id="monto" name="monto" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label for="moneda" class="form-label">Moneda</label>
                            <select id="moneda" name="moneda" class="form-control" required>
                                <option value="MXN">MXN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i data-feather="x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script> 
