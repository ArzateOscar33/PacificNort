// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_costos_registrar.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Endpoint =====
  const ENDPOINT_GUARDAR = "Operaciones_por_partida_costos/guardar";

  // ===== Refs modal / form =====
  const modalEl = document.getElementById("modalCostoPartida");
  const formEl = document.getElementById("formCostoPartida");

  const inpRowId = document.getElementById("costosPartidaRowId");
  const inpFacturaId = document.getElementById("costosPartidaFacturaId");
  const inpFacturaNombre = document.getElementById(
    "costosPartidaFacturaNombre",
  );
  const selFerro = document.getElementById("costosPartidaFerroId");
  const selTipo = document.getElementById("costosPartidaTipoMovimientoId");
  const inpMonto = document.getElementById("costosPartidaMonto");
  const selMoneda = document.getElementById("costosPartidaMoneda");
  const selPagado = document.getElementById("costosPartidaPagado");
  const txtComentario = document.getElementById("costosPartidaComentario");

  const btnGuardar = document.getElementById("btnGuardarCostoPartida");
  const btnCancelar = document.getElementById("btnCancelarCostoPartida");

  // ===== Estado =====
  let xhrGuardar = null;

  // ===== Helpers =====
  function toastSwal(icon, title, text) {
    if (typeof Swal !== "undefined" && Swal.fire) {
      Swal.fire({
        icon,
        title,
        text,
        timer: icon === "success" ? 1800 : undefined,
        showConfirmButton: icon !== "success",
      });
      return;
    }
    alert((title ? title + ": " : "") + (text || ""));
  }

  function setEnabled(el, enabled) {
    if (!el) return;
    el.disabled = !enabled;
  }

  function getIntValue(el) {
    return parseInt(el?.value || "0", 10) || 0;
  }

  function getFloatValue(el) {
    const n = parseFloat(el?.value || "0");
    return Number.isFinite(n) ? n : 0;
  }

  function isEditMode() {
    return getIntValue(inpRowId) > 0;
  }

  function updateGuardarState() {
    const tipoId = getIntValue(selTipo);
    const monto = getFloatValue(inpMonto);
    const ferroId = getIntValue(selFerro);
    const facturaId = getIntValue(inpFacturaId);

    let ok = true;

    if (isEditMode()) {
      if (tipoId <= 0 || monto <= 0 || ferroId <= 0) ok = false;
    } else {
      if (facturaId <= 0 || tipoId <= 0 || monto <= 0 || ferroId <= 0)
        ok = false;
    }

    setEnabled(btnGuardar, ok);
  }

  function syncMonedaFromTipo() {
    if (!selTipo || !selMoneda) return;

    const opt = selTipo.selectedOptions?.[0];
    const moneda = (opt?.getAttribute("data-moneda") || "").toUpperCase();

    if (moneda === "PESOS" || moneda === "DLLS") {
      selMoneda.value = moneda;
    } else {
      selMoneda.value = "";
    }
  }

  function validarForm() {
    const rowId = getIntValue(inpRowId);
    const facturaId = getIntValue(inpFacturaId);
    const ferroId = getIntValue(selFerro);
    const tipoId = getIntValue(selTipo);
    const monto = getFloatValue(inpMonto);

    if (rowId <= 0 && facturaId <= 0) {
      return { ok: false, msg: "Selecciona una factura válida." };
    }

    if (ferroId <= 0) {
      return { ok: false, msg: "Selecciona un ferro/caja." };
    }

    if (tipoId <= 0) {
      return { ok: false, msg: "Selecciona un tipo de costo." };
    }

    if (monto <= 0) {
      return { ok: false, msg: "Ingresa un monto válido." };
    }

    return { ok: true, msg: "" };
  }

  function buildFormData() {
    const fd = new FormData();

    fd.append("row_id", String(getIntValue(inpRowId)));
    fd.append("factura_id", String(getIntValue(inpFacturaId)));
    fd.append("contenedor_fisico_id", String(getIntValue(selFerro)));
    fd.append("tipo_movimiento_id", String(getIntValue(selTipo)));
    fd.append("monto", String(getFloatValue(inpMonto)));
    fd.append("comentario", String(txtComentario?.value || "").trim());
    fd.append("costosContenedoresPagado", String(getIntValue(selPagado)));

    return fd;
  }

  function closeModal() {
    try {
      const modal =
        bootstrap.Modal.getInstance(modalEl) ||
        bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.hide();
    } catch (e) {}
  }

  function refreshCatalogo() {
    if (typeof window.listarCostosPartida === "function") {
      window.listarCostosPartida(1);
      return;
    }

    if (typeof window.listarCostosOperacion === "function") {
      window.listarCostosOperacion(1);
    }
  }

  function limpiarEstadosVisuales() {
    if (inpMonto) inpMonto.classList.remove("is-invalid");
    if (selTipo) selTipo.classList.remove("is-invalid");
    if (selFerro) selFerro.classList.remove("is-invalid");
    if (inpFacturaNombre) inpFacturaNombre.classList.remove("is-invalid");
  }

  function marcarErroresBasicos() {
    limpiarEstadosVisuales();

    if (getIntValue(inpFacturaId) <= 0 && !isEditMode()) {
      inpFacturaNombre?.classList.add("is-invalid");
    }
    if (getIntValue(selFerro) <= 0) {
      selFerro?.classList.add("is-invalid");
    }
    if (getIntValue(selTipo) <= 0) {
      selTipo?.classList.add("is-invalid");
    }
    if (getFloatValue(inpMonto) <= 0) {
      inpMonto?.classList.add("is-invalid");
    }
  }

  function setSaving(flag) {
    if (!btnGuardar) return;

    if (flag) {
      btnGuardar.dataset.originalHtml = btnGuardar.innerHTML;
      btnGuardar.disabled = true;
      btnGuardar.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Guardando...';
    } else {
      btnGuardar.disabled = false;
      if (btnGuardar.dataset.originalHtml) {
        btnGuardar.innerHTML = btnGuardar.dataset.originalHtml;
      }
      updateGuardarState();
      window.feather?.replace?.();
    }
  }

  function guardarCosto() {
    const valid = validarForm();
    if (!valid.ok) {
      marcarErroresBasicos();
      toastSwal("warning", "Validación", valid.msg);
      return;
    }

    const fd = buildFormData();

    try {
      if (xhrGuardar) xhrGuardar.abort();
    } catch (e) {}

    setSaving(true);

    xhrGuardar = new XMLHttpRequest();
    xhrGuardar.open("POST", base_url + ENDPOINT_GUARDAR, true);

    xhrGuardar.onreadystatechange = function () {
      if (xhrGuardar.readyState !== 4) return;

      setSaving(false);

      if (xhrGuardar.status !== 200) {
        toastSwal("error", "Error", "Error HTTP al guardar el costo.");
        return;
      }

      let json = null;
      try {
        json = JSON.parse(xhrGuardar.responseText);
      } catch (e) {}

      if (!json) {
        toastSwal("error", "Error", "Respuesta inválida del servidor.");
        return;
      }

      const status = String(json.status || "").toLowerCase();

      if (status === "success") {
        toastSwal(
          "success",
          "Listo",
          json.message ||
            (isEditMode() ? "Costo actualizado." : "Costo registrado."),
        );

        closeModal();
        refreshCatalogo();
        return;
      }

      if (status === "warning") {
        marcarErroresBasicos();
        toastSwal("warning", "Atención", json.message || "Revisa los datos.");
        return;
      }

      toastSwal("error", "Error", json.message || "No se pudo guardar.");
    };

    xhrGuardar.send(fd);
  }

  // ===== Events =====

  if (selTipo) {
    selTipo.addEventListener("change", function () {
      syncMonedaFromTipo();
      updateGuardarState();
      selTipo.classList.remove("is-invalid");
    });
  }

  if (selFerro) {
    selFerro.addEventListener("change", function () {
      updateGuardarState();
      selFerro.classList.remove("is-invalid");
    });
  }

  if (inpMonto) {
    inpMonto.addEventListener("input", function () {
      updateGuardarState();
      inpMonto.classList.remove("is-invalid");
    });
  }

  if (inpFacturaId) {
    inpFacturaId.addEventListener("change", function () {
      updateGuardarState();
      inpFacturaNombre?.classList.remove("is-invalid");
    });
  }

  if (selPagado) {
    selPagado.addEventListener("change", updateGuardarState);
  }

  if (txtComentario) {
    txtComentario.addEventListener("input", updateGuardarState);
  }

  if (btnGuardar) {
    btnGuardar.addEventListener("click", function (e) {
      e.preventDefault();
      guardarCosto();
    });
  }

  if (formEl) {
    formEl.addEventListener("submit", function (e) {
      e.preventDefault();
      guardarCosto();
    });
  }

  if (modalEl) {
    modalEl.addEventListener("shown.bs.modal", function () {
      limpiarEstadosVisuales();
      syncMonedaFromTipo();
      updateGuardarState();
    });

    modalEl.addEventListener("hidden.bs.modal", function () {
      limpiarEstadosVisuales();
      setSaving(false);
    });
  }

  if (btnCancelar) {
    btnCancelar.addEventListener("click", function () {
      limpiarEstadosVisuales();
    });
  }

  // Estado inicial
  syncMonedaFromTipo();
  updateGuardarState();
})();
