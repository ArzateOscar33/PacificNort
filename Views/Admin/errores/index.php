<?php include 'Views/Template/admin_header.php'; ?>

<div class="container mt-5">
    <h3 class="text-center mb-4">Crear Reporte de Error</h3>

    <form id="frmErroresUsuario" method="POST" action="<?php echo BASE_URL; ?>ErroresUsuario/registrar">

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Datos del Error</h5>
            </div>

            <div class="card-body">

                <!-- Tipo de error -->
                <div class="mb-3">
                    <label for="tipo_error_id" class="form-label">Tipo de Error</label>
                    <select class="form-control" id="tipo_error_id" name="tipo_error_id" required>
                        <option value="">-- Tipo de Error --</option>
                        <?php foreach ($data['tipos_error'] as $tipo) { ?>
                            <option value="<?php echo $tipo['id_tipo_error']; ?>">
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Módulo -->
                <div class="mb-3">
                    <label for="modulo_id" class="form-label">Módulo del Error</label>
                    <select class="form-control" name="modulo_id" id="modulo_id" required>
                        <option value="">-- Seleccione Módulo --</option>
                        <?php foreach ($data['modulos_error'] as $modulo) { ?>
                            <option value="<?php echo $modulo['id_modulo_error']; ?>">
                                <?php echo htmlspecialchars($modulo['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción del Error</label>
                    <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                </div>

                <!-- Valor propuesto -->
                <div class="mb-3">
                    <label for="proposed_value" class="form-label">Valor Propuesto</label>
                    <input type="text" class="form-control" name="proposed_value" id="proposed_value">
                </div>

                <!-- Razón del error -->
                <div class="mb-3">
                    <label for="reason" class="form-label">Razón del Error</label>
                    <textarea class="form-control" name="reason" id="reason" rows="3"></textarea>
                </div>

                <!-- Fecha visual -->
                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha de la Solicitud</label>
                    <input type="date" class="form-control" id="fecha" readonly value="<?php echo date('Y-m-d'); ?>">
                </div>

            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Crear Reporte</button>
    </form>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src='<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/errores_usuario.js'></script>