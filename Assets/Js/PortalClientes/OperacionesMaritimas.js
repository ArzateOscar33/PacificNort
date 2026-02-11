/* Assets/Js/PortalClientes/OperacionesMaritimas.js
   Lista operaciones (tabla operaciones) MAR + LBMF para Portal Cliente
   (XHR + JSON limpio + paginación + filtros)
*/
(function () {
  "use strict";

  // ===== Config BASE_URL =====
  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  const ENDPOINT = `${BASE_URL}PortalClientes/listarOperacionesCliente`;

  //detalle maritima
  const ENDPOINT_DETALLE_MAR = `${BASE_URL}PortalClientes/detalleMaritima`;

  // ===== Refs UI (IDs de tu vista) =====
  const tb = document.getElementById("tbOpsMar");
  const pagingLbl = document.getElementById("marPagingLbl");
  const paging = document.getElementById("marPaging");
  const selPageSize = document.getElementById("marPageSize");

  const inpSearch = document.getElementById("marSearch");
  const selTipo = document.getElementById("marTipo");
  const selEstatus = document.getElementById("marEstatus");
  const inpEtaIni = document.getElementById("marEtaIni");
  const inpEtaFin = document.getElementById("marEtaFin");

  const btnFiltrar = document.getElementById("btnMarFiltrar");
  const btnLimpiar = document.getElementById("btnMarLimpiar");
  const btnRefrescar = document.getElementById("btnRefrescarTodo");

  // Chips (opcionales; si no existen, no pasa nada)
  const chipCliente = document.getElementById("chipCliente");
  const chipEstatus = document.getElementById("chipEstatus");
  const filtrosBar = document.getElementById("filtrosActivosBar");

  // ===== Estado =====
  const state = {
    page: 1,
    pageSize: selPageSize ? parseInt(selPageSize.value || "15", 10) : 15,
    total: 0,
    rows: [],
    filters: {
      search: "",
      tipo: "",
      estatus: 0,
      eta_ini: "",
      eta_fin: "",
    },
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

  //filtros
  function buildChip(label, value) {
    return `
    <span class="chip">
      <i data-feather="tag" style="width:14px;height:14px;"></i>
      ${esc(label)}: ${esc(value)}
    </span>
  `;
  }

  function renderFiltrosActivosMar() {
    if (!filtrosBar) return;

    const chips = [];

    const s = inpSearch ? String(inpSearch.value || "").trim() : "";
    const estVal = selEstatus ? parseInt(selEstatus.value || "0", 10) : 0;
    const etaIni = inpEtaIni ? String(inpEtaIni.value || "") : "";
    const etaFin = inpEtaFin ? String(inpEtaFin.value || "") : "";

    // OJO: en tu HTML no existe marTipo, así que esto solo aplica si luego lo agregas.
    const tipoVal = selTipo ? String(selTipo.value || "") : "";

    if (s) chips.push(buildChip("Buscar", s));

    if (estVal && selEstatus) {
      const opt = selEstatus.options[selEstatus.selectedIndex];
      chips.push(buildChip("Estatus", opt ? opt.text : String(estVal)));
    }

    if (tipoVal) chips.push(buildChip("Tipo", tipoVal));
    if (etaIni) chips.push(buildChip("ETA ini", etaIni));
    if (etaFin) chips.push(buildChip("ETA fin", etaFin));

    filtrosBar.innerHTML = chips.join(" ");

    if (window.feather) window.feather.replace();
  }

  function fmtDate(d) {
    if (!d) return "";
    // si viene "YYYY-MM-DD ..." recorta
    const s = String(d);
    return s.length >= 10 ? s.slice(0, 10) : s;
  }

  function badgeEstatus(row) {
    // Tu vista usa "badge-soft" (puedes personalizar)
    const name = row.estatus || "—";
    return `<span class="badge badge-soft">${esc(name)}</span>`;
  }

  function buildQueryFromUI() {
    state.filters.search = inpSearch
      ? String(inpSearch.value || "").trim()
      : "";
    state.filters.tipo = selTipo ? String(selTipo.value || "") : "";
    state.filters.estatus = selEstatus
      ? parseInt(selEstatus.value || "0", 10)
      : 0;
    state.filters.eta_ini = inpEtaIni ? String(inpEtaIni.value || "") : "";
    state.filters.eta_fin = inpEtaFin ? String(inpEtaFin.value || "") : "";
  }

  function syncChips() {
    // Cliente: normalmente lo pones desde PHP en la vista; aquí no lo tocamos.
    if (chipEstatus) {
      const est = state.filters.estatus;
      const txt =
        est === 0
          ? "Todos"
          : est === 1
            ? "Pendiente"
            : est === 5
              ? "En revisión"
              : est === 9
                ? "Abierta"
                : est === 2
                  ? "Cerrada"
                  : `Estatus ${est}`;
      chipEstatus.innerHTML = `<i data-feather="tag" style="width:16px;height:16px;"></i> ${esc(txt)}`;
    }
  }

  // ===== XHR helper (tu estilo) =====
  function xhrPost(url, dataObj, cb) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    // Mandamos como x-www-form-urlencoded para que $_POST lo lea seguro
    xhr.setRequestHeader(
      "Content-Type",
      "application/x-www-form-urlencoded; charset=UTF-8",
    );

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        let json = null;
        try {
          json = JSON.parse(xhr.responseText);
        } catch (e) {
          cb(new Error("Respuesta no válida (JSON)"), null);
          return;
        }
        cb(null, json);
      } else {
        cb(new Error("Error HTTP " + xhr.status), null);
      }
    };

    xhr.send(toFormUrlEncoded(dataObj));
  }

  function toFormUrlEncoded(obj) {
    const parts = [];
    for (const k in obj) {
      if (!Object.prototype.hasOwnProperty.call(obj, k)) continue;
      parts.push(
        encodeURIComponent(k) + "=" + encodeURIComponent(obj[k] ?? ""),
      );
    }
    return parts.join("&");
  }

  // ===== Render tabla =====
  function renderTable(rows) {
    if (!tb) return;

    if (!rows || rows.length === 0) {
      tb.innerHTML = `
        <tr>
          <td colspan="7" class="text-center py-4 pn-muted">
            No hay operaciones para mostrar con los filtros actuales.
          </td>
        </tr>
      `;
      return;
    }

    let html = "";
    for (let i = 0; i < rows.length; i++) {
      const r = rows[i];

      const opId = r.id_operacion ?? 0;
      const opNum = r.numero_operacion ?? "";
      const bl = r.numero_bl ?? "";
      const conts = r.contenedores ?? ""; // viene del GROUP_CONCAT
      const etd = fmtDate(r.etd);
      const eta = fmtDate(r.eta);

      html += `
        <tr>
          <td class="fw-semibold">${esc(opNum)}</td>
          <td>${esc(conts || "—")}</td>
          <td>${esc(bl || "—")}</td>
          <td>${esc(etd || "—")}</td>
          <td>${esc(eta || "—")}</td>
          <td>${badgeEstatus(r)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
                <!-- ✅ Botón Detalle -->
                <button class="btn btn-outline-dark"
                        type="button"
                        data-action="detalle-mar"
                        data-id="${esc(opId)}">
                <i data-feather="eye" class="me-1"></i> Ver
                </button>

                <button class="btn btn-outline-primary"
                        type="button"
                        data-action="docs"
                        data-id="${esc(opId)}"
                        data-num="${esc(opNum)}">
                <i data-feather="file-text" class="me-1"></i> Docs
                </button>
            </div>
            </td>

        </tr>
      `;
    }

    tb.innerHTML = html;

    // Reemplaza íconos después de pintar
    if (window.feather) window.feather.replace();
  }

  // ===== Paginación =====
  function renderPaging() {
    if (!paging || !pagingLbl) return;

    const total = state.total;
    const pageSize = state.pageSize;
    const page = state.page;

    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    const from = total === 0 ? 0 : (page - 1) * pageSize + 1;
    const to = Math.min(page * pageSize, total);

    pagingLbl.textContent = `Mostrando ${from}–${to} de ${total}`;

    // Si no hay resultados, no pintamos páginas
    if (total === 0) {
      paging.innerHTML = "";
      return;
    }

    // Ventana de páginas (tu estilo "compacto")
    const win = 2;
    let start = Math.max(1, page - win);
    let end = Math.min(totalPages, page + win);

    // Ajuste para mantener ancho
    if (end - start < win * 2) {
      start = Math.max(1, end - win * 2);
      end = Math.min(totalPages, start + win * 2);
    }

    let html = "";

    // Prev
    html += page > 1 ? pageBtn("«", page - 1) : pageBtn("«", 1, true);

    // First + ellipsis
    if (start > 1) {
      html += pageBtn("1", 1, page === 1);
      if (start > 2) html += ellipsis();
    }

    // Window
    for (let p = start; p <= end; p++) {
      html += pageBtn(String(p), p, p === page);
    }

    // Last + ellipsis
    if (end < totalPages) {
      if (end < totalPages - 1) html += ellipsis();
      html += pageBtn(String(totalPages), totalPages, page === totalPages);
    }

    // Next
    html +=
      page < totalPages
        ? pageBtn("»", page + 1)
        : pageBtn("»", totalPages, true);

    paging.innerHTML = html;
  }

  function pageBtn(label, page, disabledOrActive) {
    const isActive =
      disabledOrActive === true &&
      /^[0-9]+$/.test(label) &&
      parseInt(label, 10) === state.page;
    const disabled = disabledOrActive === true && !isActive;

    const clsLi = `page-item ${isActive ? "active" : ""} ${disabled ? "disabled" : ""}`;
    const clsA = "page-link";
    return `
      <li class="${clsLi}">
        <a class="${clsA}" href="#" data-page="${page}">${esc(label)}</a>
      </li>
    `;
  }

  function ellipsis() {
    return `
      <li class="page-item disabled">
        <span class="page-link">…</span>
      </li>
    `;
  }

  //modal maritimo
  // ===== Modal refs (Marítima) =====
  const modalMar = document.getElementById("modalDetalleMaritima");

  const elMarNumero = document.getElementById("mar_numero");
  const elMarCliente = document.getElementById("mar_cliente");
  const elMarTipo = document.getElementById("mar_tipo");
  const elMarEstatus = document.getElementById("mar_estatus");
  const elMarEtd = document.getElementById("mar_etd");
  const elMarEta = document.getElementById("mar_eta");
  const elMarBl = document.getElementById("mar_bl");

  const inpMarNumero = document.getElementById("mar_numero_input");
  const inpMarPuerto = document.getElementById("mar_puerto");
  const inpMarNaviera = document.getElementById("mar_naviera");
  const inpMarContenedor = document.getElementById("mar_contenedor");
  const txtMarComentario = document.getElementById("mar_comentario");

  const tbMarEventos = document.getElementById("mar_eventos");

  function tipoLabel(tipoClave) {
    const t = String(tipoClave || "").toUpperCase();
    if (t === "LBMF") return "Mixto (LBMF)";
    if (t === "MAR") return "Marítimo";
    return t || "—";
  }

  function setBadgeEstatus(el, estatusTxt) {
    if (!el) return;
    const t = String(estatusTxt || "—");

    // limpia clases bootstrap badge
    el.classList.remove(
      "text-bg-success",
      "text-bg-warning",
      "text-bg-danger",
      "text-bg-secondary",
      "text-bg-info",
      "text-bg-primary",
    );

    // mapeo simple por texto (ajusta si prefieres por id)
    const low = t.toLowerCase();
    if (low.includes("abier")) el.classList.add("text-bg-success");
    else if (low.includes("revisi")) el.classList.add("text-bg-warning");
    else if (low.includes("pend")) el.classList.add("text-bg-secondary");
    else if (low.includes("cerr")) el.classList.add("text-bg-danger");
    else el.classList.add("text-bg-primary");

    el.textContent = t;
  }

  function renderEventosMar(rows) {
    if (!tbMarEventos) return;

    if (!rows || rows.length === 0) {
      tbMarEventos.innerHTML = `
        <tr>
          <td colspan="3" class="text-center pn-muted py-3">Sin eventos</td>
        </tr>
      `;
      return;
    }

    let html = "";
    for (let i = 0; i < rows.length; i++) {
      const r = rows[i];
      html += `
        <tr>
          <td>${esc(fmtDate(r.fecha) || "—")}</td>
          <td>${esc(r.evento || "—")}</td>
          <td class="pn-muted">${esc(r.comentario || "")}</td>
        </tr>
      `;
    }
    tbMarEventos.innerHTML = html;
  }

  function limpiarModalMar() {
    if (elMarNumero) elMarNumero.textContent = "—";
    if (inpMarNumero) inpMarNumero.value = "";

    if (elMarCliente) elMarCliente.textContent = "—";
    if (elMarTipo) elMarTipo.textContent = "—";
    if (elMarEstatus) setBadgeEstatus(elMarEstatus, "—");

    if (elMarEtd) elMarEtd.textContent = "—";
    if (elMarEta) elMarEta.textContent = "—";
    if (elMarBl) elMarBl.textContent = "—";

    if (inpMarPuerto) inpMarPuerto.value = "";
    if (inpMarNaviera) inpMarNaviera.value = "";
    if (inpMarContenedor) inpMarContenedor.value = "";
    if (txtMarComentario) txtMarComentario.value = "";

    if (tbMarEventos) {
      tbMarEventos.innerHTML = `
        <tr>
          <td colspan="3" class="text-center pn-muted py-3">Cargando...</td>
        </tr>
      `;
    }
  }

  function fillModalMar(det) {
    // det = json.detalle
    const opNum = det.numero_operacion || "—";
    const cliente = det.cliente || "—";
    const tipo = det.tipo_nombre || tipoLabel(det.tipo_clave);
    const estatus = det.estatus || "—";
    if (inpMarPuerto) inpMarPuerto.value = det.puerto || "";
    if (inpMarNaviera) inpMarNaviera.value = det.naviera || "";
    if (txtMarComentario) txtMarComentario.value = det.comentario || "";

    if (elMarNumero) elMarNumero.textContent = opNum;
    if (inpMarNumero) inpMarNumero.value = opNum;

    if (elMarCliente) elMarCliente.textContent = cliente;
    if (elMarTipo) elMarTipo.textContent = tipo;
    if (elMarEstatus) setBadgeEstatus(elMarEstatus, estatus);

    if (elMarEtd) elMarEtd.textContent = fmtDate(det.etd) || "—";
    if (elMarEta) elMarEta.textContent = fmtDate(det.eta) || "—";
    if (elMarBl) elMarBl.textContent = det.numero_bl || "—";

    // Contenedores (concat)
    if (inpMarContenedor) inpMarContenedor.value = det.contenedores || "";

    // Campos opcionales (dependen de tu SQL final)
    if (inpMarPuerto) inpMarPuerto.value = det.puerto || "";
    if (inpMarNaviera) inpMarNaviera.value = det.naviera || "";
    if (txtMarComentario) txtMarComentario.value = det.comentario || "";
  }

  function cargarDetalleMaritima(opId) {
    if (!opId || opId <= 0) return;

    limpiarModalMar();

    xhrPost(ENDPOINT_DETALLE_MAR, { id_operacion: opId }, function (err, json) {
      if (err) {
        // deja el modal con mensaje
        if (tbMarEventos) {
          tbMarEventos.innerHTML = `
            <tr>
              <td colspan="3" class="text-center text-danger py-3">
                No se pudo cargar el detalle.
              </td>
            </tr>
          `;
        }
        return;
      }

      if (!json || json.ok !== true) {
        const msg =
          json && json.msg ? json.msg : "No se pudo obtener el detalle.";
        if (tbMarEventos) {
          tbMarEventos.innerHTML = `
            <tr>
              <td colspan="3" class="text-center text-danger py-3">
                ${esc(msg)}
              </td>
            </tr>
          `;
        }
        return;
      }

      const det = json.detalle || null;
      if (!det) {
        if (tbMarEventos) {
          tbMarEventos.innerHTML = `
            <tr>
              <td colspan="3" class="text-center text-danger py-3">
                Detalle vacío.
              </td>
            </tr>
          `;
        }
        return;
      }

      fillModalMar(det);
      renderEventosMar(Array.isArray(json.eventos) ? json.eventos : []);

      if (window.feather) window.feather.replace();
    });
  }

  // ===== Cargar datos =====
  function cargarListado() {
    buildQueryFromUI();
    renderFiltrosActivosMar();
    syncChips();

    const payload = {
      page: state.page,
      page_size: state.pageSize,
      search: state.filters.search,
      tipo: state.filters.tipo,
      estatus: state.filters.estatus,
      eta_ini: state.filters.eta_ini,
      eta_fin: state.filters.eta_fin,
    };

    // Loading
    if (tb) {
      tb.innerHTML = `
        <tr>
          <td colspan="7" class="text-center py-4 pn-muted">
            Cargando...
          </td>
        </tr>
      `;
    }

    xhrPost(ENDPOINT, payload, function (err, json) {
      if (err) {
        if (tb) {
          tb.innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-4 text-danger">
                No se pudo cargar el listado.
              </td>
            </tr>
          `;
        }
        if (pagingLbl) pagingLbl.textContent = "Mostrando 0–0 de 0";
        if (paging) paging.innerHTML = "";
        return;
      }

      if (!json || json.ok !== true) {
        const msg = json && json.msg ? json.msg : "Respuesta inválida.";
        if (tb) {
          tb.innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-4 text-danger">
                ${esc(msg)}
              </td>
            </tr>
          `;
        }
        if (pagingLbl) pagingLbl.textContent = "Mostrando 0–0 de 0";
        if (paging) paging.innerHTML = "";
        return;
      }

      state.rows = Array.isArray(json.rows) ? json.rows : [];
      state.total = parseInt(json.total || "0", 10) || 0;

      // Si el user quedó en page muy alta (por filtros), corrige
      const totalPages = Math.max(1, Math.ceil(state.total / state.pageSize));
      if (state.page > totalPages) {
        state.page = totalPages;
        cargarListado();
        return;
      }

      renderTable(state.rows);
      renderPaging();
    });
  }

  // ===== Events =====
  // Filtrar
  if (btnFiltrar) {
    btnFiltrar.addEventListener("click", function () {
      state.page = 1;
      cargarListado();
    });
  }

  // Limpiar
  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", function () {
      if (inpSearch) inpSearch.value = "";
      if (selTipo) selTipo.value = "";
      if (selEstatus) selEstatus.value = "0";
      if (inpEtaIni) inpEtaIni.value = "";
      if (inpEtaFin) inpEtaFin.value = "";

      state.page = 1;
      renderFiltrosActivosMar();
      cargarListado();
    });
  }

  // Refrescar
  if (btnRefrescar) {
    btnRefrescar.addEventListener("click", function () {
      cargarListado();
    });
  }

  // Page size
  if (selPageSize) {
    selPageSize.addEventListener("change", function () {
      state.pageSize = parseInt(selPageSize.value || "15", 10) || 15;
      state.page = 1;
      cargarListado();
    });
  }

  // Paging click (delegado)
  if (paging) {
    paging.addEventListener("click", function (e) {
      const a = e.target.closest("a[data-page]");
      if (!a) return;
      e.preventDefault();
      const p = parseInt(a.getAttribute("data-page") || "1", 10);
      if (!p || p === state.page) return;
      state.page = p;
      cargarListado();
    });
  }

  // Tabla acciones (delegado): abrir modal documentos
  if (tb) {
    tb.addEventListener("click", function (e) {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;

      const action = btn.getAttribute("data-action");
      const opId = parseInt(btn.getAttribute("data-id") || "0", 10) || 0;

      // ✅ 1) Abrir modal detalle Marítima
      if (action === "detalle-mar") {
        console.log("CLICK detalle-mar", { opId });
        console.log("modalMar:", modalMar);
        console.log("bootstrap:", window.bootstrap);

        if (!modalMar) {
          alert("No se encontró #modalDetalleMaritima en el DOM");
          return;
        }
        if (!window.bootstrap || !window.bootstrap.Modal) {
          alert(
            "Bootstrap Modal no está disponible (window.bootstrap undefined)",
          );
          return;
        }

        const m = window.bootstrap.Modal.getOrCreateInstance(modalMar);
        m.show();

        cargarDetalleMaritima(opId);
        return;
      }

      // ✅ 2) Abrir modal Docs (lo que ya tenías)
      if (action === "docs") {
        const opNum = btn.getAttribute("data-num") || "";

        const elId = document.getElementById("docsOperacionId");
        const elTipo = document.getElementById("docsTipoOperacion");
        const elNum = document.getElementById("docsOperacionNumero");

        if (elId) elId.value = String(opId);
        if (elNum) elNum.textContent = opNum || "—";

        // tipo real (MAR/LBMF)
        let tipo = "MAR";
        for (let i = 0; i < state.rows.length; i++) {
          if (String(state.rows[i].id_operacion) === String(opId)) {
            tipo = state.rows[i].tipo_clave || "MAR";
            break;
          }
        }
        if (elTipo) elTipo.value = tipo;

        const modalDocs = document.getElementById("modalDocs");
        if (modalDocs && window.bootstrap) {
          const m = window.bootstrap.Modal.getOrCreateInstance(modalDocs);
          m.show();
        }
        return;
      }
    });
  }

  // UX: Enter en buscar → filtrar
  if (inpSearch) {
    inpSearch.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        state.page = 1;
        cargarListado();
      }
    });
  }
  // ===== Buscar en vivo (debounce) como FO =====
  let tSearch = null;

  function onSearchInput() {
    if (tSearch) clearTimeout(tSearch);
    tSearch = setTimeout(function () {
      state.page = 1;
      renderFiltrosActivosMar(); // para que el chip "Buscar" se actualice mientras escribes
      cargarListado();
    }, 350);
  }

  if (inpSearch) {
    inpSearch.addEventListener("input", onSearchInput);
  }

  if (selTipo)
    selTipo.addEventListener("change", () => {
      state.page = 1;
      renderFiltrosActivosMar();
      cargarListado();
    });
  if (selEstatus)
    selEstatus.addEventListener("change", () => {
      state.page = 1;
      renderFiltrosActivosMar();
      cargarListado();
    });
  if (inpEtaIni)
    inpEtaIni.addEventListener("change", () => {
      state.page = 1;
      renderFiltrosActivosMar();
      cargarListado();
    });
  if (inpEtaFin)
    inpEtaFin.addEventListener("change", () => {
      state.page = 1;
      renderFiltrosActivosMar();
      cargarListado();
    });

  // ===== Init =====
  cargarListado();
})();
