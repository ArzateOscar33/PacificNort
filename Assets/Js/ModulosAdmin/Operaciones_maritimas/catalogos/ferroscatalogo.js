 
(function(){
  "use strict";

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

      const url = BASE_URL + 'operaciones_terrestres/sugerencias_operaciones?q=' + encodeURIComponent(q) + '&limit=10';
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

    // 2) poblar sugerencias de MARÍTIMOS de esta operación
    const maritimos = Array.isArray(item.maritimos) ? item.maritimos : [];
    // Si quieres que el input de marítimo también autocomplete (con los de esa op):
    contenedorMaritimoNombreFerroOP.value = '';
    contenedorMaritimoIdFerroOP.value     = '';
    renderMaritimosSugeridos(maritimos);

    // 3) set bultos del marítimo (total de todos) y calcular restantes
    const totalMaritimo = toInt(item.total_bultos_maritimos || 0);
    bultosMaritimoFerroOP.value = String(totalMaritimo);

    // 4) pedir suma de bultos ya asignados a ferros en esta operación
    let asignados = 0;
    try {
      const res = await fetchJSON(BASE_URL + 'operaciones_terrestres/suma_bultos_operacion?operacion_id=' + encodeURIComponent(item.id));
      if (res && typeof res.total_asignados !== 'undefined') asignados = toInt(res.total_asignados);
    } catch(_){ /* ignora, deja 0 */ }

    const restantes = totalMaritimo - asignados;
    bultosRestantesFerroOP.value = String(restantes);
    // pinta badge
    badgeSaldoFerroOP.textContent = 'Saldo: ' + restantes;
    badgeSaldoFerroOP.className   = 'badge ' + (restantes < 0 ? 'bg-danger' : 'bg-success') + ' text-white';
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

      // Si quieres recalcular con el marítimo elegido (en lugar del total):
      const bMar = toInt(it.raw.bultos);
      bultosMaritimoFerroOP.value = String(bMar);

      // recalcular restantes usando el total asignado de la operación
      // (si quisieras solo lo asignado a ese marítimo, necesitarías otro endpoint)
      const totalAsign = toInt(bultosMaritimoFerroOP.value) - toInt(bultosRestantesFerroOP.value);
      const rest = bMar - totalAsign;
      bultosRestantesFerroOP.value = String(rest);
      badgeSaldoFerroOP.textContent = 'Saldo: ' + rest;
      badgeSaldoFerroOP.className   = 'badge ' + (rest < 0 ? 'bg-danger' : 'bg-success');

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
