/* ============================================================================
   MÓDULO: Costos por Operación (SOLO MF) - REGISTRAR/EDITAR (XHR)
   Archivo: costos_operacion_registrar.js

   ✅ Actualizado a tu nueva implementación:
   - Ya NO se manda "fuente"
   - Ya NO se manda "operacion_ferro_id"
   - El backend espera:
       row_id (0 crea / >0 actualiza)
       operacion_id
       tipo_movimiento_id
       monto
       comentario
       costosContenedoresPagado   (ojo: así lo lee tu controlador)
   - Endpoint:
       Operaciones_maritimo_ferro_costos_Contenedor/guardar
   - Refresco:
       window.listarCostosOperacion(1)
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

  // Opcionales
  const formModal = document.getElementById("formCostosOperacion");
  const btnGuardar = document.getElementById("btnGuardarCostoOperacion");

  // ------- Helpers -------
  function toast(icon, title, text) {
    if (window.Swal) {
      Swal.fire({ icon, title, text, confirmButtonText: "OK" });
    } else {
      alert(`${title}${text ? `: ${text}` : ""}`);
    }
  }

  function syncMonedaPorTipo() {
    // Si tu <option> trae data-moneda, sincroniza moneda
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

    // Soporta "1,234.56" y "1234,56" de forma tolerante
    let montoRaw = String(montoModal?.value || "").trim();
    montoRaw = montoRaw.replace(/\s/g, "").replace(/,/g, ""); // simple (si usas coma decimal, cámbialo)
    const monto = parseFloat(montoRaw) || 0;

    const comentario = String(comentModal?.value || "").trim();
    const pagado = parseInt(selPagadoModal?.value || "0", 10) === 1 ? 1 : 0;

    // Validaciones mínimas (alineadas al controlador)
    if (opId <= 0 && rowId <= 0) return { ok: false, msg: "Falta operación." };
    if (tipoId <= 0) return { ok: false, msg: "Selecciona un tipo/concepto." };
    if (!Number.isFinite(monto) || monto <= 0)
      return { ok: false, msg: "Ingresa un monto válido (> 0)." };

    const fd = new FormData();
    fd.append("row_id", String(rowId)); // siempre
    if (opId > 0) fd.append("operacion_id", String(opId)); // requerido al crear

    fd.append("tipo_movimiento_id", String(tipoId));
    fd.append("monto", String(monto.toFixed(2)));
    fd.append("comentario", comentario);

    // 👇 IMPORTANTE: tu controlador lee este nombre exacto
    fd.append("costosContenedoresPagado", String(pagado));

    return { ok: true, data: fd };
  }

  function guardarXHR() {
    const pkg = buildPayload();
    if (!pkg.ok) return toast("warning", "Validación", pkg.msg);

    const url = `${base_url}Operaciones_maritimo_ferro_costos_Contenedor/guardar`;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    // UX: bloquear botón (si existe)
    const btn =
      btnGuardar ||
      (formModal ? formModal.querySelector('button[type="submit"]') : null);
    const oldHtml = btn ? btn.innerHTML : "";
    if (btn) {
      btn.disabled = true;
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Guardando…';
    }

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (btn) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
      }

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

        // reset row_id para que el siguiente sea "crear"
        if (hidRowId) hidRowId.value = "";

        // cerrar modal
        if (modalEl && window.bootstrap) {
          window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }

        // refrescar listado
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

  // ------- UX: cuando abre modal, sincroniza moneda -------
  modalEl?.addEventListener("shown.bs.modal", () => {
    syncMonedaPorTipo();
  });
})();
