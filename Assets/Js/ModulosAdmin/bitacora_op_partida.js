document.addEventListener("DOMContentLoaded", function () {
  const tbody = document.getElementById("tbodyBitacoraOpPartida");
  const paginacion = document.getElementById("paginacionBitacoraPartida");
  const metaResumen = document.getElementById("metaResumenBitacoraPartida");

  const inputUsuario = document.getElementById("buscarUsuarioBitacoraPartida");
  const inputEntidad = document.getElementById("buscarEntidadBitacoraPartida");
  const selectModulo = document.getElementById("filtroModuloBitacoraPartida");
  const selectAccion = document.getElementById("filtroAccionBitacoraPartida");
  const fechaInicio = document.getElementById(
    "filtroFechaInicioBitacoraPartida",
  );
  const fechaFin = document.getElementById("filtroFechaFinBitacoraPartida");
  const perPage = document.getElementById("perPageBitacoraPartida");
  const btnRefrescar = document.getElementById("btnRefrescarBitacoraOpPartida");

  let paginaActual = 1;
  let timerBusqueda = null;

  function escapeHtml(texto) {
    if (texto === null || texto === undefined) return "";
    return String(texto)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function badgeAccion(accion) {
    const map = {
      crear: "success",
      actualizacion: "primary",
      baja_logica: "danger",
      reactivacion: "warning",
      subir_imagen: "info",
      eliminar_imagen: "secondary",
    };

    const clase = map[accion] || "dark";
    return `<span class="badge bg-${clase} text-white p-2">${escapeHtml(accion || "-")}</span>`;
  }

  function renderRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No se encontraron registros en la bitácora.
                    </td>
                </tr>
            `;
      return;
    }

    let html = "";

    rows.forEach((row) => {
      html += `
                <tr>
                    <td class="text-center fw-semibold">${escapeHtml(row.id_bitacora)}</td>
                    <td>${escapeHtml(row.modulo || "-")}</td>
                    <td class="text-center">${badgeAccion(row.accion)}</td>
                    <td>${escapeHtml(row.entidad || "-")}</td>
                    <td class="text-center">${escapeHtml(row.entidad_id ?? "-")}</td>
                    <td>${escapeHtml(row.usuario_nombre || "Sistema")}</td>
                    <td>${escapeHtml(row.detalle || "-")}</td>
                    <td>${escapeHtml(row.fecha || "-")}</td>
                </tr>
            `;
    });

    tbody.innerHTML = html;
  }

  function renderPaginacion(page, totalPages) {
    paginacion.innerHTML = "";

    if (!totalPages || totalPages <= 1) return;

    const createItem = (
      label,
      targetPage,
      disabled = false,
      active = false,
    ) => {
      const li = document.createElement("li");
      li.className = `page-item${disabled ? " disabled" : ""}${active ? " active" : ""}`;

      const a = document.createElement("a");
      a.className = "page-link";
      a.href = "#";
      a.textContent = label;

      if (!disabled) {
        a.addEventListener("click", function (e) {
          e.preventDefault();
          paginaActual = targetPage;
          listarBitacora();
        });
      }

      li.appendChild(a);
      paginacion.appendChild(li);
    };

    createItem("«", page - 1, page <= 1);

    let inicio = Math.max(1, page - 2);
    let fin = Math.min(totalPages, page + 2);

    for (let i = inicio; i <= fin; i++) {
      createItem(String(i), i, false, i === page);
    }

    createItem("»", page + 1, page >= totalPages);
  }

  function listarBitacora() {
    const xhr = new XMLHttpRequest();
    const url = new URL(
      base_url + "BitacoraOpPartida/listar",
      window.location.origin,
    );

    url.searchParams.append("page", paginaActual);
    url.searchParams.append("perPage", perPage.value || 10);
    url.searchParams.append("usuario", inputUsuario.value.trim());
    url.searchParams.append("entidad", inputEntidad.value.trim());
    url.searchParams.append("modulo", selectModulo.value);
    url.searchParams.append("accion", selectAccion.value);
    url.searchParams.append("fecha_desde", fechaInicio.value);
    url.searchParams.append("fecha_hasta", fechaFin.value);

    xhr.open("GET", url.toString(), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status === 200) {
        try {
          const res = JSON.parse(xhr.responseText);

          if (!res.success) {
            tbody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center text-danger py-4">
                                    ${escapeHtml(res.message || "No fue posible cargar la bitácora.")}
                                </td>
                            </tr>
                        `;
            metaResumen.textContent = "Mostrando 0–0 de 0";
            paginacion.innerHTML = "";
            return;
          }

          renderRows(res.rows || []);
          metaResumen.textContent = `Mostrando ${res.from || 0}–${res.to || 0} de ${res.total || 0}`;
          renderPaginacion(res.page || 1, res.totalPages || 1);

          if (typeof feather !== "undefined") {
            feather.replace();
          }
        } catch (e) {
          console.error("Error parseando respuesta de bitácora:", e);
          tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                Error al interpretar la respuesta del servidor.
                            </td>
                        </tr>
                    `;
        }
      } else {
        tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-danger py-4">
                            Error HTTP ${xhr.status} al cargar la bitácora.
                        </td>
                    </tr>
                `;
      }
    };
    xhr.send();
  }

  function recargarConDebounce() {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
      paginaActual = 1;
      listarBitacora();
    }, 300);
  }

  inputUsuario.addEventListener("input", recargarConDebounce);
  inputEntidad.addEventListener("input", recargarConDebounce);
  selectModulo.addEventListener("change", function () {
    paginaActual = 1;
    listarBitacora();
  });
  selectAccion.addEventListener("change", function () {
    paginaActual = 1;
    listarBitacora();
  });
  fechaInicio.addEventListener("change", function () {
    paginaActual = 1;
    listarBitacora();
  });
  fechaFin.addEventListener("change", function () {
    paginaActual = 1;
    listarBitacora();
  });
  perPage.addEventListener("change", function () {
    paginaActual = 1;
    listarBitacora();
  });

  if (btnRefrescar) {
    btnRefrescar.addEventListener("click", function () {
      listarBitacora();
    });
  }

  listarBitacora();
});
