// ===== Exportar a PDF (Resumen) =====
(function(){
  const btnPdfResumen   = document.getElementById('btnExportPdfResumen');
  const rootCardResumen = document.getElementById('cardResumenRoot');   // Área a “fotografiar”
  const sugBoxResumen   = document.getElementById('sugerenciasOperacionResumen');

  // Opcional: estilos que ayudan a que la captura salga limpia
  const exportCssResumen = document.createElement('style');
  exportCssResumen.id = 'exportCssResumen';
  exportCssResumen.textContent = `
    /* Evita sombras/animaciones para que el raster se vea más nítido */
    #cardResumenRoot * { transition: none !important; }
    /* Si tu dropdown de sugerencias está abierto, lo ocultamos en la foto */
    ._ocultar-en-export { visibility: hidden !important; }
    /* Mejora contraste de fondos en captura */
    .bg-warning, .bg-danger, .bg-info { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  `;
  document.head.appendChild(exportCssResumen);

  async function esperarResumen(ms){ return new Promise(r => setTimeout(r, ms)); }

  // Calcula un nombre de archivo amigable
  function buildNombreArchivoResumen(){
    const numeroOp = (document.getElementById('buscarOperacionResumen')?.value || '').trim();
    const opt      = document.getElementById('selectContenedorResumen')?.selectedOptions?.[0];
    const numCont  = opt?.dataset?.numero || opt?.textContent || 'CONTENEDOR';
    const opSlug   = numeroOp ? numeroOp.replace(/\s+/g,'_') : `OP_${operacionIdActivoResumen||''}`;
    const contSlug = String(numCont).trim().replace(/\s+/g,'_');
    return `Resumen_${opSlug}_${contSlug}.pdf`;
  }

  // Ajusta el root que se exporta (puedes cambiar a #informeOperacionResumen si quieres todo)
  function getExportRootResumen(){
    return rootCardResumen || document.getElementById('informeOperacionResumen') || document.body;
  }

  // Convierte el root a PNG grande y lo mete en un PDF A4 multipágina
  async function exportPDFResumen(){
    // 1) Oculta elementos flotantes que estorben (sugerencias)
    if (sugBoxResumen) sugBoxResumen.classList.add('_ocultar-en-export');

    // 2) Asegúrate que los gráficos hayan rendereado su frame final
    // (Chart.js actualiza de forma asíncrona tras setData/update)
    await esperarResumen(150);

    const root = getExportRootResumen();

    // 3) Captura del DOM → canvas
    const canvas = await html2canvas(root, {
      scale: window.devicePixelRatio < 2 ? 2 : window.devicePixelRatio, // alta resolución
      useCORS: true,
      backgroundColor: '#ffffff',
      logging: false,
      windowWidth: root.scrollWidth,
      windowHeight: root.scrollHeight
    });

    // 4) Canvas → imagen → PDF multipágina
    const imgData    = canvas.toDataURL('image/png');
    const { jsPDF }  = window.jspdf;
    const pdf        = new jsPDF('p', 'mm', 'a4');

    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();

    // Ajuste de ancho A4
    const imgW = pageW;
    const imgH = (canvas.height * imgW) / canvas.width;

    if (imgH <= pageH){
      pdf.addImage(imgData, 'PNG', 0, 0, imgW, imgH, undefined, 'FAST');
    } else {
      // multipágina: desplazando la imagen grande en Y
      let heightLeft = imgH;
      let position   = 0;
      while (heightLeft > 0) {
        pdf.addImage(imgData, 'PNG', 0, position, imgW, imgH, undefined, 'FAST');
        heightLeft -= pageH;
        if (heightLeft <= 0) break;
        pdf.addPage();
        position -= pageH;
      }
    }

    // 5) Guardar
    pdf.save(buildNombreArchivoResumen());

    // 6) Restaurar UI
    if (sugBoxResumen) sugBoxResumen.classList.remove('_ocultar-en-export');
  }
if (btnPdfResumen){
  btnPdfResumen.addEventListener('click', async (e) => {
    e.preventDefault();
    // ✔ validación
    if (!hasOperacionSeleccionadaResumen()){
      alert('Selecciona una operación y un contenedor antes de generar el informe en PDF.');
      return;
    }
    try {
      await exportPDFResumen();
    } catch (err) {
      console.error('[Resumen] Error exportando PDF:', err);
      alert('No fue posible generar el PDF.');
    }
  });
}
 
})();
