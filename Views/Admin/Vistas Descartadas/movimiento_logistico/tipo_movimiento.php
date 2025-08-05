<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Tipo_movimiento</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control " placeholder="Buscar Tipo_movimiento">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Tipo de Movimiento</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Divisa</option>
                                <option>DLLS</option>
                                <option>MXN</option> 
                            </select>
                        </div>
                        <div class="col-md-3">
                         <button class="btn btn-primary white">
                           <i class="fas fa-plus"></i> Tipo de Movimiento
                            
                         </button>   
                        </div>
 
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo de movimiento</th>
                                    <th>Moneda</th>
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
</div>

 
<div class="container mt-4 col-md-12">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Registrar Tipo de Movimiento</h4>
    </div>
    <div class="card-body">
      <form id="formTipoMovimiento" method="POST" action="#">

        <div class="mb-3">
          <label for="nombre_movimiento" class="form-label">Nombre del Tipo de Movimiento</label>
          <input type="text" name="nombre_movimiento" class="form-control" placeholder="Ej. Carga, Descarga, Traslado" required>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="plus-circle"></i> Registrar Movimiento
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 
<?php include 'Views/Template/admin_footer.php'; ?>