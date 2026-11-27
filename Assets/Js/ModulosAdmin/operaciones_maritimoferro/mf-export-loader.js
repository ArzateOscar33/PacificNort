/* MF.02.3: dependencias de exportacion bajo demanda; solo modulo MF. */
(function (w, d) {
  'use strict';
  const base = new URL('Assets/Js/ModulosAdmin/librerias/', new URL(w.BASE_URL, w.location.href)).href;
  const urls = {
    xlsx: base + 'xlsx.full.min.js',
    styled: 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    pdf: base + 'jspdf.umd.min.js',
    auto: base + 'jspdf.plugin.autotable.min.js',
    canvas: base + 'html2canvas.min.js'
  };
  const pending = new Map();
  const variants = new Map();
  let busy = false;
  function load(key, valid) {
    if (pending.has(key)) return pending.get(key);
    const promise = new Promise((resolve, reject) => {
      const s = d.createElement('script');
      s.src = urls[key]; s.async = true;
      let timer;
      const fail = () => { clearTimeout(timer); s.onload = s.onerror = null; s.remove(); reject(new Error('No se pudo cargar ' + key)); };
      s.onerror = fail;
      s.onload = () => {
        if (!valid()) { fail(); return; }
        clearTimeout(timer); s.onload = s.onerror = null; resolve();
      };
      timer = setTimeout(fail, 30000);
      d.head.appendChild(s);
    });
    pending.set(key, promise);
    promise.catch(() => pending.delete(key));
    return promise;
  }
  async function excel(key) {
    if (!variants.has(key)) {
      // Both libraries publish window.XLSX; load sequentially and retain each API.
      w.XLSX = undefined;
      await load(key, () => !!w.XLSX?.utils && typeof w.XLSX.writeFile === 'function');
      variants.set(key, w.XLSX);
    }
    w.XLSX = variants.get(key);
  }
  async function dependencies(kind) {
    if (kind === 'xlsx' || kind === 'styled') return excel(kind);
    if (kind !== 'pdf' && kind !== 'resumen') throw new Error('Formato no soportado');
    await load('pdf', () => typeof w.jspdf?.jsPDF === 'function');
    if (kind === 'pdf') await load('auto', () => typeof w.jspdf?.jsPDF?.API?.autoTable === 'function');
    else await load('canvas', () => typeof w.html2canvas === 'function');
  }
  async function run(kind, action, button = d.activeElement) {
    if (busy) return false;
    busy = true;
    const oldXLSX = w.XLSX;
    const isButton = button?.tagName === 'BUTTON';
    const oldBusy = isButton ? button.getAttribute('aria-busy') : null;
    const pane = isButton ? button.closest('.tab-pane') : null;
    if (isButton) button.setAttribute('aria-busy', 'true');
    try {
      await dependencies(kind);
      if (pane && !pane.classList.contains('active')) {
        w.alert('Regresa a la pestaña de origen y vuelve a pulsar Exportar.');
        return false;
      }
      await action();
      return true;
    } catch (err) {
      console.error('[MF exportacion]', err);
      w.alert('No fue posible generar el archivo. Verifica la conexión e inténtalo nuevamente.');
      return false;
    } finally {
      if (kind === 'xlsx' || kind === 'styled') {
        if (oldXLSX === undefined) w.XLSX = undefined;
        else w.XLSX = oldXLSX;
      }
      if (isButton) {
        if (oldBusy === null) button.removeAttribute('aria-busy');
        else button.setAttribute('aria-busy', oldBusy);
      }
      busy = false;
    }
  }
  w.PNMFExport = Object.freeze({run});
})(window, document);
