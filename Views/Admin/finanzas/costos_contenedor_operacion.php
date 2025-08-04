<!-- costos_contenedor.php -->
<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Costos por Contenedor</h4>
        </div>
        <div class="card-body">
            <form method="post" action="#">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Contenedor de operación</label>
                        <select name="contenedor_operacion_id" class="form-control" required>
                            <option value="">Seleccione contenedor</option>
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Tipo de costo</label>
                        <select name="tipo_costo" class="form-control">
                            <option value="transbordo">Transbordo</option>
                            <option value="flete_local">Flete Local</option>
                            <option value="broker">Broker</option>
                            <option value="abrecha">Abrecha</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" placeholder="$0.00">
                    </div>
                    <div class="col-md-4">
                        <label>Moneda</label>
                        <select name="moneda" class="form-control">
                            <option value="PESOS">PESOS</option>
                            <option value="DLLS">DLLS</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Comentario</label>
                        <textarea name="comentario" class="form-control" rows="1" placeholder="Opcional..."></textarea>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success"><i data-feather="save"></i> Guardar Costo</button>
                </div>

            </form>
        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
