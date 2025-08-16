 

<div class="container py-4 col-md-12">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i data-feather="plus-circle" class="me-1"></i> Crear Nueva Operación</h5>
        </div>
        <div class="card-body">
            <form id="formCrearOperacion" action="#" method="post">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="numero_operacion" class="form-label">Contenedor maritimo</label> 
                        <select class="form-control" id="cliente" name="cliente" required>
                            <option value="">Selecciona un Contenedor maritimo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_operacion" class="form-label">ETD</label>
                        <input type="date" class="form-control" id="fecha_operacion" name="fecha_operacion" required>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_operacion" class="form-label">ETA</label>
                        <input type="date" class="form-control" id="fecha_operacion" name="fecha_operacion" required>
                    </div>
                    <div class="col-md-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <select class="form-control" id="cliente" name="cliente" required>
                            <option value="">Selecciona un cliente</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="fecha_operacion" class="form-label">BL</label>
                        <input type="text" class="form-control" id="fecha_operacion" name="fecha_operacion" required>
                    </div>
                    <div class="col-md-3">
                        <label for="estatus" class="form-label">Estatus</label>
                        <select class="form-control" id="estatus" name="estatus" required>
                            <option value="">Selecciona estatus</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_operacion" class="form-label">ISF</label>
                        <select name="isf" id="isf" class="form-control" required>
                            <option value="">Si</option>
                             <option value="">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_operacion" class="form-label">Shipper</label>
                        <input type="text" class="form-control" id="" name="fecha_operacion" >
                    </div>
                </div>

                <div class="row mb-3">                   
                     <div class="col-md-6">
                        <label for="fecha_operacion" class="form-label">Fecha Arribo</label>
                        <input type="date" class="form-control" id="" name="" required>
                    </div>

                    <div class="col-md-6">
                        <label for="comentarios" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><i data-feather="save"></i> Guardar Operación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>
 
