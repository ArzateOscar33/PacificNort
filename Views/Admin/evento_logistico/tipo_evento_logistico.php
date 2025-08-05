<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Tipo de Evento</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <div class="row  align-items-center mb-3 col-md-12"  >
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Tipo de Evento" id="txtTipoEvento">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-warning">
                                <i class="fas fa-search"></i> Buscar Tipo Evento
                            </button>
                        </div>

                        <div class="col-md-4">
                  <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalRegistrarTipoEvento"><i class="fas fa-plus"></i> Agregar Tipo
                            </div>
                        </div>

                    </div>

                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <th></th>
                                <th></th>
                                <th></th>
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
<div class="modal fade" id="modalRegistrarTipoEvento" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarTipoEventoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarTipoEventoLabel">
                    <i data-feather="calendar" class="me-2"></i> Registrar Tipo de Evento Logístico
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formTipoEvento" method="POST" action="#">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Evento</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej. Arribo, Salida, Revisión">
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