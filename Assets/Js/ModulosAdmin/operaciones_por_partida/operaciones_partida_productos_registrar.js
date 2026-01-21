// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_registrar.js
(function () {
  "use strict";

  // ==========================
  // CONFIG / BASE URL
  // ==========================
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // Endpoint del controlador
  const ENDPOINT_REGISTRAR_PRODUCTO = "Operaciones_por_partida/registrarProducto";
  

  // ==========================
  // REFS MODAL PRODUCTOS
  // ==========================
  const modalEl = document.getElementById("modalProductosFactura");
  const pfTbody = document.getElementById("pf_tbody");
  const inputFacturaHidden = document.getElementById("pf_invoice_id");
  const btnGuardar = document.getElementById("pf_btnGuardarProductos");

  if (!modalEl || !pfTbody || !inputFacturaHidden || !btnGuardar) return;

  // ==========================
  // HELPERS
  // ==========================
  function getFacturaId() {
    const v = (inputFacturaHidden.value || "").trim();
    const id = parseInt(v, 10);
    return Number.isFinite(id) ? id : 0;
  }

  function toStr(v) {
    return (v === null || v === undefined) ? "" : String(v).trim();
  }

  function toInt(v) {
    const n = parseInt(String(v ?? "").trim(), 10);
    return Number.isFinite(n) ? n : 0;
  }

  function setBtnLoading(isLoading) {
    btnGuardar.disabled = !!isLoading;
    btnGuardar.dataset.loading = isLoading ? "1" : "0";
  }

  function swalInfo(title, text) {
    if (window.Swal) return Swal.fire({ icon: "info", title, text });
    alert(title + "\n" + text);
  }
  function swalError(title, text) {
    if (window.Swal) return Swal.fire({ icon: "error", title, text });
    alert(title + "\n" + text);
  }
  function swalSuccess(title, text) {
    if (window.Swal) return Swal.fire({ icon: "success", title, text });
    alert(title + "\n" + text);
  }

  function closeModal() {
    // Bootstrap 5
    try {
      const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      inst.hide();
    } catch (_) {
      // fallback: botón dismiss o manual
      modalEl.classList.remove("show");
    }
  }

  function xhrPostForm(url, formData) {
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", url, true);

      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;

        // intentar parsear JSON siempre
        let res = null;
        try { res = JSON.parse(xhr.responseText); } catch (_) {}

        if (xhr.status >= 200 && xhr.status < 300) {
          resolve({ okHttp: true, res, raw: xhr.responseText });
        } else {
          resolve({ okHttp: false, res, raw: xhr.responseText, status: xhr.status });
        }
      };

      xhr.send(formData);
    });
  }

  function getDraftRows() {
    // Tus filas nuevas las marcas como draft en el catálogo:
    // tr.dataset.state = "draft";
    return Array.from(pfTbody.querySelectorAll('tr[data-state="draft"]'));
  }

  function readDraftRow(tr) {
    const descripcion = toStr(tr.querySelector(".pf_descripcion")?.value);
    const upc         = toStr(tr.querySelector(".pf_upc")?.value);
    const marca       = toStr(tr.querySelector(".pf_marca")?.value);
    const expiracion  = toStr(tr.querySelector(".pf_expiracion")?.value); // YYYY-MM-DD o ""

    const inner_pack  = toStr(tr.querySelector(".pf_inner")?.value);
    const case_pack   = toStr(tr.querySelector(".pf_case")?.value);

    const pallets_rcv = toInt(tr.querySelector(".pf_pallets_rcv")?.value);
    const cajas       = toInt(tr.querySelector(".pf_cajas")?.value);
    const piezas      = toInt(tr.querySelector(".pf_piezas")?.value);

    return {
      descripcion, upc, marca, expiracion,
      inner_pack, case_pack,
      pallets_rcv, cajas, piezas
    };
  }

  function validarRow(row) {
    if (!row.descripcion || !row.upc || !row.marca) {
      return "Descripción, UPC y Marca son obligatorios.";
    }
    if (row.pallets_rcv < 0 || row.cajas < 0 || row.piezas < 0) {
      return "Valores numéricos inválidos (no negativos).";
    }
    // expiración es opcional; si viene, debe ser YYYY-MM-DD (el input date ya ayuda)
    return "";
  }

  async function guardarDrafts() {
    if (btnGuardar.dataset.loading === "1") return;

    const facturaId = getFacturaId();
    if (facturaId <= 0) {
      await swalError("Factura inválida", "No se encontró un ID de factura válido para registrar productos.");
      return;
    }

    const drafts = getDraftRows();
    if (drafts.length === 0) {
      await swalInfo("Sin cambios", "No hay renglones nuevos para registrar.");
      return;
    }

    // Validar todo antes de enviar
    for (let i = 0; i < drafts.length; i++) {
      const row = readDraftRow(drafts[i]);
      const msg = validarRow(row);
      if (msg) {
        await swalError("Validación", `Fila ${i + 1}: ${msg}`);
        return; // NO cierra modal
      }
    }

    setBtnLoading(true);

    const url = base_url + ENDPOINT_REGISTRAR_PRODUCTO;

    let okCount = 0;
    let failMsg = "";

    // Registrar 1x1 (tu backend registra un producto por request)
    for (let i = 0; i < drafts.length; i++) {
      const tr = drafts[i];
      const row = readDraftRow(tr);

      const fd = new FormData();
      fd.append("factura_id", String(facturaId));
      fd.append("descripcion", row.descripcion);
      fd.append("upc", row.upc);
      fd.append("marca", row.marca);

      // Opcionales
      if (row.expiracion)  fd.append("expiracion", row.expiracion);
      if (row.inner_pack !== "") fd.append("inner_pack", row.inner_pack);
      if (row.case_pack  !== "") fd.append("case_pack", row.case_pack);

      fd.append("pallets_rcv", String(row.pallets_rcv));
      fd.append("cajas", String(row.cajas));
      fd.append("piezas", String(row.piezas));

      const resp = await xhrPostForm(url, fd);

      // Si HTTP falló o backend ok=false
      if (!resp.okHttp || !resp.res || resp.res.ok !== true) {
        const backendMsg = resp.res?.msg ? String(resp.res.msg) : "";
        failMsg = backendMsg || `No se pudo registrar la fila ${i + 1}.`;
        break;
      }

      okCount++;

      // Si se registró, puedes eliminar la fila draft del DOM
      // (opcional, pero recomendable para no re-enviar)
      tr.remove();
    }

    setBtnLoading(false);

    if (failMsg) {
      // NO cerrar modal
      await swalError("Error al registrar", failMsg);
      return;
    }

    // Si todo OK
    await swalSuccess("Registrado", `Se registraron ${okCount} producto(s) correctamente.`);

    // Refrescar lista (tu catálogo ya escucha este evento)
    document.dispatchEvent(new CustomEvent("opPartida:productos:refresh", {
      detail: { facturaId }
    }));

    // Cerrar modal SOLO si fue exitoso
    closeModal();
  }

  // ==========================
  // EVENTS
  // ==========================
  btnGuardar.addEventListener("click", function () {
    guardarDrafts();
    if (window.opPartidaListarFacturas) window.opPartidaListarFacturas();

  });

})();
