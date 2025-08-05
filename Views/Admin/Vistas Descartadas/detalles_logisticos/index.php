<!-- detalle_logistico.php -->
<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4 col-md-12">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Detalle Logístico de la Operación</h4>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo BASE_URL . 'operaciones/guardarDetalleLogistico'; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Operación</label>
                        <select name="operacion_id" class="form-control" required>
                            <option value="">Seleccione una operación</option>
                            <!-- Aquí van las opciones dinámicas desde el controlador -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Bodega</label>
                        <select name="bodega_id" class="form-control">
                            <option value="">Seleccione una bodega</option>
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Broker</label>
                        <select name="broker_id" class="form-control">
                            <option value="">Seleccione un broker</option>
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Fecha de arribo a SD</label>
                        <input type="date" name="arribo_sd" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Fecha de cargado</label>
                        <input type="date" name="fecha_cargado" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>Fecha de cruce</label>
                        <input type="date" name="fecha_cruce" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Fecha de entrega</label>
                        <input type="date" name="fecha_entrega" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Bultos</label>
                        <input type="number" name="bultos" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Peso (kg)</label>
                        <input type="number" step="0.01" name="peso" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>VGM (kg)</label>
                        <input type="number" step="0.01" name="vgm" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Brecha</label>
                        <input type="number" step="0.01" name="brecha" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Comentarios</label>
                        <textarea name="comentarios" class="form-control"></textarea>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success"><i data-feather="save"></i> Guardar Detalle</button>
                </div>

            </form>
        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
