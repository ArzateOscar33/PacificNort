// assets/js/rastreo.js

document.addEventListener("DOMContentLoaded", function () {
  const inputNumeroGuia       = document.getElementById("inputNumeroGuia");
  const btnRastrearEnvio      = document.getElementById("btnRastrearEnvio");

  // ====== BLOQUE FO ======
  const lblOperacionSeleccion = document.getElementById("lblOperacionSeleccionada");
  const tbodyRutasOperacion   = document.getElementById("tbodyRutasOperacion");
  const tablaRutas            = document.getElementById("tablaRutasOperacion");

  // Contenedor de la tabla FO (el div.table-responsive)
  const contenedorTablaFO = tablaRutas ? tablaRutas.closest(".table-responsive") : null;
  // Contenedor del encabezado FO: el <div class="d-flex ...">
  const headerRutasFO = lblOperacionSeleccion
    ? lblOperacionSeleccion.closest(".d-flex")
    : null;

  // Contenedor general del bloque FO (si existe en tu vista nueva)
  const bloqueResultadoFO = document.getElementById("bloqueResultadoFO");

  // ====== BLOQUE MARÍTIMO ======
  const bloqueResultadoMaritimo = document.getElementById("bloqueResultadoMaritimo");
  const lblOperacionMaritima    = document.getElementById("lblOperacionMaritima");
  const tbodyOperacionMaritima  = document.getElementById("tbodyOperacionMaritima");
  const tablaOperacionMaritima  = document.getElementById("tablaOperacionMaritima");
  const contenedorTablaMar = tablaOperacionMaritima
    ? tablaOperacionMaritima.closest(".table-responsive")
    : null;

  // Crear botón "Limpiar" dinámicamente y agregarlo al input-group
  let btnLimpiar = document.getElementById("btnLimpiarRastreo");
  if (!btnLimpiar) {
    btnLimpiar = document.createElement("button");
    btnLimpiar.type = "button";
    btnLimpiar.id = "btnLimpiarRastreo";
    btnLimpiar.className = "btn btn-outline-secondary d-none ms-2";
    btnLimpiar.textContent = "Limpiar";

    const inputGroup = btnRastrearEnvio.closest(".input-group");
    if (inputGroup) {
      inputGroup.appendChild(btnLimpiar);
    }
  }

  // =========================
  // Estado inicial
  // =========================
  inicializarVista();

  function inicializarVista() {
    // Reset FO
    if (lblOperacionSeleccion) {
      lblOperacionSeleccion.innerHTML =
        'Operación: <span class="fw-semibold">—</span> • Contenedor/Caja: ' +
        '<span class="fw-semibold">—</span>';
    }
    renderTablaSinDatosFO();

    // Reset Marítimo
    if (lblOperacionMaritima) {
      lblOperacionMaritima.innerHTML =
        'Operación: <span class="fw-semibold">—</span>';
    }
    renderTablaSinDatosMaritimo();

    // Ocultar ambos bloques hasta búsqueda
    ocultarBloqueFO();
    ocultarBloqueMaritimo();

    // Ocultar botón limpiar
    btnLimpiar.classList.add("d-none");
  }

  // =========================
  // FO helpers
  // =========================
  function renderTablaSinDatosFO() {
    if (!tbodyRutasOperacion) return;
    tbodyRutasOperacion.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted">
          Sin datos para mostrar.
        </td>
      </tr>
    `;
  }

  function renderTablaTramos(tramos) {
    if (!tbodyRutasOperacion) return;

    if (!Array.isArray(tramos) || tramos.length === 0) {
      renderTablaSinDatosFO();
      return;
    }

    let html = "";
    tramos.forEach(function (row, idx) {
      const num           = idx + 1;
      const origen        = row.origen_nombre || "";
      const destino       = row.destino_nombre || "";
      const transportista = row.transportista_nombre || "";
      const fechaHora     = row.fecha_hora || "";
      const comentario    = row.comentario || "";

      html += `
        <tr>
          <td>${num}</td>
          <td>${origen}</td>
          <td>${destino}</td>
          <td>${transportista}</td>
          <td>${fechaHora}</td>
          <td>${comentario}</td>
        </tr>
      `;
    });

    tbodyRutasOperacion.innerHTML = html;
  }

  function actualizarEncabezadoFO(encabezado) {
    if (!lblOperacionSeleccion) return;

    const numeroOperacion =
      (encabezado && encabezado.numero_operacion) ? encabezado.numero_operacion : "—";
    const contenedor =
      (encabezado && encabezado.contenedor) ? encabezado.contenedor : "—";

    lblOperacionSeleccion.innerHTML =
      'Operación: <span class="fw-semibold">' + numeroOperacion +
      '</span> • Contenedor/Caja: <span class="fw-semibold">' + contenedor + '</span>';
  }

  function mostrarBloqueFO() {
    // Si tienes bloque contenedor, úsalo
    if (bloqueResultadoFO) bloqueResultadoFO.classList.remove("d-none");

    // Compatibilidad con tu lógica anterior (encabezado + table-responsive)
    if (contenedorTablaFO) contenedorTablaFO.classList.remove("d-none");
    if (headerRutasFO) headerRutasFO.classList.remove("d-none");
  }

  function ocultarBloqueFO() {
    if (bloqueResultadoFO) bloqueResultadoFO.classList.add("d-none");

    if (contenedorTablaFO) contenedorTablaFO.classList.add("d-none");
    if (headerRutasFO) headerRutasFO.classList.add("d-none");
  }

  // =========================
  // Marítimo helpers
  // =========================
  function renderTablaSinDatosMaritimo() {
    if (!tbodyOperacionMaritima) return;
    tbodyOperacionMaritima.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted">
          Sin datos para mostrar.
        </td>
      </tr>
    `;
  }

  function actualizarEncabezadoMaritimo(encabezado) {
    if (!lblOperacionMaritima) return;
    const numeroOperacion =
      (encabezado && encabezado.numero_operacion) ? encabezado.numero_operacion : "—";

    lblOperacionMaritima.innerHTML =
      'Operación: <span class="fw-semibold">' + numeroOperacion + '</span>';
  }

  function escapeHtml(value) {
    const s = (value === null || value === undefined) ? "" : String(value);
    return s
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function renderTablaMaritimo(rows) {
    if (!tbodyOperacionMaritima) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      renderTablaSinDatosMaritimo();
      return;
    }

    let html = "";
    rows.forEach(function (r) {
      const op    = escapeHtml(r.numero_operacion || "");
      const cont  = escapeHtml(r.contenedor || "—");
      const est   = escapeHtml(r.estatus_nombre || "");
      const comen = escapeHtml(r.comentario || "");

      html += `
        <tr>
          <td>${op}</td>
          <td>${cont}</td>
          <td>${est}</td>
          <td>${comen}</td>
        </tr>
      `;
    });

    tbodyOperacionMaritima.innerHTML = html;
  }

  function mostrarBloqueMaritimo() {
    if (bloqueResultadoMaritimo) bloqueResultadoMaritimo.classList.remove("d-none");
    if (contenedorTablaMar) contenedorTablaMar.classList.remove("d-none");
  }

  function ocultarBloqueMaritimo() {
    if (bloqueResultadoMaritimo) bloqueResultadoMaritimo.classList.add("d-none");
    if (contenedorTablaMar) contenedorTablaMar.classList.add("d-none");
  }

  // =========================
  // Buscar
  // =========================
  function buscarOperacion() {
    const numeroOperacion = inputNumeroGuia.value.trim();

    if (numeroOperacion === "") {
      Swal.fire("Aviso", "Debes ingresar un número de operación.", "warning");
      inputNumeroGuia.disabled = false;
      return;
    }

    const url  = base_url + "Rastreo/buscarOperacion";
    const data = new FormData();
    data.append("numero_operacion", numeroOperacion);

    const http = new XMLHttpRequest();
    http.open("POST", url, true);
    http.send(data);

    http.onreadystatechange = function () {
      if (this.readyState === 4) {

        if (this.status === 200) {
          let res;
          try {
            res = JSON.parse(this.responseText);
          } catch (e) {
            console.error("Error al parsear JSON:", e, this.responseText);
            Swal.fire("Error", "Respuesta inválida del servidor.", "error");

            // Reset visual
            ocultarBloqueFO();
            ocultarBloqueMaritimo();
            actualizarEncabezadoFO(null);
            renderTablaSinDatosFO();
            actualizarEncabezadoMaritimo(null);
            renderTablaSinDatosMaritimo();
            return;
          }

          // Antes de pintar, ocultamos ambos bloques y luego mostramos el correcto
          ocultarBloqueFO();
          ocultarBloqueMaritimo();

          if (!res.ok) {
            Swal.fire(
              "Sin resultados",
              (res.msg || "No se encontraron resultados.").toUpperCase(),
              "info"
            );

            // Según el tipo, dejamos visible el bloque correspondiente con "sin datos"
            const tipo = (res.tipo || "").toLowerCase();

            if (tipo === "maritimo") {
              mostrarBloqueMaritimo();
              actualizarEncabezadoMaritimo({ numero_operacion: res.numero_operacion || numeroOperacion });
              renderTablaSinDatosMaritimo();
            } else {
              // default FO
              mostrarBloqueFO();
              actualizarEncabezadoFO({
                numero_operacion: res.numero_operacion || numeroOperacion,
                contenedor: "—"
              });
              renderTablaSinDatosFO();
            }

          } else {
            const tipo = (res.tipo || "").toLowerCase();

            if (tipo === "maritimo") {
              // Éxito Marítimo
              mostrarBloqueMaritimo();
              actualizarEncabezadoMaritimo(res.encabezado || { numero_operacion: numeroOperacion });
              renderTablaMaritimo(res.data || []);

            } else {
              // Éxito FO
              mostrarBloqueFO();
              actualizarEncabezadoFO(res.encabezado || null);
              renderTablaTramos(res.tramos || []);
            }

            // Bloquear inputs tras éxito
            inputNumeroGuia.disabled = true;
            btnRastrearEnvio.classList.add("disabled");

            Swal.fire(
              "Listo",
              (res.msg || "Consulta realizada correctamente.").toUpperCase(),
              "success"
            );
          }

          // Mostrar botón limpiar después de una búsqueda
          btnLimpiar.classList.remove("d-none");

        } else {
          console.error("Error HTTP:", this.status, this.responseText);
          Swal.fire("Error", "Ocurrió un error al consultar la operación.", "error");

          // Reset visual
          ocultarBloqueFO();
          ocultarBloqueMaritimo();
          actualizarEncabezadoFO(null);
          renderTablaSinDatosFO();
          actualizarEncabezadoMaritimo(null);
          renderTablaSinDatosMaritimo();
        }
      }
    };
  }

  // =========================
  // Eventos
  // =========================
  btnRastrearEnvio.addEventListener("click", function () {
    buscarOperacion();
  });

  inputNumeroGuia.addEventListener("keyup", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
      buscarOperacion();
    }
  });

  inputNumeroGuia.addEventListener("input", function () {
    btnLimpiar.classList.add("d-none");
    // No ocultamos resultados automáticamente; solo cuando el usuario limpie.
  });

  btnLimpiar.addEventListener("click", function () {
    inputNumeroGuia.value = "";
    inicializarVista();
    inputNumeroGuia.focus();
    btnRastrearEnvio.classList.remove("disabled");
    inputNumeroGuia.disabled = false;
  });
});
