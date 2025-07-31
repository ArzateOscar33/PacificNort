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

<?php include 'Views/Template/admin_footer.php'; ?>