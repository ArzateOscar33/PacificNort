<div id="documentosRoot">
  <div class="container py-4 col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4><i data-feather="file-text" class="me-2"></i>Gestión de Documentos</h4>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarDocumentoDocumentos">
        <i data-feather="plus-circle"></i> Añadir Documento
      </button>
    </div>

    <!-- Filtro: Operación -->
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
    <div class="row flex-wrap gap-2 align-items-center mb-2">
      <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
        <label for="documentosFiltroCMONombre" class="form-label mb-1">Contenedor marítimo</label>
        <div class="position-relative">
          <input type="hidden" id="documentosFiltroCMOId">
          <input type="text" id="documentosFiltroCMONombre" class="form-control"
            placeholder="Escribe para buscar (ej. MGUU1234567)" autocomplete="off" readonly>
          <div id="documentosFiltroCMOSugerencias" class="list-group"
            style="position:absolute; z-index:1061; width:100%; display:none;"></div>
        </div>
        <div class="form-text">Primero selecciona la operación; aquí verás sus contenedores marítimos.</div>
      </div>
    </div>

  </div>

  <div class="row g-3 mb-4">
    <!-- Documentos Subidos -->
    <div class="col-md-6">
      <div class="card border-success">
        <div class="card-header bg-success text-white">
          <i data-feather="check-circle" class="me-2"></i>Documentos Subidos
        </div>
        <div class="card-body p-0">
          <ul id="listaDocumentos" class="list-group list-group-flush small"></ul>
        </div>
      </div>
    </div>

    <!-- Documentos Faltantes -->
    <div class="col-md-6">
      <div class="card border-danger">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
          <span><i data-feather="alert-circle" class="me-2"></i>Documentos Faltantes</span>
          <button id="btnNotificarFaltantes" class="btn btn-warning btn-sm" style="display:none;">
            <i data-feather="mail"></i> Notificar al cliente
          </button>
        </div>
        <div class="card-body p-0">
          <ul id="listaFaltantesDocumentos" class="list-group list-group-flush small">
            <li class="list-group-item text-muted">Sin faltantes</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-responsive mb-4">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Operación</th>
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

<!-- Modal: Agregar Documento -->
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
          <div class="mb-3">
            <label for="modalDocumentosCMONombre" class="form-label">Contenedor marítimo</label>
            <div class="position-relative">
              <input type="hidden" id="modalDocumentosCMOId" name="contenedor_maritimo_id">
              <input type="text" id="modalDocumentosCMONombre" class="form-control "
                placeholder="Busca el contenedor marítimo" autocomplete="off">
              <div id="modalDocumentosCMOSugerencias" class="list-group"
                style="position:absolute; z-index:1061; width:100%; display:none;"></div>
            </div>
            <div class="form-text" id="modalDocumentosCMOMeta">Se listan los contenedores de la operación seleccionada.
            </div>
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

<!-- Modal: Vista Previa -->
<div class="modal fade" id="modalPreviewDocumentoDocumentos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i data-feather="file-text" class="me-2"></i>Vista previa</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
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
 

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/Operaciones_maritimas/catalogos/documentos.js"></script>