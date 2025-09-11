<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Clientes</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10 position-relative" >
                            <input type="text" id="buscarCliente" class="form-control" placeholder="Buscar Cliente (nombre, RFC, correo, teléfono, dirección)">
                        <div id="sugerenciasCliente" class="list-group position-absolute w-100" style="z-index: 1050; display:none;"></div>
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarCliente" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarCliente"><i class="fas fa-plus"></i> Agregar Cliente</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
                                     
                                    <th>Telefono</th>
                                    <th>Correo</th> 
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaClientes" class="text-center">
                                
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
<div class="modal fade" id="modalRegistrarCliente" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            
            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarClienteLabel">
                    <i data-feather="user" class="me-2"></i> Registrar Nuevo Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form action="#" method="POST" id="formClientes">
                <input type="hidden" name="id_cliente" id="id_cliente" value="">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="nombre" class="form-label">Nombre del Cliente</label>
                            <input type="text" class="form-control" name="nombre" id="nombre" required>
                        </div> 
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo" id="correo">
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="telefono">
                        </div>
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
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/clientes.js"></script>