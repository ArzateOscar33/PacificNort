// assets/js/modulosAdmin/operaciones_maritimoferro/en_piso.js

(function () {
  "use strict";

  // ===== Compat base_url / BASE_URL =====
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Refs UI =====
  const tbody = document.getElementById("mercanciaPisoTbody");
  const tabla = document.getElementById("mercanciaPisoTabla");

  const inputBuscar = document.getElementById("mercanciaPisoBuscar");
  const filtroBodega = document.getElementById("mercanciaPisoFiltroBodega");
  const fechaDesde = document.getElementById("mercanciaPisoFechaDesde");
  const fechaHasta = document.getElementById("mercanciaPisoFechaHasta");
  const perPageSel = document.getElementById("mercanciaPisoPerPage");

  const badgeTotal = document.getElementById("mercanciaPisoTotal");
  const badgeTJ = document.getElementById("mercanciaPisoTotalTJ");
  const badgeSD = document.getElementById("mercanciaPisoTotalSD");

  const metaResumen = document.getElementById("mercanciaPisoMetaResumen");
  const ulPaginacion = document.getElementById("mercanciaPisoPaginacion");

  const btnExcel = document.getElementById("mercanciaPisoBtnExcel");
  const btnPdf = document.getElementById("mercanciaPisoBtnPdf");
  const btnActualizar = document.getElementById("mercanciaPisoBtnActualizar");

  // ===== Estado =====
  let state = {
    page: 1,
    per_page: parseInt(perPageSel?.value || "10", 10),
    term: "",
    bodega: "",
    date_from: "",
    date_to: "",
  };

  // ===== Debounce =====
  let tSearch = null;
  function debounce(fn, wait) {
    if (tSearch) clearTimeout(tSearch);
    tSearch = setTimeout(fn, wait);
  }

  // ===== Init =====
  window.addEventListener("DOMContentLoaded", function () {
    // Seguridad: si falta algo, no explotar silenciosamente
    if (!tbody || !ulPaginacion || !metaResumen) return;

    // Listar al cargar
    listar();

    // Eventos filtros
    if (inputBuscar) {
      inputBuscar.addEventListener("keyup", function () {
        debounce(() => {
          state.term = (inputBuscar.value || "").trim();
          state.page = 1;
          listar();
        }, 250);
      });
    }

    if (filtroBodega) {
      filtroBodega.addEventListener("change", function () {
        state.bodega = (filtroBodega.value || "").trim();
        state.page = 1;
        listar();
      });
    }

    if (fechaDesde) {
      fechaDesde.addEventListener("change", function () {
        state.date_from = (fechaDesde.value || "").trim();
        state.page = 1;
        listar();
      });
    }

    if (fechaHasta) {
      fechaHasta.addEventListener("change", function () {
        state.date_to = (fechaHasta.value || "").trim();
        state.page = 1;
        listar();
      });
    }

    if (perPageSel) {
      perPageSel.addEventListener("change", function () {
        state.per_page = parseInt(perPageSel.value || "10", 10) || 10;
        state.page = 1;
        listar();
      });
    }

    // Botón actualizar
    if (btnActualizar) {
      btnActualizar.addEventListener("click", function () {
        listar();
      });
    }
  });

  // ===== Helper: construir querystring =====
  function buildQuery() {
    const p = new URLSearchParams();
    p.append("page", String(state.page));
    p.append("per_page", String(state.per_page));

    if (state.term) p.append("term", state.term);
    if (state.bodega) p.append("bodega", state.bodega);
    if (state.date_from) p.append("date_from", state.date_from);
    if (state.date_to) p.append("date_to", state.date_to);

    return p.toString();
  }

  // ===== Request: listar =====
  function listar() {
    const qs = buildQuery();
    const url = base_url + "Piso/listar" + (qs ? "?" + qs : "");

    // Estado de carga simple
    renderCargando();

    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState !== 4) return;

      if (this.status !== 200) {
        renderError("No fue posible cargar la información.");
        console.error("Piso/listar error:", this.responseText);
        return;
      }

      let res;
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        renderError("Respuesta no válida del servidor.");
        console.error("JSON inválido:", this.responseText);
        return;
      }

      if (!res || res.ok !== true) {
        renderError(
          res && res.msg ? res.msg : "No fue posible cargar la información.",
        );
        return;
      }

      // Pintar
      renderBadges(res.badges || null);
      renderTabla(res.data || []);
      renderPaginacion(res.meta || null);
      renderMetaResumen(res.meta || null);

      // Feather refresh
      if (typeof feather !== "undefined") feather.replace();
    };
  }

  // ===== Renderers =====
  function renderCargando() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted py-4">
          Cargando...
        </td>
      </tr>`;
    if (metaResumen) metaResumen.textContent = "Mostrando 0-0 de 0";
    if (ulPaginacion) ulPaginacion.innerHTML = "";
  }

  function renderError(msg) {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-danger py-4">
          ${escapeHtml(msg || "Error")}
        </td>
      </tr>`;
    if (metaResumen) metaResumen.textContent = "Mostrando 0-0 de 0";
    if (ulPaginacion) ulPaginacion.innerHTML = "";
  }

  function renderBadges(b) {
    // badges: { total, tj, sd }
    const total = b && typeof b.total === "number" ? b.total : 0;
    const tj = b && typeof b.tj === "number" ? b.tj : 0;
    const sd = b && typeof b.sd === "number" ? b.sd : 0;

    if (badgeTotal) badgeTotal.textContent = String(total);
    if (badgeTJ) badgeTJ.textContent = String(tj);
    if (badgeSD) badgeSD.textContent = String(sd);
  }

  function renderTabla(rows) {
    if (!tbody) return;

    tbody.innerHTML = "";

    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            No se encontraron resultados
          </td>
        </tr>`;
      return;
    }

    rows.forEach((r) => {
      const bodega = (r.bodega || "").toString();
      const cliente = (r.cliente || "").toString();
      const contenedor = (r.numero_contenedor || "").toString();

      const bTot = toInt(r.bultos_totales);
      const bRes = toInt(r.bultos_restantes);

      const bodegaBadge = badgeBodegaHtml(bodega);
      const totalBadge = `<span class="badge bg-primary text-white">${bTot}</span>`;
      const restantesBadge = badgeRestantesHtml(bRes, bTot);
      const num_operacion = r.numero_operacion || "";

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${bodegaBadge}</td>
        <td><span class="fw-semibold">${num_operacion || ""}</span></td>
        <td>${escapeHtml(cliente)}</td>
        <td><span class="fw-semibold">${escapeHtml(contenedor)}</span></td>
        <td>${totalBadge}</td>
        <td>${restantesBadge}</td>
 
      `;
      tbody.appendChild(tr);
    });
  }

  function renderMetaResumen(meta) {
    if (!metaResumen) return;

    const total = toInt(meta && meta.total);
    const page = toInt(meta && meta.page) || 1;
    const per_page = toInt(meta && meta.per_page) || state.per_page;

    if (total <= 0) {
      metaResumen.textContent = "Mostrando 0-0 de 0";
      return;
    }

    const start = (page - 1) * per_page + 1;
    const end = Math.min(page * per_page, total);
    metaResumen.textContent = `Mostrando ${start}-${end} de ${total}`;
  }

  function renderPaginacion(meta) {
    if (!ulPaginacion) return;

    ulPaginacion.innerHTML = "";

    const total_pages = toInt(meta && meta.total_pages) || 1;
    const page = toInt(meta && meta.page) || 1;

    if (total_pages <= 1) return;

    // Prev
    ulPaginacion.appendChild(pagItem("«", page - 1, page <= 1));

    // Ventana de páginas
    const windowSize = 5;
    let start = Math.max(1, page - Math.floor(windowSize / 2));
    let end = Math.min(total_pages, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);

    for (let p = start; p <= end; p++) {
      ulPaginacion.appendChild(pagItem(String(p), p, false, p === page));
    }

    // Next
    ulPaginacion.appendChild(pagItem("»", page + 1, page >= total_pages));
  }

  function pagItem(label, pageTarget, disabled, active) {
    const li = document.createElement("li");
    li.className = "page-item";
    if (disabled) li.classList.add("disabled");
    if (active) li.classList.add("active");

    const a = document.createElement("a");
    a.className = "page-link";
    a.href = "javascript:void(0)";
    a.textContent = label;

    a.onclick = function () {
      if (disabled) return;
      state.page = pageTarget;
      listar();
    };

    li.appendChild(a);
    return li;
  }

  // ===== Helpers badges =====
  function badgeBodegaHtml(bodega) {
    const name = (bodega || "").toString().trim();
    if (name === "BODEGA MX") {
      return `<span class="badge bg-success text-white">${escapeHtml(name)}</span>`;
    }
    if (name === "BODEGA USA") {
      return `<span class="badge bg-primary text-white">${escapeHtml(name)}</span>`;
    }
    return `<span class="badge bg-light text-dark border">${escapeHtml(name || "—")}</span>`;
  }

  function badgeRestantesHtml(rest, total) {
    const r = toInt(rest);
    const t = Math.max(0, toInt(total));

    // Reglas visuales (similares a tus ejemplos):
    // - si restantes == total => success
    // - si restantes muy bajos (<=10%) => danger
    // - si restantes medios (<=40%) => warning
    // - si restantes > 40% => primary
    // - si restantes 0 => secondary
    if (t === 0) return `<span class="badge bg-secondary text-white">0</span>`;
    if (r <= 0) return `<span class="badge bg-secondary text-white">0</span>`;
    if (r >= t) return `<span class="badge bg-warning text-dark">${r}</span>`;

    const ratio = r / t;
    if (ratio <= 0.1)
      return `<span class="badge bg-danger text-white">${r}</span>`;
    if (ratio <= 0.4)
      return `<span class="badge bg-warning text-dark">${r}</span>`;
    return `<span class="badge bg-primary text-white">${r}</span>`;
  }

  // ===== Utils =====
  function toInt(v) {
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : 0;
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
})();

// ===============================
// Exportaciones
// ===============================
document
  .getElementById("mercanciaPisoBtnExcel")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "mercanciaPisoTabla",
      formato: "xlsx",
      nombre: "MercanciaEnBodegas.xlsx",
      columnasOcultas: [],
      soloVisibles: true,
      sheetName: "Mercancia en Bodegas",
    });
  });

document
  .getElementById("mercanciaPisoBtnPdf")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "#mercanciaPisoTabla",
      formato: "pdf",
      nombre: "MercanciaEnBodegas.pdf",
      titulo: "Mercancia en Bodegas",
      orientacion: "landscape",
      formatoPagina: "letter",
      columnasOcultas: [],
      soloVisibles: true,
    });
  });
