// Assets/Js/ModulosAdmin/operaciones_maritimoferro/trazabilidad_catalogo.js
(function () {
  "use strict";

  // ========= BASE URL =========
  const BASE_URL =
    (typeof base_url !== "undefined" ? base_url : "") ||
    window.base_url ||
    window.BASE_URL ||
    "";

  // ========= ENDPOINTS =========
  const EP_LISTAR = BASE_URL + "Operaciones_maritimo_ferro_trazabilidad/listar";
  // ✅ TU ENDPOINT REAL (según tu controlador actual)
  const EP_HISTORIAL =
    BASE_URL + "Operaciones_maritimo_ferro_trazabilidad/listarHistorial";

  // ========= REFS DOM (tabla rutas) =========
  const tbody = document.getElementById("tbodyRutasFerro");
  const inputBuscar = document.getElementById("rutasBuscar");
  const inpFechaIni = document.getElementById("rutasFechaIni");
  const inpFechaFin = document.getElementById("rutasFechaFin");
  const selectPerPage = document.getElementById("rutasPerPage");
  const metaEl = document.getElementById("rutasMeta");
  const ulPag = document.getElementById("rutasPaginacion");
  const btnExcel = document.getElementById("rutasExcel");
  const btnPdf = document.getElementById("rutasPdf");

  // ========= REFS DOM (modal historial) =========
  const modalTimeline = document.getElementById("rutaHist_timeline");
  const modalMeta = document.getElementById("rutaHist_meta");
  const modalBadgeParadas = document.getElementById(
    "rutaHist_badgeTotalParadas",
  );

  const selectCliente = document.getElementById("rutasFiltroCliente");
  const selectOrigen = document.getElementById("rutasFiltroOrigen");
  const selectUbicacion = document.getElementById("rutasFiltroUbicacion");
  const selectDestino = document.getElementById("rutasFiltroDestino");

  const hidOperacionId = document.getElementById("rutaHist_operacionId");
  const hidOperacionFerroId = document.getElementById(
    "rutaHist_operacionFerroId",
  );
  const hidAsignacionId = document.getElementById("rutaHist_asignacionId");
  const hidContenedorFisicoId = document.getElementById(
    "rutaHist_contenedorFisicoId",
  );

  // ✅ NUEVOS hidden (asegúrate de tenerlos en el HTML)
  const hidDestinoNombre = document.getElementById("rutaHist_destinoNombre");
  const hidLlegoDestino = document.getElementById("rutaHist_llegoDestino");

  const badgeOp = document.getElementById("rutaHist_badgeOperacion");
  const badgeCont = document.getElementById("rutaHist_badgeContenedor");
  const badgeFerro = document.getElementById("rutaHist_badgeFerro");

  const detFecha = document.getElementById("rutaHist_det_fecha");
  const detUbi = document.getElementById("rutaHist_det_ubicacion");
  const detNotas = document.getElementById("rutaHist_det_notas");
  const detUsr = document.getElementById("rutaHist_det_usuario");

  // ========= STATE =========
  let currentPage = 1;
  let perPage = (selectPerPage?.value || "10").toString();
  let currentXHR = null;
  let debounceId = null;

  // ========= HELPERS =========
  const safe = (v) => (v === undefined || v === null ? "" : String(v));

  function isAllMode(pp) {
    return Number(pp || 0) >= 10000000;
  }

  function validarRangoFechas() {
    const fi = (inpFechaIni?.value || "").trim();
    const ff = (inpFechaFin?.value || "").trim();
    if (fi && ff && fi > ff) {
      inpFechaIni.value = ff;
      inpFechaFin.value = fi;
    }
  }

  function renderCargandoTabla() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr>
        <td colspan="8" class="text-center text-muted py-4">
          Cargando rutas...
        </td>
      </tr>`;
  }

  function renderEmptyTabla() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr id="rutasEmptyRow">
        <td colspan="8" class="text-center text-muted">
          <i data-feather="info" class="me-1"></i> No hay rutas para mostrar.
        </td>
      </tr>`;
    if (window.feather) feather.replace();
  }

  function renderMeta(meta) {
    if (!metaEl) return;

    const total = Number(meta?.total || 0);
    const page = Number(meta?.page || 1);
    const pp = meta?.per_page ?? perPage;

    if (total <= 0) {
      metaEl.textContent = "Mostrando 0-0 de 0";
      return;
    }

    if (isAllMode(pp)) {
      metaEl.textContent = `Mostrando 1-${total} de ${total} | pág 1 de 1`;
      return;
    }

    const per = Number(pp || 10);
    const start = (page - 1) * per + 1;
    const end = Math.min(total, page * per);
    const totalPages = Number(meta?.total_pages || 1);

    metaEl.textContent = `Mostrando ${start}-${end} de ${total} | pág ${page} de ${totalPages}`;
  }

  function renderPaginacion(meta) {
    if (!ulPag) return;

    const pp = meta?.per_page ?? perPage;
    const isAll = isAllMode(pp);

    if (isAll) {
      ulPag.innerHTML = `
        <li class="page-item active">
          <a class="page-link" href="#" onclick="return false;">1</a>
        </li>`;
      return;
    }

    const totalPages = Math.max(1, parseInt(meta?.total_pages || 1, 10) || 1);
    const page = Math.min(
      totalPages,
      Math.max(1, parseInt(meta?.page || 1, 10) || 1),
    );

    ulPag.innerHTML = "";

    if (totalPages <= 1) {
      ulPag.innerHTML = `
        <li class="page-item active">
          <a class="page-link" href="#" onclick="return false;">1</a>
        </li>`;
      return;
    }

    // Prev
    const liPrev = document.createElement("li");
    liPrev.className = "page-item" + (page <= 1 ? " disabled" : "");
    liPrev.innerHTML = `<a class="page-link" href="#" aria-label="Anterior">&laquo;</a>`;
    liPrev.onclick = (e) => {
      e.preventDefault();
      if (page > 1) {
        currentPage = page - 1;
        listar();
      }
    };
    ulPag.appendChild(liPrev);

    const windowSize = 5;
    let start = Math.max(1, page - Math.floor(windowSize / 2));
    let end = Math.min(totalPages, start + windowSize - 1);
    if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

    for (let p = start; p <= end; p++) {
      const li = document.createElement("li");
      li.className = "page-item" + (p === page ? " active" : "");
      li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
      li.onclick = (e) => {
        e.preventDefault();
        if (p !== page) {
          currentPage = p;
          listar();
        }
      };
      ulPag.appendChild(li);
    }

    // Next
    const liNext = document.createElement("li");
    liNext.className = "page-item" + (page >= totalPages ? " disabled" : "");
    liNext.innerHTML = `<a class="page-link" href="#" aria-label="Siguiente">&raquo;</a>`;
    liNext.onclick = (e) => {
      e.preventDefault();
      if (page < totalPages) {
        currentPage = page + 1;
        listar();
      }
    };
    ulPag.appendChild(liNext);
  }

  function buildAcciones(r) {
    const asigId = safe(r.asignacion_id);
    const foId = safe(r.id_operacion_ferro);
    const opId = safe(r.id_operacion);
    const fisicoId = safe(r.id_fisico);

    return `
    <div class="d-flex gap-1 justify-content-center flex-wrap">
      <button
        type="button"
        class="btn btn-sm btn-outline-success btn-traza-detalle"
        data-bs-toggle="modal"
        data-bs-target="#modalRutaHistorial"
        data-asig-id="${asigId}"
        data-fo-id="${foId}"
        data-op-id="${opId}"
        data-fisico-id="${fisicoId}"
        data-op="${safe(r.operacion_maritima || r.numero_operacion)}"
        data-cont="${safe(r.contenedor_maritimo || r.numero_contenedor)}"
        data-ferro="${safe(r.ferro_caja || r.numero_ferro)}"
        data-destino="${safe(r.destino)}"
        data-llego="${safe(r.llego_destino)}"
        title="Ver historial"
      >
        <i data-feather="map-pin" class="me-1"></i> Historial
      </button>
    </div>`;
  }

  function renderTabla(rows) {
    if (!tbody) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      renderEmptyTabla();
      return;
    }

    tbody.innerHTML = "";
    rows.forEach((r) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="text-center">${safe(r.operacion_maritima || r.numero_operacion)}</td>
        <td class="text-center">${safe(r.contenedor_maritimo || r.numero_contenedor)}</td>
        <td class="text-center">${safe(r.ferro_caja || r.numero_ferro)}</td>
        <td>${safe(r.cliente)}</td>
        <td>${safe(r.origen)}</td>
        <td>${safe(r.ubicacion_actual)}</td>
        <td>${safe(r.destino)}</td>
        <td class="text-center">${buildAcciones(r)}</td>
      `;
      tbody.appendChild(tr);
    });

    if (window.feather) feather.replace();
  }

  // ========= LISTAR =========
  function listar() {
    const params = new URLSearchParams();

    const term = (inputBuscar?.value || "").trim();
    const fi = (inpFechaIni?.value || "").trim();
    const ff = (inpFechaFin?.value || "").trim();
    const pp = (perPage || "10").toString();
    const all = isAllMode(pp);
    const clienteId = (selectCliente?.value || "").trim();
    const origenId = (selectOrigen?.value || "").trim();
    const ubicacionId = (selectUbicacion?.value || "").trim();
    const destinoId = (selectDestino?.value || "").trim();

    if (clienteId) params.append("cliente_id", clienteId);
    if (origenId) params.append("origen_id", origenId);
    if (ubicacionId) params.append("ubicacion_id", ubicacionId);
    if (destinoId) params.append("destino_id", destinoId);

    if (term) params.append("term", term);
    if (fi) params.append("fecha_inicio", fi);
    if (ff) params.append("fecha_fin", ff);

    params.append("page", String(all ? 1 : currentPage));
    params.append("per_page", String(pp));

    if (all) currentPage = 1;

    const url = EP_LISTAR + "?" + params.toString();

    if (currentXHR && currentXHR.readyState !== 4) currentXHR.abort();

    renderCargandoTabla();

    const x = new XMLHttpRequest();
    currentXHR = x;
    x.open("GET", url, true);
    x.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    x.send();

    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;
      if (currentXHR !== x) return;

      if (x.status !== 200) {
        console.error("listar rutas error:", x.responseText);
        renderTabla([]);
        renderPaginacion({ page: 1, total_pages: 1, per_page: pp });
        renderMeta({ total: 0, page: 1, per_page: pp, total_pages: 1 });
        return;
      }

      let payload = {};
      try {
        payload = JSON.parse(x.responseText);
      } catch (e) {
        payload = {};
      }

      if (!payload || payload.status !== "success") {
        renderTabla([]);
        renderPaginacion({ page: 1, total_pages: 1, per_page: pp });
        renderMeta({ total: 0, page: 1, per_page: pp, total_pages: 1 });
        return;
      }

      const rows = payload.rows || [];
      const meta = payload.meta || {
        total: 0,
        page: 1,
        per_page: pp,
        total_pages: 1,
      };

      renderTabla(rows);
      renderPaginacion(meta);
      renderMeta(meta);
    };
  }

  // ========= HISTORIAL =========
  function resetDetalleEvento() {
    if (detFecha) detFecha.textContent = "—";
    if (detUbi) detUbi.textContent = "—";
    if (detNotas) detNotas.textContent = "—";
    if (detUsr) detUsr.textContent = "—";
  }

  function renderDestinoFinal(meta) {
    if (!modalTimeline) return;

    const destino =
      safe(meta?.destino_nombre) || safe(hidDestinoNombre?.value) || "—";
    const lastUbi =
      safe(meta?.ubicacion_nombre_last) || safe(meta?.ubicacion_nombre) || "";
    const llego =
      Number(meta?.llego_destino || hidLlegoDestino?.value || 0) === 1;

    // Respeta tus estilos: usamos "ruta-step" + "is-destino".
    // Solo agregamos classes bootstrap de borde (no metemos CSS nuevo).
    const borderClass = llego
      ? "border border-success"
      : "border border-danger";

    const div = document.createElement("div");
    div.className = `ruta-step is-destino ${borderClass}`;
    div.setAttribute("data-fecha", "");
    div.setAttribute("data-ubicacion", destino);
    div.setAttribute(
      "data-notas",
      llego ? "Llegó al destino." : "Pendiente de llegar al destino.",
    );
    div.setAttribute("data-usuario", "—");

    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
          <div class="ruta-lugar">Destino final: ${destino}</div>
          <div class="small text-muted">${
            llego ? "Arribo confirmado" : "Aún no registra arribo"
          }</div>
        </div>
        <div class="ruta-fecha">—</div>
      </div>
      <div class="mt-2 d-flex flex-wrap gap-2">
        ${
          llego
            ? `<span class="badge bg-success ruta-chip text-white">LLEGÓ</span>`
            : `<span class="badge bg-danger ruta-chip text-white">PENDIENTE</span>`
        }
        <span class="badge bg-light text-dark ruta-chip">${
          lastUbi ? `Última: ${lastUbi}` : "Sin ubicación registrada"
        }</span>
      </div>
    `;

    modalTimeline.appendChild(div);
  }

  function renderTimeline(rows, meta) {
    if (!modalTimeline) return;

    // Siempre limpiamos y repintamos todo
    modalTimeline.innerHTML = "";

    // Si no hay eventos, mostramos mensaje y AÚN ASÍ pintamos destino final abajo
    if (!rows || rows.length === 0) {
      modalTimeline.innerHTML = `
        <div class="ruta-step">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="ruta-lugar">Sin eventos</div>
              <div class="small text-muted">No hay historial registrado.</div>
            </div>
            <div class="ruta-fecha">—</div>
          </div> 
        </div>`;
      renderDestinoFinal(meta);
      return;
    }

    rows.forEach((r) => {
      const fecha = safe(r.fecha_evento || r.created_at);
      const ubi = safe(r.ubicacion);
      const ref = safe(r.referencia);
      const notas = safe(r.notas);

      const div = document.createElement("div");
      div.className = "ruta-step";
      div.setAttribute("data-fecha", fecha);
      div.setAttribute("data-ubicacion", ubi);
      div.setAttribute("data-notas", notas);
      div.setAttribute("data-usuario", "—");

      div.innerHTML = `
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="ruta-lugar">${ubi || "—"}</div>
            <div class="small text-muted">${ref ? ref : "Evento"}</div>
          </div>
          <div class="ruta-fecha">${fecha || "—"}</div>
        </div> 
      `;
      modalTimeline.appendChild(div);
    });

    // ✅ Siempre agregamos el destino al final (rojo/verde según meta.llego_destino)
    renderDestinoFinal(meta);
  }

  function cargarHistorialRuta() {
    const fisicoId = (hidContenedorFisicoId?.value || "").trim();
    const foId = (hidOperacionFerroId?.value || "").trim();

    if (!fisicoId || !foId) {
      if (modalMeta)
        modalMeta.textContent =
          "Faltan datos para consultar historial (contenedor físico / operación ferro).";
      if (modalBadgeParadas) modalBadgeParadas.textContent = "0 paradas";
      if (modalTimeline) modalTimeline.innerHTML = "";
      return;
    }

    if (modalTimeline) {
      modalTimeline.innerHTML = `<div class="text-center text-muted py-3">Cargando historial...</div>`;
    }
    if (modalMeta) modalMeta.textContent = "Cargando historial...";
    if (modalBadgeParadas) modalBadgeParadas.textContent = "0 paradas";
    resetDetalleEvento();

    const params = new URLSearchParams();
    params.append("contenedor_fisico_id", fisicoId);
    params.append("operacion_ferro_id", foId);
    params.append("limit", "50");

    const url = EP_HISTORIAL + "?" + params.toString();

    const x = new XMLHttpRequest();
    x.open("GET", url, true);
    x.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    x.send();

    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;

      if (x.status !== 200) {
        if (modalTimeline)
          modalTimeline.innerHTML = `<div class="text-danger">Error al cargar historial.</div>`;
        if (modalMeta) modalMeta.textContent = "Error al cargar historial.";
        if (modalBadgeParadas) modalBadgeParadas.textContent = "0 paradas";
        return;
      }

      let payload = {};
      try {
        payload = JSON.parse(x.responseText);
      } catch (e) {
        payload = {};
      }

      if (!payload || payload.status !== "success") {
        const msg = payload?.msg || "Sin historial.";
        if (modalTimeline)
          modalTimeline.innerHTML = `<div class="text-muted">${msg}</div>`;
        if (modalMeta) modalMeta.textContent = msg;
        if (modalBadgeParadas) modalBadgeParadas.textContent = "0 paradas";
        return;
      }

      const rows = Array.isArray(payload.rows) ? payload.rows : [];
      const meta = payload.meta || {};

      // Guardamos (por si la UI los quiere después)
      if (hidDestinoNombre)
        hidDestinoNombre.value =
          safe(meta?.destino_nombre) || hidDestinoNombre.value || "";
      if (hidLlegoDestino)
        hidLlegoDestino.value = String(
          Number(meta?.llego_destino || hidLlegoDestino.value || 0),
        );

      if (modalMeta) {
        const destinoTxt = safe(meta?.destino_nombre);
        modalMeta.textContent = destinoTxt
          ? `Mostrando ${rows.length} evento(s) | Destino: ${destinoTxt}`
          : `Mostrando ${rows.length} evento(s)`;
      }

      if (modalBadgeParadas)
        modalBadgeParadas.textContent = `${rows.length} paradas`;

      renderTimeline(rows, meta);
      if (window.feather) feather.replace();
    };
  }

  // ========= EVENTOS FILTROS =========
  inputBuscar?.addEventListener("keyup", () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => {
      currentPage = 1;
      listar();
    }, 250);
  });

  selectPerPage?.addEventListener("change", () => {
    perPage = (selectPerPage.value || "10").toString();
    currentPage = 1;
    listar();
  });

  inpFechaIni?.addEventListener("change", () => {
    validarRangoFechas();
    currentPage = 1;
    listar();
  });

  inpFechaFin?.addEventListener("change", () => {
    validarRangoFechas();
    currentPage = 1;
    listar();
  });

  // ========= CLICK EN BOTÓN HISTORIAL (SOLO UNO) =========
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-traza-detalle");
    if (!btn) return;

    console.log("DATASET BTN:", btn.dataset);

    // Badges
    if (badgeOp) badgeOp.textContent = btn.dataset.op || "—";
    if (badgeCont) badgeCont.textContent = btn.dataset.cont || "—";
    if (badgeFerro) badgeFerro.textContent = btn.dataset.ferro || "—";

    // Hidden refs
    if (hidOperacionId) hidOperacionId.value = btn.dataset.opId || "";
    if (hidOperacionFerroId) hidOperacionFerroId.value = btn.dataset.foId || "";
    if (hidAsignacionId) hidAsignacionId.value = btn.dataset.asigId || "";
    if (hidContenedorFisicoId)
      hidContenedorFisicoId.value = btn.dataset.fisicoId || "";

    // ✅ Guardamos destino/flag de la tabla (para mostrar rápido aunque luego se refine con meta del endpoint)
    if (hidDestinoNombre) hidDestinoNombre.value = btn.dataset.destino || "";
    if (hidLlegoDestino) hidLlegoDestino.value = btn.dataset.llego || "0";

    cargarHistorialRuta();
  });

  // ========= CLICK EN STEP PARA DETALLE =========
  document.addEventListener("click", (e) => {
    const step = e.target.closest("#rutaHist_timeline .ruta-step");
    if (!step) return;

    document
      .querySelectorAll("#rutaHist_timeline .ruta-step.active")
      .forEach((el) => el.classList.remove("active"));

    step.classList.add("active");

    if (detFecha) detFecha.textContent = step.getAttribute("data-fecha") || "—";
    if (detUbi) detUbi.textContent = step.getAttribute("data-ubicacion") || "—";
    if (detNotas) detNotas.textContent = step.getAttribute("data-notas") || "—";
    if (detUsr) detUsr.textContent = step.getAttribute("data-usuario") || "—";
  });

  document
    .getElementById("rutaHist_btnLimpiarDetalle")
    ?.addEventListener("click", () => {
      document
        .querySelectorAll("#rutaHist_timeline .ruta-step.active")
        .forEach((el) => el.classList.remove("active"));
      resetDetalleEvento();
    });

  // ========= EXPORT =========
  btnExcel?.addEventListener("click", () => {
    window.ExportarTablas?.exportar({
      ref: "tablaRutas",
      formato: "xlsx",
      nombre: "RutasFerroCaja.xlsx",
      soloVisibles: true,
      sheetName: "Rutas",
    });
  });

  btnPdf?.addEventListener("click", () => {
    window.ExportarTablas?.exportar({
      ref: "#tablaRutas",
      formato: "pdf",
      nombre: "RutasFerroCaja.pdf",
      titulo: "Rutas Ferro/Caja",
      orientacion: "landscape",
      formatoPagina: "letter",
      soloVisibles: true,
    });
  });

  // ========= INIT =========
  window.addEventListener("DOMContentLoaded", () => {
    perPage = (selectPerPage?.value || "10").toString();
    listar();
    if (window.feather) feather.replace();
  });

  function onFiltroChange() {
    currentPage = 1;
    listar();
  }

  selectCliente?.addEventListener("change", onFiltroChange);
  selectOrigen?.addEventListener("change", onFiltroChange);
  selectUbicacion?.addEventListener("change", onFiltroChange);
  selectDestino?.addEventListener("change", onFiltroChange);

  // ========= Bridge refresh =========
  (function bindRefreshEvents() {
    let to = null;
    function refresh() {
      clearTimeout(to);
      to = setTimeout(() => listar(), 120);
    }
    document.addEventListener("mf:trazabilidad-updated", refresh);
    document.addEventListener("mf:asignacion-updated", refresh);
    document.addEventListener("mf:refresh-list", refresh);
  })();
})();
