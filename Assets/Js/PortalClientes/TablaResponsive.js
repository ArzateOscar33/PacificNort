// ==========================================
// PACIFICNORT SUITE
// TABLA RESPONSIVE - FASE 7.3
// ==========================================

(function () {
  "use strict";
  const esPartida = !!document.getElementById("tblOpsPartida");

  const card = document.getElementById(
    esPartida ? "cardTablaPartida" : "cardTablaMar",
  );

  const table = document.getElementById(
    esPartida ? "tblOpsPartida" : "tblOpsMar",
  );

  const tbody = document.getElementById(
    esPartida ? "tbOpsPartida" : "tbOpsMar",
  );

  const panel = document.getElementById("pnColumnasPanel");
  const lista = document.getElementById("pnColumnasLista");

  const btnColumnas = document.getElementById("pnBtnColumnas");
  const btnCerrar = document.getElementById("pnCerrarColumnas");

  const btnTodas = document.getElementById("pnMostrarTodas");
  const btnCompacta = document.getElementById("pnVistaCompacta");

  const btnAmpliar = document.getElementById("pnBtnAmpliarTabla");

  const textoColumnas = document.getElementById("pnColumnasTexto");
  const textoAmpliar = document.getElementById("pnAmpliarTexto");

  if (
    !card ||
    !table ||
    !tbody ||
    !panel ||
    !lista ||
    !btnColumnas ||
    !btnCerrar ||
    !btnTodas ||
    !btnCompacta ||
    !btnAmpliar ||
    !textoColumnas ||
    !textoAmpliar
  ) {
    return;
  }

  // ======================================
  // CONFIGURACIÓN
  // ======================================

  const STORAGE_KEY = esPartida
    ? "pn.portal.partida.columns.v1"
    : "pn.portal.mar.columns.v1";

  // Obtener los encabezados originales.

  const headers = Array.from(table.querySelectorAll("thead tr:first-child th"));

  const totalColumnas = headers.length;

  // La configuración corresponde a la tabla
  // actual de 15 columnas.

  const columnasEsperadas = esPartida ? 14 : 15;

  if (totalColumnas !== columnasEsperadas) {
    return;
  }

  const todas = headers.map((_, index) => index + 1);
  // Columnas que siempre deben estar disponibles.

  const obligatorias = esPartida
    ? [
        1, // Número de factura
        5, // Ver productos
        14, // Notas / Acciones
      ]
    : [
        1, // Operación
        15, // Acciones
      ];

  // Vistas compactas por módulo.

  const compacta = esPartida
    ? [
        1, // Factura
        3, // Proveedor
        5, // Productos
        6, // Caja / Ferro
        8, // Destino
        9, // Estatus
        12, // Cajas enviadas
        13, // Cajas restantes
        14, // Notas / Acciones
      ]
    : [
        1, // Operación
        2, // Contenedor
        5, // ETA
        6, // Estatus
        14, // Ubicación actual
        15, // Acciones
      ];

  let visibles = new Set(todas);

  // ======================================
  // PREFERENCIAS
  // ======================================

  function cargarPreferencias() {
    try {
      const guardadas = localStorage.getItem(STORAGE_KEY);

      if (!guardadas) return;

      const data = JSON.parse(guardadas);

      if (!Array.isArray(data)) return;

      visibles = new Set(
        data.filter(function (index) {
          return (
            Number.isInteger(index) && index >= 1 && index <= totalColumnas
          );
        }),
      );

      obligatorias.forEach(function (index) {
        visibles.add(index);
      });
    } catch (error) {
      // Si localStorage no está disponible,
      // mostramos todas las columnas.

      visibles = new Set(todas);
    }
  }

  function guardarPreferencias() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(visibles)));
    } catch (error) {
      // El selector seguirá funcionando,
      // aunque no pueda guardar preferencias.
    }
  }

  // ======================================
  // ESTADOS DE TABLA
  // ======================================

  function actualizarColspan() {
    // Adaptar los mensajes de carga, error
    // o resultados vacíos al número de
    // columnas visibles.

    tbody.querySelectorAll("td[colspan]").forEach(function (cell) {
      cell.colSpan = visibles.size;
    });
  }

  // ======================================
  // VISIBILIDAD DE COLUMNAS
  // ======================================

  function aplicarColumnas(guardar = true) {
    obligatorias.forEach(function (index) {
      visibles.add(index);
    });

    const ocultas = todas.filter(function (index) {
      return !visibles.has(index);
    });

    // CSS controla las columnas sin eliminar
    // celdas ni modificar los datos.

    table.dataset.pnHidden = ocultas.join(" ");

    textoColumnas.textContent = `Columnas (${visibles.size})`;

    // Sincronizar checkboxes.

    lista
      .querySelectorAll("input[type='checkbox']")
      .forEach(function (checkbox) {
        const index = Number(checkbox.value);

        checkbox.checked = visibles.has(index);
      });

    actualizarColspan();

    if (guardar) {
      guardarPreferencias();
    }
  }

  // ======================================
  // GENERAR SELECTOR
  // ======================================

  function generarSelector() {
    lista.replaceChildren();

    headers.forEach(function (header, index) {
      const numero = index + 1;

      const requerido = obligatorias.includes(numero);

      const label = document.createElement("label");

      label.className = "pn-column-option";

      if (requerido) {
        label.classList.add("is-required");

        label.title = "Esta columna debe permanecer visible.";
      }

      const checkbox = document.createElement("input");

      checkbox.type = "checkbox";
      checkbox.value = numero;

      checkbox.checked = visibles.has(numero);

      checkbox.disabled = requerido;

      const texto = document.createElement("span");

      texto.textContent = header.textContent.trim();

      label.appendChild(checkbox);
      label.appendChild(texto);

      lista.appendChild(label);
    });
  }

  // ======================================
  // PANEL DE CONFIGURACIÓN
  // ======================================

  function mostrarPanel(mostrar) {
    panel.hidden = !mostrar;

    btnColumnas.setAttribute("aria-expanded", String(mostrar));
  }

  btnColumnas.addEventListener("click", function () {
    mostrarPanel(panel.hidden);
  });

  btnCerrar.addEventListener("click", function () {
    mostrarPanel(false);

    btnColumnas.focus();
  });

  // Cambiar individualmente una columna.

  lista.addEventListener("change", function (event) {
    const checkbox = event.target;

    if (!checkbox.matches("input[type='checkbox']")) {
      return;
    }

    const index = Number(checkbox.value);

    if (obligatorias.includes(index)) return;

    if (checkbox.checked) {
      visibles.add(index);
    } else {
      visibles.delete(index);
    }

    aplicarColumnas();
  });

  // ======================================
  // VISTAS PREDEFINIDAS
  // ======================================

  btnTodas.addEventListener("click", function () {
    visibles = new Set(todas);

    aplicarColumnas();
  });

  btnCompacta.addEventListener("click", function () {
    visibles = new Set(compacta);

    aplicarColumnas();
  });

  // ======================================
  // VISTA AMPLIADA
  // ======================================

  function ampliarTabla(activo) {
    card.classList.toggle("pn-expanded", activo);

    document.body.classList.toggle("pn-table-expanded", activo);

    btnAmpliar.setAttribute("aria-pressed", String(activo));

    textoAmpliar.textContent = activo ? "Restaurar" : "Ampliar";

    mostrarPanel(false);

    const iconoAnterior = btnAmpliar.querySelector("svg, i");

    const icono = document.createElement("i");

    icono.setAttribute("data-feather", activo ? "minimize" : "maximize");

    icono.setAttribute("aria-hidden", "true");

    if (iconoAnterior) {
      iconoAnterior.replaceWith(icono);
    } else {
      btnAmpliar.prepend(icono);
    }

    if (window.feather) {
      window.feather.replace();
    }

    btnAmpliar.focus();
  }

  btnAmpliar.addEventListener("click", function () {
    ampliarTabla(!card.classList.contains("pn-expanded"));
  });

  // ======================================
  // TECLA ESCAPE
  // ======================================

  document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") return;

    // No interferir con los modales de detalle
    // ni con SweetAlert.

    if (document.querySelector(".modal.show, .swal2-container")) {
      return;
    }

    if (!panel.hidden) {
      mostrarPanel(false);

      btnColumnas.focus();

      return;
    }

    if (card.classList.contains("pn-expanded")) {
      ampliarTabla(false);
    }
  });

  // ======================================
  // SINCRONIZACIÓN AJAX
  // ======================================

  // OperacionesMaritimas.js reemplaza el
  // contenido del tbody cuando busca, filtra
  // o cambia de página.
  //
  // Las reglas CSS se aplican automáticamente
  // a las nuevas filas.
  //
  // Solo observamos los cambios para ajustar
  // los mensajes con colspan.

  const observer = new MutationObserver(function () {
    actualizarColspan();
  });

  observer.observe(tbody, {
    childList: true,
  });

  // ======================================
  // INICIALIZACIÓN
  // ======================================

  cargarPreferencias();

  generarSelector();

  aplicarColumnas(false);

  btnColumnas.hidden = false;
  btnAmpliar.hidden = false;
})();
