// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_por_partida_rutas_registrar.js
(function () {
  "use strict";

  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // =========================
  // ENDPOINTS (AJUSTA A TU CONTROLADOR)
  // =========================
  // 1) Guardar envíos (múltiples renglones)
  const EP_GUARDAR_ENVIOS = "Operaciones_por_partida_rutas/guardarEnviosRutas";

  // 2) Registrar/alta contenedor físico (ferro) cuando NO existe
  const EP_REGISTRAR_FISICO = "Operaciones_por_partida_rutas/registrarFisicoRutas";

  // =========================
  // REFS DOM (Modal)
  // =========================
  const modalEnvioEl = document.getElementById("modalPartidasTransitoEnvio");
  if (!modalEnvioEl) return;

  const btnAddRow = document.getElementById("partidas_transito_btnAddRow");
  const btnGuardar = document.getElementById("partidas_transito_btnGuardarEnvio");

  const hidProductoId = document.getElementById("partidas_transito_idProducto");
  const hidFacturaId = document.getElementById("partidas_transito_factura");

  const badgeDisponibles = document.getElementById("partidas_transito_badgeDisponibles");
  const hidDisponibles = document.getElementById("partidas_transito_cajasDisponibles");

  const lblTotalAsignado = document.getElementById("partidas_transito_lblTotalAsignado");
  const lblRestantes = document.getElementById("partidas_transito_lblRestantes");

  const tbodyEnvios = document.getElementById("partidas_transito_tbodyEnvios");

  // =========================
  // UTIL
  // =========================
  function esc(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function abortXHR(x) {
    try { if (x && x.readyState !== 4) x.abort(); } catch (_) {}
  }

  function buildUrl(endpoint, paramsObj) {
    const qs = new URLSearchParams();
    Object.keys(paramsObj || {}).forEach((k) => {
      const v = paramsObj[k];
      if (v === undefined || v === null || String(v).trim() === "") return;
      qs.append(k, String(v));
    });
    return base_url + endpoint + (qs.toString() ? ("?" + qs.toString()) : "");
  }

  function postJson(endpoint, payload) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", base_url + endpoint, true);
      xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8");

      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
          reject({ ok: false, msg: "Error HTTP al comunicar con el servidor.", status: xhr.status });
          return;
        }
        let json = null;
        try { json = JSON.parse(xhr.responseText || "{}"); } catch (_) {}
        if (!json) {
          reject({ ok: false, msg: "Respuesta inválida del servidor." });
          return;
        }
        resolve(json);
      };

      xhr.send(JSON.stringify(payload));
    });
  }

  function toastOk(msg) {
    if (window.Swal) {
      Swal.fire({ icon: "success", title: "Listo", text: msg || "Guardado correctamente", timer: 1600, showConfirmButton: false });
      return;
    }
    alert(msg || "Guardado correctamente");
  }

  function toastWarn(msg) {
    if (window.Swal) {
      Swal.fire({ icon: "warning", title: "Atención", text: msg || "Revisa los datos." });
      return;
    }
    alert(msg || "Revisa los datos.");
  }

  function toastErr(msg) {
    if (window.Swal) {
      Swal.fire({ icon: "error", title: "Error", text: msg || "Ocurrió un error." });
      return;
    }
    alert(msg || "Ocurrió un error.");
  }

  function closeModal() {
    try {
      const m = bootstrap.Modal.getOrCreateInstance(modalEnvioEl);
      m.hide();
    } catch (_) {}
  }

  // =========================
  // TEMPLATE ROW
  // Nota: respeta tus clases (pt_destino_txt, pt_fisico_txt, etc.)
  // =========================
  function buildRowHtml(index) {
    return `
      <tr class="partidas_transito_row" data-index="${esc(index)}">
        <td class="text-start">
          <input type="hidden" class="pt_destino_id" value="">
          <div class="position-relative">
            <input type="text"
              class="form-control form-control-sm pt_destino_txt"
              placeholder="Escribe ciudad... (Ej. TIJ)"
              autocomplete="off"
              required>
            <div class="list-group position-absolute w-100 z-3 pt_destino_sug"
              style="z-index:999; display:none;"></div>
          </div>
        </td>

        <td class="text-center">
          <input type="date" class="form-control form-control-sm pt_fecha_envio" required>
        </td>

        <td class="text-start">
          <input type="hidden" class="pt_fisico_id" value="">
          <div class="position-relative">
            <input type="text"
              class="form-control form-control-sm pt_fisico_txt"
              placeholder="Buscar Ferro (Ej. FO-22 / Ferro 17)"
              autocomplete="off"
              required>
            <div class="list-group position-absolute w-100 z-3 pt_fisico_sug"
              style="z-index:999; display:none;"></div>
          </div>
        </td>

        <td class="text-center">
          <div class="input-group input-group-sm">
            <input type="number" min="1" step="1"
              class="form-control pt_cajas"
              placeholder="0"
              required>
          </div>
        </td>

        <td class="text-center">
          <select class="form-select form-control pt_estatus" required>
            <option value="1" selected>En camino</option>
            <option value="2">Entregado</option>
          </select>
        </td>

        <td class="text-start">
          <input type="text"
            class="form-control form-control-sm pt_nota"
            placeholder="Nota (opcional)"
            maxlength="255">
        </td>

        <td class="text-center">
          <button type="button" class="btn btn-outline-danger btn-sm pt_btnRemoveRow" title="Quitar renglón">
            <i data-feather="trash-2"></i>
          </button>
        </td>
      </tr>
    `;
  }

  // =========================
  // TOTALES UI
  // =========================
  function getDisponibles() {
    const n1 = Number(hidDisponibles?.value ?? 0) || 0;
    const n2 = Number(badgeDisponibles?.textContent ?? 0) || 0;
    return Math.max(n1, n2, 0);
  }

  function calcTotalAsignado() {
    if (!tbodyEnvios) return 0;
    let total = 0;
    tbodyEnvios.querySelectorAll(".partidas_transito_row").forEach((row) => {
      const v = Number(row.querySelector(".pt_cajas")?.value ?? 0) || 0;
      total += v;
    });
    return total;
  }

  function refreshTotals() {
    const disp = getDisponibles();
    const asign = calcTotalAsignado();
    const rest = Math.max(disp - asign, 0);

    if (lblTotalAsignado) lblTotalAsignado.textContent = String(asign);
    if (lblRestantes) lblRestantes.textContent = String(rest);
  }

  // =========================
  // ROWS: add/remove
  // =========================
  function addRow() {
    if (!tbodyEnvios) return;

    const idx = tbodyEnvios.querySelectorAll(".partidas_transito_row").length;
    tbodyEnvios.insertAdjacentHTML("beforeend", buildRowHtml(idx));

    try { if (window.feather) window.feather.replace(); } catch (_) {}
    refreshTotals();
  }

  function removeRow(btn) {
    const row = btn?.closest(".partidas_transito_row");
    if (!row) return;
    row.remove();
    refreshTotals();
  }

  // =========================
  // BUILD PAYLOAD
  // =========================
  function collectRows() {
    if (!tbodyEnvios) return [];

    const rows = [];
    const trs = tbodyEnvios.querySelectorAll(".partidas_transito_row");

    trs.forEach((row, i) => {
      const destino_id = (row.querySelector(".pt_destino_id")?.value || "").trim();
      const destino_txt = (row.querySelector(".pt_destino_txt")?.value || "").trim();

      const fecha_envio = (row.querySelector(".pt_fecha_envio")?.value || "").trim();

      const fisico_id = (row.querySelector(".pt_fisico_id")?.value || "").trim();
      const fisico_txt = (row.querySelector(".pt_fisico_txt")?.value || "").trim();

      const cajas = Number(row.querySelector(".pt_cajas")?.value ?? 0) || 0;
      const estatus = (row.querySelector(".pt_estatus")?.value || "").trim();

      const nota = (row.querySelector(".pt_nota")?.value || "").trim();

      rows.push({
        _idx: i,
        destino_id,      // preferido si existe
        destino_txt,     // fallback por si quieres registrar por texto
        fecha_envio,
        fisico_id,       // preferido si existe
        fisico_txt,      // fallback: alta física
        cajas,
        estatus,
        nota
      });
    });

    return rows;
  }

  function validateBeforeSave() {
    const factura_id = Number(hidFacturaId?.value ?? 0) || 0;
    const producto_id = Number(hidProductoId?.value ?? 0) || 0;

    if (factura_id <= 0 || producto_id <= 0) {
      toastWarn("No se detectó factura/producto. Cierra y vuelve a abrir el modal desde el botón de envío.");
      return null;
    }

    const rows = collectRows();
    if (!rows.length) {
      toastWarn("No hay renglones para guardar.");
      return null;
    }

    // Validaciones por renglón
    for (const r of rows) {
      if (!r.fecha_envio) {
        toastWarn(`Falta la fecha de envío en el renglón ${r._idx + 1}.`);
        return null;
      }

      // Destino: idealmente debes traer id_ciudad por sugerencias
      if (!r.destino_id && !r.destino_txt) {
        toastWarn(`Falta el destino (ciudad) en el renglón ${r._idx + 1}.`);
        return null;
      }

      // Físico: si no hay id, debe haber texto para intentar alta
      if (!r.fisico_id && !r.fisico_txt) {
        toastWarn(`Falta el Ferro (contenedor físico) en el renglón ${r._idx + 1}.`);
        return null;
      }

      if (!Number.isFinite(r.cajas) || r.cajas <= 0) {
        toastWarn(`Las cajas a enviar deben ser mayor a 0 en el renglón ${r._idx + 1}.`);
        return null;
      }

      if (!r.estatus) {
        toastWarn(`Falta el estatus en el renglón ${r._idx + 1}.`);
        return null;
      }
    }

    // Total asignado <= disponibles
    const disp = getDisponibles();
    const total = rows.reduce((a, b) => a + (Number(b.cajas) || 0), 0);
    if (total > disp) {
      toastWarn(`Estás asignando ${total} cajas, pero solo hay ${disp} disponibles.`);
      return null;
    }

    return { factura_id, producto_id, rows };
  }

  // =========================
  // ALTA FÍSICOS (si falta id_fisico)
  // =========================
  async function ensureFisicos(rows) {
    // Dedup por texto (numero_ferro)
    const need = new Map(); // key: fisico_txt_norm -> {txt, rowsIdx[]}
    rows.forEach((r) => {
      if (r.fisico_id) return;
      const key = String(r.fisico_txt || "").trim().toUpperCase();
      if (!key) return;
      if (!need.has(key)) need.set(key, { txt: r.fisico_txt.trim(), idxs: [] });
      need.get(key).idxs.push(r._idx);
    });

    if (need.size === 0) return rows;

    // Registrar uno por uno (para obtener id_fisico)
    for (const [key, info] of need.entries()) {
      // Payload mínimo: numero_ferro
      // Ajusta en tu Controller/Model según tu tabla contenedores_fisicos
      const res = await postJson(EP_REGISTRAR_FISICO, {
        numero_ferro: info.txt
      });

      if (!res || !res.ok) {
        throw new Error(res?.msg || `No se pudo registrar el ferro "${info.txt}".`);
      }

      // Esperamos que el backend regrese el id nuevo o existente:
      // { ok:true, data:{ id_fisico: 123, numero_ferro:'FO-22' } }
      const newId = res?.data?.id_fisico ?? res?.data?.id ?? 0;
      if (!newId) {
        throw new Error(`El servidor no devolvió id_fisico para "${info.txt}".`);
      }

      // Asignar ese id a todos los renglones que lo usaban
      info.idxs.forEach((idx) => {
        const row = rows.find((x) => x._idx === idx);
        if (row) row.fisico_id = String(newId);
      });
    }

    return rows;
  }

  // =========================
  // GUARDAR ENVIOS
  // =========================
  let saving = false;

  async function guardarEnvios() {
    if (saving) return;

    const data = validateBeforeSave();
    if (!data) return;

    saving = true;
    if (btnGuardar) btnGuardar.disabled = true;

    try {
      // 1) Asegurar id_fisico (alta si falta)
      const rowsWithFisico = await ensureFisicos(data.rows);

      // 2) Payload final para guardar envíos
      // Ajusta nombres de llaves si tu backend espera otro esquema.
      const payload = {
        factura_id: data.factura_id,
        producto_id: data.producto_id,
        envios: rowsWithFisico.map((r) => ({
          destino_id: r.destino_id || null,     // recomendado
          destino_txt: r.destino_txt || null,   // fallback si decides permitirlo
          fecha_envio: r.fecha_envio,
          id_fisico: Number(r.fisico_id) || null,
          cajas_enviadas: Number(r.cajas) || 0,
          estatus: Number(r.estatus) || 1,
          notas: r.nota || ""
        }))
      };

      const res = await postJson(EP_GUARDAR_ENVIOS, payload);

      if (!res || !res.ok) {
        toastWarn(res?.msg || "No se pudieron guardar los envíos.");
        return;
      }

      toastOk(res?.msg || "Envíos registrados correctamente.");
      closeModal();

      // Opcional: si tu catálogo necesita refrescar productos después del guardado,
      // lanza un evento y que el catálogo lo escuche.
      document.dispatchEvent(new CustomEvent("opp_rutas_envios_guardados", {
        detail: { factura_id: data.factura_id, producto_id: data.producto_id }
      }));

    } catch (err) {
      toastErr(err?.message || "Ocurrió un error al guardar.");
    } finally {
      saving = false;
      if (btnGuardar) btnGuardar.disabled = false;
    }
  }

  // =========================
  // EVENTS
  // =========================
  function initRegistrar() {
    // + Agregar renglón
    if (btnAddRow) {
      btnAddRow.addEventListener("click", function () {
        addRow();
      });
    }

    // Quitar renglón
    if (tbodyEnvios) {
      tbodyEnvios.addEventListener("click", function (e) {
        const btn = e.target?.closest(".pt_btnRemoveRow");
        if (!btn) return;

        // Evitar dejar el modal sin renglones: si queda vacío, agrega uno
        removeRow(btn);
        const count = tbodyEnvios.querySelectorAll(".partidas_transito_row").length;
        if (count === 0) addRow();
      });

      // Recalcular totales al cambiar cajas
      tbodyEnvios.addEventListener("input", function (e) {
        if (e.target?.classList?.contains("pt_cajas")) {
          refreshTotals();
        }
      });
    }

    // Guardar
    if (btnGuardar) {
      btnGuardar.addEventListener("click", function () {
        guardarEnvios();
      });
    }

    // Al abrir el modal: asegurar mínimo 1 renglón y totales correctos
    modalEnvioEl.addEventListener("shown.bs.modal", function () {
      if (!tbodyEnvios) return;
      const count = tbodyEnvios.querySelectorAll(".partidas_transito_row").length;
      if (count === 0) addRow();
      refreshTotals();
      try { if (window.feather) window.feather.replace(); } catch (_) {}
    });
  }

  initRegistrar();

})();
