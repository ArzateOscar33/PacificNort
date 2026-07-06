(function evOpPartidaCatalogo() {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const URL_LISTAR = base_url + "Operaciones_por_partida_eventos/listar";
  const URL_COLUMNAS =
    base_url + "Operaciones_por_partida_eventos/eventos_ferro_columnas";
  const URL_OBTENER_POR_CLAVE =
    base_url + "Operaciones_por_partida_eventos/obtener_por_clave";
  const URL_REGISTRAR = base_url + "Operaciones_por_partida_eventos/registrar";
  const URL_ACTUALIZAR =
    base_url + "Operaciones_por_partida_eventos/actualizar";
  const URL_ELIMINAR = base_url + "Operaciones_por_partida_eventos/eliminar";
  const URL_TIPOS_EVENTO =
    base_url + "Operaciones_por_partida_eventos/tipos_evento";

  // =========================
  // REFS TABLA
  // =========================
  const theadRow = document.getElementById("theadEventosOpPartida");
  const tbody = document.getElementById("tbodyEventosOpPartida");
  const pagBox = document.getElementById("evOpPartidaPaginacion");
  const metaBox = document.getElementById("evOpPartidaMetaResumen");
  const perPageSel = document.getElementById("evOpPartidaPerPage");

  // =========================
  // REFS FILTROS
  // =========================
  const filtroOpId = document.getElementById("eventosOpPartidaFiltroOpId");
  const filtroOpNom = document.getElementById("eventosOpPartidaFiltroOpNombre");

  const filtroFactura = document.getElementById(
    "eventosOpPartidaFiltroFactura",
  );
  const filtroFerro = document.getElementById("eventosOpPartidaFiltroFerro");
  const filtroTransportista = document.getElementById(
    "eventosOpPartidaFiltroTransportista",
  );
  const filtroDestino = document.getElementById(
    "eventosOpPartidaFiltroDestino",
  );

  // =========================
  // MODAL CELDA
  // =========================
  const modalCellEl = document.getElementById("modalEvtCellOpPartida");
  const formCell = document.getElementById("formEvtCellOpPartida");

  const cellOpId = document.getElementById("cellOpIdOpPartida");
  const cellCfoId = document.getElementById("cellCfoIdOpPartida");
  const cellEvtId = document.getElementById("cellEvtIdOpPartida");
  const cellIdEvento = document.getElementById("cellIdEventoOpPartida");

  const cellOpTxt = document.getElementById("cellOpTxtOpPartida");
  const cellCtnTxt = document.getElementById("cellCtnTxtOpPartida");
  const cellEvtTxt = document.getElementById("cellEvtTxtOpPartida");
  const cellFecha = document.getElementById("cellFechaOpPartida");
  const cellComentario = document.getElementById("cellComentarioOpPartida");
  const cellTitle = document.getElementById("modalEvtCellTitleOpPartida");
  const btnCellDelete = document.getElementById("btnCellDeleteOpPartida");

  if (typeof bootstrap === "undefined") {
    console.error(
      "Bootstrap JS no está cargado. Verifica bootstrap.bundle.min.js",
    );
  }

  const modalCell =
    modalCellEl && typeof bootstrap !== "undefined"
      ? new bootstrap.Modal(modalCellEl)
      : null;

  // =========================
  // ESTADO
  // =========================
  let page = 1;
  let perPage = perPageSel ? parseInt(perPageSel.value || "10", 10) : 10;
  let totalRows = 0;

  let columnasEventos = []; // [{id,nombre,key}]
  let lastRowsRaw = [];
  let lastRowsPivot = [];

  let debounceTimer = null;
  let loading = false;

  // =========================
  // HELPERS
  // =========================
  function xhGet(url, onOk, onErr) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const json = JSON.parse(xhr.responseText || "null");
          onOk(json);
        } catch (e) {
          console.error("JSON inválido GET:", e, xhr.responseText);
          if (typeof onErr === "function") onErr(e);
        }
      } else {
        console.error("GET error:", xhr.status, xhr.responseText);
        if (typeof onErr === "function") onErr(xhr);
      }
    };
    xhr.send();
  }

  function xhPost(url, formData, onOk, onErr) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const json = JSON.parse(xhr.responseText || "null");
          onOk(json);
        } catch (e) {
          console.error("JSON inválido POST:", e, xhr.responseText);
          if (typeof onErr === "function") onErr(e);
        }
      } else {
        console.error("POST error:", xhr.status, xhr.responseText);
        if (typeof onErr === "function") onErr(xhr);
      }
    };
    xhr.send(formData);
  }

  function esc(v) {
    return String(v == null ? "" : v)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function debounce(fn, ms) {
    return function () {
      const ctx = this;
      const args = arguments;
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        fn.apply(ctx, args);
      }, ms);
    };
  }

  function fmtDateInput(val) {
    if (!val) return "";
    const s = String(val).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
    return "";
  }

  function notify(icon, title, text) {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: icon || "info",
        title: title || "",
        text: text || "",
        timer: icon === "success" ? 1600 : undefined,
        showConfirmButton: icon !== "success",
      });
      return;
    }
    alert((title ? title + "\n" : "") + (text || ""));
  }

  function buildQuery() {
    const qs = new URLSearchParams();
    qs.set("page", String(page));
    qs.set("per_page", String(perPage));

    const opId = filtroOpId ? String(filtroOpId.value || "").trim() : "";
    const factura = filtroFactura
      ? String(filtroFactura.value || "").trim()
      : "";
    const ferro = filtroFerro ? String(filtroFerro.value || "").trim() : "";
    const transportistaId = filtroTransportista
      ? String(filtroTransportista.value || "").trim()
      : "";
    const destinoId = filtroDestino
      ? String(filtroDestino.value || "").trim()
      : "";

    if (opId) qs.set("op_id", opId);
    if (factura) qs.set("factura", factura);
    if (ferro) qs.set("ferro", ferro);
    if (transportistaId) qs.set("transportista_id", transportistaId);
    if (destinoId) qs.set("destino_id", destinoId);

    return qs.toString();
  }

  function renderMeta(total, currentPage, currentPerPage) {
    if (!metaBox) return;

    if (!total) {
      metaBox.textContent = "Mostrando 0–0 de 0";
      return;
    }

    const from = (currentPage - 1) * currentPerPage + 1;
    const to = Math.min(total, currentPage * currentPerPage);
    metaBox.textContent = `Mostrando ${from}–${to} de ${total}`;
  }

  function renderPagination(total, currentPage, currentPerPage) {
    if (!pagBox) return;

    pagBox.innerHTML = "";

    const totalPages = Math.max(
      1,
      Math.ceil((Number(total) || 0) / currentPerPage),
    );
    if (totalPages <= 1) return;

    function makeLi(label, targetPage, disabled, active) {
      const li = document.createElement("li");
      li.className =
        "page-item" + (disabled ? " disabled" : "") + (active ? " active" : "");

      const a = document.createElement("a");
      a.className = "page-link";
      a.href = "#";
      a.textContent = label;

      a.addEventListener("click", function (e) {
        e.preventDefault();
        if (disabled || active) return;
        page = targetPage;
        cargarListado();
      });

      li.appendChild(a);
      return li;
    }

    pagBox.appendChild(
      makeLi("«", Math.max(1, currentPage - 1), currentPage <= 1, false),
    );

    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, currentPage + 2);

    if (currentPage <= 3) end = Math.min(totalPages, 5);
    if (currentPage >= totalPages - 2) start = Math.max(1, totalPages - 4);

    for (let i = start; i <= end; i++) {
      pagBox.appendChild(makeLi(String(i), i, false, i === currentPage));
    }

    pagBox.appendChild(
      makeLi(
        "»",
        Math.min(totalPages, currentPage + 1),
        currentPage >= totalPages,
        false,
      ),
    );
  }

  function renderHead() {
    if (!theadRow) return;

    let html = "";
    html += `<th class="text-center">Operación</th>`;
    html += `<th class="text-center">Factura</th>`;
    html += `<th class="text-center">Cliente</th>`;
    html += `<th class="text-center">Destino</th>`;
    html += `<th class="text-center">Transportista</th>`;
    html += `<th class="text-center">Caja / Ferro</th>`;

    columnasEventos.forEach(function (col) {
      html += `<th class="text-center">${esc(col.nombre)}</th>`;
    });

    theadRow.innerHTML = html;
  }

  function agruparPivot(rows) {
    const map = new Map();

    (Array.isArray(rows) ? rows : []).forEach(function (r) {
      const opId = Number(r.operacion_ferro_id || 0);
      const ferroId = Number(r.contenedor_fisico_id || 0);
      const key = opId + "_" + ferroId;

      if (!map.has(key)) {
        map.set(key, {
          key: key,
          operacion_ferro_id: opId,
          contenedor_fisico_id: ferroId,
          operacion: r.operacion || r.operacion_maritima || "ENV-" + opId,
          ferro: r.ferro || "",
          cliente: r.cliente || r.nombre_cliente || "",
          destino: r.destino || "",
          transportista: r.transportista || "",
          factura:
            r.factura ||
            r.facturas ||
            r.numero_factura ||
            r.factura_folio ||
            "",
          ubicacion_actual: r.ubicacion_actual || "",
          eventos: {},
        });
      }

      const row = map.get(key);

      if (!row.factura) {
        row.factura =
          r.factura || r.facturas || r.numero_factura || r.factura_folio || "";
      }

      if (!row.cliente) {
        row.cliente = r.cliente || r.nombre_cliente || "";
      }

      if (!row.destino) {
        row.destino = r.destino || "";
      }

      if (!row.transportista) {
        row.transportista = r.transportista || "";
      }

      if (!row.ferro) {
        row.ferro = r.ferro || "";
      }

      if (r.tipo_evento_id) {
        row.eventos[String(r.tipo_evento_id)] = {
          id_evento: r.id_evento || null,
          tipo_evento_id: Number(r.tipo_evento_id || 0),
          evento: r.evento || "",
          fecha: r.fecha || "",
          comentario: r.comentario || "",
        };
      }
    });

    return Array.from(map.values());
  }

  function buildCellText(evtObj) {
    if (!evtObj || !evtObj.fecha) {
      return `<span class="text-muted">-</span>`;
    }

    return `<span>${esc(evtObj.fecha)}</span>`;
  }

  function renderBody(rowsPivot) {
    if (!tbody) return;

    if (!Array.isArray(rowsPivot) || !rowsPivot.length) {
      tbody.innerHTML = `
      <tr>
        <td colspan="${6 + columnasEventos.length}" class="text-center text-muted py-4">
          No hay registros para mostrar
        </td>
      </tr>
    `;
      return;
    }

    let html = "";

    rowsPivot.forEach(function (r) {
      html += `<tr>`;

      // OPERACIÓN
      html += `
        <td class="text-center align-middle">
          <div class="fw-semibold">${esc(r.operacion || "-")}</div>
        </td>
      `;

      // FACTURA
      html += `
        <td class="text-center align-middle">
          <span>${esc(r.factura || "-")}</span>
        </td>
      `;

      // CLIENTE
      html += `
        <td class="text-center align-middle">
          <span>${esc(r.cliente || "-")}</span>
        </td>
      `;

      // DESTINO
      html += `
        <td class="text-center align-middle">
          <span>${esc(r.destino || "-")}</span>
        </td>
      `;

      // TRANSPORTISTA
      html += `
        <td class="text-center align-middle">
          <span>${esc(r.transportista || "-")}</span>
        </td>
      `;

      // CAJA / FERRO
      html += `
        <td class="text-center align-middle">
          <div class="fw-semibold">${esc(r.ferro || "-")}</div>
        </td>
      `;

      // EVENTOS DINÁMICOS
      columnasEventos.forEach(function (col) {
        const evt = r.eventos[String(col.id)] || null;
        const comentario = evt && evt.comentario ? esc(evt.comentario) : "";
        const title = comentario ? ` title="${comentario}"` : "";

        html += `
          <td class="text-center align-middle">
            <button
              type="button"
              class="btn-cell-evento-op-partida evop-cell-btn"
              data-op-id="${esc(r.operacion_ferro_id)}"
              data-ferro-id="${esc(r.contenedor_fisico_id)}"
              data-evt-id="${esc(col.id)}"
              data-evt-nombre="${esc(col.nombre)}"
              data-op-txt="${esc(r.operacion || "")}"
              data-ferro-txt="${esc(r.ferro || "")}"
              data-id-evento="${esc(evt && evt.id_evento ? evt.id_evento : "")}"
              data-fecha="${esc(evt && evt.fecha ? evt.fecha : "")}"
              data-comentario="${esc(evt && evt.comentario ? evt.comentario : "")}"
              ${title}
              style="
                background: transparent;
                border: 0;
                padding: 0;
                min-width: 100%;
                color: inherit;
                box-shadow: none;
              "
            >
              ${buildCellText(evt)}
            </button>
          </td>
        `;
      });

      html += `</tr>`;
    });

    tbody.innerHTML = html;
  }

  function attachBodyEvents() {
    if (!tbody) return;

    tbody
      .querySelectorAll(".btn-cell-evento-op-partida")
      .forEach(function (btn) {
        btn.addEventListener("click", function () {
          openCellModalFromButton(this);
        });
      });
  }

  function openCellModalFromButton(btn) {
    if (!modalCell) return;

    const opId = String(btn.getAttribute("data-op-id") || "");
    const ferroId = String(btn.getAttribute("data-ferro-id") || "");
    const evtId = String(btn.getAttribute("data-evt-id") || "");
    const evtNombre = String(btn.getAttribute("data-evt-nombre") || "");
    const opTxt = String(btn.getAttribute("data-op-txt") || "");
    const ferroTxt = String(btn.getAttribute("data-ferro-txt") || "");
    const idEvento = String(btn.getAttribute("data-id-evento") || "");
    const fecha = String(btn.getAttribute("data-fecha") || "");
    const comentario = String(btn.getAttribute("data-comentario") || "");

    cellOpId.value = opId;
    cellCfoId.value = ferroId;
    cellEvtId.value = evtId;
    cellIdEvento.value = idEvento;

    cellOpTxt.value = opTxt;
    cellCtnTxt.value = ferroTxt;
    cellEvtTxt.value = evtNombre;
    cellFecha.value = fmtDateInput(fecha);
    cellComentario.value = comentario || "";

    if (cellTitle) {
      cellTitle.textContent = idEvento ? "Editar evento" : "Registrar evento";
    }

    if (btnCellDelete) {
      btnCellDelete.classList.toggle("d-none", !idEvento);
    }

    modalCell.show();
  }

  function cargarColumnas(cb) {
    xhGet(
      URL_COLUMNAS,
      function (json) {
        columnasEventos = Array.isArray(json && json.columns)
          ? json.columns
          : [];
        renderHead();
        if (typeof cb === "function") cb();
      },
      function () {
        columnasEventos = [];
        renderHead();
        if (tbody) {
          tbody.innerHTML = `
            <tr>
              <td colspan="6" class="text-center text-danger py-4">
                No fue posible cargar las columnas de eventos.
              </td>
            </tr>
          `;
        }
      },
    );
  }

  function cargarListado() {
    if (loading) return;
    loading = true;

    if (tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="${6 + Math.max(1, columnasEventos.length)}" class="text-center py-4 text-muted">
            Cargando...
          </td>
        </tr>
      `;
    }

    const url = URL_LISTAR + "?" + buildQuery();

    xhGet(
      url,
      function (json) {
        loading = false;

        lastRowsRaw = Array.isArray(json && json.data) ? json.data : [];
        totalRows = Number(json && json.total ? json.total : 0);

        lastRowsPivot = agruparPivot(lastRowsRaw);

        renderBody(lastRowsPivot);
        attachBodyEvents();
        renderMeta(totalRows, page, perPage);
        renderPagination(totalRows, page, perPage);

        if (typeof feather !== "undefined") feather.replace();
      },
      function () {
        loading = false;
        if (tbody) {
          tbody.innerHTML = `
            <tr>
              <td colspan="${6 + Math.max(1, columnasEventos.length)}" class="text-center text-danger py-4">
                Error al cargar el listado.
              </td>
            </tr>
          `;
        }
        renderMeta(0, page, perPage);
        renderPagination(0, page, perPage);
      },
    );
  }

  function guardarEventoCelda(e) {
    e.preventDefault();

    const opId = parseInt(cellOpId.value || "0", 10);
    const ferroId = parseInt(cellCfoId.value || "0", 10);
    const evtId = parseInt(cellEvtId.value || "0", 10);
    const idEvento = parseInt(cellIdEvento.value || "0", 10);
    const fecha = String(cellFecha.value || "").trim();
    const comentario = String(cellComentario.value || "").trim();

    if (opId <= 0) {
      notify("warning", "Dato faltante", "No se encontró la operación.");
      return;
    }
    if (ferroId <= 0) {
      notify("warning", "Dato faltante", "No se encontró el ferro/caja.");
      return;
    }
    if (evtId <= 0) {
      notify("warning", "Dato faltante", "No se encontró el tipo de evento.");
      return;
    }
    if (!fecha) {
      notify(
        "warning",
        "Fecha requerida",
        "Debes capturar la fecha del evento.",
      );
      return;
    }

    const fd = new FormData();
    fd.append("operacion_ferro_id", String(opId));
    fd.append("contenedor_fisico_id", String(ferroId));
    fd.append("tipo_evento_id", String(evtId));
    fd.append("fecha", fecha);
    fd.append("comentario", comentario);

    let url = URL_REGISTRAR;

    if (idEvento > 0) {
      fd.append("id_evento", String(idEvento));
      url = URL_ACTUALIZAR;
    }

    xhPost(
      url,
      fd,
      function (json) {
        const status = String((json && json.status) || "");
        const msg = String((json && json.msg) || "Proceso completado.");

        if (status === "success") {
          modalCell.hide();
          notify("success", "Correcto", msg);
          cargarListado();
        } else {
          notify(status === "warning" ? "warning" : "error", "Atención", msg);
        }
      },
      function () {
        notify("error", "Error", "No fue posible guardar el evento.");
      },
    );
  }

  function eliminarEventoCelda() {
    const idEvento = parseInt(cellIdEvento.value || "0", 10);
    if (idEvento <= 0) return;

    const proceed = function () {
      const fd = new FormData();
      fd.append("id_evento", String(idEvento));

      xhPost(
        URL_ELIMINAR,
        fd,
        function (json) {
          const status = String((json && json.status) || "");
          const msg = String((json && json.msg) || "");

          if (status === "success") {
            modalCell.hide();
            notify("success", "Eliminado", msg || "Evento eliminado.");
            cargarListado();
          } else {
            notify("error", "Error", msg || "No fue posible eliminar.");
          }
        },
        function () {
          notify("error", "Error", "No fue posible eliminar el evento.");
        },
      );
    };

    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: "warning",
        title: "¿Eliminar evento?",
        text: "Esta acción dará eliminara al evento seleccionado.",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
      }).then(function (res) {
        if (res.isConfirmed) proceed();
      });
      return;
    }

    if (window.confirm("¿Eliminar evento?")) {
      proceed();
    }
  }

  function exportarTablaExcelHTML() {
    const table = document.getElementById("tablaEventosOpPartida");
    if (!table) {
      notify("warning", "Sin datos", "No se encontró la tabla a exportar.");
      return;
    }

    const html =
      "<html><head><meta charset='UTF-8'></head><body>" +
      table.outerHTML +
      "</body></html>";

    const blob = new Blob([html], {
      type: "application/vnd.ms-excel",
    });

    const a = document.createElement("a");
    const now = new Date();
    const name =
      "eventos_operaciones_partida_" +
      now.getFullYear() +
      String(now.getMonth() + 1).padStart(2, "0") +
      String(now.getDate()).padStart(2, "0") +
      "_" +
      String(now.getHours()).padStart(2, "0") +
      String(now.getMinutes()).padStart(2, "0") +
      String(now.getSeconds()).padStart(2, "0") +
      ".xls";

    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(function () {
      URL.revokeObjectURL(a.href);
    }, 1000);
  }

  function attachFilters() {
    const reload = debounce(function () {
      page = 1;
      cargarListado();
    }, 350);

    if (filtroFactura) filtroFactura.addEventListener("input", reload);
    if (filtroFerro) filtroFerro.addEventListener("input", reload);
    if (filtroTransportista)
      filtroTransportista.addEventListener("change", reload);
    if (filtroDestino) filtroDestino.addEventListener("change", reload);

    if (perPageSel) {
      perPageSel.addEventListener("change", function () {
        perPage = parseInt(this.value || "10", 10);
        page = 1;
        cargarListado();
      });
    }

    const btnExcel = document.getElementById(
      "btnExportarExcelEventosLogisticosOpPartida",
    );
    if (btnExcel) {
      btnExcel.addEventListener("click", function (e) {
        e.preventDefault();
        exportarTablaExcelHTML();
      });
    }
  }

  function attachModalEvents() {
    if (formCell) {
      formCell.addEventListener("submit", guardarEventoCelda);
    }

    if (btnCellDelete) {
      btnCellDelete.addEventListener("click", eliminarEventoCelda);
    }

    if (modalCellEl) {
      modalCellEl.addEventListener("hidden.bs.modal", function () {
        if (formCell) formCell.reset();
        if (cellOpId) cellOpId.value = "";
        if (cellCfoId) cellCfoId.value = "";
        if (cellEvtId) cellEvtId.value = "";
        if (cellIdEvento) cellIdEvento.value = "";

        if (btnCellDelete) btnCellDelete.classList.add("d-none");
        if (cellTitle) cellTitle.textContent = "Evento";
      });
    }
  }

  function init() {
    attachFilters();
    attachModalEvents();

    cargarColumnas(function () {
      cargarListado();
    });
  }

  document.addEventListener("DOMContentLoaded", init);
})();
