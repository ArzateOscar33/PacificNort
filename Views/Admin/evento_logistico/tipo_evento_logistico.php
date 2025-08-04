<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Tipo_evento</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <div class="row  align-items-center mb-3 col-md-12"  >
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Tipo de Evento" id="txtTipoEvento">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-warning">
                                <i class="fas fa-search"></i> Buscar Tipo Evento
                            </button>
                        </div>

                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Agregar Tipo Evento
                            </button>
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
                                <th></th>
                                <th></th>
                                <th></th>
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
      <h4 class="mb-0">Registrar Tipo de Evento Logístico</h4>
    </div>
    <div class="card-body">
      <form id="formTipoEvento" method="POST" action="#">

        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre del Evento</label>
          <input type="text" name="nombre" class="form-control" required placeholder="Ej. Arribo, Salida, Revisión">
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="save"></i> Guardar Evento
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 


<?php include 'Views/Template/admin_footer.php'; ?>