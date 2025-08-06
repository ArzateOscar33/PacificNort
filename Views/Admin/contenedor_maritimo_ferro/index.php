<?php include 'Views/Template/admin_header.php'; ?>

<div class="container-fluid px-4">
    <h3 class="mt-4">Seguimiento Marítimo-Ferroviario</h3>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="formFiltros" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="buscar_contenedor" placeholder="Buscar por contenedor...">
                </div>
                <div class="col-md-3">
                    <select class=" form-control" name="origen">
                        <option value="">Filtrar por origen</option>
                        <!-- Opciones dinámicas -->
                        <option>Lázaro</option>
                        <option>Manzanillo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class=" form-control" name="destino">
                        <option value="">Filtrar por destino</option>
                        <option>Querétaro</option>
                        <option>Guadalajara</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-outline-primary"><i data-feather="filter"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i data-feather="truck"></i> Contenedores Marítimos/Ferroviarios</span>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarContenedorFerro">
                <i data-feather="plus"></i> Agregar Seguimiento
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table   table-hover align-middle text-center">
                    <thead class=" table-primary text-center">
                        <tr>
                            <th><i data-feather="hash"></i> ID</th>
                            <th><i data-feather="package"></i> Contenedor</th>
                            <th><i data-feather="file-text"></i> Folio Operación</th>
                            <th><i data-feather="map-pin"></i> Origen</th>
                            <th><i data-feather="map-pin"></i> Destino</th>
                            <th><i data-feather="truck"></i> Transportista</th>
                            <th><i data-feather="message-circle"></i> Comentario</th>
                            <th><i data-feather="settings"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>CMAU1234567</td>
                            <td>JL-045</td>
                            <td>Lázaro</td>
                            <td>Querétaro</td>
                            <td>Autotransportes Ferro</td>
                            <td>Contenedor en ruta</td>
                            <td>
                                <button class="btn btn-outline-info btn-sm"><i data-feather="eye"></i></button>
                            </td>
                        </tr>
                        <!-- Registros dinámicos aquí -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Seguimiento -->
<div class="modal fade" id="modalAgregarContenedorFerro" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAgregarSeguimiento">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalLabel"><i data-feather="plus-square"></i> Agregar Seguimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="contenedor" class="form-label">Contenedor</label>
                        <input type="text" class="form-control" name="contenedor" required>
                    </div>
                    <div class="col-md-6">
                        <label for="folio_operacion" class="form-label">Folio Operación</label>
                        <input type="text" class="form-control" name="folio_operacion" required>
                    </div>
                    <div class="col-md-6">
                        <label for="origen" class="form-label">Origen</label>
                        <select name="origen" class=" form-control" required>
                            <option value="">Seleccione origen</option>
                            <option value="Lázaro">Lázaro</option>
                            <option value="Manzanillo">Manzanillo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="destino" class="form-label">Destino</label>
                        <select name="destino" class=" form-control" required>
                            <option value="">Seleccione destino</option>
                            <option value="Querétaro">Querétaro</option>
                            <option value="Guadalajara">Guadalajara</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="transportista" class="form-label">Transportista</label>
                        <input type="text" class="form-control" name="transportista" required>
                    </div>
                    <div class="col-md-12">
                        <label for="comentario" class="form-label">Comentario</label>
                        <textarea class="form-control" name="comentario" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i data-feather="x"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success"><i data-feather="save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    feather.replace();
    // Aquí puedes agregar validaciones o enviar por AJAX
</script>

<?php include 'Views/Template/admin_footer.php'; ?>
