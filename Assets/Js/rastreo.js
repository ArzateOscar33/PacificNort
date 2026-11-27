// ==========================================
// PACIFICNORT - RASTREO DE CARGA
// FASE 5
// ==========================================

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("pnTrackingForm");

  if (!form) return;

  const input = document.getElementById("inputNumeroGuia");
  const btnBuscar = document.getElementById("btnRastrearEnvio");
  const btnLimpiar = document.getElementById("btnLimpiarRastreo");

  const buttonText = document.getElementById("pnTrackingButtonText");
  const spinner = document.getElementById("pnTrackingSpinner");

  const resultados = document.getElementById("bloqueResultadoMaritimo");
  const cards = document.getElementById("pnTrackingCards");
  const lblOperacion = document.getElementById("lblOperacionMaritima");
  const lblCantidad = document.getElementById("pnTrackingCount");

  const mensaje = document.getElementById("pnTrackingMessage");
  const mensajeIcono = document.getElementById("pnTrackingMessageIcon");
  const mensajeTitulo = document.getElementById("pnTrackingMessageTitle");
  const mensajeTexto = document.getElementById("pnTrackingMessageText");
  const mensajeLogin = document.getElementById("pnTrackingLogin");

  let consultando = false;

  // ======================================
  // SEGURIDAD
  // ======================================

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // ======================================
  // ESTADO DE CARGA
  // ======================================

  function setCargando(activo) {
    consultando = activo;

    btnBuscar.disabled = activo;
    btnLimpiar.disabled = activo;
    input.disabled = activo;

    spinner.classList.toggle("d-none", !activo);

    buttonText.textContent = activo ? "Consultando..." : "Rastrear";

    form.setAttribute("aria-busy", String(activo));
  }

  // ======================================
  // MENSAJES
  // ======================================

  function ocultarMensaje() {
    mensaje.classList.add("d-none");
    mensajeLogin.classList.add("d-none");
  }

  function mostrarMensaje(titulo, texto, tipo = "info", mostrarLogin = false) {
    mensaje.classList.remove("d-none", "is-error");

    mensajeTitulo.textContent = titulo;
    mensajeTexto.textContent = texto;

    mensajeLogin.classList.toggle("d-none", !mostrarLogin);

    if (tipo === "error") {
      mensaje.classList.add("is-error");

      mensajeIcono.className = "bi bi-exclamation-triangle";
    } else {
      mensajeIcono.className = "bi bi-info-circle";
    }
  }

  // ======================================
  // RESULTADOS
  // ======================================

  function ocultarResultados() {
    resultados.classList.add("d-none");

    cards.innerHTML = "";

    lblOperacion.textContent = "";
    lblCantidad.textContent = "";
  }

  // ======================================
  // COLORES DE ESTATUS
  // ======================================

  function obtenerClaseEstatus(estatus) {
    const valor = String(estatus ?? "")
      .trim()
      .toLowerCase();

    const finalizados = [
      "finalizada",
      "finalizado",
      "entregada",
      "entregado",
      "completada",
      "completado",
    ];

    const cancelados = ["cancelada", "cancelado"];

    const pendientes = ["pendiente", "en espera", "detenida", "detenido"];

    if (finalizados.includes(valor)) {
      return "is-success";
    }

    if (cancelados.includes(valor)) {
      return "is-danger";
    }

    if (pendientes.includes(valor)) {
      return "is-warning";
    }

    return "";
  }

  // ======================================
  // FORMATO DE FECHAS
  // ======================================

  function formatearFecha(valor) {
    if (!valor) {
      return "Sin fecha registrada";
    }

    // Conservamos la fecha y hora devueltas
    // por MySQL, sin modificar su zona horaria.

    const fecha = String(valor).match(
      /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/,
    );

    if (!fecha) {
      return String(valor);
    }

    const dia = fecha[3];
    const mes = fecha[2];
    const anio = fecha[1];

    const hora = fecha[4] && fecha[5] ? ` · ${fecha[4]}:${fecha[5]}` : "";

    return `${dia}/${mes}/${anio}${hora}`;
  }

  // ======================================
  // GENERAR TARJETAS
  // ======================================

  function renderizarResultados(rows, termino) {
    if (!Array.isArray(rows) || rows.length === 0) {
      mostrarMensaje(
        "Sin resultados",
        "No encontramos información para la operación indicada.",
      );

      return;
    }

    lblOperacion.textContent = termino;

    lblCantidad.textContent =
      rows.length === 1
        ? "1 registro encontrado"
        : `${rows.length} registros encontrados`;

    cards.innerHTML = rows
      .map(function (row) {
        const operacion = escapeHtml(
          row.numero_operacion || "Sin número registrado",
        );

        const contenedor = escapeHtml(
          row.contenedor || "Sin contenedor registrado",
        );

        const estatus = escapeHtml(
          row.estatus_nombre || "Sin estatus registrado",
        );

        const claseEstatus = obtenerClaseEstatus(row.estatus_nombre);

        const comentario = escapeHtml(
          row.comentario || "Sin comentarios disponibles.",
        );

        const actualizacion = escapeHtml(formatearFecha(row.actualizacion));

        return `

                <div class="col-12 col-lg-6">

                    <article class="pn-tracking-card">

                        <div class="pn-tracking-card-header">

                            <div class="pn-tracking-container-icon">

                                <i class="bi bi-box-seam"
                                   aria-hidden="true"></i>

                            </div>

                            <div class="pn-tracking-container-name">

                                <small>Contenedor</small>

                                <h4>${contenedor}</h4>

                            </div>

                            <span class="pn-tracking-status ${claseEstatus}">

                                <i class="bi bi-circle-fill"
                                   aria-hidden="true"></i>

                                ${estatus}

                            </span>

                        </div>

                        <div class="pn-tracking-card-details">

                            <span class="pn-tracking-detail-label">
                                Número de operación
                            </span>

                            <strong class="pn-tracking-operation">

                                ${operacion}

                            </strong>

                            <span class="pn-tracking-detail-label">
                                Último comentario disponible
                            </span>

                            <p class="pn-tracking-comment">

                                ${comentario}

                            </p>

                        </div>

                        <div class="pn-tracking-card-footer">

                            <i class="bi bi-clock-history"
                               aria-hidden="true"></i>

                            Último evento: ${actualizacion}

                        </div>

                    </article>

                </div>

            `;
      })
      .join("");

    resultados.classList.remove("d-none");
  }

  // ======================================
  // CONSULTA AL SERVIDOR
  // ======================================

  async function buscarOperacion() {
    if (consultando) return;

    const termino = input.value.trim().toUpperCase().replace(/\s+/g, "");

    ocultarMensaje();
    ocultarResultados();

    if (!termino) {
      mostrarMensaje(
        "Ingresa una operación",
        "Escribe un número de operación o BL para continuar.",
      );

      input.focus();

      return;
    }

    input.value = termino;

    btnLimpiar.classList.remove("d-none");

    setCargando(true);

    const data = new FormData();

    data.append("numero_operacion", termino);

    const abortController = new AbortController();

    const timeout = setTimeout(function () {
      abortController.abort();
    }, 20000);

    try {
      const response = await fetch(form.dataset.url, {
        method: "POST",

        body: data,

        credentials: "same-origin",

        headers: {
          Accept: "application/json",
        },

        signal: abortController.signal,
      });

      // Detectar restricciones de acceso.

      if (
        response.redirected ||
        response.status === 401 ||
        response.status === 403
      ) {
        mostrarMensaje(
          "Acceso restringido",
          "Debes iniciar sesión con una cuenta autorizada para realizar esta consulta.",
          "info",
          true,
        );

        return;
      }

      if (!response.ok) {
        throw new Error(`Error HTTP ${response.status}`);
      }

      const contentType = response.headers.get("content-type") || "";

      if (!contentType.includes("application/json")) {
        throw new Error("Respuesta no válida del servidor");
      }

      const res = await response.json();

      if (!res.ok) {
        mostrarMensaje(
          "No encontramos tu operación",
          res.msg || "Verifica el número ingresado e inténtalo nuevamente.",
        );

        return;
      }

      const tipo = String(res.tipo || "").toLowerCase();

      if (tipo !== "maritimo") {
        mostrarMensaje(
          "Consulta no disponible",
          "Esta modalidad de rastreo todavía no está habilitada.",
        );

        return;
      }

      renderizarResultados(
        res.data,
        res.encabezado?.numero_operacion || termino,
      );
    } catch (error) {
      console.error("Error de rastreo:", error);

      mostrarMensaje(
        "No fue posible realizar la consulta",
        error.name === "AbortError"
          ? "El servidor tardó demasiado en responder. Intenta nuevamente."
          : "Ocurrió un problema de comunicación. Intenta nuevamente.",
        "error",
      );
    } finally {
      clearTimeout(timeout);

      setCargando(false);
    }
  }

  // ======================================
  // EVENTOS
  // ======================================

  form.addEventListener("submit", function (event) {
    event.preventDefault();

    buscarOperacion();
  });

  btnLimpiar.addEventListener("click", function () {
    if (consultando) return;

    input.value = "";

    ocultarMensaje();
    ocultarResultados();

    btnLimpiar.classList.add("d-none");

    input.focus();
  });

  input.addEventListener("input", function () {
    ocultarMensaje();
    ocultarResultados();

    btnLimpiar.classList.toggle("d-none", !input.value.trim());
  });

  // Estado inicial

  ocultarMensaje();
  ocultarResultados();
});
