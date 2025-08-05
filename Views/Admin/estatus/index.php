
<?php include 'Views/Template/admin_header.php';
?>
<div class="contain col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Estatus</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                        <div class="col-md-10">
                        <input type="text" class="form-control " placeholder="Buscar Estatus">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalRegistrarEstatus"><i class="fas fa-plus"></i> Agregar Estatus</button>
                            </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Estatus 1</td>
                                    <td>1</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Estatus 2</td>
                                    <td>2</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Estatus 3</td>
                                    <td>3</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
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


 
<div class="modal fade" id="modalRegistrarEstatus" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarEstatusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarEstatusLabel">
                    <i data-feather="activity" class="me-2"></i> Registrar Estatus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formEstatus" method="POST" action="#">

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" required placeholder="Ej. En proceso, Finalizado, En revisión">
                    </div>

                    <!-- Pie del modal -->
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>



<?php include 'Views/Template/admin_footer.php'; ?>