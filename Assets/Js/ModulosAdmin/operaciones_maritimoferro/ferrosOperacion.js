// ===============================
// Ferros en Operación (LISTAR)
// Archivo sugerido: assets/js/modulosAdmin/ferros_en_operacion.js
// ===============================
(function () {
  "use strict";

  // --------- Refs DOM (con sufijo FerroOP) ---------
  const buscarFerroOP = document.getElementById("buscarFerroOP");
  const fechaDesdeFerroOP = document.getElementById("fechaDesdeFerroOP");
  const fechaHastaFerroOP = document.getElementById("fechaHastaFerroOP");
  const perPageSelFerroOP = document.getElementById("perPageFerroOP");
  const form = document.getElementById("formFerroOP");
  const tbodyFerroOP = document.getElementById("tbodyFerroOP");
  const paginacionFerroOP = document.getElementById("paginacionFerroOP");
  const metaResumenFerroOP = document.getElementById("metaResumenFerroOP");

  const btnExcelFerroOP = document.getElementById("btnExcelFerroOP");
  const btnPdfFerroOP = document.getElementById("btnPdfFerroOP");

  // Modal (para futuro guardar/editar; hoy solo listar)
  const formFerroOP = document.getElementById("formFerroOP");
  const rowIdFerroOP = document.getElementById("rowIdFerroOP");
  const operacionIdFerroOP = document.getElementById("operacionIdFerroOP");
  const operacionNombreFerroOP = document.getElementById(
    "operacionNombreFerroOP"
  );
  const clienteIdFerroOP = document.getElementById("clienteIdFerroOP");
  const clienteNombreFerroOP = document.getElementById("clienteNombreFerroOP");
  const contenedorMaritimoIdFerroOP = document.getElementById(
    "contenedorMaritimoIdFerroOP"
  );
  const contenedorMaritimoNombreFerroOP = document.getElementById(
    "contenedorMaritimoNombreFerroOP"
  );
  const bultosMaritimoFerroOP = document.getElementById(
    "bultosMaritimoFerroOP"
  );
  const bultosRestantesFerroOP = document.getElementById(
    "bultosRestantesFerroOP"
  );
  const contenedorFerroIdFerroOP = document.getElementById(
    "contenedorFerroIdFerroOP"
  );
  const contenedorFerroNombreFerroOP = document.getElementById(
    "contenedorFerroNombreFerroOP"
  );
  const bultosAsignadosFerroOP = document.getElementById(
    "bultosAsignadosFerroOP"
  );
  const badgeSaldoFerroOP = document.getElementById("badgeSaldoFerroOP");
  const comentariosFerroOP = document.getElementById("comentariosFerroOP");

  // --------- Estado ---------
  let currentPageFerroOP = 1;
  let currentXHRFerroOP = null;
  let debounceIdFerroOP = null;

  // --------- Utils ---------
  function debounceFerroOP(fn, wait = 350) {
    if (debounceIdFerroOP) clearTimeout(debounceIdFerroOP);
    debounceIdFerroOP = setTimeout(fn, wait);
  }

  function setLoadingFerroOP(isLoading) {
    if (isLoading) {
      tbodyFerroOP.innerHTML = `
        <tr>
          <td colspan="9" class="text-center text-muted py-4">
            Cargando…
          </td>
        </tr>`;
    }
  }

  function safeTextFerroOP(v) {
    return v === null || v === undefined ? "" : String(v);
  }
  function safeIntFerroOP(v) {
    return v === null || v === undefined || v === "" ? "" : Number(v);
  }

  // --------- Render ---------
  function renderRowsFerroOP(rows) {
    if (!rows || rows.length === 0) {
      tbodyFerroOP.innerHTML = `
      <tr>
        <td colspan="9" class="text-center text-muted py-4">
          Sin resultados.
        </td>
      </tr>`;
      feather.replace();
      return;
    }

    const html = rows
      .map((r) => {
        // el modelo ahora devuelve estos nombres
        const idRow = Number(r.id_row ?? r.id ?? 0);
        const numeroOperacion = r.numero_operacion ?? "";
        const contMaritimos =
          r.contenedores_maritimos ?? r.contenedor_maritimo ?? "";
        const bultosMaritimo = r.bultos_maritimo ?? "";
        const cliente = r.cliente ?? "";
        const transportista = r.transportista ?? "";
        const ferro = r.ferro ?? "";
        const divisionBultos = r.division_bultos ?? "";
        const destino = r.destino ?? "";

        return `
      <tr>
  <td>${numeroOperacion}</td>
  <td>${cliente}</td>
  <td>${ferro}</td>
  <td>${contMaritimos}</td>
  <td class="text-end">${bultosMaritimo}</td>
  <td>${transportista}</td>
  <td>${destino}</td>
  <td>${safeTextFerroOP(r.fecha_header || '')}</td>
  <td>${safeTextFerroOP(r.estatus || '')}</td>
        <td>
          <div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-outline-primary" data-id="${idRow}" onclick="editarFerroOP(${idRow})" title="Editar">
              <i data-feather="edit-2"></i>
            </button>
            <button class="btn btn-outline-danger" data-id="${idRow}" onclick="eliminarFerroOP(${idRow})" title="Eliminar">
              <i data-feather="trash-2"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
      })
      .join("");

    tbodyFerroOP.innerHTML = html;
    feather.replace();
  }

  function renderMetaFerroOP(from, to, total) {
    metaResumenFerroOP.textContent = `Mostrando ${from}-${to} de ${total}`;
  }

  function bindPaginationFerroOP(ul, totalPages, currentPage) {
    // El endpoint ya regresa pagination_html. Aquí escuchamos sus clicks.
    ul.addEventListener(
      "click",
      function (e) {
        const a = e.target.closest("a.page-link");
        if (!a) return;
        e.preventDefault();
        const page = parseInt(a.getAttribute("data-page"), 10);
        if (!isNaN(page) && page !== currentPageFerroOP) {
          currentPageFerroOP = page;
          cargarTablaFerroOP();
        }
      },
      { once: true }
    ); // se re-adjunta en cada render
  }

  // --------- Cargar tabla ---------
  function cargarTablaFerroOP() {
    // Aborta solicitud previa si existe
    if (currentXHRFerroOP && currentXHRFerroOP.readyState !== 4) {
      currentXHRFerroOP.abort();
    }

    const params = new URLSearchParams({
      q: (buscarFerroOP.value || "").trim(),
      desde: fechaDesdeFerroOP.value || "",
      hasta: fechaHastaFerroOP.value || "",
      perPage: perPageSelFerroOP.value || "10",
      page: String(currentPageFerroOP),
    });

    currentXHRFerroOP = new XMLHttpRequest();
    currentXHRFerroOP.open(
      "GET",
      BASE_URL +
        "Operaciones_maritimo_ferro_contenedores/listar?" +
        params.toString(),
      true
    );

    setLoadingFerroOP(true);

    currentXHRFerroOP.onload = function () {
      let res = null;
      try {
        res = JSON.parse(currentXHRFerroOP.responseText);
      } catch (_) {
        res = null;
      }
      if (!res || !Array.isArray(res.data)) {
        tbodyFerroOP.innerHTML = `
          <tr><td colspan="9" class="text-center text-danger">Error al cargar datos.</td></tr>`;
        renderMetaFerroOP(0, 0, 0);
        paginacionFerroOP.innerHTML = "";
        return;
      }
      renderRowsFerroOP(res.data);
      renderMetaFerroOP(res.from || 0, res.to || 0, res.total || 0);
      paginacionFerroOP.innerHTML = res.pagination_html || "";
      bindPaginationFerroOP(
        paginacionFerroOP,
        res.total_pages || 1,
        res.page || 1
      );
    };

    currentXHRFerroOP.onerror = function () {
      tbodyFerroOP.innerHTML = `
        <tr><td colspan="9" class="text-center text-danger">No se pudo conectar con el servidor.</td></tr>`;
      renderMetaFerroOP(0, 0, 0);
      paginacionFerroOP.innerHTML = "";
    };

    currentXHRFerroOP.send();
  }

  // --------- Listeners ---------
  buscarFerroOP.addEventListener("input", function () {
    currentPageFerroOP = 1;
    debounceFerroOP(cargarTablaFerroOP, 350);
  });

  fechaDesdeFerroOP.addEventListener("change", function () {
    currentPageFerroOP = 1;
    cargarTablaFerroOP();
  });

  fechaHastaFerroOP.addEventListener("change", function () {
    currentPageFerroOP = 1;
    cargarTablaFerroOP();
  });

  perPageSelFerroOP.addEventListener("change", function () {
    currentPageFerroOP = 1;
    cargarTablaFerroOP();
  });

  // --------- Modal (hooks mínimos por ahora) ---------
  window.editarFerroOP = function (idRow) {
    // TODO: abrir modal en modo editar, pedir detalle por idRow si lo deseas
    console.log("editarFerroOP", idRow);
  };

  window.eliminarFerroOP = function (idRow) {
    // TODO: confirmar y eliminar (endpoint delete)
    console.log("eliminarFerroOP", idRow);
  };

  // Validación rápida de saldo en el modal (cuando lo uses)
  // === LIMITADOR EN TIEMPO REAL ===
  bultosAsignadosFerroOP?.addEventListener("input", function () {
    const rest = Number(bultosRestantesFerroOP?.value || 0);
    let asig = Number(bultosAsignadosFerroOP?.value || 0);

    if (asig < 0 || !Number.isFinite(asig)) asig = 0;

    // Si el usuario intenta rebasar el saldo, recortamos
    if (asig > rest) {
      asig = rest;
      bultosAsignadosFerroOP.value = String(rest);
    }

    const saldo = rest - asig;
    if (badgeSaldoFerroOP) {
      badgeSaldoFerroOP.textContent = `Saldo: ${saldo}`;
      badgeSaldoFerroOP.className =
        "badge " +
        (saldo < 0 ? "bg-danger text-white" : "bg-success text-white");
    }

    // (opcional) deshabilita/enhabilita submit
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = asig <= 0 || saldo < 0;
  });

  function fetchSaldoActual(operacionId, contenedorMaritimoId) {
    return new Promise((resolve, reject) => {
      const x = new XMLHttpRequest();
      const params = new URLSearchParams({
        operacion_id: String(operacionId),
        contenedor_maritimo_id: String(contenedorMaritimoId),
      });
      x.open(
        "GET",
        BASE_URL +
          "Operaciones_maritimo_ferro_contenedores/saldo_mg?" +
          params.toString(),
        true
      );
      x.onload = () => {
        try {
          const res = JSON.parse(x.responseText || "{}");
          const wasMultiple = (carrito.length > 0);
if (res && res.ok === true){
  carrito = [];
  renderCarrito();
  actualizarTotales();
}

if (!wasMultiple) {
  // sólo en 1 línea tiene sentido mantener saldo local
  const rest = Number(bultosRestantesFerroOP.value || 0);
  const asig = Number(bultosAsignadosFerroOP.value || 0);
  const saldo = (res.data && typeof res.data.saldo !== 'undefined')
                  ? Number(res.data.saldo)
                  : (rest - asig);
  bultosRestantesFerroOP.value = String(saldo);
  setBadgeSaldo(saldo);
} else {
  // en múltiple, limpia selector
  bultosAsignadosFerroOP.value = '';
  bultosRestantesFerroOP.value = '';
  setBadgeSaldo(0);
}
        } catch (e) {
          reject(e);
        }
      };
      x.onerror = () => reject(new Error("No se pudo conectar."));
      x.send();
    });
  }

  // --------- Init ---------
  cargarTablaFerroOP();

window.cargarTablaFerroOP = cargarTablaFerroOP;
})();


// === REGISTRAR ASIGNACIÓN MG→FX ===
(function () {
  "use strict";

  const form = document.getElementById("formFerroOP");
  if (!form) return;

  const operacionIdFerroOP = document.getElementById("operacionIdFerroOP");
  const contenedorMaritimoIdFerroOP = document.getElementById(
    "contenedorMaritimoIdFerroOP"
  );
  const bultosMaritimoFerroOP = document.getElementById(
    "bultosMaritimoFerroOP"
  );
  const bultosRestantesFerroOP = document.getElementById(
    "bultosRestantesFerroOP"
  );
  const contenedorFerroIdFerroOP = document.getElementById(
    "contenedorFerroIdFerroOP"
  );
  const bultosAsignadosFerroOP = document.getElementById(
    "bultosAsignadosFerroOP"
  );
  const transportistaIdFerroOP = document.getElementById(
    "transportistaIdFerroOP"
  );
  const destinoIdFerroOP = document.getElementById("destinoIdFerroOP");
  const comentariosFerroOP = document.getElementById("comentariosFerroOP");
  const badgeSaldoFerroOP = document.getElementById("badgeSaldoFerroOP");

  function setBadgeSaldo(val) {
    if (!badgeSaldoFerroOP) return;
    const v = Number(val || 0);
    badgeSaldoFerroOP.textContent = `Saldo: ${v}`;
    badgeSaldoFerroOP.className =
      "badge " + (v < 0 ? "bg-danger text-white" : "bg-success text-white");
  }

  function toast(msg, ok = true) {
    if (window.Swal) {
      Swal.fire({
        icon: ok ? "success" : "error",
        title: ok ? "Listo" : "Aviso",
        text: msg,
        timer: 1800,
        showConfirmButton: false,
      });
    } else {
      alert(msg);
    }
  }
// ===== Carrito de marítimos (para múltiples líneas) =====
const tbodySel = document.getElementById('tbodyMaritimosSeleccionados');
const noMsg    = document.getElementById('noMaritimosMessage');
const asigHidden = document.getElementById('asignacionesHidden');

// Inputs del selector (ya existen en tu otro JS)
const opMarInp     = document.getElementById('operacionMaritimaNombreFerroOP');
const opMarIdHid   = document.getElementById('operacionMaritimaIdFerroOP');      // operacion_id (info)
const cmoIdHid     = document.getElementById('contMaritimoOperacionIdFerroOP');  // cmo.id (clave)
const contIdHid    = document.getElementById('contenedorMaritimoIdFerroOP');
const contNameInp  = document.getElementById('contenedorMaritimoNombreFerroOP');
const bultosTotInp = document.getElementById('bultosMaritimoFerroOP');
const restInp      = document.getElementById('bultosRestantesFerroOP');
const asigInp      = document.getElementById('bultosAsignadosFerroOP');
const comentarioLineaInp = document.getElementById('comentarioLineaFerroOP'); // si no existe, es opcional

let carrito = []; // { cmo_id, bultos_asignados, comentario?, numero_contenedor?, bultos_restantes? }

function renderCarrito(){
  tbodySel.innerHTML = '';
  if (!carrito.length){
    if (noMsg) noMsg.style.display = '';
    return;
  }
  if (noMsg) noMsg.style.display = 'none';

  carrito.forEach((it, idx)=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${it.numero_operacion || ''}</td>
      <td>${it.numero_contenedor || ''}</td>
      <td class="text-end">${it.bultos_asignados}</td>
      <td>${it.comentario || ''}</td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" data-idx="${idx}">
          <i data-feather="trash-2"></i>
        </button>
      </td>
    `;
    tbodySel.appendChild(tr);
  });
  feather.replace();

  tbodySel.querySelectorAll('button[data-idx]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const i = Number(btn.getAttribute('data-idx'));
      if (!isNaN(i)) {
        carrito.splice(i,1);
        renderCarrito();
        actualizarTotales();
      }
    });
  });
}


function actualizarTotales(){
  // si usas los badges de “Total de Bultos / Marítimos”
  const totBultos = carrito.reduce((a,b)=> a + Number(b.bultos_asignados||0), 0);
  const totMar    = carrito.length;
  const badgeTotB = document.getElementById('totalBultosFerroOP');
  const badgeTotM = document.getElementById('totalMaritimosFerroOP');
  if (badgeTotB) {
    badgeTotB.textContent = String(totBultos);
    badgeTotB.style.display = totBultos > 0 ? '' : 'none';
    badgeTotB.className = 'badge ' + (totBultos > 0 ? 'bg-success text-white' : 'bg-secondary text-white');
  }
  if (badgeTotM) {
    badgeTotM.textContent = String(totMar);
    badgeTotM.style.display = totMar > 0 ? '' : 'none';
    badgeTotM.className = 'badge ' + (totMar > 0 ? 'bg-success text-white' : 'bg-secondary text-white');
  }
}
// Botón confirmar del selector (ya existe en tu vista con id "btnConfirmarMaritimoFerroOP")
const btnConfirmar = document.getElementById('btnConfirmarMaritimoFerroOP');
if (btnConfirmar){
  btnConfirmar.addEventListener('click', function(){
   if (opMarInp?.dataset.lastPick !== '1') {
    return toast('Busca y elige una operación de la lista.', false);
  }

  const cmoId  = Number(cmoIdHid?.value || 0);
  const asign  = Number(asigInp?.value || 0);
  const rest   = Number(restInp?.value || 0);

  if (!cmoId || !opId) return toast('Selecciona una operación/CMO válido.', false);
  if (rest <= 0) return toast('Este marítimo no tiene saldo disponible.', false);
  if (asign <= 0) return toast('Bultos a asignar debe ser > 0.', false);
  if (asign > rest) return toast(`No hay saldo suficiente. Disponible: ${rest}.`, false);

    // snapshots del selector actual
    const opNumero = opMarInp?.value || '';                 // p.ej. "LBMF-05"
    const opId     = Number(opMarIdHid?.value || 0);        // operación_id
    const contNm   = contNameInp?.value || '';
    const coment   = (comentarioLineaInp?.value || '').trim();
    const cliente  = (document.getElementById('clienteNombreMaritimoFerroOP')?.value || '');

    // Validaciones
    if (!cmoId || !opId) return toast('Selecciona una operación/CMO válido.', false);
    console.log('DEBUG pick', {
  cmoId: cmoIdHid?.value,
  opId: opMarIdHid?.value
});
    if (asign <= 0) return toast('Bultos a asignar debe ser > 0.', false);
    if (asign > rest) return toast(`No hay saldo suficiente. Disponible: ${rest}.`, false);

    // ¿ya existe el mismo CMO?
    const ix = carrito.findIndex(x => Number(x.cmo_id) === cmoId);
    if (ix >= 0){
      carrito[ix].bultos_asignados = Number(carrito[ix].bultos_asignados) + asign;
    } else {
      carrito.push({
        cmo_id: cmoId,
        bultos_asignados: asign,
        comentario: coment || null,
        // snapshots para que no cambien después:
        numero_operacion: opNumero,
        operacion_id: opId,
        numero_contenedor: contNm,
        cliente: cliente
      });
    }

    renderCarrito();
    actualizarTotales();
    asigInp.value = '';
  });
}
function toggleBtn(){
  const asign = Number(asigInp.value || 0);
  const rest  = Number(restInp.value || 0);
  btnConfirmar.disabled = !(asign > 0 && asign <= rest && opMarInp.dataset.lastPick === '1');
}
['input','change'].forEach(ev => {
  asigInp.addEventListener(ev, toggleBtn);
  restInp.addEventListener(ev, toggleBtn);
  opMarInp.addEventListener(ev, toggleBtn);
});
toggleBtn();

 
form.addEventListener('submit', function (e) {
  e.preventDefault();
  const btn = form.querySelector('button[type="submit"]');

  // Header:
  const fecha = (document.getElementById('fechaFerroOP')?.value || '').trim();
  const fxId  = Number(document.getElementById('contenedorFerroIdFerroOP')?.value || 0);
  const fxNm  = (document.getElementById('contenedorFerroNombreFerroOP')?.value || '').trim();
  const trans = Number(transportistaIdFerroOP.value || 0);
  const dest  = Number(destinoIdFerroOP.value || 0);

  if (!fecha) return toast('La fecha es requerida.', false);
  if (!fxId && !fxNm) return toast('Selecciona la caja/ferro o escribe el número.', false);
  if (!trans) return toast('Selecciona un transportista.', false);
  if (!dest)  return toast('Selecciona un destino.', false);

  // ===== Capturas para manejar ambos modos consistentemente =====
  const wasMultiple = (carrito.length > 0);
  let cmoIdLocal = 0;
  let asigLocal  = 0;
  let restLocal  = 0;

  if (!wasMultiple) {
    // MODO 1 línea
    cmoIdLocal = Number(document.getElementById('contMaritimoOperacionIdFerroOP')?.value || 0);
    asigLocal  = Number(bultosAsignadosFerroOP.value || 0);
    restLocal  = Number(bultosRestantesFerroOP.value || 0);

    if (!cmoIdLocal) return toast('Selecciona una operación/CMO válido.', false);
    
    if (asigLocal <= 0) return toast('Los bultos asignados deben ser > 0.', false);
    if (asigLocal > restLocal) return toast(`No hay saldo suficiente. Disponible: ${restLocal}.`, false);
    // asigHidden NO se setea en modo 1 línea
  } else {
    // MODO múltiple
    if (asigHidden) asigHidden.value = JSON.stringify(carrito);
  }

  // ---- función que realiza el POST de la asignación ----
  function postAsignacion() {
    const fd = new FormData(form);
    btn && (btn.disabled = true);

    const x = new XMLHttpRequest();
    x.open("POST", BASE_URL + "Operaciones_maritimo_ferro_contenedores/guardar_asignacion", true);

    x.onload = function(){
      btn && (btn.disabled = false);

      let res = null;
      try { res = JSON.parse(x.responseText||'{}'); } catch(_){}

      if (!res || res.ok !== true) {
        // Usa las capturas solo si era 1 línea
        if (!wasMultiple) setBadgeSaldo(restLocal);
        const msg = (res && res.msg) ? res.msg : 'No se pudo registrar la asignación.';
        return toast(msg, false);
      }

      // éxito
      if (wasMultiple) {
        // limpiar carrito y totales
        carrito = [];
        renderCarrito();
        actualizarTotales();
        // no tocamos los saldos del selector (ya no aplican)
        bultosAsignadosFerroOP.value = '';
        bultosRestantesFerroOP.value = '';
        setBadgeSaldo(0);
      } else {
        // actualizar saldo visible del MG seleccionado
        const saldo = (res.data && typeof res.data.saldo !== 'undefined')
                      ? Number(res.data.saldo)
                      : (restLocal - asigLocal);
        bultosRestantesFerroOP.value = String(saldo);
        setBadgeSaldo(saldo);
      }

      // Refrescar tabla
      if (typeof window.cargarTablaFerroOP === 'function') window.cargarTablaFerroOP();

      // Cerrar modal
      const modalEl = document.getElementById('modalFerroOP');
      if (modalEl && window.bootstrap?.Modal) {
        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.hide();
      }

      // Limpieza general
      form.reset();

      const folioFx = res.data?.numero_operacion_ferro || '';
      toast(folioFx ? `Asignación registrada (${folioFx}).` : 'Asignación registrada.', true);
      feather.replace();
    };

    x.onerror = function () {
      btn && (btn.disabled = false);
      toast("No se pudo conectar con el servidor.", false);
    };
console.log('DEBUG pick', {
  cmoId: cmoIdHid?.value,
  opId: opMarIdHid?.value
});
    x.send(fd);
  }

  // refs para crear ferro al vuelo
  const fxIdInput = document.getElementById('contenedorFerroIdFerroOP');
  const fxNmInput = document.getElementById('contenedorFerroNombreFerroOP');

  // ---- Crear ferro si solo hay nombre ----
  if (!fxId && fxNm !== "") {
    btn && (btn.disabled = true);

    const fdMk = new FormData();
    fdMk.append("numero_ferro", fxNm);

    const xMk = new XMLHttpRequest();
    xMk.open("POST", BASE_URL + "Operaciones_maritimo_ferro_contenedores/crear_ferro", true);

    xMk.onload = function () {
      let r = null;
      try { r = JSON.parse(xMk.responseText || "{}"); } catch (_) {}

      if (!r || r.ok !== true || !r.id) {
        btn && (btn.disabled = false);
        return toast(r && r.msg ? r.msg : "No se pudo crear la caja/ferro.", false);
      }

      // Setear el hidden con el nuevo ID y continuar con el flujo normal
      if (fxIdInput) fxIdInput.value = String(r.id);
      if (fxNmInput && r.label) fxNmInput.value = r.label;

      postAsignacion();
    };

    xMk.onerror = function () {
      btn && (btn.disabled = false);
      toast("No se pudo conectar para crear la caja/ferro.", false);
    };

    xMk.send(fdMk);
    return; // el resto lo hace postAsignacion()
  }

  // ---- Ya hay fxId, guardar directo ----
  postAsignacion();
});


 
})();
// Excel
document.getElementById("btnExcelFerroOP")?.addEventListener("click", () => {
  ExportarTablas.exportar({
    ref: "tablaFerroOP", // "#tablaEventos" o el elemento también funciona
    formato: "xlsx",
    nombre: "FerrosEnOperacion.xlsx",
    columnasOcultas: [6], // oculta columna ID
    soloVisibles: true,
    sheetName: "Contenedores En Operacion",
  });
});

// PDF
document.getElementById("btnPdfFerroOP")?.addEventListener("click", () => {
  ExportarTablas.exportar({
    ref: "#tablaFerroOP",
    formato: "pdf",
    nombre: "FerrosEnOperacion.pdf",
    titulo: "Ferros En Operacion",
    orientacion: "landscape", // o 'portrait'
    formatoPagina: "letter", // o 'a4'
    columnasOcultas: [6],
    soloVisibles: true,
  });
});
