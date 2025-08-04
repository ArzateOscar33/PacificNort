<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Transportistas</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control " placeholder="Buscar Transportista">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Transportista</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
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

 
<div class="container mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Registrar Transportista</h4>
    </div>
    <div class="card-body">
      <form id="formTransportista" method="POST" action="#">

        <div class="mb-3">
          <label for="nombre">Nombre del Transportista</label>
          <input type="text" name="nombre" class="form-control" required>
        </div>

        <div class="mb-3">
          <select name="tipo" class="form-control">
            <option value="1" selected>Tipo de Transportista</option>
            <option value="2">Terrestre</option>
            <option value="3">Maritimo</option>
          </select>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="truck"></i> Registrar Transportista
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 


<?php include 'Views/Template/admin_footer.php'; ?>