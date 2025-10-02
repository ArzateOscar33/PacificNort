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

  /**
   * Click en Guardar (fase 1: crear ruta_ferro)
   * - Valida que haya operación y ferro/caja
   * - Llama al endpoint de crear ruta
   * - Guarda ruta_id en estado, y bloquea los campos base (opcional)
   * - (Más adelante) aquí enganchas guardar tramos con ese ruta_id
   */
  btnGuardarTrazabilidad.addEventListener("click", async function(){
    const v = uiTraz.validarBase();
    if (!v) return;

    // Comentario opcional
    const comentario = inpComentarioRuta ? String(inpComentarioRuta.value || "") : "";

    // Deshabilitar el botón mientras se procesa
    uiTraz.disable(btnGuardarTrazabilidad, true);

    const res = await crearRutaFerroTrazabilidad(v.opId, v.ferroId, comentario);
    if (!res || !res.ok) {
      uiTraz.disable(btnGuardarTrazabilidad, false);
      uiTraz.err(res?.msg || "No fue posible crear la ruta.");
      return;
    }

    // Guardamos ruta_id y bloqueamos los campos de cabecera (sugerido)
    rutaIdTrazabilidad = Number(res.ruta_id || 0);
    uiTraz.ok("Ruta creada correctamente.");

    // Si quieres impedir cambios de encabezado después de crearla:
    // uiTraz.disable(document.getElementById("rutaOperacionFerroNombre"), true);
    // uiTraz.disable(document.getElementById("rutaFerroNombre"), true);

    // Nota: 'rutasPayload' contiene el carrito de tramos (si ya lo manejas en el otro JS).
    // En esta primera fase SOLO creamos la ruta_ferro como acordamos.
    // Cuando quieras, integramos aquí la 2da fase: POST de tramos usando 'rutaIdTrazabilidad' + 'rutasPayloadTrazabilidad.value'.
    uiTraz.disable(btnGuardarTrazabilidad, false);
  });

})();
