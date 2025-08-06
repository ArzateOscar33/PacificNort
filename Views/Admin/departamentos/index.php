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
                <div class="position-relative col-md-10">
                    <input type="text" class="form-control" placeholder="Buscar Departamento" id="buscarDepartamento" autocomplete="off">
                    <div id="sugerencias" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                </div>
                    <button href="#" id="btnAgregarDepartamento" class="btn btn-primary "  data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus" ></i> Agregar Departamento</button>
                    
                        </div>
                    

                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaDepartamentos">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
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

</div>
<?php include 'Views/Template/admin_footer.php'; ?>
<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    role="dialog" aria-modal="true" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="staticBackdropLabel">
                    <i data-feather="plus-circle" class="me-2"></i> Agregar Departamento
                </h5>
            </div>

            <!-- Cuerpo del modal -->
            <div class="modal-body">
                <form id="formDepartamento">
                    <input type="hidden" id="idDepartamento" name="id" value="">
                    <div class="mb-3">
                        <label for=" " class="form-label">Nombre del Departamento</label>
                        <input type="text" class="form-control" id="nombreDepartamento" name="nombreDepartamento"
                            placeholder="Ej. Recursos Humanos">
                    </div>
                    <!-- Pie del modal -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelar">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button aria-label="Agregar Departamento" type="submit" class="btn btn-primary" id="btnSubmit">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script src='<?php echo BASE_URL; ?>/assets/js/modulosAdmin/departamentos.js'></script>
 