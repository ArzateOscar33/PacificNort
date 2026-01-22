<!-- ===================== VISTA (UPLOAD EN MODAL) ===================== -->
<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="paperclip" class="me-1"></i> Documentos de Facturas (Operaciones por Partida)
      </h5>

      <div class="d-flex gap-2">
        <button class="btn btn-light btn-sm" id="partidas_docs_btnAbrirUpload" disabled
          data-bs-toggle="modal" data-bs-target="#modalPartidasDocsUpload">
          <i data-feather="upload" class="me-1"></i> Subir documento
        </button>

        <button class="btn btn-light btn-sm" id="partidas_docs_btnRefrescar" disabled>
          <i data-feather="refresh-cw" class="me-1"></i> Refrescar
        </button>
      </div>
    </div>

    <div class="card-body">

      <!-- ===================== FILTROS / SELECCIÓN ===================== -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        <!-- FACTURA: INPUT + SUGERENCIAS -->
        <div class="position-relative" style="max-width:360px; width:100%;">
          <label class="form-label mb-1 small text-muted">Factura</label>
          <input
            id="partidas_docs_facturaInput"
            class="form-control"
            placeholder="Factura"
            autocomplete="off"
          />
          <!-- Hidden para guardar el ID real seleccionado (lo setea tu JS) -->
          <input type="hidden" id="partidas_docs_facturaId" value="">

          <!-- Sugerencias -->
          <div
            id="partidas_docs_facturaSuggest"
            class="list-group position-absolute w-100 shadow-sm d-none"
            style="z-index:1050; max-height:320px; overflow:auto;"
            aria-label="Sugerencias de facturas">
            <!--
              JS renderiza items aquí, ejemplo:
              <button type="button" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                  <span class="fw-semibold">FAC-000123</span>
                  <span class="badge bg-light text-dark border">BODEGA TJ</span>
                </div>
                <div class="small text-muted">Proveedor X • 2026-01-21 • 14 productos</div>
              </button>
            -->
          </div>

 
        </div>

        <!-- BUSCAR DOCUMENTOS -->
        <div style="max-width:320px; width:100%;">
          <label class="form-label mb-1 small text-muted">Buscar documentos</label>
          <input
            id="partidas_docs_buscar"
            class="form-control"
            placeholder="Buscar por nombre de archivo / tipo / notas"
            autocomplete="off"
            disabled
          >
        </div>

 
      </div>

      <!-- ===================== TABLA DOCUMENTOS ===================== -->
      <div class="table-responsive">
        <table class="table  align-middle" id="partidas_docs_tabla">
          <thead class="table-dark">
            <tr class="text-center">
              <th style="min-width:260px;">Archivo</th>
              <th style="width:140px;">Tipo</th>
              <th style="width:180px;">Subido</th>
              <th style="min-width:260px;">Notas</th>
              <th style="width:170px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="partidas_docs_tbody"></tbody>
        </table>
      </div>

      <div id="partidas_docs_empty" class="alert alert-light border d-none mb-0">
        Selecciona una factura para visualizar documentos relacionados.
      </div>

    </div>
  </div>
</div>

<!-- ===================== MODAL: SUBIR DOCUMENTOS ===================== -->
<div class="modal fade" id="modalPartidasDocsUpload" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title d-flex align-items-spac gap-2 mb-0 ">
          <i data-feather="upload"></i>
          <span>Subir documentos</span> 
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="partidas_docs_formUpload" autocomplete="off">
          <div class="row g-3 align-items-end">

            <div class="col-md-12">
              <div class="alert alert-light border d-flex align-items-start gap-2 mb-0">
                <i data-feather="info" class="mt-1"></i>
                <div class="small">
                  Los archivos quedarán asociados a la factura seleccionada. Puedes subir múltiples archivos.
                </div>
              </div>
            </div>

            <!-- NUEVO: TIPO DE DOCUMENTO -->
            <div class="col-md-6">
              <label class="form-label">Tipo de documento</label>
              <select id="partidas_docs_tipoDoc" class="form-control" disabled>
                <option value="">Seleccione tipo...</option>
 >
              </select>
     
            </div>

            <div class="col-md-6">
              <label class="form-label">Notas / Descripción</label>
              <input type="text" id="partidas_docs_notas" class="form-control"
                placeholder="Ej. Factura firmada / Packing list" disabled>
            </div>

            <div class="col-md-12">
              <label class="form-label">Archivo(s)</label>
              <input type="file" id="partidas_docs_inputFiles" class="form-control" multiple disabled
                accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx,.txt,.zip">
              <small class="text-muted d-block mt-1">
                Tipos sugeridos: PDF, imágenes, Excel, Word.
              </small>
            </div>

          </div>
        </form>

        <!-- Preview DEMO (lo puedes dejar o quitar; no incluye script aquí) -->
        <div class="mt-3 d-none" id="partidas_docs_uploadPreviewWrap">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
              <span class="small"><i data-feather="check-circle" class="me-1"></i> Cargado (demo)</span>
              <button type="button" class="btn btn-light btn-sm" id="partidas_docs_btnOcultarUploadPreview">Ocultar</button>
            </div>
            <div class="card-body">
              <pre class="mb-0" style="white-space:pre-wrap;" id="partidas_docs_uploadPreviewJson"></pre>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cancelar
        </button>

        <button type="button" class="btn btn-success" id="partidas_docs_btnSubir" disabled>
          <i data-feather="arrow-up-circle" class="me-1"></i> Subir
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ===================== MODAL: PREVISUALIZAR DOCUMENTO ===================== -->
<div class="modal fade" id="modalPartidasDocsPreview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-xxl-wide">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="eye"></i>
          <span>Vista previa</span>
          <span class="badge bg-light text-dark" id="partidas_docs_previewTipo">—</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <div class="small text-muted">
            <span class="me-2">Factura: <span class="fw-semibold" id="partidas_docs_previewFactura">—</span></span>
            <span class="me-2">Archivo: <span class="fw-semibold" id="partidas_docs_previewNombre">—</span></span>
          </div>

          <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="partidas_docs_previewBtnDescargar">
              <i data-feather="download" class="me-1"></i> Descargar
            </button>
          </div>
        </div>

        <div id="partidas_docs_previewWrap" class="border rounded overflow-hidden" style="min-height:60vh;"></div>

        <div id="partidas_docs_previewEmpty" class="alert alert-light border d-none mt-3 mb-0">
          Este tipo de archivo no tiene vista previa en el navegador. Usa Descargar.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cerrar
        </button>
      </div>

    </div>
  </div>
</div>

<style>
  .modal-xxl-wide { max-width: min(1600px, calc(100vw - 2rem)); }

  /* Sugerencias: mantener look Bootstrap limpio */
  #partidas_docs_facturaSuggest .list-group-item {
    cursor: pointer;
  }
</style>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_documentos_catalogo.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_documentos_registrar.js"></script>