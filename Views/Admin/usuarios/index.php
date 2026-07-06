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
                            <input type="text" id="buscarUsuario" class="form-control " placeholder="Buscar Usuario">
                            <div id="sugerenciasUsuario" class="list-group position-absolute w-100 z-3"
                                style="z-index:999;"></div>
                        </div>
                        <div class="  d-flex justify-content-end  col-md-2">
                            <button href="#" id="btnAgregarUsuario" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarUsuario"><i class="fas fa-plus"></i> Agregar
                                Usuario</button>
                        </div>
                    </div>
                    <!-- /.d-flex -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Correo</th>
                                    <th>Telefono</th>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
                                    <th>Rol</th>
                                    <th>Cliente</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaUsuarios">

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

<div class="modal fade" id="modalRegistrarUsuario" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarUsuarioLabel">
                    <i data-feather="user-plus" class="me-2"></i> Registrar Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formUsuario" method="POST" action="#" autocomplete="off">
                    <input type="hidden" name="id_usuario" id="id_usuario" value="">
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

                    </div>

                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control">
                        </div>
                        <div class="mb-3 col-md-3">
                            <label class="form-label">Departamento</label>
                            <select name="departamento_id" id="departamento_id" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($data['departamentos'] as $dep): ?>
                                    <option value="<?= $dep['id_departamento'] ?>"><?= $dep['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="wrap_puesto" class="mb-3 col-md-3">
                            <label for="puesto_id" class="form-label">Puesto</label>
                            <select name="puesto_id" id="puesto_id" class="form-control" required>
                                <option value="">Seleccione</option>

                            </select>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="rol" class="form-label">Rol</label>
                            <select name="rol_id" id="rol_id" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($data['roles'] as $dep): ?>
                                    <option value="<?= $dep['id_rol'] ?>"><?= $dep['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 col-md-6 d-none" id="wrap_cliente">
                            <label for="cliente_id" class="form-label">Cliente (solo si aplica)</label>
                            <select name="cliente_id" id="cliente_id" class="form-control">
                                <option value="">Seleccione</option>
                                <?php foreach ($data['clientes'] as $c): ?>
                                    <option value="<?= (int)$c['id_cliente'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Este campo es obligatorio cuando el rol es <b>Cliente</b>.
                            </div>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="active" class="form-label">Estado</label>
                            <select name="active" class="form-control" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div id="wrapToggleCambiarClave" class="row d-none">
                        <div class="mb-3 col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="toggleCambiarClave">
                                <label class="form-check-label" for="toggleCambiarClave">
                                    Cambiar contraseña
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="mb-3 col-md-6 d-none" id="wrapNuevaClave">
                            <label for="nueva_clave" class="form-label">Nueva contraseña</label>
                            <input type="password" id="nueva_clave" name="nueva_clave" class="form-control">
                        </div>
                        <div class="mb-3 col-md-6 d-none" id="wrapConfirmarClave">
                            <label for="confirmar_clave" class="form-label">Confirmar contraseña</label>
                            <input type="password" id="confirmar_clave" name="confirmar_clave" class="form-control">
                        </div>
                    </div>


                    <!-- Footer -->
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src='<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/usuarios.js'></script>