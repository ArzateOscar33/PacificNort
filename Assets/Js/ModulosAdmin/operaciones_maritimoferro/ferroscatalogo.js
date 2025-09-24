 
(function(){
  "use strict";
// Refs usados en este archivo
const operacionIdFerroOP               = document.getElementById('operacionIdFerroOP');
const operacionNombreFerroOP           = document.getElementById('operacionNombreFerroOP');
const sugOperacionesFerroOP            = document.getElementById('sugOperacionesFerroOP');

const clienteIdFerroOP                 = document.getElementById('clienteIdFerroOP');
const clienteNombreFerroOP             = document.getElementById('clienteNombreFerroOP');

const contenedorMaritimoIdFerroOP      = document.getElementById('contenedorMaritimoIdFerroOP');
const contenedorMaritimoNombreFerroOP  = document.getElementById('contenedorMaritimoNombreFerroOP');
const sugMaritimosFerroOP              = document.getElementById('sugMaritimosFerroOP');

const bultosMaritimoFerroOP            = document.getElementById('bultosMaritimoFerroOP');
const bultosRestantesFerroOP           = document.getElementById('bultosRestantesFerroOP');
const bultosAsignadosFerroOP           = document.getElementById('bultosAsignadosFerroOP');
const badgeSaldoFerroOP                = document.getElementById('badgeSaldoFerroOP');

  // ==== helpers ====
  const $ = (sel)=>document.querySelector(sel);
  const toInt = (v)=> Number.isFinite(Number(v)) ? Number(v) : 0;
  const fetchJSON = (url)=> new Promise((res, rej)=>{
    const x = new XMLHttpRequest();
    x.open('GET', url, true);
    x.onload = ()=> {
      try { res(JSON.parse(x.responseText||'{}')); } catch(e){ rej(e); }
    };
    x.onerror = rej;
    x.send();
  });
function setSaldoBadge(val){
  const v = Number(val || 0);
  if (!badgeSaldoFerroOP) return;
  badgeSaldoFerroOP.textContent = `Saldo: ${v}`;
  badgeSaldoFerroOP.className   = 'badge ' + (v < 0 ? 'bg-danger text-white' : 'bg-success text-white');
}

  // Crea / oculta lista de sugerencias
  function showList(box){ if (box){ box.style.display = 'block'; } }
  function hideList(box){ if (box){ box.style.display = 'none'; box.innerHTML=''; } }

  function renderSuggestions(box, items, onPick){
    if (!box) return;
    box.innerHTML = '';
    if (!items || items.length === 0){ hideList(box); return; }

    items.forEach(it=>{
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
      btn.innerHTML = `
        <span>${it.label}</span>
        ${it.total_bultos_maritimos !== undefined ? `<small class="text-muted ms-2">Bultos: ${it.total_bultos_maritimos}</small>`:''}
      `;
      const pick = (ev)=>{ ev.preventDefault(); onPick(it); };
      btn.addEventListener('pointerdown', pick);
      btn.addEventListener('click', pick);
      box.appendChild(btn);
    });
    showList(box);
  }

  // ==== Autocomplete de OPERACIÓN ====
  let opXHR = null;
  let opDebounce = null;

  operacionNombreFerroOP?.addEventListener('input', function(e){
    // limpiar dependencias al teclear
    operacionIdFerroOP.value = '';
    clienteIdFerroOP.value = '';
    clienteNombreFerroOP.value = '';
    contenedorMaritimoIdFerroOP.value = '';
    contenedorMaritimoNombreFerroOP.value = '';
    bultosMaritimoFerroOP.value = '';
    bultosRestantesFerroOP.value = '';
    badgeSaldoFerroOP.textContent = 'Saldo: 0';
    badgeSaldoFerroOP.className = 'badge bg-secondary';

    const q = (this.value||'').trim();
    if (q.length < 2){ hideList(sugOperacionesFerroOP); return; }

    // debounce
    clearTimeout(opDebounce);
    opDebounce = setTimeout(()=>{
      if (opXHR && opXHR.readyState !== 4) opXHR.abort();

      const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/sugerencias_operaciones?q=' + encodeURIComponent(q) + '&limit=10';
      opXHR = new XMLHttpRequest();
      opXHR.open('GET', url, true);
      opXHR.onload = function(){
        let res = {};
        try { res = JSON.parse(opXHR.responseText||'{}'); } catch { res = {}; }
        const items = Array.isArray(res.data) ? res.data : [];
        renderSuggestions(sugOperacionesFerroOP, items, onPickOperacionFerroOP);
      };
      opXHR.onerror = function(){ hideList(sugOperacionesFerroOP); };
      opXHR.send();
    }, 250);
  });

  operacionNombreFerroOP?.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape') hideList(sugOperacionesFerroOP);
  });
  operacionNombreFerroOP?.addEventListener('blur', ()=> setTimeout(()=> hideList(sugOperacionesFerroOP), 150));

  // Al elegir una operación:
  async function onPickOperacionFerroOP(item){
    // 1) set operación + cliente
    operacionIdFerroOP.value   = item.id;
    operacionNombreFerroOP.value = item.label; // o item.numero_operacion si lo quisieras “puro”
    clienteIdFerroOP.value     = item.cliente_id || 0;
    clienteNombreFerroOP.value = item.cliente || '';
    hideList(sugOperacionesFerroOP);

// 2) limpiar inputs dependientes
contenedorMaritimoNombreFerroOP.value = '';
contenedorMaritimoIdFerroOP.value     = '';
bultosMaritimoFerroOP.value  = '';
bultosRestantesFerroOP.value = '';
badgeSaldoFerroOP.textContent = 'Saldo: 0';
badgeSaldoFerroOP.className   = 'badge bg-secondary text-white';

// 3) pedir TODOS los MG con sus saldos de esta operación
try {
  const res = await fetchJSON(
    BASE_URL + 'operaciones_maritimo_ferro_contenedores/saldos_por_operacion?operacion_id=' + encodeURIComponent(item.id)
  );

  // Esperamos: {ok:true, operacion_id, items:[{id_cmo, contenedor_maritimo_id, numero_contenedor, bultos_totales, bultos_asignados, bultos_restantes}, ...]}
  const items = Array.isArray(res.items) ? res.items : [];

  // Cachea estos MG para el autocomplete del campo "Contenedor Marítimo"
  renderMaritimosSugeridos(
    items.map(r => ({
      cmo_id: r.id_cmo,
      id_contenedor_maritimo: r.contenedor_maritimo_id,
      numero_contenedor: r.numero_contenedor,
      bultos: Number(r.bultos_totales || 0),          // por compatibilidad con tu render
      bultos_totales: Number(r.bultos_totales || 0),  // explícito
      bultos_asignados: Number(r.bultos_asignados || 0),
      bultos_restantes: Number(r.bultos_restantes || 0)
    }))
  );
} catch(e){
  // si falla: deja el cache vacío
  renderMaritimosSugeridos([]);
}
  
  
  }

  // ==== Autocomplete de MARÍTIMOS (usando los de la operación seleccionada) ====
  let cacheMaritimosDeOp = [];
  function renderMaritimosSugeridos(maritimos){
    cacheMaritimosDeOp = maritimos || [];
    sugMaritimosFerroOP.innerHTML = '';
    hideList(sugMaritimosFerroOP);
  }

  contenedorMaritimoNombreFerroOP?.addEventListener('input', function(){
    const q = (this.value||'').trim().toLowerCase();
    if (!cacheMaritimosDeOp.length){ hideList(sugMaritimosFerroOP); return; }
    if (q.length < 1){ hideList(sugMaritimosFerroOP); return; }

    const filtered = cacheMaritimosDeOp
      .filter(m => (m.numero_contenedor||'').toLowerCase().includes(q))
      .slice(0, 10)
      .map(m => ({
        id: m.id_contenedor_maritimo,
        label: `${m.numero_contenedor} — bultos: ${m.bultos}`,
        raw: m
      }));

    renderSuggestions(sugMaritimosFerroOP, filtered, (it)=> { 
      contenedorMaritimoIdFerroOP.value = it.raw.id_contenedor_maritimo;
      contenedorMaritimoNombreFerroOP.value = it.raw.numero_contenedor;
  // Pinta valores que ya vienen del endpoint saldos_por_operacion
  bultosMaritimoFerroOP.value  = String(it.raw.bultos_totales ?? it.raw.bultos ?? 0);
  bultosRestantesFerroOP.value = String(it.raw.bultos_restantes ?? 0);
  setSaldoBadge(it.raw.bultos_restantes ?? 0);
 

      hideList(sugMaritimosFerroOP);
    });
  });

  contenedorMaritimoNombreFerroOP?.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape') hideList(sugMaritimosFerroOP);
  });
  contenedorMaritimoNombreFerroOP?.addEventListener('blur', ()=> setTimeout(()=> hideList(sugMaritimosFerroOP), 150));

  // ==== Validación visual de saldo cuando el usuario escribe bultos del ferro ====
  bultosAsignadosFerroOP?.addEventListener('input', function(){
    const restBase = toInt(bultosRestantesFerroOP.value || 0);
    const asig     = toInt(this.value || 0);
    const saldo    = restBase - asig;
    badgeSaldoFerroOP.textContent = 'Saldo: ' + saldo;
    badgeSaldoFerroOP.className   = 'badge ' + (saldo < 0 ? 'bg-danger' : 'bg-success');
  });

})(); 
// ==== Autocomplete de FERRO/CAJA ====
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
      a.textContent = it.label; // número_ferro
      a.onclick = (e)=>{
        e.preventDefault();
        hid.value = it.id;
        inp.value = it.label;
        hideList();
      };
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
// ==== Autocomplete de DESTINOS (ciudades) ====
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
      a.textContent = it.label; // nombre_ciudad
      a.onclick = (e)=>{
        e.preventDefault();
        hid.value = it.id;
        inp.value = it.label;
        hideList();
      };
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
  document.addEventListener('click', (e)=>{
    if (!box.contains(e.target) && e.target !== inp) hideList();
  });
})();

document.addEventListener('DOMContentLoaded', function(){
  const inp  = document.getElementById('transportistaNombreFerroOP');
  const hid  = document.getElementById('transportistaIdFerroOP');
  const box  = document.getElementById('sugTransportistasFerroOP');

  if (!inp || !hid || !box) {
    console.error('Inputs de transportista no encontrados en el DOM.');
    return;
  }

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
      a.onclick = (e)=>{
        e.preventDefault();
        hid.value = it.id;
        inp.value = it.label;
        hideList();
      };
      box.appendChild(a);
    }
    showList();
  }

  let lastXHR = null, deb = null;

  function fetchSug(q){
    if (lastXHR && lastXHR.abort) lastXHR.abort();

    const x = new XMLHttpRequest();
    lastXHR = x;
 
    const url = BASE_URL + 'operaciones_maritimo_ferro_contenedores/buscar_transportistas'
          + `?term=${encodeURIComponent(q)}&limit=15&tipo=ferroviario`;


    x.open('GET', url, true);
    x.onload = ()=>{
      if (x.status !== 200) { console.error('HTTP', x.status, x.responseText); return hideList(); }
      try {
        const resp = JSON.parse(x.responseText||'{}');
        if (resp.ok !== true) { console.error('Resp NOK', resp); return hideList(); }
        render(resp.items||[]);
      } catch(e){
        console.error('JSON error', e, x.responseText);
        hideList();
      }
    };
    x.onerror = ()=> { console.error('XHR error'); hideList(); };
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
