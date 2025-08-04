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
                        <div class="col-md-10">
                        <input type="text" class="form-control " placeholder="Buscar Tipo de Operacion">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Tipo de Operacion</button>
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
                                    <td>Maritimo </td>
                                    <td>1</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Aereo</td>
                                    <td>2</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i>
                                            Eliminar</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Terrestre</td>
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
      <h4 class="mb-0">Registrar Tipo de Operación</h4>
    </div>
    <div class="card-body">
      <form id="formTipoOperacion" method="POST" action="#">

        <div class="mb-3">
          <label for="nombre_operacion" class="form-label">Nombre del Tipo de Operación</label>
          <input type="text" name="nombre_operacion" class="form-control" placeholder="Ej. Importación, Exportación" required>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="plus-circle"></i> Registrar Tipo
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 


<?php include 'Views/Template/admin_footer.php'; ?>