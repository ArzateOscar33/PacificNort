// Assets/Js/ModulosAdmin/operaciones_por_partida/partidas_docs.js
(function () {
  "use strict";

  // ===== Base URL (como ya manejas en tu proyecto) =====
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Endpoints (Controller: Operaciones_por_partida) =====
  const ENDPOINT_SUGERIR_FACTURAS = "Operaciones_por_partida_documentos/sugerirFacturasDocs";
  const ENDPOINT_LISTAR_DOCS      = "Operaciones_por_partida_documentos/listarDocumentosFactura";
  const ENDPOINT_TIPOS_DOC        = "Operaciones_por_partida_documentos/listarTiposDocumentoOPP";
  const ENDPOINT_ELIMINAR_DOC = "Operaciones_por_partida_documentos/eliminarDocumentoFactura";

  // TODO: cuando lo tengas:
  // const ENDPOINT_DESCARGAR_DOC = "Operaciones_por_partida/descargarDocumento";
  // const ENDPOINT_VER_DOC       = "Operaciones_por_partida/verDocumento"; // inline/preview

  // ===== Refs de la vista =====
  const inpFactura  = document.getElementById("partidas_docs_facturaInput");
  const hidFactura  = document.getElementById("partidas_docs_facturaId");
  const suggestBox  = document.getElementById("partidas_docs_facturaSuggest");

  const inpBuscar   = document.getElementById("partidas_docs_buscar");
  const btnRef      = document.getElementById("partidas_docs_btnRefrescar");
  const btnAbrirUp  = document.getElementById("partidas_docs_btnAbrirUpload");

  const tbody       = document.getElementById("partidas_docs_tbody");
  const empty       = document.getElementById("partidas_docs_empty");

  // Modal upload
  const uploadModal   = document.getElementById("modalPartidasDocsUpload");
  const uploadFactura = document.getElementById("partidas_docs_uploadFactura");
  const selTipoDoc    = document.getElementById("partidas_docs_tipoDoc");
  const inpNotas      = document.getElementById("partidas_docs_notas");
  const inpFiles      = document.getElementById("partidas_docs_inputFiles");
  const btnSubir      = document.getElementById("partidas_docs_btnSubir");

  // Modal preview
  const previewModal  = document.getElementById("modalPartidasDocsPreview");
  const pvTipo        = document.getElementById("partidas_docs_previewTipo");
  const pvFactura     = document.getElementById("partidas_docs_previewFactura");
  const pvNombre      = document.getElementById("partidas_docs_previewNombre");
  const pvWrap        = document.getElementById("partidas_docs_previewWrap");
  const pvEmpty       = document.getElementById("partidas_docs_previewEmpty");
  const pvBtnDown     = document.getElementById("partidas_docs_previewBtnDescargar");

  // ===== Estado =====
  let debounceSuggest = null;
  let debounceSearch  = null;
  let xhrSuggest      = null;
  let xhrDocs         = null;
  let xhrTipos        = null;

  let currentDocs = [];      // último listado recibido
  let selectedFactura = null; // {id_factura, numero_factura, ...}

  // ===== Helpers =====
  const esc = (s) =>
    String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const qs = (obj) => {
    const p = new URLSearchParams();
    Object.keys(obj).forEach((k) => {
      const v = obj[k];
      if (v !== undefined && v !== null && String(v).trim() !== "") p.set(k, v);
    });
    return p.toString();
  };

  const xhrGET = (url, onOk, onErr, abortRefSetter) => {
    const x = new XMLHttpRequest();
    x.open("GET", url, true);
    x.setRequestHeader("Accept", "application/json");
    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;
      if (x.status >= 200 && x.status < 300) {
        let json = null;
        try { json = JSON.parse(x.responseText); } catch (e) {}
        onOk && onOk(json);
      } else {
        onErr && onErr(x);
      }
    };
    x.send();
    abortRefSetter && abortRefSetter(x);
  };

  const setControlsEnabled = (enabled) => {
    inpBuscar.disabled  = !enabled;
    btnRef.disabled     = !enabled;
    btnAbrirUp.disabled = !enabled;

    // Upload modal inputs (se habilitan cuando hay factura seleccionada)
    selTipoDoc.disabled = !enabled;
    inpNotas.disabled   = !enabled;
    inpFiles.disabled   = !enabled;
    btnSubir.disabled   = !enabled;

    if (!enabled) {
      inpBuscar.value = "";
      tbody.innerHTML = "";
      empty.classList.remove("d-none");
    }
  };

  const hideSuggest = () => {
    suggestBox.classList.add("d-none");
    suggestBox.innerHTML = "";
  };

  const showLoadingSuggest = () => {
    suggestBox.classList.remove("d-none");
    suggestBox.innerHTML = `
      <div class="list-group-item">
        <div class="small text-muted">Buscando...</div>
      </div>`;
  };

  // ===== Render sugerencias =====
const renderSuggest = (rows) => {
  if (!Array.isArray(rows) || !rows.length) {
    suggestBox.classList.remove("d-none");
    suggestBox.innerHTML = `
      <div class="list-group-item">
        <div class="small text-muted">Sin resultados.</div>
      </div>`;
    return;
  }

  suggestBox.classList.remove("d-none");
  suggestBox.innerHTML = rows.map((r) => {
    const id    = r.id_factura ?? "";
    const folio = r.numero_factura ?? "";    // <- AQUÍ
    const prov  = r.proveedor ?? "";
    const bod   = r.bodega ?? "";

    return `
      <button type="button"
        class="list-group-item list-group-item-action"
        data-id="${esc(id)}"
        data-folio="${esc(folio)}"
        data-proveedor="${esc(prov)}"
        data-bodega="${esc(bod)}">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fw-semibold">${esc(folio || ("Factura " + id))}</span>
          ${bod ? `<span class="badge bg-light text-dark border">${esc(bod)}</span>` : ""}
        </div>
        <div class="small text-muted">${prov ? esc(prov) : "—"}</div>
      </button>
    `;
  }).join("");

  if (window.feather) window.feather.replace();
};


const setSelectedFactura = (obj) => {
  selectedFactura = obj;

  const id = String(obj.id_factura || "");
  const folio = String(obj.numero_factura || "");

  hidFactura.value = id;
  inpFactura.value = folio;  // <- el usuario ve el número/folio, no el id

  hideSuggest();
  setControlsEnabled(true);
  loadDocumentos();
};


  // ===== Buscar facturas (autocomplete) =====
  const buscarFacturas = () => {
    const term = (inpFactura.value || "").trim();
    // si el usuario está escribiendo algo diferente, limpiar selección previa
    hidFactura.value = "";
    selectedFactura = null;
    setControlsEnabled(false);

    if (term.length < 2) {
      hideSuggest();
      return;
    }

    if (xhrSuggest) xhrSuggest.abort();
    showLoadingSuggest();

    const url = base_url + ENDPOINT_SUGERIR_FACTURAS + "?" + qs({ term, limit: 10 });

    xhrGET(
      url,
      (json) => {
        if (!json || json.ok !== true) {
          renderSuggest([]);
          return;
        }
        renderSuggest(json.data || []);
        if (window.feather) window.feather.replace();
      },
      () => renderSuggest([]),
      (x) => (xhrSuggest = x)
    );
  };

  // ===== Listar documentos =====
  const renderDocs = (rows) => {
    tbody.innerHTML = "";

    if (!hidFactura.value) {
      empty.classList.remove("d-none");
      return;
    }

    empty.classList.add("d-none");

    if (!Array.isArray(rows) || !rows.length) {
      tbody.innerHTML = `
        <tr class="text-center">
          <td colspan="5" class="text-muted py-4">No hay documentos registrados para esta factura.</td>
        </tr>`;
      return;
    }

    rows.forEach((r) => {
      // Claves esperadas (ajusta si tu backend usa otros nombres):
      // id_documento, nombre_archivo, tipo_documento, fecha_subida, notas, ruta_archivo (o url)
      const id    = r.id_documento ?? r.id ?? "";
      const nombre= r.nombre_archivo ?? r.nombre ?? "";
      const tipo  = r.tipo_documento ?? r.tipo ?? "—";
      const fecha = r.fecha_subida ?? r.fecha ?? "—";
      const notas = r.notas ?? r.descripcion ?? "—";

      // para preview/descarga: si ya envías ruta_archivo o url:
      const urlFile = r.ruta_archivo ?? r.url ?? "";

      const tr = document.createElement("tr");
      tr.className = "text-center";
      tr.innerHTML = `
        <td class="text-start">
          <div class="d-flex align-items-center gap-2">
            <i data-feather="file"></i>
            <div>
              <div class="fw-semibold">${esc(nombre)}</div>
            </div>
          </div>
        </td>
        <td><span class="badge bg-light text-dark border">${esc(tipo)}</span></td>
        <td>${esc(fecha)}</td>
        <td class="text-start">${esc(notas || "—")}</td>
        <td>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button"
              class="btn btn-outline-primary partidas_docs_btnPreview"
              data-bs-toggle="modal"
              data-bs-target="#modalPartidasDocsPreview"
              data-id="${esc(id)}"
              data-nombre="${esc(nombre)}"
              data-tipo="${esc(tipo)}"
              data-url="${esc(urlFile)}"
              title="Previsualizar">
              <i data-feather="eye"></i>
            </button>

            <button type="button"
              class="btn btn-outline-success partidas_docs_btnDescargar"
              data-id="${esc(id)}"
              data-url="${esc(urlFile)}"
              title="Descargar">
              <i data-feather="download"></i>
            </button>

            <button type="button"
  class="btn btn-outline-danger btn-sm partidas_docs_btnEliminar"
  data-id="${esc(id)}"
  title="Eliminar">
  <i data-feather="trash-2"></i>
</button>


          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    if (window.feather) window.feather.replace();
  };

  const loadDocumentos = () => {
    const facturaId = (hidFactura.value || "").trim();
    if (!facturaId) return;

    if (xhrDocs) xhrDocs.abort();

    const term = (inpBuscar.value || "").trim();
    const url = base_url + ENDPOINT_LISTAR_DOCS + "?" + qs({ factura_id: facturaId, term });

    // Estado visual rápido
    tbody.innerHTML = `
      <tr class="text-center">
        <td colspan="5" class="text-muted py-4">Cargando documentos...</td>
      </tr>`;
    empty.classList.add("d-none");

    xhrGET(
      url,
      (json) => {
        if (!json || json.ok !== true) {
          renderDocs([]);
          return;
        }
        currentDocs = json.data || [];
        renderDocs(currentDocs);
      },
      () => renderDocs([]),
      (x) => (xhrDocs = x)
    );
  };

  // ===== Tipos de documento (para modal) =====
  const cargarTiposDocumento = () => {
    if (xhrTipos) xhrTipos.abort();

    // Cargar una sola vez por sesión (si ya tiene opciones reales, no recargar)
    const hasRealOptions = selTipoDoc && selTipoDoc.options && selTipoDoc.options.length > 1;
    if (hasRealOptions) return;

    const url = base_url + ENDPOINT_TIPOS_DOC;

    xhrGET(
      url,
      (json) => {
        if (!json || json.ok !== true) return;

        const rows = json.data || [];
        selTipoDoc.innerHTML = `<option value="">Seleccione tipo...</option>` +
          rows.map((r) => {
            const id = r.id_tipo_documento ?? r.id ?? "";
            const n  = r.nombre ?? r.name ?? "";
            return `<option value="${esc(id)}">${esc(n)}</option>`;
          }).join("");

        if (window.feather) window.feather.replace();
      },
      () => {},
      (x) => (xhrTipos = x)
    );
  };

  // ===== Preview =====
  const renderPreview = (tipo, nombre, urlFile) => {
    pvTipo.textContent    = tipo || "—";
    pvFactura.textContent = inpFactura.value ? ("Factura " + inpFactura.value) : "—";
    pvNombre.textContent  = nombre || "—";

    pvWrap.innerHTML = "";
    pvEmpty.classList.add("d-none");

    // Si tu backend entrega una URL directa, intentamos preview por extensión
    const lower = (nombre || "").toLowerCase();
    const isPDF = lower.endsWith(".pdf");
    const isImg = lower.endsWith(".jpg") || lower.endsWith(".jpeg") || lower.endsWith(".png") || lower.endsWith(".webp");

    if (!urlFile) {
      pvEmpty.classList.remove("d-none");
      pvWrap.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="height:60vh;">
          <div class="text-center text-muted">
            <i data-feather="alert-circle" style="width:48px;height:48px;"></i>
            <div class="mt-2">No hay URL del archivo para vista previa.</div>
            <div class="small">Configura un endpoint para servir/descargar el documento.</div>
          </div>
        </div>`;
      if (window.feather) window.feather.replace();
      return;
    }

    if (isPDF) {
      pvWrap.innerHTML = `<iframe src="${esc(urlFile)}" style="width:100%; height:60vh; border:0;"></iframe>`;
    } else if (isImg) {
      pvWrap.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="min-height:60vh;">
          <img src="${esc(urlFile)}" alt="${esc(nombre)}" class="img-fluid rounded border">
        </div>`;
    } else {
      pvEmpty.classList.remove("d-none");
      pvWrap.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="height:60vh;">
          <div class="text-center text-muted">
            <i data-feather="file" style="width:48px;height:48px;"></i>
            <div class="mt-2">Sin vista previa para este tipo.</div>
            <div class="small">Usa Descargar.</div>
          </div>
        </div>`;
    }

    if (window.feather) window.feather.replace();
  };

  // ===== Eventos =====
  const bindEvents = () => {
    // Input factura: debounce de sugerencias
    inpFactura.addEventListener("input", () => {
      clearTimeout(debounceSuggest);
      debounceSuggest = setTimeout(buscarFacturas, 250);
    });

    // Click en sugerencias
 suggestBox.addEventListener("click", (ev) => {
  const btn = ev.target.closest("button.list-group-item");
  if (!btn) return;

  setSelectedFactura({
    id_factura: btn.getAttribute("data-id"),
    numero_factura: btn.getAttribute("data-folio"),
    proveedor: btn.getAttribute("data-proveedor"),
    bodega: btn.getAttribute("data-bodega")
  });
});


    // Cerrar sugerencias al click fuera
    document.addEventListener("click", (ev) => {
      if (!ev.target.closest("#partidas_docs_facturaSuggest") &&
          !ev.target.closest("#partidas_docs_facturaInput")) {
        hideSuggest();
      }
    });

    // Buscar documentos: debounce (server-side term)
    inpBuscar.addEventListener("input", () => {
      clearTimeout(debounceSearch);
      debounceSearch = setTimeout(loadDocumentos, 250);
    });

    // Refrescar
    btnRef.addEventListener("click", (ev) => {
      ev.preventDefault();
      loadDocumentos();
    });

    // Tabla: Descargar
    tbody.addEventListener("click", (ev) => {
      const btn = ev.target.closest("button");
      if (!btn) return;

      if (btn.classList.contains("partidas_docs_btnDescargar")) {
        const urlFile = btn.getAttribute("data-url") || "";
        const id = btn.getAttribute("data-id") || "";

        if (urlFile) {
          // Si ya es una URL pública/servida por tu backend, descarga directa
          window.open(urlFile, "_blank");
          return;
        }

        // TODO: si quieres descarga por endpoint:
        // window.open(base_url + ENDPOINT_DESCARGAR_DOC + "?" + qs({ id_documento: id }), "_blank");
        alert("No hay URL/endpoint de descarga configurado para el documento: " + id);
      }
    });

    // Modal Upload: al abrir, setear badge + cargar tipos
    if (uploadModal) {
      uploadModal.addEventListener("show.bs.modal", () => {
       

        // Tipos documento
        cargarTiposDocumento();

        // reset inputs
        if (selTipoDoc) selTipoDoc.value = "";
        if (inpNotas) inpNotas.value = "";
        if (inpFiles) inpFiles.value = "";

        if (window.feather) window.feather.replace();
      });
    }

    // Modal Preview: armar preview desde data-attrs del botón
    if (previewModal) {
      previewModal.addEventListener("show.bs.modal", (ev) => {
        const trigger = ev.relatedTarget;
        const tipo = trigger?.getAttribute("data-tipo") || "—";
        const nombre = trigger?.getAttribute("data-nombre") || "—";
        const urlFile = trigger?.getAttribute("data-url") || "";

        renderPreview(tipo, nombre, urlFile);

        pvBtnDown.onclick = () => {
          if (urlFile) window.open(urlFile, "_blank");
          else alert("No hay URL/endpoint de descarga configurado.");
        };
      });
    }
  };

  // ===== Init =====
  const init = () => {
    setControlsEnabled(false);
    hideSuggest();
    empty.classList.remove("d-none");
    tbody.innerHTML = "";

    // UX: al enfocar input factura, si hay texto, reintenta sugerencias
    inpFactura.addEventListener("focus", () => {
      const term = (inpFactura.value || "").trim();
      if (term.length >= 2 && suggestBox.classList.contains("d-none")) {
        buscarFacturas();
      }
    });

    bindEvents();

    if (window.feather) window.feather.replace();
  };

  document.addEventListener("DOMContentLoaded", init);

  const tbodyDocs = document.getElementById("partidas_docs_tbody");

function eliminarDocumento(idDocumento) {
  if (!idDocumento || idDocumento <= 0) {
    toastSwal("warning", "Documento", "ID inválido.");
    return;
  }

  // Confirmación
  if (typeof Swal !== "undefined" && Swal.fire) {
    Swal.fire({
      icon: "warning",
      title: "Eliminar documento",
      text: "Esto eliminará el archivo del servidor y el registro en la base de datos. ¿Deseas continuar?",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar"
    }).then((r) => {
      if (r.isConfirmed) ejecutarEliminacion(idDocumento);
    });
  } else {
    if (confirm("Se eliminará el documento del servidor y de la base de datos. ¿Continuar?")) {
      ejecutarEliminacion(idDocumento);
    }
  }
}


function toastSwal(icon, title, text) {
  if (typeof Swal !== "undefined" && Swal.fire) {
    Swal.fire({ icon, title, text, timer: 2200, showConfirmButton: false });
    return;
  }
  alert((title ? title + ": " : "") + (text || ""));
}

function eliminarDocumento(idDocumento) {
  idDocumento = parseInt(idDocumento || "0", 10);
  if (!idDocumento || idDocumento <= 0) {
    toastSwal("warning", "Documento", "ID inválido.");
    return;
  }

  const confirmar = () => ejecutarEliminacion(idDocumento);

  if (typeof Swal !== "undefined" && Swal.fire) {
    Swal.fire({
      icon: "warning",
      title: "Eliminar documento",
      text: "Se eliminará el archivo del servidor y el registro en la base de datos. ¿Deseas continuar?",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar"
    }).then((r) => { if (r.isConfirmed) confirmar(); });
  } else {
    if (confirm("Se eliminará el documento del servidor y de la base de datos. ¿Continuar?")) confirmar();
  }
}

function ejecutarEliminacion(idDocumento) {
  const fd = new FormData();
  fd.append("id_documento", String(idDocumento));

  const xhr = new XMLHttpRequest();
  xhr.open("POST", base_url + ENDPOINT_ELIMINAR_DOC, true);

  xhr.onreadystatechange = function () {
    if (xhr.readyState !== 4) return;

    if (xhr.status !== 200) {
      toastSwal("error", "Eliminar", "Error HTTP al eliminar documento.");
      return;
    }

    let json = null;
    try { json = JSON.parse(xhr.responseText); } catch (e) {}

    if (!json) {
      toastSwal("error", "Eliminar", "Respuesta inválida del servidor.");
      return;
    }

    const status = String(json.status || "").toLowerCase();

    if (status === "success") {
      toastSwal("success", "Listo", json.msg || "Documento eliminado.");
      // refresca listado local
      loadDocumentos();
      return;
    }

    if (status === "warning") {
      toastSwal("warning", "Atención", json.msg || "No se pudo eliminar.");
      return;
    }

    toastSwal("error", "Error", json.msg || "No se pudo eliminar.");
  };

  xhr.send(fd);
}

// Delegación: click en el botón trash
tbody.addEventListener("click", function (e) {
  const btn = e.target.closest(".partidas_docs_btnEliminar");
  if (!btn) return;
  const id = btn.getAttribute("data-id");
  eliminarDocumento(id);
});


// Delegación de eventos
if (tbodyDocs) {
  tbodyDocs.addEventListener("click", function (e) {
    const btn = e.target.closest(".partidas_docs_btnEliminar");
    if (!btn) return;

    const id = parseInt(btn.getAttribute("data-id") || "0", 10);
    eliminarDocumento(id);
  });
}

})();
