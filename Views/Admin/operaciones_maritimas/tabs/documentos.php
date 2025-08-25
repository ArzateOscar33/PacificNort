<div id="documentosRoot">
    <div class="container py-4 col-md-12"> 
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i data-feather="file-text" class="me-2"></i>Gestión de Documentos</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarDocumentoDocumentos">
                <i data-feather="plus-circle"></i> Añadir Documento
            </button>
        </div>

        <div class="row flex-wrap gap-2   align-items-center mb-2">
            <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
                <label for="documentosFiltroOpNombre" class="form-label mb-1">Operación</label>
                <div class="position-relative">
                    <input type="hidden" id="documentosFiltroOpId">
                    <input type="text" id="documentosFiltroOpNombre" class="form-control"
                        placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off">
                    <div id="documentosFiltroOpSugerencias" class="list-group"
                        style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                </div>
                <div class="form-text" id="documentosFiltroOpMeta"></div>
            </div>

        </div>
        <div class="row flex-wrap gap-2   align-items-center mb-2">
            <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
                <label for="documentosFiltroContenedorFisico" class="form-label mb-1">Contenedor </label>
                <div class="position-relative">
                    <input type="hidden" id="documentosFiltroContendorId">
                    <input type="text" id="documentosFiltroContendorNombre" class="form-control"
                        placeholder="Escribe para buscar (ej. FXE o MGU)" autocomplete="off">
                    <div id="documentosFiltroContenedorSugerencias" class="list-group"
                        style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                </div>

            </div>

        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <i data-feather="check-circle" class="me-2"></i>Documentos Subidos
                    </div>

                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <i data-feather="alert-circle" class="me-2"></i>Documentos Faltantes
                    </div>

                </div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Operación</th>
                        <th>Contenedor</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Fecha</th>
                        <th>Subido por</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaDocumentos"></tbody> 
            </table>
        </div>
    </div>

    <!-- Modal: Agregar Documento (Documentos) -->
    <div class="modal fade" id="modalAgregarDocumentoDocumentos" tabindex="-1"
        aria-labelledby="modalAgregarDocumentoLabelDocumentos" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formAgregarDocumentoDocumentos">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalAgregarDocumentoLabelDocumentos">
                            <i data-feather="file-plus" class="me-1"></i>Agregar Documento
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- OPERACIÓN -->
                        <div class="mb-3">
                            <label for="modalDocumentosOpNombre" class="form-label">Operación</label>
                            <div class="position-relative">
                                <input type="hidden" id="modalDocumentosOpId" name="operacion_id">
                                <input type="text" id="modalDocumentosOpNombre" class="form-control"
                                    placeholder="Escribe para buscar (ej. JL-05)" autocomplete="off">
                                <div id="modalDocumentosOpSugerencias" class="list-group"
                                    style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                            </div>
                            <div class="form-text" id="modalDocumentosOpMeta"></div>
                        </div>

                        <!-- CONTENEDOR (F o M) -->
                        <div class="mb-3">
                            <label for="modalDocumentosContNombre" class="form-label">Contenedor</label>
                            <div class="position-relative">
                                <input type="hidden" id="modalDocumentosContId" name="contenedor_id">
                                <input type="hidden" id="modalDocumentosContTipo" name="contenedor_tipo">
                                <!-- 'F' o 'M' -->
                                <input type="text" id="modalDocumentosContNombre" class="form-control"
                                    placeholder="Escribe para buscar (FXE..., EMCU...)" autocomplete="off">
                                <div id="modalDocumentosContSugerencias" class="list-group"
                                    style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                            </div>
                            <div class="form-text">Selecciona el contenedor físico (F) o marítimo (M) asociado.</div>
                        </div>

                        <!-- TIPO DE DOCUMENTO -->
                        <div class="mb-3">
                            <label for="tipo_documentoDocumentos" class="form-label">Tipo de Documento</label>
                            <select id="tipo_documentoDocumentos" name="tipo_documento_id" class="form-control" required>
                                <option value="">-- Selecciona tipo --</option>
                            </select>
                        </div>

                        <!-- ARCHIVO -->
                        <div class="mb-3">
                            <label for="archivoDocumentos" class="form-label">Archivo</label>
                            <input type="file" id="archivoDocumentos" name="archivo" class="form-control"
                                accept=".pdf,.jpg,.png,.docx,.xlsx" required>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle"></i> Cancelar
                        </button>
                        <button type="submit" btn="documentosSubmit" class="btn btn-primary">
                            <i data-feather="upload-cloud"></i> Subir Documento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Vista Previa Documento -->
<div class="modal fade" id="modalPreviewDocumentoDocumentos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i data-feather="file-text" class="me-2"></i>Vista previa</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
         <!-- Mensaje cuando no se puede ver inline -->
  <div id="previewUnavailableDocumentos" class="p-5 text-center" style="display:none;">
    <div class="mb-3"><i data-feather="file" style="width:48px;height:48px;"></i></div>
    <div class="h5 mb-2">Este formato no se puede previsualizar aquí</div>
    <p class="text-muted mb-0">Puedes descargar el archivo para abrirlo con su aplicación.</p>
  </div>
        <iframe id="previewFrameDocumentos" src="" style="width:100%;min-height:75vh;border:0;"></iframe>
      </div>
      <div class="modal-footer">
        <a id="previewDownloadLinkDocumentos" href="#" class="btn btn-primary" target="_blank" rel="noopener">
          <i data-feather="download"></i> Descargar
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

</div>
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/operaciones_maritimas/catalogos/documentos.js"></script>