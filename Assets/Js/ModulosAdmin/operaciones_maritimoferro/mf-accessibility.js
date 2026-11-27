/* MF.03.7 — mejora progresiva, sin solicitudes ni cambios a eventos de negocio. */
(function(d){
  'use strict';
  function boot(){
    const root=d.getElementById('mf-module-root');
    if(!root || root.dataset.mfA11y==='1') return;
    root.dataset.mfA11y='1';
    const byId=id=>d.getElementById(id);
    const name=(id,label)=>{const e=byId(id);if(e&&!e.hasAttribute('aria-label')&&!e.hasAttribute('aria-labelledby'))e.setAttribute('aria-label',label);};
    name('maritimo_ferro_buscarOperacion','Buscar por código, BL o contenedor');
    name('maritimo_ferro_filtroSubtipo','Subtipo de operación');
    name('maritimo_ferro_filtroMedidaContenedor','Medida del contenedor');
    const skip=d.createElement('a');skip.id='mf-skip-link';skip.href='#mf-module-root';skip.textContent='Saltar al contenido principal';
    d.body.prepend(skip);skip.addEventListener('click',e=>{e.preventDefault();root.focus();root.scrollIntoView({block:'start'});});
    const shell=byId('main-wrapper');
    if(shell){
      const toggle=shell.querySelector('.nav-toggler');const top=shell.querySelector('.topbartoggler');
      [[toggle,'Menú lateral'],[top,'Menú de usuario']].forEach(([el,label])=>{if(!el)return;el.setAttribute('aria-label',label);el.setAttribute('role','button');el.addEventListener('keydown',e=>{if(e.key===' '){e.preventDefault();el.click();}});});
    }
    const operation=byId('modalMaritimoFerro'), assignment=byId('modalAsignarFerroCaja');
    if(operation) operation.setAttribute('aria-labelledby','tituloModalOperacion_mf');
    if(assignment){const title=assignment.querySelector('.modal-title');if(title){if(!title.id)title.id='mf-asignacion-titulo';assignment.setAttribute('aria-labelledby',title.id);}}
    // Asociar etiquetas existentes sin modificar ids/names utilizados por los scripts.
    // Las filas clonadas no reciben IDs nuevos: usan el texto de su etiqueta como nombre accesible.
    function labels(scope){
      if(!scope)return;
      scope.querySelectorAll('label.form-label:not([for])').forEach(label=>{
        const candidates=Array.from(label.parentElement.querySelectorAll('input:not([type=hidden]), select, textarea')).filter(e=>!e.closest('template'));
        if(candidates.length!==1)return;
        const el=candidates[0],txt=label.textContent.trim();if(!txt)return;
        if(el.id&&byId(el.id)===el){label.htmlFor=el.id;}
        else if(!el.hasAttribute('aria-label')&&!el.hasAttribute('aria-labelledby')){el.setAttribute('aria-label',txt);}
      });
    }
    labels(operation);labels(assignment);
    const repeater=byId('contenedoresRepeater_mf');
    if(repeater)new MutationObserver(()=>labels(repeater)).observe(repeater,{childList:true,subtree:true});
    const folio=byId('numeroOperacion_mf');if(folio&&byId('folioHelp_mf'))folio.setAttribute('aria-describedby','folioHelp_mf');
    // Los encabezados de la tabla plana no necesitan modificar la estructura ni los datos.
    const table=byId('operaciones_mar_TablaExportar');if(table){table.setAttribute('aria-label','Listado de operaciones marítimo-ferroviarias');table.querySelectorAll('thead th').forEach(th=>th.setAttribute('scope','col'));}
  }
  if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})(document);
