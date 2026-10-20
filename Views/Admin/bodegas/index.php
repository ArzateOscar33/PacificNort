<?php include 'Views/Template/admin_header.php'; ?>
<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title mt-3 mb-3 text-white">Bodegas, Yardas y Estacionamientos</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-10">
                            <input type="text" id="buscarBodega" name="buscarBodega" class="form-control"
                                placeholder="Buscar por código, nombre, dirección o ciudad">
                            <div id="sugerenciasBodega" class="list-group position-absolute w-100"
                                style="z-index:999; display:none;"></div>
                        </div>
                        <div class="d-flex justify-content-end col-md-2">
                            <button type="button" id="btnAgregarBodega" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalRegistrarBodega">
                                <i class="fas fa-plus"></i> Agregar Ubicación
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Ciudad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBodegas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistrarBodega" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalRegistrarBodegaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarBodegaLabel">
                    <i data-feather="home" class="me-2"></i> Registrar Ubicación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formBodega" method="POST" action="#">
                    <input type="hidden" name="id" id="id" value="">

                    <!-- NUEVO: tipo y código de la ubicación -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tipo_ubicacion" class="form-label">Tipo de ubicación</label>
                            <select name="tipo_ubicacion" id="tipo_ubicacion" class="form-control" required>
                                <option value="BODEGA">Bodega</option>
                                <option value="YARDA">Yarda</option>
                                <option value="ESTACIONAMIENTO">Estacionamiento</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="codigo" class="form-label">Código (4 letras)</label>
                            <input type="text" name="codigo" id="codigo" class="form-control"
                                maxlength="4" minlength="4" placeholder="BDHC / YDTE" autocomplete="off" required>
                            <small class="text-muted">BD, YD o ES + dos letras.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la ubicación</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="direccion" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="ciudad_id" class="form-label">Ciudad</label>
                        <select name="ciudad_id" id="ciudad_id" class="form-control" required>
                            <option value="">Selecciona una ciudad</option>
                            <?php foreach ($data['ciudades'] as $ciudad): ?>
                                <option value="<?= (int)$ciudad['id_ciudad'] ?>">
                                    <?= htmlspecialchars($ciudad['nombre_ciudad'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                            <i data-feather="check-circle" class="me-1"></i> Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/bodegas.js"></script>