<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Bodegas</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10">
                            <input type="text" id="buscarBodega" name="bucarBodega" class="form-control " placeholder="Buscar Bodega">
                            <div id="sugerenciasBodega" class="list-group position-absolute w-100" style="z-index:999; display:none;"></div>

                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarBodega" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarBodega"><i class="fas fa-plus"></i> Agregar Bodega</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Direccion</th>
                                    <th>Ciudad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBodegas">
                                
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



 
<div class="modal fade" id="modalRegistrarBodega" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarBodegaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarBodegaLabel">
                    <i data-feather="home" class="me-2"></i> Registrar Bodega
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formBodega" method="POST" action="#">
                    <input type="hidden" name="id" id="id" value= "">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Bodega</label>
                        <input type="text" name="nombre" id="nombre"class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="direccion"class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="ciudad_id" class="form-label">Ciudad</label>
                        <select name="ciudad_id" id="ciudad_id"class="form-control" required>
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
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/bodegas.js"></script>