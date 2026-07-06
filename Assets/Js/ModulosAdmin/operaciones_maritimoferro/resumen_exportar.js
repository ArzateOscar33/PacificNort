// ===== Exportar a PDF (Resumen) =====
(function () {
  "use strict";

  const btnPdfResumen = document.getElementById("btnExportPdfResumen");
  const sugBoxResumen = document.getElementById("sugerenciasOperacionResumen");

  // Root principal de tu nueva vista
  const rootInforme = document.getElementById("informeOperacionResumen");
  const rootContenido = document.getElementById("contenidoOperacion"); // opcional

  // Opcional: ocultar cosas al exportar para que salga limpio
  const exportCssResumen = document.createElement("style");
  exportCssResumen.id = "exportCssResumen";
  exportCssResumen.textContent = `
    /* evita animaciones/transiciones en raster */
    #informeOperacionResumen * { transition: none !important; }

    /* ocultar elementos flotantes (autocomplete) y botones si quieres */
    ._ocultar-en-export { visibility: hidden !important; }

    /* preservar colores de bootstrap en captura */
    .bg-warning, .bg-danger, .bg-info, .bg-success { 
      -webkit-print-color-adjust: exact; 
      print-color-adjust: exact; 
    }
  `;
  document.head.appendChild(exportCssResumen);

  function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
  }

  function getSelectedContenedorNumero() {
    const sel = document.getElementById("selectContenedorResumen");
    const opt = sel?.selectedOptions?.[0];
    // en tu select, guardas dataset.numero y también el texto
    return (opt?.dataset?.numero || opt?.textContent || "").trim();
  }

  function getOperacionLabel() {
    return (
      document.getElementById("buscarOperacionResumen")?.value || ""
    ).trim();
  }

  function slugifyFilename(s) {
    return String(s || "")
      .trim()
      .replace(/[\/\\:*?"<>|]/g, "") // caracteres prohibidos en Windows
      .replace(/\s+/g, "_")
      .slice(0, 80);
  }

  function hasOperacionSeleccionada() {
    const op = getOperacionLabel();
    const cont = getSelectedContenedorNumero();
    // operacion debe existir y el select debe tener selección válida
    const sel = document.getElementById("selectContenedorResumen");
    const contId =
      sel?.value || sel?.selectedOptions?.[0]?.dataset?.baseId || "";
    return !!op && !!contId && cont !== "";
  }

  function buildNombreArchivo() {
    const op = slugifyFilename(getOperacionLabel()) || "OPERACION";
    const cont = slugifyFilename(getSelectedContenedorNumero()) || "CONTENEDOR";
    return `Resumen_${op}_${cont}.pdf`;
  }

  // ✅ Decide qué parte exportas:
  // - Si quieres TODO (incluye charts, docs, eventos): informeOperacionResumen
  // - Si quisieras solo el contenido interno: contenidoOperacion
  function getExportRoot() {
    return rootInforme || rootContenido || document.body;
  }

  async function exportPDF() {
    // Oculta sugerencias si están abiertas
    if (sugBoxResumen) sugBoxResumen.classList.add("_ocultar-en-export");

    // (Opcional) oculta botón PDF/refrescar para que no salgan en la foto:
    const btnRef = document.getElementById("btnRefrescarResumen");
    if (btnRef) btnRef.classList.add("_ocultar-en-export");
    if (btnPdfResumen) btnPdfResumen.classList.add("_ocultar-en-export");

    // Espera a que Chart.js termine de pintar el frame
    await sleep(200);

    const root = getExportRoot();

    const canvas = await html2canvas(root, {
      scale: Math.max(2, window.devicePixelRatio || 2),
      useCORS: true,
      backgroundColor: "#ffffff",
      logging: false,
      // Esto ayuda cuando hay contenido ancho/alto
      windowWidth: root.scrollWidth,
      windowHeight: root.scrollHeight,
      scrollX: 0,
      scrollY: -window.scrollY,
    });

    const imgData = canvas.toDataURL("image/png");
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF("p", "mm", "a4");

    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();

    const imgW = pageW;
    const imgH = (canvas.height * imgW) / canvas.width;

    if (imgH <= pageH) {
      pdf.addImage(imgData, "PNG", 0, 0, imgW, imgH, undefined, "FAST");
    } else {
      // multipágina
      let heightLeft = imgH;
      let position = 0;

      while (heightLeft > 0) {
        pdf.addImage(
          imgData,
          "PNG",
          0,
          position,
          imgW,
          imgH,
          undefined,
          "FAST",
        );
        heightLeft -= pageH;
        if (heightLeft <= 0) break;
        pdf.addPage();
        position -= pageH;
      }
    }

    pdf.save(buildNombreArchivo());

    // restaurar UI
    if (sugBoxResumen) sugBoxResumen.classList.remove("_ocultar-en-export");
    if (btnRef) btnRef.classList.remove("_ocultar-en-export");
    if (btnPdfResumen) btnPdfResumen.classList.remove("_ocultar-en-export");
  }

  if (btnPdfResumen) {
    btnPdfResumen.addEventListener("click", async (e) => {
      e.preventDefault();

      if (!hasOperacionSeleccionada()) {
        alert(
          "Selecciona una operación y un contenedor antes de generar el PDF.",
        );
        return;
      }

      try {
        await exportPDF();
      } catch (err) {
        console.error("[Resumen] Error exportando PDF:", err);
        alert("No fue posible generar el PDF.");
      }
    });
  }
})();
