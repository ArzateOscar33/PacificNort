// Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_ferros_catalogo.js
(function () {
  "use strict";

  // ==========================
  // BASE URL (tu patrón)
  // ==========================
  const base_url =
    (typeof window.base_url !== "undefined" && window.base_url) ||
    (typeof window.BASE_URL !== "undefined" && window.BASE_URL) ||
    (typeof BASE_URL !== "undefined" && BASE_URL) ||
    "";

  // ==========================
  // ENDPOINTS
  // ==========================
  const EP_SUGERIR_FERROS = "Operaciones_por_partida_ferros/sugerirFerros";
  const EP_LISTAR = "Operaciones_por_partida_ferros/listarFerrosEnvios";

  // ==========================
  // DOM REFS
  // ==========================
  const btnRefrescar = document.getElementById("ferros_envios_btnRefrescar");
  const btnFiltrar   = document.getElementById("ferros_envios_btnFiltrar");
  const btnLimpiar   = document.getElementById("ferros_envios_btnLimpiar");

  const hidFerroId   = document.getElementById("ferros_envios_ferroId");
  const inpFerro     = document.getElementById("ferros_envios_buscarFerro");
  const boxSugFerros = document.getElementById("ferros_envios_sugerenciasFerros");

  const inpFi        = document.getElementById("ferros_envios_fi");
  const inpFf        = document.getElementById("ferros_envios_ff");
  const inpProd      = document.getElementById("ferros_envios_buscarProducto");

  const tbody        = document.getElementById("ferros_envios_tbody");
  const emptyBox     = document.getElementById("ferros_envios_empty");

  const badgeTotal   = document.getElementById("ferros_envios_badgeTotalCajas");

  // ==========================
  // STATE
  // ==========================
  let ferroSeleccionado = false; // se activa cuando el hidden tiene id válido

  // ==========================
  // HELPERS
  // ==========================
  function escapeHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function formatFechaEnvio(fecha) {
    const s = String(fecha ?? "").trim();
    if (!s) return "—";
    return s.split(" ")[0] || s;
  }

  function numberMX(n) {
    const x = Number(n);
    if (!isFinite(x)) return "0";
    return x.toLocaleString("es-MX");
  }

  function showEmpty(msg) {
    if (!emptyBox) return;
    emptyBox.textContent = msg || "Sin resultados.";
    emptyBox.classList.remove("d-none");
  }

  function hideEmpty() {
    if (!emptyBox) return;
    emptyBox.classList.add("d-none");
  }

  function clearTable() {
    if (tbody) tbody.innerHTML = "";
  }

  function closeSug() {
    if (!boxSugFerros) return;
    boxSugFerros.innerHTML = "";
    boxSugFerros.style.display = "none";
  }

  function openSug() {
    if (!boxSugFerros) return;
    boxSugFerros.style.display = "block";
  }

  function buildQuery(params) {
    const sp = new URLSearchParams();
    Object.keys(params).forEach((k) => {
      const v = params[k];
      if (v !== null && v !== undefined && String(v).trim() !== "") {
        sp.append(k, v);
      }
    });
    return sp.toString();
  }

  function xhrGet(endpoint, params, cbOk, cbErr) {
    const qs = buildQuery(params || {});
    const url = base_url + endpoint + (qs ? "?" + qs : "");

    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        let json = null;
        try {
          json = JSON.parse(xhr.responseText);
        } catch (e) {
          cbErr && cbErr("Respuesta inválida del servidor.");
          return;
        }
        cbOk && cbOk(json);
      } else {
        cbErr && cbErr("Error HTTP " + xhr.status);
      }
    };

    xhr.send();
  }

  function debounce(fn, wait) {
    let t = null;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function setBadgeTotalCajas(total) {
    if (!badgeTotal) return;

    const t = Number(total) || 0;
    if (t <= 0) {
      badgeTotal.classList.add("d-none");
      badgeTotal.textContent = "0 cajas";
      return;
    }

    badgeTotal.textContent = `${numberMX(t)} cajas`;
    badgeTotal.classList.remove("d-none");
  }

  function resetVistaInicial() {
    ferroSeleccionado = false;
    clearTable();
    setBadgeTotalCajas(0);
    showEmpty("Busca y selecciona un ferro para ver sus envíos.");
  }

  function ensureFerroSeleccionado() {
    const id = (hidFerroId && hidFerroId.value) ? hidFerroId.value.trim() : "";
    ferroSeleccionado = id !== "";
    return ferroSeleccionado;
  }

  // ==========================
  // SUGERENCIAS FERROS (SIN ID)
  // ==========================
  function renderSugFerros(rows) {
    if (!boxSugFerros) return;

    boxSugFerros.innerHTML = "";

    if (!rows || !rows.length) {
      closeSug();
      return;
    }

    rows.forEach((r) => {
      const id = r.id_fisico ?? "";
      const label = r.numero_ferro ?? "";

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.innerHTML = `<span class="fw-semibold">${escapeHtml(label)}</span>`;

      btn.addEventListener("click", function () {
        hidFerroId.value = id;
        inpFerro.value = label;
        closeSug();
        listar(); // SOLO aquí dispara la lista (ferro seleccionado)
      });

      boxSugFerros.appendChild(btn);
    });

    openSug();
  }

  const sugerirFerrosDebounced = debounce(function () {
    const term = (inpFerro.value || "").trim();

    // al escribir, invalida selección previa
    if (hidFerroId.value) {
      hidFerroId.value = "";
      setBadgeTotalCajas(0);
      ferroSeleccionado = false;
      clearTable();
      showEmpty("Selecciona un ferro de las sugerencias para consultar.");
    }

    if (term === "") {
      closeSug();
      resetVistaInicial();
      return;
    }

    xhrGet(
      EP_SUGERIR_FERROS,
      { term: term, limit: 10 },
      function (json) {
        if (json && json.status === "success") {
          renderSugFerros(json.data || []);
        } else {
          closeSug();
        }
      },
      function () {
        closeSug();
      }
    );
  }, 250);

  // ==========================
  // LISTADO (SOLO si hay ferro seleccionado)
  // ==========================
  function getFilters() {
    return {
      ferro_id: (hidFerroId.value || "").trim(),
      fi: (inpFi && inpFi.value) ? inpFi.value : "",
      ff: (inpFf && inpFf.value) ? inpFf.value : "",
      term: (inpProd && inpProd.value) ? inpProd.value.trim() : "",
    };
  }

  function renderTable(rows) {
    clearTable();

    if (!rows || !rows.length) {
      setBadgeTotalCajas(0);
      showEmpty("No hay registros para el ferro y filtros seleccionados.");
      return;
    }

    hideEmpty();

    // total cajas del ferro (sumatoria del listado actual)
    let totalCajas = 0;

    const frag = document.createDocumentFragment();

    rows.forEach((r) => {
      const ferro = r.numero_ferro ?? "—";
      const producto = r.descripcion ?? "—";
      const marca = r.marca ?? "";
      const cajas = Number(r.cajas_enviadas ?? 0) || 0;
      const factura = r.numero_factura ?? "—";
      const fecha = formatFechaEnvio(r.fecha_envio);
      const estatus= r.envio_estatus_txt ?? "—";

      totalCajas += cajas;

      // Tabla más “acomodada”: producto + marca debajo (sin UPC, sin ids)
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="text-start">
          <div class="fw-semibold">${escapeHtml(ferro)}</div>
        </td>

        <td class="text-start">
          <div class="fw-semibold">${escapeHtml(producto)}</div>
          ${marca ? `<div class="small text-muted">${escapeHtml(marca)}</div>` : `<div class="small text-muted">—</div>`}
        </td>

        <td class="text-center">
          <span class="badge bg-dark text-white">${escapeHtml(numberMX(cajas))}</span>
        </td>

        <td class="text-start">
          <span class="fw-semibold">${escapeHtml(factura)}</span>
        </td>


        <td class="text-center">
          <span class="badge text-white bg-${estatus === 'En camino' ? 'warning' : estatus === 'Entregado' ? 'success' : estatus === 'Baja' ? 'secondary' : 'dark'}">${escapeHtml(estatus)}</span>
        </td>

        <td class="text-center">
          ${escapeHtml(fecha)}
        </td>
      `;

      frag.appendChild(tr);
    });

    tbody.appendChild(frag);
    setBadgeTotalCajas(totalCajas);
  }

  function listar() {
    if (!ensureFerroSeleccionado()) {
      // No listamos si no hay ferro seleccionado
      resetVistaInicial();
      return;
    }

    const f = getFilters();

    xhrGet(
      EP_LISTAR,
      f,
      function (json) {
        if (json && json.status === "success") {
          renderTable(json.data || []);
        } else {
          clearTable();
          setBadgeTotalCajas(0);
          showEmpty((json && json.msg) ? json.msg : "No se pudo listar.");
        }
      },
      function () {
        clearTable();
        setBadgeTotalCajas(0);
        showEmpty("No se pudo consultar el servidor.");
      }
    );
  }

  // ==========================
  // EVENTOS UI
  // ==========================
  if (inpFerro) {
    inpFerro.addEventListener("input", sugerirFerrosDebounced);

    inpFerro.addEventListener("focus", function () {
      if (boxSugFerros && boxSugFerros.children.length > 0) openSug();
    });

    inpFerro.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeSug();
    });

    inpFerro.addEventListener("blur", function () {
      setTimeout(closeSug, 200);
    });
  }

  // BOTONES: ahora SOLO funcionan si hay ferro seleccionado
  if (btnRefrescar) {
    btnRefrescar.addEventListener("click", function () {
      if (!ensureFerroSeleccionado()) {
        resetVistaInicial();
        return;
      }
      listar();
    });
  }

  if (btnFiltrar) {
    btnFiltrar.addEventListener("click", function () {
      if (!ensureFerroSeleccionado()) {
        resetVistaInicial();
        return;
      }
      listar();
    });
  }

  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", function () {
      if (hidFerroId) hidFerroId.value = "";
      if (inpFerro) inpFerro.value = "";
      if (inpFi) inpFi.value = "";
      if (inpFf) inpFf.value = "";
      if (inpProd) inpProd.value = "";
      closeSug();
      resetVistaInicial();
    });
  }

  // Enter en producto => filtra, pero SOLO si hay ferro seleccionado
  if (inpProd) {
    inpProd.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        if (!ensureFerroSeleccionado()) {
          resetVistaInicial();
          return;
        }
        listar();
      }
    });
  }

  // Fechas: ya NO disparan lista automáticamente (para evitar listar sin ferro)
  // Si quieres que sí liste al cambiar fecha, descomenta:
  // if (inpFi) inpFi.addEventListener("change", () => ensureFerroSeleccionado() && listar());
  // if (inpFf) inpFf.addEventListener("change", () => ensureFerroSeleccionado() && listar());

  // Click fuera cierra sugerencias
  document.addEventListener("click", function (e) {
    const isInside =
      e.target === inpFerro ||
      (boxSugFerros && boxSugFerros.contains(e.target));
    if (!isInside) closeSug();
  });

  // ==========================
  // INIT (NO LISTA)
  // ==========================
  resetVistaInicial();
})();
