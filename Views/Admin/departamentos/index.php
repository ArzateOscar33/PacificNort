<?php include 'Views/Template/admin_header.php';
?>

<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Departamentos</h3>

                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-end ">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Departamento</button>
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
                                    <td>Departamento 1</td>
                                    <td>1</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Departamento 2</td>
                                    <td>2</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Departamento 3</td>
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

</div>
<?php include 'Views/Template/admin_footer.php'; ?>
<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado del modal -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="staticBackdropLabel">
                    <i data-feather="plus-circle" class="me-2"></i> Agregar Departamento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Cuerpo del modal -->
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="nombreDepartamento" class="form-label">Nombre del Departamento</label>
                        <input type="text" class="form-control" id="nombreDepartamento" placeholder="Ej. Recursos Humanos">
                    </div>
                </form>
            </div>

            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x-circle" class="me-1"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i data-feather="check-circle" class="me-1"></i> Agregar
                </button>
            </div>

        </div>
    </div>
</div>
