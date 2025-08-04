<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
 
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Usuarios</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control " placeholder="Buscar Usuario">
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarDepartamento" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#staticBackdrop"><i class="fas fa-plus"></i> Agregar Usuario</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->   
                     <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Correo</th>
                                    <th>Clave</th>
                                    <th>Telefono</th>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
                                    <th>Rol</th> 
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
      <h4 class="mb-0">Registrar Usuario</h4>
    </div>
    <div class="card-body">
      <form id="formUsuario" method="POST" action="#">

        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>

          <div class="mb-3 col-md-6">
            <label for="apellido" class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" required>
          </div>
        </div>

        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" name="correo" class="form-control" required>
          </div>

          <div class="mb-3 col-md-6">
            <label for="clave" class="form-label">Contraseña</label>
            <input type="password" name="clave" class="form-control" required>
          </div>
        </div>

        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="tel" name="telefono" class="form-control">
          </div>

          <div class="mb-3 col-md-3">
            <label for="puesto_id" class="form-label">Puesto</label>
            <select name="puesto_id" class="form-control" required>
              <option value="">Seleccione</option>
              <!-- Aquí irán las opciones desde la BD -->
            </select>
          </div>

          <div class="mb-3 col-md-3">
            <label for="departamento_id" class="form-label">Departamento</label>
            <select name="departamento_id" class="form-control" required>
              <option value="">Seleccione</option>
              <!-- Aquí irán las opciones desde la BD -->
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label for="active" class="form-label">Estado</label>
          <select name="active" class="form-control" required>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i data-feather="user-plus"></i> Guardar Usuario
          </button>
        </div>

      </form>
    </div>
  </div>
</div> 

<?php include 'Views/Template/admin_footer.php'; ?>