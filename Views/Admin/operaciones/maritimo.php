<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Operaciones</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                        <div class="col-md-10">
                        <input type="text" class="form-control " placeholder="Buscar Operacion">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Operacion</button>
                            </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    
                                <th>Numero de Operacion</th>
                                <th>Etd</th>
                                <th>Eta</th>
                                <th>Numero de BL</th>
                                <th>Isfi</th>
                                <th>Shipper</th>
                                <th>Estado de Operacion</th>
                                <th>Puerto de Arribo</th>
                                <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                 
                            </tbody>
                        </table>
                    </div>
                    <!-- /.table-responsive -->
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div> 


<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Agregar Operación Marítima</h3>
                </div>
                <div class="card-body">
                    <form id="formOperacionMaritima" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="numero_operacion">Número de Operación</label>
                                <input type="text" name="numero_operacion" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="etd">ETD</label>
                                <input type="date" name="etd" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="eta">ETA</label>
                                <input type="date" name="eta" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="numero_bl">Número de BL</label>
                                <input type="text" name="numero_bl" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="isf">ISF</label>
                                <input type="text" name="isf" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="shipper_id">Shipper</label>
                                <select name="shipper_id" class="form-control" required>
                                    <option value="">Seleccione un Shipper</option>
                                    <!-- Opciones de shipper -->
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="estado_operacion">Estado de la Operación</label>
                                <input type="text" name="estado_operacion" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="puerto_arribo_id">Puerto de Arribo</label>
                                <select name="puerto_arribo_id" class="form-control" required>
                                    <option value="">Seleccione un Puerto</option>
                                    <!-- Opciones de puerto -->
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">Guardar Operación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
 


<?php include 'Views/Template/admin_footer.php'; ?>