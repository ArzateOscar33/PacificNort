// == Operaciones Marítimo-Ferroviarias (MF) ==
// Encapsulamos para evitar colisiones con el módulo marítimo
(function () {
  "use strict";

  // ===== Refs de la tabla / filtros =====
  const tablaBody = document.getElementById("maritimo_ferro_tablaBody");
  const inputBuscar = document.getElementById("maritimo_ferro_buscarOperacion");
  const selectSubtipo = document.getElementById("maritimo_ferro_filtroSubtipo");
  const selectPerPage = document.getElementById("maritimo_ferro_perPage");
  const ulPaginacion = document.getElementById("maritimo_ferro_paginacion");
  const metaResumen = document.getElementById("maritimo_ferro_metaResumen");
  const inpFechaIni = document.getElementById("maritimo_ferro_fechaInicio");
  const inpFechaFin = document.getElementById("maritimo_ferro_fechaFin");

  const inputOrdenEta = document.getElementById("maritimo_ferro_ordenEta");
  const txtOrdenEta = document.getElementById("txtOrdenEta");
  const opcionesOrdenEta = document.querySelectorAll(".opcionOrdenEta");
  // ===== NUEVOS FILTROS =====
  const selectEstatus = document.getElementById("maritimo_ferro_filtroEstatus");

  const selectMedida = document.getElementById(
    "maritimo_ferro_filtroMedidaContenedor",
  );

  const selBroker = document.getElementById("brokerId_mf");
  const selTransportista = document.getElementById("transportistaId_mf");
  const checksEstatus = document.querySelectorAll(".chkFiltroEstatus");
  const txtFiltroEstatus = document.getElementById("txtFiltroEstatus");
  const btnLimpiarFiltroEstatus = document.getElementById(
    "btnLimpiarFiltroEstatus",
  );

  const checksTransportista = document.querySelectorAll(
    ".chkFiltroTransportista",
  );
  const txtFiltroTransportista = document.getElementById(
    "txtFiltroTransportista",
  );
  const btnLimpiarFiltroTransportista = document.getElementById(
    "btnLimpiarFiltroTransportista",
  );

  const checksCliente = document.querySelectorAll(".chkFiltroCliente");
  const txtFiltroCliente = document.getElementById("txtFiltroCliente");
  const btnLimpiarFiltroCliente = document.getElementById(
    "btnLimpiarFiltroCliente",
  );

  const checksBroker = document.querySelectorAll(".chkFiltroBroker");
  const txtFiltroBroker = document.getElementById("txtFiltroBroker");
  const btnLimpiarFiltroBroker = document.getElementById(
    "btnLimpiarFiltroBroker",
  );
  let currentPage = 1;
  let perPage = (selectPerPage?.value || "10").toString(); // "10" | "25" | ... | "todos"
  let currentListXHR = null;
  let debounceId = null;
  // ===== Edición rápida tipo Excel =====
  let celdaEditandoMF = null;
  let inlineEditandoMF = false;

  // ===== Refs del modal =====
  const modalEl = document.getElementById("modalMaritimoFerro");
  const tituloModal = document.getElementById("tituloModalOperacion_mf");
  const formOp = document.getElementById("formOperacionMaritimoFerro");
  const btnNuevaOp = document.getElementById(
    "maritimo_ferro_btnNuevaOperacion",
  );
  const pesoInputActual = modalEl.querySelector("#pesoOperacion_mf");
  const btnGuardarOp = document.getElementById("btnGuardarOperacion_mf");

  const inpIdOperacion = document.getElementById("id_operacion_mf");
  const selSubtipo = document.getElementById("subtipoOperacion_mf");
  const inpNumeroOp = document.getElementById("numeroOperacion_mf");
  const selEstatus = document.getElementById("estatusId_mf");
  const inpETD = document.getElementById("etd_mf");
  const inpETA = document.getElementById("eta_mf");
  const inpUbicacionActual = document.getElementById("ubicacionActual_mf");
  const inpBL = document.getElementById("numeroBL_mf");

  const selPuerto = document.getElementById("puertoArribo_mf"); // disabled/readonly
  const selNaviera = document.getElementById("navieraId_mf");
  const selForwarder = document.getElementById("forwarderId_mf");
  const selShipper = document.getElementById("shipperId_mf");

  const inpClienteNom = document.getElementById("clienteNombre_mf");
  const hidCliente = document.getElementById("clienteId_mf");
  const boxSugCliente = document.getElementById("sugerenciasCliente_mf");

  const txtNotas = document.getElementById("notas_mf");
  const chkISF = document.getElementById("chkIsf"); // checkbox
  const inpCitaPuerto = document.getElementById("cita_puerto"); // input date
  const txtMercancia = document.getElementById("descripcion_mercancia_mf");

  // Repeater (contenedores marítimos)
  const repeater = document.getElementById("contenedoresRepeater_mf");
  const tplContenedor = document.getElementById("contenedorTemplate_mf");

  // Bootstrap modal
  let modalInstance = null;
  if (modalEl && window.bootstrap) {
    modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  // ===== Helpers UI/DOM =====
  const safe = (v) => (v === undefined || v === null ? "" : v);
  const opcional = (v) => {
    const s = String(v ?? "").trim();

    if (
      s === "" ||
      s === "0" ||
      s === "#0" ||
      s.toLowerCase() === "null" ||
      s.toLowerCase() === "undefined"
    ) {
      return "-";
    }

    return s;
  };
  function escapeHtmlMF(v) {
    return String(v ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function attrMF(v) {
    return escapeHtmlMF(v);
  }

  function textoCeldaMF(v) {
    const s = String(v ?? "").trim();
    return s === "" ? "-" : escapeHtmlMF(s);
  }
  const show = (el) => el?.classList.remove("d-none");
  const hide = (el) => el?.classList.add("d-none");
  const enable = (el) => el?.removeAttribute("disabled");
  const disable = (el) => el?.setAttribute("disabled", "disabled");
  const clearSelect = (sel) => {
    if (sel) sel.value = "";
  };

  function setSelectValue(sel, val) {
    if (!sel) return;

    const s = String(val ?? "").trim();

    if (s === "" || s === "0" || s === "#0") {
      sel.value = "";
      return;
    }

    const has = Array.from(sel.options).some((o) => String(o.value) === s);
    if (has) {
      sel.value = s;
      return;
    }

    const opt = document.createElement("option");
    opt.value = s;
    opt.textContent = `#${s}`;
    sel.appendChild(opt);
    sel.value = s;
  }

  function renderCargando() {
    if (!tablaBody) return;
    tablaBody.innerHTML = `
      <tr>
        <td colspan="25" class="text-center text-muted py-4">Cargando resultados...</td>
      </tr>`;
  }

  function mfColorValido(hex) {
    return /^#([0-9A-F]{3}){1,2}$/i.test(String(hex || "").trim());
  }

  function mfTextoContraste(hex) {
    hex = String(hex || "")
      .replace("#", "")
      .trim();

    if (hex.length === 3) {
      hex = hex
        .split("")
        .map((c) => c + c)
        .join("");
    }

    if (hex.length !== 6) {
      return "#000000";
    }

    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    // Fórmula simple de luminancia
    const luminancia = 0.299 * r + 0.587 * g + 0.114 * b;

    return luminancia > 160 ? "#000000" : "#ffffff";
  }

  function mfEstiloRenglonPorEstatus(colorHex) {
    const color = mfColorValido(colorHex) ? colorHex : "#ffffff";
    const texto = mfTextoContraste(color);

    return `class="row-estatus-color" style="--estatus-bg:${color}; --estatus-text:${texto};"`;
  }

  function renderAsignacionesCols(item) {
    const ferrosStr = (item.ferros_cajas || "").trim();
    const destinosStr = (item.destinos_ferros_cajas || "").trim();
    const fechasStr = (item.fechas_salida_ferros_cajas || "").trim();
    const transportistasFerrosCajasStr = (
      item.transportistas_ferros_cajas || ""
    ).trim();

    // ✅ NUEVO
    const ubicacionesStr = (item.ubicaciones_ferros_cajas || "").trim();

    if (!ferrosStr)
      return {
        ferros: "-",
        destinos: "-",
        fechas: "-",
        ubicaciones: "-",
        transportistas: "-",
      };

    const ferros = ferrosStr
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean);
    const destinos = destinosStr
      ? destinosStr.split(",").map((s) => s.trim())
      : [];
    const fechas = fechasStr ? fechasStr.split(",").map((s) => s.trim()) : [];

    // ✅ NUEVO
    const ubicaciones = ubicacionesStr
      ? ubicacionesStr.split(",").map((s) => s.trim())
      : [];

    const opId = item.id_operacion || "";

    const mkBadge = (txt, i) => `
    <span
      class="badge badge-asignacion bg-primary text-white w-100 text-start text-truncate mt-1"
      data-op="${safe(opId)}"
      data-asig="${i}"
      title="${safe(txt)}"
    >${safe(txt)}</span>
  `;

    const mkStack = (arr) => `
    <div class="d-flex flex-column align-items-stretch">
      ${arr.join("")}
    </div>
  `;

    return {
      ferros: mkStack(ferros.map((v, i) => mkBadge(v || "—", i))),
      destinos: mkStack(ferros.map((_, i) => mkBadge(destinos[i] || "—", i))),
      fechas: mkStack(ferros.map((_, i) => mkBadge(fechas[i] || "—", i))),

      // ✅ NUEVO
      ubicaciones: mkStack(
        ferros.map((_, i) => mkBadge(ubicaciones[i] || "Sin Ubicación", i)),
      ),
      transportistas_ferros_cajas: mkStack(
        ferros.map((_, i) =>
          mkBadge(transportistasFerrosCajasStr.split(",")[i] || "—", i),
        ),
      ),
    };
  }

  function renderTabla(rows) {
    if (!tablaBody) return;
    tablaBody.innerHTML = "";

    if (!Array.isArray(rows) || rows.length === 0) {
      tablaBody.innerHTML =
        "<tr><td colspan='24' class='text-center'>No se encontraron resultados</td></tr>";
      return;
    }

    rows.forEach((item) => {
      const tr = document.createElement("tr");
      const asig = renderAsignacionesCols(item);

      const colorEstatus = mfColorValido(item.estatus_color)
        ? item.estatus_color
        : "#ffffff";

      const textoEstatus = mfTextoContraste(colorEstatus);

      tr.classList.add("text-center", "row-estatus-color");
      tr.style.setProperty("--estatus-bg", colorEstatus);
      tr.style.setProperty("--estatus-text", textoEstatus);

      tr.innerHTML = `
      <td class="sticky-col sticky-col-1 text-center">${safe(item.numero_operacion)}</td>
      <td class="sticky-col sticky-col-2 text-center">${safe(item.contenedores)}</td>
     <!-- <td>${safe(item.subtipo || item.subtipo_operacion)}</td> -->
      <td>${safe(item.etd)}</td>
      <td>${safe(item.eta)}</td>

      <td>${safe(item.peso_total)} Kg</td>
      <td>${safe(item.bultos_total)}</td>
      <td>${safe(item.tipo_contenedor)}</td>
      <td class="mf-cell-editable"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="descripcion_mercancia"
          data-tipo="text"
          data-value="${attrMF(item.mercancia || "")}"
          title="Doble clic para editar mercancía">
        ${textoCeldaMF(item.mercancia)}
      </td>

      <td class="mf-cell-editable"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="transportista_id"
          data-tipo="select-transportista"
          data-value="${attrMF(item.transportista_id || "")}"
          title="Doble clic para cambiar transportista">
        ${textoCeldaMF(opcional(item.transportista))}
      </td>

      <td>${opcional(item.brokers)}</td>
      <td>${safe(item.numero_bl)}</td>
      <td>${safe(item.puerto_arribo)}</td>
      <td>${safe(item.cliente)}</td> 

      <td class="mf-cell-editable"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="estatus_id"
          data-tipo="select-estatus"
          data-value="${attrMF(item.estatus_id || "")}"
          title="Doble clic para cambiar estatus">
        ${textoCeldaMF(item.estatus)}
      </td>

      <td class="mf-cell-editable"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="isf"
          data-tipo="bool"
          data-value="${Number(item.isf) === 1 ? "1" : "0"}"
          title="Doble clic para alternar ISF">
        ${Number(item.isf) === 1 ? '<span class="badge bg-success text-white">Si</span>' : '<span class="badge bg-secondary text-white">No</span>'}
      </td>

      <td class="mf-cell-editable"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="cita_puerto"
          data-tipo="date"
          data-value="${attrMF(item.cita_puerto || "")}"
          title="Doble clic para editar cita en puerto">
        ${textoCeldaMF(item.cita_puerto)}
      </td>

      <td class="mf-cell-editable col-ellipsis"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="ubicacion_actual"
          data-tipo="text"
          data-value="${attrMF(item.ubicacion_actual || "")}"
          title="${attrMF(item.ubicacion_actual || "Doble clic para editar ubicación actual")}">
        ${textoCeldaMF(item.ubicacion_actual)}
      </td>

      <td class="mf-cell-editable col-wrap"
          data-id="${attrMF(item.id_operacion)}"
          data-campo="notas"
          data-tipo="textarea"
          data-value="${attrMF(item.observaciones || "")}"
          title="${attrMF(item.observaciones || "Doble clic para editar observaciones")}">
        ${textoCeldaMF(item.observaciones)}
      </td>

      <td>${asig.ferros}</td>
      <td>${asig.destinos}</td>
      <td>${asig.fechas}</td>
      <td>${asig.ubicaciones}</td>
      <td>${asig.transportistas_ferros_cajas ? asig.transportistas_ferros_cajas : "-"}</td>
      <td>
        <div class="d-flex justify-content-center">
          <button class="btn btn-sm btn-primary text-white me-1 btn-edit-mf" data-id="${safe(item.id_operacion)}" title="Editar">
            <i data-feather="edit"></i>Editar Operacion
          </button>

          <button class="btn btn-sm btn-success"
            data-bs-toggle="modal"
            data-bs-target="#modalAsignarFerroCaja"
            data-mf-action="ferro"
            data-id="${safe(item.id_operacion)}"
            data-codigo="${safe(item.numero_operacion)}">
            <i data-feather="truck" class="me-1"></i> Caja/Ferro
          </button>
        </div>
      </td>
    `;

      tablaBody.appendChild(tr);
    });

    if (window.feather) feather.replace();
  }
  function obtenerCatalogoEditableMF(tipo) {
    const cats = window.MF_CATALOGOS || {};

    if (tipo === "select-estatus") {
      return (cats.estatus || []).map((x) => ({
        id: x.id_estatus,
        nombre: x.nombre,
      }));
    }

    if (tipo === "select-transportista") {
      return (cats.transportistas || []).map((x) => ({
        id: x.id_transportista,
        nombre: x.nombre,
      }));
    }

    return [];
  }

  function renderValorEditableMF(campo, valor, texto) {
    if (campo === "isf") {
      return String(valor) === "1"
        ? '<span class="badge bg-success text-white">Si</span>'
        : '<span class="badge bg-secondary text-white">No</span>';
    }

    const txt = String(texto ?? valor ?? "").trim();
    return txt === "" ? "-" : escapeHtmlMF(txt);
  }

  function guardarCeldaEditableMF(td, nuevoValor, textoMostrar = null) {
    const idOperacion = String(td.dataset.id || "").trim();
    const campo = String(td.dataset.campo || "").trim();
    const valorAnterior = td.dataset.value ?? "";
    const htmlAnterior = td.innerHTML;

    if (!idOperacion || !campo) return;

    const fd = new FormData();
    fd.append("id_operacion", idOperacion);
    fd.append("campo", campo);
    fd.append("valor", nuevoValor ?? "");

    td.classList.add("mf-saving");

    const x = new XMLHttpRequest();
    x.open(
      "POST",
      base_url + "Operaciones_maritimo_ferro/actualizar_celda",
      true,
    );

    x.onerror =
      x.onabort =
      x.ontimeout =
        function () {
          td.classList.remove("mf-saving");
          td.dataset.value = valorAnterior;
          td.innerHTML = htmlAnterior;

          Swal?.fire(
            "Error de red",
            "No se pudo actualizar la celda.",
            "error",
          );
        };

    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;

      td.classList.remove("mf-saving");

      let res = {};
      try {
        res = JSON.parse(x.responseText || "{}");
      } catch (e) {
        res = {};
      }

      if (x.status !== 200 || res.status !== "success") {
        td.dataset.value = valorAnterior;
        td.innerHTML = htmlAnterior;

        Swal?.fire(
          "Error",
          res.msg || "No se pudo actualizar la celda.",
          "error",
        );
        return;
      }

      /*
       * Según tu jsonOk(), la respuesta puede venir anidada:
       * res.data.data.valor / res.data.data.texto
       */
      const payload = res.data?.data || res.data || {};
      const valorFinal = payload.valor ?? nuevoValor ?? "";
      const textoFinal = payload.texto ?? textoMostrar ?? valorFinal ?? "";

      td.dataset.value = String(valorFinal ?? "");
      td.innerHTML = renderValorEditableMF(campo, valorFinal, textoFinal);

      if (
        campo === "ubicacion_actual" ||
        campo === "notas" ||
        campo === "descripcion_mercancia"
      ) {
        td.setAttribute("title", String(textoFinal || ""));
      }

      /*
       * Si cambia estatus, refrescamos porque también cambia el color del renglón.
       */
      if (campo === "estatus_id") {
        listar(true);
      }
    };

    x.send(fd);
  }

  function cerrarEdicionMF(td, htmlOriginal) {
    td.classList.remove("mf-editing");
    td.innerHTML = htmlOriginal;
    celdaEditandoMF = null;
    inlineEditandoMF = false;
  }

  function activarEdicionCeldaMF(td) {
    if (!td || td.classList.contains("mf-editing")) return;

    if (celdaEditandoMF && celdaEditandoMF !== td) {
      return;
    }

    celdaEditandoMF = td;
    inlineEditandoMF = true;

    td.classList.add("mf-editing");

    const tipo = td.dataset.tipo || "text";
    const campo = td.dataset.campo || "";
    const valorActual = td.dataset.value ?? "";
    const htmlOriginal = td.innerHTML;

    let finalizado = false;

    function cancelar() {
      if (finalizado) return;
      finalizado = true;
      cerrarEdicionMF(td, htmlOriginal);
    }

    function terminar(nuevoValor, textoMostrar = null) {
      if (finalizado) return;
      finalizado = true;

      td.classList.remove("mf-editing");
      celdaEditandoMF = null;
      inlineEditandoMF = false;

      const valAnt = String(valorActual ?? "");
      const valNvo = String(nuevoValor ?? "");

      if (valAnt === valNvo) {
        td.innerHTML = htmlOriginal;
        return;
      }

      td.innerHTML = renderValorEditableMF(campo, nuevoValor, textoMostrar);
      guardarCeldaEditableMF(td, nuevoValor, textoMostrar);
    }

    if (tipo === "bool") {
      const nuevo = String(valorActual) === "1" ? "0" : "1";

      td.classList.remove("mf-editing");
      celdaEditandoMF = null;
      inlineEditandoMF = false;

      td.innerHTML = renderValorEditableMF(
        campo,
        nuevo,
        nuevo === "1" ? "SI" : "NO",
      );
      guardarCeldaEditableMF(td, nuevo, nuevo === "1" ? "SI" : "NO");
      return;
    }

    if (tipo === "select-estatus" || tipo === "select-transportista") {
      const select = document.createElement("select");
      select.className = "form-control form-control-sm";

      if (tipo === "select-transportista") {
        const optVacio = document.createElement("option");
        optVacio.value = "";
        optVacio.textContent = "SIN TRANSPORTISTA";
        select.appendChild(optVacio);
      }

      obtenerCatalogoEditableMF(tipo).forEach((row) => {
        const opt = document.createElement("option");
        opt.value = String(row.id ?? "");
        opt.textContent = String(row.nombre ?? "");
        select.appendChild(opt);
      });

      select.value = String(valorActual || "");

      td.innerHTML = "";
      td.appendChild(select);
      select.focus();

      select.addEventListener("change", () => {
        const selected = select.options[select.selectedIndex];
        terminar(select.value, selected ? selected.textContent : "-");
      });

      select.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          e.preventDefault();
          cancelar();
        }

        if (e.key === "Enter") {
          e.preventDefault();
          const selected = select.options[select.selectedIndex];
          terminar(select.value, selected ? selected.textContent : "-");
        }
      });

      select.addEventListener("blur", () => {
        setTimeout(() => {
          if (!finalizado) cancelar();
        }, 120);
      });

      return;
    }

    if (tipo === "date") {
      const input = document.createElement("input");
      input.type = "date";
      input.className = "form-control form-control-sm";
      input.value = String(valorActual || "").slice(0, 10);

      td.innerHTML = "";
      td.appendChild(input);
      input.focus();

      input.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          e.preventDefault();
          cancelar();
        }

        if (e.key === "Enter") {
          e.preventDefault();
          terminar(input.value, input.value || "-");
        }
      });

      input.addEventListener("blur", () => {
        terminar(input.value, input.value || "-");
      });

      return;
    }

    const input = document.createElement(
      tipo === "textarea" ? "textarea" : "input",
    );
    input.className = "form-control form-control-sm input-uppercase";

    if (tipo === "textarea") {
      input.rows = 2;
    } else {
      input.type = "text";
    }

    input.value = String(valorActual || "");

    td.innerHTML = "";
    td.appendChild(input);
    input.focus();
    input.select?.();

    input.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        e.preventDefault();
        cancelar();
      }

      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        const val = String(input.value || "")
          .trim()
          .toUpperCase();
        terminar(val, val || "-");
      }
    });

    input.addEventListener("blur", () => {
      const val = String(input.value || "")
        .trim()
        .toUpperCase();
      terminar(val, val || "-");
    });
  }

  tablaBody?.addEventListener("dblclick", (e) => {
    const td = e.target.closest(".mf-cell-editable");
    if (!td) return;

    activarEdicionCeldaMF(td);
  });
  (function bindAsignacionesHover() {
    let lastKey = null;

    document.addEventListener("mouseover", (e) => {
      const badge = e.target.closest(".badge-asignacion");
      if (!badge) return;

      const op = badge.getAttribute("data-op");
      const i = badge.getAttribute("data-asig");
      const key = `${op}|${i}`;
      if (key === lastKey) return;
      lastKey = key;

      document
        .querySelectorAll(
          `.badge-asignacion[data-op="${CSS.escape(op)}"][data-asig="${CSS.escape(i)}"]`,
        )
        .forEach((el) => el.classList.add("is-linked"));
    });

    document.addEventListener("mouseout", (e) => {
      const badge = e.target.closest(".badge-asignacion");
      if (!badge) return;

      const op = badge.getAttribute("data-op");
      const i = badge.getAttribute("data-asig");

      document
        .querySelectorAll(
          `.badge-asignacion[data-op="${CSS.escape(op)}"][data-asig="${CSS.escape(i)}"]`,
        )
        .forEach((el) => el.classList.remove("is-linked"));

      lastKey = null;
    });
  })();

  function renderResumen(meta) {
    if (!metaResumen) return;
    const { total = 0, page = 1, per_page = perPage, total_pages = 1 } = meta;

    if (total === 0) {
      metaResumen.textContent = "Mostrando 0-0 de 0";
      return;
    }

    const isAll = String(per_page).toLowerCase() === "todos";
    if (isAll) {
      metaResumen.textContent = `Mostrando 1-${total} de ${total} | pág 1 de 1`;
      return;
    }

    const pp = Number(per_page || 10);
    const start = (page - 1) * pp + 1;
    const end = Math.min(total, page * pp);
    metaResumen.textContent = `Mostrando ${start}-${end} de ${total} | pág ${page} de ${total_pages}`;
  }

  function renderPaginacion(meta) {
    if (!ulPaginacion) return;

    // Normaliza
    const pageRaw = meta?.page ?? 1;
    const totalRaw = meta?.total_pages ?? 1;
    const perRaw = meta?.per_page ?? perPage;

    const isAll = String(perRaw).toLowerCase() === "todos";

    // Si es "todos", forzamos UI simple (sin paginación)
    if (isAll) {
      ulPaginacion.innerHTML = `
      <li class="page-item active">
        <a class="page-link" href="#" onclick="return false;">1</a>
      </li>
    `;
      return;
    }

    // Ya es paginado: asegurar números válidos
    const total_pages = Math.max(1, parseInt(totalRaw, 10) || 1);
    const page = Math.min(total_pages, Math.max(1, parseInt(pageRaw, 10) || 1));

    // Si solo hay una página, puedes mostrar "1" o dejar vacío
    if (total_pages <= 1) {
      ulPaginacion.innerHTML = `
      <li class="page-item active">
        <a class="page-link" href="#" onclick="return false;">1</a>
      </li>
    `;
      return;
    }

    ulPaginacion.innerHTML = "";

    // Prev
    const liPrev = document.createElement("li");
    liPrev.className = "page-item" + (page <= 1 ? " disabled" : "");
    liPrev.innerHTML = `<a class="page-link" href="#" aria-label="Anterior">&laquo;</a>`;
    liPrev.onclick = (e) => {
      e.preventDefault();
      if (page > 1) {
        currentPage = page - 1;
        listar();
      }
    };
    ulPaginacion.appendChild(liPrev);

    // Ventana de páginas
    const windowSize = 5;
    let start = Math.max(1, page - Math.floor(windowSize / 2));
    let end = Math.min(total_pages, start + windowSize - 1);
    if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

    for (let p = start; p <= end; p++) {
      const li = document.createElement("li");
      li.className = "page-item" + (p === page ? " active" : "");
      li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
      li.onclick = (e) => {
        e.preventDefault();
        if (p !== page) {
          currentPage = p;
          listar();
        }
      };
      ulPaginacion.appendChild(li);
    }

    // Next
    const liNext = document.createElement("li");
    liNext.className = "page-item" + (page >= total_pages ? " disabled" : "");
    liNext.innerHTML = `<a class="page-link" href="#" aria-label="Siguiente">&raquo;</a>`;
    liNext.onclick = (e) => {
      e.preventDefault();
      if (page < total_pages) {
        currentPage = page + 1;
        listar();
      }
    };
    ulPaginacion.appendChild(liNext);
  }

  // ===== Listar (usa tu endpoint MF) =====
  function listar(silencioso = false) {
    const params = new URLSearchParams();
    const subtipo = (selectSubtipo?.value || "").trim();
    const term = (inputBuscar?.value || "").trim();
    const fi = (inpFechaIni?.value || "").trim();
    const ff = (inpFechaFin?.value || "").trim();
    const ordenEta = (inputOrdenEta?.value ?? "asc").trim();
    const estatusSeleccionados = Array.from(
      document.querySelectorAll(".chkFiltroEstatus:checked"),
    )
      .map((chk) => (chk.value || "").trim())
      .filter((v) => v !== "");

    const transportistasSeleccionados = Array.from(
      document.querySelectorAll(".chkFiltroTransportista:checked"),
    )
      .map((chk) => (chk.value || "").trim())
      .filter((v) => v !== "");
    const clientesSeleccionados = Array.from(
      document.querySelectorAll(".chkFiltroCliente:checked"),
    )
      .map((chk) => (chk.value || "").trim())
      .filter((v) => v !== "");

    const brokersSeleccionados = Array.from(
      document.querySelectorAll(".chkFiltroBroker:checked"),
    )
      .map((chk) => (chk.value || "").trim())
      .filter((v) => v !== "");

    const medida = (selectMedida?.value || "").trim();

    if (subtipo !== "") params.append("maritimo_ferro_filtroSubtipo", subtipo);
    if (term !== "") params.append("maritimo_ferro_buscarOperacion", term);
    if (fi !== "") params.append("maritimo_ferro_fechaInicio", fi);
    if (ff !== "") params.append("maritimo_ferro_fechaFin", ff);
    params.append("maritimo_ferro_ordenEta", ordenEta);
    estatusSeleccionados.forEach((id) => {
      params.append("maritimo_ferro_filtroEstatus[]", id);
    });

    transportistasSeleccionados.forEach((id) => {
      params.append("maritimo_ferro_filtroTransportista[]", id);
    });
    clientesSeleccionados.forEach((id) => {
      params.append("maritimo_ferro_filtroCliente[]", id);
    });

    brokersSeleccionados.forEach((id) => {
      params.append("maritimo_ferro_filtroBroker[]", id);
    });
    if (medida !== "")
      params.append("maritimo_ferro_filtroMedidaContenedor", medida);
    const isAll = String(perPage).toLowerCase() === "todos";
    params.append("page", String(isAll ? 1 : currentPage));
    params.append("perPage", String(perPage));
    if (isAll) currentPage = 1;

    const url =
      base_url +
      "Operaciones_maritimo_ferro/listar_operaciones?" +
      params.toString();

    if (currentListXHR && currentListXHR.readyState !== 4) {
      currentListXHR.abort();
    }

    if (!silencioso) renderCargando();
    const x = new XMLHttpRequest();
    currentListXHR = x;
    x.open("GET", url, true);
    x.send();
    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;

      if (currentListXHR !== x) return; // descartar respuestas viejas

      if (x.status !== 200) {
        console.error("listar_operaciones error:", x.responseText);
        renderTabla([]);
        renderPaginacion({ page: 1, total_pages: 1 });
        renderResumen({ total: 0, page: 1, per_page: perPage, total_pages: 1 });
        return;
      }

      let payload = {};
      try {
        payload = JSON.parse(x.responseText);
      } catch (e) {
        payload = {};
      }

      // Este endpoint NO devuelve {status, meta}. Devuelve:
      // { data, from, to, total, page, per_page, total_pages, pagination_html }
      const rows = payload.data || [];
      renderTabla(rows);

      const pp = payload.per_page ?? perPage;
      const isAll = String(pp).toLowerCase() === "todos";

      const meta = {
        total: Number(payload.total || 0),
        page: Number(payload.page || 1),
        per_page: isAll ? "todos" : Number(pp || 10),
        total_pages: isAll ? 1 : Number(payload.total_pages || 1),
      };
      renderPaginacion(meta);
      renderResumen(meta);
    };
  }

  // ===== Validaciones simples =====
  function validarRangoFechas() {
    const fi = (inpFechaIni?.value || "").trim();
    const ff = (inpFechaFin?.value || "").trim();
    if (fi && ff && fi > ff) {
      if (inpFechaIni) inpFechaIni.value = ff;
      if (inpFechaFin) inpFechaFin.value = fi;
    }
  }

  function validarBL() {
    const v = (inpBL?.value || "").trim();
    if (!v) return true;
    return /^[A-Za-z0-9]+$/.test(v);
  }

  // ===== Modal: estado de campos/repeater =====
  function mf_setContenedoresReadonly(isReadonly) {
    if (!repeater) return;
    const items = repeater.querySelectorAll(".contenedor-item");
    items.forEach((it) => {
      const inp = it.querySelector(".contenedor-input_mf");
      const btnAdd = it.querySelector(".btnContAddOne");
      const btnRem = it.querySelector(".btnContRemoveOne");
      if (inp) {
        if (isReadonly) {
          inp.setAttribute("readonly", "readonly");
          inp.classList.add("bg-light");
        } else {
          inp.removeAttribute("readonly");
          inp.classList.remove("bg-light");
        }
      }
      if (btnAdd) btnAdd.disabled = !!isReadonly;
      if (btnRem) btnRem.disabled = !!isReadonly;
    });
  }

  function resetRepeater() {
    if (!repeater) return;
    repeater.innerHTML = "";
    const node = tplContenedor?.content?.cloneNode(true);
    const item = node ? node.querySelector(".contenedor-item") : null;
    if (item) repeater.appendChild(item);
  }

  function resetModal(mode = "create") {
    if (tituloModal) {
      tituloModal.textContent =
        mode === "edit"
          ? "Editar Operación Marítimo-Ferroviaria"
          : "Nueva Operación Marítimo-Ferroviaria";
    }
    if (formOp) formOp.dataset.mode = mode;
    if (txtMercancia) txtMercancia.value = "";
    if (inpIdOperacion) inpIdOperacion.value = "";
    if (inpNumeroOp) inpNumeroOp.value = "";
    setSelectValue(selSubtipo, "");
    setSelectValue(selEstatus, "");
    if (inpETD) inpETD.value = "";
    if (inpETA) inpETA.value = "";
    if (inpUbicacionActual) inpUbicacionActual.value = "";
    if (inpBL) inpBL.value = "";
    if (hidCliente) hidCliente.value = "";
    if (inpClienteNom) inpClienteNom.value = "";
    if (txtNotas) txtNotas.value = "";
    setSelectValue(selNaviera, "");
    setSelectValue(selForwarder, "");
    setSelectValue(selShipper, "");
    setSelectValue(selPuerto, "");
    if (chkISF) chkISF.checked = false;
    if (inpCitaPuerto) inpCitaPuerto.value = "";
    if (selBroker) setSelectValue(selBroker, "");
    if (selTransportista) setSelectValue(selTransportista, "");
    if (pesoInputActual) pesoInputActual.value = "";

    resetRepeater();
    if (btnGuardarOp) btnGuardarOp.setAttribute("disabled", "disabled");

    if (selPuerto) {
      selPuerto.setAttribute("disabled", "disabled"); // regla
      selPuerto.classList.add("bg-light");
    }

    if (mode === "edit") {
      if (selSubtipo) {
        selSubtipo.setAttribute("disabled", "disabled");
        selSubtipo.classList.add("bg-light");
      }
      if (inpNumeroOp) {
        inpNumeroOp.setAttribute("readonly", "readonly");
        inpNumeroOp.classList.add("bg-light");
      }
      mf_setContenedoresReadonly(true);
    } else {
      if (selSubtipo) {
        selSubtipo.removeAttribute("disabled");
        selSubtipo.classList.remove("bg-light");
      }
      if (inpNumeroOp) {
        inpNumeroOp.setAttribute("readonly", "readonly");
        inpNumeroOp.classList.remove("bg-light");
      }
      if (selNaviera) enable(selNaviera);
      if (selForwarder) enable(selForwarder);
      mf_setContenedoresReadonly(false);
    }
  }

  // ===== Subtipo: pedir info (req naviera/forwarder + puerto default) =====
  let subtipoReq = {
    requiere_naviera: 0,
    requiere_forwarder: 0,
    puerto_default: null,
  };
  function fetchSubtipoInfo(subtipoId) {
    return new Promise((resolve) => {
      if (!subtipoId) {
        subtipoReq = {
          requiere_naviera: 0,
          requiere_forwarder: 0,
          puerto_default: null,
        };
        resolve(subtipoReq);
        return;
      }
      const x = new XMLHttpRequest();
      x.open(
        "GET",
        base_url +
          "Operaciones_maritimo_ferro/subtipo_info?id=" +
          encodeURIComponent(subtipoId),
        true,
      );
      x.send();
      x.onreadystatechange = function () {
        if (x.readyState !== 4) return;
        if (x.status !== 200) {
          resolve(subtipoReq);
          return;
        }
        let payload = {};
        try {
          payload = JSON.parse(x.responseText);
        } catch (e) {
          payload = {};
        }
        if (payload.status === "success" && payload.data) {
          const d = payload.data;
          subtipoReq = {
            requiere_naviera: Number(d.requiere_naviera || 0),
            requiere_forwarder: Number(d.requiere_forwarder || 0),
            puerto_default: d.puerto_arribo_default_id ?? null,
          };
        }
        resolve(subtipoReq);
      };
    });
  }

  function applyPuertoDefault() {
    if (!selPuerto) return;
    const def = Number(subtipoReq.puerto_default || 0);
    if (!def) return;
    const has = Array.from(selPuerto.options).some(
      (o) => Number(o.value || 0) === def,
    );
    if (has) selPuerto.value = String(def);
  }

  function validarCamposObligatorios() {
    if (!selSubtipo?.value) return false;
    if (Number(subtipoReq.requiere_naviera) === 1 && !selNaviera?.value)
      return false;
    if (Number(subtipoReq.requiere_forwarder) === 1 && !selForwarder?.value)
      return false;
    return true;
  }

  // ===== Eventos de filtros/listado =====
  // Refrescar al cambiar filtros tipo select
  [selectMedida, selectSubtipo].forEach((sel) => {
    sel?.addEventListener("change", () => {
      currentPage = 1;
      listar();
    });
  });
  inputBuscar?.addEventListener("keyup", () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => {
      currentPage = 1;
      listar();
    }, 250);
  });
  selectPerPage?.addEventListener("change", () => {
    perPage = (selectPerPage.value || "10").toString(); // puede ser "todos"
    currentPage = 1;
    listar();
  });
  inpFechaIni?.addEventListener("change", () => {
    validarRangoFechas();
    currentPage = 1;
    listar();
  });
  inpFechaFin?.addEventListener("change", () => {
    validarRangoFechas();
    currentPage = 1;
    listar();
  });

  window.addEventListener("DOMContentLoaded", () => {
    perPage = (selectPerPage?.value || "10").toString();

    actualizarTextoFiltroEstatus();
    actualizarTextoFiltroTransportista();
    actualizarTextoFiltroCliente();
    actualizarTextoFiltroBroker();
    actualizarTextoOrdenEta();

    listar();

    if (window.feather) feather.replace();
  });

  // ===== Abrir modal: Nueva =====
  btnNuevaOp?.addEventListener("click", () => {
    resetModal("create");
  });

  modalEl?.addEventListener("hidden.bs.modal", () => {
    resetModal("create");
  });

  // ===== Cambio de Subtipo en modal: trae reglas + puerto default =====
  selSubtipo?.addEventListener("change", async () => {
    const sid = Number(selSubtipo.value || 0);
    await fetchSubtipoInfo(sid);
    applyPuertoDefault();
    // Si estás en modo crear (no edición), también rellena folio preliminar
    const idEdit =
      (document.getElementById("id_operacion_mf")?.value || "").trim() !== "";
    if (!idEdit && typeof prefillNumeroPorSubtipoMF === "function") {
      prefillNumeroPorSubtipoMF();
    }
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute("disabled");
    else btnGuardarOp?.setAttribute("disabled", "disabled");
  });

  selNaviera?.addEventListener("change", () => {
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute("disabled");
    else btnGuardarOp?.setAttribute("disabled", "disabled");
  });
  selForwarder?.addEventListener("change", () => {
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute("disabled");
    else btnGuardarOp?.setAttribute("disabled", "disabled");
  });

  // ===== Autocomplete de clientes (endpoint MF) =====
  let xhrCliente = null,
    debounceCliente = null;
  function hideSugCliente() {
    if (boxSugCliente) {
      boxSugCliente.style.display = "none";
      boxSugCliente.innerHTML = "";
    }
  }
  function showSugCliente() {
    if (boxSugCliente) {
      boxSugCliente.style.display = "block";
    }
  }
  function setCliente(id, nombre) {
    if (hidCliente) hidCliente.value = String(id || "");
    if (inpClienteNom) inpClienteNom.value = nombre || "";
    hideSugCliente();
  }
  function renderSugClientes(list) {
    boxSugCliente.innerHTML = "";
    if (!Array.isArray(list) || list.length === 0) {
      hideSugCliente();
      return;
    }
    list.forEach((cli) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = cli.nombre;
      btn.addEventListener("mousedown", (e) => {
        e.preventDefault();
        setCliente(cli.id_cliente, cli.nombre);
      });
      boxSugCliente.appendChild(btn);
    });
    showSugCliente();
  }
  function buscarClientesAjax(q) {
    if (xhrCliente && xhrCliente.readyState !== 4) xhrCliente.abort();
    xhrCliente = new XMLHttpRequest();
    xhrCliente.open(
      "GET",
      base_url +
        "Operaciones_maritimo_ferro/autocomplete_clientes?q=" +
        encodeURIComponent(q),
      true,
    );
    xhrCliente.send();
    xhrCliente.onreadystatechange = function () {
      if (xhrCliente.readyState !== 4) return;
      if (xhrCliente.status !== 200) {
        hideSugCliente();
        return;
      }
      let payload = {};
      try {
        payload = JSON.parse(xhrCliente.responseText);
      } catch (e) {
        payload = {};
      }
      const list = payload.status === "success" ? payload.data || [] : [];
      renderSugClientes(list);
    };
  }
  inpClienteNom?.addEventListener("keyup", (e) => {
    const q = (inpClienteNom.value || "").trim();
    if (hidCliente && q.length >= 0) hidCliente.value = "";
    if (e.key === "Escape") {
      hideSugCliente();
      return;
    }
    if (q.length < 2) {
      hideSugCliente();
      return;
    }
    clearTimeout(debounceCliente);
    debounceCliente = setTimeout(() => buscarClientesAjax(q), 220);
  });
  inpClienteNom?.addEventListener("blur", () =>
    setTimeout(hideSugCliente, 150),
  );
  document.addEventListener("click", (ev) => {
    if (!boxSugCliente) return;
    const inside =
      boxSugCliente.contains(ev.target) || inpClienteNom === ev.target;
    if (!inside) hideSugCliente();
  });

  // ===== Repeater contenedores (buscar contenedores marítimos)
  const debounceMap = new WeakMap();
  const xhrMap = new WeakMap();

  function hideBox(box) {
    if (box) {
      box.style.display = "none";
      box.innerHTML = "";
    }
  }
  function showBox(box) {
    if (box) {
      box.style.display = "block";
    }
  }
  function setContenedor($item, id, numero) {
    const hid = $item.querySelector(".contenedor-id_mf");
    const inp = $item.querySelector(".contenedor-input_mf");
    const box = $item.querySelector(".sugerencias-contenedor_mf");
    if (hid) hid.value = String(id || "");
    if (inp) inp.value = numero || "";
    hideBox(box);
  }

  function addRow(afterItem = null) {
    const node = tplContenedor.content.cloneNode(true);
    const newItem = node.querySelector(".contenedor-item");
    if (afterItem && afterItem.parentNode === repeater) {
      afterItem.insertAdjacentElement("afterend", newItem);
    } else {
      repeater.appendChild(newItem);
    }
    if (window.feather) feather.replace();
    return newItem;
  }

  function removeRow(item) {
    const items = repeater.querySelectorAll(".contenedor-item");
    if (items.length <= 1) {
      const hid = item.querySelector(".contenedor-id_mf");
      const inp = item.querySelector(".contenedor-input_mf");
      if (hid) hid.value = "";
      if (inp) inp.value = "";
      const box = item.querySelector(".sugerencias-contenedor_mf");
      hideBox(box);
      return;
    }
    item.remove();
  }

  function buscarContenedoresAjax(inputEl, q) {
    const prev = xhrMap.get(inputEl);
    if (prev && prev.readyState !== 4) prev.abort();

    const x = new XMLHttpRequest();
    xhrMap.set(inputEl, x);
    x.open(
      "GET",
      base_url +
        "Operaciones_maritimo_ferro/buscar_contenedores_mar?q=" +
        encodeURIComponent(q),
      true,
    );
    x.send();
    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;
      if (xhrMap.get(inputEl) !== x) return;

      const item = inputEl.closest(".contenedor-item");
      const box = item?.querySelector(".sugerencias-contenedor_mf");
      if (x.status !== 200) {
        hideBox(box);
        return;
      }

      let payload = {};
      try {
        payload = JSON.parse(x.responseText);
      } catch (e) {
        payload = {};
      }
      const data = payload.status === "success" ? payload.data || [] : [];

      box.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        hideBox(box);
        return;
      }

      data.forEach((row) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "list-group-item list-group-item-action";
        btn.textContent = row.numero_contenedor;
        btn.addEventListener("mousedown", (ev) => {
          ev.preventDefault();
          const contItem = inputEl.closest(".contenedor-item");
          setContenedor(
            contItem,
            row.id_contenedor_maritimo,
            row.numero_contenedor,
          );
        });
        box.appendChild(btn);
      });
      showBox(box);
    };
  }

  repeater?.addEventListener("click", (ev) => {
    const target = ev.target.closest("button");
    if (!target) return;
    if (target.classList.contains("btnContAddOne")) {
      addRow(target.closest(".contenedor-item"));
      return;
    }
    if (target.classList.contains("btnContRemoveOne")) {
      removeRow(target.closest(".contenedor-item"));
      return;
    }
  });

  repeater?.addEventListener("keyup", (ev) => {
    const inp = ev.target.closest(".contenedor-input_mf");
    if (!inp) return;
    const item = inp.closest(".contenedor-item");
    const box = item?.querySelector(".sugerencias-contenedor_mf");

    const hid = item.querySelector(".contenedor-id_mf");
    if (hid) hid.value = "";

    if (ev.key === "Escape") {
      hideBox(box);
      return;
    }

    const q = (inp.value || "").trim();
    if (q.length < 3) {
      hideBox(box);
      return;
    }

    const prevTO = debounceMap.get(inp);
    if (prevTO) clearTimeout(prevTO);
    const to = setTimeout(() => buscarContenedoresAjax(inp, q), 220);
    debounceMap.set(inp, to);
  });

  repeater?.addEventListener(
    "blur",
    (ev) => {
      const inp = ev.target.closest(".contenedor-input_mf");
      if (!inp) return;
      const item = inp.closest(".contenedor-item");
      const box = item?.querySelector(".sugerencias-contenedor_mf");
      setTimeout(() => hideBox(box), 150);
    },
    true,
  );

  document.addEventListener("click", (ev) => {
    const anyBox = document.querySelectorAll(
      "#contenedoresRepeater_mf .sugerencias-contenedor_mf",
    );
    anyBox.forEach((box) => {
      const input = box
        .closest(".contenedor-item")
        ?.querySelector(".contenedor-input_mf");
      const inside = box.contains(ev.target) || input === ev.target;
      if (!inside) hideBox(box);
    });
  });

  // Solo números no negativos para bultos
  repeater?.addEventListener("input", (e) => {
    const el = e.target.closest(".contenedor-bultos_mf");
    if (!el) return;
    const val = el.value.trim();
    if (val !== "" && (!/^\d+$/.test(val) || Number(val) < 0)) el.value = "";
  });

  // ===== Cargar operación para editar =====
  function cargarOperacionParaEditar(id) {
    resetModal("edit");

    const x = new XMLHttpRequest();
    x.open(
      "GET",
      base_url +
        "Operaciones_maritimo_ferro/obtener_operacion?id=" +
        encodeURIComponent(id),
      true,
    );
    x.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    x.send();

    x.onreadystatechange = async function () {
      if (x.readyState !== 4) return;

      if (x.status !== 200) {
        console.error("obtener_operacion error:", x.responseText);
        Swal?.fire("Error", "No se pudo obtener la operación", "error");
        return;
      }

      let payload = {};
      try {
        payload = JSON.parse(x.responseText);
      } catch (e) {
        payload = {};
      }

      if (payload.status !== "success" || !payload.data) {
        Swal?.fire(
          "Aviso",
          payload.msg || "Operación no encontrada",
          "warning",
        );
        return;
      }

      const op = payload.data;

      // ✅ reglas de subtipo (si aplica)
      await fetchSubtipoInfo(Number(op.subtipo_operacion_id || 0));

      // ===== Helpers internos =====
      const val = (v) => (v === undefined || v === null ? "" : String(v));
      const pick = (obj, keys, fallback = "") => {
        for (const k of keys) {
          const v = obj?.[k];
          if (v !== undefined && v !== null && String(v).trim() !== "")
            return v;
        }
        return fallback;
      };

      // ===== Pintar campos =====
      if (inpIdOperacion) inpIdOperacion.value = val(op.id_operacion);
      if (inpNumeroOp) inpNumeroOp.value = val(op.numero_operacion);
      if (txtMercancia) {
        txtMercancia.value = val(op.mercancia ?? op.descripcion_mercancia);
      }
      setSelectValue(selSubtipo, op.subtipo_operacion_id);
      setSelectValue(selEstatus, op.estatus_id);

      if (inpETD) inpETD.value = val(op.etd);
      if (inpETA) inpETA.value = val(op.eta);
      if (inpUbicacionActual)
        inpUbicacionActual.value = val(op.ubicacion_actual);
      if (inpBL) inpBL.value = val(op.numero_bl);

      if (hidCliente) hidCliente.value = val(op.cliente_id);
      if (inpClienteNom) inpClienteNom.value = val(op.cliente_nombre);

      if (txtNotas) txtNotas.value = val(op.notas);

      if (chkISF) chkISF.checked = Number(op.isf) === 1;

      if (inpCitaPuerto) {
        const raw = val(op.cita_puerto).trim();
        inpCitaPuerto.value = raw ? raw.slice(0, 10) : "";
      }

      // selects catálogos
      setSelectValue(selNaviera, op.naviera_id);
      setSelectValue(selForwarder, op.forwarder_id);
      setSelectValue(selShipper, op.shipper_id);
      setSelectValue(selBroker, op.broker_id);
      setSelectValue(selTransportista, op.transportista_id);

      // Puerto: si viene, úsalo; si no, default del subtipo
      if (selPuerto) {
        const p = pick(
          op,
          ["puerto_arribo_id_prefill", "puerto_arribo_id"],
          "",
        );
        if (p) setSelectValue(selPuerto, p);
        else applyPuertoDefault();
      }

      // =========================================================
      // ✅ CONTENEDORES (tu repeater borra el DOM, incluido el peso
      // si tu input de peso está dentro del repeater)
      // =========================================================
      if (repeater) repeater.innerHTML = "";

      const conts = Array.isArray(op.contenedores) ? op.contenedores : [];
      if (conts.length) {
        conts.forEach((c) => {
          const row = addRow();

          const cid = pick(
            c,
            ["id_contenedor_maritimo", "id", "contenedor_id"],
            "",
          );
          const cnum = pick(c, ["numero_contenedor", "numero", "codigo"], "");
          const cbul = pick(c, ["bultos", "bultos_total"], "");
          const ctpo = pick(c, ["tipo_contenedor", "tipo"], "");
          const ctPeso = pick(c, ["peso_total", "peso_total"], "Sin peso");

          const hid = row.querySelector(".contenedor-id_mf");
          const inp = row.querySelector(".contenedor-input_mf");
          const inpBul = row.querySelector(".contenedor-bultos_mf");
          const inpTipo = row.querySelector(".contenedor-tipo_mf");

          if (hid) hid.value = val(cid);
          if (inp) inp.value = val(cnum);
          if (inpBul) inpBul.value = val(cbul);
          if (inpTipo) inpTipo.value = val(ctpo);
        });
      } else {
        // si no hay contenedores, deja al menos 1 fila limpia
        resetRepeater();
      }

      // ✅ AHORA SÍ: setear el peso DESPUÉS de reconstruir el repeater
      // (porque antes se lo tragaba el innerHTML="")
      if (pesoInputActual) {
        pesoInputActual.value = val(op.peso_total);
      } else {
        // fallback si el input se busca por selector dentro del modal
        const modalEl = document.getElementById("modalMaritimoFerro");
        const pesoEl =
          modalEl?.querySelector("#pesoOperacion_mf") ||
          modalEl?.querySelector(".pesoOperacion_mf");
        if (pesoEl) pesoEl.value = val(op.peso_total);
      }

      // En edición: readonly a contenedores
      mf_setContenedoresReadonly(true);

      // ✅ IMPORTANTE: habilitar Guardar en edición
      btnGuardarOp?.removeAttribute("disabled");

      // Mostrar modal
      const el = document.getElementById("modalMaritimoFerro");
      const modal =
        el && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(el) : null;
      modal?.show();

      if (window.feather) feather.replace();
    };
  }

  function actualizarTextoFiltroEstatus() {
    const seleccionados = Array.from(
      document.querySelectorAll(".chkFiltroEstatus:checked"),
    );

    if (!txtFiltroEstatus) return;

    if (seleccionados.length === 0) {
      txtFiltroEstatus.textContent = "Estatus";
      return;
    }

    if (seleccionados.length === 1) {
      const label = seleccionados[0].closest("label");
      const texto = label ? label.innerText.trim() : "1 seleccionado";
      txtFiltroEstatus.textContent = texto;
      return;
    }

    txtFiltroEstatus.textContent = `${seleccionados.length} estatus seleccionados`;
  }

  function actualizarTextoFiltroTransportista() {
    const seleccionados = Array.from(
      document.querySelectorAll(".chkFiltroTransportista:checked"),
    );

    if (!txtFiltroTransportista) return;

    if (seleccionados.length === 0) {
      txtFiltroTransportista.textContent = "Transportista";
      return;
    }

    if (seleccionados.length === 1) {
      const label = seleccionados[0].closest("label");
      const texto = label ? label.innerText.trim() : "1 seleccionado";
      txtFiltroTransportista.textContent = texto;
      return;
    }

    txtFiltroTransportista.textContent = `${seleccionados.length} transportistas seleccionados`;
  }

  function actualizarTextoFiltroCliente() {
    const seleccionados = Array.from(
      document.querySelectorAll(".chkFiltroCliente:checked"),
    );

    if (!txtFiltroCliente) return;

    if (seleccionados.length === 0) {
      txtFiltroCliente.textContent = "Cliente";
      return;
    }

    if (seleccionados.length === 1) {
      const label = seleccionados[0].closest("label");
      const texto = label ? label.innerText.trim() : "1 seleccionado";
      txtFiltroCliente.textContent = texto;
      return;
    }

    txtFiltroCliente.textContent = `${seleccionados.length} clientes seleccionados`;
  }

  function actualizarTextoFiltroBroker() {
    const seleccionados = Array.from(
      document.querySelectorAll(".chkFiltroBroker:checked"),
    );

    if (!txtFiltroBroker) return;

    if (seleccionados.length === 0) {
      txtFiltroBroker.textContent = "Broker";
      return;
    }

    if (seleccionados.length === 1) {
      const label = seleccionados[0].closest("label");
      const texto = label ? label.innerText.trim() : "1 seleccionado";
      txtFiltroBroker.textContent = texto;
      return;
    }

    txtFiltroBroker.textContent = `${seleccionados.length} brokers seleccionados`;
  }

  function actualizarTextoOrdenEta() {
    if (!txtOrdenEta || !inputOrdenEta) return;

    const valor = (inputOrdenEta.value ?? "asc").trim();

    if (valor === "desc") {
      txtOrdenEta.textContent = "ETA: Recientes primero";
      return;
    }

    if (valor === "") {
      txtOrdenEta.textContent = "Orden ETA";
      return;
    }

    txtOrdenEta.textContent = "ETA: Antiguas primero";
  }

  checksEstatus.forEach((chk) => {
    chk.addEventListener("change", () => {
      currentPage = 1;
      actualizarTextoFiltroEstatus();
      listar();
    });
  });

  if (btnLimpiarFiltroEstatus) {
    btnLimpiarFiltroEstatus.addEventListener("click", () => {
      checksEstatus.forEach((chk) => {
        chk.checked = false;
      });

      currentPage = 1;
      actualizarTextoFiltroEstatus();
      listar();
    });
  }
  checksTransportista.forEach((chk) => {
    chk.addEventListener("change", () => {
      currentPage = 1;
      actualizarTextoFiltroTransportista();
      listar();
    });
  });

  if (btnLimpiarFiltroTransportista) {
    btnLimpiarFiltroTransportista.addEventListener("click", () => {
      checksTransportista.forEach((chk) => {
        chk.checked = false;
      });

      currentPage = 1;
      actualizarTextoFiltroTransportista();
      listar();
    });
  }

  checksCliente.forEach((chk) => {
    chk.addEventListener("change", () => {
      currentPage = 1;
      actualizarTextoFiltroCliente();
      listar();
    });
  });

  if (btnLimpiarFiltroCliente) {
    btnLimpiarFiltroCliente.addEventListener("click", () => {
      checksCliente.forEach((chk) => {
        chk.checked = false;
      });

      currentPage = 1;
      actualizarTextoFiltroCliente();
      listar();
    });
  }

  checksBroker.forEach((chk) => {
    chk.addEventListener("change", () => {
      currentPage = 1;
      actualizarTextoFiltroBroker();
      listar();
    });
  });

  if (btnLimpiarFiltroBroker) {
    btnLimpiarFiltroBroker.addEventListener("click", () => {
      checksBroker.forEach((chk) => {
        chk.checked = false;
      });

      currentPage = 1;
      actualizarTextoFiltroBroker();
      listar();
    });
  }
  opcionesOrdenEta.forEach((opcion) => {
    opcion.addEventListener("click", (e) => {
      e.preventDefault();

      const valor = (opcion.dataset.value ?? "asc").trim();
      const texto = (opcion.dataset.text ?? "").trim();

      if (inputOrdenEta) {
        inputOrdenEta.value = valor;
      }

      if (txtOrdenEta) {
        if (texto !== "") {
          txtOrdenEta.textContent = texto;
        } else {
          actualizarTextoOrdenEta();
        }
      }

      currentPage = 1;
      listar();
    });
  });
  // Delegación: click en botón Editar de la tabla
  tablaBody?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-edit-mf");
    if (!btn) return;
    const id = parseInt(btn.getAttribute("data-id") || "0", 10);
    if (!id) return;
    cargarOperacionParaEditar(id);
  });

  function guardarEdicionMF() {
    const modalEl = document.getElementById("modalMaritimoFerro");
    const form = document.getElementById("formOperacionMaritimoFerro");
    const id = parseInt(
      document.getElementById("id_operacion_mf")?.value || "0",
      10,
    );

    if (!form) {
      Swal?.fire("Error", "No se encontró el formulario.", "error");
      return;
    }
    if (!id) {
      Swal?.fire("Error", "Falta id de la operación.", "error");
      return;
    }
    const ubicacionActual = (inpUbicacionActual?.value || "").trim();

    if (ubicacionActual.length > 250) {
      Swal?.fire(
        "Ubicación actual muy larga",
        "La ubicación actual no puede superar los 250 caracteres.",
        "warning",
      );
      inpUbicacionActual?.focus();
      return;
    }

    // ---- helpers locales para UI (duplicado contenedor) ----
    const clearContenedorInvalid = () => {
      document
        .querySelectorAll("#contenedoresRepeater_mf .contenedor-input_mf")
        .forEach((el) => el.classList.remove("is-invalid"));
    };

    const markContenedorInvalid = (numero) => {
      const needle = String(numero || "")
        .trim()
        .toUpperCase();
      if (!needle) return false;

      let first = null;

      document
        .querySelectorAll("#contenedoresRepeater_mf .contenedor-item")
        .forEach((row) => {
          const inp = row.querySelector(".contenedor-input_mf");
          if (!inp) return;

          const val = String(inp.value || "")
            .trim()
            .toUpperCase();

          if (val && val === needle) {
            inp.classList.add("is-invalid");
            if (!first) first = inp;
          }
        });

      if (first) {
        try {
          first.scrollIntoView({ behavior: "smooth", block: "center" });
        } catch (e) {}
        first.focus?.();
        first.select?.();
        return true;
      }
      return false;
    };

    // Limpia marcados previos
    clearContenedorInvalid();

    // ✅ 1) Tomar TODO desde los name="" reales de la vista
    const fd = new FormData(form);

    // Asegurar ID (por si acaso)
    fd.set("id_operacion_mf", String(id));

    fd.set(
      "ubicacion_actual_mf",
      (inpUbicacionActual?.value || "").trim().toUpperCase(),
    );
    // ✅ 2) Normalizar BL (tu input tiene name="numero_bl_mf")
    const blInp = document.getElementById("numeroBL_mf");
    const bl = (blInp?.value || "").replace(/[^A-Za-z0-9]/g, "").toUpperCase();
    fd.set("numero_bl_mf", bl);

    // ✅ 3) ISF: tu controlador usa isset($_POST['isf'])
    const chkIsf = document.getElementById("chkIsf");
    fd.delete("isf");
    if (chkIsf && chkIsf.checked) {
      fd.set("isf", "1");
    }

    // ✅ 4) Contenedores: el repeater NO tiene name="", así que hay que agregarlos aquí
    fd.delete("contenedores_id[]");
    fd.delete("contenedores_codigo[]");
    fd.delete("contenedores_bultos[]");
    fd.delete("contenedores_tipo[]");
    fd.delete("contenedores_peso[]");

    const rows = document.querySelectorAll(
      "#contenedoresRepeater_mf .contenedor-item",
    );
    rows.forEach((row) => {
      const idInp = row.querySelector(".contenedor-id_mf");
      const numInp = row.querySelector(".contenedor-input_mf");
      const bInp = row.querySelector(".contenedor-bultos_mf");

      const tSel = row.querySelector(".contenedor-tipo_mf");
      const pInp = row.querySelector(".pesoOperacion_mf");

      const cid = (idInp?.value || "").trim();
      const codigo = (numInp?.value || "").trim().toUpperCase();
      const bultos = (bInp?.value || "").trim();
      const tipo = (tSel?.value || "").trim();
      const peso = (pInp?.value || "").trim();

      if (!cid) return;

      fd.append("contenedores_id[]", cid);
      fd.append("contenedores_codigo[]", codigo);
      fd.append("contenedores_bultos[]", bultos);
      fd.append("contenedores_tipo[]", tipo);
      fd.append("contenedores_peso[]", peso);
    });

    // ✅ 5) Enviar
    const x = new XMLHttpRequest();
    x.open("POST", base_url + "Operaciones_maritimo_ferro/actualizar", true);

    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;

      let res = null;
      try {
        res = JSON.parse(x.responseText || "{}");
      } catch (e) {}

      if (!res || x.status !== 200) {
        Swal?.fire("Error", "No se pudo actualizar.", "error");
        return;
      }

      if (res.status === "success") {
        Swal?.fire(
          "Actualizado",
          res.data?.msg || "Operación actualizada",
          "success",
        );

        bootstrap?.Modal.getOrCreateInstance(modalEl)?.hide();

        try {
          listar?.();
        } catch (e) {}
        return;
      }

      // ✅ NUEVO: warning con DUP_CONT_MES
      if (res.status === "warning") {
        clearContenedorInvalid();

        if (res.code === "DUP_CONT_MES") {
          const cont = res.data?.contenedor || "";
          const op = res.data?.operacion || "";
          const mes = res.data?.mes || "";

          markContenedorInvalid(cont);

          Swal?.fire(
            "Contenedor duplicado",
            res.msg ||
              `El contenedor ${cont} ya está asignado a ${op} en ${mes}.`,
            "warning",
          );
          return;
        }

        Swal?.fire("Atención", res.msg || "Revisa los datos.", "warning");
        return;
      }

      // error normal
      Swal?.fire("Error", res.msg || "No se pudo actualizar", "error");
    };

    x.send(fd);
  }

  // Habilitar/deshabilitar Guardar cuando cambian campos clave
  [selSubtipo, selNaviera, selForwarder].forEach((el) => {
    el?.addEventListener("change", () => {
      if (validarCamposObligatorios())
        btnGuardarOp?.removeAttribute("disabled");
      else btnGuardarOp?.setAttribute("disabled", "disabled");
    });
  });

  // Al mostrar el modal, si hay subtipo seleccionado aplica default de puerto
  modalEl?.addEventListener("shown.bs.modal", () => {
    if (!selSubtipo?.value) return;
    applyPuertoDefault();
    if (validarCamposObligatorios()) btnGuardarOp?.removeAttribute("disabled");
  });

  btnGuardarOp?.addEventListener("click", (e) => {
    e.preventDefault();

    // Validaciones básicas que ya tienes
    if (!validarBL()) {
      Swal?.fire(
        "BL inválido",
        "El BL solo debe contener letras y números.",
        "warning",
      );
      inpBL?.focus();
      return;
    }
    if (!validarCamposObligatorios()) {
      Swal?.fire(
        "Faltan datos",
        "Completa los campos obligatorios.",
        "warning",
      );
      return;
    }

    // ¿Crear o Editar? -> lo define resetModal('create'|'edit')
    const mode = formOp?.dataset?.mode || "create";

    if (mode === "edit") {
      // Usará tu función que POSTea a /actualizar
      guardarEdicionMF();
    } else {
      // Crear: si tu función global de alta está en el otro JS, úsala
      if (typeof window.guardarOperacionMF === "function") {
        // tu guardarOperacionMF() devuelve una Promise<boolean>
        window.guardarOperacionMF().then((ok) => {
          if (ok) {
            // Cierra el modal y refresca la tabla del listado
            window.bootstrap
              ? bootstrap.Modal.getOrCreateInstance(modalEl).hide()
              : null;
            listar();
          }
        });
      } else {
        console.warn("No se encontró window.guardarOperacionMF para crear.");
      }
    }
  });

  // =========================================================
  // ✅ Bridge: refrescar tabla cuando otro módulo actualiza
  //    asignaciones o trazabilidad
  // =========================================================
  (function bindRefreshEvents() {
    let refreshTO = null;

    function requestRefresh(detail) {
      // opcional: si quieres forzar volver a página 1
      // currentPage = 1;

      // Debounce corto para evitar 2-3 refrescos seguidos
      clearTimeout(refreshTO);
      refreshTO = setTimeout(() => {
        try {
          listar();
        } catch (e) {
          console.warn("No se pudo refrescar MF listar()", e);
        }
      }, 120);
    }

    // Evento único para todo MF
    document.addEventListener("mf:refresh-list", (ev) => {
      requestRefresh(ev.detail || {});
    });

    // (Opcional) si quieres diferenciar acciones
    document.addEventListener("mf:asignacion-updated", (ev) => {
      requestRefresh(ev.detail || {});
    });
    document.addEventListener("mf:trazabilidad-updated", (ev) => {
      requestRefresh(ev.detail || {});
    });
  })();

  // =========================================================
  // AUTO-REFRESH: dentro del IIFE para poder acceder a listar()
  // =========================================================
  (function () {
    var INTERVALO_MS = 15000;
    var autoRefreshTimer = null;
    var pausado = false;

    function iniciar() {
      if (autoRefreshTimer) clearInterval(autoRefreshTimer);
      autoRefreshTimer = setInterval(function () {
        if (!pausado && !inlineEditandoMF) listar(true);
      }, INTERVALO_MS);
    }

    var modalPrincipal = document.getElementById("modalMaritimoFerro");
    var modalAsignacion = document.getElementById("modalAsignarFerroCaja");

    [modalPrincipal, modalAsignacion].forEach(function (m) {
      if (!m) return;
      m.addEventListener("show.bs.modal", function () {
        pausado = true;
      });
      m.addEventListener("hidden.bs.modal", function () {
        pausado = false;
        listar(true);
      });
    });

    iniciar();
  })();
})();
// ===============================
// Exportaciones
// ===============================
document
  .getElementById("operaciones_mar_ExportarExcel")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "operaciones_mar_TablaExportar",
      formato: "xlsx",
      nombre: "OperacionesMaritimas.xlsx",
      columnasOcultas: [25],
      soloVisibles: true,
      sheetName: "Contenedores En Operacion",
    });
  });

document
  .getElementById("operaciones_mar_ExportarPDF")
  ?.addEventListener("click", () => {
    ExportarTablas.exportar({
      ref: "#operaciones_mar_TablaExportar",
      formato: "pdf",
      nombre: "OperacionesMaritimas.pdf",
      titulo: "OperacionesMaritimas",
      orientacion: "landscape",
      formatoPagina: "letter",
      columnasOcultas: [25],
      soloVisibles: false,
    });
  });
