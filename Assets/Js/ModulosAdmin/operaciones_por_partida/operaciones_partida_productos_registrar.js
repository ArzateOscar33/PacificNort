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

  // NUEVO: endpoint batch (insert + update)
  const ENDPOINT_GUARDAR_PRODUCTOS = "Operaciones_por_partida/guardarProductos";

  // (Opcional) si tu catálogo usa este evento para recargar:
  const EVT_REFRESH = "opPartida:productos:refresh";

  // ==========================
  // REFS MODAL PRODUCTOS
  // ==========================
  const modalEl = document.getElementById("modalProductosFactura");
  const pfTbody = document.getElementById("pf_tbody");
  const inputFacturaHidden = document.getElementById("pf_invoice_id");
  const btnGuardar = document.getElementById("pf_btnGuardarProductos");
  const btnAgregarLinea = document.getElementById("pf_btnAgregarLinea");
  const tpl = document.getElementById("pf_tplFilaProducto");

  // Totales/labels (opcionales)
  const lblCount = document.getElementById("pf_badgeCount");
  const emptyBox = document.getElementById("pf_empty");
  const metaBox = document.getElementById("pf_meta");

  if (
    !modalEl ||
    !pfTbody ||
    !inputFacturaHidden ||
    !btnGuardar ||
    !btnAgregarLinea ||
    !tpl
  )
    return;

  // ==========================
  // HELPERS
  // ==========================
  function getFacturaId() {
    const v = (inputFacturaHidden.value || "").trim();
    const id = parseInt(v, 10);
    return Number.isFinite(id) ? id : 0;
  }

  function toStr(v) {
    return v === null || v === undefined ? "" : String(v).trim();
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
    try {
      const inst =
        bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      inst.hide();
    } catch (_) {}

    // Limpieza forzada por si el backdrop se queda pegado
    modalEl.classList.remove("show");
    modalEl.setAttribute("aria-hidden", "true");
    modalEl.style.display = "none";

    document.body.classList.remove("modal-open");
    document.body.style.removeProperty("overflow");
    document.body.style.removeProperty("padding-right");

    document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());
  }

  function xhrPostForm(url, formData) {
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", url, true);

      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;

        let res = null;
        try {
          res = JSON.parse(xhr.responseText);
        } catch (_) {}

        if (xhr.status >= 200 && xhr.status < 300) {
          resolve({ okHttp: true, res, raw: xhr.responseText });
        } else {
          resolve({
            okHttp: false,
            res,
            raw: xhr.responseText,
            status: xhr.status,
          });
        }
      };

      xhr.send(formData);
    });
  }

  function featherRefresh() {
    try {
      if (window.feather) feather.replace();
    } catch (_) {}
  }

  function updateMetaUI() {
    const rows = Array.from(pfTbody.querySelectorAll("tr"));
    const count = rows.length;

    if (lblCount) lblCount.textContent = String(count);
    if (metaBox) metaBox.textContent = `Mostrando ${count} de ${count}`;

    if (emptyBox) {
      emptyBox.classList.toggle("d-none", count !== 0);
    }
  }

  // ==========================
  // STATE CONVENTIONS
  // ==========================
  // tr.dataset.state:
  // - "draft" : fila nueva no guardada (INSERT)
  // - "dirty" : fila existente editada (UPDATE)
  // - ""      : fila existente sin cambios

  function markDirty(tr) {
    if (tr.dataset.state !== "draft") tr.dataset.state = "dirty";
  }

  // ==========================
  // ROW BUILDERS
  // ==========================
  function createDraftRow(prefill = {}) {
    const node = tpl.content.cloneNode(true);
    const tr = node.querySelector("tr");
    tr.dataset.state = "draft";
    tr.dataset.idProducto = ""; // nuevo

    // Prefill (opcional)
    tr.querySelector(".pf_descripcion").value = toStr(prefill.descripcion);
    tr.querySelector(".pf_item").value = toStr(prefill.item);
    tr.querySelector(".pf_upc").value = toStr(prefill.upc);
    tr.querySelector(".pf_marca").value = toStr(prefill.marca);
    tr.querySelector(".pf_expiracion").value = toStr(prefill.expiracion);
    tr.querySelector(".pf_inner").value = toStr(prefill.inner_pack);
    tr.querySelector(".pf_case").value = toStr(prefill.case_pack);
    tr.querySelector(".pf_pallets_rcv").value = toStr(
      prefill.pallets_rcv ?? "",
    );
    tr.querySelector(".pf_cajas").value = toStr(prefill.cajas ?? "");
    tr.querySelector(".pf_piezas").value = toStr(prefill.piezas ?? "");
    tr.querySelector(".pf_observaciones").value = toStr(prefill.observaciones);

    return tr;
  }

  function isEditingRow(tr) {
    // Si tiene inputs pf_* en la fila, la consideramos editable (draft o convertida)
    return !!tr.querySelector(
      ".pf_descripcion,.pf_item, .pf_upc, .pf_marca, .pf_expiracion, .pf_inner, .pf_case, .pf_pallets_rcv, .pf_cajas, .pf_piezas , .pf_observaciones",
    );
  }

  function readRowFromInputs(tr) {
    const descripcion = toStr(tr.querySelector(".pf_descripcion")?.value);
    const upc = toStr(tr.querySelector(".pf_upc")?.value);
    const marca = toStr(tr.querySelector(".pf_marca")?.value);
    const expiracion = toStr(tr.querySelector(".pf_expiracion")?.value); // YYYY-MM-DD o ""
    const item = toStr(tr.querySelector(".pf_item")?.value);

    const inner_pack = toStr(tr.querySelector(".pf_inner")?.value);
    const case_pack = toStr(tr.querySelector(".pf_case")?.value);

    const pallets_rcv = toInt(tr.querySelector(".pf_pallets_rcv")?.value);
    const cajas = toInt(tr.querySelector(".pf_cajas")?.value);
    const piezas = toInt(tr.querySelector(".pf_piezas")?.value);
    const observaciones = toStr(tr.querySelector(".pf_observaciones")?.value);

    return {
      descripcion,
      upc,
      marca,
      expiracion,
      inner_pack,
      case_pack,
      pallets_rcv,
      cajas,
      piezas,
      item,
      observaciones,
    };
  }
  function syncDraftCajasRestantes(tr) {
    if (!tr) return;
    if (tr.dataset.state !== "draft") return;

    const inpCajas = tr.querySelector(".pf_cajas");
    const inpRestantes = tr.querySelector(".pf_cajas_restantes");
    if (!inpCajas || !inpRestantes) return;

    const cajas = toInt(inpCajas.value);
    inpRestantes.value = String(cajas);
  }
  function validarRow(row) {
    if (!row.descripcion || !row.upc || !row.marca || !row.item) {
      return "Descripción, UPC, Marca e Item son obligatorios.";
    }
    if (row.pallets_rcv < 0 || row.cajas < 0 || row.piezas < 0) {
      return "Valores numéricos inválidos (no negativos).";
    }
    if (row.observaciones.length > 500) {
      return "Las observaciones no pueden exceder 500 caracteres.";
    }
    // expiración opcional (input date ya valida)
    return "";
  }

  // ==========================
  // CONVERT EXISTING ROW TO EDITABLE
  // ==========================
  // Requisito: tu catálogo debe renderizar filas existentes con:
  // tr.dataset.idProducto = "123"
  // y celdas con clases:
  // .pf_txt_descripcion, .pf_txt_upc, .pf_txt_marca, .pf_txt_expiracion,
  // .pf_txt_inner, .pf_txt_case, .pf_txt_pallets_rcv, .pf_txt_cajas, .pf_txt_piezas
  //
  // Si tu catálogo no tiene esas clases, abajo te digo qué ajustar.

  function getCellText(tr, cls) {
    return toStr(tr.querySelector(cls)?.textContent);
  }

  function replaceCellWithInput(td, type, cls, value, placeholder) {
    td.innerHTML = "";
    const inp = document.createElement("input");
    inp.type = type;
    inp.className = "form-control form-control-sm " + cls;
    if (placeholder) inp.placeholder = placeholder;
    inp.value = toStr(value);
    td.appendChild(inp);
  }

  function ensureActionsButtons(tr) {
    const tdActions =
      tr.querySelector("td[data-pf-actions]") ||
      tr.querySelector("td:last-child");
    if (!tdActions) return;

    // Si ya tiene botones de guardar/cancelar, no duplicar
    if (
      tdActions.querySelector(".pf_btnGuardarFila") &&
      tdActions.querySelector(".pf_btnCancelarEdicion")
    )
      return;

    tdActions.innerHTML = `
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-success pf_btnGuardarFila" title="Aplicar edición">
          <i data-feather="check"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary pf_btnCancelarEdicion" title="Cancelar">
          <i data-feather="x"></i>
        </button>
      </div>
    `;
    featherRefresh();
  }

  function snapshotOriginalRow(tr) {
    // Guardamos snapshot para cancelar
    if (tr.dataset.snapshot === "1") return;

    const tds = Array.from(tr.children);
    const html = tds.map((td) => td.innerHTML);
    tr._pf_snapshot = html;
    tr.dataset.snapshot = "1";
  }

  function restoreSnapshot(tr) {
    if (!tr._pf_snapshot) return;
    const tds = Array.from(tr.children);
    for (let i = 0; i < tds.length; i++) {
      tds[i].innerHTML = tr._pf_snapshot[i];
    }
    tr.dataset.snapshot = "";
    tr._pf_snapshot = null;
    tr.dataset.snapshot = "0";
    // si era dirty y canceló, vuelve a normal
    if (tr.dataset.state === "dirty") tr.dataset.state = "";
    featherRefresh();
  }

  function convertExistingRowToEdit(tr) {
    if (!tr) return;
    if (tr.dataset.state === "draft") return; // ya es editable
    if (isEditingRow(tr)) return; // ya editable

    snapshotOriginalRow(tr);

    // Intentar leer texto desde clases esperadas del catálogo.
    // Si no existen, caerá en "" y tendrás que ajustar tu catálogo.
    const descripcion = getCellText(tr, ".pf_txt_descripcion");
    const item = getCellText(tr, ".pf_txt_item");
    const upc = getCellText(tr, ".pf_txt_upc");
    const marca = getCellText(tr, ".pf_txt_marca");
    const expiracion = getCellText(tr, ".pf_txt_expiracion"); // ideal "YYYY-MM-DD"
    const inner_pack = getCellText(tr, ".pf_txt_inner");
    const case_pack = getCellText(tr, ".pf_txt_case");
    const pallets_rcv = getCellText(tr, ".pf_txt_pallets_rcv");
    const cajas = getCellText(tr, ".pf_txt_cajas");
    const piezas = getCellText(tr, ".pf_txt_piezas");
    const observaciones = getCellText(tr, ".pf_txt_observaciones");

    const tds = Array.from(tr.children);

    // Estructura de tu tabla:
    // 0 desc,1 item,2 upc,3 marca,4 expiración,5 inner,6 case,7 pallets,8 cajas,9 piezas,10 observaciones,11 acciones
    if (tds.length < 12) return;

    replaceCellWithInput(
      tds[0],
      "text",
      "pf_descripcion",
      descripcion,
      "Descripción",
    );
    replaceCellWithInput(tds[1], "text", "pf_item", item, "Item");
    replaceCellWithInput(tds[2], "text", "pf_upc", upc, "UPC");
    replaceCellWithInput(tds[3], "text", "pf_marca", marca, "Marca");
    replaceCellWithInput(tds[4], "date", "pf_expiracion", expiracion, "");
    replaceCellWithInput(tds[5], "text", "pf_inner", inner_pack, "Opcional");
    replaceCellWithInput(tds[6], "text", "pf_case", case_pack, "Opcional");

    // Numéricos
    replaceCellWithInput(
      tds[7],
      "number",
      "pf_pallets_rcv",
      pallets_rcv || "0",
      "0",
    );
    tds[7].querySelector("input").min = "0";
    tds[7].querySelector("input").step = "1";

    replaceCellWithInput(tds[8], "number", "pf_cajas", cajas || "0", "0");
    tds[8].querySelector("input").min = "0";
    tds[8].querySelector("input").step = "1";

    replaceCellWithInput(tds[9], "number", "pf_piezas", piezas || "0", "0");
    tds[9].querySelector("input").min = "0";
    tds[9].querySelector("input").step = "1";

    tds[10].innerHTML = `
  <textarea class="form-control form-control-sm pf_observaciones" rows="2" placeholder="Observaciones">${observaciones}</textarea>
`;

    // Botones guardar/cancelar para la fila
    // Marcamos el td acciones para encontrarlo fácil (si no existía)
    tds[11].setAttribute("data-pf-actions", "1");
    ensureActionsButtons(tr);

    // Marcar dirty
    markDirty(tr);
  }

  function applyRowEdit(tr) {
    // Solo valida y deja la fila como dirty (no guarda aún a BD)
    const row = readRowFromInputs(tr);
    const msg = validarRow(row);
    if (msg) {
      swalError("Validación", msg);
      return false;
    }

    // Mantener como dirty (ya está) y dejar inputs (para guardar batch)
    markDirty(tr);
    return true;
  }

  // ==========================
  // BUILD PAYLOAD (draft + dirty)
  // ==========================
  function collectChanges() {
    const items = [];

    const rows = Array.from(pfTbody.querySelectorAll("tr"));
    for (const tr of rows) {
      const state = tr.dataset.state || "";
      if (state !== "draft" && state !== "dirty") continue;

      // Debe estar en modo inputs para poder leer
      if (!isEditingRow(tr)) continue;

      const data = readRowFromInputs(tr);
      const msg = validarRow(data);
      if (msg) {
        return { ok: false, msg, tr };
      }

      // Si es update, debe traer id_producto
      const idProducto = toInt(tr.dataset.idProducto);

      const item = {
        descripcion: data.descripcion,
        item: data.item,
        upc: data.upc,
        marca: data.marca,
        expiracion: data.expiracion || null,
        inner_pack: data.inner_pack === "" ? null : data.inner_pack,
        case_pack: data.case_pack === "" ? null : data.case_pack,
        pallets_rcv: data.pallets_rcv,
        cajas: data.cajas,
        piezas: data.piezas,
        observaciones: data.observaciones,
      };

      if (state === "dirty") item.id_producto = idProducto; // UPDATE
      // draft: sin id_producto => INSERT

      items.push(item);
    }

    if (items.length === 0) {
      return {
        ok: false,
        msg: "No hay cambios para guardar (no hay filas nuevas ni editadas).",
      };
    }

    return { ok: true, items };
  }

  // ==========================
  // SAVE (BATCH)
  // ==========================
  async function guardarCambiosBatch() {
    if (btnGuardar.dataset.loading === "1") return;

    const facturaId = getFacturaId();
    if (facturaId <= 0) {
      await swalError(
        "Factura inválida",
        "No se encontró un ID de factura válido.",
      );
      return;
    }

    const pack = collectChanges();
    if (!pack.ok) {
      await swalInfo("Sin cambios / Validación", pack.msg);
      return;
    }

    setBtnLoading(true);

    const fd = new FormData();
    fd.append("factura_id", String(facturaId));
    fd.append("items", JSON.stringify(pack.items));

    const url = base_url + ENDPOINT_GUARDAR_PRODUCTOS;
    const resp = await xhrPostForm(url, fd);

    setBtnLoading(false);

    if (!resp.okHttp || !resp.res) {
      await swalError(
        "Error",
        "No se pudo guardar (respuesta inválida del servidor).",
      );
      return;
    }

    if (resp.res.ok !== true) {
      await swalError(
        "Error al guardar",
        String(resp.res.msg || "No se pudo guardar."),
      );
      return;
    }

    const ins = toInt(resp.res.insertados);
    const upd = toInt(resp.res.actualizados);

    await swalSuccess(
      "Guardado",
      `Cambios aplicados. Insertados: ${ins}, Actualizados: ${upd}.`,
    );

    // Notificar a tu catálogo que recargue productos
    document.dispatchEvent(
      new CustomEvent(EVT_REFRESH, { detail: { facturaId } }),
    );

    // Opcional: refrescar facturas (para actualizar #productos)
    if (window.opPartidaListarFacturas) window.opPartidaListarFacturas();

    closeModal();
  }

  // ==========================
  // EVENTS
  // ==========================

  // Agregar fila draft
  btnAgregarLinea.addEventListener("click", async function () {
    const facturaId = getFacturaId();
    if (facturaId <= 0) {
      swalError("Factura inválida", "Abre la factura primero.");
      return;
    }

    // ← NUEVO: alerta si es "Envío sin Revisión"
    if (window.opPartidaEsEnvioSinRevision) {
      const result = await Swal.fire({
        icon: "warning",
        title: "Factura sin revisión",
        html: `Esta factura tiene estatus <strong>"Envío sin Revisión"</strong>.<br>
             Los productos se registrarán como <strong>cantidad dummy (1)</strong> 
             ya que no se revisó el contenido real.`,
        showCancelButton: true,
        confirmButtonText: "Entendido, agregar igual",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#ffc107",
        cancelButtonColor: "#6c757d",
      });
      if (!result.isConfirmed) return;
    }

    const tr = createDraftRow();
    pfTbody.prepend(tr);
    featherRefresh();
    updateMetaUI();
  });
  pfTbody.addEventListener("input", function (ev) {
    const el = ev.target;
    if (!el) return;

    if (el.classList.contains("pf_cajas")) {
      const tr = el.closest("tr");
      syncDraftCajasRestantes(tr);
    }
  });
  // Guardar batch
  btnGuardar.addEventListener("click", function () {
    guardarCambiosBatch();
  });

  // Delegación de eventos en tbody
  pfTbody.addEventListener("click", function (ev) {
    const btn = ev.target.closest("button");
    if (!btn) return;

    const tr = btn.closest("tr");
    if (!tr) return;

    // Eliminar fila draft (solo local)
    if (btn.classList.contains("pf_btnEliminarFila")) {
      if (tr.dataset.state === "draft") {
        tr.remove();
        updateMetaUI();
        return;
      }
      // Si quisieras baja lógica de producto, aquí iría OTRO endpoint.
      swalInfo(
        "Acción no disponible",
        "La eliminación de productos existentes aún no está implementada (solo filas nuevas).",
      );
      return;
    }

    // Editar fila existente (debe existir el botón en catálogo con esta clase)
    if (btn.classList.contains("pf_btnEditarFila")) {
      // Convierte a inputs y la marca dirty
      convertExistingRowToEdit(tr);
      return;
    }

    // Aplicar edición (deja inputs y valida)
    if (btn.classList.contains("pf_btnGuardarFila")) {
      applyRowEdit(tr);
      return;
    }

    // Cancelar edición (revierte snapshot)
    if (btn.classList.contains("pf_btnCancelarEdicion")) {
      restoreSnapshot(tr);
      return;
    }
  });

  // Cuando se abre/cierra modal, actualizar meta UI
  modalEl.addEventListener("shown.bs.modal", function () {
    updateMetaUI();
  });
})();
