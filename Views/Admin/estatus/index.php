
<?php include 'Views/Template/admin_header.php';
?>
<div class="contain col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Estatus</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                        <div class="col-md-10">
                        <input type="text" class="form-control " placeholder="Buscar Estatus">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Estatus</button>
                            </div>
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
                                    <td>Estatus 1</td>
                                    <td>1</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Estatus 2</td>
                                    <td>2</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Estatus 3</td>
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
    <!-- /.row -->    
</div>


 
<div class="container mt-4 col-md-12">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Registrar Estatus</h4>
    </div>
    <div class="card-body">
      <form id="formEstatus" method="POST" action="#">

        <div class="mb-3">
          <label for="descripcion" class="form-label">Descripción</label>
          <input type="text" name="descripcion" class="form-control" required placeholder="Ej. En proceso, Finalizado, En revisión">
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="save"></i> Guardar Estatus
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 



<?php include 'Views/Template/admin_footer.php'; ?>