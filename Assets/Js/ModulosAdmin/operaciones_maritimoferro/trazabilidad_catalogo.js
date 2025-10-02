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
      btn.textContent = item.nombre;
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
