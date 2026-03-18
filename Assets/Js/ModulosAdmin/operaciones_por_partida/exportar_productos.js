(function () {
  "use strict";

  // =========================================================
  // CONFIG
  // =========================================================
  const SELECTORS = {
    modal: "#modalProductosFactura",
    btnExcel: "#operaciones_partida_productos_ExportarExcel",
    btnPDF: "#operaciones_partida_productos_ExportarPDF",

    table: "#pf_tablaExportar",
    tbody: "#pf_tbody",
    hiddenInvoiceId: "#pf_invoice_id",

    factura: "#pf_lblFactura",
    proveedor: "#pf_lblProveedor",
    xdock: "#pf_lblXdock",
    recibido: "#pf_lblRecibido",
    revision: "#pf_lblRevision",
    palletsInv: "#pf_lblPalletsRcv",

    totalCajas: "#pf_totalCajas",
    totalPiezas: "#pf_totalPiezas",
    totalPallets: "#pf_totalPalletsRcv",
  };

  const EXPORT_CONFIG = {
    excelFileName: "ProductosFactura.xlsx",
    pdfFileName: "ProductosFactura.pdf",
    pdfTitle: "Productos de Factura",
    pdfOrientation: "landscape",
    pdfPageFormat: "letter",
    sheetName: "Productos Factura",
    exportColumns: 10,
  };

  // =========================================================
  // HELPERS
  // =========================================================
  function $(selector, root = document) {
    return root.querySelector(selector);
  }

  function cleanText(value) {
    return String(value || "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function getText(selector) {
    const el = $(selector);
    return el ? cleanText(el.textContent || "") || "—" : "—";
  }

  function safeFilePart(value) {
    return cleanText(value)
      .replace(/[\\/:*?"<>|]+/g, "")
      .replace(/\s+/g, "_")
      .substring(0, 80);
  }

  function hasExportHelper() {
    return (
      typeof window.ExportarTablas !== "undefined" &&
      window.ExportarTablas &&
      typeof window.ExportarTablas.exportar === "function"
    );
  }

  function notify(type, message) {
    if (typeof window.Swal !== "undefined") {
      window.Swal.fire({
        icon: type,
        text: message,
        confirmButtonText: "Aceptar",
      });
      return;
    }
    alert(message);
  }

  function getValueFromCell(cell) {
    if (!cell) return "";

    const field = cell.querySelector("input, select, textarea");
    if (field) {
      if (field.tagName === "SELECT") {
        const selected = field.options[field.selectedIndex];
        return selected ? cleanText(selected.textContent || "") : "";
      }
      return cleanText(field.value || "");
    }

    return cleanText(cell.textContent || "");
  }

  function hasRows() {
    const tbody = $(SELECTORS.tbody);
    if (!tbody) return false;

    return Array.from(tbody.querySelectorAll("tr")).some((tr) => {
      return tr.querySelectorAll("td").length > 0;
    });
  }

  // =========================================================
  // DATA
  // =========================================================
  function collectHeaderData() {
    return {
      factura: getText(SELECTORS.factura),
      proveedor: getText(SELECTORS.proveedor),
      xdock: getText(SELECTORS.xdock),
      recibido: getText(SELECTORS.recibido),
      revision: getText(SELECTORS.revision),
      palletsInv: getText(SELECTORS.palletsInv),

      totalCajas: getText(SELECTORS.totalCajas),
      totalPiezas: getText(SELECTORS.totalPiezas),
      totalPallets: getText(SELECTORS.totalPallets),
    };
  }

  function getProductHeaders() {
    const table = $(SELECTORS.table);
    if (!table) return [];

    const ths = Array.from(table.querySelectorAll("thead th"));
    return ths.slice(0, 10).map((th) => cleanText(th.textContent || ""));
  }

  function collectProductRows() {
    const table = $(SELECTORS.table);
    if (!table) return [];

    const rows = Array.from(table.querySelectorAll("tbody tr"));

    return rows
      .map((tr) => {
        const tds = Array.from(tr.querySelectorAll("td"));
        if (!tds.length) return null;

        // Excluir Acciones
        return tds.slice(0, 10).map((td) => getValueFromCell(td));
      })
      .filter(Boolean);
  }

  // =========================================================
  // TABLE BUILDER
  // =========================================================
  function createCell(tag, text, colSpan = 1) {
    const el = document.createElement(tag);
    el.textContent = text;
    if (colSpan > 1) el.colSpan = colSpan;
    return el;
  }

  function appendEmptyRow(section, columns) {
    const tr = document.createElement("tr");
    for (let i = 0; i < columns; i++) {
      tr.appendChild(document.createElement("td"));
    }
    section.appendChild(tr);
  }

  function appendMetaRow(section, items, columns) {
    const tr = document.createElement("tr");

    items.forEach((item) => {
      tr.appendChild(createCell("td", item.label));
      tr.appendChild(createCell("td", item.value));
    });

    const used = items.length * 2;
    const remaining = columns - used;

    for (let i = 0; i < remaining; i++) {
      tr.appendChild(document.createElement("td"));
    }

    section.appendChild(tr);
  }

  function buildTemporaryExportTable() {
    const header = collectHeaderData();
    const productHeaders = getProductHeaders();
    const productRows = collectProductRows();
    const cols = EXPORT_CONFIG.exportColumns;

    const wrapper = document.createElement("div");
    wrapper.style.position = "absolute";
    wrapper.style.opacity = "0";
    wrapper.style.pointerEvents = "none";
    wrapper.style.zIndex = "-1";
    wrapper.style.top = "0";
    wrapper.style.left = "0";
    wrapper.style.width = "1800px";
    wrapper.setAttribute("aria-hidden", "true");

    const table = document.createElement("table");
    table.id = "tmp_export_productos_factura_" + Date.now();
    table.className = "table table-bordered";
    table.style.width = "100%";
    table.style.borderCollapse = "collapse";

    const thead = document.createElement("thead");
    const tbody = document.createElement("tbody");

    // =========================
    // TITULO
    // =========================
    {
      const tr = document.createElement("tr");
      const th = createCell("th", "PRODUCTOS DE FACTURA", cols);
      tr.appendChild(th);
      thead.appendChild(tr);
    }

    // =========================
    // DATOS GENERALES
    // =========================
    appendMetaRow(
      tbody,
      [
        { label: "Factura", value: header.factura },
        { label: "Proveedor", value: header.proveedor },
        { label: "Bodega", value: header.xdock },
      ],
      cols,
    );

    appendMetaRow(
      tbody,
      [
        { label: "Recibido", value: header.recibido },
        { label: "Revisión", value: header.revision },
        { label: "Pallets INV (Factura)", value: header.palletsInv },
      ],
      cols,
    );

    appendEmptyRow(tbody, cols);

    // =========================
    // TOTALES
    // =========================
    appendMetaRow(
      tbody,
      [
        { label: "Total cajas", value: header.totalCajas },
        { label: "Total piezas", value: header.totalPiezas },
        { label: "Total pallets RCV", value: header.totalPallets },
      ],
      cols,
    );

    appendEmptyRow(tbody, cols);

    // =========================
    // SUBTITULO DETALLE
    // =========================
    {
      const tr = document.createElement("tr");
      const td = createCell("td", "DETALLE DE PRODUCTOS", cols);
      tr.appendChild(td);
      tbody.appendChild(tr);
    }

    // =========================
    // ENCABEZADOS PRODUCTOS
    // =========================
    {
      const tr = document.createElement("tr");
      productHeaders.forEach((text) => {
        tr.appendChild(createCell("td", text));
      });
      tbody.appendChild(tr);
    }

    // =========================
    // FILAS PRODUCTOS
    // =========================
    productRows.forEach((row) => {
      const tr = document.createElement("tr");
      row.forEach((value) => {
        tr.appendChild(createCell("td", value));
      });
      tbody.appendChild(tr);
    });

    table.appendChild(thead);
    table.appendChild(tbody);
    wrapper.appendChild(table);
    document.body.appendChild(wrapper);

    return {
      wrapper,
      table,
      header,
    };
  }

  function removeTemporaryExportTable(wrapper) {
    if (wrapper && wrapper.parentNode) {
      wrapper.parentNode.removeChild(wrapper);
    }
  }

  // =========================================================
  // VALIDACIONES
  // =========================================================
  function validateBeforeExport() {
    if (!$(SELECTORS.modal) || !$(SELECTORS.table) || !$(SELECTORS.tbody)) {
      notify("error", "No se encontró la estructura del modal de productos.");
      return false;
    }

    if (!hasExportHelper()) {
      notify("error", "No se encontró el helper ExportarTablas.exportar.");
      return false;
    }

    const invoiceId = cleanText($(SELECTORS.hiddenInvoiceId)?.value || "");
    const factura = getText(SELECTORS.factura);

    if (!invoiceId && (!factura || factura === "—")) {
      notify("warning", "Primero abre una factura válida.");
      return false;
    }

    if (!hasRows()) {
      notify("warning", "No hay productos para exportar.");
      return false;
    }

    return true;
  }

  // =========================================================
  // EXPORT
  // =========================================================
  function buildFileName(ext) {
    const factura = safeFilePart(getText(SELECTORS.factura) || "SIN_FACTURA");
    const proveedor = safeFilePart(
      getText(SELECTORS.proveedor) || "SIN_PROVEEDOR",
    );
    return `ProductosFactura_${factura}_${proveedor}.${ext}`;
  }

  function exportExcel() {
    if (!validateBeforeExport()) return;

    const temp = buildTemporaryExportTable();

    try {
      window.ExportarTablas.exportar({
        ref: "#" + temp.table.id,
        formato: "xlsx",
        nombre: buildFileName("xlsx") || EXPORT_CONFIG.excelFileName,
        sheetName: EXPORT_CONFIG.sheetName,
        soloVisibles: false,
      });
    } catch (err) {
      console.error("Error al exportar Excel:", err);
      notify("error", "Ocurrió un error al exportar a Excel.");
    } finally {
      removeTemporaryExportTable(temp.wrapper);
    }
  }

  function exportPDF() {
    if (!validateBeforeExport()) return;

    const temp = buildTemporaryExportTable();

    try {
      window.ExportarTablas.exportar({
        ref: "#" + temp.table.id,
        formato: "pdf",
        nombre: buildFileName("pdf") || EXPORT_CONFIG.pdfFileName,
        titulo: `${EXPORT_CONFIG.pdfTitle} - Factura ${temp.header.factura}`,
        orientacion: EXPORT_CONFIG.pdfOrientation,
        formatoPagina: EXPORT_CONFIG.pdfPageFormat,
        soloVisibles: false,
      });
    } catch (err) {
      console.error("Error al exportar PDF:", err);
      notify("error", "Ocurrió un error al exportar a PDF.");
    } finally {
      removeTemporaryExportTable(temp.wrapper);
    }
  }

  // =========================================================
  // INIT
  // =========================================================
  function bindEvents() {
    const btnExcel = $(SELECTORS.btnExcel);
    const btnPDF = $(SELECTORS.btnPDF);

    if (btnExcel) {
      btnExcel.addEventListener("click", exportExcel);
    }

    if (btnPDF) {
      btnPDF.addEventListener("click", exportPDF);
    }
  }

  function init() {
    bindEvents();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
