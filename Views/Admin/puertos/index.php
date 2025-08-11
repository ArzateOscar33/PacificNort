<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Puertos</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-6">
                            <input type="text" id="buscarPuerto"class="form-control " placeholder="Buscar Puerto">
                            <div id="sugerenciasPuerto" class="list-group position-absolute w-100 z-3" style="z-index:999;"></div>
                        </div>
                        <div class="col-md-4"> 
                                <select name="ciudades_filtro" id="ciudades_filtro" class="form-control" required>
                                    <option value="">Seleccione una ciudad</option>
                                    <?php foreach ($data['ciudades'] as $ciudad): ?>
                                        <option value="<?= $ciudad['id_ciudad'] ?>"><?= $ciudad['nombre_ciudad'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                             
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarPuerto" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarPuerto"><i class="fas fa-plus"></i> Agregar Puerto</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Puerto</th> 
                                    <th>Ciudad a la que Pertenece</th> 
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaPuertos"> 
                                
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
<div class="modal fade" id="modalRegistrarPuerto" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarPuertoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarPuertoLabel">
                    <i data-feather="anchor" class="me-2"></i> Registrar Puerto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formPuerto" method="POST" action="#">
                    <input type="hidden" name="id_puerto" id="id_puerto" value="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Puerto</label>
                        <input type="text" name="nombre_puerto" id="nombre_puerto" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="ciudad_id" class="form-label">Ciudad</label>
                        <select name="ciudad_id" id="ciudad_id" class="form-control" required>
                            <option value="">Selecciona una ciudad</option>
                            <?php foreach ($data['ciudades'] as $ciudad): ?>
                                <option value="<?= $ciudad['id_ciudad'] ?>"><?= $ciudad['nombre_ciudad'] ?></option>
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
<script src='<?php echo BASE_URL; ?>assets/js/modulosAdmin/puertos.js'></script>