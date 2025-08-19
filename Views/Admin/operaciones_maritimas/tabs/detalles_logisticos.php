 
<div class="container mt-4 col-md-12">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i data-feather="file-text" class="me-2"></i> Detalles Logísticos</h5>
    </div>
    <div class="card-body">
      <form id="formDetallesLogisticos" method="POST" action="#">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="operacion_id" class="form-label">Operación</label>
            <select class="form-control" name="operacion_id" required>
              <option value="">Selecciona una operación</option>
              <!-- Llenar dinámicamente con las operaciones -->
              <option value="1">JL-46</option>
              <option value="2">JL-47</option>
            </select>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label for="arribo_sd" class="form-label">Arribo SD</label>
            <input type="date" class="form-control" name="arribo_sd" required>
          </div>
          <div class="col-md-3">
            <label for="fecha_cargado" class="form-label">Fecha Cargado</label>
            <input type="date" class="form-control" name="fecha_cargado">
          </div>
          <div class="col-md-3">
            <label for="fecha_cruce" class="form-label">Fecha Cruce</label>
            <input type="date" class="form-control" name="fecha_cruce">
          </div>
          <div class="col-md-3">
            <label for="fecha_entrega" class="form-label">Fecha Entrega</label>
            <input type="date" class="form-control" name="fecha_entrega">
          </div>
          <div class="col-md-3">
            <label for="bultos" class="form-label">Bultos</label>
            <input type="number" class="form-control" name="bultos">
          </div>
 
          <div class="col-md-3">
            <label for="brecha" class="form-label">Brecha</label>
            <input type="number" class="form-control" step="0.01" name="brecha">
          </div>
          <div class="col-md-6">
            <label for="bodega_id" class="form-label">Bodega</label>
            <select class="form-control" name="bodega_id">
              <option value="">Selecciona una bodega</option>
              <!-- Llenar desde DB -->
            </select>
          </div>
          <div class="col-md-6">
            <label for="broker_id" class="form-label">Broker</label>
            <select class="form-control" name="broker_id">
              <option value="">Selecciona un broker</option>
              <!-- Llenar desde DB -->
            </select>
          </div>
          <div class="col-12">
            <label for="comentarios" class="form-label">Comentarios</label>
            <textarea class="form-control" name="comentarios" rows="3"></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary">
              <i data-feather="save"></i> Guardar Detalles
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="mt-4">
  <h5>Registros Guardados</h5>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Operación</th>
          <th>Arribo SD</th>
          <th>Cargado</th>
          <th>Cruce</th>
          <th>Entrega</th>
          <th>Bultos</th> 
          <th>Brecha</th>
          <th>Bodega</th>
          <th>Broker</th>
          <th>Comentarios</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <!-- Reemplazar con foreach PHP o JS dinámico -->
        <tr>
          <td>JL-46</td>
          <td>2025-08-05</td>
          <td>2025-08-06</td>
          <td>2025-08-07</td>
          <td>2025-08-08</td>
          <td>22</td> 
          <td>50</td>
          <td>PT-02</td>
          <td>BKR-09</td>
          <td>Urgente</td>
          <td>
            <button class="btn btn-sm btn-outline-secondary"><i data-feather="edit"></i></button>
            <button class="btn btn-sm btn-outline-danger"><i data-feather="trash-2"></i></button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  feather.replace();
</script> 
