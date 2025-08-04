<?php include 'Views/Template/admin_header.php';
?>
 <div class="contianer col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Trazabilidad</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between mb-3">  
                        <div class="col-md-10">
                        <input type="text" class="form-control " placeholder="Buscar Trazabilidad">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                        <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Trazabilidad</button>
                            </div>
                    </div>


                <!-- /.card-header -->
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Número de Trazabilidad</th>
                                    <th>Contenedor Fisico</th>
                                    <th>Origen</th>
                                    <th>Destino</th>
                                    <th>Fecha Estimada</th>
                                    <th>Observaciones</th>
                                    <th>Tipo de Transporte</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

 
<div class="container mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Registrar Trazabilidad del Contenedor</h4>
    </div>
    <div class="card-body">
      <form id="formTrazabilidad" method="POST" action="#">

        <div class="mb-3">
          <label for="contenedor_fisico_id" class="form-label">Contenedor Físico</label>
          <select name="contenedor_fisico_id" class="form-control" required>
            <option value="">Seleccione un contenedor</option>
            <!-- foreach PHP -->
          </select>
        </div>

        <div class="mb-3">
          <label for="tipo_transporte" class="form-label">Tipo de Transporte</label>
          <select name="tipo_transporte" class="form-control" required>
            <option value="">Seleccione tipo</option>
            <option value="Marítimo">Marítimo</option>
            <option value="Terrestre">Terrestre</option>
            <option value="Ferroviario">Ferroviario</option>
            <option value="Aéreo">Aéreo</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="origen" class="form-label">Origen</label>
          <input type="text" name="origen" class="form-control" required placeholder="Puerto/Lugar de origen">
        </div>

        <div class="mb-3">
          <label for="destino" class="form-label">Destino</label>
          <input type="text" name="destino" class="form-control" required placeholder="Puerto/Lugar de destino">
        </div>

        <div class="mb-3">
          <label for="fecha_llegada" class="form-label">Fecha de Llegada</label>
          <input type="date" name="fecha_llegada" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="observaciones" class="form-label">Observaciones</label>
          <textarea name="observaciones" class="form-control" rows="3" placeholder="Opcional..."></textarea>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="map-pin"></i> Registrar Trazabilidad
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 

<?php include 'Views/Template/admin_footer.php'; ?>