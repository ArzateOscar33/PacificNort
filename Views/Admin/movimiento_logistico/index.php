<!-- movimiento_logistico.php -->
<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Registrar Movimiento Logístico</h4>
        </div>
        <div class="card-body">
            <form method="post" action="#">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Contenedor Físico (Ferrocarril)</label>
                        <select name="contenedor_fisico_id" class="form-control" required>
                            <option value="">Seleccione contenedor</option>
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tipo de operación</label>
                        <select name="tipo_operacion_id" class="form-control">
                            <option value="">Tipo</option>
                            <!-- Dinámico -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Estatus</label>
                        <select name="estatus_id" class="form-control">
                            <option value="">Estatus</option>
                            <!-- Dinámico -->
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Ciudad de origen</label>
                        <select name="origen_id" class="form-control">
                            <option value="">Seleccione</option>
                            <!-- Dinámico -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Ciudad destino</label>
                        <select name="destino_id" class="form-control">
                            <option value="">Seleccione</option>
                            <!-- Dinámico -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Puerto</label>
                        <select name="puerto_id" class="form-control">
                            <option value="">Seleccione</option>
                            <!-- Dinámico -->
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Transportista</label>
                        <select name="transportista_id" class="form-control">
                            <option value="">Seleccione</option>
                            <!-- Dinámico -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Comentario de la etapa</label>
                        <textarea name="comentario_etapa" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i data-feather="truck"></i> Registrar Movimiento
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
