// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_factura_registrar.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ===== Endpoints =====
  const ENDPOINT_REGISTRAR_FACTURA = "Operaciones_por_partida/registrar";
  const ENDPOINT_GET_FACTURA = "Operaciones_por_partida/getFactura";
  const ENDPOINT_ACTUALIZAR_FACTURA = "Operaciones_por_partida/actualizar";
  const ENDPOINT_BAJA_FACTURA = "Operaciones_por_partida/baja";

  const tbodyFacturas = document.getElementById(
    "operaciones_partida_facturasBody",
  );

  // ===== Refs (IDs existentes) =====
  const formFactura = document.getElementById("formOperacionesPartida");
  const btnGuardar = document.getElementById(
    "operaciones_partida_btnGuardarEncabezado",
  );
  const btnNueva = document.getElementById(
    "operaciones_partida_btnNuevaFactura",
  );

  const selBodega = document.getElementById("operaciones_partida_bodega");
  const selCliente = document.getElementById("operaciones_partida_cliente");
  const chkRevision = document.getElementById("operaciones_partida_revision");
  const inpPallets = document.getElementById("operaciones_partida_pallets_inv");
  const inpFactura = document.getElementById("operaciones_partida_factura");
  const inpProveedor = document.getElementById("operaciones_partida_proveedor");
  const inpFecha = document.getElementById("operaciones_partida_fechaRecibido");
  const inpNotas = document.getElementById("operaciones_partida_notas");

  // Hidden id factura en tu form (name="operaciones_partida_id")
  const inpIdFacturaHidden = document.getElementById("operaciones_partida_id");

  // Modal element
  const modalEl = document.getElementById("modalOperacionesPartida");

  let xhrReq = null;

  // ===== Helpers =====
  function setBtnLoading(isLoading) {
    if (!btnGuardar) return;
    btnGuardar.disabled = !!isLoading;
  }

  function swalSuccess(msg) {
    if (typeof Swal === "undefined") {
      alert(msg);
      return;
    }
    Swal.fire({
      icon: "success",
      title: "Correcto",
      text: msg,
      confirmButtonText: "Aceptar",
      confirmButtonColor: "#198754",
    });
  }

  function swalError(msg) {
    if (typeof Swal === "undefined") {
      alert(msg);
      return;
    }
    Swal.fire({
      icon: "error",
      title: "Error",
      text: msg,
      confirmButtonText: "Aceptar",
      confirmButtonColor: "#dc3545",
    });
  }

  function swalWarning(msg) {
    if (typeof Swal === "undefined") {
      alert(msg);
      return;
    }
    Swal.fire({
      icon: "warning",
      title: "Atención",
      text: msg,
      confirmButtonText: "Aceptar",
      confirmButtonColor: "#ffc107",
    });
  }

  function validarEncabezado() {
    const bodegaId = selBodega ? String(selBodega.value || "").trim() : "";
    const clienteId = selCliente ? String(selCliente.value || "").trim() : "";
    const factura = inpFactura ? String(inpFactura.value || "").trim() : "";
    const proveedor = inpProveedor
      ? String(inpProveedor.value || "").trim()
      : "";

    if (!bodegaId) return "Selecciona una bodega.";
    if (!clienteId) return "Selecciona un cliente.";
    if (!factura) return "El número de factura es obligatorio.";
    if (!proveedor) return "El proveedor es obligatorio.";

    if (inpPallets) {
      const n = parseInt(inpPallets.value || "0", 10);
      if (isNaN(n) || n < 0) return "Pallets INV (Factura) debe ser 0 o mayor.";
    }

    return null;
  }

  function abrirModalFactura() {
    if (!modalEl || typeof bootstrap === "undefined") return;

    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();
  }

  function setModoCrear() {
    // limpiar hidden id y form
    if (inpIdFacturaHidden) inpIdFacturaHidden.value = "";
    if (formFactura) formFactura.reset();

    // título
    const titulo = document.getElementById("operaciones_partida_tituloModal");
    if (titulo) titulo.textContent = "Nueva Factura";
  }

  function setModoEditar() {
    const titulo = document.getElementById("operaciones_partida_tituloModal");
    if (titulo) titulo.textContent = "Editar Factura";
  }

  // ===== Cargar factura para editar =====
  function cargarFacturaParaEditar(idFactura) {
    const id = parseInt(String(idFactura || "0"), 10);
    if (!id || id <= 0) {
      swalWarning("ID de factura inválido.");
      return;
    }

    // abortar request previo si existe
    if (xhrReq && xhrReq.readyState !== 4) {
      try {
        xhrReq.abort();
      } catch (_) {}
    }

    setBtnLoading(true);

    xhrReq = new XMLHttpRequest();
    xhrReq.open(
      "GET",
      base_url + ENDPOINT_GET_FACTURA + "?id_factura=" + encodeURIComponent(id),
      true,
    );

    xhrReq.onreadystatechange = function () {
      if (xhrReq.readyState !== 4) return;
      //console.log(this.responseText);
      setBtnLoading(false);

      let resp = null;
      try {
        resp = JSON.parse(xhrReq.responseText || "{}");
      } catch (e) {
        swalError("Respuesta inválida del servidor al obtener la factura.");
        return;
      }

      if (!resp || resp.ok !== true || !resp.factura) {
        swalError(
          resp && resp.msg ? resp.msg : "No se pudo obtener la factura.",
        );
        return;
      }

      const f = resp.factura;

      // llenar form
      if (inpIdFacturaHidden)
        inpIdFacturaHidden.value = String(f.id_factura || "");

      if (selBodega) selBodega.value = String(f.bodega_id || "");
      if (selCliente) selCliente.value = String(f.cliente_id || "");
      if (inpFactura) inpFactura.value = String(f.numero_factura || "");
      if (inpProveedor) inpProveedor.value = String(f.proveedor || "");
      if (inpPallets) inpPallets.value = String(f.pallets_inv ?? 0);

      // fecha debe venir como YYYY-MM-DD desde el modelo getFacturaByIdEditar
      if (inpFecha)
        inpFecha.value = f.fecha_recibido ? String(f.fecha_recibido) : "";

      if (inpNotas) inpNotas.value = f.notas ? String(f.notas) : "";

      if (chkRevision)
        chkRevision.checked =
          String(f.revision_pasa) === "1" || f.revision_pasa === 1;

      setModoEditar();
      abrirModalFactura();
    };

    xhrReq.onerror = function () {
      setBtnLoading(false);
      swalError("Error de red al obtener la factura.");
    };

    xhrReq.send(null);
  }

  // ===== Guardar encabezado (crear o actualizar) =====
  function guardarEncabezadoFactura() {
    if (!formFactura) return;

    const err = validarEncabezado();
    if (err) {
      swalWarning(err);
      return;
    }

    // abortar request previo si existe
    if (xhrReq && xhrReq.readyState !== 4) {
      try {
        xhrReq.abort();
      } catch (_) {}
    }

    const fd = new FormData(formFactura);

    // checkbox: si no está checked, no viaja => controlador lo interpreta como 0
    if (chkRevision) {
      if (chkRevision.checked) fd.set("revision_pasa", "1");
      else fd.delete("revision_pasa");
    }

    // asegurar bodega con name esperado
    if (selBodega) {
      fd.set("operaciones_partida_bodega", selBodega.value || "");
    }
    if (selCliente) {
      fd.set("operaciones_partida_cliente", selCliente.value || "");
    }

    const idEdit = inpIdFacturaHidden
      ? String(inpIdFacturaHidden.value || "").trim()
      : "";
    const endpoint = idEdit
      ? ENDPOINT_ACTUALIZAR_FACTURA
      : ENDPOINT_REGISTRAR_FACTURA;

    xhrReq = new XMLHttpRequest();
    xhrReq.open("POST", base_url + endpoint, true);

    xhrReq.onreadystatechange = function () {
      if (xhrReq.readyState !== 4) return;
      //console.log(this.responseText);
      setBtnLoading(false);

      let resp = null;
      try {
        resp = JSON.parse(xhrReq.responseText || "{}");
      } catch (e) {
        swalError("Respuesta inválida del servidor.");
        return;
      }

      if (!resp || resp.ok !== true) {
        swalError(
          resp && resp.msg ? resp.msg : "No se pudo guardar la factura.",
        );
        return;
      }

      // Si fue registro, guardar id en hidden
      if (!idEdit) {
        const newId =
          resp.id_factura || (resp.factura && resp.factura.id_factura) || null;

        if (inpIdFacturaHidden && newId)
          inpIdFacturaHidden.value = String(newId);
      }

      swalSuccess(
        resp.msg ||
          (idEdit
            ? "Factura actualizada correctamente."
            : "Factura registrada correctamente."),
      );

      //  refrescar listado
      if (window.opPartidaListarFacturas) window.opPartidaListarFacturas();
      modalEl && bootstrap.Modal.getInstance(modalEl).hide();
    };

    xhrReq.onerror = function () {
      setBtnLoading(false);
      swalError("Error de red al guardar la factura.");
    };

    setBtnLoading(true);
    xhrReq.send(fd);
  }

  // ===== Bindings =====
  document.addEventListener("DOMContentLoaded", function () {
    if (btnGuardar) {
      btnGuardar.addEventListener("click", function (e) {
        e.preventDefault();
        guardarEncabezadoFactura();
      });
    }

    // Al abrir "Nueva Factura", limpiar modal
    if (btnNueva) {
      btnNueva.addEventListener("click", function () {
        setModoCrear();
      });
    }
    if (tbodyFacturas) {
      tbodyFacturas.addEventListener("click", function (e) {
        const btn = e.target.closest(".btnEliminarFactura");
        if (!btn) return;

        e.preventDefault();

        const idFactura = parseInt(btn.getAttribute("data-id") || "0", 10);
        if (!idFactura) {
          swalError("No se pudo identificar la factura.");
          return;
        }

        confirmarBajaFactura(idFactura);
      });
    }
  });

  function confirmarBajaFactura(idFactura) {
    Swal.fire({
      icon: "warning",
      title: "Dar de baja factura",
      text: "Esta acción ocultará la factura del listado, pero conservará sus productos. ¿Deseas continuar?",
      showCancelButton: true,
      confirmButtonText: "Sí, dar de baja",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
    }).then((result) => {
      if (result.isConfirmed) {
        bajaFactura(idFactura);
      }
    });
  }

  function bajaFactura(idFactura) {
    const id = parseInt(String(idFactura || "0"), 10);
    if (!id || id <= 0) {
      swalError("ID de factura inválido.");
      return;
    }

    // abortar request previo si existe
    if (xhrReq && xhrReq.readyState !== 4) {
      try {
        xhrReq.abort();
      } catch (_) {}
    }

    const fd = new FormData();
    fd.set("id_factura", String(id));

    xhrReq = new XMLHttpRequest();
    xhrReq.open("POST", base_url + ENDPOINT_BAJA_FACTURA, true);

    xhrReq.onreadystatechange = function () {
      if (xhrReq.readyState !== 4) return;

      let resp = null;
      try {
        resp = JSON.parse(xhrReq.responseText || "{}");
      } catch (e) {
        swalError("Respuesta inválida del servidor al dar de baja.");
        return;
      }

      if (!resp || resp.ok !== true) {
        swalError(
          resp && resp.msg ? resp.msg : "No se pudo dar de baja la factura.",
        );
        return;
      }

      swalSuccess(resp.msg || "Factura dada de baja correctamente.");

      // Refrescar lista del catálogo
      if (typeof window.opPartidaListarFacturas === "function") {
        window.opPartidaListarFacturas({ resetPage: false });
      }
    };

    xhrReq.onerror = function () {
      swalError("Error de red al dar de baja la factura.");
    };

    xhrReq.send(fd);
  }

  // Delegación: botón editar en tabla (render dinámico)
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btnEditarFactura");
    if (!btn) return;

    e.preventDefault();
    const id = btn.getAttribute("data-id") || "";
    cargarFacturaParaEditar(id);
  });

  // Exponer para debug si lo necesitas
  window.opPartidaGuardarEncabezadoFactura = guardarEncabezadoFactura;
  window.opPartidaCargarFacturaParaEditar = cargarFacturaParaEditar;
})();
