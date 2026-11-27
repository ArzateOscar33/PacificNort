/* MF.03.1: selector de secciones responsive; Bootstrap sigue siendo la fuente de verdad. */
(function (w, d) {
  'use strict';
  function boot() {
    const shell = d.getElementById('mf-module-nav');
    const nav = d.getElementById('operacionTabs');
    const select = d.getElementById('mf-section-select');
    const mobile = d.getElementById('mf-mobile-nav');
    const Tab = w.bootstrap && w.bootstrap.Tab;
    if (!shell || !nav || !select || !mobile || !Tab || shell.dataset.mfNavReady === 'true') return;
    const tabs = Array.from(nav.querySelectorAll('a[data-bs-toggle="tab"]'));
    if (!tabs.length || tabs.some(tab => !tab.id || !d.getElementById(tab.getAttribute('aria-controls')))) return;
    const byId = new Map(tabs.map(tab => [tab.id, tab]));
    const options = d.createDocumentFragment();
    for (const tab of tabs) {
      const opt = d.createElement('option');
      opt.value = tab.id;
      opt.textContent = tab.textContent.trim().replace(/\s+/g, ' ');
      options.appendChild(opt);
    }
    function sync() {
      const active = tabs.find(tab => tab.classList.contains('active'));
      if (active) select.value = active.id;
    }
    select.replaceChildren(options);
    sync();
    for (const tab of tabs) {
      tab.addEventListener('shown.bs.tab', sync);
    }
    select.addEventListener('change', () => {
      const tab = byId.get(select.value);
      if (!tab) { sync(); return; }
      try {
        Tab.getOrCreateInstance(tab).show();
      } catch (error) {
        console.error('[MF navigation]', error);
        // Fallback: recuperar las pestañas reales si Bootstrap no puede cambiar de sección.
        mobile.hidden = true;
        delete shell.dataset.mfNavReady;
        tab.focus();
      }
      // También cubre un show cancelado o una selección repetida.
      sync();
    });
    mobile.hidden = false;
    shell.dataset.mfNavReady = 'true';
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})(window, document);
