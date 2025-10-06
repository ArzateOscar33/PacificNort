// ================================================
// Alta/Edición - Trazabilidad Ferro/Caja (Rutas)
// ================================================
(function () {
  "use strict";

  // ====== Refs (con sufijo trazabilidad) ======
  const hidOpIdTrazabilidad = document.getElementById("rutaOperacionFerroId");
  const hidFerroIdTrazabilidad = document.getElementById("rutaFerroId");
  const btnGuardarTrazabilidad = document.getElementById("btnGuardarRutas");
  const rutasPayloadTrazabilidad = document.getElementById("rutasPayload"); // hidden existente
  // Comentario opcional de la ruta (si luego agregas un input/textarea):
  const inpComentarioRuta = document.getElementById("rutaComentario") || null;

  // ---- MODO (debe estar definido antes de usar en helpers) ----
  let isEditTraz = false; // false = alta, true = edición (append-only)
  let rutaIdTrazabilidad = null; // se llenará al crear/cargar la ruta_ferro

  // Helpers mínimos
  const uiTraz = {
    err(msg) {
      if (window.Swal) Swal.fire("Error", msg, "error");
      else alert("Error: " + msg);
    },
    ok(msg) {
      if (window.Swal) Swal.fire("Éxito", msg, "success");
      else alert(msg);
    },
    info(msg) {
      if (window.Swal) Swal.fire("Info", msg, "info");
      else alert(msg);
    },
    disable(el, v = true) {
      if (el) el.disabled = !!v;
    },
    // Validaciones base antes de crear/guardar ruta
    validarBase() {
      const opId = Number(hidOpIdTrazabilidad?.value || 0);
      const ferroId = Number(hidFerroIdTrazabilidad?.value || 0);
      if (!opId) {
        this.err("Selecciona una Operación Ferroviaria.");
        return false;
      }
      if (!ferroId) {
        this.err("Selecciona el Ferro/Caja vinculado.");
        return false;
      }
      return { opId, ferroId };
    },
  };
function setOrigenReadonlyByState(){
  if (!inpOrigenNomTraz) return;
  if (tramosTraz.length > 0) {
    inpOrigenNomTraz.setAttribute("readonly", "readonly");
  } else {
    inpOrigenNomTraz.removeAttribute("readonly");
  }
}

  /**
   * Crea encabezado en rutas_ferro
   * POST -> operaciones_maritimo_ferro_trazabilidad/crear_ruta_ferro
   */
  function crearRutaFerroTrazabilidad(opId, ferroId, comentario) {
    return new Promise((resolve) => {
      const fd = new FormData();
      fd.append("operacion_ferro_id", String(opId));
      fd.append("contenedor_fisico_id", String(ferroId));
      if (comentario && comentario.trim() !== "") {
        fd.append("comentario", comentario.trim());
      }

      const xhr = new XMLHttpRequest();
      xhr.open(
        "POST",
        BASE_URL + "operaciones_maritimo_ferro_trazabilidad/crear_ruta_ferro",
        true
      );
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
          const res = JSON.parse(xhr.responseText || "{}");
          resolve(res);
        } catch (e) {
          resolve({ ok: false, msg: "Respuesta inválida del servidor." });
        }
      };
      xhr.send(fd);
    });
  }


  // ===== FASE 2: Guardar tramos + costos por tramo =====
async function guardarTramosTrazabilidad(rutaId, operacionFerroId){
  // 1) Construir payload desde el estado 'tramosTraz'
  //    (incluir EXISTENTES + NUEVOS; así no se borran)
  if (!Array.isArray(tramosTraz) || tramosTraz.length === 0){
    if (window.Swal) Swal.fire("Atención","No hay tramos en el carrito.","warning");
    else alert("No hay tramos en el carrito.");
    return;
  }

  // separar por estado
  const existentes = tramosTraz.filter(t => Number(t.id_tramo) > 0);
  const nuevos     = tramosTraz.filter(t => !t.id_tramo);

  // base de orden = max orden de existentes (o 0 si no hay)
  let baseOrden = existentes.reduce((mx, t) => {
    const o = Number(t.orden || 0);
    return Number.isFinite(o) && o > mx ? o : mx;
  }, 0);

  // payload final
  const payload = [
    // conservar existentes tal cual (con id_tramo y su orden)
    ...existentes.map((t) => ({
      id_tramo: Number(t.id_tramo),
      orden: Number(t.orden || 0) || 0, // usa el orden que vino del servidor
      origen_id: Number(t.origen_id),
      destino_id: Number(t.destino_id),
      transportista_id: Number(t.transportista_id),
      monto: Number(t.monto || 0),
      comentario: t.comentario ?? null
    })),
    // asignar orden consecutivo a los nuevos
    ...nuevos.map((t) => ({
      orden: ++baseOrden,
      origen_id: Number(t.origen_id),
      destino_id: Number(t.destino_id),
      transportista_id: Number(t.transportista_id),
      monto: Number(t.monto || 0),
      comentario: t.comentario ?? null
    })),
  ];

  // 2) Validaciones mínimas (IDs válidos; monto puede ser 0.00)
  for (let i = 0; i < payload.length; i++) {
    const t = payload[i];
    if (!(t.origen_id > 0 && t.destino_id > 0 && t.transportista_id > 0)) {
      if (window.Swal) Swal.fire("Error", `Tramo #${i + 1} inválido.`, "error");
      else alert(`Tramo #${i + 1} inválido.`);
      return;
    }
  }

  // 3) Usar SIEMPRE el endpoint existente diferencial (no el append)
  const url = BASE_URL + "operaciones_maritimo_ferro_trazabilidad/guardar_tramos";

  // 4) Enviar
  return new Promise((resolve) => {
    const fd = new FormData();
    fd.append("ruta_id", String(rutaId));
    fd.append("operacion_ferro_id", String(operacionFerroId));
    fd.append("rutasPayload", JSON.stringify(payload));

    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      try {
        resolve(JSON.parse(xhr.responseText || "{}"));
      } catch (e) {
        resolve({ ok: false, msg: "Respuesta inválida del servidor." });
      }
    };
    xhr.send(fd);
  });
}

  // === Click en Guardar (encabezado + tramos) ===
  btnGuardarTrazabilidad?.addEventListener("click", async function () {
    const v = uiTraz.validarBase();
    if (!v) return;

    uiTraz.disable(btnGuardarTrazabilidad, true);

    // 1) Crear ruta (solo en alta, o si aún no hay id en estado)
    const comentario = inpComentarioRuta
      ? String(inpComentarioRuta.value || "")
      : "";
    let rutaId = rutaIdTrazabilidad;
    if (!rutaId) {
      const resRuta = await crearRutaFerroTrazabilidad(
        v.opId,
        v.ferroId,
        comentario
      );
      if (!resRuta || !resRuta.ok) {
        uiTraz.disable(btnGuardarTrazabilidad, false);
        return uiTraz.err(resRuta?.msg || "No fue posible crear la ruta.");
      }
      rutaId = rutaIdTrazabilidad = Number(resRuta.ruta_id || 0);
    }

    // 2) Guardar tramos (+ costos por tramo)
    const resTr = await guardarTramosTrazabilidad(rutaId, v.opId);
    uiTraz.disable(btnGuardarTrazabilidad, false);

    if (!resTr || !resTr.ok) {
      return uiTraz.err(resTr?.msg || "No fue posible guardar los tramos.");
    }

    // Después de guardar OK
    uiTraz.ok("Rutas y costos guardados correctamente.");

    // Refrescar catálogo si existe y cerrar modal
    if (typeof window.cargarRutasFerroCatalogo === "function") {
      window.cargarRutasFerroCatalogo();
    }
// Limpiar y cerrar modal
const modalEl = document.getElementById("modalRutasFerro");
if (modalEl && window.bootstrap?.Modal) {
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
  modalInstance.hide();
  // El evento 'hidden.bs.modal' se encargará de limpiar
}
    
  });

  // ===============================
  // Carrito de TRAMOS (Trazabilidad)
  // ===============================

  // ---- Refs UI (ids del modal) ----
  const inpOrigenNomTraz = document.getElementById("tramoOrigenNombre");
  const hidOrigenIdTraz = document.getElementById("tramoOrigenId");
  const inpDestinoNomTraz = document.getElementById("tramoDestinoNombre");
  const hidDestinoIdTraz = document.getElementById("tramoDestinoId");
  const inpTransNomTraz = document.getElementById("tramoTransportistaNombre");
  const hidTransIdTraz = document.getElementById("tramoTransportistaId");
  const inpMontoTraz = document.getElementById("tramoMonto");
  const inpComentTraz = document.getElementById("tramoComentario");

  const btnAgregarTramoTraz = document.getElementById("btnAgregarTramo");
  const tbodyTramosTraz = document.getElementById("tbodyTramos");
  const noTramosRowTraz = document.getElementById("noTramosRow");
  const totalMontoBadgeTraz = document.getElementById("totalMonto");

  // Hidden que viaja al back
  const payloadHiddenTraz = document.getElementById("rutasPayload");

  // ---- Estado del carrito ----
  // { id_tramo?:number, orden:number, origen_id, origen_nombre, destino_id, destino_nombre,
  //   transportista_id, transportista_nombre, monto, comentario?:string }
  let tramosTraz = [];

  // ---- Helpers ----
  const utilTraz = {
    money(n) {
      return Number(n || 0).toFixed(2);
    }, // permite 0.00
    toNumber(n) {
      const v = Number(n);
      return Number.isFinite(v) ? v : 0;
    },
    toastErr(msg) {
      if (window.Swal) Swal.fire("Error", msg, "error");
      else alert(msg);
    },
    toastInfo(msg) {
      if (window.Swal) Swal.fire("Info", msg, "info");
      else alert(msg);
    },

    // Sincroniza el hidden con el carrito (JSON)
    // En edición (append-only) se envían SOLO los tramos NUEVOS (sin id_tramo)
    syncPayload() {
      if (!payloadHiddenTraz) return;

      const items = tramosTraz
        .filter((t) => !isEditTraz || !t.id_tramo) // en edición: solo nuevos
        .map((t, i) => ({
          ...(t.id_tramo && !isEditTraz ? { id_tramo: t.id_tramo } : {}),
          orden: i + 1, // el orden real es la posición visible (no reordenamos)
          origen_id: t.origen_id,
          destino_id: t.destino_id,
          transportista_id: t.transportista_id,
          monto: utilTraz.toNumber(t.monto),
          comentario: t.comentario ?? null,
        }));

      payloadHiddenTraz.value = JSON.stringify(items);
    },

    // Render de tabla + cálculo total
    render() {
      // limpiar
      tbodyTramosTraz.innerHTML = "";
      if (!tramosTraz.length) {
        noTramosRowTraz.style.display = "";
        tbodyTramosTraz.appendChild(noTramosRowTraz);
        totalMontoBadgeTraz.textContent = utilTraz.money(0);
        return;
      }
      noTramosRowTraz.style.display = "none";

      let total = 0;
      tramosTraz.forEach((t, idx) => {
        total += utilTraz.toNumber(t.monto);
        const esExistente = !!t.id_tramo && isEditTraz;

        const btnEliminar = esExistente
          ? `<button type="button" class="btn btn-sm btn-outline-secondary" title="No editable" disabled>
               <i data-feather="lock"></i>
             </button>`
          : `<button type="button" class="btn btn-sm btn-outline-danger" data-idx="${idx}" title="Eliminar">
               <i data-feather="trash-2"></i>
             </button>`;

        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${t.origen_nombre || "-"}</td>
          <td>${t.destino_nombre || "-"}</td>
          <td>${t.transportista_nombre || "-"}</td>
          <td class="text-end">${utilTraz.money(t.monto)}</td>
          <td>${t.comentario ? t.comentario : ""}</td>
          <td class="text-center">${btnEliminar}</td>
        `;
        tbodyTramosTraz.appendChild(tr);
      });

      totalMontoBadgeTraz.textContent = utilTraz.money(total);
      feather?.replace();

      // delegar eliminar (solo en filas nuevas, las que traen data-idx)
      tbodyTramosTraz.querySelectorAll("button[data-idx]").forEach((btn) => {
        btn.addEventListener("click", () => {
          const i = Number(btn.getAttribute("data-idx"));
          if (!Number.isInteger(i) || i < 0 || i >= tramosTraz.length) return;
          tramosTraz.splice(i, 1);
          utilTraz.render();
          utilTraz.syncPayload();
          // encadenar origen del siguiente tramo
          setNextOriginFromLastTraz();
          setOrigenReadonlyByState(); 
        });
      });
    },
  };

  // Encadena: ORIGEN del siguiente tramo = DESTINO del último
  function setNextOriginFromLastTraz() {
    if (!inpOrigenNomTraz || !hidOrigenIdTraz) return;
    if (tramosTraz.length === 0) return; // si quieres, aquí podrías setear un origen base
    const last = tramosTraz[tramosTraz.length - 1];
    hidOrigenIdTraz.value = String(last.destino_id || "");
    inpOrigenNomTraz.value = last.destino_nombre || "";
  }

  // Limpia inputs de línea (manteniendo el ORIGEN si se encadena)
  function resetLineaTraz(keepOrigin = true) {
    if (!keepOrigin) {
      hidOrigenIdTraz.value = "";
      inpOrigenNomTraz.value = "";
    }
    hidDestinoIdTraz.value = "";
    inpDestinoNomTraz.value = "";
    hidTransIdTraz.value = "";
    inpTransNomTraz.value = "";
    inpMontoTraz.value = "";
    inpComentTraz.value = "";
  }
// Hook global llamado desde el archivo del modal al detectar edición del nombre de operación
window.onOperacionEditClear = function () {
  // Limpia ids de encabezado dependientes
  hidOpIdTrazabilidad.value = "";
  hidFerroIdTrazabilidad.value = "";

  // Ferro/caja: texto vacío y deshabilitado hasta seleccionar operación válida otra vez
  const fxNom = document.getElementById("rutaFerroNombre");
  if (fxNom) {
    fxNom.value = "";
    fxNom.disabled = true;
  }

  // Limpia inputs de línea y carrito completo de tramos
  tramosTraz.length = 0;
  utilTraz.render();
  utilTraz.syncPayload();
setOrigenReadonlyByState(); 
  // Reinicia inputs de tramo (incluido ORIGEN)
  resetLineaTraz(false);
};

  // ---- Agregar tramo (botón +) ----
  btnAgregarTramoTraz?.addEventListener("click", function () {
    // Validaciones mínimas
    const origenId = Number(hidOrigenIdTraz?.value || 0);
    const destinoId = Number(hidDestinoIdTraz?.value || 0);
    const transId = Number(hidTransIdTraz?.value || 0);
    const monto = utilTraz.toNumber(inpMontoTraz?.value || 0);
    const origenNom = (inpOrigenNomTraz?.value || "").trim();
    const destinoNom = (inpDestinoNomTraz?.value || "").trim();
    const transNom = (inpTransNomTraz?.value || "").trim();
    const coment = (inpComentTraz?.value || "").trim() || null;

    if (!origenId || !origenNom)
      return utilTraz.toastErr("Define el Origen (elige de las sugerencias).");
    if (!destinoId || !destinoNom)
      return utilTraz.toastErr("Define el Destino (elige de las sugerencias).");
    if (!transId || !transNom)
      return utilTraz.toastErr(
        "Define el Transportista (elige de las sugerencias)."
      );
    if (!Number.isFinite(monto) || monto < 0)
      return utilTraz.toastErr("Monto inválido.");

    // Construir item (orden se determina al render -> idx+1)
    tramosTraz.push({
      origen_id: origenId,
      origen_nombre: origenNom,
      destino_id: destinoId,
      destino_nombre: destinoNom,
      transportista_id: transId,
      transportista_nombre: transNom,
      monto: monto,
      comentario: coment,
    });

    utilTraz.render();
    utilTraz.syncPayload();

    // Preparar siguiente: origen = destino recién agregado
    resetLineaTraz(true);
    setNextOriginFromLastTraz();
    setOrigenReadonlyByState(); 
    inpDestinoNomTraz?.focus();
  });

  // ---- Al abrir el modal, si ya hay un tramo, encadenar origen ----
  document
    .getElementById("modalRutasFerro")
    ?.addEventListener("shown.bs.modal", function () {
      setNextOriginFromLastTraz();
    });

  // === MODO EDICIÓN (append-only) ===

  // Bloquear inputs de encabezado y el origen (readonly)
  function lockHeaderAndOriginTraz() {
    // Encabezado solo lectura
    document
      .getElementById("rutaOperacionFerroNombre")
      ?.setAttribute("readonly", "readonly");
    document
      .getElementById("rutaFerroNombre")
      ?.setAttribute("readonly", "readonly");
    // Origen del tramo siguiente = destino del último y readonly
    inpOrigenNomTraz?.setAttribute("readonly", "readonly");
  }

  // Desbloquear (modo alta)
  function unlockHeaderTraz() {
    document
      .getElementById("rutaOperacionFerroNombre")
      ?.removeAttribute("readonly");
    //document.getElementById("rutaFerroNombre")?.removeAttribute("readonly");
    inpOrigenNomTraz?.removeAttribute("readonly");
  }

  // Cargar detalle para edición
  async function cargarRutaDetalleTraz(rutaId) {
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest();
      xhr.open(
        "GET",
        BASE_URL +
          "operaciones_maritimo_ferro_trazabilidad/ruta_detalle?id_ruta=" +
          encodeURIComponent(String(rutaId)),
        true
      );
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
          const res = JSON.parse(xhr.responseText || "{}");
          resolve(res);
        } catch (e) {
          resolve({ ok: false, msg: "Respuesta inválida del servidor." });
        }
      };
      xhr.send();
    });
  }

  // Pinta header (solo lectura) y chips de clientes
  function pintarHeaderTraz(hdr, clientes) {
    if (!hdr) return;

    // Hidden ids
    hidOpIdTrazabilidad.value = String(hdr.operacion_ferro_id || "");
    hidFerroIdTrazabilidad.value = String(hdr.contenedor_fisico_id || "");

    // Inputs texto (readonly en edición)
    const opNom = document.getElementById("rutaOperacionFerroNombre");
    const fxNom = document.getElementById("rutaFerroNombre");
    if (opNom) opNom.value = hdr.numero_operacion || "";
    if (fxNom) fxNom.value = hdr.numero_ferro || "";

    // Comentario (si usas campo)
    if (inpComentarioRuta) inpComentarioRuta.value = hdr.comentario_ruta || "";

    // Chips clientes (reutiliza ui.renderClientes si existe del JS de catálogo)
    if (Array.isArray(clientes)) {
      const chips = clientes.map((c) => ({
        id_cliente: c.id_cliente,
        nombre: c.nombre,
      }));
      if (typeof ui !== "undefined" && ui.renderClientes)
        ui.renderClientes(chips);
    }
  }

  // Rellena carrito con tramos existentes (conservar id_tramo)
  function setCarritoDesdeServidorTraz(tramosSrv) {
    tramosTraz.length = 0;
    (tramosSrv || []).forEach((t) => {
      tramosTraz.push({
        id_tramo: Number(t.id_tramo || 0),
        origen_id: Number(t.origen_id || 0),
        origen_nombre: t.origen_nombre || "",
        destino_id: Number(t.destino_id || 0),
        destino_nombre: t.destino_nombre || "",
        transportista_id: Number(t.transportista_id || 0),
        transportista_nombre: t.transportista_nombre || "",
        monto: Number(t.monto || 0),
        comentario: t.comentario || null,
      });
    });
    utilTraz.render();
    utilTraz.syncPayload(); // en edición solo se enviarán nuevos
    setNextOriginFromLastTraz();
  }

  // Lanza el modal en MODO EDICIÓN (append-only)
  window.editarRutaFerro = async function (rutaId) {
    if (!rutaId || rutaId <= 0) return;

    isEditTraz = true;
    rutaIdTrazabilidad = rutaId;

    uiTraz.disable(btnGuardarTrazabilidad, true);

    // 1) Cargar detalle
    const res = await cargarRutaDetalleTraz(rutaId);
    if (!res || !res.ok) {
      uiTraz.err(res?.msg || "No fue posible cargar la ruta.");
      uiTraz.disable(btnGuardarTrazabilidad, false);
      return;
    }

    // 2) Pintar encabezado y clientes
    pintarHeaderTraz(res.header, res.clientes);

    // 3) Poner tramos en carrito
    setCarritoDesdeServidorTraz(res.tramos || []);

    // 4) Encabezado y Origen readonly
    lockHeaderAndOriginTraz();

    uiTraz.disable(btnGuardarTrazabilidad, false);

    // 5) Abrir modal
    const modalEl = document.getElementById("modalRutasFerro");
    if (modalEl && window.bootstrap?.Modal) {
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
  };

// Cuando vayas a crear NUEVA ruta (alta)
window.nuevaRutaFerro = function () {
  limpiarModalRutasTraz(); // Usa la función centralizada
};


// Función para limpiar completamente el modal
function limpiarModalRutasTraz() {
  // Resetear modo
  isEditTraz = false;
  rutaIdTrazabilidad = null;

  // Limpiar encabezado
  hidOpIdTrazabilidad.value = "";
  hidFerroIdTrazabilidad.value = "";
  
  const opNom = document.getElementById("rutaOperacionFerroNombre");
  const fxNom = document.getElementById("rutaFerroNombre");
  
  if (opNom) {
    opNom.value = "";
    opNom.removeAttribute("readonly"); // Activo para poder registrar
  }
  
  if (fxNom) {
    fxNom.value = "";
    fxNom.disabled = true; // Deshabilitar hasta que se seleccione operación
    //fxNom.removeAttribute("readonly"); // Remover readonly también
  }
  
  if (inpComentarioRuta) inpComentarioRuta.value = "";

  // Limpiar chips de clientes
  const chipsClientes = document.getElementById("rutaClientesChips");
  if (chipsClientes) {
    chipsClientes.innerHTML = `<span class="text-muted small">Sin clientes detectados para esta operación.</span>`;
  }

  // Limpiar inputs de línea de tramo
  hidOrigenIdTraz.value = "";
  if (inpOrigenNomTraz) {
    inpOrigenNomTraz.value = "";
    inpOrigenNomTraz.removeAttribute("readonly"); // Activo para poder escribir
  }
  
  hidDestinoIdTraz.value = "";
  if (inpDestinoNomTraz) inpDestinoNomTraz.value = "";
  
  hidTransIdTraz.value = "";
  if (inpTransNomTraz) inpTransNomTraz.value = "";
  
  if (inpMontoTraz) inpMontoTraz.value = "";
  if (inpComentTraz) inpComentTraz.value = "";

  // Limpiar carrito de tramos
  tramosTraz.length = 0;
  utilTraz.render();
  utilTraz.syncPayload();

  // Limpiar el payload hidden explícitamente
  if (payloadHiddenTraz) payloadHiddenTraz.value = "";

  // Desbloquear campos (por si venía de edición)
  unlockHeaderTraz();
  
  // Limpiar sugerencias visibles (del archivo trazabilidad.txt)
  const sugOpsBox = document.getElementById("sugOperacionesFerroRuta");
  const sugFerrosBox = document.getElementById("sugFerrosRuta");
  const sugOrigenes = document.getElementById("sugOrigenesRuta");
  const sugDestinos = document.getElementById("sugDestinosRuta");
  const sugTrans = document.getElementById("sugTransportistasRuta");
  setOrigenReadonlyByState();
  
  if (sugOpsBox) {
    sugOpsBox.innerHTML = "";
    sugOpsBox.style.display = "none";
  }
  if (sugFerrosBox) {
    sugFerrosBox.innerHTML = "";
    sugFerrosBox.style.display = "none";
  }
  if (sugOrigenes) {
    sugOrigenes.innerHTML = "";
    sugOrigenes.style.display = "none";
  }
  if (sugDestinos) {
    sugDestinos.innerHTML = "";
    sugDestinos.style.display = "none";
  }
  if (sugTrans) {
    sugTrans.innerHTML = "";
    sugTrans.style.display = "none";
  }
}

// Limpiar modal al cerrarse (tanto después de guardar como al cancelar)
const modalRutasFerro = document.getElementById("modalRutasFerro");
modalRutasFerro?.addEventListener('hidden.bs.modal', function () {
  limpiarModalRutasTraz();
});
})();
