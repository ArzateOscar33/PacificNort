<!-- costos_logisticos.php -->
<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4 col-md-12">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Costos Logísticos por Operación</h4>
        </div>
        <div class="card-body">
            <form method="post" action="#">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Operación</label>
                        <select name="operacion_id" class="form-control" required>
                            <option value="">Seleccione operación</option>
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Moneda</label>
                        <select name="moneda" class="form-control">
                            <option value="PESOS">PESOS</option>
                            <option value="DLLS">DLLS</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Transbordo</label>
                        <input type="number" step="0.01" name="transbordo" class="form-control" placeholder="$0.00">
                    </div>
                    <div class="col-md-4">
                        <label>Flete Local</label>
                        <input type="number" step="0.01" name="flete_local" class="form-control" placeholder="$0.00">
                    </div>
                    <div class="col-md-4">
                        <label>Flete Ferroviario</label>
                        <input type="number" step="0.01" name="flete_ferro" class="form-control" placeholder="$0.00">
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success"><i data-feather="save"></i> Guardar Costos</button>
                </div>

            </form>
        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
