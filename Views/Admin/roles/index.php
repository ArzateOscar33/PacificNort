<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Roles</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                    <div class="col-md-10 position-relative">
                        <input type="text" class="form-control" id="buscarRol" name="buscarRol" placeholder="Buscar Rol">
                        <div id="sugerenciasRoles" class="list-group position-absolute w-100" style="z-index: 1050;"></div>
                    </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarRol" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalRegistrarRol"><i class="fas fa-plus"></i> Agregar Rol</button>
                            </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="" >
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRoles" class="text-center">
                                <!-- Aquí se llenarán los roles -->
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
 
<div class="modal fade" id="modalRegistrarRol" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarRolLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarRolLabel">
                    <i data-feather="shield" class="me-2"></i> Registrar Rol
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formRol" method="POST" action="#">
                    <input type="hidden" name="id" id="id" value="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Rol</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required placeholder="Ej. Administrador, Cliente, etc.">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion"  id="descripcion" class="form-control" required placeholder="Ej. Administrador de Clientes, etc."></textarea>
                    </div>

                    <!-- Pie del modal -->
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button id="btnSubmit" type="submit" class="btn btn-primary">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>



<?php include 'Views/Template/admin_footer.php'; ?>

<script src='<?php echo BASE_URL; ?>assets/js/modulosAdmin/roles.js'></script>