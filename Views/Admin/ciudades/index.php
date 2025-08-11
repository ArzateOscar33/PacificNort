<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Ciudades</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-6">
                            <input type="text" id="buscarCiudad" class="form-control " placeholder="Buscar Ciudad">
                            <div id="sugerenciasCiudad" class="list-group position-absolute w-100 z-3" style="z-index:999;"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="select" id="estados">
                                <select name="estado_id_filtro" id="estado_id_filtro" class="form-control" required>
                                    <option value="">Seleccione un estado</option>
                                    <?php foreach ($data['estados'] as $estado): ?>
                                        <option value="<?= $estado['id_estado'] ?>"><?= $estado['nombre_estado'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarCiudad" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarCiudad"><i class="fas fa-plus"></i> Agregar Ciudad</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Ciudad</th>
                                    <th>Estado al que Pertenece</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaCiudades">
                                
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

 
<div class="modal fade" id="modalRegistrarCiudad" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarCiudadLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarCiudadLabel">
                    <i data-feather="map" class="me-2"></i> Registrar Ciudad
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formCiudad" method="POST" action="#">
                    <input type="hidden" name="id_ciudad" id="id_ciudad" value="">
                    <div class="mb-3">
                        <label for="nombre_ciudad" class="form-label">Nombre de la Ciudad</label>
                        <input type="text" name="nombre_ciudad" id="nombre_ciudad" class="form-control" placeholder="Ej. Guadalajara" required>
                    </div>

                    <div class="mb-3">
                        <label for="estado_id" class="form-label">Estado</label>
                        <select name="estado_id" id="estado_id" class="form-control" required>
                            <option value="">Seleccione un estado</option>
                            <?php foreach ($data['estados'] as $estado): ?>
                                <option value="<?= $estado['id_estado'] ?>"><?= $estado['nombre_estado'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Footer -->
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

<script src='<?php echo BASE_URL; ?>assets/js/modulosAdmin/ciudades.js'></script>