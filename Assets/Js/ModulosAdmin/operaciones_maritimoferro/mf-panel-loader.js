/* MF.02.4 D - carga de fragmentos autenticados, sin ejecutar scripts del HTML. */
(function (w, d) {
  'use strict';
  const nav = d.getElementById('operacionTabs');
  if (!nav || !w.BASE_URL) return;
  const base = new URL(w.BASE_URL, w.location.href);
  const dir = new URL('Assets/Js/ModulosAdmin/operaciones_maritimoferro/', base);
  const configs = {
    'resumen': {table: 'informeOperacionResumen', scripts: ['../librerias/chart.js', '../librerias/chartjs-adapter-date-fns.js', 'resumen_graficos.js', 'resumen.js', 'resumen_exportar.js']},
    'en-piso': {table: 'mercanciaPisoTabla', scripts: ['en_piso.js']},
    'costos_operacion_clientes': {table: 'costosCliente_table', scripts: ['costos_clientes.js', 'exportarCostosClientes.js']},
    'Eventos_Logisticos': {table: 'tablaEventosMar', scripts: ['eventos_logisticos_mar.js']},
    'Eventos_Logisticos_ferro': {table: 'tablaEventosFer', scripts: ['eventos_logisticos_fer.js'], uppercase: ['eventosFerFiltroContenedor', 'eventosFerFiltroFerro']},
    'trazabilidad': {table: 'tablaRutas', scripts: ['trazabilidad_catalogo.js'], uppercase: ['rutaOperacionFerroNombre']},
    'documentos': {table: 'tablaDocumentos', scripts: ['documentos_ferro.js'], uppercase: ['documentosFiltroOpNombre']},
    'costos_operacion': {table: 'tablaCostosOperacionExportar', scripts: ['costos_operacion_ferro_catalogo.js', 'costos_operacion_ferro_registrar.js'], uppercase: ['costosOperacionFiltroOpNombre', 'costosOperacionNombre', 'costosContenedoresFactura']}
  };
  const states = new Map();
  const scripts = new Map();
  function loadScript(name) {
    if (scripts.has(name)) return scripts.get(name);
    const task = new Promise((resolve, reject) => {
      const tag = d.createElement('script');
      tag.src = new URL(name + '?v=mf024d', dir).href;
      tag.async = false;
      tag.onload = () => { tag.onload = tag.onerror = null; resolve(); };
      tag.onerror = () => { tag.remove(); reject(new Error('No se pudo cargar ' + name)); };
      d.head.appendChild(tag);
    });
    scripts.set(name, task);
    task.catch(() => scripts.delete(name));
    return task;
  }
  function status(panel, text, retry) {
    let el = panel.querySelector('[data-mf-panel-status]');
    if (!el) { el = d.createElement('div'); el.setAttribute('data-mf-panel-status', ''); panel.prepend(el); }
    el.className = 'alert ' + (retry ? 'alert-warning' : 'alert-light') + ' mt-3';
    el.setAttribute('role', retry ? 'alert' : 'status');
    el.replaceChildren(d.createTextNode(text));
    if (retry) {
      const b = d.createElement('button'); b.type = 'button'; b.className = 'btn btn-sm btn-outline-secondary ms-2';
      b.textContent = 'Reintentar'; b.addEventListener('click', retry); el.appendChild(b);
    }
  }
  async function mount(id) {
    const config = configs[id];
    const panel = d.getElementById(id);
    if (!config || !panel) return;
    const state = states.get(id) || {html: false, pending: false, done: false};
    states.set(id, state);
    if (state.done || state.pending) return;
    state.pending = true;
    panel.setAttribute('aria-busy', 'true');
    status(panel, 'Cargando información…');
    try {
      if (!state.html) {
        // Same authenticated navigation route and operation id (if any).
        const url = new URL(w.location.href); url.hash = ''; url.searchParams.set('mf_panel', id);
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 30000);
        let html;
        try {
          const res = await w.fetch(url.href, {credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
          if (!res.ok || res.headers.get('X-PN-MF-Panel') !== id || !/^text\/html\b/i.test(res.headers.get('Content-Type') || '')) {
            throw new Error('Respuesta de panel no válida (HTTP ' + res.status + ')');
          }
          html = await res.text();
        } finally { clearTimeout(timeout); }
        const template = d.createElement('template'); template.innerHTML = html;
        // Only known static JS files are loaded from the manifest above.
        template.content.querySelectorAll('script').forEach(node => node.remove());
        if (!template.content.querySelector('#' + config.table)) throw new Error('Fragmento incompleto');
        panel.appendChild(template.content);
        state.html = true;
      }
      for (const name of config.scripts) await loadScript(name);
      // Equivalente al script inline de Eventos Terrestres, que no se ejecuta al montar HTML.
      for (const fieldId of config.uppercase || []) {
        const input = d.getElementById(fieldId);
        if (!input || !panel.contains(input)) continue;
        input.addEventListener('input', function () {
          const start = this.selectionStart, end = this.selectionEnd;
          const value = this.value.toUpperCase();
          if (value === this.value) return;
          this.value = value;
          if (start !== null && end !== null) this.setSelectionRange(start, end);
        });
      }
      if (w.feather && typeof w.feather.replace === 'function') w.feather.replace();
      state.done = true;
      panel.querySelector('[data-mf-panel-status]')?.remove();
    } catch (e) {
      console.error('[MF panel ' + id + ']', e);
      status(panel, 'No se pudo cargar esta pestaña. Verifica tu sesión y conexión.', () => mount(id));
    } finally { state.pending = false; panel.removeAttribute('aria-busy'); }
  }
  function boot() {
    for (const tab of nav.querySelectorAll('[data-bs-toggle="tab"]')) {
      const target = tab.getAttribute('data-bs-target') || tab.getAttribute('href') || '';
      const id = target.slice(1);
      if (!Object.prototype.hasOwnProperty.call(configs, id)) continue;
      tab.addEventListener('shown.bs.tab', () => mount(id));
      if (d.getElementById(id)?.classList.contains('active')) mount(id);
    }
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', boot, {once: true});
  else boot();
})(window, document);
