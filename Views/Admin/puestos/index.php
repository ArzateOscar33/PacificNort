<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Puestos</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <div class="position-relative col-md-10">
                            <input type="text" class="form-control" placeholder="Buscar Puesto" id="buscarPuesto"
                                autocomplete="off">
                            <div id="sugerenciasPuestos" class="list-group position-absolute w-100"
                                style="z-index: 1000;"></div>
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarPuesto" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarPuesto"><i class="fas fa-plus"></i> Agregar
                                Puesto</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover " id="tablaPuestos">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
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

<div class="modal fade" id="modalRegistrarPuesto" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarPuestoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarPuestoLabel">
                    <i data-feather="briefcase" class="me-2"></i> Registrar Puesto
                </h5>

            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formPuesto" method="POST" action="#">

                    <div class="mb-3">
                        <div id="contenedorDepartamento" class="mb-3">
                            <label for="nombreDepartamento" class="form-label">Nombre del Departamento</label>
                            <select name="nombreDepartamento" id="nombreDepartamento" class="form-control" required>
                                <option value="">Seleccione un Departamento</option>
                            </select>
                        </div>
                        <input type="hidden" id="idPuesto" name="idPuesto" value="">
                        <label for="nombre" class="form-label">Nombre del Puesto</label>
                        <input type="text" id="nombrePuesto" name="nombrePuesto" class="form-control" required
                            placeholder="Ej. Supervisor, Cliente, etc.">

                    </div>

                    <!-- Pie del modal -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src='<?php echo BASE_URL; ?>assets/js/modulosAdmin/puestos.js'></script>