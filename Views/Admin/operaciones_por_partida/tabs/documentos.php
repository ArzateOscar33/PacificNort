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

        <button class="btn btn-light btn-sm" id="partidas_docs_btnRefrescar">
          <i data-feather="refresh-cw" class="me-1"></i> Refrescar
        </button>
      </div>
    </div>

    <div class="card-body">

      <!-- ===================== FILTROS / SELECCIÓN ===================== -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        <select id="partidas_docs_selectFactura" class="form-control" style="max-width:240px;">
          <option value="">Seleccione factura...</option>
          <!-- DEMO -->
          <option value="43">Factura 43</option>
          <option value="48">Factura 48</option>
        </select>

        <input id="partidas_docs_buscar" class="form-control" style="max-width:320px;"
          placeholder="Buscar por nombre de archivo / tipo / notas" autocomplete="off" disabled>

        <div class="ms-auto d-flex align-items-center gap-2">
          <span class="small text-muted">Resumen:</span>
          <span class="badge bg-secondary" id="partidas_docs_badgeTotal">Archivos: 0</span>
          <span class="badge bg-light text-dark border" id="partidas_docs_lblFacturaTop">Factura: —</span>
        </div>
      </div>

      <!-- ===================== TABLA DOCUMENTOS ===================== -->
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="partidas_docs_tabla">
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
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="upload"></i>
          <span>Subir documentos</span>
          <span class="badge bg-light text-dark" id="partidas_docs_uploadFactura">Factura: —</span>
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

            <div class="col-md-7">
              <label class="form-label">Archivo(s)</label>
              <input type="file" id="partidas_docs_inputFiles" class="form-control" multiple
                accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx,.txt,.zip">
              <small class="text-muted d-block mt-1">
                Tipos sugeridos: PDF, imágenes, Excel, Word.
              </small>
            </div>

            <div class="col-md-5">
              <label class="form-label">Notas / Descripción</label>
              <input type="text" id="partidas_docs_notas" class="form-control"
                placeholder="Ej. Factura firmada / Packing list">
            </div>

          </div>
        </form>

        <!-- Preview DEMO -->
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

        <button type="button" class="btn btn-success" id="partidas_docs_btnSubir">
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
  .modal-xxl-wide{ max-width: min(1600px, calc(100vw - 2rem)); }
</style>

<!-- ===================== DEMO SCRIPT (SIN BD) ===================== -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  // ===== Refs =====
  const selFactura   = document.getElementById("partidas_docs_selectFactura");
  const inpBuscar    = document.getElementById("partidas_docs_buscar");
  const btnRef       = document.getElementById("partidas_docs_btnRefrescar");
  const btnAbrirUp   = document.getElementById("partidas_docs_btnAbrirUpload");

  const tbody        = document.getElementById("partidas_docs_tbody");
  const empty        = document.getElementById("partidas_docs_empty");
  const badgeTotal   = document.getElementById("partidas_docs_badgeTotal");
  const lblFacturaTop= document.getElementById("partidas_docs_lblFacturaTop");

  // Upload modal
  const uploadModal  = document.getElementById("modalPartidasDocsUpload");
  const uploadFactura= document.getElementById("partidas_docs_uploadFactura");
  const inpFiles     = document.getElementById("partidas_docs_inputFiles");
  const inpNotas     = document.getElementById("partidas_docs_notas");
  const btnSubir     = document.getElementById("partidas_docs_btnSubir");

  const upPrevWrap   = document.getElementById("partidas_docs_uploadPreviewWrap");
  const upPrevJson   = document.getElementById("partidas_docs_uploadPreviewJson");
  const btnHideUpPrev= document.getElementById("partidas_docs_btnOcultarUploadPreview");

  // Preview modal
  const previewModal = document.getElementById("modalPartidasDocsPreview");
  const pvTipo       = document.getElementById("partidas_docs_previewTipo");
  const pvFactura    = document.getElementById("partidas_docs_previewFactura");
  const pvNombre     = document.getElementById("partidas_docs_previewNombre");
  const pvWrap       = document.getElementById("partidas_docs_previewWrap");
  const pvEmpty      = document.getElementById("partidas_docs_previewEmpty");
  const pvBtnDown    = document.getElementById("partidas_docs_previewBtnDescargar");

  // ===== DEMO DATA (por factura) =====
  const DB = {
    "43": [
      { id:"D-4301", nombre:"Factura_43_PLATINUM.pdf", tipo:"PDF", fecha:"2025-04-22 10:14", notas:"Factura firmada", url:"#"},
      { id:"D-4302", nombre:"Packing_List_43.xlsx", tipo:"Excel", fecha:"2025-04-22 10:18", notas:"Detalle de productos", url:"#"},
      { id:"D-4303", nombre:"Evidencia_Recepcion_43.jpg", tipo:"Imagen", fecha:"2025-04-22 10:25", notas:"Fotos de pallets", url:"#"}
    ],
    "48": [
      { id:"D-4801", nombre:"Factura_48.pdf", tipo:"PDF", fecha:"2025-04-25 09:05", notas:"Factura", url:"#"}
    ]
  };

  let currentRows = [];

  const extToTipo = (filename) => {
    const f = (filename || "").toLowerCase();
    if (f.endsWith(".pdf")) return "PDF";
    if (f.endsWith(".jpg") || f.endsWith(".jpeg") || f.endsWith(".png") || f.endsWith(".webp")) return "Imagen";
    if (f.endsWith(".xlsx") || f.endsWith(".xls")) return "Excel";
    if (f.endsWith(".doc") || f.endsWith(".docx")) return "Word";
    if (f.endsWith(".txt")) return "Texto";
    if (f.endsWith(".zip")) return "ZIP";
    return "Archivo";
  };

  const render = (rows) => {
    tbody.innerHTML = "";

    if (!selFactura.value) {
      empty.classList.remove("d-none");
      badgeTotal.textContent = "Archivos: 0";
      return;
    }

    empty.classList.add("d-none");

    if (!rows.length) {
      badgeTotal.textContent = "Archivos: 0";
      tbody.innerHTML = `
        <tr class="text-center">
          <td colspan="5" class="text-muted py-4">No hay documentos registrados para esta factura.</td>
        </tr>
      `;
      return;
    }

    badgeTotal.textContent = "Archivos: " + rows.length;

    rows.forEach(r => {
      const tr = document.createElement("tr");
      tr.className = "text-center";
      tr.innerHTML = `
        <td class="text-start">
          <div class="d-flex align-items-center gap-2">
            <i data-feather="file"></i>
            <div>
              <div class="fw-semibold">${r.nombre}</div>
              <div class="small text-muted">${r.id}</div>
            </div>
          </div>
        </td>
        <td><span class="badge bg-light text-dark border">${r.tipo}</span></td>
        <td>${r.fecha}</td>
        <td class="text-start">${r.notas || "—"}</td>
        <td>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button"
              class="btn btn-outline-primary partidas_docs_btnPreview"
              data-bs-toggle="modal"
              data-bs-target="#modalPartidasDocsPreview"
              data-id="${r.id}"
              data-nombre="${(r.nombre||"").replace(/"/g,'&quot;')}"
              data-tipo="${r.tipo}"
              title="Previsualizar">
              <i data-feather="eye"></i>
            </button>

            <button type="button"
              class="btn btn-outline-success partidas_docs_btnDescargar"
              data-id="${r.id}"
              title="Descargar">
              <i data-feather="download"></i>
            </button>

            <button type="button"
              class="btn btn-outline-danger partidas_docs_btnEliminar"
              data-id="${r.id}"
              title="Eliminar (demo)">
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    if (window.feather) window.feather.replace();
  };

  const loadFactura = () => {
    const fac = selFactura.value;
    currentRows = (DB[fac] || []).slice();
    inpBuscar.value = "";
    render(currentRows);
  };

  const applySearch = () => {
    const term = (inpBuscar.value || "").trim().toLowerCase();
    if (!term) return render(currentRows);

    const filtered = currentRows.filter(r =>
      (r.nombre || "").toLowerCase().includes(term) ||
      (r.tipo || "").toLowerCase().includes(term) ||
      (r.notas || "").toLowerCase().includes(term)
    );
    render(filtered);
  };

  const enableInputs = (enabled) => {
    inpBuscar.disabled = !enabled;
    btnAbrirUp.disabled = !enabled;
  };

  // ===== Events =====
  selFactura.addEventListener("change", () => {
    const fac = selFactura.value;
    lblFacturaTop.textContent = fac ? ("Factura: " + fac) : "Factura: —";
    enableInputs(!!fac);
    loadFactura();
  });

  inpBuscar.addEventListener("input", applySearch);
  btnRef.addEventListener("click", () => loadFactura());

  // Upload modal: set factura badge y reset
  uploadModal.addEventListener("show.bs.modal", () => {
    const fac = selFactura.value || "";
    uploadFactura.textContent = fac ? ("Factura: " + fac) : "Factura: —";
    inpFiles.value = "";
    inpNotas.value = "";
    upPrevWrap.classList.add("d-none");
    if (window.feather) window.feather.replace();
  });

  btnHideUpPrev.addEventListener("click", () => upPrevWrap.classList.add("d-none"));

  // Subir (DEMO): agrega a DB y refresca tabla
  btnSubir.addEventListener("click", () => {
    const fac = selFactura.value;
    if (!fac) return alert("Selecciona una factura.");

    const files = Array.from(inpFiles.files || []);
    if (!files.length) return alert("Selecciona al menos un archivo.");

    DB[fac] = DB[fac] || [];
    const notas = (inpNotas.value || "").trim();

    const inserted = [];

    files.forEach((f) => {
      const row = {
        id: "DEMO-" + String(Date.now()).slice(-6) + "-" + Math.floor(Math.random() * 90 + 10),
        nombre: f.name,
        tipo: extToTipo(f.name),
        fecha: new Date().toISOString().slice(0,19).replace("T"," "),
        notas: notas || "—",
        url: "#"
      };
      DB[fac].push(row);
      inserted.push(row);
    });

    // Preview demo
    upPrevJson.textContent = JSON.stringify({ factura: fac, archivos: inserted }, null, 2);
    upPrevWrap.classList.remove("d-none");

    // refrescar tabla
    loadFactura();
    if (window.feather) window.feather.replace();
  });

  // Delegación tabla: descargar / eliminar
  tbody.addEventListener("click", (ev) => {
    const btn = ev.target.closest("button");
    if (!btn) return;

    const id = btn.getAttribute("data-id");
    const fac = selFactura.value;

    if (btn.classList.contains("partidas_docs_btnDescargar")) {
      alert("Descargar (demo): " + id);
      return;
    }

    if (btn.classList.contains("partidas_docs_btnEliminar")) {
      if (!confirm("¿Eliminar este archivo? (demo)")) return;
      DB[fac] = (DB[fac] || []).filter(x => x.id !== id);
      loadFactura();
      return;
    }
  });

  // Preview modal: contenido según tipo (demo)
  previewModal.addEventListener("show.bs.modal", (ev) => {
    const btn = ev.relatedTarget;
    const tipo = btn?.getAttribute("data-tipo") || "—";
    const nombre = btn?.getAttribute("data-nombre") || "—";

    pvTipo.textContent = tipo;
    pvFactura.textContent = selFactura.value ? ("Factura " + selFactura.value) : "—";
    pvNombre.textContent = nombre;

    pvWrap.innerHTML = "";
    pvEmpty.classList.add("d-none");

    if (tipo === "PDF") {
      pvWrap.innerHTML = `<iframe src="about:blank" style="width:100%; height:60vh; border:0;"></iframe>`;
    } else if (tipo === "Imagen") {
      pvWrap.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="height:60vh;">
          <div class="text-center text-muted">
            <i data-feather="image" style="width:48px;height:48px;"></i>
            <div class="mt-2">Vista previa de imagen (demo)</div>
            <div class="small">Aquí cargarías la URL real del archivo.</div>
          </div>
        </div>
      `;
    } else {
      pvEmpty.classList.remove("d-none");
      pvWrap.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="height:60vh;">
          <div class="text-center text-muted">
            <i data-feather="file" style="width:48px;height:48px;"></i>
            <div class="mt-2">Sin vista previa para: ${tipo}</div>
            <div class="small">Usa Descargar.</div>
          </div>
        </div>
      `;
    }

    pvBtnDown.onclick = () => alert("Descargar (demo): " + nombre);

    if (window.feather) window.feather.replace();
  });

  // Init
  enableInputs(false);
  empty.classList.remove("d-none");
  render([]);
  if (window.feather) window.feather.replace();
});
</script>
