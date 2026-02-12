/* Assets/Js/PortalClientes/OperacionesFerro.js
   Lista FO para Portal Cliente (XHR + JSON limpio)
   - IDs de la vista: foSearch, foEstatus, foFechaIni, foFechaFin, btnFOFiltrar, btnFOLimpiar,
     foPageSize, tbOpsFO, foPagingLbl, foPaging, foFiltrosActivosBar
   - Endpoint: PortalClientes/listarOperacionesFerroCliente
*/

(function () {
  "use strict";

  // ===== Config baseUrl =====
  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  // Endpoint FO
  const ENDPOINT = `${BASE_URL}PortalClientes/listarOperacionesFerroCliente`;
  const ENDPOINT_ASIG = `${BASE_URL}PortalClientes/asignacionesFO`;
  const ENDPOINT_EVT = `${BASE_URL}PortalClientes/eventosFO`;

  // ===== Refs UI =====
  const tb = document.getElementById("tbOpsFO");
  const pagingLbl = document.getElementById("foPagingLbl");
  const paging = document.getElementById("foPaging");
  const selPageSize = document.getElementById("foPageSize");

  const inpSearch = document.getElementById("foSearch");
  const selEstatus = document.getElementById("foEstatus");
  const inpFIni = document.getElementById("foFechaIni");
  const inpFFin = document.getElementById("foFechaFin");

  const btnFiltrar = document.getElementById("btnFOFiltrar");
  const btnLimpiar = document.getElementById("btnFOLimpiar");
  const filtrosBar = document.getElementById("foFiltrosActivosBar");

  const btnRefrescarTodo = document.getElementById("btnRefrescarTodo");

  // Modal Detalle FO
  const modalDetalleEl = document.getElementById("modalDetalleFO");
  const modalDetalle = modalDetalleEl
    ? new bootstrap.Modal(modalDetalleEl)
    : null;

  // Modal Docs
  const modalDocsEl = document.getElementById("modalDocs");
  const modalDocs = modalDocsEl ? new bootstrap.Modal(modalDocsEl) : null;

  // Hidden refs docs
  const docsOperacionId = document.getElementById("docsOperacionId");
  const docsTipoOperacion = document.getElementById("docsTipoOperacion");
  const docsOperacionNumero = document.getElementById("docsOperacionNumero");

  // ===== Estado =====
  const state = {
    page: 1,
    pageSize: selPageSize ? parseInt(selPageSize.value, 10) || 15 : 15,
    total: 0,
    rows: [], // rows crudos del server
  };

  // ===== Helpers =====
  function esc(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function toText(x) {
    return String(x ?? "").trim();
  }

  function norm(s) {
    return toText(s).toLowerCase();
  }

  function parseDateISO(s) {
    // acepta "YYYY-MM-DD" o "YYYY-MM-DD HH:MM:SS"
    const t = toText(s);
    if (!t) return null;
    const d = t.substring(0, 10);
    const dt = new Date(d + "T00:00:00");
    return isNaN(dt.getTime()) ? null : dt;
  }

  function badgeClassByEstatus(nombre) {
    const n = norm(nombre);
    if (!n) return "text-bg-secondary";
    if (n.includes("entre")) return "text-bg-success";
    if (n.includes("bodega") || n.includes("yar")) return "text-bg-warning";
    if (n.includes("puer")) return "text-bg-secondary";
    if (n.includes("en ag") || n.includes("canc")) return "text-bg-danger";
    return "text-bg-primary";
  }

  function buildChip(label, value) {
    return `
      <span class="chip">
        <i data-feather="tag" style="width:14px;height:14px;"></i>
        ${esc(label)}: ${esc(value)}
      </span>
    `;
  }

  function renderFiltrosActivosFO() {
    if (!filtrosBar) return;

    const chips = [];
    const s = toText(inpSearch?.value);
    const est = parseInt(selEstatus?.value || "0", 10);
    const f1 = toText(inpFIni?.value);
    const f2 = toText(inpFFin?.value);

    if (s) chips.push(buildChip("Buscar", s));
    if (est && selEstatus) {
      const opt = selEstatus.options[selEstatus.selectedIndex];
      chips.push(buildChip("Estatus", opt ? opt.text : String(est)));
    }
    if (f1) chips.push(buildChip("Fecha ini", f1));
    if (f2) chips.push(buildChip("Fecha fin", f2));

    filtrosBar.innerHTML = chips.join(" ");
    if (window.feather) feather.replace();
  }

  // ===== Filtro del lado cliente (mientras el Model no filtre) =====
  function aplicarFiltrosCliente(rows) {
    const s = norm(inpSearch?.value);
    const est = parseInt(selEstatus?.value || "0", 10);

    const f1 = parseDateISO(inpFIni?.value);
    const f2 = parseDateISO(inpFFin?.value);

    return (rows || []).filter((r) => {
      // estatus
      if (est && parseInt(r.estatus_id, 10) !== est) return false;

      // fecha range (r.fecha)
      if (f1 || f2) {
        const rf = parseDateISO(r.fecha);
        if (!rf) return false;

        if (f1 && rf < f1) return false;
        if (f2) {
          // incluir el día completo
          const end = new Date(f2.getTime());
          end.setHours(23, 59, 59, 999);
          if (rf > end) return false;
        }
      }

      // search
      if (s) {
        const hay = [
          r.numero_operacion,
          r.contenedor_fisico,
          r.destino,
          r.transportista,
          r.subtipo_clave,
          r.subtipo_nombre,
          r.comentarios,
          r.creado_por_nombre,
          r.fecha,
          r.estatus,
        ]
          .map(norm)
          .join(" | ");

        if (!hay.includes(s)) return false;
      }

      return true;
    });
  }

  // ===== Render tabla =====
  function renderTable(rows) {
    if (!tb) return;

    if (!rows || rows.length === 0) {
      tb.innerHTML = `
        <tr>
          <td colspan="8" class="text-center pn-muted py-4">
            No hay operaciones FO para mostrar.
          </td>
        </tr>
      `;
      return;
    }

    tb.innerHTML = rows
      .map((r) => {
        const op = esc(r.numero_operacion || `FO-${r.id_operacion_ferro}`);
        const ferro = esc(r.contenedor_fisico || "-");

        // Origen: tu SELECT actual no trae origen -> dejamos "-"

        const destino = esc(r.destino || "-");
        const contMar = `<span class="badge badge-soft">${esc(r.contenedores_maritimos || "-")}</span>`; // pendiente cuando agregues asignaciones en endpoint
        const fecha = esc((r.fecha || "").toString().substring(0, 10));
        const estatusTxt = esc(r.estatus || "-");
        const badge = badgeClassByEstatus(r.estatus);

        return `
          <tr>
            <td class="fw-semibold">${op}</td>
            <td>${ferro}</td> 
            <td>${destino}</td>
            <td>${contMar}</td>
            <td>${fecha}</td>
            <td><span class="badge ${badge}">${estatusTxt}</span></td>
            <td class="text-end">
             <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-dark"
                data-fo-action="detalle"
                data-id="${esc(r.id_operacion_ferro)}">
                <i data-feather="eye" class="me-1"></i> Ver
                </button>

              <button class="btn btn-outline-primary"
                data-fo-action="docs"
                data-id="${esc(r.id_operacion_ferro)}"
                data-num="${esc(r.numero_operacion || "")}">
                 <i data-feather="file-text" class="me-1"></i> Docs
                </button>
                </div>
            </td>
          </tr>
        `;
      })
      .join("");

    if (window.feather) feather.replace();
  }

  // ===== Paginación (usa total del server; slicing local con “ventana” del endpoint) =====
  function renderPaging(page, pageSize, total) {
    if (!paging || !pagingLbl) return;

    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    const p = Math.min(Math.max(1, page), totalPages);

    const start = total === 0 ? 0 : (p - 1) * pageSize + 1;
    const end = Math.min(p * pageSize, total);

    pagingLbl.textContent = `Mostrando ${start}–${end} de ${total}`;

    // rango de páginas (max 7)
    const maxBtns = 7;
    let from = Math.max(1, p - Math.floor(maxBtns / 2));
    let to = Math.min(totalPages, from + maxBtns - 1);
    from = Math.max(1, to - maxBtns + 1);

    const li = [];

    li.push(`
      <li class="page-item ${p <= 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-fo-page="${p - 1}">«</a>
      </li>
    `);

    for (let i = from; i <= to; i++) {
      li.push(`
        <li class="page-item ${i === p ? "active" : ""}">
          <a class="page-link" href="#" data-fo-page="${i}">${i}</a>
        </li>
      `);
    }

    li.push(`
      <li class="page-item ${p >= totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-fo-page="${p + 1}">»</a>
      </li>
    `);

    paging.innerHTML = li.join("");
  }

  // ===== XHR =====
  function xhrPost(url, dataObj, cb) {
    const xhr = new XMLHttpRequest();
    const fd = new FormData();

    Object.keys(dataObj || {}).forEach((k) => {
      if (dataObj[k] !== undefined && dataObj[k] !== null)
        fd.append(k, dataObj[k]);
    });

    xhr.open("POST", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status >= 200 && xhr.status < 300) {
        let json = null;
        try {
          json = JSON.parse(xhr.responseText);
        } catch (e) {
          cb(new Error("Respuesta JSON inválida"));
          return;
        }
        cb(null, json);
      } else {
        cb(new Error("HTTP " + xhr.status));
      }
    };
    xhr.send(fd);
  }
  function cargarAsignacionesFO(opFerroId) {
    const tbAsig = document.getElementById("fo_asignaciones");
    if (!tbAsig) return;

    tbAsig.innerHTML = `
    <tr>
      <td colspan="3" class="text-center pn-muted py-3">Cargando asignaciones...</td>
    </tr>
  `;

    xhrPost(
      ENDPOINT_ASIG,
      { id_operacion_ferro: opFerroId },
      function (err, res) {
        if (err || !res || res.ok !== true) {
          const msg = res && res.msg ? res.msg : err ? err.message : "Error";
          tbAsig.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-danger py-3">
            No se pudieron cargar asignaciones. ${esc(msg)}
          </td>
        </tr>
      `;
          return;
        }

        const rows = Array.isArray(res.rows) ? res.rows : [];
        if (rows.length === 0) {
          tbAsig.innerHTML = `
        <tr>
          <td colspan="3" class="text-center pn-muted py-3">
            No hay contenedores marítimos asignados.
          </td>
        </tr>
      `;
          return;
        }

        tbAsig.innerHTML = rows
          .map((r) => {
            const opMar = esc(r.operacion_maritima || "-");
            const cont = esc(r.contenedor_maritimo || "-");
            const b = esc(r.bultos_asignados || "0");
            return `
          <tr>
            <td>${opMar}</td>
            <td>${cont}</td>
            <td class="text-end fw-semibold">${b}</td>
          </tr>
        `;
          })
          .join("");

        if (window.feather) feather.replace();
      },
    );
  }

  function cargarEventosFO(opFerroId) {
    const tbEvt = document.getElementById("fo_eventos");
    if (!tbEvt) return;

    tbEvt.innerHTML = `
    <tr>
      <td colspan="3" class="text-center pn-muted py-3">Cargando eventos...</td>
    </tr>
  `;

    xhrPost(
      ENDPOINT_EVT,
      { id_operacion_ferro: opFerroId },
      function (err, res) {
        if (err || !res || res.ok !== true) {
          const msg = res && res.msg ? res.msg : err ? err.message : "Error";
          tbEvt.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-danger py-3">
            No se pudieron cargar eventos. ${esc(msg)}
          </td>
        </tr>
      `;
          return;
        }

        const rows = Array.isArray(res.rows) ? res.rows : [];
        if (rows.length === 0) {
          tbEvt.innerHTML = `
        <tr>
          <td colspan="3" class="text-center pn-muted py-3">
            No hay eventos registrados.
          </td>
        </tr>
      `;
          return;
        }

        tbEvt.innerHTML = rows
          .map((r) => {
            const fecha = esc((r.fecha || "").toString().substring(0, 10));
            const evt = esc(r.evento || "-");
            const com = esc(r.comentario || "");
            return `
          <tr>
            <td>${fecha}</td>
            <td class="fw-semibold">${evt}</td>
            <td class="pn-muted">${com || "-"}</td>
          </tr>
        `;
          })
          .join("");

        if (window.feather) feather.replace();
      },
    );
  }

  // ===== Cargar FO =====
  function cargarFO() {
    if (!tb) return;

    renderFiltrosActivosFO();

    const payload = {
      search: toText(inpSearch?.value),
      estatus: toText(selEstatus?.value || "0"),
      fecha_ini: toText(inpFIni?.value),
      fecha_fin: toText(inpFFin?.value),
      page: state.page,
      page_size: state.pageSize,
    };

    // Loading
    tb.innerHTML = `
      <tr>
        <td colspan="8" class="text-center pn-muted py-4">
          Cargando operaciones FO...
        </td>
      </tr>
    `;

    xhrPost(ENDPOINT, payload, function (err, res) {
      if (err || !res || res.ok !== true) {
        const msg = res && res.msg ? res.msg : err ? err.message : "Error";
        tb.innerHTML = `
          <tr>
            <td colspan="8" class="text-center text-danger py-4">
              No se pudo cargar FO. ${esc(msg)}
            </td>
          </tr>
        `;
        if (pagingLbl) pagingLbl.textContent = "Mostrando 0–0 de 0";
        if (paging) paging.innerHTML = "";
        return;
      }

      state.total = parseInt(res.total || 0, 10);
      state.rows = Array.isArray(res.rows) ? res.rows : [];

      // Como el endpoint actual devuelve "ventana" (LIMIT = page*pageSize),
      // aplicamos filtros del lado cliente y luego hacemos slicing a la página actual.
      const filtradas = aplicarFiltrosCliente(state.rows);

      const totalFiltrado = filtradas.length; // por ahora UI local; el "total" real del server se mantiene
      // Si quieres que paginación siga "total server", usa state.total.
      // Pero si filtras cliente-side, paginar con totalFiltrado es más coherente visualmente.
      const totalParaUI =
        inpSearch?.value ||
        selEstatus?.value !== "0" ||
        inpFIni?.value ||
        inpFFin?.value
          ? totalFiltrado
          : state.total;

      const startIdx = (state.page - 1) * state.pageSize;
      const endIdx = startIdx + state.pageSize;
      const pageRows = filtradas.slice(startIdx, endIdx);

      renderTable(pageRows);
      renderPaging(state.page, state.pageSize, totalParaUI);
    });
  }

  // ===== Acciones tabla =====
  function onTableClick(e) {
    const a = e.target.closest("[data-fo-action]");
    if (!a) return;

    e.preventDefault();

    const action = a.getAttribute("data-fo-action");
    const id = parseInt(a.getAttribute("data-id") || "0", 10);
    if (!id) return;

    // Buscar row en cache (ojo: puede no estar si estás en otra página)
    const row =
      (state.rows || []).find(
        (x) => parseInt(x.id_operacion_ferro, 10) === id,
      ) || null;

    if (action === "detalle") {
      if (!modalDetalle || !row) return;

      // Pintar modal con lo que tenemos en listado (sin endpoint detalle por ahora)
      const opNum = toText(
        row.numero_operacion || `FO-${row.id_operacion_ferro}`,
      );
      const ferro = toText(row.contenedor_fisico || "-");
      const destino = toText(row.destino || "-");
      const bultos = toText(row.bultos_total || "0");
      const transp = toText(row.transportista || "-");
      const fecha = toText((row.fecha || "").toString().substring(0, 10));
      const est = toText(row.estatus || "-");
      const comm = toText(row.comentarios || "");

      // spans
      const setText = (idEl, val) => {
        const el = document.getElementById(idEl);
        if (el) el.textContent = val;
      };
      const setVal = (idEl, val) => {
        const el = document.getElementById(idEl);
        if (el) el.value = val;
      };

      setText("fo_numero", opNum);
      setText("fo_ferro", ferro);
      setText("fo_bultos", bultos);
      setText("fo_transportista", transp);
      setText("fo_destino", destino);
      setText("fo_fecha", fecha);

      // badge estatus
      const elBadge = document.getElementById("fo_estatus");
      if (elBadge) {
        elBadge.className = "badge " + badgeClassByEstatus(est);
        elBadge.textContent = est;
      }

      // inputs
      setVal("fo_numero_input", opNum);
      setVal("fo_ferro_input", ferro);
      setVal("fo_transportista_input", transp);
      setVal("fo_destino_input", destino);
      setVal("fo_bultos_input", bultos);
      setVal("fo_fecha_input", fecha);
      setVal("fo_comentarios", comm);

      if (window.feather) feather.replace();
      modalDetalle.show();
      // tablas con asignacion y evengtos
      cargarAsignacionesFO(id);
      cargarEventosFO(id);
      return;
    }

    if (action === "docs") {
      if (!modalDocs) return;

      // Configurar modal docs para FO
      const opNum =
        a.getAttribute("data-num") || (row ? row.numero_operacion : "");
      if (docsOperacionId) docsOperacionId.value = String(id);
      if (docsTipoOperacion) docsTipoOperacion.value = "FO";
      if (docsOperacionNumero)
        docsOperacionNumero.textContent = opNum ? opNum : `FO-${id}`;

      if (window.feather) feather.replace();
      modalDocs.show();
      return;
    }
  }

  // ===== Eventos filtros/paginación =====
  function onPagingClick(e) {
    const link = e.target.closest("[data-fo-page]");
    if (!link) return;
    e.preventDefault();

    const p = parseInt(link.getAttribute("data-fo-page") || "1", 10);
    if (!p || p < 1) return;
    state.page = p;
    cargarFO();
  }

  function limpiarFO() {
    if (inpSearch) inpSearch.value = "";
    if (selEstatus) selEstatus.value = "0";
    if (inpFIni) inpFIni.value = "";
    if (inpFFin) inpFFin.value = "";

    state.page = 1;
    renderFiltrosActivosFO();
    cargarFO();
  }

  // Debounce para buscar
  let tSearch = null;
  function onSearchInput() {
    if (tSearch) clearTimeout(tSearch);
    tSearch = setTimeout(function () {
      state.page = 1;
      cargarFO();
    }, 350);
  }

  // ===== Init =====
  function init() {
    if (!tb) return;

    // Handlers
    if (btnFiltrar)
      btnFiltrar.addEventListener("click", function () {
        state.page = 1;
        cargarFO();
      });

    if (btnLimpiar)
      btnLimpiar.addEventListener("click", function () {
        limpiarFO();
      });

    if (selPageSize) {
      selPageSize.addEventListener("change", function () {
        state.pageSize = parseInt(selPageSize.value, 10) || 15;
        state.page = 1;
        cargarFO();
      });
    }

    if (inpSearch) inpSearch.addEventListener("input", onSearchInput);
    if (selEstatus)
      selEstatus.addEventListener("change", function () {
        state.page = 1;
        cargarFO();
      });
    if (inpFIni)
      inpFIni.addEventListener("change", function () {
        state.page = 1;
        cargarFO();
      });
    if (inpFFin)
      inpFFin.addEventListener("change", function () {
        state.page = 1;
        cargarFO();
      });

    if (paging) paging.addEventListener("click", onPagingClick);
    if (tb) tb.addEventListener("click", onTableClick);

    // Refrescar todo (si existe)
    if (btnRefrescarTodo) {
      btnRefrescarTodo.addEventListener("click", function () {
        cargarFO();
      });
    }

    // Primera carga
    renderFiltrosActivosFO();
    cargarFO();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
