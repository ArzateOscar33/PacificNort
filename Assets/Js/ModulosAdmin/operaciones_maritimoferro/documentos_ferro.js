// ============================================================
// Gestión de Documentos MF - Vista + Modal
// Controller base: Operaciones_maritimo_ferro_documentos/
// Lógica actual: SOLO contenedor marítimo
// ============================================================

(function () {
  "use strict";

  const root = document.getElementById("documentosRoot");
  if (!root) return;

  const docBase = base_url + "Operaciones_maritimo_ferro_documentos/";

  // ---------- Refs (vista) ----------
  const opIdInput = document.getElementById("documentosFiltroOpId");
  const opNombreInput = document.getElementById("documentosFiltroOpNombre");
  const opSugBox = document.getElementById("documentosFiltroOpSugerencias");

  // OJO: en la vista está escrito "Contendor" (sin 'a').
  const contIdInput = document.getElementById("documentosFiltroContendorId");
  const contNomInput = document.getElementById(
    "documentosFiltroContendorNombre",
  );
  const contSugBox = document.getElementById(
    "documentosFiltroContenedorSugerencias",
  );

  // Tabla + listas
  const tbody = document.getElementById("tablaDocumentos");
  const listaSubidos = document.getElementById("listaDocumentos");
  const listaFaltantes = document.getElementById("listaFaltantesDocumentos");
  const btnNotificar = document.getElementById("btnNotificarFaltantes");

  // ---------- Helpers ----------
  const clear = (el) => {
    if (el) el.innerHTML = "";
  };

  const show = (el, v) => {
    if (el) el.style.display = v ? "block" : "none";
  };

  const safe = (v) => (v == null ? "" : String(v));

  const TD_EMPTY = (cols) =>
    `<tr><td colspan="${cols}" class="text-center text-muted py-3">Sin resultados</td></tr>`;

  const fmtFecha = (val) => {
    if (!val) return "";
    const d = new Date(String(val).replace(" ", "T"));
    return isNaN(d) ? String(val) : d.toLocaleString();
  };

  const escAttr = (s) =>
    String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/'/g, "&#39;");

  function getJSON(url) {
    return new Promise((resolve) => {
      const x = new XMLHttpRequest();
      x.open("GET", url, true);
      x.onload = () => {
        try {
          resolve(JSON.parse(x.responseText || "[]"));
        } catch {
          resolve([]);
        }
      };
      x.onerror = () => resolve([]);
      x.send();
    });
  }

  // ---------- Autocomplete de Operación ----------
  opNombreInput?.addEventListener("keyup", function () {
    const term = this.value.trim();
    clear(opSugBox);

    if (term === "") {
      show(opSugBox, false);
      return;
    }

    const http = new XMLHttpRequest();
    http.open(
      "GET",
      docBase + "buscarOperaciones?term=" + encodeURIComponent(term),
      true,
    );
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;
      if (this.status !== 200) {
        console.warn("buscarOperaciones:", this.status);
        return;
      }

      let data = [];
      try {
        data = JSON.parse(this.responseText) || [];
      } catch {}

      if (!Array.isArray(data) || data.length === 0) {
        show(opSugBox, false);
        return;
      }

      data.slice(0, 10).forEach((o) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.innerHTML = `
          <div class="d-flex justify-content-between">
            <span>${escAttr(o.label)}</span>
          </div>
        `;
        btn.onclick = () => seleccionarOperacion(o.id, o.label);
        opSugBox.appendChild(btn);
      });

      show(opSugBox, true);
    };
  });

  // Reset si limpian operación a mano
  opNombreInput?.addEventListener("input", () => {
    if ((opNombreInput.value || "").trim() === "") {
      opIdInput.value = "";
      limpiarContenedorVista();
      renderListadoVacio();
    }
  });

  async function seleccionarOperacion(id, label) {
    opIdInput.value = String(id);
    opNombreInput.value = label;

    clear(opSugBox);
    show(opSugBox, false);

    await autollenarContenedorPorOperacion(id);
    listarDocumentos();
    cargarFaltantes();
  }

  // Cerrar sugerencias con clic fuera
  document.addEventListener("click", (e) => {
    if (
      opSugBox &&
      !opSugBox.contains(e.target) &&
      !opNombreInput.contains(e.target)
    ) {
      show(opSugBox, false);
    }
    if (
      contSugBox &&
      !contSugBox.contains(e.target) &&
      !contNomInput.contains(e.target)
    ) {
      show(contSugBox, false);
    }
  });

  // ---------- Autocomplete de Contenedor ----------
  contNomInput?.addEventListener("keyup", async function () {
    const opId = (opIdInput.value || "").trim();
    const term = (this.value || "").trim();
    clear(contSugBox);

    if (!opId) {
      show(contSugBox, false);
      return;
    }

    const data = await getJSON(
      docBase + "contenedoresPorOperacion/" + encodeURIComponent(opId),
    );

    if (!Array.isArray(data) || data.length === 0) {
      show(contSugBox, false);
      return;
    }

    const filt = term
      ? data.filter((it) =>
          (it.label || "").toLowerCase().includes(term.toLowerCase()),
        )
      : data;

    filt.slice(0, 10).forEach((it) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className =
        "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
      btn.innerHTML = `
        <span>${escAttr(it.label || "CONT " + it.id)}</span>
        <span class="badge bg-info">M</span>
      `;
      btn.onclick = () =>
        seleccionarContenedor(it.id, it.label || String(it.id));
      contSugBox.appendChild(btn);
    });

    show(contSugBox, true);
  });

  function seleccionarContenedor(id, etiqueta) {
    contIdInput.value = String(id);
    contNomInput.value = String(etiqueta);
    contNomInput.setAttribute("readonly", "readonly");
    contNomInput.placeholder = "";
    clear(contSugBox);
    show(contSugBox, false);

    listarDocumentos();
    cargarFaltantes();
  }

  function limpiarContenedorVista() {
    if (!contIdInput || !contNomInput) return;

    contIdInput.value = "";
    contNomInput.value = "";
    contNomInput.removeAttribute("readonly");
    contNomInput.placeholder = "Selecciona una operación primero";
    clear(contSugBox);
    show(contSugBox, false);

    if (btnNotificar) btnNotificar.style.display = "none";
    renderFaltantes([]);
  }

  async function autollenarContenedorPorOperacion(opId) {
    limpiarContenedorVista();

    const data = await getJSON(
      docBase + "contenedoresPorOperacion/" + encodeURIComponent(opId),
    );

    if (Array.isArray(data) && data.length > 0) {
      const it = data[0];
      contIdInput.value = String(it.id);
      contNomInput.value = it.label || String(it.id);
      contNomInput.setAttribute("readonly", "readonly");
      contNomInput.placeholder = "";
    }
  }

  // ---------- Listado / Render ----------
  function renderListadoVacio() {
    if (tbody) tbody.innerHTML = TD_EMPTY(9);
    if (listaSubidos) {
      listaSubidos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
    }
    renderFaltantes([]);
    if (window.feather) feather.replace();
  }

  function listarDocumentos() {
    const opId = (opIdInput.value || "").trim();
    const contId = (contIdInput.value || "").trim();

    if (!opId) {
      renderListadoVacio();
      return;
    }

    let url = docBase + "listar?operacion_id=" + encodeURIComponent(opId);

    if (contId) {
      url += "&contenedor_id=" + encodeURIComponent(contId);
    }

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let rows = [];
      try {
        rows = JSON.parse(this.responseText) || [];
      } catch {}

      renderTabla(rows);
      renderSubidos(rows);
      cargarFaltantes();
      if (window.feather) feather.replace();
    };
  }

  function renderTabla(rows) {
    if (!tbody) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = TD_EMPTY(9);
      return;
    }

    const html = rows
      .map(
        (r) => `
      <tr>
        <td>${safe(r.numero_operacion)}</td>
        <td>${safe(r.contenedor)}</td>
        <td>${safe(r.cliente)}</td>
        <td class="text-uppercase">${safe(r.tipo_nombre || r.tipo_clave || "")}</td>
        <td>${safe(r.nombre_archivo || "")}</td>
        <td>${fmtFecha(r.fecha_subida || "")}</td>
        <td>${safe(r.subido_por || "")}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-primary"
                  title="Ver"
                  data-nombre="${escAttr(r.nombre_archivo || "")}"
                  data-mime="${escAttr(r.mime_type || "")}"
                  onclick="documentosVerDocumentoMF(${r.id_documento})">
            <i data-feather="eye"></i>
          </button>
        </td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-danger"
                  title="Eliminar"
                  onclick="documentosEliminarDocumentoMF(${r.id_documento})">
            <i data-feather="trash-2"></i>
          </button>
        </td>
      </tr>
    `,
      )
      .join("");

    tbody.innerHTML = html;
  }

  function renderSubidos(rows) {
    if (!listaSubidos) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      listaSubidos.innerHTML = `<li class="list-group-item text-muted">No hay documentos</li>`;
      return;
    }

    listaSubidos.innerHTML = rows
      .map((r) => {
        const fecha = fmtFecha(r.fecha_subida || "");
        const tipo = r.tipo_nombre || r.tipo_clave || "";
        const nombre = r.nombre_archivo || "";
        return `<li class="list-group-item">${safe(tipo).toUpperCase()} — ${safe(nombre)} <span class="text-muted">(${fecha})</span></li>`;
      })
      .join("");
  }

  // ---------- Faltantes ----------
  function renderFaltantes(items) {
    if (!listaFaltantes) return;

    if (!Array.isArray(items) || items.length === 0) {
      listaFaltantes.innerHTML =
        '<li class="list-group-item text-muted">Sin faltantes</li>';
      if (btnNotificar) btnNotificar.style.display = "none";
      return;
    }

    listaFaltantes.innerHTML = items
      .map((it) => {
        const nom = safe(it.nombre || "");
        const clv = safe(it.clave || "");
        return `<li class="list-group-item"><strong>${nom}</strong> ${clv ? `<span class="text-muted">(${clv})</span>` : ""}</li>`;
      })
      .join("");

    if (btnNotificar) btnNotificar.style.display = "";
  }

  function cargarFaltantes() {
    const opId = (opIdInput.value || "").trim();
    const contId = (contIdInput.value || "").trim();

    if (!opId || !contId) {
      renderFaltantes([]);
      return;
    }

    const url =
      docBase +
      "faltantes?operacion_id=" +
      encodeURIComponent(opId) +
      "&contenedor_id=" +
      encodeURIComponent(contId);

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let data = [];
      try {
        data = JSON.parse(this.responseText) || [];
      } catch {}

      renderFaltantes(data);
      if (window.feather) feather.replace();
    };
  }

  // ---------- Notificar faltantes ----------
  btnNotificar?.addEventListener("click", function () {
    const opId = (opIdInput.value || "").trim();
    const contId = (contIdInput.value || "").trim();

    if (!opId || !contId) {
      Swal.fire("Aviso", "Selecciona operación y contenedor", "info");
      return;
    }

    Swal.fire({
      title: "Enviar correo al cliente",
      text: "Se enviará la lista de faltantes del contenedor seleccionado.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Enviar",
      cancelButtonText: "Cancelar",
    }).then((r) => {
      if (!r.isConfirmed) return;

      const fd = new FormData();
      fd.append("operacion_id", opId);
      fd.append("contenedor_id", contId);

      const http = new XMLHttpRequest();
      http.open("POST", docBase + "notificarFaltantes", true);
      http.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        let j = {};
        try {
          j = JSON.parse(this.responseText) || {};
        } catch {}

        Swal.fire(
          j.status === "success"
            ? "Enviado"
            : j.status === "info"
              ? "Aviso"
              : "Error",
          j.msg || "(sin mensaje)",
          j.status === "success"
            ? "success"
            : j.status === "info"
              ? "info"
              : "error",
        );
      };
      http.send(fd);
    });
  });

  // Exponer para acciones globales
  window.listarDocumentosMF = listarDocumentos;
  window.cargarFaltantesMF = cargarFaltantes;

  // ---------- Ver / Eliminar ----------
  window.documentosVerDocumentoMF = function (id) {
    const url =
      base_url +
      "Operaciones_maritimo_ferro_documentos/ver/" +
      encodeURIComponent(id);

    const iframe = document.getElementById("previewFrameDocumentos");
    const aDown = document.getElementById("previewDownloadLinkDocumentos");
    const msg = document.getElementById("previewUnavailableDocumentos");

    if (aDown) aDown.href = url + "?dl=1";

    if (msg) msg.style.display = "none";
    if (iframe) {
      iframe.style.display = "block";
      iframe.src = url;
    }

    const modalEl = document.getElementById("modalPreviewDocumentoDocumentos");
    const m = new bootstrap.Modal(modalEl);
    m.show();

    modalEl.addEventListener(
      "hidden.bs.modal",
      function _cleanup() {
        if (iframe) iframe.src = "about:blank";
        modalEl.removeEventListener("hidden.bs.modal", _cleanup);
      },
      { once: true },
    );
  };

  window.documentosEliminarDocumentoMF = function (id) {
    Swal.fire({
      title: "¿Eliminar documento?",
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then((res) => {
      if (!res.isConfirmed) return;

      const http = new XMLHttpRequest();
      http.open("POST", docBase + "eliminar/" + encodeURIComponent(id), true);
      http.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      http.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        let r = {};
        try {
          r = JSON.parse(this.responseText) || {};
        } catch {}

        Swal.fire(
          r.status === "success" ? "Eliminado" : "Aviso",
          r.msg || "(sin mensaje)",
          r.status === "success" ? "success" : "info",
        );

        if (r.status === "success") listarDocumentos();
      };
      http.send();
    });
  };

  // Boot
  (async () => {
    const opIdBoot = (opIdInput?.value || "").trim();
    if (opIdBoot) {
      await autollenarContenedorPorOperacion(opIdBoot);
      listarDocumentos();
      cargarFaltantes();
    } else {
      limpiarContenedorVista();
      renderListadoVacio();
    }
  })();
})();

// ============================================================
// Modal: Agregar Documento
// Lógica actual: SOLO contenedor marítimo
// ============================================================
(function () {
  "use strict";

  const docBase = base_url + "Operaciones_maritimo_ferro_documentos/";
  const modalEl = document.getElementById("modalAgregarDocumentoDocumentos");
  const form = document.getElementById("formAgregarDocumentoDocumentos");

  // Operación (modal)
  const mdOpId = document.getElementById("modalDocumentosOpId");
  const mdOpNom = document.getElementById("modalDocumentosOpNombre");
  const mdOpSug = document.getElementById("modalDocumentosOpSugerencias");
  const mdOpMeta = document.getElementById("modalDocumentosOpMeta");

  // Contenedor (modal)
  const mdContId = document.getElementById("modalDocumentosContId");
  const mdContTipo = document.getElementById("modalDocumentosContTipo");
  const mdContNom = document.getElementById("modalDocumentosContNombre");
  const mdContSug = document.getElementById("modalDocumentosContSugerencias");

  // Tipos
  const selTipo = document.getElementById("tipo_documentoDocumentos");

  // Prefill desde vista
  const vwOpId = document.getElementById("documentosFiltroOpId");
  const vwOpNom = document.getElementById("documentosFiltroOpNombre");
  const vwContId = document.getElementById("documentosFiltroContendorId");
  const vwContNom = document.getElementById("documentosFiltroContendorNombre");

  const clear = (el) => {
    if (el) el.innerHTML = "";
  };

  const show = (el, v) => {
    if (el) el.style.display = v ? "block" : "none";
  };

  function escAttr(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/'/g, "&#39;");
  }

  function getJSON(url) {
    return new Promise((resolve) => {
      const x = new XMLHttpRequest();
      x.open("GET", url, true);
      x.onload = () => {
        try {
          resolve(JSON.parse(x.responseText || "[]"));
        } catch {
          resolve([]);
        }
      };
      x.onerror = () => resolve([]);
      x.send();
    });
  }

  modalEl?.addEventListener("show.bs.modal", async () => {
    mdOpId.value = vwOpId?.value || "";
    mdOpNom.value = vwOpNom?.value || "";
    mdOpMeta.textContent = mdOpNom.value ? `Operación ${mdOpNom.value}` : "";

    mdContId.value = vwContId?.value || "";
    mdContNom.value = vwContNom?.value || "";
    mdContTipo.value = "M";

    if (!mdContId.value && mdOpId.value) {
      const data = await getJSON(
        docBase +
          "contenedoresPorOperacion/" +
          encodeURIComponent(mdOpId.value),
      );
      if (Array.isArray(data) && data.length > 0) {
        const it = data[0];
        mdContId.value = String(it.id);
        mdContNom.value = it.label || String(it.id);
        mdContTipo.value = "M";
      }
    }

    cargarTiposSegunContenedor();
  });

  modalEl?.addEventListener("hidden.bs.modal", () => {
    form?.reset();
    clear(mdOpSug);
    show(mdOpSug, false);
    clear(mdContSug);
    show(mdContSug, false);
    if (mdContTipo) mdContTipo.value = "M";
  });

  // Autocomplete operación modal
  mdOpNom?.addEventListener("keyup", function () {
    const term = this.value.trim();
    clear(mdOpSug);

    if (term === "") {
      show(mdOpSug, false);
      return;
    }

    const http = new XMLHttpRequest();
    http.open(
      "GET",
      docBase + "buscarOperaciones?term=" + encodeURIComponent(term),
      true,
    );
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let data = [];
      try {
        data = JSON.parse(this.responseText) || [];
      } catch {}

      if (!Array.isArray(data) || data.length === 0) {
        show(mdOpSug, false);
        return;
      }

      data.slice(0, 10).forEach((o) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.innerHTML = `
          <div class="d-flex justify-content-between">
            <span>${escAttr(o.label)}</span>
          </div>
        `;
        btn.onclick = () => {
          mdOpId.value = String(o.id);
          mdOpNom.value = o.label;
          mdOpMeta.textContent = `Operación ${o.label}`;
          clear(mdOpSug);
          show(mdOpSug, false);
          autollenarContenedorModal(mdOpId.value);
        };
        mdOpSug.appendChild(btn);
      });

      show(mdOpSug, true);
    };
  });

  // Autocomplete contenedor modal
  mdContNom?.addEventListener("keyup", async function () {
    const opId = (mdOpId.value || "").trim();
    const term = (this.value || "").trim();
    clear(mdContSug);

    if (!opId) {
      show(mdContSug, false);
      return;
    }

    const data = await getJSON(
      docBase + "contenedoresPorOperacion/" + encodeURIComponent(opId),
    );

    if (!Array.isArray(data) || data.length === 0) {
      show(mdContSug, false);
      return;
    }

    const filt = term
      ? data.filter((it) =>
          (it.label || "").toLowerCase().includes(term.toLowerCase()),
        )
      : data;

    filt.slice(0, 10).forEach((it) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className =
        "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
      btn.innerHTML = `
        <span>${escAttr(it.label || "CONT " + it.id)}</span>
        <span class="badge bg-info">M</span>
      `;
      btn.onclick = () => {
        mdContId.value = String(it.id);
        mdContNom.value = it.label || String(it.id);
        mdContTipo.value = "M";
        clear(mdContSug);
        show(mdContSug, false);
        cargarTiposSegunContenedor();
      };
      mdContSug.appendChild(btn);
    });

    show(mdContSug, true);
  });

  document.addEventListener("click", (e) => {
    if (mdOpSug && !mdOpSug.contains(e.target) && !mdOpNom.contains(e.target)) {
      show(mdOpSug, false);
    }
    if (
      mdContSug &&
      !mdContSug.contains(e.target) &&
      !mdContNom.contains(e.target)
    ) {
      show(mdContSug, false);
    }
  });

  async function autollenarContenedorModal(opId) {
    mdContId.value = "";
    mdContNom.value = "";
    mdContTipo.value = "M";

    const data = await getJSON(
      docBase + "contenedoresPorOperacion/" + encodeURIComponent(opId),
    );

    if (Array.isArray(data) && data.length > 0) {
      const it = data[0];
      mdContId.value = String(it.id);
      mdContNom.value = it.label || String(it.id);
      mdContTipo.value = "M";
      cargarTiposSegunContenedor();
    }
  }

  function cargarTiposSegunContenedor() {
    if (!selTipo) return;

    selTipo.disabled = true;
    selTipo.innerHTML = '<option value="">Cargando…</option>';

    const http = new XMLHttpRequest();
    http.open("GET", docBase + "tipos", true);
    http.send();

    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let data = [];
      try {
        data = JSON.parse(this.responseText) || [];
      } catch {}

      if (!Array.isArray(data) || data.length === 0) {
        selTipo.innerHTML = '<option value="">(Sin tipos disponibles)</option>';
        selTipo.disabled = false;
        return;
      }

      selTipo.innerHTML =
        '<option value="">-- Selecciona tipo --</option>' +
        data
          .map((t) => `<option value="${t.id}">${escAttr(t.nombre)}</option>`)
          .join("");

      selTipo.disabled = false;
    };
  }

  // Submit modal
  form?.addEventListener("submit", function (e) {
    e.preventDefault();

    if (!mdOpId.value) {
      Swal.fire("Aviso", "Selecciona una operación", "info");
      return;
    }

    if (!mdContId.value) {
      Swal.fire("Aviso", "Selecciona el contenedor marítimo", "info");
      return;
    }

    if (!selTipo?.value) {
      Swal.fire("Aviso", "Selecciona el tipo de documento", "info");
      return;
    }

    const fd = new FormData(form);
    fd.set("operacion_id", mdOpId.value);
    fd.set("contenedor_id", mdContId.value);
    fd.set("contenedor_tipo", "M");

    const http = new XMLHttpRequest();
    http.open("POST", docBase + "registrar", true);
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      let res = {};
      try {
        res = JSON.parse(this.responseText) || {};
      } catch {}

      Swal.fire(
        res.status === "success" ? "Éxito" : "Aviso",
        res.msg || "(sin mensaje)",
        res.status || "info",
      );

      if (res.status === "success") {
        const inst = bootstrap.Modal.getInstance(modalEl);
        inst?.hide();
        form.reset();
        if (mdContTipo) mdContTipo.value = "M";
        if (window.listarDocumentosMF) window.listarDocumentosMF();
        if (window.cargarFaltantesMF) window.cargarFaltantesMF();
        if (window.feather) feather.replace();
      }
    };
    http.send(fd);
  });
})();
