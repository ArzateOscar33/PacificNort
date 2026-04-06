document.addEventListener("DOMContentLoaded", function () {
  listarReportesErrores();

  const btnBuscar = document.getElementById("btnBuscarErroresAdmin");
  const btnLimpiar = document.getElementById("btnLimpiarFiltrosErroresAdmin");
  const frmResolver = document.getElementById("frmResolverErrorAdmin");

  if (btnBuscar) {
    btnBuscar.addEventListener("click", function () {
      listarReportesErrores();
    });
  }

  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", function () {
      document.getElementById("filtro_estatus").value = "";
      document.getElementById("filtro_modulo").value = "";
      document.getElementById("filtro_tipo").value = "";
      document.getElementById("filtro_busqueda").value = "";
      listarReportesErrores();
    });
  }

  if (frmResolver) {
    frmResolver.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarEstatusReporte();
    });
  }
});

function listarReportesErrores() {
  const estatus = document.getElementById("filtro_estatus")
    ? document.getElementById("filtro_estatus").value
    : "";
  const modulo = document.getElementById("filtro_modulo")
    ? document.getElementById("filtro_modulo").value
    : "";
  const tipo = document.getElementById("filtro_tipo")
    ? document.getElementById("filtro_tipo").value
    : "";
  const busqueda = document.getElementById("filtro_busqueda")
    ? document.getElementById("filtro_busqueda").value.trim()
    : "";

  const tbody = document.getElementById("tbodyErroresAdmin");
  const totalBadge = document.getElementById("totalReportesErrores");

  const url =
    base_url +
    "ErroresAdmin/listar?estatus=" +
    encodeURIComponent(estatus) +
    "&modulo=" +
    encodeURIComponent(modulo) +
    "&tipo=" +
    encodeURIComponent(tipo) +
    "&busqueda=" +
    encodeURIComponent(busqueda);

  const http = new XMLHttpRequest();
  http.open("GET", url, true);

  http.onreadystatechange = function () {
    if (http.readyState === 4) {
      if (http.status === 200) {
        let res;

        try {
          res = JSON.parse(http.responseText);
        } catch (error) {
          if (tbody) {
            tbody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center text-danger">Respuesta inválida del servidor</td>
                            </tr>
                        `;
          }
          return;
        }

        if (!res.status) {
          if (tbody) {
            tbody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center text-danger">${res.msg || "No se pudieron cargar los reportes"}</td>
                            </tr>
                        `;
          }
          return;
        }

        const data = Array.isArray(res.data) ? res.data : [];
        let html = "";

        if (data.length === 0) {
          html = `
                        <tr>
                            <td colspan="10" class="text-center text-muted">No hay reportes para mostrar</td>
                        </tr>
                    `;
        } else {
          for (let i = 0; i < data.length; i++) {
            const row = data[i];

            html += `
                            <tr>
                                <td>${row.id_reporte ?? ""}</td>
                                <td>${escapeHtml(row.tipo_error ?? "")}</td>
                                <td>${escapeHtml(row.modulo ?? "")}</td>
                                <td>${escapeHtml(row.descripcion ?? "")}</td>
                                <td>${escapeHtml(row.reportado_por ?? "")}</td>
                                <td>${escapeHtml(row.fecha_reporte ?? "")}</td>
                                <td>${escapeHtml(row.resuelto_por ?? "-")}</td>
                                <td>${escapeHtml(row.fecha_resolucion ?? "-")}</td>
                                <td>${renderBadgeEstatus(row.estatus)}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" type="button" onclick="verReporte(${row.id_reporte})">
                                        <i data-feather="eye"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
          }
        }

        if (tbody) {
          tbody.innerHTML = html;
        }

        if (totalBadge) {
          totalBadge.textContent = data.length + " reportes";
        }

        if (typeof feather !== "undefined") {
          feather.replace();
        }
      } else {
        if (tbody) {
          tbody.innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center text-danger">Error al cargar los reportes</td>
                        </tr>
                    `;
        }
      }
    }
  };

  http.send();
}

function verReporte(idReporte) {
  if (!idReporte || idReporte <= 0) {
    Swal.fire({
      icon: "warning",
      title: "Reporte inválido",
      text: "No se pudo identificar el reporte",
    });
    return;
  }

  const url =
    base_url +
    "ErroresAdmin/getReporte?id_reporte=" +
    encodeURIComponent(idReporte);
  const http = new XMLHttpRequest();
  http.open("GET", url, true);

  http.onreadystatechange = function () {
    if (http.readyState === 4) {
      if (http.status === 200) {
        let res;

        try {
          res = JSON.parse(http.responseText);
        } catch (error) {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "La respuesta del servidor no es válida",
          });
          return;
        }

        if (!res.status || !res.data) {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.msg || "No se pudo obtener el detalle del reporte",
          });
          return;
        }

        const row = res.data;

        document.getElementById("id_reporte_admin").value =
          row.id_reporte ?? "";
        document.getElementById("detalle_tipo_error").value =
          row.tipo_error ?? "";
        document.getElementById("detalle_modulo_error").value =
          row.modulo ?? "";
        document.getElementById("detalle_reportado_por").value =
          row.reportado_por ?? "";
        document.getElementById("detalle_fecha_reporte").value =
          row.fecha_reporte ?? "";
        document.getElementById("detalle_descripcion").value =
          row.descripcion ?? "";
        document.getElementById("detalle_valor_propuesto").value =
          row.valor_propuesto ?? "";
        document.getElementById("detalle_razon_error").value =
          row.razon_error ?? "";
        document.getElementById("estatus_nuevo").value = "";

        const modalElement = document.getElementById("modalResolverError");
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        if (typeof feather !== "undefined") {
          feather.replace();
        }
      } else {
        Swal.fire({
          icon: "error",
          title: "Error del servidor",
          text: "No se pudo cargar el detalle del reporte",
        });
      }
    }
  };

  http.send();
}

function actualizarEstatusReporte() {
  const frm = document.getElementById("frmResolverErrorAdmin");
  const idReporte = document.getElementById("id_reporte_admin");
  const estatusNuevo = document.getElementById("estatus_nuevo");

  if (!idReporte || idReporte.value.trim() === "") {
    Swal.fire({
      icon: "warning",
      title: "Campo requerido",
      text: "No se identificó el reporte",
    });
    return;
  }

  if (!estatusNuevo || estatusNuevo.value.trim() === "") {
    Swal.fire({
      icon: "warning",
      title: "Campo requerido",
      text: "Seleccione un nuevo estatus",
    });
    estatusNuevo.focus();
    return;
  }

  const data = new FormData(frm);
  const url = base_url + "ErroresAdmin/actualizarEstatus";
  const http = new XMLHttpRequest();

  http.open("POST", url, true);

  http.onreadystatechange = function () {
    if (http.readyState === 4) {
      if (http.status === 200) {
        let res;

        try {
          res = JSON.parse(http.responseText);
        } catch (error) {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "La respuesta del servidor no es válida",
          });
          return;
        }

        if (res.status) {
          Swal.fire({
            icon: "success",
            title: "Éxito",
            text: res.msg,
          }).then(() => {
            const modalElement = document.getElementById("modalResolverError");
            const modalInstance = bootstrap.Modal.getInstance(modalElement);

            if (modalInstance) {
              modalInstance.hide();
            }

            frm.reset();
            listarReportesErrores();
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.msg || "No se pudo actualizar el estatus",
          });
        }
      } else {
        Swal.fire({
          icon: "error",
          title: "Error del servidor",
          text: "No se pudo procesar la solicitud",
        });
      }
    }
  };

  http.send(data);
}

function renderBadgeEstatus(estatus) {
  const valor = String(estatus);

  if (valor === "0") {
    return `<span class="badge bg-warning text-dark">Sin resolver</span>`;
  }

  if (valor === "1") {
    return `<span class="badge bg-success text-white">Resuelto</span>`;
  }

  if (valor === "2") {
    return `<span class="badge bg-danger text-white">Rechazado</span>`;
  }

  return `<span class="badge bg-secondary text-white">Desconocido</span>`;
}

function escapeHtml(text) {
  if (text === null || text === undefined) return "";
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
