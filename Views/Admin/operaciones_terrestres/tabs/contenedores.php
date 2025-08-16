<div class="container py-4 col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Contenedores en Operación</h4>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarContenedor">
    <i data-feather="plus"></i> Añadir Contenedor
</button>
    </div>

    <div class="row mb-4">
 
        <div class="col-md-12">
            <label for="buscar" class="form-label">Buscar Cliente o Contenedor</label>
            <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th> 
                    <th>Numero de Ferro</th>
                    <th>Fecha De Creacion</th>
                    <th>Origen</th>
                    <th>Destino</th> 
                    <th>Comentarios</th>
                    <th>Acciones</th>
                 
                </tr>
            </thead>
            <tbody>
                <tr>
                    
                </tr>
                <tr>
                    <td> Waldos</td>
                    <td>FXEU98889</td>
                    <td>20/08/2025</td>
                    <td>San Diego</td>
                    <td>Pantaco</td>
                    <td>Sin Comentarios</td> 
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i data-feather="edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i data-feather="x"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Agregar Contenedor a la Operación -->
<!-- Modal: Agregar Contenedor a la Operación -->
<div class="modal fade" id="modalAgregarContenedor" tabindex="-1" aria-labelledby="modalAgregarContenedorLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAgregarContenedorLabel">
                    <i data-feather="plus-circle" class="me-1"></i> Añadir Contenedor a la Operación
                </h5>
            </div>
            <div class="modal-body">
                <form id="formAgregarContenedor">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tipo_contenedor" class="form-label">Tipo de Contenedor</label>
                            <select id="tipo_contenedor" name="tipo_contenedor" class="form-control" required>
                                <option value="">Selecciona una opción</option>
                                <option value="maritimo">Marítimo</option>
                                <option value="terrestre">Terrestre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="contenedor_id" class="form-label">Contenedor Físico</label>
                            <select id="contenedor_id" name="contenedor_id" class="form-control" required>
                                <option value="">Selecciona un contenedor</option>
                                <!-- Opciones dinámicas desde DB -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <input type="text" id="cliente" name="cliente" class="form-control" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bultos" class="form-label">Bultos</label>
                            <input type="number" id="bultos" name="bultos" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="peso" class="form-label">Peso (t)</label>
                            <input type="number" step="0.01" id="peso" name="peso" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="comentarios" class="form-label">Comentarios</label>
                        <textarea id="comentarios" name="comentarios" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x"></i> Cancelar
                </button>
                <button type="submit" form="formAgregarContenedor" class="btn btn-primary">
                    <i data-feather="save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>


<script>
    feather.replace();
</script>
 