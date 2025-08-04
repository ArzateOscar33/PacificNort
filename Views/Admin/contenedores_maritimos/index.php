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
                                data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Contenedor Marino</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
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

  
<div class="container mt-4 col-md-12">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Registrar Contenedor Marítimo</h4>
    </div>
    <div class="card-body">
      <form id="formContenedorMaritimo" method="POST" action="#">

        <div class="mb-3">
          <label for="numero_contenedor">Número de Contenedor</label>
          <input type="text" class="form-control" name="numero_contenedor" required>
        </div>

        <div class="mb-3">
          <label for="tipo">Tipo</label>
          <input type="text" class="form-control" name="tipo" placeholder="Ej: 40HQ, 20STD" required>
        </div>

        <div class="mb-3">
          <label for="naviera">Naviera</label>
          <input type="text" class="form-control" name="naviera" required>
        </div>

        <div class="mb-3">
          <label for="observaciones">Observaciones</label>
          <textarea class="form-control" name="observaciones" rows="3"></textarea>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="plus-circle"></i> Registrar Contenedor
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 

<?php include 'Views/Template/admin_footer.php'; ?>