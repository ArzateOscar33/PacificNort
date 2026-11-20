/* ============================================================================
 * PacificNort Suite - Traslados de Mercancia (Maritimo-Ferroviario)
 * Archivo: Assets/Js/ModulosAdmin/operaciones_maritimoferro/traslados_mercancia.js
 *
 * Compatible con:
 * - Vista parcial: tabs/modal_traslados.php
 * - Controlador: Operaciones_maritimo_ferro_traslados
 * - Modelo: Operaciones_maritimo_ferro_trasladosModel
 * - Listado principal: #maritimo_ferro_tablaBody, botones .btn-edit-mf[data-id]
 *
 * Arquitectura: IIFE; eventos delegados; peticiones AJAX fetch; Bootstrap 5;
 * alertas SweetAlert2 con fallback. No modifica el JS principal.
 * Las validaciones contables y transacciones se realizan SOLO en el servidor.
 * ========================================================================== */
(function () {
  "use strict";

  function iniciarTrasladosMF() {
    const modal = document.getElementById("modalTrasladosMf");
    const tablaOperaciones = document.getElementById("maritimo_ferro_tablaBody");
    const plantilla = document.getElementById("mfTrPlantillaFila");
    if (!modal || !tablaOperaciones || !plantilla) return;
    if (modal.dataset.mfTrJsIniciado === "1") return;
    modal.dataset.mfTrJsIniciado = "1";

    const baseRaw =
      (typeof window.base_url === "string" && window.base_url) ||
      (typeof window.BASE_URL === "string" && window.BASE_URL) ||
      (typeof window.base === "string" && window.base) ||
      (typeof base_url !== "undefined" && typeof base_url === "string" && base_url) ||
      "";
    const BASE = baseRaw && !baseRaw.endsWith("/") ? baseRaw + "/" : baseRaw;
    const ENDPOINT = BASE + "Operaciones_maritimo_ferro_traslados/";
    const MAX_LOTE = 100;
    const MONTO_REGEX = /^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/;

    const dom = {
      opId: document.getElementById("mfTrOperacionId"),
      numero: document.getElementById("mfTrNumeroOperacion"),
      contenedores: document.getElementById("mfTrContenedores"),
      concepto: document.getElementById("mfTrConcepto"),
      estado: document.getElementById("mfTrEstado"),
      advertencia: document.getElementById("mfTrAdvertencia"),
      error: document.getElementById("mfTrError"),
      verCancelados: document.getElementById("mfTrMostrarCancelados"),
      btnRecargar: document.getElementById("mfTrBtnRecargar"),
      btnAgregar: document.getElementById("mfTrBtnAgregarFila"),
      btnGuardar: document.getElementById("mfTrBtnGuardarPendientes"),
      tbody: document.getElementById("mfTrTablaBody"),
      conteo: document.getElementById("mfTrConteo"),
      pendientes: document.getElementById("mfTrPendientes"),
      total: document.getElementById("mfTrTotalUsd"),
      mensaje: document.getElementById("mfTrMensaje"),
    };

    const state = {
      operacionId: 0,
      token: 0,
      ocupado: false,
      listo: false,
      puedeRegistrar: false,
      filas: [],
      resumen: { total_tramos: 0, total_usd: "0.00" },
      catalogos: { ubicaciones: [], transportistas: [] },
      editandoId: null,
      permitirCierre: false,
      confirmandoCierre: false,
    };

    // Evita interpolar HTML proveniente de MySQL. Solo se utiliza textContent.
    function texto(elemento, valor) {
      if (elemento) elemento.textContent = valor == null ? "" : String(valor);
    }

    function iconos() {
      if (window.feather && typeof window.feather.replace === "function") {
        window.feather.replace();
      }
    }

    function boton(etiqueta, clases, accion, icono) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = clases;
      if (accion) btn.dataset.mfAccion = accion;
      if (icono) {
        const i = document.createElement("i");
        i.setAttribute("data-feather", icono);
        i.className = "me-1";
        btn.appendChild(i);
      }
      btn.appendChild(document.createTextNode(etiqueta));
      return btn;
    }

    function td(valor, clase) {
      const celda = document.createElement("td");
      if (clase) celda.className = clase;
      if (valor !== undefined) texto(celda, valor);
      return celda;
    }

    function etiqueta(textoEstado, css) {
      const el = document.createElement("span");
      el.className = "badge " + css;
      texto(el, textoEstado);
      return el;
    }

    function vaciar(elemento) {
      if (elemento) elemento.replaceChildren();
    }

    function alertaInline(elemento, mensaje) {
      if (!elemento) return;
      texto(elemento, mensaje || "");
      elemento.classList.toggle("d-none", !mensaje);
    }

    function avisar(icono, titulo, mensaje) {
      if (window.Swal && typeof window.Swal.fire === "function") {
        return window.Swal.fire({ icon: icono, title: titulo, text: mensaje || "", confirmButtonText: "Aceptar" });
      }
      window.alert(titulo + (mensaje ? "\n" + mensaje : ""));
      return Promise.resolve();
    }

    function confirmar(titulo, mensaje, textoAceptar) {
      if (window.Swal && typeof window.Swal.fire === "function") {
        return window.Swal.fire({
          icon: "warning",
          title: titulo,
          text: mensaje,
          showCancelButton: true,
          confirmButtonText: textoAceptar || "Confirmar",
          cancelButtonText: "Volver",
          reverseButtons: true,
        }).then((r) => Boolean(r.isConfirmed));
      }
      return Promise.resolve(window.confirm(titulo + "\n" + mensaje));
    }

    function formatearUSD(valor) {
      const numero = Number(valor);
      return "US$" + new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number.isFinite(numero) ? numero : 0);
    }

    function normalizarMonto(valor) {
      const raw = String(valor == null ? "" : valor).trim().replace(",", ".");
      if (!MONTO_REGEX.test(raw)) {
        throw new Error("Indica un monto USD válido (0.00 o mayor, máximo dos decimales).");
      }
      const partes = raw.split(".");
      return partes[0] + "." + (partes[1] || "").padEnd(2, "0");
    }

    async function solicitar(ruta, opciones) {
      const url = ENDPOINT + ruta;
      let respuesta;
      try {
        respuesta = await fetch(url, {
          credentials: "same-origin",
          headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
          cache: "no-store",
          ...(opciones || {}),
        });
      } catch (e) {
        throw new Error("No se pudo conectar con PacificNort. Comprueba tu conexión.");
      }
      const contentType = respuesta.headers.get("content-type") || "";
      if (!contentType.includes("application/json")) {
        throw new Error("El servidor no devolvió JSON. Revisa la sesión, la ruta y los errores PHP.");
      }
      let json;
      try {
        json = await respuesta.json();
      } catch (e) {
        throw new Error("La respuesta del servidor no contiene un JSON válido.");
      }
      if (!respuesta.ok || !json || json.status !== "success") {
        throw new Error((json && (json.message || json.msg)) || "No fue posible completar la solicitud.");
      }
      return json;
    }

    function post(ruta, payload) {
      const fd = new FormData();
      Object.keys(payload).forEach((clave) => fd.append(clave, payload[clave]));
      return solicitar(ruta, { method: "POST", body: fd });
    }

    function establecerOcupado(valor) {
      state.ocupado = Boolean(valor);
      actualizarControles();
      modal.querySelectorAll(".mf-tr-fila .form-control").forEach((input) => {
        input.disabled = state.ocupado;
      });
      modal.querySelectorAll(".mf-tr-fila button").forEach((btn) => {
        btn.disabled = state.ocupado;
      });
    }

    function borradores() {
      return Array.from(dom.tbody.querySelectorAll("tr[data-estado='nuevo']"));
    }

    function hayCambiosSinGuardar() {
      return borradores().length > 0 || state.editandoId !== null;
    }

    function actualizarControles() {
      const pend = borradores().length;
      const permiso = state.listo && state.puedeRegistrar;
      dom.btnAgregar.disabled = state.ocupado || !permiso || pend >= MAX_LOTE || state.editandoId !== null;
      dom.btnGuardar.disabled = state.ocupado || !permiso || pend === 0 || state.editandoId !== null;
      dom.btnRecargar.disabled = state.ocupado || !state.operacionId;
      dom.verCancelados.disabled = state.ocupado || !state.listo;
      texto(dom.pendientes, `${pend} ${pend === 1 ? "pendiente" : "pendientes"}`);
      const activas = Number(state.resumen.total_tramos || 0);
      texto(dom.conteo, `${activas} ${activas === 1 ? "tramo activo" : "tramos activos"}`);
      texto(dom.total, formatearUSD(state.resumen.total_usd));
      texto(dom.mensaje, state.ocupado ? "Procesando solicitud…" : "");
    }

    function mostrarEstado(estado) {
      if (!dom.estado) return;
      if (estado === "cargando") {
        dom.estado.className = "badge bg-secondary";
        texto(dom.estado, "Cargando…");
      } else if (estado === "listo") {
        dom.estado.className = "badge bg-success";
        texto(dom.estado, "Disponible");
      } else {
        dom.estado.className = "badge bg-warning text-dark";
        texto(dom.estado, "Solo consulta");
      }
    }

    function resetear() {
      state.token += 1;
      state.operacionId = 0;
      state.ocupado = false;
      state.listo = false;
      state.puedeRegistrar = false;
      state.filas = [];
      state.resumen = { total_tramos: 0, total_usd: "0.00" };
      state.catalogos = { ubicaciones: [], transportistas: [] };
      state.editandoId = null;
      if (dom.opId) dom.opId.value = "";
      if (dom.verCancelados) dom.verCancelados.checked = false;
      texto(dom.numero, "—");
      texto(dom.concepto, "Transporte / USD");
      vaciar(dom.contenedores);
      vaciar(dom.tbody);
      alertaInline(dom.advertencia, "");
      alertaInline(dom.error, "");
      mostrarEstado("cargando");
      actualizarControles();
    }

    function avisoDeContinuidad() {
      const activas = state.filas.filter((f) => Number(f.estatus) === 1).sort((a, b) => Number(a.orden) - Number(b.orden));
      for (let i = 1; i < activas.length; i++) {
        if (Number(activas[i - 1].destino_bodega_id) !== Number(activas[i].origen_bodega_id)) {
          return "Advertencia: los tramos activos presentan una interrupción entre las posiciones históricas " +
            activas[i - 1].orden + " y " + activas[i].orden + ". Revisa origen y destino; no se modificarán automáticamente.";
        }
      }
      return "";
    }

    function cabecera(datos) {
      texto(dom.numero, datos.numero_operacion || "—");
      vaciar(dom.contenedores);
      const contenedores = Array.isArray(datos.contenedores) ? datos.contenedores : [];
      if (!contenedores.length) {
        const sin = document.createElement("span");
        sin.className = "text-muted small";
        texto(sin, "Sin contenedores asociados");
        dom.contenedores.appendChild(sin);
        return;
      }
      contenedores.forEach((c) => {
        const span = document.createElement("span");
        span.className = "mf-tr-contenedor-etiqueta";
        texto(span, c.numero_contenedor || "—");
        dom.contenedores.appendChild(span);
      });
    }

    function nombresUbicacion(item) {
      return String(item.nombre || "") + (item.nombre_ciudad ? " · " + item.nombre_ciudad : "");
    }

    const CAMPOS = {
      transportista: { hidden: ".mf-tr-transportista-id", input: ".mf-tr-transportista-codigo", nombre: ".mf-tr-transportista-nombre", sugerencias: ".mf-tr-sugerencias-transportista", catalogo: "transportistas", key: "id_transportista" },
      origen: { hidden: ".mf-tr-origen-id", input: ".mf-tr-origen-codigo", nombre: ".mf-tr-origen-nombre", sugerencias: ".mf-tr-sugerencias-origen", catalogo: "ubicaciones", key: "id_bodega" },
      destino: { hidden: ".mf-tr-destino-id", input: ".mf-tr-destino-codigo", nombre: ".mf-tr-destino-nombre", sugerencias: ".mf-tr-sugerencias-destino", catalogo: "ubicaciones", key: "id_bodega" },
    };

    function tipoCampo(input) {
      if (input.classList.contains("mf-tr-transportista-codigo")) return "transportista";
      if (input.classList.contains("mf-tr-origen-codigo")) return "origen";
      if (input.classList.contains("mf-tr-destino-codigo")) return "destino";
      return null;
    }

    function asignarCatalogo(fila, tipo, dato) {
      const c = CAMPOS[tipo];
      if (!c) return;
      const hid = fila.querySelector(c.hidden);
      const input = fila.querySelector(c.input);
      const nombre = fila.querySelector(c.nombre);
      const lista = fila.querySelector(c.sugerencias);
      if (!hid || !input || !nombre || !lista) return;
      hid.value = dato ? String(dato[c.key]) : "";
      input.value = dato ? String(dato.codigo || "").trim().toUpperCase() : "";
      texto(nombre, dato ? (tipo === "transportista" ? dato.nombre : nombresUbicacion(dato)) : "Selecciona " + tipo);
      lista.classList.add("d-none");
      vaciar(lista);
      input.classList.remove("is-invalid");
    }

    function ocultarSugerencias(excepto) {
      modal.querySelectorAll(".mf-tr-sugerencias").forEach((el) => {
        if (el !== excepto) el.classList.add("d-none");
      });
    }

    function sugerir(input) {
      const tipo = tipoCampo(input);
      if (!tipo) return;
      const fila = input.closest("tr");
      const c = CAMPOS[tipo];
      const opciones = state.catalogos[c.catalogo] || [];
      const hid = fila.querySelector(c.hidden);
      const nombre = fila.querySelector(c.nombre);
      const panel = fila.querySelector(c.sugerencias);
      const valor = String(input.value || "").toUpperCase().replace(/[^A-Z0-9]/g, "").slice(0, 4);
      input.value = valor;
      const exacta = opciones.find((x) => String(x.codigo || "").trim().toUpperCase() === valor);
      if (exacta && valor.length === 4) {
        asignarCatalogo(fila, tipo, exacta);
        ocultarSugerencias();
        return;
      }
      hid.value = "";
      texto(nombre, "Selecciona " + tipo);
      input.classList.remove("is-invalid");
      const coinciden = opciones.filter((x) => {
        return String(x.codigo || "").toUpperCase().includes(valor) ||
          String(x.nombre || "").toUpperCase().includes(valor);
      }).slice(0, 8);
      vaciar(panel);
      coinciden.forEach((dato) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "list-group-item list-group-item-action py-1 mf-tr-opcion";
        b.dataset.mfTipo = tipo;
        b.dataset.mfId = String(dato[c.key]);
        b.setAttribute("role", "option");
        texto(b, String(dato.codigo).toUpperCase() + " · " + (tipo === "transportista" ? dato.nombre : nombresUbicacion(dato)));
        panel.appendChild(b);
      });
      if (!coinciden.length) {
        const vacio = document.createElement("div");
        vacio.className = "list-group-item small text-muted";
        texto(vacio, "Sin coincidencias");
        panel.appendChild(vacio);
      }
      ocultarSugerencias(panel);
      panel.classList.remove("d-none");
    }

    function pickerFila(fila, tipo, id, codigo, nombre) {
      const c = CAMPOS[tipo];
      const lista = state.catalogos[c.catalogo] || [];
      const opcion = lista.find((x) => Number(x[c.key]) === Number(id));
      if (opcion) {
        asignarCatalogo(fila, tipo, opcion);
      } else {
        fila.querySelector(c.hidden).value = "";
        fila.querySelector(c.input).value = String(codigo || "");
        texto(fila.querySelector(c.nombre), nombre || "Catálogo inactivo o no disponible");
      }
    }

    function nuevaFila() {
      const fila = plantilla.content.firstElementChild.cloneNode(true);
      fila.dataset.estado = "nuevo";
      return fila;
    }

    function actualizarNumerosPendientes() {
      const cantidad = state.filas.filter((x) => Number(x.estatus) === 1).length;
      borradores().forEach((fila, indice) => {
        texto(fila.querySelector(".mf-tr-numero"), "Nuevo " + (cantidad + indice + 1));
      });
    }

    function pintarFilaGuardada(item) {
      const fila = document.createElement("tr");
      const cancelado = Number(item.estatus) !== 1;
      const pagado = item.pagado != null && Number(item.pagado) !== 0;
      const costoInactivo = Number(item.estatus_costo) !== 1;
      fila.dataset.estado = cancelado ? "cancelado" : "guardado";
      fila.dataset.trasladoId = String(item.id_traslado);
      if (cancelado) fila.classList.add("mf-tr-cancelado");

      const n = td(cancelado ? "—" : item.numero_visual || item.orden, "text-center mf-tr-numero");
      n.title = "Orden histórico: " + item.orden;
      fila.appendChild(n);
      [
        [item.codigo_transportista, item.nombre_transportista],
        [item.codigo_origen, item.nombre_origen],
        [item.codigo_destino, item.nombre_destino],
      ].forEach(([codigo, nombre]) => {
        const celda = td();
        const strong = document.createElement("strong");
        texto(strong, codigo || "—");
        celda.appendChild(strong);
        const small = document.createElement("div");
        small.className = "text-muted small";
        texto(small, nombre || "");
        celda.appendChild(small);
        fila.appendChild(celda);
      });
      fila.appendChild(td(formatearUSD(item.monto), "text-end"));
      const estado = td(undefined, "text-center");
      estado.appendChild(cancelado ? etiqueta("Cancelado", "bg-secondary")
        : (costoInactivo ? etiqueta("Costo inactivo", "bg-danger")
        : (pagado ? etiqueta("Pagado", "bg-info text-dark") : etiqueta("Activo", "bg-success"))));
      fila.appendChild(estado);

      const acciones = td(undefined, "text-center mf-tr-acciones");
      if (!cancelado && !pagado && !costoInactivo) {
        acciones.appendChild(boton("Editar", "btn btn-sm btn-outline-primary me-1", "editar", "edit"));
        acciones.appendChild(boton("Baja", "btn btn-sm btn-outline-danger", "cancelar", "trash-2"));
      } else {
        const span = document.createElement("span");
        span.className = "small text-muted";
        texto(span, cancelado ? "Histórico" : pagado ? "Costo pagado" : "Revisión requerida");
        acciones.appendChild(span);
      }
      fila.appendChild(acciones);
      return fila;
    }

    function pintarTabla() {
      vaciar(dom.tbody);
      if (!state.filas.length) {
        const fila = document.createElement("tr");
        const celda = td("No hay tramos registrados para esta operación.", "text-center text-muted py-4");
        celda.colSpan = 7;
        fila.appendChild(celda);
        dom.tbody.appendChild(fila);
      } else {
        state.filas.forEach((item) => dom.tbody.appendChild(pintarFilaGuardada(item)));
      }
      actualizarNumerosPendientes();
      alertaInline(dom.advertencia, avisoDeContinuidad() || (!state.puedeRegistrar
        ? "Falta un concepto único y activo de Transporte en DLLS. Solo puedes consultar los tramos existentes."
        : ""));
      actualizarControles();
      iconos();
    }

    function mostrarError(error) {
      const msg = error && error.message ? error.message : "Ocurrió un error inesperado.";
      alertaInline(dom.error, msg);
      return avisar("error", "Traslados de Mercancía", msg);
    }

    async function inicializarOperacion(operacionId) {
      resetear();
      state.operacionId = operacionId;
      state.ocupado = true;
      if (dom.opId) dom.opId.value = String(operacionId);
      texto(dom.numero, "Operación #" + operacionId);
      actualizarControles();
      const token = state.token;
      try {
        const data = await solicitar("inicializar?operacion_id=" + encodeURIComponent(operacionId));
        if (token !== state.token) return;
        cabecera(data.cabecera || {});
        state.catalogos.ubicaciones = Array.isArray(data.catalogos?.ubicaciones) ? data.catalogos.ubicaciones : [];
        state.catalogos.transportistas = Array.isArray(data.catalogos?.transportistas) ? data.catalogos.transportistas : [];
        state.filas = Array.isArray(data.rows) ? data.rows : [];
        state.resumen = data.resumen || state.resumen;
        state.puedeRegistrar = Boolean(data.puede_registrar);
        state.listo = true;
        const concepto = data.concepto_transporte_usd || null;
        texto(dom.concepto, concepto ? `${concepto.nombre} / USD` : "No configurado");
        mostrarEstado(state.puedeRegistrar ? "listo" : "consulta");
        pintarTabla();
        if (data.advertencia) alertaInline(dom.advertencia, data.advertencia);
      } catch (error) {
        if (token !== state.token) return;
        mostrarEstado("consulta");
        mostrarError(error);
      } finally {
        if (token === state.token) establecerOcupado(false);
      }
    }

    async function recargarTabla() {
      if (!state.operacionId) return;
      const token = state.token;
      establecerOcupado(true);
      try {
        const q = "listar?operacion_id=" + encodeURIComponent(state.operacionId) +
          "&incluir_cancelados=" + (dom.verCancelados.checked ? "1" : "0");
        const data = await solicitar(q);
        if (token !== state.token) return;
        state.filas = Array.isArray(data.rows) ? data.rows : [];
        state.resumen = data.resumen || state.resumen;
        state.listo = true;
        mostrarEstado(state.puedeRegistrar ? "listo" : "consulta");
        pintarTabla();
        alertaInline(dom.error, "");
      } catch (error) {
        if (token === state.token) mostrarError(error);
      } finally {
        if (token === state.token) establecerOcupado(false);
      }
    }

    function agregarFila() {
      if (!state.listo || !state.puedeRegistrar || state.ocupado || state.editandoId !== null) return;
      if (borradores().length >= MAX_LOTE) {
        avisar("warning", "Límite de filas", "Solo puedes guardar 100 tramos por solicitud.");
        return;
      }
      if (!borradores().length && !state.filas.length) vaciar(dom.tbody);
      const fila = nuevaFila();
      dom.tbody.appendChild(fila);
      actualizarNumerosPendientes();
      actualizarControles();
      iconos();
      fila.querySelector(".mf-tr-transportista-codigo")?.focus();
    }

    function leerFila(fila) {
      const ids = {};
      for (const tipo of ["transportista", "origen", "destino"]) {
        const c = CAMPOS[tipo];
        const hid = fila.querySelector(c.hidden);
        const code = fila.querySelector(c.input);
        const id = Number(hid?.value || 0);
        if (!Number.isSafeInteger(id) || id <= 0 || !code || code.value.trim().length !== 4) {
          code?.classList.add("is-invalid");
          throw new Error("Selecciona un código válido de cuatro caracteres para " + tipo + ".");
        }
        const lista = state.catalogos[c.catalogo] || [];
        if (!lista.some((x) => Number(x[c.key]) === id &&
          String(x.codigo).toUpperCase() === code.value.trim().toUpperCase())) {
          code.classList.add("is-invalid");
          throw new Error("El código de " + tipo + " no coincide con el catálogo activo.");
        }
        ids[tipo] = id;
      }
      const inpMonto = fila.querySelector(".mf-tr-monto");
      let monto;
      try {
        monto = normalizarMonto(inpMonto?.value || "");
        inpMonto.classList.remove("is-invalid");
      } catch (e) {
        inpMonto?.classList.add("is-invalid");
        throw e;
      }
      if (ids.origen === ids.destino) {
        throw new Error("El origen y el destino de un tramo deben ser diferentes.");
      }
      return {
        transportista_id: ids.transportista,
        origen_bodega_id: ids.origen,
        destino_bodega_id: ids.destino,
        monto,
      };
    }

    function validarContinuidadNuevos(renglones) {
      const activas = state.filas.filter((f) => Number(f.estatus) === 1).sort((a, b) => Number(a.orden) - Number(b.orden));
      let destinoAnterior = activas.length ? Number(activas[activas.length - 1].destino_bodega_id) : null;
      renglones.forEach((r, idx) => {
        if (destinoAnterior !== null && Number(r.origen_bodega_id) !== destinoAnterior) {
          throw new Error("El origen del nuevo tramo " + (idx + 1) +
            " debe coincidir con el destino del último tramo activo.");
        }
        destinoAnterior = Number(r.destino_bodega_id);
      });
    }

    async function guardarNuevos() {
      if (state.ocupado || !state.puedeRegistrar || state.editandoId !== null) return;
      const pendientes = borradores();
      if (!pendientes.length) return;
      let renglones;
      try {
        renglones = pendientes.map(leerFila);
        validarContinuidadNuevos(renglones);
      } catch (error) {
        await avisar("warning", "Revisa los renglones", error.message);
        return;
      }
      const token = state.token;
      establecerOcupado(true);
      try {
        const respuesta = await post("guardarLote", {
          operacion_id: String(state.operacionId),
          renglones: JSON.stringify(renglones),
        });
        if (token !== state.token) return;
        // La transacción YA quedó confirmada: descartar drafts antes de
        // intentar la segunda petición para evitar un segundo guardado.
        borradores().forEach((fila) => fila.remove());
        avisarOtrosModulos("creacion");
        const actualizado = await refrescarTrasConfirmacion();
        await avisar(actualizado ? "success" : "warning", "Traslados guardados",
          actualizado ? (respuesta.message || "Se registraron todos los tramos y sus costos.")
            : "Los tramos fueron registrados. Pulsa Actualizar para cargar la tabla.");
      } catch (error) {
        if (token === state.token) mostrarError(error);
      } finally {
        if (token === state.token) establecerOcupado(false);
      }
    }

    // Se usa después de guardar: vuelve a consultar datos persistidos y
    // descarta drafts solamente si el backend confirmó la transacción.
    async function recargarTablaDespuesDeEscritura() {
      const q = "listar?operacion_id=" + encodeURIComponent(state.operacionId) +
        "&incluir_cancelados=" + (dom.verCancelados.checked ? "1" : "0");
      const data = await solicitar(q);
      state.filas = Array.isArray(data.rows) ? data.rows : [];
      state.resumen = data.resumen || state.resumen;
      state.editandoId = null;
      state.listo = true;
      mostrarEstado(state.puedeRegistrar ? "listo" : "consulta");
      pintarTabla();
      alertaInline(dom.error, "");
    }

    // El backend puede confirmar una escritura y luego fallar la recarga AJAX.
    // NO debemos informar que falló el guardado ni permitir reenviar el lote.
    async function refrescarTrasConfirmacion() {
      try {
        await recargarTablaDespuesDeEscritura();
        return true;
      } catch (error) {
        state.listo = false;
        state.editandoId = null;
        state.filas = [];
        state.resumen = { total_tramos: 0, total_usd: "0.00" };
        mostrarEstado("consulta");
        pintarTabla();
        texto(dom.tbody.querySelector("td"), "Cambio guardado en el servidor. Pulsa Actualizar para consultar los datos.");
        alertaInline(dom.error, "El cambio fue guardado, pero falló la actualización de la tabla: " + error.message);
        actualizarControles();
        return false;
      }
    }

    function avisarOtrosModulos(accion, id) {
      const detalle = { operacion_id: state.operacionId, accion };
      if (id) detalle.id_traslado = id;
      document.dispatchEvent(new CustomEvent("mf:traslados-updated", { detail: detalle }));
      document.dispatchEvent(new CustomEvent("mf:refresh-list", { detail: detalle }));
    }

    function empezarEdicion(guardada) {
      if (state.ocupado || hayCambiosSinGuardar()) {
        avisar("warning", "Cambios pendientes", "Primero guarda o elimina las filas nuevas, o finaliza la edición actual.");
        return;
      }
      const id = Number(guardada.dataset.trasladoId);
      const dato = state.filas.find((x) => Number(x.id_traslado) === id);
      if (!dato || Number(dato.estatus) !== 1 || Number(dato.pagado) !== 0 || Number(dato.estatus_costo) !== 1) {
        avisar("warning", "Edición no disponible", "El traslado debe estar activo y su costo no debe estar pagado.");
        return;
      }
      const edicion = nuevaFila();
      edicion.dataset.estado = "edicion";
      edicion.dataset.trasladoId = String(id);
      edicion.classList.remove("mf-tr-fila-nueva");
      texto(edicion.querySelector(".mf-tr-numero"), dato.numero_visual || dato.orden);
      pickerFila(edicion, "transportista", dato.transportista_id, dato.codigo_transportista, dato.nombre_transportista);
      pickerFila(edicion, "origen", dato.origen_bodega_id, dato.codigo_origen, dato.nombre_origen);
      pickerFila(edicion, "destino", dato.destino_bodega_id, dato.codigo_destino, dato.nombre_destino);
      edicion.querySelector(".mf-tr-monto").value = String(dato.monto);
      texto(edicion.querySelector(".mf-tr-etiqueta-estado"), "Editando");
      const celdaAcciones = edicion.querySelector(".mf-tr-acciones");
      vaciar(celdaAcciones);
      celdaAcciones.appendChild(boton("Guardar", "btn btn-sm btn-success me-1", "guardar-edicion", "save"));
      celdaAcciones.appendChild(boton("Volver", "btn btn-sm btn-outline-secondary", "descartar-edicion", "x"));
      guardada.replaceWith(edicion);
      state.editandoId = id;
      actualizarControles();
      iconos();
    }

    function descartarEdicion() {
      if (state.editandoId === null || state.ocupado) return;
      const actual = dom.tbody.querySelector("tr[data-estado='edicion']");
      const dato = state.filas.find((x) => Number(x.id_traslado) === Number(state.editandoId));
      if (actual && dato) actual.replaceWith(pintarFilaGuardada(dato));
      state.editandoId = null;
      actualizarControles();
      iconos();
    }

    async function guardarEdicion(fila) {
      if (state.ocupado) return;
      let payload;
      try {
        payload = leerFila(fila);
      } catch (error) {
        await avisar("warning", "Revisa la edición", error.message);
        return;
      }
      const token = state.token;
      const id = Number(fila.dataset.trasladoId);
      establecerOcupado(true);
      try {
        const respuesta = await post("guardar", {
          operacion_id: String(state.operacionId),
          id_traslado: String(id),
          ...payload,
        });
        if (token !== state.token) return;
        avisarOtrosModulos("actualizacion", id);
        const actualizado = await refrescarTrasConfirmacion();
        await avisar(actualizado ? "success" : "warning", "Traslado actualizado",
          actualizado ? (respuesta.message || "Cambios guardados.")
            : "El cambio fue guardado. Pulsa Actualizar para consultar el resultado.");
      } catch (error) {
        if (token === state.token) mostrarError(error);
      } finally {
        if (token === state.token) establecerOcupado(false);
      }
    }

    async function cancelarTramo(fila) {
      if (state.ocupado || hayCambiosSinGuardar()) {
        avisar("warning", "Cambios pendientes", "Primero termina la edición o guarda las filas nuevas.");
        return;
      }
      const id = Number(fila.dataset.trasladoId);
      const dato = state.filas.find((x) => Number(x.id_traslado) === id);
      if (!dato || Number(dato.estatus) !== 1 || Number(dato.pagado) !== 0) {
        avisar("warning", "Baja no disponible", "Este traslado no está activo o su costo ya fue pagado.");
        return;
      }
      const tokenAntes = state.token;
      const aceptado = await confirmar(
        "¿Dar de baja el tramo?",
        "Se conservará su ID y orden histórico (" + dato.orden + "). También se cancelará su costo no pagado.",
        "Sí, dar de baja"
      );
      if (!aceptado || state.ocupado || tokenAntes !== state.token) return;
      const token = state.token;
      establecerOcupado(true);
      try {
        const respuesta = await post("cancelar", {
          operacion_id: String(state.operacionId),
          id_traslado: String(id),
          motivo: "Cancelación desde Traslados de Mercancía",
        });
        if (token !== state.token) return;
        avisarOtrosModulos("cancelacion", id);
        const actualizado = await refrescarTrasConfirmacion();
        if (!actualizado) {
          await avisar("warning", "Baja aplicada", "El tramo y su costo quedaron cancelados, pero es necesario pulsar Actualizar para consultar el resultado.");
        } else if (respuesta.advertencia_continuidad) {
          await avisar("warning", "Tramo cancelado", "La baja se registró correctamente, pero el recorrido activo quedó desconectado. Corrige origen/destino según corresponda.");
        } else {
          await avisar("success", "Baja lógica aplicada", respuesta.message || "Tramo y costo desactivados.");
        }
      } catch (error) {
        if (token === state.token) mostrarError(error);
      } finally {
        if (token === state.token) establecerOcupado(false);
      }
    }

    // Integracion no intrusiva: la tabla MF se redibuja por AJAX.
    // El observer inserta un unico boton por operacion, sin editar el JS grande.
    function insertarBotonesEnOperaciones() {
      let agregados = 0;
      tablaOperaciones.querySelectorAll("tr").forEach((fila) => {
        if (fila.querySelector(".btn-traslados-mf")) return;
        const editar = fila.querySelector(".btn-edit-mf[data-id]");
        if (!editar) return;
        const id = Number(editar.getAttribute("data-id"));
        if (!Number.isSafeInteger(id) || id <= 0) return;
        const contenedor = editar.parentElement;
        if (!contenedor) return;
        contenedor.classList.remove("flex-wrap");
        contenedor.classList.add("flex-nowrap", "align-items-center", "gap-1", "mf-tr-acciones-listado");
        const b = boton("Traslados", "btn btn-sm btn-traslados-mf", null, "truck");
        b.dataset.id = String(id);
        b.title = "Gestionar traslados de mercancía de esta operación";
        contenedor.appendChild(b);
        agregados++;
      });
      if (agregados) iconos();
    }

    const observer = new MutationObserver(insertarBotonesEnOperaciones);
    observer.observe(tablaOperaciones, { childList: true, subtree: true });
    insertarBotonesEnOperaciones();

    // Delegación: reconoce botones de filas creadas después de buscar/paginar.
    tablaOperaciones.addEventListener("click", (ev) => {
      const b = ev.target.closest(".btn-traslados-mf");
      if (!b) return;
      ev.preventDefault();
      if (!window.bootstrap || !window.bootstrap.Modal) {
        avisar("error", "Bootstrap no disponible", "No se pudo abrir el modal de traslados.");
        return;
      }
      window.bootstrap.Modal.getOrCreateInstance(modal).show(b);
    });

    modal.addEventListener("show.bs.modal", (ev) => {
      const id = Number(ev.relatedTarget?.getAttribute("data-id") || 0);
      if (!Number.isSafeInteger(id) || id <= 0) {
        ev.preventDefault();
        avisar("warning", "Selecciona una operación", "No se recibió un ID de operación válido.");
        return;
      }
      inicializarOperacion(id);
    });

    // Salvaguarda: no descartar ediciones y filas pendientes por accidente.
    modal.addEventListener("hide.bs.modal", (ev) => {
      if (state.permitirCierre) return;
      if (state.ocupado) {
        ev.preventDefault();
        return;
      }
      if (hayCambiosSinGuardar()) {
        ev.preventDefault();
        if (state.confirmandoCierre) return;
        state.confirmandoCierre = true;
        confirmar("Cambios sin guardar", "Los tramos pendientes o la edición en curso se perderán.", "Descartar cambios")
          .then((si) => {
            if (si) {
              state.permitirCierre = true;
              window.bootstrap.Modal.getOrCreateInstance(modal).hide();
            }
          })
          .finally(() => { state.confirmandoCierre = false; });
      }
    });
    modal.addEventListener("hidden.bs.modal", () => {
      resetear();
      state.permitirCierre = false;
      state.confirmandoCierre = false;
    });

    const formulario = document.getElementById("mfTrForm");
    formulario?.addEventListener("submit", (ev) => {
      ev.preventDefault();
      if (state.ocupado) return;
      if (state.editandoId !== null) {
        const fila = dom.tbody.querySelector("tr[data-estado='edicion']");
        if (fila) guardarEdicion(fila);
      } else if (borradores().length) {
        guardarNuevos();
      }
    });

    dom.btnAgregar.addEventListener("click", agregarFila);
    dom.btnGuardar.addEventListener("click", guardarNuevos);
    dom.btnRecargar.addEventListener("click", () => {
      if (hayCambiosSinGuardar()) {
        avisar("warning", "Cambios pendientes", "Guarda o descarta los cambios antes de actualizar.");
        return;
      }
      recargarTabla();
    });
    dom.verCancelados.addEventListener("change", () => {
      if (hayCambiosSinGuardar()) {
        dom.verCancelados.checked = !dom.verCancelados.checked;
        avisar("warning", "Cambios pendientes", "Termina primero los cambios para consultar los cancelados.");
        return;
      }
      recargarTabla();
    });

    dom.tbody.addEventListener("click", (ev) => {
      const opcion = ev.target.closest(".mf-tr-opcion");
      if (opcion) {
        ev.preventDefault();
        const fila = opcion.closest("tr");
        const tipo = opcion.dataset.mfTipo;
        const c = CAMPOS[tipo];
        const candidato = (state.catalogos[c.catalogo] || []).find((x) => String(x[c.key]) === opcion.dataset.mfId);
        if (fila && candidato) asignarCatalogo(fila, tipo, candidato);
        return;
      }
      const btnQuitar = ev.target.closest(".mf-tr-btn-quitar");
      if (btnQuitar) {
        ev.preventDefault();
        const fila = btnQuitar.closest("tr[data-estado='nuevo']");
        if (fila) fila.remove();
        if (!state.filas.length && borradores().length === 0) pintarTabla();
        actualizarNumerosPendientes();
        actualizarControles();
        return;
      }
      const btn = ev.target.closest("button[data-mf-accion]");
      if (!btn || state.ocupado) return;
      const fila = btn.closest("tr");
      switch (btn.dataset.mfAccion) {
        case "editar": empezarEdicion(fila); break;
        case "guardar-edicion": guardarEdicion(fila); break;
        case "descartar-edicion": descartarEdicion(); break;
        case "cancelar": cancelarTramo(fila); break;
        default: break;
      }
    });

    dom.tbody.addEventListener("input", (ev) => {
      const input = ev.target;
      if (tipoCampo(input)) sugerir(input);
      if (input.classList.contains("mf-tr-monto")) input.classList.remove("is-invalid");
    });
    dom.tbody.addEventListener("focusin", (ev) => {
      const input = ev.target;
      if (tipoCampo(input)) sugerir(input);
    });
    dom.tbody.addEventListener("focusout", (ev) => {
      const input = ev.target;
      if (!tipoCampo(input)) return;
      window.setTimeout(() => {
        if (!document.contains(input)) return;
        const fila = input.closest("tr");
        const tipo = tipoCampo(input);
        if (tipo && fila) fila.querySelector(CAMPOS[tipo].sugerencias)?.classList.add("d-none");
      }, 130);
    });
    dom.tbody.addEventListener("keydown", (ev) => {
      if (!tipoCampo(ev.target)) return;
      const tipo = tipoCampo(ev.target);
      const fila = ev.target.closest("tr");
      const lista = fila.querySelector(CAMPOS[tipo].sugerencias);
      if (!lista || lista.classList.contains("d-none")) return;
      const opciones = Array.from(lista.querySelectorAll(".mf-tr-opcion"));
      if (!opciones.length) return;
      const actual = opciones.findIndex((el) => el.classList.contains("active"));
      if (ev.key === "ArrowDown" || ev.key === "ArrowUp") {
        ev.preventDefault();
        const delta = ev.key === "ArrowDown" ? 1 : -1;
        const nuevo = (actual + delta + opciones.length) % opciones.length;
        opciones.forEach((el) => el.classList.remove("active"));
        opciones[nuevo].classList.add("active");
      } else if (ev.key === "Enter") {
        ev.preventDefault();
        const elegido = opciones[actual < 0 ? 0 : actual];
        elegido.click();
      } else if (ev.key === "Escape") {
        lista.classList.add("d-none");
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", iniciarTrasladosMF, { once: true });
  } else {
    iniciarTrasladosMF();
  }
})();
