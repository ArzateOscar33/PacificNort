 
<div class="container py-4 col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i data-feather="file-text" class="me-2"></i>Gestión de Documentos</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarDocumento">
            <i data-feather="plus-circle"></i> Añadir Documento
        </button>
    </div>

    <div class="mb-3">
        <label for="selectOperacion" class="form-label">Seleccionar Operación</label>
        <select id="selectOperacion" class="form-control">
            <option value="">-- Elige una operación --</option>
            <option value="JL-46">JL-46</option>
            <option value="JL-47">JL-47</option>
            <!-- Agregar dinámicamente desde DB -->
        </select>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <i data-feather="check-circle" class="me-2"></i>Documentos Subidos
                </div>
                <ul class="list-group list-group-flush" id="listaDocumentos">
                    <li class="list-group-item">Carta Encomienda - Subido el 01/08/2025</li>
                    <li class="list-group-item">Garantía - Subido el 02/08/2025</li>
                </ul>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i data-feather="alert-circle" class="me-2"></i>Documentos Faltantes
                </div>
                <ul class="list-group list-group-flush" id="listaFaltantes">
                    <li class="list-group-item">EIR</li>
                    <li class="list-group-item">Notificación de Arribo</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tipo de Documento</th>
                    <th>Subido por</th>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaDocumentos">
                <tr>
                    <td>Carta Encomienda</td>
                    <td>Oscar Arzate</td>
                    <td>01/08/2025</td>
                    <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger"><i data-feather="trash-2"></i></button>
                    </td>
                </tr>
                <!-- Más filas dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Agregar Documento -->
<div class="modal fade" id="modalAgregarDocumento" tabindex="-1" aria-labelledby="modalAgregarDocumentoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarDocumento">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalAgregarDocumentoLabel"><i data-feather="file-plus" class="me-1"></i>Agregar Documento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tipo_documento" class="form-label">Tipo de Documento</label>
                        <select id="tipo_documento" name="tipo_documento" class="form-control" required>
                            <option value="">-- Selecciona tipo --</option>
                            <option value="argos">Pago de Argos Locales</option>
                            <option value="revalidacion">Pago de Revalidación</option>
                            <option value="encomienda">Carta Encomienda</option>
                            <option value="garantia">Garantía</option>
                            <option value="eir">EIR</option>
                            <option value="arribo">Notificación de Arribo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="archivo" class="form-label">Archivo</label>
                        <input type="file" id="archivo" name="archivo" class="form-control" accept=".pdf,.jpg,.png,.docx,.xlsx" required>
                    </div>

                    <input type="hidden" id="operacion_id" name="operacion_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i data-feather="x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="upload-cloud"></i> Subir Documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    feather.replace();

    // Asignar operación seleccionada al modal
    document.getElementById('selectOperacion').addEventListener('change', function () {
        const operacion = this.value;
        document.getElementById('operacion_id').value = operacion;

        // Aquí deberías hacer una llamada AJAX para traer los documentos de esa operación
        console.log("Operación seleccionada:", operacion);
    });

    document.getElementById('formAgregarDocumento').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        // Lógica para enviar a backend aquí (AJAX)
        console.log("Formulario enviado:", Object.fromEntries(formData));
        // Cerrar modal y limpiar
        this.reset();
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarDocumento'));
        modal.hide();
    });
</script>
