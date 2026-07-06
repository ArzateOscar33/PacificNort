const form = document.getElementById("formAgregarEstatus");
const modal = new bootstrap.Modal(
  document.getElementById("modalRegistrarEstatus"),
);
const tabla = document.getElementById("tablaEstatus");

const inputBuscar = document.getElementById("buscarEstatus");
const sugerenciasEl = document.getElementById("sugerenciasEstatus");

const colorInput = document.getElementById("color_hex");
const colorText = document.getElementById("color_hex_text");

const COLOR_DEFAULT = "#807A79";

// Inicializar
document.addEventListener("DOMContentLoaded", () => {
  listar();

  if (colorInput && colorText) {
    colorInput.value = COLOR_DEFAULT;
    colorText.value = COLOR_DEFAULT;
  }
});

// Botón agregar
document.getElementById("btnAgregarEstatus").addEventListener("click", () => {
  form.reset();

  document.getElementById("id_estatus").value = "";

  if (colorInput && colorText) {
    colorInput.value = COLOR_DEFAULT;
    colorText.value = COLOR_DEFAULT;
  }

  document.getElementById("modalRegistrarEstatusLabel").textContent =
    "Registrar Estatus";
  document.getElementById("btnSubmit").innerHTML =
    '<i data-feather="check-circle" class="me-1"></i> Agregar';

  feather.replace();
});

// Listar estatus
function listar() {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estatus/listar", true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status === 200) {
        try {
          const data = JSON.parse(this.responseText);
          renderTabla(data);
        } catch (error) {
          console.error("Error al parsear JSON:", error);
          console.error("Respuesta recibida:", this.responseText);

          Swal.fire(
            "Error",
            "No fue posible cargar los estatus. Respuesta inválida del servidor.",
            "error",
          );
        }
      } else {
        Swal.fire(
          "Error",
          "No fue posible conectar con el servidor al listar estatus.",
          "error",
        );
      }
    }
  };
}

// Renderizar tabla
function renderTabla(data) {
  tabla.innerHTML = "";

  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = `
      <tr>
        <td colspan="3" class="text-center">No se encontraron resultados</td>
      </tr>
    `;
    return;
  }

  data.forEach((item) => {
    const color = validarColorHex(item.color_hex)
      ? item.color_hex
      : COLOR_DEFAULT;

    const row = document.createElement("tr");
    row.classList.add("text-center");

    row.innerHTML = `
      <td>${item.nombre}</td>

      <td>
        <div class="d-flex justify-content-center align-items-center gap-2">
          <span 
            style="
              display:inline-block;
              width:24px;
              height:24px;
              border-radius:50%;
              background:${color};
              border:1px solid #ccc;
            ">
          </span>

          <span class="badge text-white" style="background:${color};">
            ${color}
          </span>
        </div>
      </td>

      <td>
        <button class="btn btn-sm btn-info" onclick="editarEstatus(${item.id_estatus})">
          <i class="fas fa-edit"></i> Editar
        </button>

        <button class="btn btn-sm btn-danger" onclick="eliminarEstatus(${item.id_estatus})">
          <i class="fas fa-trash-alt"></i> Eliminar
        </button>
      </td>
    `;

    tabla.appendChild(row);
  });
}

// Registrar / actualizar
form.addEventListener("submit", function (e) {
  e.preventDefault();

  const nombre = form.nombre.value.trim();

  if (nombre === "") {
    Swal.fire("Aviso", "EL NOMBRE DEL ESTATUS ES OBLIGATORIO", "warning");
    return;
  }

  if (colorText && !validarColorHex(colorText.value.trim())) {
    Swal.fire(
      "Aviso",
      "EL COLOR DEBE TENER FORMATO HEXADECIMAL. EJEMPLO: #807A79",
      "warning",
    );
    return;
  }

  if (colorInput && colorText) {
    colorInput.value = colorText.value.trim();
  }

  const http = new XMLHttpRequest();
  http.open("POST", base_url + "Estatus/registrar", true);
  http.send(new FormData(form));

  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status === 200) {
        try {
          const res = JSON.parse(this.responseText);

          if (res.status === "success") {
            modal.hide();
            form.reset();

            if (colorInput && colorText) {
              colorInput.value = COLOR_DEFAULT;
              colorText.value = COLOR_DEFAULT;
            }

            listar();
          }

          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        } catch (error) {
          console.error("Error al parsear JSON:", error);
          console.error("Respuesta recibida:", this.responseText);

          Swal.fire(
            "Error",
            "No fue posible guardar el estatus. Respuesta inválida del servidor.",
            "error",
          );
        }
      } else {
        Swal.fire(
          "Error",
          "No fue posible conectar con el servidor al guardar el estatus.",
          "error",
        );
      }
    }
  };
});

// Editar estatus
function editarEstatus(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Estatus/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status === 200) {
        try {
          const data = JSON.parse(this.responseText);

          document.getElementById("id_estatus").value = data.id_estatus;
          form.nombre.value = data.nombre;

          const color = validarColorHex(data.color_hex)
            ? data.color_hex
            : COLOR_DEFAULT;

          if (colorInput && colorText) {
            colorInput.value = color;
            colorText.value = color;
          }

          document.getElementById("modalRegistrarEstatusLabel").textContent =
            "Editar Estatus";
          document.getElementById("btnSubmit").innerHTML =
            '<i data-feather="check-circle" class="me-1"></i> Actualizar';

          feather.replace();
          modal.show();
        } catch (error) {
          console.error("Error al parsear JSON:", error);
          console.error("Respuesta recibida:", this.responseText);

          Swal.fire(
            "Error",
            "No fue posible obtener la información del estatus.",
            "error",
          );
        }
      } else {
        Swal.fire(
          "Error",
          "No fue posible conectar con el servidor al editar el estatus.",
          "error",
        );
      }
    }
  };
}

// Eliminar estatus
function eliminarEstatus(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((r) => {
    if (r.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Estatus/eliminar/" + id, true);
      http.send();

      http.onreadystatechange = function () {
        if (this.readyState === 4) {
          if (this.status === 200) {
            try {
              const res = JSON.parse(this.responseText);

              if (res.status === "success") {
                listar();
              }

              Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
            } catch (error) {
              console.error("Error al parsear JSON:", error);
              console.error("Respuesta recibida:", this.responseText);

              Swal.fire(
                "Error",
                "No fue posible eliminar el estatus. Respuesta inválida del servidor.",
                "error",
              );
            }
          } else {
            Swal.fire(
              "Error",
              "No fue posible conectar con el servidor al eliminar el estatus.",
              "error",
            );
          }
        }
      };
    }
  });
}

// Buscar + sugerencias
inputBuscar.addEventListener("keyup", function () {
  const term = this.value.trim();

  if (term === "") {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
    listar();
    return;
  }

  const http = new XMLHttpRequest();
  http.open(
    "GET",
    base_url + "Estatus/buscar?term=" + encodeURIComponent(term),
    true,
  );
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status === 200) {
        try {
          const data = JSON.parse(this.responseText);

          renderTabla(data);

          sugerenciasEl.innerHTML = "";

          if (Array.isArray(data) && data.length > 0) {
            data.slice(0, 8).forEach((t) => {
              const color = validarColorHex(t.color_hex)
                ? t.color_hex
                : COLOR_DEFAULT;

              const item = document.createElement("button");
              item.classList.add("list-group-item", "list-group-item-action");
              item.type = "button";

              item.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                  <span 
                    style="
                      display:inline-block;
                      width:14px;
                      height:14px;
                      border-radius:50%;
                      background:${color};
                      border:1px solid #ccc;
                    ">
                  </span>
                  <span>${t.nombre}</span>
                </div>
              `;

              item.onclick = () => {
                inputBuscar.value = t.nombre;
                sugerenciasEl.innerHTML = "";
                sugerenciasEl.style.display = "none";
                renderTabla([t]);
              };

              sugerenciasEl.appendChild(item);
            });

            sugerenciasEl.style.display = "block";
          } else {
            sugerenciasEl.style.display = "none";
          }
        } catch (error) {
          console.error("Error al parsear JSON:", error);
          console.error("Respuesta recibida:", this.responseText);

          Swal.fire(
            "Error",
            "No fue posible realizar la búsqueda. Respuesta inválida del servidor.",
            "error",
          );
        }
      }
    }
  };
});

// Sincronizar input color con input texto
if (colorInput && colorText) {
  colorInput.addEventListener("input", function () {
    colorText.value = this.value.toUpperCase();
  });

  colorText.addEventListener("input", function () {
    let valor = this.value.trim();

    if (valor !== "" && valor.charAt(0) !== "#") {
      valor = "#" + valor;
      this.value = valor;
    }

    if (validarColorHex(valor)) {
      colorInput.value = valor;
    }
  });

  colorText.addEventListener("blur", function () {
    let valor = this.value.trim();

    if (valor === "") {
      this.value = COLOR_DEFAULT;
      colorInput.value = COLOR_DEFAULT;
      return;
    }

    if (valor.charAt(0) !== "#") {
      valor = "#" + valor;
    }

    if (!validarColorHex(valor)) {
      Swal.fire(
        "Aviso",
        "EL COLOR INGRESADO NO ES VÁLIDO. SE USARÁ EL COLOR POR DEFECTO.",
        "warning",
      );

      this.value = COLOR_DEFAULT;
      colorInput.value = COLOR_DEFAULT;
      return;
    }

    this.value = valor.toUpperCase();
    colorInput.value = valor;
  });
}

// Cerrar sugerencias al hacer click fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar.contains(e.target) && !sugerenciasEl.contains(e.target)) {
    sugerenciasEl.innerHTML = "";
    sugerenciasEl.style.display = "none";
  }
});

// Validar color hexadecimal
function validarColorHex(color) {
  return /^#[0-9A-Fa-f]{6}$/.test(color);
}

// Exponer funciones globales para onclick
window.editarEstatus = editarEstatus;
window.eliminarEstatus = eliminarEstatus;
