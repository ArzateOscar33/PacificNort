// =============== Portal Clientes: Documentos MAR/LBMF (solo listar + subir) ===============
(function () {
  "use strict";

  // ===== Refs modal =====
  const modalEl = document.getElementById("modalDocs");
  if (!modalEl) return;

  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  // Endpoints Portal Clientes
  const EP_LIST = BASE_URL + "PortalClientes/listarDocsOperacion";
  const EP_UPLOAD = BASE_URL + "PortalClientes/subirDocOperacion"; // si tu endpoint se llama distinto, cambia aquí

  // Hidden refs
  const opIdEl = document.getElementById("docsOperacionId");
  const tipoOpEl = document.getElementById("docsTipoOperacion");
  const opNumEl = document.getElementById("docsOperacionNumero");

  // UI upload
  const selTipo = document.getElementById("docsTipo");
  const inpFile = document.getElementById("docsArchivo");
  const btnSubir = document.getElementById("btnDocsSubir");

  // Lista
  const list = document.getElementById("docsList");

  // -------- Helpers (similar a tu estilo) --------
  const safe = (v) => (v == null ? "" : String(v));
  const esc = (s) =>
    String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/'/g, "&#39;");

  const fmtFecha = (val) => {
    if (!val) return "";
    const d = new Date(String(val).replace(" ", "T"));
    return isNaN(d) ? safe(val) : d.toLocaleString();
  };

  function normalizeUrl(ruta) {
    const r = String(ruta || "").trim();
    if (!r) return "#";
    if (/^https?:\/\//i.test(r)) return r;
    if (r.startsWith("/")) return BASE_URL.replace(/\/+$/, "") + r;
    return BASE_URL + r;
  }

  function setLoading() {
    if (!list) return;
    list.innerHTML = `
      <div class="list-group-item d-flex align-items-center gap-2">
        <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
        <div class="small pn-muted">Cargando documentos...</div>
      </div>
    `;
  }

  function setEmpty() {
    if (!list) return;
    list.innerHTML = `
      <div class="list-group-item">
        <div class="small pn-muted">Aún no hay documentos cargados para esta operación.</div>
      </div>
    `;
  }

  function setError(msg) {
    if (!list) return;
    list.innerHTML = `
      <div class="list-group-item">
        <div class="small text-danger">${esc(msg || "No se pudo cargar documentos.")}</div>
      </div>
    `;
  }

  function renderList(rows) {
    if (!list) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      setEmpty();
      return;
    }

    list.innerHTML = rows
      .map((r) => {
        const tipoNombre = safe(r.tipo_nombre || r.tipo_clave || "Documento");
        const clave = safe(r.tipo_clave || "");
        const nombre = safe(r.nombre_archivo || "archivo");
        const fecha = fmtFecha(r.fecha_subida);
        const subido = safe(r.subido_por || "");
        const cont = safe(r.contenedor || r.contenedor_maritimo || "");
        const url = normalizeUrl(r.ruta_archivo);

        return `
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3"
             href="${esc(url)}" target="_blank" rel="noopener">
            <div>
              <div class="fw-semibold">
                ${esc(tipoNombre)}
 
              </div>
              <div class="small pn-muted">${esc(nombre)}</div>
              <div class="small pn-muted">
                ${fecha ? `Subido: ${esc(fecha)}` : ""}
                ${cont ? ` · Cont: ${esc(cont)}` : ""}
              </div>
            </div>
            <div class="text-nowrap small pn-muted">
            <button type="button" class="btn btn-sm btn-outline-secondary p-2">
              Ver <i data-feather="external-link" class="ms-1"></i>
              </button>
            </div>
          </a>
        `;
      })
      .join("");

    if (window.feather) feather.replace();
  }

  // -------- XHR Listar --------
  function listarDocs() {
    const opId = parseInt(opIdEl?.value || "0", 10) || 0;
    if (!opId) {
      setEmpty();
      return;
    }

    setLoading();

    const http = new XMLHttpRequest();
    http.open("POST", EP_LIST, true);
    http.setRequestHeader(
      "Content-Type",
      "application/x-www-form-urlencoded; charset=UTF-8",
    );
    http.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        setError("Error HTTP " + this.status);
        return;
      }

      let json = null;
      try {
        json = JSON.parse(this.responseText);
      } catch {
        setError("Respuesta inválida del servidor.");
        return;
      }

      if (!json || json.ok !== true) {
        setError(json?.msg || "No se pudieron cargar los documentos.");
        return;
      }

      renderList(json.rows || []);
    };

    http.onerror = function () {
      setError("Error de red al cargar documentos.");
    };

    http.send("id_operacion=" + encodeURIComponent(String(opId)));
  }

  // -------- XHR Subir (opcional; ya que tu modal lo trae) --------
  function subirDoc() {
    const opId = parseInt(opIdEl?.value || "0", 10) || 0;
    const tipoOp = safe(tipoOpEl?.value || "MAR").trim(); // MAR|LBMF
    const tipoDoc = safe(selTipo?.value || "").trim();
    const file = inpFile?.files?.[0] || null;

    if (!opId) return alert("Operación inválida.");
    if (!tipoDoc) return alert("Selecciona un tipo de documento.");
    if (!file) return alert("Selecciona un archivo.");

    // Validación básica extensión (servidor valida lo real)
    const name = (file.name || "").toLowerCase();
    if (!/\.(pdf|png|jpe?g)$/i.test(name)) {
      return alert("Archivo no permitido. Usa PDF/JPG/PNG.");
    }

    if (btnSubir) btnSubir.disabled = true;

    const fd = new FormData();
    fd.append("id_operacion", String(opId));
    fd.append("tipo_operacion", tipoOp);
    fd.append("tipo_documento", tipoDoc);
    fd.append("archivo", file);

    const http = new XMLHttpRequest();
    http.open("POST", EP_UPLOAD, true);
    http.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (btnSubir) btnSubir.disabled = false;

      if (this.status !== 200) {
        alert("No se pudo subir el documento (HTTP " + this.status + ").");
        return;
      }

      let json = null;
      try {
        json = JSON.parse(this.responseText);
      } catch {
        alert("Respuesta inválida del servidor.");
        return;
      }

      if (!json || json.ok !== true) {
        alert(json?.msg || "No se pudo subir el documento.");
        return;
      }

      // reset y refrescar
      if (inpFile) inpFile.value = "";
      listarDocs();
    };

    http.onerror = function () {
      if (btnSubir) btnSubir.disabled = false;
      alert("Error de red al subir documento.");
    };

    http.send(fd);
  }

  // ===== Eventos =====
  // 1) Al abrir el modal, listar
  modalEl.addEventListener("shown.bs.modal", function () {
    listarDocs();
  });

  // 2) Botón subir
  btnSubir?.addEventListener("click", function () {
    subirDoc();
  });

  // 3) Limpieza al cerrar
  modalEl.addEventListener("hidden.bs.modal", function () {
    if (opIdEl) opIdEl.value = "0";
    if (tipoOpEl) tipoOpEl.value = "MAR";
    if (opNumEl) opNumEl.textContent = "—";
    if (inpFile) inpFile.value = "";
    if (list) list.innerHTML = "";
    if (btnSubir) btnSubir.disabled = false;
  });

  // (Opcional) exponer refresco por si lo quieres llamar desde otro módulo
  window.PNDocs = window.PNDocs || {};
  window.PNDocs.refresh = listarDocs;
})();
