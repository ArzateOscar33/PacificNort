 // ===============================
// Trazabilidad Ferro/Caja (Modal)
// ===============================
(function(){
  "use strict";

  // ====== Refs del Modal / Inputs ======
  const inpOpNombre   = document.getElementById("rutaOperacionFerroNombre");
  const hidOpId       = document.getElementById("rutaOperacionFerroId");
  const sugOpsBox     = document.getElementById("sugOperacionesFerroRuta");

  const inpFerroNom   = document.getElementById("rutaFerroNombre");
  const hidFerroId    = document.getElementById("rutaFerroId");
  const sugFerrosBox  = document.getElementById("sugFerrosRuta"); // reservado (si luego habilitas búsqueda de ferro)

  const chipsClientes = document.getElementById("rutaClientesChips");
// Inputs Origen / Destino
const inpOrigenNom = document.getElementById("tramoOrigenNombre");
const hidOrigenId  = document.getElementById("tramoOrigenId");
const sugOrigenes  = document.getElementById("sugOrigenesRuta");

const inpDestinoNom = document.getElementById("tramoDestinoNombre");
const hidDestinoId  = document.getElementById("tramoDestinoId");
const sugDestinos   = document.getElementById("sugDestinosRuta");

// Inputs Transportista
const inpTransNom = document.getElementById("tramoTransportistaNombre");
const hidTransId  = document.getElementById("tramoTransportistaId");
const sugTrans    = document.getElementById("sugTransportistasRuta");

  // ===== Helpers UI =====
  const ui = {
    empty(el){ if(!el) return; el.innerHTML = ""; el.style.display = "none"; },
    show(el){ if(!el) return; el.style.display = "block"; },
    hide(el){ if(!el) return; el.style.display = "none"; },
    badge(txt){
      const span = document.createElement("span");
      span.className = "badge bg-success text-white";
      span.textContent = txt;
      return span;
    },
    renderClientes(list){
      if(!chipsClientes) return;
      chipsClientes.innerHTML = "";
      if(!Array.isArray(list) || list.length === 0){
        const muted = document.createElement("span");
        muted.className = "text-muted small";
        muted.textContent = "Sin clientes detectados para esta operación.";
        chipsClientes.appendChild(muted);
        return;
      }
      list.forEach(c => {
        chipsClientes.appendChild(ui.badge(c.nombre || ("ID " + c.id_cliente)));
      });
    },
    setFerro(data){
      if(!data){ hidFerroId.value = ""; inpFerroNom.value = ""; return; }
      hidFerroId.value  = data.id_fisico != null ? String(data.id_fisico) : "";
      inpFerroNom.value = data.numero_ferro || "";
      inpFerroNom.disabled = false; // lo habilitamos por si quieres editar/confirmar
    },
    setOperacion(op){
      hidOpId.value       = op.id_operacion_ferro != null ? String(op.id_operacion_ferro) : "";
      inpOpNombre.value   = op.numero_operacion || "";
    },
    toastErr(msg){ if(window.Swal){ Swal.fire("Error", msg, "error"); } else { alert(msg); } },
    toastInfo(msg){ if(window.Swal){ Swal.fire("Info", msg, "info"); } else { alert(msg); } }
  };

  // ===== Debounce =====
  function debounce(fn, wait){
    let t; return function(){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, arguments), wait); };
  }

  // ====== AUTOSUGGEST: Operación Ferroviaria ======
  inpOpNombre.addEventListener("input", debounce(function(){
    const term = (this.value || "").trim();
    hidOpId.value = ""; // si empieza a escribir, limpiamos selección previa
    ui.empty(sugOpsBox);

    if(term.length === 0){ return; }

    const xhr = new XMLHttpRequest();
    xhr.open("GET", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/sugerencias_operaciones_ferro?q=" + encodeURIComponent(term) + "&limit=10", true);
    xhr.onreadystatechange = function(){
      if(xhr.readyState !== 4) return;
      if(xhr.status !== 200){
        console.error("sugerencias_operaciones_ferro:", xhr.responseText);
        return;
      }
      let res;
      try { res = JSON.parse(xhr.responseText || "{}"); } catch(e){ console.error("JSON inválido:", xhr.responseText); return; }
      if(!res.ok || !Array.isArray(res.items)){ return; }

      // Pintar lista
      ui.empty(sugOpsBox);
      if(res.items.length === 0){ return; }

      res.items.forEach(item => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        // Ej: FO-0007 — FX12345 — ACME
        const ferroTxt = item.numero_ferro ? ` — ${item.numero_ferro}` : ""; 
        btn.textContent = `${item.numero_operacion}${ferroTxt}`;
        btn.onclick = function(){
          // Fijar selección
          ui.empty(sugOpsBox);
          hidOpId.value     = String(item.id);
          inpOpNombre.value = item.numero_operacion || "";

          // Cargar datos completos para llenar Ferro y Clientes
          cargarDatosModal(Number(item.id));
        };
        sugOpsBox.appendChild(btn);
      });
      ui.show(sugOpsBox);
      feather.replace();
    };
    xhr.send();
  }, 250));

  // Cerrar sugerencias si clic fuera
  document.addEventListener("click", function(ev){
    if(sugOpsBox && !sugOpsBox.contains(ev.target) && !inpOpNombre.contains(ev.target)){
      ui.empty(sugOpsBox);
    }
  });

  // ====== Cargar paquete del modal (Ferro + Clientes) ======
  function cargarDatosModal(opFerroId){
    if(!opFerroId || opFerroId <= 0){
      ui.toastErr("Operación inválida.");
      return;
    }
    const xhr = new XMLHttpRequest();
    xhr.open("GET", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/datos_modal_trazabilidad?id=" + encodeURIComponent(String(opFerroId)), true);
    xhr.onreadystatechange = function(){
      if(xhr.readyState !== 4) return;
      if(xhr.status !== 200){
        console.error("datos_modal_trazabilidad:", xhr.responseText);
        ui.toastErr("No fue posible cargar la información de la operación.");
        return;
      }
      let res;
      try { res = JSON.parse(xhr.responseText || "{}"); } catch(e){ ui.toastErr("Respuesta inválida del servidor."); return; }
      if(!res.ok || !res.operacion){
        ui.toastErr(res.msg || "No se encontraron datos de la operación.");
        return;
      }

      // Operación (por si quieres volver a pintar el nombre normalizado)
      ui.setOperacion(res.operacion);

      // Ferro/Caja
      ui.setFerro(res.ferro || null);

      // Clientes chips
      ui.renderClientes(res.clientes || []);
      feather.replace();
    };
    xhr.send();
  }

 inpOrigenNom.addEventListener("input", debounce(function(){
  const term = (this.value || "").trim();
  hidOrigenId.value = "";
  ui.empty(sugOrigenes);
  if(term.length === 0) return;

  const xhr = new XMLHttpRequest();
  xhr.open("GET", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/sugerencias_lugares?q=" + encodeURIComponent(term) + "&limit=10", true);
  xhr.onreadystatechange = function(){
    if(xhr.readyState !== 4) return;
    if(xhr.status !== 200) return;
    let res;
    try{ res = JSON.parse(xhr.responseText||"{}"); }catch(e){ return; }
    if(!res.ok || !Array.isArray(res.items)) return;

    ui.empty(sugOrigenes);
    res.items.forEach(item=>{
      const btn = document.createElement("button");
      btn.type="button";
      btn.className="list-group-item list-group-item-action";
      btn.textContent = `${item.nombre} (${item.tipo})`;
      btn.onclick = function(){
        hidOrigenId.value = item.id;
        inpOrigenNom.value = item.nombre;
        ui.empty(sugOrigenes);
      };
      sugOrigenes.appendChild(btn);
    });
    ui.show(sugOrigenes);
  };
  xhr.send();
},250));
inpDestinoNom.addEventListener("input", debounce(function(){
  const term = (this.value || "").trim();
  hidDestinoId.value = "";
  ui.empty(sugDestinos);
  if(term.length === 0) return;

  const xhr = new XMLHttpRequest();
  xhr.open("GET", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/sugerencias_lugares?q=" + encodeURIComponent(term) + "&limit=10", true);
  xhr.onreadystatechange = function(){
    if(xhr.readyState !== 4) return;
    if(xhr.status !== 200) return;
    let res;
    try{ res = JSON.parse(xhr.responseText||"{}"); }catch(e){ return; }
    if(!res.ok || !Array.isArray(res.items)) return;

    ui.empty(sugDestinos);
    res.items.forEach(item=>{
      const btn = document.createElement("button");
      btn.type="button";
      btn.className="list-group-item list-group-item-action";
      btn.textContent = `${item.nombre} (${item.tipo})`;
      btn.onclick = function(){
        hidDestinoId.value = item.id;
        inpDestinoNom.value = item.nombre;
        ui.empty(sugDestinos);
      };
      sugDestinos.appendChild(btn);
    });
    ui.show(sugDestinos);
  };
  xhr.send();
},250));
inpTransNom.addEventListener("input", debounce(function(){
  const term = (this.value || "").trim();
  hidTransId.value = "";
  ui.empty(sugTrans);
  if(term.length === 0) return;

  const xhr = new XMLHttpRequest();
  xhr.open("GET", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/sugerencias_transportistas?q=" + encodeURIComponent(term) + "&tipo=ferroviario&limit=10", true);
  xhr.onreadystatechange = function(){
    if(xhr.readyState !== 4) return;
    if(xhr.status !== 200) return;
    let res;
    try{ res = JSON.parse(xhr.responseText||"{}"); }catch(e){ return; }
    if(!res.ok || !Array.isArray(res.items)) return;

    ui.empty(sugTrans);
    res.items.forEach(item=>{
      const btn = document.createElement("button");
      btn.type="button";
      btn.className="list-group-item list-group-item-action";
      btn.textContent = item.nombre+ "-"+ (item.tipo);
      btn.onclick = function(){
        hidTransId.value = item.id;
        inpTransNom.value = item.nombre;
        ui.empty(sugTrans);
      };
      sugTrans.appendChild(btn);
    });
    ui.show(sugTrans);
  };
  xhr.send();
},250));


})();
// ===============================================
// Catálogo Rutas Ferro/Caja — Listado & Paginación
// ===============================================
(function(){
  "use strict";

  // ----- Refs -----
  const tbody   = document.getElementById("tbodyRutasFerro");
  const emptyTr = document.getElementById("rutasEmptyRow");
  const meta    = document.getElementById("rutasMeta");
  const ulPag   = document.getElementById("rutasPaginacion");

  const inpQ    = document.getElementById("rutasBuscar");
  const inpIni  = document.getElementById("rutasFechaIni");
  const inpFin  = document.getElementById("rutasFechaFin");
  const selPer  = document.getElementById("rutasPerPage");

  const btnXls  = document.getElementById("rutasExcel");
  const btnPdf  = document.getElementById("rutasPdf");

  // ----- Estado -----
  let page    = 1;
  let perPage = Number(selPer?.value || 10);
  let total   = 0;
  let rows    = [];
  let inflight = null;
  let tmr = null;

  // ----- Utils -----
  const U = {
    money(n){ return Number(n || 0).toLocaleString('es-MX', { style:'currency', currency:'MXN' }); },
    fmt(iso){
      if(!iso) return '';
      const d = new Date(iso);
      if (isNaN(d.getTime())) return String(iso);
      return d.toLocaleString();
    },
    setMeta(){
      const from = total === 0 ? 0 : ((page-1)*perPage + 1);
      const to   = total === 0 ? 0 : Math.min(total, page*perPage);
      if (meta) meta.textContent = `Mostrando ${from}–${to} de ${total}`;
    },
    renderPag(){
      if (!ulPag) return;
      ulPag.innerHTML = '';
      const pages = Math.max(1, Math.ceil(total / perPage));
      const add = (p, label, disabled=false, active=false)=>{
        const li = document.createElement('li');
        li.className = `page-item ${disabled?'disabled':''} ${active?'active':''}`;
        li.innerHTML = `<a class="page-link" href="#" data-page="${p}">${label}</a>`;
        ulPag.appendChild(li);
      };
      add(Math.max(1, page-1), '«', page===1);
      for (let i=1;i<=pages;i++){
        if (i===1 || i===pages || Math.abs(i-page)<=2){
          add(i, i, false, i===page);
        } else if (Math.abs(i-page)===3) {
          const li = document.createElement('li');
          li.className = 'page-item disabled';
          li.innerHTML = `<span class="page-link">…</span>`;
          ulPag.appendChild(li);
        }
      }
      add(Math.min(pages, page+1), '»', page===pages);
    },
    renderRows(){
      if (!tbody) return;
      tbody.innerHTML = '';
      if (!Array.isArray(rows) || rows.length === 0){
        if (emptyTr){ emptyTr.style.display=''; tbody.appendChild(emptyTr); }
        U.setMeta(); U.renderPag(); feather?.replace(); return;
      }
      if (emptyTr){ emptyTr.style.display='none'; }

      rows.forEach(it=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <div class="fw-semibold">${it.operacion_numero || '-'}</div> 
          </td>
          <td>
            <div class="fw-semibold">${it.ferro_nombre || '-'}</div> 
          </td>
          <td>${it.cliente || '-'}</td>
          <td>${it.origen_inicial || '-'}</td>
          <td><span class="badge bg-info text-white">${it.destino_actual || '-'}</span></td>
          <td class="text-center"><span class="badge bg-secondary text-white">${it.tramos_count || 0}</span></td>
          <td class="text-end"><span class="badge bg-warning text-dark">${U.money(it.costo_acumulado || 0)}</span></td>
          <td>${U.fmt(it.updated_at)}</td>
          <td class="text-nowrap"> 
            <button class="btn btn-sm btn-outline-success me-1" data-action="editar" data-id="${it.ferro_ruta_id}">
              <i data-feather="edit-2"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" data-action="eliminar" data-id="${it.ferro_ruta_id}">
              <i data-feather="trash-2"></i>
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
      U.setMeta(); U.renderPag(); feather?.replace();
    },
    debounce(fn, ms=300){
      return function(){ clearTimeout(tmr); tmr = setTimeout(()=>fn.apply(this, arguments), ms); };
    }
  };

  // ----- Fetch -----
  function cargar(){
    // cancelar inflight
    try { if (inflight && inflight.readyState !== 4) inflight.abort(); } catch(_){}

    const q     = (inpQ?.value || '').trim();
    const desde = (inpIni?.value || '').trim();
    const hasta = (inpFin?.value || '').trim();

    const params = new URLSearchParams({
      q, desde, hasta,
      page: String(page),
      perPage: String(perPage)
    });

    inflight = new XMLHttpRequest();
    inflight.open('GET', BASE_URL + 'operaciones_maritimo_ferro_trazabilidad/rutas_list?' + params.toString(), true);

    // loading
    if (tbody){
      tbody.innerHTML = `
        <tr><td colspan="9" class="text-center text-muted py-4">
          Cargando…
        </td></tr>`;
    }

    inflight.onreadystatechange = function(){
      if (inflight.readyState !== 4) return;
      if (inflight.status !== 200){
        rows = []; total = 0;
        U.renderRows();
        console.error('rutas_list error:', inflight.responseText);
        return;
      }
      let res;
      try { res = JSON.parse(inflight.responseText || '{}'); } catch(e){ res = { ok:false, data:[] }; }

      if (!res.ok){ rows=[]; total=0; U.renderRows(); return; }
      rows  = Array.isArray(res.data) ? res.data : [];
      total = Number(res.total || 0);
      U.renderRows();
    };

    inflight.send();
  }

  // ----- Eventos -----
  inpQ?.addEventListener('input', U.debounce(()=>{ page=1; cargar(); }, 350));
  inpIni?.addEventListener('change', ()=>{ page=1; cargar(); });
  inpFin?.addEventListener('change', ()=>{ page=1; cargar(); });
  selPer?.addEventListener('change', ()=>{ perPage = Number(selPer.value||10); page=1; cargar(); });

  ulPag?.addEventListener('click', function(ev){
    const a = ev.target.closest('a[data-page]');
    if (!a) return;
    ev.preventDefault();
    const p = Number(a.getAttribute('data-page'));
    if (Number.isInteger(p) && p>0 && p !== page){ page = p; cargar(); }
  });

  // Export (si tus endpoints existen)
  btnXls?.addEventListener('click', function(){
    const qs = new URLSearchParams({
      q: inpQ?.value || '', desde: inpIni?.value || '', hasta: inpFin?.value || ''
    });
    window.open(BASE_URL + 'operaciones_maritimo_ferro_trazabilidad/rutas_export_excel?' + qs.toString(), '_blank');
  });
  btnPdf?.addEventListener('click', function(){
    const qs = new URLSearchParams({
      q: inpQ?.value || '', desde: inpIni?.value || '', hasta: inpFin?.value || ''
    });
    window.open(BASE_URL + 'operaciones_maritimo_ferro_trazabilidad/rutas_export_pdf?' + qs.toString(), '_blank');
  });

  // Hooks de acción de fila (placeholder)
tbody?.addEventListener('click', function(ev){
  const btn = ev.target.closest('button[data-action]');
  if (!btn) return;
  const action = btn.getAttribute('data-action');
  const id     = Number(btn.getAttribute('data-id') || 0);
  if (!id) return;

  if (action === 'ver'){
    console.log('ver ruta', id);
  } else if (action === 'editar'){
    // >>> Abrir modal de edición con esa ruta
    if (typeof window.editarRutaFerro === 'function') {
      window.editarRutaFerro(id);
    } else {
      console.error('window.editarRutaFerro no está disponible. ¿Cargaste el JS del modal?');
    }
  } else if (action === 'eliminar'){
    console.log('eliminar ruta', id);
  }
});


  // ----- Inicio -----
  cargar();
  // expón si quieres refrescar desde fuera
  window.cargarRutasFerroCatalogo = cargar;
})();
