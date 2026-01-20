 // Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_registro.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  const ENDPOINT_REGISTRAR_FACTURA = "Operaciones_por_partida/registrar";

  // ===== Refs (IDs existentes) =====
  const formFactura  = document.getElementById("formOperacionesPartida");
  const btnGuardar   = document.getElementById("operaciones_partida_btnGuardarEncabezado");

  const selBodega    = document.getElementById("operaciones_partida_bodega");
  const chkRevision  = document.getElementById("operaciones_partida_revision");
  const inpPallets   = document.getElementById("operaciones_partida_pallets_rcv");
  const inpFactura   = document.getElementById("operaciones_partida_factura");
  const inpProveedor = document.getElementById("operaciones_partida_proveedor");
  const inpFecha     = document.getElementById("operaciones_partida_fechaRecibido");
  const inpNotas     = document.getElementById("operaciones_partida_notas");

  // (Opcional) hidden id en tu form: operaciones_partida_id
  const inpIdFacturaHidden = document.getElementById("operaciones_partida_id");

  let xhrGuardar = null;

  function esc(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function setBtnLoading(isLoading) {
    if (!btnGuardar) return;
    btnGuardar.disabled = !!isLoading;
  }

function swalSuccess(msg) {
  Swal.fire({
    icon: "success",
    title: "Correcto",
    text: msg,
    confirmButtonText: "Aceptar",
    confirmButtonColor: "#198754", // bootstrap success
  });
}

function swalError(msg) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: msg,
    confirmButtonText: "Aceptar",
    confirmButtonColor: "#dc3545", // bootstrap danger
  });
}

function swalWarning(msg) {
  Swal.fire({
    icon: "warning",
    title: "Atención",
    text: msg,
    confirmButtonText: "Aceptar",
    confirmButtonColor: "#ffc107", // bootstrap warning
  });
}


  function validarEncabezado() {
    const bodegaId  = selBodega ? String(selBodega.value || "").trim() : "";
    const factura   = inpFactura ? String(inpFactura.value || "").trim() : "";
    const proveedor = inpProveedor ? String(inpProveedor.value || "").trim() : "";

    if (!bodegaId) return "Selecciona una bodega.";
    if (!factura) return "El número de factura es obligatorio.";
    if (!proveedor) return "El proveedor es obligatorio.";

    // Pallets puede ser 0 válido; fecha puede ser opcional según tu regla
    if (inpPallets) {
      const n = parseInt(inpPallets.value || "0", 10);
      if (isNaN(n) || n < 0) return "Pallets INV (Factura) debe ser 0 o mayor.";
    }

    return null;
  }

  function registrarFactura() {
    if (!formFactura) return;

    const err = validarEncabezado();
if (err) {
  swalWarning(err);
  return;
}

    // abortar request previo si existe
    if (xhrGuardar && xhrGuardar.readyState !== 4) {
      try { xhrGuardar.abort(); } catch (_) {}
    }

    const fd = new FormData(formFactura);

    // Asegurar checkbox en FormData (por si tu backend depende del name="revision_pasa")
    // En tu vista el checkbox tiene name="revision_pasa" => perfecto; si no viniera, lo forzamos:
    if (chkRevision) {
      if (chkRevision.checked) {
        fd.set("revision_pasa", "1");
      } else {
        // si no está checked, lo quitamos para que el controlador lo interprete como 0
        fd.delete("revision_pasa");
      }
    }

    // Asegurar que el select bodega viaje sí o sí con el name esperado por el controlador
    if (selBodega) {
      fd.set("operaciones_partida_bodega", selBodega.value || "");
    }

    xhrGuardar = new XMLHttpRequest();
    xhrGuardar.open("POST", base_url + ENDPOINT_REGISTRAR_FACTURA, true);

xhrGuardar.onreadystatechange = function () {
    
  if (xhrGuardar.readyState !== 4) return;
 console.log(xhrGuardar.status, xhrGuardar.responseText);
  setBtnLoading(false);

  let resp = null;
  try {
    resp = JSON.parse(xhrGuardar.responseText || "{}");
  } catch (e) {
    swalError("Respuesta inválida del servidor al registrar la factura.");
    return;
  }

  if (!resp || resp.ok !== true) {
    swalError(resp && resp.msg ? resp.msg : "No se pudo registrar la factura.");
    return;
  }

  // OK
  const idFactura =
    resp.id_factura ||
    (resp.factura && resp.factura.id_factura) ||
    null;

  if (inpIdFacturaHidden && idFactura) {
    inpIdFacturaHidden.value = String(idFactura);
  }

  swalSuccess(resp.msg || "Factura registrada correctamente.");

  // Opcional: cerrar modal
  // const modalEl = document.getElementById("modalOperacionesPartida");
  // const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
  // if (modal) modal.hide();
};


xhrGuardar.onerror = function () {
  setBtnLoading(false);
  swalError("Error de red al registrar la factura.");
};

    setBtnLoading(true);
    xhrGuardar.send(fd);
  }

  // ===== Bind =====
  document.addEventListener("DOMContentLoaded", function () {
    if (!btnGuardar) return;

    btnGuardar.addEventListener("click", function (e) {
      e.preventDefault();
      registrarFactura();
    });
  });

  // (Opcional) exponer para debug/uso externo
  window.opPartidaRegistrarFactura = registrarFactura;

})();
