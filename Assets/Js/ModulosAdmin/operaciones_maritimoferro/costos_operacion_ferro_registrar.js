 
/* ============================================================================
   MÓDULO: Costos por Operación (FO/MF) - REGISTRAR/EDITAR (XHR)
   Archivo: costos_operacion_registrar.js
   Requisitos en la vista/modal (ya existen en tu archivo de catálogo):
     - modal:   #modalCostoOperacion
     - inputs:  #row_id, #costosOperacionid, #costosOperacionNombre,
                #costosContenedoresTipoCosto, #costosContenedoresMoneda,
                #costosContenedoresMonto, #costosContenedoresComentarios,
                #costosContenedorContenedorId, #costosContenedorContenedorNombre
   Disparadores:
     - form submit con id="formCostosOperacion" (si existe)
     - o botón guardar con id="btnGuardarCostoOperacion" (si no hay form)
   ============================================================================ */
(function () {
  "use strict";

  // ------- DOM del modal (los mismos IDs del archivo visual) -------
  const modalEl      = document.getElementById("modalCostoOperacion");
  const hidRowId     = document.getElementById("row_id");
  const opIdModal    = document.getElementById("costosOperacionid");
  const opNomModal   = document.getElementById("costosOperacionNombre");
  const selTipoModal = document.getElementById("costosContenedoresTipoCosto");
  const selMonModal  = document.getElementById("costosContenedoresMoneda");
  const montoModal   = document.getElementById("costosContenedoresMonto");
  const comentModal  = document.getElementById("costosContenedoresComentarios");
  const contIdModal  = document.getElementById("costosContenedorContenedorId");
  const contNomModal = document.getElementById("costosContenedorContenedorNombre");

  // Opcionales en tu HTML:
  const formModal    = document.getElementById("formCostosOperacion");       // si tu modal tiene form
  const btnGuardar   = document.getElementById("btnGuardarCostoOperacion");  // o un botón específico

  // ------- Helpers -------
  const getFuente = () => (String(window.fuenteSel || "F").toUpperCase() === "MF" ? "MF" : "F");

  function toast(icon, title, text) {
    if (window.Swal) {
      Swal.fire({ icon, title, text, confirmButtonText: "OK" });
    } else {
      alert(`${title}${text ? `: ${text}` : ""}`);
    }
  }

  function syncMonedaPorTipo() {
    // Si tu <option> en el select de tipo trae data-moneda, sincroniza moneda
    const opt = selTipoModal?.selectedOptions?.[0];
    if (!opt || !selMonModal) return;
    const m = (opt.getAttribute("data-moneda") || "").toUpperCase();
    if (m === "PESOS" || m === "DLLS") selMonModal.value = m;
  }
  selTipoModal?.addEventListener("change", syncMonedaPorTipo);

  function collectingPayload() {
    const fuente = getFuente();
    const rowId  = parseInt(hidRowId?.value || "0", 10) || 0;
    const opId   = parseInt(opIdModal?.value || "0", 10) || 0;
    const tipoId = parseInt(selTipoModal?.value || "0", 10) || 0;
    const monto  = parseFloat(montoModal?.value || "0") || 0;
    const coment = (comentModal?.value || "").trim();

    // Validaciones mínimas
    if (opId <= 0)        return { ok:false, msg:"Selecciona una operación." };
    if (tipoId <= 0)      return { ok:false, msg:"Selecciona un tipo/concepto." };
    if (!Number.isFinite(monto) || monto <= 0) return { ok:false, msg:"Ingresa un monto válido (> 0)." };

    // El backend acepta ambos nombres de FK (compat). Mandamos ambos.
    const data = new FormData();
    data.append("fuente", fuente);
    if (rowId > 0) data.append("row_id", String(rowId));
    data.append("operacion_id", String(opId));
    data.append("operacion_ferro_id", String(opId)); // compat
    data.append("tipo_movimiento_id", String(tipoId));
    data.append("monto", String(monto.toFixed(2)));
    data.append("comentario", coment);

    return { ok:true, data };
  }

  function guardarXHR() {
    const pkg = collectingPayload();
    if (!pkg.ok) { toast("warning", "Validación", pkg.msg); return; }

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/guardar`;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      let resp = {};
      try { resp = JSON.parse(xhr.responseText) || {}; } catch { resp = {}; }

      // Manejo de estado
      const st = String(resp.status || "").toLowerCase();
      const mg = resp.message || "";

      if (xhr.status !== 200) {
        toast("error", "Error al guardar", mg || `HTTP ${xhr.status}`);
        return;
      }

      if (st === "success") {
        toast("success", "Éxito", mg || "Guardado correctamente");
        // Cerrar modal y resetear row_id
        if (hidRowId) hidRowId.value = "";
        if (modalEl && window.bootstrap) {
          window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }
        // Refrescar listado (expuesto por el catálogo)
        if (typeof window.listarCostosOperacion === "function") {
          window.listarCostosOperacion(1);
        }
      } else if (st === "warning") {
        toast("warning", "Aviso", mg || "Revisa los datos.");
      } else {
        toast("error", "No se pudo guardar", mg || "Intenta de nuevo.");
      }
    };

    xhr.send(pkg.data);
  }

  // ------- Enganches (form submit o botón) -------
  if (formModal) {
    formModal.addEventListener("submit", (e) => {
      e.preventDefault();
      guardarXHR();
    });
  }
  if (btnGuardar) {
    btnGuardar.addEventListener("click", (e) => {
      e.preventDefault();
      guardarXHR();
    });
  }

  // ------- UX: cuando se abre el modal, sincroniza moneda por tipo -------
  modalEl?.addEventListener("shown.bs.modal", () => {
    syncMonedaPorTipo();
  });

})();
 