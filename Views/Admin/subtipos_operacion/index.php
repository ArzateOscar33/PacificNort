<?php include 'Views/Template/admin_header.php';
?> 
<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Subtipos de Operaciones</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                    <div class="position-relative col-md-10">
                    <input type="text" class="form-control" id="buscarSubSubtipoOperacion" placeholder="Buscar Subtipo de Operación">
                    <div id="sugerenciasSubtipoOperacion" class="list-group position-absolute w-100" style="z-index: 999; display: none;"></div>
                    </div>

                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarSubtipoOperacion" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalRegistrarSubtipoOperacion"><i class="fas fa-plus"></i> Agregar Subtipo de Operacion</button>
                            </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Tipo de Operacion</th>
                                    <th>Clave</th>
                                    <th>Nombre</th>
                                    <th>Puerto</th> 
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaSubTiposOperacion"> 
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

 
<div class="modal fade" id="modalRegistrarSubtipoOperacion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarSubtipoOperacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarSubtipoOperacionLabel">
                    <i data-feather="repeat" class="me-2"></i> Registrar Tipo de Operación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formSubtipoOperacion" method="POST" action="#">
                    <input type="hidden" id="id" name="id">
                    <div class="mb-3">
                        <label for="nombre_operacion" class="form-label">Tipo de Operacion</label>
                         <select name="tipo_operacion_id" id="tipo_operacion_id" class="form-control" required>
                        <!-- Opciones dinámicas -->
                        <?php foreach (($data['tipos_operacion'] ?? []) as $op): ?>
                        <option value="<?= $op['id_tipo_operacion'] ?>"> <?= htmlspecialchars($op['nombre_operacion']) ?></option>
                        <?php endforeach; ?>

                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_operacion" class="form-label">Clave</label>
                        <input type="text" name="claveSubtipoOperacion"  id="claveSubtipoOperacion" class="form-control" placeholder=" " required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_operacion" class="form-label">Nombre del Subtipo de Operacion</label>
                        <input type="text" name="nombreSubtipoOperacion"  id="nombreSubtipoOperacion" class="form-control" placeholder=" " required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_operacion" class="form-label">Puerto </label>
                         <select name="puerto_id" id="puerto_id" class="form-control" required>
                        <!-- Opciones dinámicas -->
                        <?php foreach (($data['puertos'] ?? []) as $puerto): ?>
                        <option value="<?= $puerto['id_puerto'] ?>"> <?= htmlspecialchars($puerto['nombre']) ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pie del modal -->
                    <div class="modal-footer px-0">
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
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/subtipos_operacion.js"></script>