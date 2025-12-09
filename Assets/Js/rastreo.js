// assets/js/rastreo.js

document.addEventListener("DOMContentLoaded", function () {
  const inputNumeroGuia       = document.getElementById("inputNumeroGuia");
  const btnRastrearEnvio      = document.getElementById("btnRastrearEnvio");
  const lblOperacionSeleccion = document.getElementById("lblOperacionSeleccionada");
  const tbodyRutasOperacion   = document.getElementById("tbodyRutasOperacion");
  const tablaRutas            = document.getElementById("tablaRutasOperacion");

  // Contenedor de la tabla (el div.table-responsive)
  const contenedorTabla = tablaRutas ? tablaRutas.closest(".table-responsive") : null;
  // Contenedor del encabezado: el <div class="d-flex justify-content-between ...">
  const headerRutas = lblOperacionSeleccion
    ? lblOperacionSeleccion.closest(".d-flex")
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
    // Encabezado genérico
    lblOperacionSeleccion.innerHTML =
      'Operación: <span class="fw-semibold">—</span> • Contenedor/Caja: ' +
      '<span class="fw-semibold">—</span>';

    // Quitar cualquier fila estática y dejar "Sin datos"
    renderTablaSinDatos();

    // Ocultar tabla y encabezado hasta que se haga la primera búsqueda
    if (contenedorTabla) {
      contenedorTabla.classList.add("d-none");
    }
    if (headerRutas) {
      headerRutas.classList.add("d-none");
    }

    // Ocultar botón limpiar
    btnLimpiar.classList.add("d-none");
  }

  function renderTablaSinDatos() {
    tbodyRutasOperacion.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted">
          Sin datos para mostrar.
        </td>
      </tr>
    `;
  }

  function renderTablaTramos(tramos) {
    if (!Array.isArray(tramos) || tramos.length === 0) {
      renderTablaSinDatos();
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

  function actualizarEncabezado(encabezado) {
    const numeroOperacion =
      (encabezado && encabezado.numero_operacion) ? encabezado.numero_operacion : "—";
    const contenedor =
      (encabezado && encabezado.contenedor) ? encabezado.contenedor : "—";

    lblOperacionSeleccion.innerHTML =
      'Operación: <span class="fw-semibold">' + numeroOperacion +
      '</span> • Contenedor/Caja: <span class="fw-semibold">' + contenedor + '</span>';
  }

  function mostrarBloqueRutas() {
    if (contenedorTabla) {
      contenedorTabla.classList.remove("d-none");
    }
    if (headerRutas) {
      headerRutas.classList.remove("d-none");
    }
  }

  function ocultarBloqueRutas() {
    if (contenedorTabla) {
      contenedorTabla.classList.add("d-none");
    }
    if (headerRutas) {
      headerRutas.classList.add("d-none");
    }
  }

  function buscarOperacion() {
    const numeroOperacion = inputNumeroGuia.value.trim();

    if (numeroOperacion === "") {
      Swal.fire("Aviso", "Debes ingresar un número de operación.", "warning");
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
        // Mostrar bloque (encabezado + tabla) aunque haya error,
        // para que se vea "Sin datos"
        mostrarBloqueRutas();

        if (this.status === 200) {
          let res;
          try {
            res = JSON.parse(this.responseText);
          } catch (e) {
            console.error("Error al parsear JSON:", e, this.responseText);
            Swal.fire("Error", "Respuesta inválida del servidor.", "error");
            actualizarEncabezado(null);
            renderTablaSinDatos();
            return;
          }

          if (!res.ok) {
            // No se encontraron rutas o mensaje desde el backend
            Swal.fire(
              "Sin resultados",
              (res.msg || "No se encontraron rutas.").toUpperCase(),
              "info"
            );
            actualizarEncabezado({
              numero_operacion: res.numero_operacion || numeroOperacion,
              contenedor: "—"
            });
            renderTablaSinDatos();
          } else {
            // Éxito
            actualizarEncabezado(res.encabezado || null);
            renderTablaTramos(res.tramos || []);

            Swal.fire(
              "Listo",
              (res.msg || "Rutas encontradas correctamente.").toUpperCase(),
              "success"
            );
          }

          // Mostrar botón limpiar después de una búsqueda
          btnLimpiar.classList.remove("d-none");
        } else {
          console.error("Error HTTP:", this.status, this.responseText);
          Swal.fire("Error", "Ocurrió un error al consultar la operación.", "error");
          actualizarEncabezado(null);
          renderTablaSinDatos();
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

  // Buscar al presionar Enter en el input
  inputNumeroGuia.addEventListener("keyup", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
      buscarOperacion();
    }
  });

  // Cuando el usuario empieza a escribir otra vez:
  // ocultamos botón limpiar, pero dejamos oculto el encabezado+tabla
  inputNumeroGuia.addEventListener("input", function () {
    btnLimpiar.classList.add("d-none");
    // No tocamos la tabla aquí para que el usuario pueda seguir viendo la info
    // hasta que decida limpiar. Si quisieras ocultarla aquí, podríamos llamar:
    // ocultarBloqueRutas();
  });

  // Acción del botón limpiar
  btnLimpiar.addEventListener("click", function () {
    inputNumeroGuia.value = "";
    inicializarVista();
    ocultarBloqueRutas();
    inputNumeroGuia.focus();
  });
});
