<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title   mt-3 mb-3 text-white">Contenedores maritimos</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control " placeholder="Buscar Contenedor Marino">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarContenedorMaritimo"><i class="fas fa-plus"></i> Agregar Contenedor Marino</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Numero de Contenedor</th>
                                    <th>Tipo</th>
                                    <th>Naviera</th>
                                    <th>Observaciones</th>
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

  
<div class="modal fade" id="modalRegistrarContenedorMaritimo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarContenedorMaritimoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarContenedorMaritimoLabel">
                    <i data-feather="plus-circle" class="me-2"></i> Registrar Contenedor Marítimo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formContenedorMaritimo" method="POST" action="#">

                    <div class="mb-3">
                        <label for="numero_contenedor" class="form-label">Número de Contenedor</label>
                        <input type="text" class="form-control" name="numero_contenedor" required>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <input type="text" class="form-control" name="tipo" placeholder="Ej: 40HQ, 20STD" required>
                    </div>

                    <div class="mb-3">
                        <label for="naviera" class="form-label">Naviera</label>
                        <select name="naviera" class="form-control" required>
                            <option value="" disabled selected>Seleccione una naviera</option>
                            <?php foreach ($data['navieras'] as $naviera): ?>
                                <option value="<?= $naviera['id_naviera'] ?>"><?= $naviera['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="3"></textarea>
                    </div>

                    <!-- Footer -->
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