<?php include 'Views/Template/admin_header.php';
?>
<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Modal title</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...dsadas
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Understood</button>
            </div>
        </div>
    </div>
</div>

<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Departamentos</h3>

                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-end ">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Departamento</button>
                    </div>
 

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Departamento 1</td>
                                    <td>1</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Departamento 2</td>
                                    <td>2</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Departamento 3</td>
                                    <td>3</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
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

    <div class="row">
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Agregar Nuevo Departamento</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body"> 
                                   <form action="">
                                       <div class="form-group">
                                           <label for="exampleInputEmail1">Nombre del Departamento</label>
                                           <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Escriba el nombre del departamento">
                                         
                                       </div>
                                           <div class="d-flex justify-content-end">
                                           <button class="mb-3 mt-3 btn btn-primary " type="submit">Agregar</button>
                                           </div>
                                   </form>
                                </div>
                            </div>
                        </div>
 
                    </div>
                    <!-- /.row -->  
                </div>
            </div>
    </div>
  
</div>

<?php include 'Views/Template/admin_footer.php'; ?>

 