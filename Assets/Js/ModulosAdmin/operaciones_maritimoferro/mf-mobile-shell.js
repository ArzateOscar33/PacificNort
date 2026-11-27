/* MF.03.1 R1: cierre del panel móvil. El toggle original de la plantilla se conserva. */
(function (w, d) {
  'use strict';
  function boot() {
    const root = d.getElementById('main-wrapper');
    if (!root || !root.classList.contains('pn-mf-mobile-shell') || !d.getElementById('mf-module-root') || d.getElementById('mf-sidebar-backdrop')) return;
    const sidebar = root.querySelector('.left-sidebar');
    const toggle = root.querySelector('.nav-toggler');
    if (!sidebar || !toggle) return;
    const mobile = w.matchMedia('(max-width: 767.98px)');
    const backdrop = d.createElement('button');
    backdrop.type = 'button'; backdrop.id = 'mf-sidebar-backdrop';
    backdrop.setAttribute('aria-label', 'Cerrar menú lateral');
    backdrop.tabIndex = -1;
    root.insertBefore(backdrop, sidebar);
    if (!sidebar.id) sidebar.id = 'mf-admin-sidebar';
    toggle.setAttribute('aria-controls', sidebar.id);
    toggle.setAttribute('aria-label', 'Menú lateral');
    function sync() {
      const open = mobile.matches && root.classList.contains('show-sidebar');
      toggle.setAttribute('aria-expanded', String(open));
    }
    function close(restoreFocus) {
      if (!root.classList.contains('show-sidebar')) return;
      // Se utiliza el controlador original para mantener sincronizado el icono.
      toggle.click();
      if (restoreFocus && mobile.matches) toggle.focus();
      sync();
    }
    backdrop.addEventListener('click', () => close(true));
    d.addEventListener('keydown', e => {
      if (e.key === 'Escape' && mobile.matches && root.classList.contains('show-sidebar') && !d.querySelector('.modal.show, .swal2-container')) close(true);
    });
    const observer = new MutationObserver(sync);
    observer.observe(root, { attributes: true, attributeFilter: ['class'] });
    function resized() { if (!mobile.matches) close(false); sync(); }
    if (mobile.addEventListener) mobile.addEventListener('change', resized);
    else mobile.addListener(resized);
    sync();
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})(window, document);
