 
(function(){
  "use strict";

  // ===== Refs usados en este archivo =====
  const operacionNombreFerroOP       = document.getElementById('operacionNombreFerroOP'); // Folio FO (solo lectura)
  const contenedorFerroIdFerroOP     = document.getElementById('contenedorFerroIdFerroOP');
  const contenedorFerroNombreFerroOP = document.getElementById('contenedorFerroNombreFerroOP');

  const destinoIdFerroOP             = document.getElementById('destinoIdFerroOP');
  const destinoNombreFerroOP         = document.getElementById('destinoNombreFerroOP');

  const transportistaIdFerroOP       = document.getElementById('transportistaIdFerroOP');
  const transportistaNombreFerroOP   = document.getElementById('transportistaNombreFerroOP');

  // Campos del selector NUEVO (operación marítima + MG)
  const opInp     = document.getElementById('operacionMaritimaNombreFerroOP');  // editable
  const opIdHid   = document.getElementById('operacionMaritimaIdFerroOP');      // operacion_id
  const cmoIdHid  = document.getElementById('contMaritimoOperacionIdFerroOP');  // cmo.id
  const sugBox    = document.getElementById('sugOperacionesMaritimasFerroOP');

  const contIdHid   = document.getElementById('contenedorMaritimoIdFerroOP');
  const contNameInp = document.getElementById('contenedorMaritimoNombreFerroOP'); // readonly
  const cliNameInp  = document.getElementById('clienteNombreMaritimoFerroOP');    // readonly

  const bultosTotInp = document.getElementById('bultosMaritimoFerroOP');   // readonly
  const restInp      = document.getElementById('bultosRestantesFerroOP');  // readonly
  const asigInp      = document.getElementById('bultosAsignadosFerroOP');  // editable

  // Hacer el folio FO de solo lectura (se prellena al abrir modal)
  if (operacionNombreFerroOP) operacionNombreFerroOP.readOnly = true;

  // ==== helpers ====
  const toInt = (v)=> Number.isFinite(Number(v)) ? Number(v) : 0;
  function showList(el){ if (el){ el.style.display = 'block'; } }
  function hideList(el){ if (el){ el.style.display = 'none'; el.innerHTML = ''; } }
const form = document.getElementById("formFerroOP");
 
if (form && !form.dataset.mode) {
  form.dataset.mode = 'create';   // ← default al cargar la página
}
  // ==============
  // AUTOCOMPLETE: Operación marítima (con MG incluido)
  // ==============
  function renderSuggestions(items, onPick){
    sugBox.innerHTML = '';
    if (!items || !items.length){ hideList(sugBox); return; }
    for (const it of items){
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
      btn.innerHTML = `
        <span>
          <strong>${it.numero_operacion}</strong> — ${it.numero_contenedor}
          <small class="text-muted"> · ${it.cliente || ''}</small>
        </span>
        <small class="text-muted">Tot: ${it.bultos_totales} · Asig: ${it.bultos_asignados} · Rest: ${it.bultos_restantes}</small>
      `;
      btn.addEventListener('pointerdown', (e)=>{ e.preventDefault(); onPick(it); });
      btn.addEventListener('click',       (e)=>{ e.preventDefault(); onPick(it); });
      sugBox.appendChild(btn);
    }
    showList(sugBox);
  }

  let lastXHR = null, deb = null;
  opInp?.addEventListener('input', function(){

// === SOLO LIMPIA EL BLOQUE MARÍTIMO SI EL USUARIO BORRA CARACTERES ===
const prevLen = Number(this.dataset.prevLen || 0);
const currLen = (this.value || '').length;

if (currLen < prevLen) {
  // Limpia únicamente la línea/bloque marítimo actual
  if (typeof window.limpiarLinea === 'function') window.limpiarLinea();

  // Asegúrate de que el selector marítimo siga visible para volver a escribir
  if (typeof window.toggleSelectorMaritimo === 'function') window.toggleSelectorMaritimo(true);
}
// Actualiza el largo previo
this.dataset.prevLen = String(currLen);


    // Limpiar dependientes al teclear
    opIdHid.value   = '';
    cmoIdHid.value  = '';
    contIdHid.value = '';
    opInp.dataset.lastPick = '0';

    contNameInp.value = '';
    cliNameInp.value  = '';
    bultosTotInp.value= '';
    restInp.value     = '';
    asigInp.value     = '';

    const q = (this.value||'').trim();
    if (q.length < 2){ hideList(sugBox); return; }

    if (deb) clearTimeout(deb);
    deb = setTimeout(()=>{
      if (lastXHR && lastXHR.readyState !== 4) try{ lastXHR.abort(); }catch{}
      const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/sugerencias_operaciones_maritimas'
                + '?q=' + encodeURIComponent(q) + '&limit=12';
      const x = new XMLHttpRequest(); lastXHR = x;
      x.open('GET', url, true);
      x.onload = ()=>{
        if (x.status !== 200){ hideList(sugBox); return; }
        let resp = {};
        try { resp = JSON.parse(x.responseText||'{}'); } catch { resp = {}; }
        const items = Array.isArray(resp.items) ? resp.items : [];
        renderSuggestions(items, onPickOp);
      };
      x.onerror = ()=> hideList(sugBox);
      x.send();
    }, 220);
  });

  opInp?.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideList(sugBox); });
  opInp?.addEventListener('blur',   ()=> setTimeout(()=> hideList(sugBox), 150));
  const opMarInp   = document.getElementById('operacionMaritimaNombreFerroOP');
  const opMarIdHid = document.getElementById('operacionMaritimaIdFerroOP');

  // al teclear:
  if (opMarInp) opMarInp.dataset.lastPick = '0';

  // al elegir:
function onPickOp(it){
  // IDs y campos base
  opMarIdHid.value = it.operacion_id;
  cmoIdHid.value   = it.cmo_id;

  opMarInp.value    = it.numero_operacion;
  contIdHid.value   = it.contenedor_maritimo_id;
  contNameInp.value = it.numero_contenedor;
  cliNameInp.value  = it.cliente || '';

  const tot  = Number(it.bultos_totales ?? 0);
  const rest = Number(it.bultos_restantes ?? 0);
  bultosTotInp.value = String(tot);
  restInp.value      = String(rest);

  // Prefill “Asignar al Ferro” (sin duplicar líneas)
  asigInp.min  = '1';
  asigInp.step = '1';
  asigInp.value = rest > 0 ? '1' : '';

  // Muy importante: setear el pick antes de toggle
  opMarInp.dataset.lastPick = '1';

  // Refrescar estado del botón confirmar
  if (typeof window.toggleAsignBtn === 'function') {
    window.toggleAsignBtn();
  }

  // UX
  asigInp.focus();
  asigInp.select();
  hideList(sugBox);
}


  // Validación visual de saldo (si quieres mostrar un badge)
  asigInp?.addEventListener('input', function(){
    const restBase = toInt(restInp.value || 0);
    const asig     = toInt(this.value || 0);
    const saldo    = restBase - asig;
    // badgeSaldoFerroOP?.textContent = `Saldo: ${saldo}`;
  });

  // ==============
  // AUTOCOMPLETE: FERRO/CAJA
  // ==============
  (() => {
    const inp  = document.getElementById('contenedorFerroNombreFerroOP');
    const hid  = document.getElementById('contenedorFerroIdFerroOP');
    const box  = document.getElementById('sugFerrosFerroOP');
    if (!inp || !hid || !box) return;

    function showList(){ box.style.display = 'block'; }
    function hideList(){ box.style.display = 'none'; box.innerHTML = ''; }

    function render(items){
      box.innerHTML = '';
      if (!items || !items.length){ hideList(); return; }
      for (const it of items){
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = it.label;
        a.onclick = (e)=>{ e.preventDefault(); hid.value = it.id; inp.value = it.label; hideList(); };
        box.appendChild(a);
      }
      showList();
    }

    let lastXHR = null, deb = null;
    function fetchSug(q){
      if (lastXHR && lastXHR.abort) lastXHR.abort();
      const x = new XMLHttpRequest();
      lastXHR = x;
      const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/buscar_ferros'
                + '?term=' + encodeURIComponent(q) + '&limit=15';
      x.open('GET', url, true);
      x.onload = ()=>{
        if (x.status !== 200){ hideList(); return; }
        try {
          const resp = JSON.parse(x.responseText||'{}');
          if (resp.ok !== true){ hideList(); return; }
          render(resp.items || []);
        } catch { hideList(); }
      };
      x.onerror = ()=> hideList();
      x.send();
    }

    inp.addEventListener('input', ()=>{
      const q = (inp.value||'').trim();
      hid.value = '';
      if (deb) clearTimeout(deb);
      deb = setTimeout(()=> { if (q.length >= 2) fetchSug(q); else hideList(); }, 180);
    });

    inp.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideList(); });
    document.addEventListener('click', (e)=>{ if (!box.contains(e.target) && e.target !== inp) hideList(); });
  })();

  // ==============
  // AUTOCOMPLETE: DESTINOS
  // ==============
  (() => {
    const inp  = document.getElementById('destinoNombreFerroOP');
    const hid  = document.getElementById('destinoIdFerroOP');
    const box  = document.getElementById('destinoFerroOP');
    if (!inp || !hid || !box) return;

    function showList(){ box.style.display = 'block'; }
    function hideList(){ box.style.display = 'none'; box.innerHTML = ''; }

    function render(items){
      box.innerHTML = '';
      if (!items || !items.length){ hideList(); return; }
      for (const it of items){
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = it.label;
        a.onclick = (e)=>{ e.preventDefault(); hid.value = it.id; inp.value = it.label; hideList(); };
        box.appendChild(a);
      }
      showList();
    }

    let lastXHR = null, deb = null;
    function fetchSug(q){
      if (lastXHR && lastXHR.abort) lastXHR.abort();
      const x = new XMLHttpRequest(); lastXHR = x;
      const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/buscar_destinos'
                + '?term=' + encodeURIComponent(q) + '&limit=15';
      x.open('GET', url, true);
      x.onload = ()=>{
        if (x.status !== 200){ hideList(); return; }
        try {
          const resp = JSON.parse(x.responseText||'{}');
          if (resp.ok !== true){ hideList(); return; }
          render(resp.items || []);
        } catch { hideList(); }
      };
      x.onerror = ()=> hideList();
      x.send();
    }

    inp.addEventListener('input', ()=>{
      const q = (inp.value||'').trim();
      hid.value = '';
      if (deb) clearTimeout(deb);
      deb = setTimeout(()=>{ if (q.length >= 2) fetchSug(q); else hideList(); }, 180);
    });

    inp.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideList(); });
    document.addEventListener('click', (e)=>{ if (!box.contains(e.target) && e.target !== inp) hideList(); });
  })();

  // ==============
  // AUTOCOMPLETE: TRANSPORTISTAS
  // ==============
  document.addEventListener('DOMContentLoaded', function(){
    const inp  = document.getElementById('transportistaNombreFerroOP');
    const hid  = document.getElementById('transportistaIdFerroOP');
    const box  = document.getElementById('sugTransportistasFerroOP');

    if (!inp || !hid || !box) return;

    function showList(){ box.style.display = 'block'; }
    function hideList(){ box.style.display = 'none'; box.innerHTML = ''; }

    function render(items){
      box.innerHTML = '';
      if (!items || items.length === 0){ hideList(); return; }
      for (const it of items){
        const a = document.createElement('a');
        a.href   = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = it.label + (it.tipo ? ` (${it.tipo})` : '');
        a.onclick = (e)=>{ e.preventDefault(); hid.value = it.id; inp.value = it.label; hideList(); };
        box.appendChild(a);
      }
      showList();
    }

    let lastXHR = null, deb = null;
    function fetchSug(q){
      if (lastXHR && lastXHR.abort) lastXHR.abort();
      const x = new XMLHttpRequest(); lastXHR = x;
      const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/buscar_transportistas'
                + `?term=${encodeURIComponent(q)}&limit=15&tipo=ferroviario`;
      x.open('GET', url, true);
      x.onload = ()=>{
        if (x.status !== 200) return hideList();
        try {
          const resp = JSON.parse(x.responseText||'{}');
          if (resp.ok !== true) return hideList();
          render(resp.items||[]);
        } catch { hideList(); }
      };
      x.onerror = ()=> hideList();
      x.send();
    }

    inp.addEventListener('input', ()=>{
      const q = inp.value.trim();
      hid.value = '';
      if (deb) clearTimeout(deb);
      deb = setTimeout(()=> { if (q.length >= 2) fetchSug(q); else hideList(); }, 180);
    });

    document.addEventListener('click', (e)=>{
      if (!box.contains(e.target) && e.target !== inp) hideList();
    });
  });

})();

 
// Pre-llenar número FO-## al abrir el modal
// ==============
(function(){
  "use strict";
  const modal = document.getElementById('modalFerroOP');
  const inpNumeroFO = document.getElementById('operacionNombreFerroOP');
//const form = document.getElementById('formFerroOP');
//if (form) form.dataset.mode = 'edit';

  if (!modal || !inpNumeroFO) return;
modal.addEventListener('shown.bs.modal', function(){
  const form = document.getElementById('formFerroOP');
  const isEdit = form && form.dataset.mode === 'edit';
  if (isEdit) {
    // En edición NO hacer preview; respetar el FO ya cargado en editarFerroOP()
    return;
  }

  // Solo en crear: pedir preview
  inpNumeroFO.value = '';
  const x = new XMLHttpRequest();
  const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/numero_fo_preview';
  x.open('GET', url, true);
  x.onload = function(){
    if (x.status !== 200) return;
    try {
      const res = JSON.parse(x.responseText || '{}');
      if (res.ok && res.numero) inpNumeroFO.value = res.numero; // p.ej. FO-12
    } catch(e){}
  };
  x.onerror = function(){};
  x.send();
});

  (function attachModalReset(){
    const modal = document.getElementById('modalFerroOP');
    if (!modal) return;
    modal.addEventListener('hidden.bs.modal', function(){
      if (typeof window.resetModalFerroOP === 'function') window.resetModalFerroOP();
    });
  })();

})();

 