<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Asignar Permiso de Operación</h4>
    </div>
    <div class="card-body">
      <form id="formPermisoOperacion" method="POST" action="#">

        <div class="mb-3">
          <label for="usuario_id" class="form-label">Usuario</label>
          <select name="usuario_id" class="form-control" required>
            <option value="">Seleccione usuario</option>
            <!-- Llenar dinámicamente con foreach de tabla usuarios -->
          </select>
        </div>

        <div class="mb-3">
          <label for="tipo_operacion_id" class="form-label">Tipo de Operación</label>
          <select name="tipo_operacion_id" class="form-control" required>
            <option value="">Seleccione tipo de operación</option>
            <!-- Llenar dinámicamente con foreach de tabla tipos_operacion -->
          </select>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="user-check"></i> Asignar Permiso
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
