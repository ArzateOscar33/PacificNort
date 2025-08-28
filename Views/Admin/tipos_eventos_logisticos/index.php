<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Tipos de Eventos Logísticos</h3>
        </div>

        <div class="card-body">
          <!-- Fila de buscador + botón -->
          <div class="row g-2 align-items-start mb-3">
            <div class="col-12 col-md-9 position-relative">
              <input type="text" class="form-control" id="buscarTipoEvento" name="buscarTipoEvento"
                placeholder="Buscar Tipo de Evento Logístico" autocomplete="off">
              <!-- Sugerencias dinámicas -->
              <div id="sugerenciasTipoEvento" class="list-group position-absolute w-100" style="z-index:999;"></div>
            </div>

            <div class="col-12 col-md-3 text-md-end">
              <button id="btnAgregarTipoEvento" class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal"
                data-bs-target="#modalRegistrarTipoEvento">
                <i class="fas fa-plus"></i> Agregar Tipo de Evento
              </button>
            </div>
          </div>

          <!-- Tabla dentro del card-body -->
          <div class="table-responsive">
           <table class="table table-hover">
  <thead class="table-primary text-center">
    <tr>
      <th>Nombre</th>
      <th>Operación</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody id="tablaTiposEventos"></tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="modalRegistrarTipoEvento" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="modalRegistrarTipoEventoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarTipoEventoLabel">
          <i data-feather="calendar" class="me-2"></i> Registrar Tipo de Evento Logístico
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formTipoEvento" method="POST" action="#">
        <div class="modal-body">
          <input type="hidden" name="id_tipo_evento" id="id_tipo_evento" value="">

          <div class="mb-3">
            <label for="nombre_evento" class="form-label">Nombre del Evento</label>
            <input type="text" id="nombre_evento" name="nombre" class="form-control" required
                   placeholder="Ej. Arribo, Salida, Revisión">
          </div>

<div class="mb-3">
  <label for="tipo_operacion_id" class="form-label">Tipo de Operación</label>
  <select id="tipo_operacion_id" name="tipo_operacion_id" class="form-control">
    <option value="">Selecciona...</option>
    <?php if (!empty($data['tipos_operacion'])): ?>
      <?php foreach ($data['tipos_operacion'] as $op): ?>
        <option value="<?= $op['id_tipo_operacion'] ?>">
          <?= htmlspecialchars($op['nombre_operacion']) ?>
        </option>
      <?php endforeach; ?>
    <?php endif; ?>
  </select>
  <div class="form-text">Déjalo vacío si el evento aplica a cualquier tipo de operación.</div>
</div>

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

<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/tipos_eventos_logisticos.js"></script>