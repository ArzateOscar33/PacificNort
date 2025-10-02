// ================================================
// Alta/Edición - Trazabilidad Ferro/Caja (Rutas)
// ================================================
(function(){
  "use strict";

  // ====== Refs (con sufijo trazabilidad) ======
  const hidOpIdTrazabilidad     = document.getElementById("rutaOperacionFerroId");
  const hidFerroIdTrazabilidad  = document.getElementById("rutaFerroId");
  const btnGuardarTrazabilidad  = document.getElementById("btnGuardarRutas");
  const rutasPayloadTrazabilidad= document.getElementById("rutasPayload"); // ya existe en vista (hidden)
  // Comentario opcional de la ruta (si luego agregas un input/textarea):
  const inpComentarioRuta       = document.getElementById("rutaComentario") || null;

  // Estado local
  let rutaIdTrazabilidad = null; // se llenará al crear la ruta_ferro

  // Helpers mínimos
  const uiTraz = {
    err(msg){ if (window.Swal) Swal.fire("Error", msg, "error"); else alert("Error: " + msg); },
    ok(msg){ if (window.Swal) Swal.fire("Éxito", msg, "success"); else alert(msg); },
    info(msg){ if (window.Swal) Swal.fire("Info", msg, "info"); else alert(msg); },
    disable(el, v=true){ if(el) el.disabled = !!v; },
    // Validaciones base antes de crear ruta
    validarBase(){
      const opId   = Number(hidOpIdTrazabilidad?.value || 0);
      const ferroId= Number(hidFerroIdTrazabilidad?.value || 0);
      if (!opId)   { this.err("Selecciona una Operación Ferroviaria."); return false; }
      if (!ferroId){ this.err("Selecciona el Ferro/Caja vinculado."); return false; }
      return { opId, ferroId };
    },
  };

  /**
   * Crea encabezado en rutas_ferro
   * POST -> operaciones_maritimo_ferro_trazabilidad/crear_ruta_ferro
   */
  function crearRutaFerroTrazabilidad(opId, ferroId, comentario){
    return new Promise((resolve)=>{
      const fd = new FormData();
      fd.append("operacion_ferro_id", String(opId));
      fd.append("contenedor_fisico_id", String(ferroId));
      if (comentario && comentario.trim() !== "") {
        // el endpoint acepta 'comentario' o 'comentario_ruta'
        fd.append("comentario", comentario.trim());
      }

      const xhr = new XMLHttpRequest();
      xhr.open("POST", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/crear_ruta_ferro", true);
      xhr.onreadystatechange = function(){
        if (xhr.readyState !== 4) return;
        try{
          const res = JSON.parse(xhr.responseText || "{}");
          resolve(res);
        }catch(e){
          resolve({ ok:false, msg:"Respuesta inválida del servidor." });
        }
      };
      xhr.send(fd);
    });
  }

 
// ===== FASE 2: Guardar tramos + costos por tramo =====
async function guardarTramosTrazabilidad(rutaId, operacionFerroId){
  const payloadEl = document.getElementById("rutasPayload");
  let raw = payloadEl?.value || "[]";
  let tramos;
  try { tramos = JSON.parse(raw); } catch(_){ tramos = []; }

  if (!Array.isArray(tramos) || tramos.length === 0) {
    if (window.Swal) Swal.fire("Atención","No hay tramos en el carrito.","warning");
    else alert("No hay tramos en el carrito.");
    return;
  }

  // Validación mínima (solo números válidos; monto puede ser 0.00)
  for (let i=0; i<tramos.length; i++){
    const t = tramos[i];
    if (!(Number(t.origen_id) > 0 && Number(t.destino_id) > 0 && Number(t.transportista_id) > 0)){
      if (window.Swal) Swal.fire("Error",`Tramo #${i+1} inválido.`, "error"); else alert(`Tramo #${i+1} inválido.`);
      return;
    }
  }

  return new Promise((resolve)=>{
    const fd = new FormData();
    fd.append("ruta_id", String(rutaId));
    fd.append("operacion_ferro_id", String(operacionFerroId));
    fd.append("rutasPayload", JSON.stringify(tramos)); // tu carrito

    const xhr = new XMLHttpRequest();
    xhr.open("POST", BASE_URL + "operaciones_maritimo_ferro_trazabilidad/guardar_tramos", true);
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) return;
      try {
        const res = JSON.parse(xhr.responseText || "{}");
        resolve(res);
      } catch(e){ resolve({ ok:false, msg:"Respuesta inválida del servidor." }); }
    };
    xhr.send(fd);
  });
}

// === Integra la Fase 2 después de crear la ruta ===
btnGuardarTrazabilidad.addEventListener("click", async function(){
  const v = uiTraz.validarBase();
  if (!v) return;

  uiTraz.disable(btnGuardarTrazabilidad, true);

  // 1) Crear ruta (si aún no la tienes creada)
  const comentario = inpComentarioRuta ? String(inpComentarioRuta.value || "") : "";
  let rutaId = rutaIdTrazabilidad;
  if (!rutaId) {
    const resRuta = await crearRutaFerroTrazabilidad(v.opId, v.ferroId, comentario);
    if (!resRuta || !resRuta.ok) {
      uiTraz.disable(btnGuardarTrazabilidad, false);
      return uiTraz.err(resRuta?.msg || "No fue posible crear la ruta.");
    }
    rutaId = rutaIdTrazabilidad = Number(resRuta.ruta_id || 0);
  }

  // 2) Guardar tramos + costos por tramo
  const resTr = await guardarTramosTrazabilidad(rutaId, v.opId);
  uiTraz.disable(btnGuardarTrazabilidad, false);

  if (!resTr || !resTr.ok) {
    return uiTraz.err(resTr?.msg || "No fue posible guardar los tramos.");
  }

  uiTraz.ok("Rutas y costos guardados correctamente.");
  // Aquí puedes cerrar modal, limpiar carrito, refrescar resumen, etc.
});

// ===============================
// Carrito de TRAMOS (Trazabilidad)
// ===============================

// ---- Refs UI (ids del modal) ----
const inpOrigenNomTraz   = document.getElementById("tramoOrigenNombre");
const hidOrigenIdTraz    = document.getElementById("tramoOrigenId");
const inpDestinoNomTraz  = document.getElementById("tramoDestinoNombre");
const hidDestinoIdTraz   = document.getElementById("tramoDestinoId");
const inpTransNomTraz    = document.getElementById("tramoTransportistaNombre");
const hidTransIdTraz     = document.getElementById("tramoTransportistaId");
const inpMontoTraz       = document.getElementById("tramoMonto");
const inpComentTraz      = document.getElementById("tramoComentario");

const btnAgregarTramoTraz = document.getElementById("btnAgregarTramo");
const tbodyTramosTraz     = document.getElementById("tbodyTramos");
const noTramosRowTraz     = document.getElementById("noTramosRow");
const totalMontoBadgeTraz = document.getElementById("totalMonto");

// Hidden que viaja al back
const payloadHiddenTraz   = document.getElementById("rutasPayload");

// ---- Estado del carrito ----
// Estructura de cada item:
// { id_tramo?:number, orden:number, origen_id:number, origen_nombre:string,
//   destino_id:number, destino_nombre:string, transportista_id:number,
//   transportista_nombre:string, monto:number, comentario?:string }
let tramosTraz = [];

// ---- Helpers ----
const utilTraz = {
  money(n){ return Number(n || 0).toFixed(2); },      // permite 0.00
  toNumber(n){ const v = Number(n); return Number.isFinite(v) ? v : 0; },
  toastErr(msg){ if (window.Swal) Swal.fire("Error", msg, "error"); else alert(msg); },
  toastInfo(msg){ if (window.Swal) Swal.fire("Info", msg, "info"); else alert(msg); },

  // Sincroniza el hidden con el carrito (JSON)
  syncPayload(){
    if (!payloadHiddenTraz) return;
    payloadHiddenTraz.value = JSON.stringify(tramosTraz.map((t, i) => ({
      // si en edición traes id_tramo, CONSÉRVALO
      ...(t.id_tramo ? { id_tramo: t.id_tramo } : {}),
      orden: (i+1), // orden real = posición en carrito (no hay reordenamiento manual)
      origen_id: t.origen_id,
      destino_id: t.destino_id,
      transportista_id: t.transportista_id,
      monto: utilTraz.toNumber(t.monto),
      comentario: (t.comentario ?? null)
    })));
  },

  // Render de tabla + cálculo total
  render(){
    // limpiar
    tbodyTramosTraz.innerHTML = "";
    if (!tramosTraz.length){
      noTramosRowTraz.style.display = "";
      tbodyTramosTraz.appendChild(noTramosRowTraz);
      totalMontoBadgeTraz.textContent = utilTraz.money(0);
      return;
    }
    noTramosRowTraz.style.display = "none";

    let total = 0;
    tramosTraz.forEach((t, idx) => {
      total += utilTraz.toNumber(t.monto);
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${idx+1}</td>
        <td>${t.origen_nombre || "-"}</td>
        <td>${t.destino_nombre || "-"}</td>
        <td>${t.transportista_nombre || "-"}</td>
        <td class="text-end">${utilTraz.money(t.monto)}</td>
        <td>${t.comentario ? t.comentario : ""}</td>
        <td>
          <button type="button" class="btn btn-sm btn-outline-danger" data-idx="${idx}" title="Eliminar">
            <i data-feather="trash-2"></i>
          </button>
        </td>
      `;
      tbodyTramosTraz.appendChild(tr);
    });

    totalMontoBadgeTraz.textContent = utilTraz.money(total);
    feather?.replace();

    // delegar eliminar
    tbodyTramosTraz.querySelectorAll("button[data-idx]").forEach(btn=>{
      btn.addEventListener("click", () => {
        const i = Number(btn.getAttribute("data-idx"));
        if (!Number.isInteger(i) || i < 0 || i >= tramosTraz.length) return;
        tramosTraz.splice(i, 1);
        utilTraz.render();
        utilTraz.syncPayload();
        // encadenar origen del siguiente tramo
        setNextOriginFromLastTraz();
      });
    });
  }
};

// Encadena: ORIGEN del siguiente tramo = DESTINO del último
function setNextOriginFromLastTraz(){
  if (!inpOrigenNomTraz || !hidOrigenIdTraz) return;
  if (tramosTraz.length === 0) return; // si quieres, aquí podrías setear el origen base de la operación
  const last = tramosTraz[tramosTraz.length - 1];
  hidOrigenIdTraz.value  = String(last.destino_id || "");
  inpOrigenNomTraz.value = last.destino_nombre || "";
}

// Limpia inputs de línea (manteniendo el ORIGEN si se encadena)
function resetLineaTraz(keepOrigin = true){
  if (!keepOrigin){
    hidOrigenIdTraz.value  = "";
    inpOrigenNomTraz.value = "";
  }
  hidDestinoIdTraz.value     = "";
  inpDestinoNomTraz.value    = "";
  hidTransIdTraz.value       = "";
  inpTransNomTraz.value      = "";
  inpMontoTraz.value         = "";
  inpComentTraz.value        = "";
}

// ---- Agregar tramo (botón +) ----
btnAgregarTramoTraz?.addEventListener("click", function(){
  // Validaciones mínimas
  const origenId  = Number(hidOrigenIdTraz?.value || 0);
  const destinoId = Number(hidDestinoIdTraz?.value || 0);
  const transId   = Number(hidTransIdTraz?.value || 0);
  const monto     = utilTraz.toNumber(inpMontoTraz?.value || 0);
  const origenNom = (inpOrigenNomTraz?.value || "").trim();
  const destinoNom= (inpDestinoNomTraz?.value || "").trim();
  const transNom  = (inpTransNomTraz?.value || "").trim();
  const coment    = (inpComentTraz?.value || "").trim() || null;

  if (!origenId || !origenNom) return utilTraz.toastErr("Define el Origen (elige de las sugerencias).");
  if (!destinoId || !destinoNom) return utilTraz.toastErr("Define el Destino (elige de las sugerencias).");
  if (!transId || !transNom)     return utilTraz.toastErr("Define el Transportista (elige de las sugerencias).");
  // monto puede ser 0.00, solo verifica NaN
  if (!Number.isFinite(monto) || monto < 0) return utilTraz.toastErr("Monto inválido.");

  // Construir item (orden se determina al render -> idx+1)
  tramosTraz.push({
    origen_id: origenId,
    origen_nombre: origenNom,
    destino_id: destinoId,
    destino_nombre: destinoNom,
    transportista_id: transId,
    transportista_nombre: transNom,
    monto: monto,
    comentario: coment
  });

  utilTraz.render();
  utilTraz.syncPayload();

  // Preparar siguiente: origen = destino recién agregado
  resetLineaTraz(true);
  setNextOriginFromLastTraz();
  inpDestinoNomTraz?.focus();
});

// ---- Al abrir el modal, si ya hay un tramo, encadenar origen ----
document.getElementById("modalRutasFerro")?.addEventListener("shown.bs.modal", function(){
  setNextOriginFromLastTraz();
});


})();
