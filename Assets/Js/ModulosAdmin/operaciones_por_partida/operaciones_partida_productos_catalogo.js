// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_productos_catalogo.js
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

  const ENDPOINT_LISTAR_PRODUCTOS = "Operaciones_por_partida/listarProductos";
  let facturaEsEnvioSinRevision = false;
  // ==========================
  // REFS MODAL PRODUCTOS
  // ==========================
  const modalEl = document.getElementById("modalProductosFactura");

  const inputFacturaHidden = document.getElementById("pf_invoice_id");
  const pfTbody = document.getElementById("pf_tbody");
  const pfEmpty = document.getElementById("pf_empty");
  const pfMeta = document.getElementById("pf_meta");
  const pfBuscar = document.getElementById("pf_buscar");

  const pfBadgeCount = document.getElementById("pf_badgeCount");
  const pfTotalCajas = document.getElementById("pf_totalCajas");
  const pfTotalPiezas = document.getElementById("pf_totalPiezas");
  const pfTotalPallets = document.getElementById("pf_totalPalletsRcv");
  const pfTotalCajasRestantes = document.getElementById(
    "pf_totalCajasRestantes",
  );

  // Labels header del modal
  const lblId = document.getElementById("pf_lblFactura");
  const lblVendor = document.getElementById("pf_lblProveedor");
  const lblXdock = document.getElementById("pf_lblXdock");
  const lblRec = document.getElementById("pf_lblRecibido");
  const lblRev = document.getElementById("pf_lblRevision");
  const lblPal = document.getElementById("pf_lblPalletsRcv");

  // ===== Refs del modal =====
  const tbody = document.getElementById("pf_tbody");
  const empty = document.getElementById("pf_empty");
  const tplFila = document.getElementById("pf_tplFilaProducto");

  // Si esta vista no tiene el modal, no hacemos nada
  if (!tbody || !tplFila) return;

  function ocultarEmpty() {
    if (!empty) return;
    empty.classList.add("d-none");
  }

  function mostrarEmptySiNoHayFilas() {
    if (!empty) return;
    if (tbody.children.length === 0) empty.classList.remove("d-none");
    else empty.classList.add("d-none");
  }

  function agregarFilaProducto() {
    // 1) Ocultar placeholder "No hay productos"
    ocultarEmpty();

    // 2) Clonar el template
    const fragmento = tplFila.content.cloneNode(true);
    const tr = fragmento.querySelector("tr");
    if (!tr) return;

    // (Opcional) Marcar como fila nueva/draft
    tr.dataset.state = "draft";

    // 3) Insertar fila (arriba o abajo, tú decides)
    // Arriba:
    tbody.insertBefore(tr, tbody.firstChild);
    // Abajo (alternativa):
    // tbody.appendChild(tr);

    // 4) Re-render iconos feather
    if (window.feather) window.feather.replace();

    // 5) Enfocar el primer input (Descripción)
    const inpDesc = tr.querySelector(".pf_descripcion");
    if (inpDesc) inpDesc.focus();

    // 6) Asegurar que el empty se muestre/oculte correctamente
    mostrarEmptySiNoHayFilas();
  }

  // (Opcional) Si el modal se abre sin filas, mostrar empty

  if (modalEl) {
    modalEl.addEventListener("shown.bs.modal", function () {
      mostrarEmptySiNoHayFilas();
    });
  }

  // Guard rails
  if (!pfTbody) {
    // Si el modal no existe en esta vista, no hacemos nada
    return;
  }

  // ==========================
  // ESTADO MODAL
  // ==========================
  let facturaIdActual = 0;
  let page = 1;
  let perPage = 200; // modal suele traer todo
  let debounce = null;
  let xhrActual = null;

  // ==========================
  // HELPERS
  // ==========================
  function esc(s) {
    if (s === null || s === undefined) return "";
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function fmtDateToDDMMMYY(dateStr) {
    if (!dateStr) return "—";
    const part = String(dateStr).slice(0, 10);
    const [y, m, d] = part.split("-");
    if (!y || !m || !d) return esc(dateStr);

    const months = [
      "ene",
      "feb",
      "mar",
      "abr",
      "may",
      "jun",
      "jul",
      "ago",
      "sep",
      "oct",
      "nov",
      "dic",
    ];
    const mm = parseInt(m, 10);
    const yy = String(y).slice(-2);
    return `${parseInt(d, 10)}-${months[mm - 1 || 0]}-${yy}`;
  }

  function buildQuery(params) {
    const qp = [];
    Object.keys(params).forEach((k) => {
      const v = params[k];
      if (v === null || v === undefined) return;
      qp.push(encodeURIComponent(k) + "=" + encodeURIComponent(String(v)));
    });
    return qp.join("&");
  }

  function setLoading() {
    pfTbody.innerHTML = `
      <tr class="text-center">
        <td colspan="14" class="py-4 text-muted">Cargando...</td>
      </tr>`;
    if (pfEmpty) pfEmpty.classList.add("d-none");
  }

  function renderEmpty() {
    pfTbody.innerHTML = "";
    if (pfEmpty) pfEmpty.classList.remove("d-none");

    if (pfBadgeCount) pfBadgeCount.textContent = "0";
    if (pfTotalCajas) pfTotalCajas.textContent = "Cajas: 0";
    if (pfTotalPiezas) pfTotalPiezas.textContent = "Piezas: 0";
    if (pfTotalPallets) pfTotalPallets.textContent = "Pallets RCV: 0";
    if (pfTotalCajasRestantes)
      pfTotalCajasRestantes.textContent = "Cajas restantes: 0";
    if (pfMeta) pfMeta.textContent = "Mostrando 0 de 0";
  }

  function renderRows(rows) {
    if (!rows || rows.length === 0) {
      renderEmpty();
      if (window.feather) window.feather.replace();
      return;
    }

    if (pfEmpty) pfEmpty.classList.add("d-none");

    let html = "";
    rows.forEach((p) => {
      const idDetalle =
        p.id ?? p.id_producto ?? p.id_detalle ?? p.detalle_id ?? "";
      const descripcion = p.descripcion ?? "—";
      const upc = p.upc ?? "—";
      const marca = p.marca ?? "—";
      const expiracion = p.expiracion ? fmtDateToDDMMMYY(p.expiracion) : "—";
      const innerPack =
        (p.inner_pack ?? "") === null ? "" : (p.inner_pack ?? "");
      const casePack = (p.case_pack ?? "") === null ? "" : (p.case_pack ?? "");
      const palletsRcv = p.pallets_rcv ?? 0;
      const cajas = p.cajas ?? 0;
      const cajasRestantes = p.cajas_restantes ?? 0;
      const piezas = p.piezas ?? 0;
      const observaciones = p.observaciones ?? "";

      const fotosJson = JSON.stringify(p.fotos || []).replace(/'/g, "&#039;");
      const fotosCount = (p.fotos || []).length;

      html += `
    <tr class="text-center"
    data-id="${esc(idDetalle)}"
    data-expiracion="${esc((p.expiracion || "").slice(0, 10))}"
    data-observaciones="${esc(observaciones)}"
    data-fotos='${fotosJson}'>
    <td class="text-start">${esc(descripcion)}</td>
    <td>${esc(p.item ?? "—")}</td>
    <td>${esc(upc)}</td>
    <td>${esc(marca)}</td>
    <td>${esc(expiracion)}</td>
    <td>${esc(innerPack)}</td>
    <td>${esc(casePack)}</td>
    <td>${esc(palletsRcv)}</td>
    <td>${esc(cajas)}</td>
    <td>${esc(cajasRestantes)}</td>
    <td>${esc(piezas)}</td>
    <td class="text-start">${esc(observaciones || "")}</td>
<td class="pf_celdaFotos">
      ${
        fotosCount > 0
          ? `<span class="badge bg-info text-dark">${fotosCount} foto${fotosCount > 1 ? "s" : ""}</span>`
          : `<span class="text-muted small">Sin fotos</span>`
      }
    </td>

    <td>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-info pf_btnFotos"
          data-id="${esc(idDetalle)}"
          data-descripcion="${esc(descripcion)}"
          title="Ver/subir fotos">
          <i data-feather="camera"></i>
        </button>
        <button type="button" class="btn btn-outline-warning pf_btnEditar" data-id="${esc(idDetalle)}" title="Editar">
          <i data-feather="edit"></i>
        </button>
        <button type="button" class="btn btn-outline-danger pf_btnEliminar" data-id="${esc(idDetalle)}" title="Eliminar">
          <i data-feather="trash-2"></i>
        </button>
      </div>
    </td>
   
  </tr>
`;
    });

    pfTbody.innerHTML = html;
    if (window.feather) window.feather.replace();
  }

  function renderTotals(meta, totals) {
    const total = meta?.total ?? 0;
    if (pfBadgeCount) pfBadgeCount.textContent = String(total);

    if (pfTotalCajas)
      pfTotalCajas.textContent = `Cajas: ${totals?.total_cajas ?? 0}`;
    if (pfTotalPiezas)
      pfTotalPiezas.textContent = `Piezas: ${totals?.total_piezas ?? 0}`;
    if (pfTotalPallets)
      pfTotalPallets.textContent = `Pallets RCV: ${totals?.total_pallets_rcv ?? 0}`;
    if (pfTotalCajasRestantes)
      pfTotalCajasRestantes.textContent = `Cajas restantes: ${totals?.total_cajas_restantes ?? 0}`;

    const showing =
      meta?.page && meta?.per_page
        ? Math.min(total, meta.page * meta.per_page)
        : total;

    if (pfMeta) pfMeta.textContent = `Mostrando ${showing} de ${total}`;
  }

  // ==========================
  // INLINE EDIT (SOLO UI)
  // Convierte un renglón en inputs y permite cancelar
  // ==========================

  // Solo 1 fila en edición a la vez (opcional pero recomendado)
  function cancelarOtrasEdiciones(exceptTr) {
    const editando = pfTbody.querySelectorAll('tr[data-editing="1"]');
    editando.forEach((tr) => {
      if (tr !== exceptTr) cancelarEdicionFila(tr);
    });
  }

  function entrarEdicionFila(tr) {
    if (!tr) return;

    // Si ya está editando, no lo vuelve a convertir
    if (tr.dataset.editing === "1") return;

    cancelarOtrasEdiciones(tr);

    // Guardar snapshot para cancelar
    tr.dataset.editing = "1";
    tr.dataset.originalHtml = tr.innerHTML;

    const tds = tr.querySelectorAll("td");
    // Estructura esperada en tu tabla:
    // 0 descripcion, 1 item, 2 upc, 3 marca, 4 expiracion, 5 inner, 6 case,
    // 7 pallets_rcv, 8 cajas, 9 cajas restantes, 10 piezas, 11 observaciones, 12 acciones
    if (tds.length < 13) return;

    const getText = (i) => (tds[i]?.textContent || "").trim();

    const descripcion = getText(0);
    const item = getText(1);
    const upc = getText(2);
    const marca = getText(3);

    // Para date, usa el raw si lo guardaste en data-expiracion (YYYY-MM-DD)
    const expiracionRaw = (tr.dataset.expiracion || "").trim();

    const innerPack = getText(5);
    const casePack = getText(6);
    const palletsRcv = getText(7);
    const cajas = getText(8);
    // const cajasRestantes = getText(9); // solo visual, no editable
    const piezas = getText(10);
    const observaciones = (
      tr.dataset.observaciones ||
      getText(11) ||
      ""
    ).trim();

    tds[0].innerHTML = `<input type="text" class="form-control form-control-sm pf_descripcion" value="${esc(descripcion)}">`;
    tds[1].innerHTML = `<input type="text" class="form-control form-control-sm pf_item" value="${esc(item)}">`;
    tds[2].innerHTML = `<input type="text" class="form-control form-control-sm pf_upc" value="${esc(upc)}">`;
    tds[3].innerHTML = `<input type="text" class="form-control form-control-sm pf_marca" value="${esc(marca)}">`;

    tds[4].innerHTML = `<input type="date" class="form-control form-control-sm pf_expiracion" value="${esc(expiracionRaw)}">`;

    tds[5].innerHTML = `<input type="text" class="form-control form-control-sm pf_inner" value="${esc(innerPack)}">`;
    tds[6].innerHTML = `<input type="text" class="form-control form-control-sm pf_case" value="${esc(casePack)}">`;

    tds[7].innerHTML = `<input type="number" min="0" step="1" class="form-control form-control-sm pf_pallets_rcv" value="${esc(palletsRcv || "0")}">`;
    tds[8].innerHTML = `<input type="number" min="0" step="1" class="form-control form-control-sm pf_cajas" value="${esc(cajas || "0")}">`;

    // Columna solo informativa, no editable
    tds[9].innerHTML = `<span class="fw-semibold text-primary">${esc(tr.children[9]?.textContent?.trim() || "0")}</span>`;

    tds[10].innerHTML = `<input type="number" min="0" step="1" class="form-control form-control-sm pf_piezas" value="${esc(piezas || "0")}">`;
    tds[11].innerHTML = `<textarea class="form-control form-control-sm pf_observaciones" rows="2">${esc(observaciones)}</textarea>`;

    // Cambiar botón Editar a Cancelar en la celda de acciones (última)
    const btnEditar = tr.querySelector(".pf_btnEditar");
    if (btnEditar) {
      btnEditar.classList.remove("btn-outline-warning");
      btnEditar.classList.add("btn-outline-secondary");
      btnEditar.dataset.mode = "cancel"; // marcador
      btnEditar.title = "Cancelar";
      // Cambiar icono a X
      btnEditar.innerHTML = `<i data-feather="x"></i>`;
    }

    if (window.feather) window.feather.replace();

    const first = tr.querySelector(".pf_descripcion");
    if (first) first.focus();

    // Para que el registrador pueda hacer UPDATE
    tr.dataset.state = "dirty"; // marca la fila como editada
    tr.dataset.idProducto = tr.dataset.id || ""; // mapea data-id -> data-id-producto
    tr.dataset.observaciones = observaciones;
  }

  function cancelarEdicionFila(tr) {
    if (!tr) return;
    if (tr.dataset.editing !== "1") return;

    const snap = tr.dataset.originalHtml || "";
    if (snap) tr.innerHTML = snap;

    delete tr.dataset.editing;
    delete tr.dataset.originalHtml;

    if (window.feather) window.feather.replace();
  }

  // Delegación de evento para el botón editar/cancelar
  pfTbody.addEventListener("click", function (e) {
    const btn = e.target.closest(".pf_btnEditar");
    if (!btn) return;

    const tr = btn.closest("tr");
    if (!tr) return;

    // Si está en modo cancelar o ya está editando, cancela
    if (tr.dataset.editing === "1" || btn.dataset.mode === "cancel") {
      cancelarEdicionFila(tr);
      return;
    }

    // Entra en edición
    entrarEdicionFila(tr);
  });

  // Marcar dirty automáticamente al cambiar cualquier input de la fila
  pfTbody.addEventListener("input", function (ev) {
    const el = ev.target;
    if (!el) return;

    // Solo inputs del módulo
    if (
      !el.classList.contains("pf_descripcion") &&
      !el.classList.contains("pf_item") &&
      !el.classList.contains("pf_upc") &&
      !el.classList.contains("pf_marca") &&
      !el.classList.contains("pf_expiracion") &&
      !el.classList.contains("pf_inner") &&
      !el.classList.contains("pf_case") &&
      !el.classList.contains("pf_pallets_rcv") &&
      !el.classList.contains("pf_cajas") &&
      !el.classList.contains("pf_piezas") &&
      !el.classList.contains("pf_observaciones")
    ) {
      return;
    }

    const tr = el.closest("tr");
    if (!tr) return;

    // No cambies draft, pero sí convierte existentes a dirty
    if (tr.dataset.state !== "draft") tr.dataset.state = "dirty";
  });

  // Opcional: si cierran el modal, cancela cualquier edición activa
  if (modalEl) {
    modalEl.addEventListener("hidden.bs.modal", function () {
      const editando = pfTbody.querySelectorAll('tr[data-editing="1"]');
      editando.forEach((tr) => cancelarEdicionFila(tr));
    });
  }

  // ==========================
  // CARGA PRODUCTOS
  // ==========================
  function cargarProductosFactura(facturaId) {
    facturaIdActual = parseInt(facturaId, 10) || 0;

    if (inputFacturaHidden) inputFacturaHidden.value = String(facturaIdActual);

    if (facturaIdActual <= 0) {
      renderEmpty();
      return;
    }

    try {
      if (xhrActual && xhrActual.readyState !== 4) xhrActual.abort();
    } catch (_) {}

    const term = pfBuscar ? (pfBuscar.value || "").trim() : "";

    const qs = buildQuery({
      factura_id: facturaIdActual,
      term,
      page,
      per_page: perPage,
    });

    const url = base_url + ENDPOINT_LISTAR_PRODUCTOS + "?" + qs;

    setLoading();

    const xhr = new XMLHttpRequest();
    xhrActual = xhr;

    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        let res;
        try {
          res = JSON.parse(xhr.responseText);
        } catch (e) {
          console.error(
            "[OP Partida Productos] JSON inválido:",
            e,
            xhr.responseText,
          );
          renderEmpty();
          return;
        }

        if (!res || res.ok !== true) {
          console.warn("[OP Partida Productos] Respuesta no ok:", res);
          renderEmpty();
          return;
        }

        renderRows(res.data || []);
        renderTotals(res.meta || {}, res.totals || {});
        return;
      }

      console.error(
        "[OP Partida Productos] Error HTTP:",
        xhr.status,
        xhr.responseText,
      );
      renderEmpty();
    };

    xhr.send();
  }

  // Hacer disponible esta función (para registrar, editar, etc.)
  window.opPartidaCargarProductosFactura = function (facturaId) {
    page = 1;
    cargarProductosFactura(facturaId);
  };

  Object.defineProperty(window, "opPartidaEsEnvioSinRevision", {
    get: () => facturaEsEnvioSinRevision,
  });
  // ==========================
  // CLICK EN "VER PRODUCTOS" (VIENE DESDE FACTURAS)
  // ==========================
  function bindClickVerProductos() {
    // Escuchamos en todo el documento para que funcione aunque la tabla se renderice dinámicamente
    document.addEventListener("click", function (e) {
      const btnVer = e.target.closest(".btnVerProductosFactura");
      if (!btnVer) return;

      // Llenar labels del modal
      if (lblId) lblId.textContent = btnVer.getAttribute("data-invoice") || "—";
      if (lblVendor)
        lblVendor.textContent = btnVer.getAttribute("data-vendor") || "—";
      if (lblXdock)
        lblXdock.textContent = btnVer.getAttribute("data-xdock") || "—";
      if (lblRec)
        lblRec.textContent = btnVer.getAttribute("data-recibido") || "—";
      if (lblRev) {
        const revision = btnVer.getAttribute("data-revision");
        pintarBadgeRevision(lblRev, revision);

        // ← NUEVO: guardar si es "Envío sin Revisión" (valor 2)
        const rawRev = String(revision ?? "").trim();
        const numRev = parseInt(rawRev, 10);
        facturaEsEnvioSinRevision = !isNaN(numRev)
          ? numRev === 2
          : rawRev.toLowerCase() === "envio sin revision" ||
            rawRev.toLowerCase() === "envío sin revisión";
      }
      if (lblPal)
        lblPal.textContent = btnVer.getAttribute("data-pallets_inv") || "—";

      // Reset búsqueda y cargar
      page = 1;
      if (pfBuscar) pfBuscar.value = "";

      const facturaId = btnVer.getAttribute("data-invoice") || "0";
      cargarProductosFactura(facturaId);
    });
  }
  function pintarBadgeRevision(el, val) {
    if (!el) return;

    const raw = String(val ?? "")
      .trim()
      .toLowerCase();
    let n = parseInt(raw, 10);

    if (Number.isNaN(n)) {
      if (raw === "factura no revisada") n = 0;
      else if (raw === "factura revisada") n = 1;
      else if (raw === "envío sin revisión" || raw === "envio sin revision")
        n = 2;
      else if (raw === "factura no cuadrada") n = 3;
      else n = -1;
    }

    el.className = "fw-semibold badge p-2 mb-1 mt-1";

    switch (n) {
      case 0:
        el.classList.add("bg-secondary", "text-white");
        el.textContent = "Factura No Revisada";
        break;
      case 1:
        el.classList.add("bg-success", "text-white");
        el.textContent = "Factura Revisada";
        break;
      case 2:
        el.classList.add("bg-warning", "text-dark");
        el.textContent = "Envío sin Revisión";
        break;
      case 3:
        el.classList.add("bg-danger", "text-white");
        el.textContent = "Factura No Cuadrada";
        break;
      default:
        el.classList.add("bg-light", "text-dark", "border");
        el.textContent = "Sin estatus";
        break;
    }
  }
  // ==========================
  // BUSCADOR MODAL + LIMPIEZA
  // ==========================
  function bindModal() {
    if (pfBuscar) {
      pfBuscar.addEventListener("input", function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          page = 1;
          if (facturaIdActual > 0) cargarProductosFactura(facturaIdActual);
        }, 250);
      });
    }

    if (modalEl) {
      modalEl.addEventListener("hidden.bs.modal", function () {
        facturaEsEnvioSinRevision = false;
        if (pfBuscar) pfBuscar.value = "";
        facturaIdActual = 0;
        page = 1;
        renderEmpty();
      });
    }
  }

  // ==========================
  // EVENTO DE RECARGA (PARA TU JS DE REGISTRAR)
  // ==========================
  document.addEventListener("opPartida:productos:refresh", function (ev) {
    const facturaId = ev.detail?.facturaId || 0;
    if (facturaId > 0) {
      page = 1;
      cargarProductosFactura(facturaId);
    }
  });

  // ==========================
  // INIT
  // ==========================
  document.addEventListener("DOMContentLoaded", function () {
    bindClickVerProductos();
    bindModal();
  });

  // ==========================
  // Eliminar fila (renglón agregado desde template)
  // ==========================
  pfTbody.addEventListener("click", function (e) {
    const btn = e.target.closest(".pf_btnEliminarFila");
    if (!btn) return;

    const tr = btn.closest("tr");
    if (!tr) return;

    // Si había una fila "en edición" guardada, limpiamos markers
    if (tr.dataset && tr.dataset.editing === "1") {
      delete tr.dataset.editing;
      delete tr.dataset.originalHtml;
    }

    tr.remove();

    // Si manejas totales en el modal, recalcula aquí (opcional)
    // calcularTotalesEnModal(); // <-- si ya tienes una función así
  });

  // ==========================
  // Auto-cálculo Piezas = CasePack * Cajas
  // Permite override manual si el usuario edita piezas
  // ==========================

  function toNum(v) {
    // Acepta "12", "12.5", " 12 ", etc. Si no es número => 0
    const n = parseFloat(
      String(v ?? "")
        .replace(",", ".")
        .trim(),
    );
    return Number.isFinite(n) ? n : 0;
  }

  function calcularPiezasFila(tr) {
    if (!tr) return;

    const inpCase = tr.querySelector(".pf_case");
    const inpCajas = tr.querySelector(".pf_cajas");
    const inpPzs = tr.querySelector(".pf_piezas"); // AJUSTA si tu clase es distinta

    if (!inpCase || !inpCajas || !inpPzs) return;

    const casePack = toNum(inpCase.value);
    const cajas = toNum(inpCajas.value);

    // Si no hay datos, no fuerces nada
    if (casePack <= 0 || cajas <= 0) return;

    const calculado = Math.round(casePack * cajas); // si necesitas decimales, quita Math.round

    // Si el usuario ya hizo override manual, no pisar
    if (tr.dataset.pzsManual === "1") return;

    inpPzs.value = String(calculado);
    // Guardamos el último valor calculado para comparar
    tr.dataset.pzsCalc = String(calculado);
  }

  function evaluarOverrideManual(tr) {
    if (!tr) return;

    const inpPzs = tr.querySelector(".pf_piezas"); // AJUSTA si tu clase es distinta
    if (!inpPzs) return;

    const val = (inpPzs.value || "").trim();

    // Si el usuario deja vacío, “desbloquea” y vuelve a auto-calcular en el siguiente cambio
    if (val === "") {
      delete tr.dataset.pzsManual;
      return;
    }

    const actual = toNum(val);
    const calc = toNum(tr.dataset.pzsCalc);

    // Si todavía no hay calc guardado, no marques manual
    if (!Number.isFinite(calc) || calc <= 0) return;

    // Si es diferente al calculado, marcamos override manual
    if (actual !== calc) {
      tr.dataset.pzsManual = "1";
    } else {
      // Si coincide, podemos considerarlo “no manual”
      delete tr.dataset.pzsManual;
    }
  }

  // 1) Cuando cambie CasePack o Cajas => auto-calcula piezas (si no está manual)
  pfTbody.addEventListener("input", function (e) {
    const el = e.target;

    if (!el.classList) return;

    if (el.classList.contains("pf_case") || el.classList.contains("pf_cajas")) {
      const tr = el.closest("tr");
      calcularPiezasFila(tr);
    }

    // 2) Si el usuario escribe piezas => detectar override manual
    if (el.classList.contains("pf_piezas")) {
      const tr = el.closest("tr");
      evaluarOverrideManual(tr);
    }
  });

  // 3) Opcional: al salir del input piezas, reafirmar override
  pfTbody.addEventListener(
    "blur",
    function (e) {
      const el = e.target;
      if (el?.classList?.contains("pf_piezas")) {
        evaluarOverrideManual(el.closest("tr"));
      }
    },
    true,
  );

  // ==========================
  // BAJA LOGICA DE PRODUCTO (estatus = 0)
  // Endpoint: Operaciones_por_partida/bajaProducto
  // ==========================
  const ENDPOINT_BAJA_PRODUCTO = "Operaciones_por_partida/bajaProducto";

  function swalConfirm(title, text) {
    if (window.Swal) {
      return Swal.fire({
        icon: "warning",
        title,
        text,
        showCancelButton: true,
        confirmButtonText: "Sí, dar de baja",
        cancelButtonText: "Cancelar",
      }).then((r) => !!r.isConfirmed);
    }
    return Promise.resolve(confirm(title + "\n" + text));
  }

  function swalError(title, text) {
    if (window.Swal) return Swal.fire({ icon: "error", title, text });
    alert(title + "\n" + text);
  }

  function swalSuccess(title, text) {
    if (window.Swal) return Swal.fire({ icon: "success", title, text });
    alert(title + "\n" + text);
  }

  function xhrPostFormData(url, formData) {
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

  // Delegación: botón eliminar existente (viene del backend)
  pfTbody.addEventListener("click", async function (e) {
    const btn = e.target.closest(".pf_btnEliminar");
    if (!btn) return;

    const tr = btn.closest("tr");
    if (!tr) return;

    // id del producto: en tu renderRows lo pones en data-id
    const idProducto =
      parseInt(
        btn.getAttribute("data-id") || tr.getAttribute("data-id") || "0",
        10,
      ) || 0;

    // factura actual (ya la tienes en el estado del catálogo)
    const facturaId = parseInt(String(facturaIdActual || 0), 10) || 0;

    if (idProducto <= 0 || facturaId <= 0) {
      await swalError(
        "Parámetros inválidos",
        "No se pudo identificar el producto o la factura.",
      );
      return;
    }

    const ok = await swalConfirm(
      "Dar de baja producto",
      "El producto dejará de mostrarse en la factura. ¿Deseas continuar?",
    );
    if (!ok) return;

    const url = base_url + ENDPOINT_BAJA_PRODUCTO;

    const fd = new FormData();
    fd.append("id_producto", String(idProducto));
    fd.append("factura_id", String(facturaId));

    const resp = await xhrPostFormData(url, fd);

    if (!resp.okHttp || !resp.res) {
      await swalError(
        "Error",
        "No se pudo procesar la baja (respuesta inválida del servidor).",
      );
      return;
    }

    if (resp.res.ok !== true) {
      await swalError(
        "Error al dar de baja",
        String(resp.res.msg || "No se pudo dar de baja el producto."),
      );
      return;
    }

    // 1) Remover fila del DOM (feedback inmediato)
    tr.remove();

    // 2) Si backend regresó totals, actualízalos (más rápido que relistar)
    if (resp.res.totals) {
      // Mantén el contador en base a meta.total real: lo más seguro es relistar.
      // Pero actualizamos badges de totales aquí mismo:
      if (pfTotalCajas)
        pfTotalCajas.textContent = `Cajas: ${resp.res.totals.total_cajas ?? 0}`;
      if (pfTotalPiezas)
        pfTotalPiezas.textContent = `Piezas: ${resp.res.totals.total_piezas ?? 0}`;
      if (pfTotalPallets)
        pfTotalPallets.textContent = `Pallets RCV: ${resp.res.totals.total_pallets_rcv ?? 0}`;
    }

    // 3) Re-listar para que badgeCount/meta queden perfectos (y filtros/term)
    document.dispatchEvent(
      new CustomEvent("opPartida:productos:refresh", { detail: { facturaId } }),
    );

    // 4) Refrescar facturas para actualizar "productos_count"
    if (window.opPartidaListarFacturas) window.opPartidaListarFacturas();

    await swalSuccess("Listo", "Producto dado de baja correctamente.");
  });
  // ==========================
  // FOTOS DE PRODUCTO
  // ==========================
  const ENDPOINT_SUBIR_FOTO = "Operaciones_por_partida/subirFotoProducto";
  const ENDPOINT_ELIMINAR_FOTO = "Operaciones_por_partida/eliminarFotoProducto";

  const fotoModalEl = document.getElementById("modalFotosProducto");
  const fotoProductoId = document.getElementById("fotoModal_productoId");
  const fotoFacturaId = document.getElementById("fotoModal_facturaId");
  const fotoLblDesc = document.getElementById("fotoModal_lblDescripcion");

  // Instancia Bootstrap del mini-modal
  let fotoModalInst = null;
  if (fotoModalEl) {
    fotoModalInst = new bootstrap.Modal(fotoModalEl);
  }

  // Renderiza los 3 slots con los datos de fotos que vienen del backend
  function renderFotoSlots(fotos) {
    for (let orden = 1; orden <= 3; orden++) {
      const foto = (fotos || []).find((f) => parseInt(f.orden, 10) === orden);
      const preview = document.getElementById("fotoPreview_" + orden);
      const btnEliminar = document.querySelector(
        `.fotoEliminarBtn[data-orden="${orden}"]`,
      );
      const inputFile = document.querySelector(
        `.fotoInput[data-orden="${orden}"]`,
      );

      if (!preview) continue;

      if (foto && foto.ruta_archivo) {
        // Mostrar imagen
        preview.innerHTML = `
        <a href="${base_url}${esc(foto.ruta_archivo)}" target="_blank">
          <img src="${base_url}${esc(foto.ruta_archivo)}"
               alt="Foto ${orden}"
               class="img-fluid rounded"
               style="max-height:130px;object-fit:contain;">
        </a>`;

        // Mostrar botón eliminar con el id_foto
        if (btnEliminar) {
          btnEliminar.dataset.idFoto = foto.id_foto;
          btnEliminar.classList.remove("d-none");
        }
        // Resetear input file por si venía con algo
        if (inputFile) inputFile.value = "";
      } else {
        // Sin foto: ícono placeholder
        preview.innerHTML = `<i data-feather="image" style="width:48px;height:48px;color:#ccc;"></i>`;
        if (btnEliminar) {
          btnEliminar.classList.add("d-none");
          btnEliminar.dataset.idFoto = "";
        }
      }
    }
    if (window.feather) feather.replace();
  }

  // Abre el mini-modal cargando los datos del producto
  function abrirModalFotos(productoId, facturaId, descripcion, fotos) {
    if (!fotoModalInst) return;

    if (fotoProductoId) fotoProductoId.value = String(productoId);
    if (fotoFacturaId) fotoFacturaId.value = String(facturaId);
    if (fotoLblDesc) fotoLblDesc.textContent = descripcion || "";

    renderFotoSlots(fotos);
    fotoModalInst.show();
  }

  // Click en botón "Ver fotos" dentro del tbody
  pfTbody.addEventListener("click", function (e) {
    const btn = e.target.closest(".pf_btnFotos");
    if (!btn) return;

    const tr = btn.closest("tr");
    if (!tr) return;

    const productoId = parseInt(btn.dataset.id || tr.dataset.id || "0", 10);
    const descripcion = btn.dataset.descripcion || "";

    // Las fotos ya vienen en el JSON del listar, guardadas en el tr como JSON
    let fotos = [];
    try {
      fotos = JSON.parse(tr.dataset.fotos || "[]");
    } catch (_) {}

    abrirModalFotos(productoId, facturaIdActual, descripcion, fotos);
  });

  // Subir foto al seleccionar archivo
  if (fotoModalEl) {
    fotoModalEl.addEventListener("change", async function (e) {
      const input = e.target.closest(".fotoInput");
      if (!input || !input.files || !input.files[0]) return;

      const orden = parseInt(input.dataset.orden, 10);
      const productoId = parseInt(fotoProductoId?.value || "0", 10);
      const facturaId = parseInt(fotoFacturaId?.value || "0", 10);

      if (orden < 1 || orden > 3 || productoId <= 0 || facturaId <= 0) {
        await swalError("Error", "Datos de contexto inválidos.");
        return;
      }

      const file = input.files[0];

      // Preview local inmediato
      const reader = new FileReader();
      reader.onload = function (ev) {
        const preview = document.getElementById("fotoPreview_" + orden);
        if (preview) {
          preview.innerHTML = `<img src="${ev.target.result}"
          class="img-fluid rounded" style="max-height:130px;object-fit:contain;">`;
        }
      };
      reader.readAsDataURL(file);

      // Subir al servidor
      const fd = new FormData();
      fd.append("producto_id", String(productoId));
      fd.append("factura_id", String(facturaId));
      fd.append("orden", String(orden));
      fd.append("foto", file);

      const url = base_url + ENDPOINT_SUBIR_FOTO;
      const resp = await xhrPostFormData(url, fd);

      if (!resp.okHttp || !resp.res || resp.res.ok !== true) {
        await swalError(
          "Error al subir",
          resp.res?.msg || "No se pudo subir la foto.",
        );
        // Revertir preview
        const preview = document.getElementById("fotoPreview_" + orden);
        if (preview)
          preview.innerHTML = `<i data-feather="image" style="width:48px;height:48px;color:#ccc;"></i>`;
        if (window.feather) feather.replace();
        return;
      }

      // Actualizar botón eliminar con el nuevo id_foto
      const btnEliminar = document.querySelector(
        `.fotoEliminarBtn[data-orden="${orden}"]`,
      );
      if (btnEliminar) {
        btnEliminar.dataset.idFoto = resp.res.id_foto;
        btnEliminar.classList.remove("d-none");
      }

      // Actualizar el data-fotos del tr correspondiente para que el modal
      // refleje el estado actual si se vuelve a abrir
      const tr = pfTbody.querySelector(`tr[data-id="${productoId}"]`);
      if (tr) {
        let fotos = [];
        try {
          fotos = JSON.parse(tr.dataset.fotos || "[]");
        } catch (_) {}
        // Quitar foto anterior en esa posición si existía
        fotos = fotos.filter((f) => parseInt(f.orden, 10) !== orden);
        fotos.push({
          id_foto: resp.res.id_foto,
          orden: orden,
          ruta_archivo: resp.res.ruta_archivo,
          nombre_archivo: resp.res.nombre_archivo,
        });
        tr.dataset.fotos = JSON.stringify(fotos);

        // Actualizar badge de fotos en la celda
        actualizarBadgeFotos(tr, fotos);
      }

      if (window.feather) feather.replace();
    });
  }

  // Eliminar foto
  if (fotoModalEl) {
    fotoModalEl.addEventListener("click", async function (e) {
      const btn = e.target.closest(".fotoEliminarBtn");
      if (!btn) return;

      const idFoto = parseInt(btn.dataset.idFoto || "0", 10);
      const orden = parseInt(btn.dataset.orden || "0", 10);
      const productoId = parseInt(fotoProductoId?.value || "0", 10);

      if (idFoto <= 0) return;

      const ok = await swalConfirm(
        "Eliminar foto",
        "¿Deseas eliminar esta foto?",
      );
      if (!ok) return;

      const fd = new FormData();
      fd.append("id_foto", String(idFoto));

      const url = base_url + ENDPOINT_ELIMINAR_FOTO;
      const resp = await xhrPostFormData(url, fd);

      if (!resp.okHttp || !resp.res || resp.res.ok !== true) {
        await swalError(
          "Error",
          resp.res?.msg || "No se pudo eliminar la foto.",
        );
        return;
      }

      // Limpiar slot
      const preview = document.getElementById("fotoPreview_" + orden);
      if (preview) {
        preview.innerHTML = `<i data-feather="image" style="width:48px;height:48px;color:#ccc;"></i>`;
      }
      btn.classList.add("d-none");
      btn.dataset.idFoto = "";

      // Actualizar data-fotos del tr
      const tr = pfTbody.querySelector(`tr[data-id="${productoId}"]`);
      if (tr) {
        let fotos = [];
        try {
          fotos = JSON.parse(tr.dataset.fotos || "[]");
        } catch (_) {}
        fotos = fotos.filter((f) => parseInt(f.orden, 10) !== orden);
        tr.dataset.fotos = JSON.stringify(fotos);
        actualizarBadgeFotos(tr, fotos);
      }

      if (window.feather) feather.replace();
    });
  }

  // Actualiza el badge de fotos en la celda del renglón
  function actualizarBadgeFotos(tr, fotos) {
    const celda = tr.querySelector(".pf_celdaFotos");
    if (!celda) return;
    const count = (fotos || []).length;
    celda.innerHTML =
      count > 0
        ? `<span class="badge bg-info text-dark">${count} foto${count > 1 ? "s" : ""}</span>`
        : `<span class="text-muted small">Sin fotos</span>`;
  }
})();
