// ============================================================
// Resumen MF (solo Marítimo) - resumen.js
// Controlador: Operaciones_maritimo_ferro_resumen
// Endpoints usados:
//   - sugerencias
//   - listarContenedoresPorOperacion
//   - detalles_contenedor (solo MARITIMO)
//   - faltantes (solo contenedor marítimo)
//   - costos_totales_operacion
//   - costos_desglosados_operacion
//   - eventos_contenedor (solo MARITIMO)
//   - eventos_progreso (solo MARITIMO)
// ============================================================

(function () {
  "use strict";

  // -------------------------
  // Refs + estado
  // -------------------------
  const selectContenedorResumen = document.getElementById(
    "selectContenedorResumen",
  );
  const inpBuscarOpResumen = document.getElementById("buscarOperacionResumen");
  const boxSugsOpResumen = document.getElementById(
    "sugerenciasOperacionResumen",
  );
  const btnRefResumen = document.getElementById("btnRefrescarResumen");
  const btnPdfResumen = document.getElementById("btnExportPdfResumen");

  // Cards / badges
  const elPuerto = document.getElementById("puertoResumen");
  const elEta = document.getElementById("etaContenedor");
  const elEtd = document.getElementById("etdContenedor");
  const elBl = document.getElementById("blContenedor");
  const elComentarios = document.getElementById("comentarioContenedor");

  const elDocsPend = document.getElementById("docsPendientesResumen");
  const badgeTotalCostos = document.getElementById("badgeTotalCostos");
  const badgeEventosResumen = document.getElementById("badgeEventosResumen");

  // Documentos faltantes UI
  const dfContenedorInfoResumen = document.getElementById("dfContenedorInfo");
  const dfBadgeCountResumen = document.getElementById("dfBadgeCount");
  const dfLoadingResumen = document.getElementById("dfLoading");
  const dfEmptyResumen = document.getElementById("dfEmpty");
  const dfListaResumen = document.getElementById("dfLista");

  // Eventos tabla
  const tbodyEventosResumen = document.getElementById("tablaEventosLogisticos");

  // Moneda / TC (gráfico)
  const selMoneda = document.getElementById("costosResumenMonedaVista");
  const inpTC = document.getElementById("costosResumenTipoCambio");

  // (Opcional) lista de costos si existe en tu vista (en lo pegado NO existe)
  const listaCostos = document.getElementById("listaCostosContenedor");

  // Estado
  let operacionIdActivoResumen = null;
  let contenedorMaritimoIdActivo = null;
  let lastXHRContenedoresResumen = null;
  let lastXHRSugerenciasResumen = null;
  let lastXHRFaltantesResumen = null;
  let debounceTimerResumen = null;
  let opLabelSeleccionadaResumen = null;

  // -------------------------
  // Init charts + PDF btn
  // -------------------------
  document.addEventListener("DOMContentLoaded", function () {
    if (window.CostosChart) CostosChart.init("costosChart", "costosLeyenda");
    if (window.TimelineChart) TimelineChart.init("timelineChart");
    setExportPdfEnabledResumen(false);

    // listeners moneda/tc para CostosChart (si existe)
    if (selMoneda && inpTC && window.CostosChart) {
      selMoneda.addEventListener("change", () => {
        CostosChart.setDisplayCurrency(selMoneda.value, Number(inpTC.value));
      });
      inpTC.addEventListener("input", () => {
        CostosChart.setDisplayCurrency(selMoneda.value, Number(inpTC.value));
      });
    }
  });

  function setExportPdfEnabledResumen(enabled) {
    if (!btnPdfResumen) return;
    btnPdfResumen.disabled = !enabled;
    btnPdfResumen.classList.toggle("disabled", !enabled);
  }

  // -------------------------
  // Helpers UI (contenedores)
  // -------------------------
  function setContenedoresLoadingResumen() {
    if (!selectContenedorResumen) return;
    selectContenedorResumen.innerHTML =
      '<option value="">Cargando contenedores…</option>';
  }
  function setContenedoresEmptyResumen(msg = "Sin contenedores") {
    if (!selectContenedorResumen) return;
    selectContenedorResumen.innerHTML = `<option value="">${msg}</option>`;
    setExportPdfEnabledResumen(false);
  }
  function clearSugerenciasResumen() {
    if (!boxSugsOpResumen) return;
    boxSugsOpResumen.style.display = "none";
    boxSugsOpResumen.innerHTML = "";
  }

  // -------------------------
  // Helpers UI (detalle)
  // -------------------------
  function safeSetText(el, v) {
    if (!el) return;
    el.textContent = v == null || v === "" ? "—" : String(v);
  }

  function limpiarDetalleUIResumen() {
    safeSetText(elPuerto, "—");
    safeSetText(elEta, "—");
    safeSetText(elEtd, "—");
    safeSetText(elBl, "—");
    safeSetText(elComentarios, "—");
  }

  function setDetalleLoadingResumen() {
    safeSetText(elComentarios, "Cargando…");
  }

  // -------------------------
  // Helpers UI (DF)
  // -------------------------
  function toggleDFResumen(loading = false, hasData = false, empty = false) {
    if (dfLoadingResumen)
      dfLoadingResumen.style.display = loading ? "" : "none";
    if (dfListaResumen) dfListaResumen.style.display = hasData ? "" : "none";
    if (dfEmptyResumen) dfEmptyResumen.style.display = empty ? "" : "none";
  }

  function setDFHeaderResumen(infoTexto, count = 0) {
    if (dfContenedorInfoResumen)
      dfContenedorInfoResumen.textContent =
        infoTexto || "Seleccione un contenedor…";
    if (dfBadgeCountResumen)
      dfBadgeCountResumen.textContent = String(count || 0);
    if (elDocsPend) elDocsPend.textContent = String(count || 0);
  }

  function escapeHtmlResumen(s) {
    return String(s ?? "").replace(
      /[&<>"']/g,
      (m) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[m],
    );
  }

  function renderFaltantesResumen(items) {
    if (!dfListaResumen) return;

    dfListaResumen.innerHTML = "";
    const count = Array.isArray(items) ? items.length : 0;

    if (count === 0) {
      toggleDFResumen(false, false, true);
      setDFHeaderResumen(
        dfContenedorInfoResumen?.textContent || "Seleccione un contenedor…",
        0,
      );
      return;
    }

    items.forEach((row) => {
      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-center";
      const nombre = escapeHtmlResumen(row.nombre);
      const clave = escapeHtmlResumen(row.clave ?? "");
      li.innerHTML = `<span>${nombre}</span><span class="badge bg-light text-dark">${clave}</span>`;
      dfListaResumen.appendChild(li);
    });

    toggleDFResumen(false, true, false);
    setDFHeaderResumen(dfContenedorInfoResumen?.textContent || "—", count);
  }

  // -------------------------
  // Helpers UI (costos)
  // -------------------------
  function setTotalCostos(v) {
    if (!badgeTotalCostos) return;
    badgeTotalCostos.textContent = String(v ?? "—");
  }

  function renderCostosDesglosadosOperacion(rows) {
    if (!listaCostos) return; // en tu vista pegada NO existe, pero si existe en otra, lo soporta

    listaCostos.innerHTML = "";
    if (!Array.isArray(rows) || rows.length === 0) {
      listaCostos.innerHTML =
        '<li class="list-group-item text-muted">Sin costos</li>';
      return;
    }

    rows.forEach((r) => {
      const li = document.createElement("li");
      li.className = "list-group-item d-flex justify-content-between";
      const desc = (r.nombre_movimiento || r.comentario || "").toString();
      const monto = Number(r.monto || 0);
      li.innerHTML = `<span>${escapeHtmlResumen(desc)}</span><strong>${monto.toFixed(2)}</strong>`;
      listaCostos.appendChild(li);
    });
  }

  function fetchCostosTotalesOperacion(operacionId) {
    setTotalCostos("…");
    const url =
      `${base_url}Operaciones_maritimo_ferro_resumen/costos_totales_operacion` +
      `?operacion_id=${encodeURIComponent(operacionId)}`;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) {
        setTotalCostos("—");
        return;
      }
      let r;
      try {
        r = JSON.parse(xhr.responseText);
      } catch {
        setTotalCostos("—");
        return;
      }
      if (!r || r.status !== "ok") {
        setTotalCostos("—");
        return;
      }
      const data = r.data || {};
      const totalFmt = data.total_fmt ?? Number(data.total || 0).toFixed(2);
      setTotalCostos(`$${totalFmt}`);
    };
    xhr.send();
  }

  function fetchCostosDesglosadosOperacion(operacionId) {
    if (listaCostos)
      listaCostos.innerHTML =
        '<li class="list-group-item text-muted">Cargando…</li>';

    const url =
      `${base_url}Operaciones_maritimo_ferro_resumen/costos_desglosados_operacion` +
      `?operacion_id=${encodeURIComponent(operacionId)}`;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) {
        renderCostosDesglosadosOperacion([]);
        return;
      }
      let r;
      try {
        r = JSON.parse(xhr.responseText);
      } catch {
        renderCostosDesglosadosOperacion([]);
        return;
      }
      if (!r || r.status !== "ok") {
        renderCostosDesglosadosOperacion([]);
        return;
      }
      renderCostosDesglosadosOperacion(r.data);
    };
    xhr.send();

    // si tienes CostosChart: ahora es por operación
    if (window.CostosChart && typeof CostosChart.update === "function") {
      CostosChart.update({ tipo: "OP", operacionId: Number(operacionId) });
    }
  }

  // -------------------------
  // Eventos (tabla + timeline)
  // -------------------------
  function setEventosLoadingResumen() {
    if (!tbodyEventosResumen) return;
    tbodyEventosResumen.innerHTML = `<tr><td colspan="2" class="text-muted">Cargando eventos…</td></tr>`;
  }

  function setEventosEmptyResumen() {
    if (!tbodyEventosResumen) return;
    tbodyEventosResumen.innerHTML = `<tr><td colspan="2" class="text-muted">Sin eventos</td></tr>`;
  }

  function fmtFechaResumen(isoLike) {
    if (!isoLike) return "—";
    const s = String(isoLike).trim();
    const parts = s.split(" ");
    const ymd = parts[0] || "";
    const time = (parts[1] || "").slice(0, 5);
    const [y, m, d] = ymd.split("-");
    if (!y || !m || !d) return s;
    return `${d}/${m}/${y}${time ? " " + time : ""}`;
  }

  function renderEventosResumen(rows) {
    if (!tbodyEventosResumen) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      setEventosEmptyResumen();
      return;
    }

    const frag = document.createDocumentFragment();
    rows.forEach((r) => {
      const tr = document.createElement("tr");
      const tdF = document.createElement("td");
      const tdE = document.createElement("td");
      tdF.textContent = fmtFechaResumen(r.fecha);
      tdE.textContent = r.nombre_evento || "(sin nombre)";
      tr.appendChild(tdF);
      tr.appendChild(tdE);
      frag.appendChild(tr);
    });

    tbodyEventosResumen.innerHTML = "";
    tbodyEventosResumen.appendChild(frag);
  }

  function cargarEventosResumen(operacionId, contenedorMaritimoId) {
    if (!operacionId || !contenedorMaritimoId) {
      setEventosEmptyResumen();
      if (
        window.TimelineChart &&
        typeof TimelineChart.setEventos === "function"
      )
        TimelineChart.setEventos([]);
      return;
    }

    setEventosLoadingResumen();

    const url =
      `${base_url}Operaciones_maritimo_ferro_resumen/eventos_contenedor` +
      `?operacion_id=${encodeURIComponent(operacionId)}` +
      `&tipo=${encodeURIComponent("MARITIMO")}` +
      `&id_contenedor=${encodeURIComponent(contenedorMaritimoId)}`;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) {
        setEventosEmptyResumen();
        if (
          window.TimelineChart &&
          typeof TimelineChart.setEventos === "function"
        )
          TimelineChart.setEventos([]);
        return;
      }
      let r;
      try {
        r = JSON.parse(xhr.responseText);
      } catch {
        setEventosEmptyResumen();
        if (
          window.TimelineChart &&
          typeof TimelineChart.setEventos === "function"
        )
          TimelineChart.setEventos([]);
        return;
      }

      if (r.status === "ok" && Array.isArray(r.data)) {
        renderEventosResumen(r.data);
        if (
          window.TimelineChart &&
          typeof TimelineChart.setEventos === "function"
        ) {
          TimelineChart.setEventos(r.data);
        }
      } else {
        setEventosEmptyResumen();
        if (
          window.TimelineChart &&
          typeof TimelineChart.setEventos === "function"
        )
          TimelineChart.setEventos([]);
      }
    };
    xhr.send();
  }

  function setEventosBadgeResumen(completados, total) {
    if (!badgeEventosResumen) return;
    badgeEventosResumen.textContent = `${Number(completados || 0)} / ${Number(total || 0)}`;
  }

  function fetchEventosProgresoResumen(operacionId, contenedorMaritimoId) {
    if (!operacionId || !contenedorMaritimoId) {
      setEventosBadgeResumen(0, 0);
      return;
    }

    const url =
      `${base_url}Operaciones_maritimo_ferro_resumen/eventos_progreso` +
      `?operacion_id=${encodeURIComponent(operacionId)}` +
      `&tipo=${encodeURIComponent("MARITIMO")}` +
      `&id_contenedor=${encodeURIComponent(contenedorMaritimoId)}`;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) {
        setEventosBadgeResumen(0, 0);
        return;
      }

      let r;
      try {
        r = JSON.parse(xhr.responseText);
      } catch {
        setEventosBadgeResumen(0, 0);
        return;
      }

      if (r.status === "ok" && r.data) {
        setEventosBadgeResumen(r.data.completados, r.data.total);
      } else {
        setEventosBadgeResumen(0, 0);
      }
    };
    xhr.send();
  }

  // -------------------------
  // Reset total
  // -------------------------
  function resetOperacionSeleccionResumen() {
    operacionIdActivoResumen = null;
    contenedorMaritimoIdActivo = null;

    try {
      lastXHRContenedoresResumen?.abort();
    } catch (e) {}
    try {
      lastXHRSugerenciasResumen?.abort();
    } catch (e) {}
    try {
      lastXHRFaltantesResumen?.abort();
    } catch (e) {}

    setContenedoresEmptyResumen("-- Selecciona una Operación --");
    limpiarDetalleUIResumen();

    setDFHeaderResumen("Seleccione un contenedor…", 0);
    toggleDFResumen(false, false, false);
    if (dfListaResumen) dfListaResumen.innerHTML = "";

    setEventosEmptyResumen();
    setEventosBadgeResumen(0, 0);
    if (
      window.TimelineChart &&
      typeof TimelineChart.setEventos === "function"
    ) {
      TimelineChart.setEventos([]);
    }

    setTotalCostos("$0");
    if (listaCostos)
      listaCostos.innerHTML =
        '<li class="list-group-item text-muted">Sin costos</li>';
    if (window.CostosChart && typeof CostosChart.clear === "function") {
      CostosChart.clear();
    }

    setExportPdfEnabledResumen(false);
  }

  // -------------------------
  // Sugerencias (autocomplete) - MF only
  // -------------------------
  function renderSugerenciasResumen(items) {
    if (!boxSugsOpResumen) return;

    if (!Array.isArray(items) || items.length === 0) {
      clearSugerenciasResumen();
      return;
    }

    boxSugsOpResumen.innerHTML = "";
    items.forEach((row) => {
      const a = document.createElement("a");
      a.className = "list-group-item list-group-item-action";
      a.href = "#";
      // MF only -> tag OP
      a.textContent = `[OP] ${row.label}`;

      a.addEventListener("click", (e) => {
        e.preventDefault();
        seleccionarSugerenciaResumen(row);
      });

      boxSugsOpResumen.appendChild(a);
    });

    boxSugsOpResumen.style.display = "block";
  }

  function normStrResumen(s) {
    return String(s || "")
      .trim()
      .replace(/\s+/g, " ")
      .toUpperCase();
  }

  function isInputSyncedResumen() {
    const a = normStrResumen(inpBuscarOpResumen?.value);
    const b = normStrResumen(opLabelSeleccionadaResumen);
    return !!a && a === b;
  }

  function doSearchSugerenciasResumen(term) {
    if (lastXHRSugerenciasResumen) {
      try {
        lastXHRSugerenciasResumen.abort();
      } catch (e) {}
    }

    const xhr = new XMLHttpRequest();
    lastXHRSugerenciasResumen = xhr;

    xhr.open(
      "GET",
      base_url +
        "Operaciones_maritimo_ferro_resumen/sugerencias?term=" +
        encodeURIComponent(term),
      true,
    );

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status === 200) {
        let res;
        try {
          res = JSON.parse(xhr.responseText);
        } catch {
          res = null;
        }
        if (!res || res.status !== "ok") {
          clearSugerenciasResumen();
          return;
        }
        renderSugerenciasResumen(res.data);
      } else {
        clearSugerenciasResumen();
      }
    };

    xhr.send();
  }

  if (inpBuscarOpResumen) {
    inpBuscarOpResumen.addEventListener("input", function () {
      const term = this.value.trim();
      clearTimeout(debounceTimerResumen);

      if (term.length === 0) {
        resetOperacionSeleccionResumen();
        opLabelSeleccionadaResumen = null;
        clearSugerenciasResumen();
        return;
      }

      if (operacionIdActivoResumen && !isInputSyncedResumen()) {
        resetOperacionSeleccionResumen();
      }

      if (term.length < 2) {
        clearSugerenciasResumen();
        return;
      }

      debounceTimerResumen = setTimeout(
        () => doSearchSugerenciasResumen(term),
        250,
      );
    });

    inpBuscarOpResumen.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        inpBuscarOpResumen.value = "";
        clearSugerenciasResumen();
        resetOperacionSeleccionResumen();
      }
    });
  }

  document.addEventListener("click", (e) => {
    if (!boxSugsOpResumen || !inpBuscarOpResumen) return;
    if (
      !boxSugsOpResumen.contains(e.target) &&
      e.target !== inpBuscarOpResumen
    ) {
      clearSugerenciasResumen();
    }
  });

  function seleccionarSugerenciaResumen(row) {
    if (!inpBuscarOpResumen) return;

    inpBuscarOpResumen.value = row.label;
    operacionIdActivoResumen = String(row.id);
    opLabelSeleccionadaResumen = row.label;

    cargarContenedoresResumen(operacionIdActivoResumen);
    clearSugerenciasResumen();
    setExportPdfEnabledResumen(true);

    // ✅ como ahora los costos viven en operación, los cargamos al seleccionar operación
    fetchCostosTotalesOperacion(operacionIdActivoResumen);
    fetchCostosDesglosadosOperacion(operacionIdActivoResumen);
  }

  // -------------------------
  // Contenedores por operación (solo marítimos)
  // -------------------------
  function cargarContenedoresResumen(operacionId) {
    if (!selectContenedorResumen) return;

    if (lastXHRContenedoresResumen) {
      try {
        lastXHRContenedoresResumen.abort();
      } catch (e) {}
    }
    setContenedoresLoadingResumen();

    const xhr = new XMLHttpRequest();
    lastXHRContenedoresResumen = xhr;

    xhr.open(
      "GET",
      base_url +
        "Operaciones_maritimo_ferro_resumen/listarContenedoresPorOperacion?id_operacion=" +
        encodeURIComponent(operacionId),
      true,
    );

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status === 200) {
        let res;
        try {
          res = JSON.parse(xhr.responseText);
        } catch {
          res = null;
        }

        if (!res || res.status !== "ok") {
          setContenedoresEmptyResumen("No se pudieron cargar contenedores");
          return;
        }

        renderContenedoresResumen(res);
      } else {
        setContenedoresEmptyResumen("Error al cargar contenedores");
      }
    };

    xhr.send();
  }

  function renderContenedoresResumen(res) {
    if (!selectContenedorResumen) return;

    selectContenedorResumen.innerHTML = "";

    const data = Array.isArray(res.contenedores)
      ? res.contenedores
      : Array.isArray(res.data)
        ? res.data
        : [];

    if (!data || data.length === 0) {
      setContenedoresEmptyResumen("Sin contenedores marítimos");
      return;
    }

    data.forEach((c) => {
      // ✅ Solo marítimos
      const option = document.createElement("option");
      option.value = c.id_contenedor ?? ""; // id_contenedor_maritimo
      option.textContent = `${c.tipo_contenedor || "Maritimo"} · ${c.numero_contenedor || "—"}`;

      option.dataset.tipo = "MARITIMO";
      option.dataset.baseId = c.id_contenedor ?? "";
      option.dataset.numero = c.numero_contenedor || "";

      selectContenedorResumen.appendChild(option);
    });

    // auto seleccionar el primero
    if (selectContenedorResumen.options.length > 0) {
      selectContenedorResumen.selectedIndex = 0;
      consultarDetallesContenedorResumen();
    }
  }

  // -------------------------
  // Detalle del contenedor (solo marítimo)
  // -------------------------
  function pintarDetalleContenedorResumen(data) {
    safeSetText(elPuerto, data.puerto || "—");
    safeSetText(elEta, data.eta || "—");
    safeSetText(elEtd, data.etd || "—");
    safeSetText(elBl, data.bl || "—");
    safeSetText(elComentarios, data.comentarios || "—");
    safeSetText(document.getElementById("isf"), data.isf == 1 ? "Sí" : "No");
    console.log("Detalle contenedor:", data);
    safeSetText(
      document.getElementById("brokerContenedor"),
      data.broker || "Sin Broker Registrado",
    );
    safeSetText(
      document.getElementById("transportistaContenedor"),
      data.transportista || "Sin Transportista Registrado",
    );
    safeSetText(
      document.getElementById("citaPuertoContenedor"),
      data.cita_puerto || "Sin Cita",
    );
  }

  function consultarDetallesContenedorResumen() {
    if (!selectContenedorResumen) return;

    const opt =
      selectContenedorResumen.options[selectContenedorResumen.selectedIndex];
    if (!opt || !operacionIdActivoResumen) return;

    const contenedorId = opt.dataset.baseId || opt.value;
    contenedorMaritimoIdActivo = contenedorId;

    limpiarDetalleUIResumen();
    setDetalleLoadingResumen();

    // 1) Detalle contenedor (MARITIMO)
    const urlDetalle =
      `${base_url}Operaciones_maritimo_ferro_resumen/detalles_contenedor` +
      `?operacion_id=${encodeURIComponent(operacionIdActivoResumen)}` +
      `&tipo=${encodeURIComponent("MARITIMO")}` +
      `&id_contenedor=${encodeURIComponent(contenedorId)}`;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", urlDetalle, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) return;

      let res;
      try {
        res = JSON.parse(xhr.responseText);
      } catch {
        res = null;
      }
      if (res && res.status === "ok" && res.data) {
        pintarDetalleContenedorResumen(res.data);
      }
    };
    xhr.send();

    // 2) Faltantes (contendor_id = id_contenedor_maritimo)
    const etiqueta = `Contenedor ${opt.dataset.numero || opt.textContent || "—"}`;
    cargarFaltantesResumen(operacionIdActivoResumen, contenedorId, etiqueta);

    // 3) Progreso eventos
    fetchEventosProgresoResumen(operacionIdActivoResumen, contenedorId);

    // 4) Eventos tabla + timeline
    cargarEventosResumen(operacionIdActivoResumen, contenedorId);
  }

  if (selectContenedorResumen) {
    selectContenedorResumen.addEventListener(
      "change",
      consultarDetallesContenedorResumen,
    );
  }

  if (btnRefResumen) {
    btnRefResumen.addEventListener("click", (e) => {
      e.preventDefault();
      consultarDetallesContenedorResumen();
    });
  }

  // -------------------------
  // Faltantes (solo marítimo)
  // -------------------------
  function cargarFaltantesResumen(operacionId, contenedorMarId, etiquetaTexto) {
    if (!operacionId || !contenedorMarId) {
      setDFHeaderResumen("Seleccione un contenedor…", 0);
      toggleDFResumen(false, false, false);
      if (dfListaResumen) dfListaResumen.innerHTML = "";
      return;
    }

    setDFHeaderResumen(etiquetaTexto || "—", 0);
    toggleDFResumen(true, false, false);

    if (lastXHRFaltantesResumen) {
      try {
        lastXHRFaltantesResumen.abort();
      } catch (e) {}
    }

    const xhr = new XMLHttpRequest();
    lastXHRFaltantesResumen = xhr;

    // El controller ya no necesita origen; tipo se ignora, pero lo mandamos fijo si lo quieres conservar
    const url =
      `${base_url}Operaciones_maritimo_ferro_resumen/faltantes` +
      `?operacion_id=${encodeURIComponent(operacionId)}` +
      `&contenedor_id=${encodeURIComponent(contenedorMarId)}` +
      `&tipo=${encodeURIComponent("M")}`;

    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status === 200) {
        let data;
        try {
          data = JSON.parse(xhr.responseText);
        } catch {
          data = null;
        }
        renderFaltantesResumen(Array.isArray(data) ? data : []);
      } else {
        renderFaltantesResumen([]);
      }
    };
    xhr.send();
  }
})();
