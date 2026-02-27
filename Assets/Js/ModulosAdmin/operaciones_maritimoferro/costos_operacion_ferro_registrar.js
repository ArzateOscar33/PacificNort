/* ============================================================================
   MÓDULO: Costos por Operación (SOLO MF) - REGISTRAR/EDITAR (XHR)
   Archivo: costos_operacion_ferro_registrar.js  (o costos_operacion_registrar.js)

   ✅ Anti-doble-guardado:
   - Lock global window.__costosOpSaving
   - Evita doble binding con dataset.bound
   - Soporta form id real: formAgregarCostoContenedores (y fallback formCostosOperacion)
   - Preferencia: guardar por CLICK (tu botón es type="button")
   ============================================================================ */
(function () {
  "use strict";

  // ------- DOM del modal -------
  const modalEl = document.getElementById("modalCostoOperacion");
  const hidRowId = document.getElementById("row_id");
  const opIdModal = document.getElementById("costosOperacionid");
  const opNomModal = document.getElementById("costosOperacionNombre");
  const selTipoModal = document.getElementById("costosContenedoresTipoCosto");
  const selMonModal = document.getElementById("costosContenedoresMoneda");
  const montoModal = document.getElementById("costosContenedoresMonto");
  const comentModal = document.getElementById("costosContenedoresComentarios");
  const contIdModal = document.getElementById("costosContenedorContenedorId");
  const contNomModal = document.getElementById(
    "costosContenedorContenedorNombre",
  );
  const selPagadoModal = document.getElementById("costosContenedoresPagado");

  // ✅ Tu vista actual:
  const formModal =
    document.getElementById("formAgregarCostoContenedores") ||
    document.getElementById("formCostosOperacion");

  const btnGuardar = document.getElementById("btnGuardarCostoOperacion");

  if (!modalEl || !btnGuardar) {
    // No hay modal o no hay botón: nada que hacer
    return;
  }

  // ------- Helpers -------
  function toast(icon, title, text) {
    if (window.Swal) Swal.fire({ icon, title, text, confirmButtonText: "OK" });
    else alert(`${title}${text ? `: ${text}` : ""}`);
  }

  function syncMonedaPorTipo() {
    if (!selTipoModal || !selMonModal) return;
    const opt = selTipoModal.selectedOptions?.[0];
    const m = opt
      ? String(opt.getAttribute("data-moneda") || "").toUpperCase()
      : "";
    if (m === "PESOS" || m === "DLLS") selMonModal.value = m;
  }
  selTipoModal?.addEventListener("change", syncMonedaPorTipo);

  function buildPayload() {
    const rowId = parseInt(hidRowId?.value || "0", 10) || 0;
    const opId = parseInt(opIdModal?.value || "0", 10) || 0;
    const tipoId = parseInt(selTipoModal?.value || "0", 10) || 0;

    let montoRaw = String(montoModal?.value || "").trim();
    montoRaw = montoRaw.replace(/\s/g, "").replace(/,/g, "");
    const monto = parseFloat(montoRaw) || 0;

    const comentario = String(comentModal?.value || "").trim();
    const pagado = parseInt(selPagadoModal?.value || "0", 10) === 1 ? 1 : 0;

    if (opId <= 0 && rowId <= 0) return { ok: false, msg: "Falta operación." };
    if (tipoId <= 0) return { ok: false, msg: "Selecciona un tipo/concepto." };
    if (!Number.isFinite(monto) || monto <= 0)
      return { ok: false, msg: "Ingresa un monto válido (> 0)." };

    const fd = new FormData();
    fd.append("row_id", String(rowId));
    if (opId > 0) fd.append("operacion_id", String(opId));
    fd.append("tipo_movimiento_id", String(tipoId));
    fd.append("monto", String(monto.toFixed(2)));
    fd.append("comentario", comentario);
    fd.append("costosContenedoresPagado", String(pagado));

    return { ok: true, data: fd };
  }

  function setBtnLoading(isLoading) {
    if (!btnGuardar) return;
    if (!btnGuardar.dataset.oldHtml)
      btnGuardar.dataset.oldHtml = btnGuardar.innerHTML;

    if (isLoading) {
      btnGuardar.disabled = true;
      btnGuardar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Guardando…';
    } else {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = btnGuardar.dataset.oldHtml || btnGuardar.innerHTML;
    }
  }

  function guardarXHR() {
    // ✅ Lock global anti doble click / doble bind / doble submit
    if (window.__costosOpSaving) return;
    window.__costosOpSaving = true;

    const pkg = buildPayload();
    if (!pkg.ok) {
      window.__costosOpSaving = false;
      return toast("warning", "Validación", pkg.msg);
    }

    setBtnLoading(true);

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/guardar`;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      setBtnLoading(false);
      window.__costosOpSaving = false;

      let resp = {};
      try {
        resp = JSON.parse(xhr.responseText) || {};
      } catch {
        resp = {};
      }

      const st = String(resp.status || "").toLowerCase();
      const mg = resp.message || resp.msg || "";

      if (xhr.status !== 200) {
        toast("error", "Error al guardar", mg || `HTTP ${xhr.status}`);
        return;
      }

      if (st === "success") {
        toast("success", "Éxito", mg || "Guardado correctamente");

        if (hidRowId) hidRowId.value = "";

        if (modalEl && window.bootstrap) {
          window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }

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

  // ------- Enganches -------
  // ✅ Evitar doble binding aunque el script se cargue 2 veces
  if (btnGuardar.dataset.bound === "1") return;
  btnGuardar.dataset.bound = "1";

  // Preferimos CLICK (tu botón es type="button")
  btnGuardar.addEventListener("click", (e) => {
    e.preventDefault();
    guardarXHR();
  });

  // Si el usuario presiona Enter dentro del form, evitamos submit normal
  // y lo convertimos en guardar, sin duplicar (lock global lo previene).
  if (formModal) {
    formModal.addEventListener("submit", (e) => {
      e.preventDefault();
      guardarXHR();
    });
  }

  // UX: cuando abre modal, sincroniza moneda
  modalEl.addEventListener("shown.bs.modal", () => {
    syncMonedaPorTipo();
    // liberamos lock por si el modal se reabrió tras un cierre raro
    window.__costosOpSaving = false;
    setBtnLoading(false);
  });

  // Al cerrar modal, reset lock por seguridad
  modalEl.addEventListener("hidden.bs.modal", () => {
    window.__costosOpSaving = false;
    setBtnLoading(false);
  });
})();
