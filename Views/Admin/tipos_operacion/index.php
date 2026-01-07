<?php include 'Views/Template/admin_header.php';
?> 
<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Tipos de Operaciones</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                    <div class="position-relative col-md-10">
                    <input type="text" class="form-control" id="buscarTipoOperacion" placeholder="Buscar Tipo de Operación">
                    <div id="sugerenciasTipoOperacion" class="list-group position-absolute w-100" style="z-index: 999; display: none;"></div>
                    </div>

                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarTipoOperacion" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalRegistrarTipoOperacion"><i class="fas fa-plus"></i> Agregar Tipo de Operacion</button>
                            </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th> 
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaTiposOperacion"> 
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

 
<div class="modal fade" id="modalRegistrarTipoOperacion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarTipoOperacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarTipoOperacionLabel">
                    <i data-feather="repeat" class="me-2"></i> Registrar Tipo de Operación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formTipoOperacion" method="POST" action="#">
                    <input type="hidden" id="id" name="id">
                    <div class="mb-3">
                        <label for="nombre_operacion" class="form-label">Nombre del Tipo de Operación</label>
                        <input type="text" name="nombreTipoOperacion"  id="nombreTipoOperacion" class="form-control" placeholder="Ej. Importación, Exportación" required>
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
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/tipos_operacion.js"></script>