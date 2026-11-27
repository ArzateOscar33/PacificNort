/* MF.03.3 — Selector reutilizable, perfiles y preferencias locales. */
(function (global) {
  'use strict';
  if (global.PNMFColumns) return;
  const script = document.currentScript;
  const user = script?.dataset.mfUser || '';
  const appPath = script?.src ? new URL(script.src, location.href).pathname.split('/Assets/')[0] : '';
  const instances = new WeakMap();
  let sequence = 0;
  const el = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null) node.textContent = text;
    return node;
  };
  const normalize = s => String(s).trim().replace(/\s+/g, ' ').normalize('NFC');

  function mount({table, before, columns, onLayout, isBusy, profiles, preferenceKey} = {}) {
    if (typeof table === 'string') table = document.getElementById(table);
    if (typeof before === 'string') before = document.getElementById(before);
    if (!table || table.tagName !== 'TABLE' || !before?.parentNode) return null;
    if (instances.has(table)) return instances.get(table);
    const head = table.tHead;
    const body = table.tBodies[0];
    if (!head || head.rows.length !== 1 || table.tBodies.length !== 1 || !body || !Array.isArray(columns)) return null;
    const headers = Array.from(head.rows[0].cells);
    const keys = columns.map(c => c.key);
    if (headers.length !== columns.length || new Set(keys).size !== keys.length || !columns.length ||
        columns.some((c,i) => typeof c.key !== 'string' || !c.key || headers[i].tagName !== 'TH' || headers[i].colSpan !== 1 || headers[i].rowSpan !== 1 || normalize(headers[i].textContent) !== normalize(c.label))) {
      console.warn('[MF columnas] Cabecera incompatible; se conserva la tabla completa.');
      return null;
    }
    const required = new Set(columns.filter(c => c.required).map(c => c.key));
    const presets = new Map();
    for (const [name, values] of Object.entries(profiles || {})) {
      if (['compacto','estandar','completo'].includes(name) && Array.isArray(values) && values.every(k=>keys.includes(k))) {
        presets.set(name, keys.filter(k=>values.includes(k)||required.has(k)));
      }
    }
    const adaptive = presets.has('compacto') && presets.has('estandar') && presets.has('completo');
    const mobile = matchMedia('(max-width: 767.98px)');
    const tablet = matchMedia('(max-width: 1023.98px)');
    const defaultProfile = () => mobile.matches ? 'compacto' : tablet.matches ? 'estandar' : 'completo';
    const storageKey = preferenceKey && /^[1-9]\d*$/.test(user) ? `pn.mf.columns.v1:${appPath}:${user}:${preferenceKey}` : null;
    let mode = adaptive ? 'auto' : 'completo';
    let visible = new Set(adaptive ? presets.get(defaultProfile()) : keys);
    function loadPreference() {
      if (!storageKey) return;
      try {
        const data = JSON.parse(localStorage.getItem(storageKey));
        if (!data || data.version !== 1) return;
        if (presets.has(data.mode)) { mode=data.mode; visible=new Set(presets.get(mode)); }
        else if (data.mode==='personalizado' && Array.isArray(data.visible) && data.visible.every(k=>typeof k==='string')) {
          const saved=keys.filter(k=>data.visible.includes(k)||required.has(k));
          if (saved.length && data.visible.some(k=>keys.includes(k))) { mode=data.mode; visible=new Set(saved); }
        }
      } catch (_) { /* storage no disponible o datos inválidos: usar valores automáticos */ }
    }
    function savePreference() {
      if (!storageKey) return;
      try {
        if (mode==='auto') localStorage.removeItem(storageKey);
        else localStorage.setItem(storageKey, JSON.stringify({version:1,mode,visible:Array.from(visible)}));
      } catch (_) { /* la vista sigue operativa sin persistencia */ }
    }
    loadPreference();
    const token = 'mfcols-' + (++sequence);
    const style = el('style');
    style.dataset.mfColumnsStyle = token;
    table.dataset.mfColumnsToken = token;
    document.head.appendChild(style);

    const root = el('details', 'mf-columns');
    const summary = el('summary', 'mf-columns-summary');
    const title = el('span', 'mf-columns-title', 'Columnas');
    const count = el('span', 'mf-columns-count');
    const modeText = el('span','mf-columns-mode');
    summary.append(title, count, modeText);
    const panel = el('div', 'mf-columns-panel');
    const help = el('p', 'mf-columns-help', 'Selecciona las columnas que deseas ver. Código, Contenedor y Acciones permanecen visibles en Operaciones.');
    if (columns !== operationColumns) help.textContent = 'Selecciona las columnas que deseas ver. Las columnas obligatorias permanecen visibles.';
    const options = el('div', 'mf-columns-options');
    options.setAttribute('role', 'group');
    options.setAttribute('aria-label', 'Columnas visibles');
    const checks = new Map();
    columns.forEach(c => {
      const label = el('label', 'mf-columns-option' + (required.has(c.key) ? ' mf-columns-required' : ''));
      const input = el('input'); input.type = 'checkbox'; input.value = c.key;
      input.checked = true; input.disabled = required.has(c.key);
      const text = el('span', '', c.label);
      label.append(input, text);
      if (input.disabled) label.append(el('small', 'mf-columns-required-note', 'Obligatoria'));
      options.appendChild(label); checks.set(c.key, input);
      input.addEventListener('change', () => {
        const next = new Set(visible);
        if (input.checked) next.add(c.key); else next.delete(c.key);
        setVisible(Array.from(next));
      });
    });
    const profileBar = el('div','mf-columns-profiles');
    profileBar.setAttribute('role','group'); profileBar.setAttribute('aria-label','Perfiles de visualización');
    const profileButtons = new Map();
    const labels = {auto:'Automático',compacto:'Compacto',estandar:'Estándar',completo:'Completo',personalizado:'Personalizado'};
    for (const name of adaptive ? ['auto','compacto','estandar','completo'] : Array.from(presets.keys())) {
      const btn = el('button','btn btn-sm btn-outline-secondary mf-columns-profile',labels[name]);
      btn.type='button'; btn.addEventListener('click',()=>setProfile(name));
      profileButtons.set(name,btn); profileBar.appendChild(btn);
    }
    const actions = el('div', 'mf-columns-actions');
    const all = el('button', 'btn btn-sm btn-outline-secondary mf-columns-reset', 'Mostrar todas'); all.type = 'button';
    const close = el('button', 'btn btn-sm btn-outline-secondary', 'Cerrar'); close.type = 'button';
    actions.append(all, close);
    const status = el('p', 'mf-columns-status'); status.setAttribute('role','status'); status.setAttribute('aria-live','polite');
    panel.append(help); if(profileButtons.size) panel.append(profileBar); panel.append(options, actions, status); root.append(summary,panel);
    before.parentNode.insertBefore(root,before);

    let frame = 0;
    function adjustMessages() {
      // Solamente las filas de estado de una celda, no merges de datos.
      for (const row of body.rows) {
        if (row.cells.length === 1 && row.cells[0].hasAttribute('colspan')) {
          if (row.cells[0].colSpan !== visible.size) row.cells[0].colSpan = visible.size;
        }
      }
    }
    function layout() {
      if (frame) cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => { frame = 0; if (typeof onLayout === 'function') onLayout(); });
    }
    function syncControls() {
      count.textContent = visible.size + ' / ' + columns.length;
      for (const [key,input] of checks) input.checked = visible.has(key);
      all.disabled = visible.size === columns.length && mode === 'completo';
      modeText.textContent = adaptive ? (mode==='auto' ? 'Auto · '+labels[defaultProfile()] : labels[mode] || '') : '';
      for (const [name,btn] of profileButtons) btn.setAttribute('aria-pressed',String(name===mode));
    }
    function render() {
      // El CSS se aplica inmediatamente también a filas AJAX nuevas.
      const base = 'table[data-mf-columns-token="' + token + '"]';
      const rules = [];
      columns.forEach((c,i) => {
        if (visible.has(c.key)) return;
        rules.push(base + ' > thead > tr > th:nth-child(' + (i+1) + ')');
        rules.push(base + ' > tbody > tr > td:nth-child(' + (i+1) + '):not([colspan])');
      });
      style.textContent = rules.length ? rules.join(',\n') + ' { display: none !important; }' : '';
      syncControls(); adjustMessages(); layout();
      table.dispatchEvent(new CustomEvent('mf:columns-changed', {detail:{visible:Array.from(visible)}}));
    }
    function setVisible(next, nextMode = "personalizado", persist = true) {
      if (!Array.isArray(next) || next.some(k => !keys.includes(k))) return false;
      const requested = new Set([...next,...required]);
      if (!requested.size) return false;
      if (table.querySelector('.mf-editing, .mf-saving') || (typeof isBusy === 'function' && isBusy())) {
        syncControls(); status.textContent = 'Termina de editar o guardar la celda antes de cambiar las columnas.'; return false;
      }
      visible = new Set(keys.filter(k => requested.has(k)));
      mode = nextMode; status.textContent = ''; render(); if(persist) savePreference(); return true;
    }
    function setProfile(name) {
      if (name==='auto' && adaptive) return setVisible(presets.get(defaultProfile()),'auto');
      if (presets.has(name)) return setVisible(presets.get(name),name);
      return false;
    }
    let pendingAuto=false;
    function autoResize() {
      if (mode!=='auto' || !adaptive) { pendingAuto=false; return; }
      const next=presets.get(defaultProfile());
      if (next.length===visible.size && next.every(k=>visible.has(k))) { pendingAuto=false; syncControls(); return; }
      pendingAuto=!setVisible(next,'auto',false);
    }
    mobile.addEventListener('change',autoResize); tablet.addEventListener('change',autoResize);
    // Una orientación nueva durante un guardado no debe ocultar la celda activa.
    const busyObserver = new MutationObserver(()=>{ if(pendingAuto) autoResize(); });
    busyObserver.observe(body,{subtree:true,childList:true,attributes:true,attributeFilter:['class']});
    all.addEventListener('click', () => setVisible(keys,'completo'));
    close.addEventListener('click', () => { root.open = false; summary.focus(); });
    root.addEventListener('keydown', e => {
      if (e.key === 'Escape' && root.open) { e.preventDefault(); e.stopPropagation(); root.open = false; summary.focus(); }
    });
    const observer = new MutationObserver(() => { adjustMessages(); layout(); });
    observer.observe(body,{childList:true});
    const resize = typeof ResizeObserver === 'function' ? new ResizeObserver(layout) : null;
    resize?.observe(table);
    if (table.parentElement) resize?.observe(table.parentElement);
    const api = Object.freeze({getVisible:()=>Array.from(visible), getMode:()=>mode, setVisible:next=>setVisible(next), setProfile, showAll:()=>setVisible(keys,'completo'),
      destroy(){observer.disconnect();busyObserver.disconnect();mobile.removeEventListener('change',autoResize);tablet.removeEventListener('change',autoResize);resize?.disconnect();if(frame)cancelAnimationFrame(frame);root.remove();style.remove();delete table.dataset.mfColumnsToken;visible=new Set(keys);adjustMessages();if(typeof onLayout==='function')onLayout();instances.delete(table);}});
    instances.set(table,api); render(); return api;
  }
  const operationColumns = [
    ['codigo','Código',true], ['contenedor','Contenedor',true], ['etd','ETD'], ['eta','ETA'],
    ['peso','Peso'], ['bultos','Bultos'], ['medida','Medida'], ['mercancia','Mercancia'],
    ['transportista','Transportista'], ['broker','Broker'], ['bl','BL'], ['puerto','Puerto'],
    ['cliente','Cliente'], ['estatus','Estatus'], ['isf','ISF'], ['cita_puerto','Cita en Puerto'],
    ['ubicacion','Ubicación Actual'], ['observaciones','Observaciones'], ['ferro','Caja/Ferro'], ['destino','Destino'],
    ['fecha_salida','Fecha Salida'], ['ubicacion_ferro','Ubicación Actual Caja/Ferro'],
    ['transportista_ferro','Transportista Caja/Ferro'], ['acciones','Acciones',true]
  ].map(([key,label,required=false]) => Object.freeze({key,label,required}));
  function boot() {
    const table = document.getElementById('operaciones_mar_TablaExportar');
    const top = document.getElementById('mf_topScroll');
    const inner = document.getElementById('mf_topScrollInner');
    const wrap = table?.closest('.mf-table-wrap');
    if (!table || !top || !inner || !wrap) return;
    mount({table,before:top,columns:operationColumns,preferenceKey:'operaciones',profiles:{
      compacto:['codigo','contenedor','eta','estatus','ubicacion','acciones'],
      estandar:['codigo','contenedor','eta','cliente','estatus','ubicacion','ferro','destino','acciones'],
      completo:operationColumns.map(c=>c.key)
    },onLayout(){
      const width = Math.max(table.scrollWidth, wrap.scrollWidth);
      inner.style.width = width + 'px';
      top.style.display = wrap.scrollWidth > wrap.clientWidth ? 'block' : 'none';
      top.scrollLeft = wrap.scrollLeft;
    }});
  }
  global.PNMFColumns = Object.freeze({mount, get: table => instances.get(typeof table === 'string' ? document.getElementById(table) : table)});
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',boot,{once:true}); else boot();
})(window);
