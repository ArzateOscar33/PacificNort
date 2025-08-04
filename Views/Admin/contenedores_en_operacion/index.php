<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Asignar Contenedor Físico a Operación</h4>
    </div>
    <div class="card-body">
      <form id="formContenedorOperacion" method="POST" action="#">

        <div class="mb-3">
          <label for="id_fisico" class="form-label">Contenedor Físico</label>
          <select name="id_fisico" class="form-control" required>
            <option value="">Seleccione un contenedor físico</option>
            <!-- foreach PHP -->
          </select>
        </div>

        <div class="mb-3">
          <label for="operacion_id" class="form-label">Operación</label>
          <select name="operacion_id" class="form-control" required>
            <option value="">Seleccione una operación</option>
            <!-- foreach PHP -->
          </select>
        </div>

        <div class="mb-3">
          <label for="cliente_id" class="form-label">Cliente</label>
          <select name="cliente_id" class="form-control" required>
            <option value="">Seleccione un cliente</option>
            <!-- foreach PHP -->
          </select>
        </div>

        <div class="mb-3">
          <label for="peso" class="form-label">Peso (kg)</label>
          <input type="number" step="0.01" name="peso" class="form-control" placeholder="Ej. 1500.50" required>
        </div>

        <div class="mb-3">
          <label for="bultos" class="form-label">Número de Bultos</label>
          <input type="number" name="bultos" class="form-control" placeholder="Ej. 10" required>
        </div>

        <div class="mb-3">
          <label for="comentarios" class="form-label">Comentarios</label>
          <textarea name="comentarios" class="form-control" rows="3" placeholder="Opcional..."></textarea>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="save"></i> Guardar Asignación
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
