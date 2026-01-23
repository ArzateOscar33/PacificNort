 // Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_documentos_registrar.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Endpoints (Controller: Operaciones_por_partida) =====
  const ENDPOINT_TIPOS_DOC = "Operaciones_por_partida_documentos/listarTiposDocumentoOPP";
  const ENDPOINT_REGISTRAR_DOCS = "Operaciones_por_partida_documentos/registrarDocumentosFactura";

  // ===== Refs UI =====
  const inpFactura     = document.getElementById("partidas_docs_facturaInput");
  const inpFacturaId   = document.getElementById("partidas_docs_facturaId");
  const btnAbrirUpload = document.getElementById("partidas_docs_btnAbrirUpload");
  const btnRefrescar   = document.getElementById("partidas_docs_btnRefrescar");

  // Modal upload
  const modalUploadEl  = document.getElementById("modalPartidasDocsUpload");
  const formUpload     = document.getElementById("partidas_docs_formUpload");
  const selTipoDoc     = document.getElementById("partidas_docs_tipoDoc");
  const inpNotas       = document.getElementById("partidas_docs_notas");
  const inpFiles       = document.getElementById("partidas_docs_inputFiles");
  const btnSubir       = document.getElementById("partidas_docs_btnSubir");

  // Preview demo (opcional)
  const previewWrap    = document.getElementById("partidas_docs_uploadPreviewWrap");
  const previewJson    = document.getElementById("partidas_docs_uploadPreviewJson");
  const btnOcultarPrev = document.getElementById("partidas_docs_btnOcultarUploadPreview");

  // Estado
  let tiposDocCargados = false;
  let xhrTiposDoc = null;
  let xhrUpload = null;

  // ===== Helpers =====
  function esc(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function toastSwal(icon, title, text) {
    if (typeof Swal !== "undefined" && Swal.fire) {
      Swal.fire({ icon, title, text, timer: 2200, showConfirmButton: false });
      return;
    }
    // fallback
    alert((title ? title + ": " : "") + (text || ""));
  }

  function setEnabled(el, enabled) {
    if (!el) return;
    el.disabled = !enabled;
  }

  function getFacturaId() {
    const v = parseInt(inpFacturaId?.value || "0", 10);
    return Number.isFinite(v) ? v : 0;
  }

  function hasFilesSelected() {
    return inpFiles && inpFiles.files && inpFiles.files.length > 0;
  }

  function validarForm() {
    const facturaId = getFacturaId();
    if (facturaId <= 0) return { ok: false, msg: "Selecciona una factura válida." };

    const tipo = parseInt(selTipoDoc?.value || "0", 10);
    if (!tipo || tipo <= 0) return { ok: false, msg: "Selecciona un tipo de documento." };

    if (!hasFilesSelected()) return { ok: false, msg: "Selecciona al menos un archivo." };

    return { ok: true, msg: "" };
  }

  function updateUploadControlsState() {
    const facturaId = getFacturaId();
    const hasFactura = facturaId > 0;

    // Habilita botones globales si hay factura
    setEnabled(btnAbrirUpload, hasFactura);
    setEnabled(btnRefrescar, hasFactura);

    // En el modal: habilita campos si hay factura
    setEnabled(selTipoDoc, hasFactura);
    setEnabled(inpNotas, hasFactura);
    setEnabled(inpFiles, hasFactura);

    // Botón subir requiere factura + tipo + archivos
    const tipo = parseInt(selTipoDoc?.value || "0", 10);
    const canUpload = hasFactura && tipo > 0 && hasFilesSelected();
    setEnabled(btnSubir, canUpload);
  }

  function limpiarModalUpload() {
    if (formUpload) formUpload.reset();
    if (previewWrap) previewWrap.classList.add("d-none");
    if (previewJson) previewJson.textContent = "";
    // Importante: tipoDoc se queda con "Seleccione..." (form reset lo hace)
    updateUploadControlsState();
  }

  function refrescarListadoDocumentos() {
    // 1) Si tu catálogo expone una función global
    if (typeof window.partidasDocsCatalogoRefrescar === "function") {
      window.partidasDocsCatalogoRefrescar();
      return;
    }
    if (typeof window.refrescarDocumentosPartidas === "function") {
      window.refrescarDocumentosPartidas();
      return;
    }

    // 2) fallback: disparar click en botón refrescar si tu catálogo lo escucha
    if (btnRefrescar) {
      btnRefrescar.click();
    }
  }

  // ===== Cargar tipos de documento =====
  function cargarTiposDocumentoOPP() {
    if (tiposDocCargados) return;
    if (!selTipoDoc) return;

    // Abort previa
    try { if (xhrTiposDoc) xhrTiposDoc.abort(); } catch (e) {}

    xhrTiposDoc = new XMLHttpRequest();
    xhrTiposDoc.open("GET", base_url + ENDPOINT_TIPOS_DOC, true);

    xhrTiposDoc.onreadystatechange = function () {
      if (xhrTiposDoc.readyState !== 4) return;

      if (xhrTiposDoc.status !== 200) {
        toastSwal("error", "Tipos de documento", "No se pudieron cargar los tipos de documento.");
        return;
      }

      let json = null;
      try { json = JSON.parse(xhrTiposDoc.responseText); } catch (e) {}

      if (!json || !json.ok) {
        toastSwal("error", "Tipos de documento", json?.msg || "Respuesta inválida al cargar tipos.");
        return;
      }

      const rows = Array.isArray(json.data) ? json.data : [];
      // Render options
      let html = '<option value="">Seleccione tipo...</option>';
      for (const r of rows) {
        html += `<option value="${esc(r.id_tipo_documento)}">${esc(r.nombre)}</option>`;
      }
      selTipoDoc.innerHTML = html;

      tiposDocCargados = true;
      updateUploadControlsState();
    };

    xhrTiposDoc.send(null);
  }

  // ===== Registrar (upload) =====
  function subirDocumentos() {
    const v = validarForm();
    if (!v.ok) {
      toastSwal("warning", "Validación", v.msg);
      return;
    }

    const facturaId = getFacturaId();
    const tipoId = parseInt(selTipoDoc.value, 10);
    const notas = String(inpNotas?.value || "").trim();

    const fd = new FormData();
    fd.append("factura_id", String(facturaId));
    fd.append("tipo_documento_id", String(tipoId));
    fd.append("notas", notas);

    // Tu controlador acepta 'files' o 'archivo' y lo normaliza.
    // Usamos 'files[]' para multi.
    for (let i = 0; i < inpFiles.files.length; i++) {
      fd.append("files[]", inpFiles.files[i]);
    }

    // Abort previa
    try { if (xhrUpload) xhrUpload.abort(); } catch (e) {}

    setEnabled(btnSubir, false);

    xhrUpload = new XMLHttpRequest();
    xhrUpload.open("POST", base_url + ENDPOINT_REGISTRAR_DOCS, true);

    xhrUpload.onreadystatechange = function () {
      if (xhrUpload.readyState !== 4) return;
        //console.log(this.responseText);
      // re-habilitar según estado actual
      updateUploadControlsState();

      if (xhrUpload.status !== 200) {
        toastSwal("error", "Subida", "Error HTTP al subir documentos.");
        return;
      }

      let json = null;
      try { json = JSON.parse(xhrUpload.responseText); } catch (e) {}

      if (!json) {
        toastSwal("error", "Subida", "Respuesta inválida del servidor.");
        return;
      }

      // Tu endpoint de registrar docs devuelve: status (success|warning|error)
      const status = String(json.status || "").toLowerCase();

      if (status === "success") {
        toastSwal("success", "Listo", json.msg || "Documento(s) subido(s) correctamente.");

        // Demo preview (opcional)
        if (previewWrap && previewJson) {
          previewJson.textContent = JSON.stringify(json, null, 2);
          previewWrap.classList.remove("d-none");
        }

        // Cierra modal y refresca tabla
        try {
          const modal = bootstrap.Modal.getInstance(modalUploadEl) || new bootstrap.Modal(modalUploadEl);
          modal.hide();
        } catch (e) {
          // si falla bootstrap, al menos limpiamos
        }

        limpiarModalUpload();
        refrescarListadoDocumentos();
        return;
      }

      if (status === "warning") {
        toastSwal("warning", "Atención", json.msg || "Revisa los datos.");
        return;
      }

      // status === "error" o cualquier otro
      toastSwal("error", "Error", json.msg || "No se pudo subir.");
      // Si el backend manda errors por archivo, lo mostramos en consola para debug
      if (Array.isArray(json.errors) && json.errors.length) {
        console.warn("Errores por archivo:", json.errors);
      }
    };

    xhrUpload.send(fd);
  }

  // ===== Events =====
  // Habilitación dinámica
  if (selTipoDoc) {
    selTipoDoc.addEventListener("change", updateUploadControlsState);
  }
  if (inpFiles) {
    inpFiles.addEventListener("change", updateUploadControlsState);
  }
  if (inpFacturaId) {
    // Cuando tu catálogo seleccione factura y setee el hidden, este input cambia.
    // Si tu catálogo no dispara change, puedes dispararlo ahí.
    inpFacturaId.addEventListener("change", function () {
      updateUploadControlsState();
      // Opcional: precargar tipos
      cargarTiposDocumentoOPP();
    });
  }

  // Abrir modal: valida que hay factura, carga tipos, resetea form
  if (btnAbrirUpload && modalUploadEl) {
    btnAbrirUpload.addEventListener("click", function () {
      const facturaId = getFacturaId();
      if (facturaId <= 0) {
        toastSwal("warning", "Factura", "Selecciona una factura antes de subir documentos.");
        return;
      }
      cargarTiposDocumentoOPP();
      limpiarModalUpload();
    });
  }

  // Al mostrarse el modal (por si el usuario lo abre por data-bs-target)
  if (modalUploadEl) {
    modalUploadEl.addEventListener("shown.bs.modal", function () {
      const facturaId = getFacturaId();
      if (facturaId > 0) {
        cargarTiposDocumentoOPP();
      }
      updateUploadControlsState();
    });

    modalUploadEl.addEventListener("hidden.bs.modal", function () {
      limpiarModalUpload();
    });
  }

  // Botón subir
  if (btnSubir) {
    btnSubir.addEventListener("click", subirDocumentos);
  }

  // Ocultar preview demo
  if (btnOcultarPrev && previewWrap) {
    btnOcultarPrev.addEventListener("click", function () {
      previewWrap.classList.add("d-none");
    });
  }

  // Refrescar (botón global)
  if (btnRefrescar) {
    btnRefrescar.addEventListener("click", function () {
      const facturaId = getFacturaId();
      if (facturaId <= 0) {
        toastSwal("warning", "Factura", "Selecciona una factura para refrescar.");
        return;
      }
      refrescarListadoDocumentos();
    });
  }

  // Estado inicial
  updateUploadControlsState();
})();
