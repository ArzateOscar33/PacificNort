<div class="container py-4 col-md-12">
  <div class="card shadow-sm">

    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i data-feather="truck" class="me-1"></i> Partidas en Tránsito (Envíos por Factura)
      </h5>

      <button class="btn btn-light btn-sm" id="partidas_transito_btnRefrescar">
        <i data-feather="refresh-cw" class="me-1"></i> Refrescar
      </button>
    </div>

    <div class="card-body">

      <!-- ===================== FILTROS / SELECCIÓN DE FACTURA ===================== -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        <select id="partidas_transito_selectFactura" class="form-control" style="max-width:220px;">
          <option value="">Seleccione factura...</option>
          <!-- DEMO: -->
          <option value="48">Factura 48</option>
          <option value="43">Factura 43</option>
        </select>

        <select id="partidas_transito_selectProveedor" class="form-control" style="max-width:240px;">
          <option value="">Proveedor (Todos)</option>
          <option value="PLATINUM">PLATINUM</option>
        </select>

        <input id="partidas_transito_buscarProducto" class="form-control" style="max-width:320px;"
          placeholder="Buscar producto por descripción / UPC / marca" autocomplete="off">

        <div class="ms-auto d-flex align-items-center gap-2">
          <span class="small text-muted">Resumen:</span>
          <span class="badge bg-secondary text-white" id="partidas_transito_badgeProductos">Productos: 0</span>
          <span class="badge bg-success text-white" id="partidas_transito_badgeCajasTotal">Cajas: 0</span>
          <span class="badge bg-warning text-dark" id="partidas_transito_badgeCajasRestantes">Restantes: 0</span>
        </div>
      </div>

      <!-- ===================== TABLA DE PRODUCTOS DE LA FACTURA ===================== -->
      <div class="table-responsive">
        <table class="table table align-middle" id="partidas_transito_tablaProductos">
          <thead class="table-dark">
            <tr class="text-center">
              <th style="min-width:280px;">Producto</th>
              <th style="width:140px;">UPC</th>
              <th style="width:140px;">Marca</th>

              <th style="width:120px;">Cajas (Total)</th>
              <th style="width:120px;">TJ</th>
              <th style="width:120px;">Lerma</th>
              <th style="width:120px;">GDL</th>
              <th style="width:120px;">San Bartolo</th>

              <th style="width:150px;">Restantes (Bodega)</th>
              <th style="width:130px;">Acción</th>
            </tr>
          </thead>
          <tbody id="partidas_transito_tbodyProductos"></tbody>
        </table>
      </div>

      <div id="partidas_transito_empty" class="alert alert-light border d-none mb-0">
        Selecciona una factura para visualizar sus productos.
      </div>

    </div>
  </div>
</div>

<!-- ===================== MODAL: REGISTRAR ENVÍO POR PRODUCTO ===================== -->
<div class="modal fade" id="modalPartidasTransitoEnvio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i data-feather="send"></i>
          <span>Registrar Envío</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="partidas_transito_formEnvio" autocomplete="off">
          <input type="hidden" id="partidas_transito_idProducto" value="">
          <input type="hidden" id="partidas_transito_factura" value="">

          <div class="alert alert-light border mb-3">
            <div class="small">
              <div><span class="text-muted">Factura:</span> <span class="fw-semibold" id="partidas_transito_lblFactura">—</span></div>
              <div><span class="text-muted">Producto:</span> <span class="fw-semibold" id="partidas_transito_lblProducto">—</span></div>
              <div><span class="text-muted">Cajas restantes:</span> <span class="fw-semibold" id="partidas_transito_lblRestantes">0</span></div>
            </div>
          </div>

          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Destino</label>
              <select id="partidas_transito_destino" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="TJ">Tijuana</option>
                <option value="LERMA">Lerma</option>
                <option value="GDL">Guadalajara</option>
                <option value="SB">San Bartolo</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fecha de envío</label>
              <input type="date" id="partidas_transito_fechaEnvio" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Caja / Ferro</label>
              <input type="text" id="partidas_transito_cajaFerro" class="form-control"
                placeholder="Ej. Caja 102 / FO-22 / Ferro 17" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Cajas enviadas</label>
              <input type="number" min="1" step="1" id="partidas_transito_cajasEnviadas" class="form-control"
                placeholder="0" required>
              <small class="text-muted d-block mt-1">No debe exceder las cajas restantes.</small>
            </div>

            <div class="col-md-12">
              <label class="form-label">Notas</label>
              <textarea id="partidas_transito_notasEnvio" class="form-control" rows="2"
                placeholder="Opcional"></textarea>
            </div>

          </div>
        </form>

        <!-- DEMO preview -->
        <div class="mt-3 d-none" id="partidas_transito_previewWrap">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
              <span class="small"><i data-feather="code" class="me-1"></i> Preview (DEMO)</span>
              <button type="button" class="btn btn-light btn-sm" id="partidas_transito_btnOcultarPreview">Ocultar</button>
            </div>
            <div class="card-body">
              <pre class="mb-0" style="white-space:pre-wrap;" id="partidas_transito_previewJson"></pre>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x-circle" class="me-1"></i> Cancelar
        </button>

        <button type="button" class="btn btn-success" id="partidas_transito_btnGuardarEnvio">
          <i data-feather="save" class="me-1"></i> Guardar (demo)
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ===================== SCRIPT DEMO (SIN BD) ===================== -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  // ====== Refs ======
  const selFactura   = document.getElementById("partidas_transito_selectFactura");
  const inpBuscar    = document.getElementById("partidas_transito_buscarProducto");
  const tbody        = document.getElementById("partidas_transito_tbodyProductos");
  const empty        = document.getElementById("partidas_transito_empty");

  const badgeProd    = document.getElementById("partidas_transito_badgeProductos");
  const badgeCajas   = document.getElementById("partidas_transito_badgeCajasTotal");
  const badgeRest    = document.getElementById("partidas_transito_badgeCajasRestantes");

  // Modal Envío
  const modalEnvioEl = document.getElementById("modalPartidasTransitoEnvio");
  const inpIdProd    = document.getElementById("partidas_transito_idProducto");
  const inpFacturaH  = document.getElementById("partidas_transito_factura");
  const lblFact      = document.getElementById("partidas_transito_lblFactura");
  const lblProd      = document.getElementById("partidas_transito_lblProducto");
  const lblRest      = document.getElementById("partidas_transito_lblRestantes");

  const selDestino   = document.getElementById("partidas_transito_destino");
  const inpFecha     = document.getElementById("partidas_transito_fechaEnvio");
  const inpCajaF     = document.getElementById("partidas_transito_cajaFerro");
  const inpCajasEnv  = document.getElementById("partidas_transito_cajasEnviadas");
  const inpNotas     = document.getElementById("partidas_transito_notasEnvio");

  const btnGuardar   = document.getElementById("partidas_transito_btnGuardarEnvio");

  const previewWrap  = document.getElementById("partidas_transito_previewWrap");
  const preJson      = document.getElementById("partidas_transito_previewJson");
  const btnHidePrev  = document.getElementById("partidas_transito_btnOcultarPreview");

  // ====== DEMO DATA ======
  // Nota: aquí simulamos una factura 48 con un producto y sus envíos parciales
  const DATA = {
    "48": [
      {
        id: "P-4801",
        producto: "LOREAL MATTE SIGNATURE LIQUID EYELINER",
        upc: "",
        marca: "LOREAL",
        cajas_total: 45,
        enviados: { TJ: 20, LERMA: 20, GDL: 3, SB: 2 }
      },
      {
        id: "P-4802",
        producto: "LOREAL PURE SUGAR SCRUB-MINI",
        upc: "",
        marca: "LOREAL",
        cajas_total: 75,
        enviados: { TJ: 0, LERMA: 0, GDL: 0, SB: 0 }
      }
    ],
    "43": [
      { id: "P-4301", producto: "DUNION - SHOES", upc: "", marca: "", cajas_total: 221, enviados: { TJ: 0, LERMA: 0, GDL: 0, SB: 0 } }
    ]
  };

  const sumEnvios = (e) => (e.TJ||0) + (e.LERMA||0) + (e.GDL||0) + (e.SB||0);

  const calcularResumen = (rows) => {
    const totalProd = rows.length;
    const totalCajas = rows.reduce((a,r)=> a + (r.cajas_total||0), 0);
    const totalRest = rows.reduce((a,r)=> a + ((r.cajas_total||0) - sumEnvios(r.enviados||{})), 0);

    badgeProd.textContent = "Productos: " + totalProd;
    badgeCajas.textContent = "Cajas: " + totalCajas;
    badgeRest.textContent = "Restantes: " + totalRest;
  };

  const render = (rows) => {
    tbody.innerHTML = "";

    if (!rows || rows.length === 0) {
      empty.classList.remove("d-none");
      badgeProd.textContent = "Productos: 0";
      badgeCajas.textContent = "Cajas: 0";
      badgeRest.textContent = "Restantes: 0";
      return;
    }

    empty.classList.add("d-none");
    calcularResumen(rows);

    rows.forEach((r) => {
      const enviados = r.enviados || { TJ:0, LERMA:0, GDL:0, SB:0 };
      const restantes = (r.cajas_total || 0) - sumEnvios(enviados);

      const tr = document.createElement("tr");
      tr.className = "text-center";

      tr.innerHTML = `
        <td class="text-start">${r.producto || "—"}</td>
        <td>${r.upc || "—"}</td>
        <td>${r.marca || "—"}</td>

        <td class="fw-semibold">${r.cajas_total ?? 0}</td>
        <td>${enviados.TJ ?? 0}</td>
        <td>${enviados.LERMA ?? 0}</td>
        <td>${enviados.GDL ?? 0}</td>
        <td>${enviados.SB ?? 0}</td>

        <td>
          <span class="badge ${restantes > 0 ? "bg-warning text-dark" : "bg-success"}">
            ${restantes}
          </span>
        </td>

        <td>
          <button type="button"
            class="btn btn-outline-primary btn-sm partidas_transito_btnRegistrarEnvio"
            data-bs-toggle="modal"
            data-bs-target="#modalPartidasTransitoEnvio"
            data-id="${r.id}"
            data-factura="${selFactura.value}"
            data-producto="${(r.producto||"").replace(/"/g, "&quot;")}"
            data-restantes="${restantes}">
            <i data-feather="send"></i>
          </button>
        </td>
      `;

      tbody.appendChild(tr);
    });

    if (window.feather) window.feather.replace();
  };

  const getCurrentRows = () => {
    const fac = selFactura.value;
    return (DATA[fac] || []).slice();
  };

  const applySearch = () => {
    const term = (inpBuscar.value || "").trim().toLowerCase();
    const rows = getCurrentRows();

    if (!term) {
      render(rows);
      return;
    }

    const filtered = rows.filter(r =>
      (r.producto || "").toLowerCase().includes(term) ||
      (r.upc || "").toLowerCase().includes(term) ||
      (r.marca || "").toLowerCase().includes(term)
    );

    render(filtered);
  };

  // ====== Events ======
  selFactura.addEventListener("change", () => {
    inpBuscar.value = "";
    render(getCurrentRows());
  });

  inpBuscar.addEventListener("input", applySearch);

  // Modal: set data on open
  modalEnvioEl.addEventListener("show.bs.modal", (ev) => {
    const btn = ev.relatedTarget;
    const id = btn?.getAttribute("data-id") || "";
    const factura = btn?.getAttribute("data-factura") || "";
    const producto = btn?.getAttribute("data-producto") || "";
    const restantes = btn?.getAttribute("data-restantes") || "0";

    inpIdProd.value = id;
    inpFacturaH.value = factura;

    lblFact.textContent = factura ? ("Factura " + factura) : "—";
    lblProd.textContent = producto || "—";
    lblRest.textContent = restantes;

    // Reset form
    selDestino.value = "";
    inpFecha.value = "";
    inpCajaF.value = "";
    inpCajasEnv.value = "";
    inpNotas.value = "";
    previewWrap.classList.add("d-none");

    if (window.feather) window.feather.replace();
  });

  btnHidePrev.addEventListener("click", () => previewWrap.classList.add("d-none"));

  // Guardar envío (DEMO)
  btnGuardar.addEventListener("click", () => {
    const restantes = parseInt(lblRest.textContent || "0", 10) || 0;
    const cajas = parseInt(inpCajasEnv.value || "0", 10) || 0;

    if (!selDestino.value) return alert("Selecciona destino.");
    if (!inpFecha.value) return alert("Selecciona fecha de envío.");
    if (!inpCajaF.value.trim()) return alert("Captura Caja / Ferro.");
    if (cajas <= 0) return alert("Captura cajas enviadas.");
    if (cajas > restantes) return alert("Las cajas enviadas no pueden exceder las restantes.");

    const payload = {
      factura: inpFacturaH.value,
      producto_id: inpIdProd.value,
      destino: selDestino.value,
      fecha_envio: inpFecha.value,
      caja_ferro: inpCajaF.value.trim(),
      cajas_enviadas: cajas,
      notas: (inpNotas.value || "").trim()
    };

    preJson.textContent = JSON.stringify(payload, null, 2);
    previewWrap.classList.remove("d-none");

    if (window.feather) window.feather.replace();
  });

  // Init
  empty.classList.remove("d-none");
  render([]);
  if (window.feather) window.feather.replace();
});
</script>
