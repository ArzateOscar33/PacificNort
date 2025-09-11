// =======================================================
// Exportador Pro de Tablas (XLSX / PDF)
// Requiere: SheetJS (xlsx), jsPDF, autoTable
// Proyecto SMASH
// =======================================================
(function (global) {
  "use strict";

  // -------- Helpers básicos --------
  function $(ref) {
    if (!ref) return null;
    if (ref instanceof HTMLElement) return ref;
    if (typeof ref === "string") {
      // permitir "#id", ".clase", "tag", o "miTabla" (id sin #)
      const byId = document.getElementById(ref.replace(/^#/, ""));
      if (byId) return byId;
      return document.querySelector(ref);
    }
    return null;
  }
  const pad2 = n => String(n).padStart(2, "0");
  function timestamp() {
    const d = new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}_${pad2(d.getHours())}-${pad2(d.getMinutes())}`;
  }

  // Convierte una <table> en array de arrays (AOA), respetando solo visibles si se pide
  function tableToAOA(tableEl, { columnasOcultas = [], soloVisibles = true, maxFilas = null } = {}) {
    const ocultas = new Set((columnasOcultas || []).map(Number));

    const isVisible = (el) =>
      !soloVisibles || (el && el.offsetParent !== null && getComputedStyle(el).display !== "none");

    const rows = [];
    const secciones = ["thead", "tbody", "tfooter", "tfoot"]; // cubrir variantes

    secciones.forEach(sec => {
      tableEl.querySelectorAll(sec + " tr").forEach((tr) => {
        if (!isVisible(tr)) return;

        const r = [];
        const cells = Array.from(tr.children);
        cells.forEach((cell, idx) => {
          if (ocultas.has(idx)) return;

          // Normalizar valor si hay inputs/selects
          let txt = "";
          const input = cell.querySelector("input, select, textarea");
          if (input) {
            if (input.tagName === "SELECT") {
              const sel = input;
              txt = sel.options && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : (sel.value || "");
            } else if (input.type === "checkbox" || input.type === "radio") {
              txt = input.checked ? "✓" : "";
            } else {
              txt = (input.value || "").trim();
            }
          } else {
            // Quitar iconos / elementos no exportables
            const clone = cell.cloneNode(true);
            clone.querySelectorAll("button, .btn, i, svg, [data-no-export]").forEach(el => el.remove());
            txt = (clone.textContent || "").replace(/\s+/g, " ").trim();
          }
          r.push(txt);
        });

        if (r.length) rows.push(r);
      });
    });

    // Limitar filas si hace falta (solo sobre TBODY en general no se distingue aquí)
    if (Number.isInteger(maxFilas) && maxFilas > 0) {
      // Conserva cabecera si existe
      const header = rows.length ? rows[0] : null;
      const body = header ? rows.slice(1) : rows;
      const limited = body.slice(0, maxFilas);
      return header ? [header, ...limited] : limited;
    }
    return rows;
  }

  // -------- Exportar XLSX con SheetJS --------
  function exportXLSX(tableEl, {
    nombre = `tabla_${timestamp()}.xlsx`,
    columnasOcultas = [],
    soloVisibles = true,
    maxFilas = null,
    sheetName = "Datos"
  } = {}) {
    if (typeof XLSX === "undefined") {
      alert("Falta la librería XLSX (SheetJS).");
      return;
    }

    // Si la tabla proviene de DataTables u otro, puedes preferir leer del DOM directamente:
    const aoa = tableToAOA(tableEl, { columnasOcultas, soloVisibles, maxFilas });

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(aoa);
    XLSX.utils.book_append_sheet(wb, ws, sheetName);

    // Auto-width básico por contenido
    const colWidths = [];
    aoa.forEach(row => {
      row.forEach((cell, c) => {
        const v = cell == null ? "" : String(cell);
        colWidths[c] = Math.max(colWidths[c] || 10, Math.min(60, v.length + 2));
      });
    });
    ws['!cols'] = colWidths.map(w => ({ wch: w }));

    XLSX.writeFile(wb, nombre);
  }

  // -------- Exportar PDF con jsPDF + autoTable --------
  async function exportPDF(tableEl, {
    nombre = `tabla_${timestamp()}.pdf`,
    titulo = "Exportación de Tabla",
    orientacion = "landscape", // 'portrait' | 'landscape'
    formatoPagina = "letter",  // 'a4' | 'letter' | etc.
    columnasOcultas = [],
    soloVisibles = true,
    maxFilas = null
  } = {}) {
    if (typeof window.jspdf === "undefined" || !window.jspdf.jsPDF || !("autoTable" in (window.jspdf.jsPDF.API || {}))) {
      alert("Falta jsPDF o autoTable.");
      return;
    }

    const aoa = tableToAOA(tableEl, { columnasOcultas, soloVisibles, maxFilas });

    // Separar header (primera fila) y body
    const head = aoa.length ? [aoa[0]] : [];
    const body = aoa.length > 1 ? aoa.slice(1) : [];

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: orientacion, unit: "pt", format: formatoPagina });

    // Título
    doc.setFontSize(14);
    doc.text(String(titulo), 40, 32);

    // Tabla
    doc.autoTable({
    startY: 50,
    head,
    body,
    styles: { fontSize: 9, cellPadding: 4, lineColor: [200, 200, 200], lineWidth: 0.5 },
    headStyles: { 
        fillColor: [27, 34, 86], // azul corporativo (1b2256)
        textColor: [255, 255, 255],
        fontStyle: 'bold'
    },
    alternateRowStyles: { fillColor: [240, 240, 240] }, // filas intercaladas gris claro
    theme: "grid",
    margin: { left: 40, right: 40 },
    didDrawPage: (data) => {
        const pageSize = doc.internal.pageSize;
        const pageHeight = pageSize.height;
        doc.setFontSize(9);
        doc.text(`Generado: ${new Date().toLocaleString()}`, 40, pageHeight - 20);
        const str = `Página ${doc.internal.getNumberOfPages()}`;
        doc.text(str, pageSize.width - 40 - doc.getTextWidth(str), pageHeight - 20);
    }
    });


    doc.save(nombre);
  }

  // -------- API pública unificada --------
  function exportar({
    ref,
    formato = "xlsx",     // "xlsx" | "pdf"
    nombre,
    titulo,
    orientacion = "landscape",
    formatoPagina = "letter",
    columnasOcultas = [],
    soloVisibles = true,
    maxFilas = null,
    sheetName = "Datos",
  } = {}) {
    const tableEl = $(ref);
    if (!tableEl || tableEl.tagName !== "TABLE") {
      console.error("[ExportarTablas] Proporciona una <table> válida en 'ref'.");
      alert("No se encontró una tabla válida.");
      return;
    }

    if (formato === "xlsx") {
      exportXLSX(tableEl, { nombre, columnasOcultas, soloVisibles, maxFilas, sheetName });
    } else if (formato === "pdf") {
      exportPDF(tableEl, { nombre, titulo, orientacion, formatoPagina, columnasOcultas, soloVisibles, maxFilas });
    } else {
      console.error("[ExportarTablas] Formato no soportado:", formato);
      alert("Formato no soportado. Usa 'xlsx' o 'pdf'.");
    }
  }

  global.ExportarTablas = { exportar };

})(window);
