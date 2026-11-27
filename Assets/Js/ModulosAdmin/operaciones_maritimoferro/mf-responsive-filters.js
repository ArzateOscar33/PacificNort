/* MF.03.4 - Panel de filtros responsive; no realiza consultas. */
(function (global) {
  'use strict';
  if (global.PNMFFilters) return;
  const instances = new WeakMap();
  function mount(root, options = {}) {
    if (typeof root === 'string') root = document.querySelector(root);
    if (!root || instances.has(root)) return root && instances.get(root);
    const panel = root.querySelector('[data-mf-advanced]');
    const button = root.querySelector('[data-mf-filter-toggle]');
    const count = root.querySelector('[data-mf-filter-count]');
    if (!panel || !button || !count) return;
    const groups = options.groups || [];
    const mq = global.matchMedia(options.media || '(max-width: 1023.98px)');
    let mobileOpen = false;
    let disposed = false;
    let countTimer = null;
    function updateCount() {
      if (disposed) return;
      let n = 0;
      groups.forEach(group => {
        const active = group.some(field => Array.from(root.querySelectorAll(field.selector)).some(el => {
          if (el.type === 'checkbox' || el.type === 'radio') return el.checked;
          return String(el.value ?? '').trim() !== String(field.defaultValue ?? '');
        }));
        if (active) n++;
      });
      count.textContent = String(n);
      count.hidden = n === 0;
      button.setAttribute('aria-label', n ? `Filtros avanzados, ${n} grupos activos` : 'Filtros avanzados');
    }
    function hideDropdowns() {
      panel.querySelectorAll('[data-bs-toggle="dropdown"][aria-expanded="true"]').forEach(el => {
        const instance = global.bootstrap?.Dropdown?.getInstance(el);
        if (instance) instance.hide();
      });
    }
    function render() {
      const visible = !mq.matches || mobileOpen;
      button.hidden = !mq.matches;
      if (!visible) {
        hideDropdowns();
        if (panel.contains(document.activeElement)) button.focus();
      }
      panel.hidden = !visible;
      button.setAttribute('aria-expanded', String(visible));
      root.classList.toggle('pn-mf-filters-open', visible);
      updateCount();
    }
    function toggle() { if (mq.matches) { mobileOpen = !mobileOpen; render(); } }
    function changed() {
      clearTimeout(countTimer);
      countTimer = setTimeout(updateCount, 0);
    }
    function escape(e) {
      if (e.key !== 'Escape' || !mq.matches || !mobileOpen || e.defaultPrevented) return;
      if (panel.querySelector('.dropdown-menu.show') || document.querySelector('.modal.show, .swal2-container')) return;
      if (!root.contains(document.activeElement)) return;
      mobileOpen = false; render(); button.focus();
    }
    button.addEventListener('click', toggle);
    root.addEventListener('change', changed);
    root.addEventListener('input', changed);
    // Los botones originales de limpiar y ordenar cambian valores en sus propios listeners.
    root.addEventListener('click', changed, true);
    root.addEventListener('keydown', escape);
    if (mq.addEventListener) mq.addEventListener('change', render);
    else mq.addListener(render);
    const api = { updateCount, destroy() {
      disposed = true;
      clearTimeout(countTimer);
      button.removeEventListener('click', toggle);
      root.removeEventListener('change', changed);
      root.removeEventListener('input', changed);
      root.removeEventListener('click', changed, true);
      root.removeEventListener('keydown', escape);
      if (mq.removeEventListener) mq.removeEventListener('change', render); else mq.removeListener(render);
      panel.hidden = false; button.hidden = true;
      root.classList.remove('pn-mf-filters-open'); instances.delete(root);
    }};
    instances.set(root, api); render(); return api;
  }
  global.PNMFFilters = Object.freeze({ mount });
  function boot() {
    mount('#pn-mf-filters-operaciones', {groups: [
      [{selector:'#maritimo_ferro_fechaInicio'}, {selector:'#maritimo_ferro_fechaFin'}],
      [{selector:'#maritimo_ferro_ordenEta', defaultValue:'asc'}],
      [{selector:'#maritimo_ferro_filtroSubtipo'}],
      [{selector:'.chkFiltroEstatus'}], [{selector:'.chkFiltroCliente'}],
      [{selector:'.chkFiltroTransportista'}], [{selector:'.chkFiltroBroker'}],
      [{selector:'#maritimo_ferro_filtroMedidaContenedor'}]
    ]});
  }
  // Script al final de la vista: los controles ya existen; evitar esperar al listado AJAX.
  if (document.querySelector('#pn-mf-filters-operaciones')) boot();
  else if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, {once:true});
})(window);
