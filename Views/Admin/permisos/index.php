<?php include 'Views/Template/admin_header.php'; ?>
<div class="contain col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title mt-3 mb-3 text-white">Permisos de Operación</h3>
                </div>

                <div class="card-body">

                    <!-- Buscador y botón -->
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control" id="buscarPermiso" placeholder="Buscar permiso por usuario, tipo de operación...">
                            <div id="sugerenciasPermisos" class="list-group position-absolute w-100 z-3" style="z-index:999;"></div>
                        </div>
                        <div class="d-flex justify-content-end col-md-2">
                            <button id="btnAgregarPermiso" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAsignarPermiso">
                                <i class="fas fa-plus"></i> Asignar Permiso
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de permisos -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>#</th>
                                    <th>Usuario</th>
                                    <th>Tipo de Operación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaPermisos">
                                <!-- Datos de ejemplo (reemplazar con PHP dinámico) -->
                                
                            </tbody>
                        </table>
                    </div>

                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div>
    </div>
</div>

<!-- Modal para Asignar Permiso -->
<div class="modal fade" id="modalAsignarPermiso" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalAsignarPermisoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAsignarPermisoLabel">
                    <i data-feather="user-plus" class="me-2"></i> Asignar Permiso de Operación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <form id="formPermisoOperacion" method="POST" action="#">
                    <div class="mb-3">
                        <input type="hidden" name="id" id="id" value="">
                        <label for="usuario_id" class="form-label">Usuario</label>
                        <select name="usuario_id" class="form-control" required>
                            <option value="">Seleccione usuario</option>
                            <!-- Aquí va el foreach de usuarios -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_operacion_id" class="form-label">Tipo de Operación</label>
                        <select name="tipo_operacion_id" class="form-control" required>
                            <option value="">Seleccione tipo de operación</option>
                            <!-- Aquí va el foreach de tipos de operación -->
                        </select>
                    </div>

                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i data-feather="check-circle" class="me-1"></i> Asignar
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src='<?php echo BASE_URL; ?>assets/js/modulosAdmin/permisos.js'></script>
