/* ============================================================
   EXPORTAR "Costos por Cliente" a Excel (con merges + estilo)
   Requiere: https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js
   Tabla: #costosCliente_table
   Botón: #btnExportarExcelCostosCliente
   ============================================================ */
(function () {
  "use strict";

  const btn = document.getElementById("btnExportarExcelCostosCliente");
  const table = document.getElementById("costosCliente_table");

  if (!btn || !table) return;

  // ========= Utils =========
  const getTextClean = (el) => {
    if (!el) return "";

    // Si dentro hay badge, toma el texto del badge
    const badge = el.querySelector?.(".badge");
    if (badge) return String(badge.textContent || "").trim();

    // Si es icono feather o similar, ignóralo y toma el texto visible
    let t = String(el.textContent || "").trim();

    // Limpieza de espacios repetidos
    t = t.replace(/\s+/g, " ").trim();
    return t;
  };

  // Convierte "$1,234.50" -> número, si no se puede, deja texto
  const parseMoney = (txt) => {
    const s = String(txt || "").trim();
    if (!s) return null;
    // quita $ y comas
    const num = s.replace(/\$/g, "").replace(/,/g, "");
    const n = Number(num);
    return Number.isFinite(n) ? n : null;
  };

  const normSiNo = (txt) => {
    const t = String(txt || "")
      .trim()
      .toLowerCase();
    if (t === "si" || t === "sí" || t === "1" || t === "true") return "Sí";
    if (t === "no" || t === "0" || t === "false") return "No";
    return String(txt || "").trim();
  };

  // ========= Construcción AOA + merges desde tabla HTML =========
  function tableToAOAWithMerges(htmlTable) {
    const rows = Array.from(htmlTable.rows || []);
    const aoa = [];
    const merges = [];

    // Grid para manejar spans
    const grid = []; // grid[r][c] = true ocupado

    const ensureRow = (r) => {
      if (!grid[r]) grid[r] = [];
      if (!aoa[r]) aoa[r] = [];
    };

    for (let r = 0; r < rows.length; r++) {
      ensureRow(r);

      const cells = Array.from(rows[r].cells || []);
      let cIndex = 0;

      for (const cell of cells) {
        // Encuentra la siguiente columna libre (por rowspans previos)
        while (grid[r][cIndex]) cIndex++;

        const rs = Math.max(1, Number(cell.rowSpan || 1));
        const cs = Math.max(1, Number(cell.colSpan || 1));

        // Texto limpio
        let val = getTextClean(cell);

        // Normalizaciones específicas por columna (según tu thead)
        // Cols:
        // 0 Operación, 1 Contenedor, 2 Transportista, 3 Broker, 4 Estatus,
        // 5 Cita Puerto, 6 ISF, 7 Categoria, 8 Concepto, 9 Monto, 10 Pagado
        const col = cIndex;

        if (col === 6) val = normSiNo(val); // ISF
        if (col === 10) val = normSiNo(val); // Pagado

        // Monto: convertir a número para que Excel lo trate como numérico
        // Ojo: solo si parece dinero
        if (col === 9) {
          const n = parseMoney(val);
          if (n !== null) val = n;
        }

        // Coloca valor (solo en la celda superior izquierda del merge)
        aoa[r][cIndex] = val;

        // Marca ocupadas las celdas del span
        for (let rr = r; rr < r + rs; rr++) {
          ensureRow(rr);
          for (let cc = cIndex; cc < cIndex + cs; cc++) {
            grid[rr][cc] = true;
            // Rellena con vacío para que Excel tenga la "forma"
            if (aoa[rr][cc] === undefined) aoa[rr][cc] = "";
          }
        }

        // Si hay merge, lo guardamos
        if (rs > 1 || cs > 1) {
          merges.push({
            s: { r: r, c: cIndex },
            e: { r: r + rs - 1, c: cIndex + cs - 1 },
          });
        }

        cIndex += cs;
      }
    }

    // Normaliza filas al mismo ancho
    const maxCols = aoa.reduce((m, row) => Math.max(m, row.length), 0);
    aoa.forEach((row) => {
      for (let i = 0; i < maxCols; i++) {
        if (row[i] === undefined) row[i] = "";
      }
    });

    return { aoa, merges };
  }

  // ========= Estilos (xlsx-js-style) =========
  const BORDER_THIN = {
    top: { style: "thin", color: { rgb: "D9D9D9" } },
    bottom: { style: "thin", color: { rgb: "D9D9D9" } },
    left: { style: "thin", color: { rgb: "D9D9D9" } },
    right: { style: "thin", color: { rgb: "D9D9D9" } },
  };

  const STYLE_HEADER = {
    font: { bold: true, color: { rgb: "000000" } },
    fill: { patternType: "solid", fgColor: { rgb: "F2F2F2" } },
    alignment: { vertical: "center", horizontal: "center", wrapText: true },
    border: BORDER_THIN,
  };

  const STYLE_CELL = {
    alignment: { vertical: "center", horizontal: "left", wrapText: true },
    border: BORDER_THIN,
  };

  const STYLE_CENTER = {
    alignment: { vertical: "center", horizontal: "center", wrapText: true },
    border: BORDER_THIN,
  };

  const STYLE_RIGHT = {
    alignment: { vertical: "center", horizontal: "right", wrapText: true },
    border: BORDER_THIN,
  };

  const STYLE_MONEY = {
    alignment: { vertical: "center", horizontal: "right", wrapText: true },
    border: BORDER_THIN,
    numFmt: '"$"#,##0.00',
  };

  function applySheetStyles(ws, range) {
    // range: {s:{r,c}, e:{r,c}}
    for (let R = range.s.r; R <= range.e.r; ++R) {
      for (let C = range.s.c; C <= range.e.c; ++C) {
        const addr = XLSX.utils.encode_cell({ r: R, c: C });
        const cell = ws[addr];
        if (!cell) continue;

        // Header row (tu tabla usa thead, normalmente es la fila 0)
        if (R === 0) {
          cell.s = STYLE_HEADER;
          continue;
        }

        // Columnas especiales
        // Monto (col 9)
        if (C === 9) {
          // Si es número, money; si no, right normal
          if (typeof cell.v === "number") cell.s = STYLE_MONEY;
          else cell.s = STYLE_RIGHT;
          continue;
        }

        // ISF (6) y Pagado (10): centrado
        if (C === 6 || C === 10) {
          cell.s = STYLE_CENTER;
          continue;
        }

        // Cita Puerto (5): centrado
        if (C === 5) {
          cell.s = STYLE_CENTER;
          continue;
        }

        // Default
        cell.s = STYLE_CELL;
      }
    }
  }

  function setColumnWidths(ws) {
    // Ajusta anchos según tu tabla (aprox)
    ws["!cols"] = [
      { wch: 14 }, // Operación
      { wch: 18 }, // Contenedor
      { wch: 20 }, // Transportista
      { wch: 20 }, // Broker
      { wch: 16 }, // Estatus
      { wch: 14 }, // Cita Puerto
      { wch: 8 }, // ISF
      { wch: 22 }, // Categoria
      { wch: 28 }, // Concepto
      { wch: 14 }, // Monto
      { wch: 10 }, // Pagado
    ];
  }

  function getFileName() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    const stamp =
      d.getFullYear() +
      "-" +
      pad(d.getMonth() + 1) +
      "-" +
      pad(d.getDate()) +
      "_" +
      pad(d.getHours()) +
      pad(d.getMinutes());
    return `CostosCliente_${stamp}.xlsx`;
  }

  // ========= Export =========
  function exportExcel() {
    if (typeof XLSX === "undefined") {
      alert(
        "Falta la librería XLSX. Agrega xlsx-js-style antes de este script.",
      );
      return;
    }

    // Tomamos SOLO la tabla (ya viene con thead/tbody renderizados)
    const { aoa, merges } = tableToAOAWithMerges(table);

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(aoa);

    // merges (rowspans/colspans)
    ws["!merges"] = merges;

    // rango total
    const range = XLSX.utils.decode_range(ws["!ref"]);

    // estilos + anchos
    applySheetStyles(ws, range);
    setColumnWidths(ws);

    XLSX.utils.book_append_sheet(wb, ws, "CostosCliente");

    XLSX.writeFile(wb, getFileName());
  }

  btn.addEventListener("click", function (e) {
    e.preventDefault();
    exportExcel();
  });
})();
