/* ============================================================================
   MÓDULO: Costos por Operación MF - REGISTRAR / EDITAR (XHR)
   Archivo sugerido: costos_operacion_ferro_registrar.js

   Compatible con:
   - Catálogo: Costos por Operación (SOLO MF)
   - Controlador: Operaciones_maritimo_ferro_costos_Contenedor
   - Modal: #modalCostoOperacion

   Espera:
   - row_id
   - operacion_id
   - tipo_movimiento_id
   - monto
   - comentario
   - costosContenedoresPagado
   ============================================================================ */
(function () {
  "use strict";

  // ------- BASE URL -------
  const base =
    (typeof window.base !== "undefined" && window.base) ||
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ------- DOM del modal MF -------
  const modalEl = document.getElementById("modalCostoOperacion");
  const hidRowId = document.getElementById("row_id");

  const operacionIdModal = document.getElementById("costosOperacionid");
  const operacionNomModal = document.getElementById("costosOperacionNombre");

  const selTipoModal = document.getElementById("costosContenedoresTipoCosto");
  const selMonModal = document.getElementById("costosContenedoresMoneda");
  const montoModal = document.getElementById("costosContenedoresMonto");
  const comentModal = document.getElementById("costosContenedoresComentarios");
  const selPagadoModal = document.getElementById("costosContenedoresPagado");

  const contIdModal = document.getElementById("costosContenedorContenedorId");
  const contNomModal = document.getElementById(
    "costosContenedorContenedorNombre",
  );

  const formModal = document.getElementById("formAgregarCostoContenedores");
  const btnGuardar = document.getElementById("btnGuardarCostoOperacion");

  const facturaModal = document.getElementById("costosContenedoresFactura");
  const selBrokerModal = document.getElementById("costosContenedoresBroker");

  if (!modalEl || !btnGuardar) {
    return;
  }

  // ------- Helpers -------
  function toast(icon, title, text) {
    if (window.Swal) {
      Swal.fire({
        icon,
        title,
        text,
        confirmButtonText: "OK",
      });
    } else {
      alert(`${title}${text ? `: ${text}` : ""}`);
    }
  }

  function syncMonedaPorTipo() {
    if (!selTipoModal || !selMonModal) return;

    const opt = selTipoModal.selectedOptions?.[0];
    const moneda = opt
      ? String(opt.getAttribute("data-moneda") || "").toUpperCase()
      : "";

    if (moneda === "PESOS" || moneda === "DLLS") {
      selMonModal.value = moneda;
    } else {
      selMonModal.value = "";
    }
  }

  selTipoModal?.addEventListener("change", syncMonedaPorTipo);

  function buildPayload() {
    const rowId = parseInt(hidRowId?.value || "0", 10) || 0;
    const operacionId = parseInt(operacionIdModal?.value || "0", 10) || 0;
    const tipoId = parseInt(selTipoModal?.value || "0", 10) || 0;
    const brokerId = parseInt(selBrokerModal?.value || "0", 10) || 0;

    let montoRaw = String(montoModal?.value || "").trim();
    montoRaw = montoRaw.replace(/\s/g, "").replace(/,/g, "");
    const monto = parseFloat(montoRaw) || 0;

    const factura = String(facturaModal?.value || "")
      .trim()
      .toUpperCase();
    const comentario = String(comentModal?.value || "").trim();
    const pagado = parseInt(selPagadoModal?.value || "0", 10) === 1 ? 1 : 0;

    if (operacionId <= 0 && rowId <= 0) {
      return { ok: false, msg: "Falta operación." };
    }

    if (tipoId <= 0) {
      return { ok: false, msg: "Selecciona un tipo de movimiento." };
    }

    if (!Number.isFinite(monto) || monto <= 0) {
      return { ok: false, msg: "Ingresa un monto válido (> 0)." };
    }

    const fd = new FormData();
    fd.append("row_id", String(rowId));

    if (operacionId > 0) {
      fd.append("operacion_id", String(operacionId));
    }

    fd.append("tipo_movimiento_id", String(tipoId));
    fd.append("monto", String(monto.toFixed(2)));
    fd.append("factura", factura);
    fd.append("broker_id", String(brokerId));
    fd.append("comentario", comentario);
    fd.append("costosContenedoresPagado", String(pagado));

    return { ok: true, data: fd };
  }

  function setBtnLoading(isLoading) {
    if (!btnGuardar) return;

    if (!btnGuardar.dataset.oldHtml) {
      btnGuardar.dataset.oldHtml = btnGuardar.innerHTML;
    }

    if (isLoading) {
      btnGuardar.disabled = true;
      btnGuardar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Guardando…';
    } else {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = btnGuardar.dataset.oldHtml || btnGuardar.innerHTML;
    }
  }

  function cerrarModal() {
    if (modalEl && window.bootstrap) {
      window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    }
  }

  function refrescarListado() {
    if (typeof window.listarCostosOperacion === "function") {
      window.listarCostosOperacion(1);
    }
  }

  function limpiarEstadoGuardado() {
    window.__costosOperacionSaving = false;
    setBtnLoading(false);
  }

  function guardarXHR() {
    if (window.__costosOperacionSaving) return;
    window.__costosOperacionSaving = true;

    const pkg = buildPayload();
    if (!pkg.ok) {
      window.__costosOperacionSaving = false;
      return toast("warning", "Validación", pkg.msg);
    }

    setBtnLoading(true);

    const url = `${base}Operaciones_maritimo_ferro_costos_Contenedor/guardar`;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      setBtnLoading(false);
      window.__costosOperacionSaving = false;

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

        cerrarModal();
        refrescarListado();
      } else if (st === "warning") {
        toast("warning", "Aviso", mg || "Revisa los datos.");
      } else {
        toast("error", "No se pudo guardar", mg || "Intenta de nuevo.");
      }
    };

    xhr.onerror = function () {
      setBtnLoading(false);
      window.__costosOperacionSaving = false;
      toast("error", "Error de red", "No se pudo conectar con el servidor.");
    };

    xhr.send(pkg.data);
  }

  // ------- Enganches -------
  if (btnGuardar.dataset.bound === "1") return;
  btnGuardar.dataset.bound = "1";

  btnGuardar.addEventListener("click", function (e) {
    e.preventDefault();
    guardarXHR();
  });

  if (formModal) {
    formModal.addEventListener("submit", function (e) {
      e.preventDefault();
      guardarXHR();
    });
  }

  modalEl.addEventListener("shown.bs.modal", function () {
    syncMonedaPorTipo();
    window.__costosOperacionSaving = false;
    setBtnLoading(false);
  });

  modalEl.addEventListener("hidden.bs.modal", function () {
    window.__costosOperacionSaving = false;
    setBtnLoading(false);
  });
})();
