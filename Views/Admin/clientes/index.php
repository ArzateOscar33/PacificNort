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
                        <div class="col-md-10">
                            <input type="text" class="form-control " placeholder="Buscar Cliente">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Cliente</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>RFC</th>
                                    <th>Telefono</th>
                                    <th>Correo</th>
                                    <th>Direccion</th>
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

<div class="container mt-4 col-md-12">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Registrar Nuevo Cliente</h4>
        </div>
        <div class="card-body">
            <form action="#" method="POST" id="formNuevoCliente">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre">Nombre del Cliente</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label for="rfc">RFC</label>
                        <input type="text" class="form-control" name="rfc">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" class="form-control" name="correo">
                    </div>
                    <div class="col-md-6">
                        <label for="telefono">Teléfono</label>
                        <input type="text" class="form-control" name="telefono">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="direccion">Dirección</label>
                    <textarea class="form-control" name="direccion" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label for="estatus">Estatus</label>
                    <select name="estatus" class="form-control">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i data-feather="user-plus"></i> Registrar Cliente
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>