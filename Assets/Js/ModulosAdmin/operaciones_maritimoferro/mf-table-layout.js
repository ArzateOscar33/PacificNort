/* MF.03.5 — Layout de tabla, sin llamadas AJAX ni modificación de datos. */
(function () {
  'use strict';
  function boot() {
    const card=document.getElementById('mf-operations-card');
    const table=document.getElementById('operaciones_mar_TablaExportar');
    const filters=document.getElementById('pn-mf-filters-operaciones');
    const top=document.getElementById('mf_topScroll');
    const inner=document.getElementById('mf_topScrollInner');
    const wrap=table?.closest('.mf-table-wrap');
    const btn=document.getElementById('mf-expand-table');
    const restore=document.getElementById('mf-restore-table');
    const status=document.getElementById('mf-table-layout-status');
    const advanced=filters?.querySelector('[data-mf-advanced]');
    const pane=card?.closest('.tab-pane');
    if (!card || !table || !filters || !top || !inner || !wrap || !btn || !restore || !status || !advanced || !pane || card.dataset.mfLayoutReady) return;
    card.dataset.mfLayoutReady='true';
    const mq=matchMedia('(max-width:1023.98px)');
    let expanded=false, columns=null, frame=0;
    function layout() {
      if(frame) cancelAnimationFrame(frame);
      frame=requestAnimationFrame(()=>{frame=0;if(!pane.classList.contains('active'))return;
        inner.style.width=Math.max(table.scrollWidth,wrap.scrollWidth)+'px';
        top.style.display=wrap.scrollWidth>wrap.clientWidth?'block':'none';top.scrollLeft=wrap.scrollLeft;
      });
    }
    function regroup() {
      columns ||= card.querySelector('.mf-columns');
      if (!columns) return;
      if (mq.matches) {
        if (columns.parentElement!==filters) filters.insertBefore(columns,advanced);
        filters.classList.add('pn-mf-layout-tools');
      } else {
        if (columns.parentElement!==top.parentElement) top.parentElement.insertBefore(columns,top);
        filters.classList.remove('pn-mf-layout-tools');
      }
      layout();
    }
    // Boot de MF.03.3 puede ejecutarse más tarde; observar únicamente hasta encontrar su selector.
    const mountObserver=new MutationObserver(()=>{if(card.querySelector('.mf-columns')){regroup();mountObserver.disconnect();}});
    if(!card.querySelector('.mf-columns'))mountObserver.observe(card,{childList:true,subtree:true});
    regroup();
    const busy=()=>!!table.querySelector('.mf-editing,.mf-saving');
    function setExpanded(value,{force=false,focus=true}={}) {
      if(value===expanded)return true;
      if(!force && (busy() || document.querySelector('.modal.show,.swal2-container'))) {
        status.textContent='Termina la edición, el guardado o el diálogo antes de cambiar la vista.';return false;
      }
      if(value && !pane.classList.contains('active'))return false;
      status.textContent='';expanded=value;
      card.classList.toggle('pn-mf-expanded',value);
      document.body.classList.toggle('pn-mf-table-expanded',value);
      btn.setAttribute('aria-pressed',String(value));
      btn.querySelector('[data-mf-expand-label]').textContent=value?'Restaurar':'Ampliar';
      restore.hidden=!value;
      if(focus)(value?restore:btn).focus({preventScroll:true});
      layout();return true;
    }
    btn.addEventListener('click',()=>setExpanded(!expanded));
    restore.addEventListener('click',()=>setExpanded(false));
    document.addEventListener('keydown',e=>{
      if(!expanded || e.key!=='Escape' || e.defaultPrevented || busy() || document.querySelector('.modal.show,.swal2-container,.dropdown-menu.show'))return;
      if(card.querySelector('.mf-columns[open]') || (mq.matches && !advanced.hidden))return;
      e.preventDefault();setExpanded(false);
    });
    const tab=document.getElementById('crear_operacions-tab');
    tab?.addEventListener('hide.bs.tab',()=>setExpanded(false,{force:true,focus:false}));
    new MutationObserver(()=>{if(!pane.classList.contains('active'))setExpanded(false,{force:true,focus:false});else layout();}).observe(pane,{attributes:true,attributeFilter:['class']});
    const changed=()=>{regroup();layout();};
    if(mq.addEventListener)mq.addEventListener('change',changed);else mq.addListener(changed);
    window.addEventListener('resize',layout);
    table.addEventListener('mf:columns-changed',layout);
    if(typeof ResizeObserver==='function'){const resize=new ResizeObserver(layout);resize.observe(wrap);resize.observe(table);}
    new MutationObserver(()=>{layout();}).observe(table.tBodies[0],{childList:true});
    layout();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
