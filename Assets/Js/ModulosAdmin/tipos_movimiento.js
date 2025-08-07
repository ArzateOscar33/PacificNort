const tabla = document.getElementById("tablaTiposMovimiento");
const form = document.getElementById("formTipoMovimiento");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarTipoMovimiento"));

document.addEventListener("DOMContentLoaded", function () {
  listarTiposMovimiento();
});

function listarTiposMovimiento() {
  const tabla = document.getElementById("tablaTiposMovimiento");
  const url = base_url + "Movimiento_logistico/listar";
  const http = new XMLHttpRequest();

  http.open("GET", url, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      console.log("Respuesta del servidor:", this.responseText); // 👈 Aquí lo ves en consola
      const data = JSON.parse(this.responseText);

      tabla.innerHTML = "";

      if (data.length === 0) {
        tabla.innerHTML = `<tr><td colspan="4" class="text-center">No hay registros</td></tr>`;
        return;
      }

      data.forEach((mov) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td>${mov.nombre}</td>
          <td>${mov.tipo}</td>
          <td>${mov.moneda}</td>
          <td>
            <button class="btn btn-sm btn-info" onclick="editarTipoMovimiento(${mov.id_tipo_movimiento})"><i class="fas fa-edit"></i> Editar</button>
            <button class="btn btn-sm btn-danger" onclick="eliminarTipoMovimiento(${mov.id_tipo_movimiento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
          </td>
        `;
        tabla.appendChild(tr);
      });
    }
  };
}




 
form.addEventListener("submit", function (e) {
  e.preventDefault();

  let data = new FormData(this);
  const url = base_url + "Movimiento_logistico/registrar";
  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(data);

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
         console.log("Respuesta del servidor:", this.responseText);
      const res = JSON.parse(this.responseText);
    

      if (res.status === "success") {
        modal.hide();
        form.reset();
        listarTiposMovimiento();
      }

      Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
    }
  };
});

function editarTipoMovimiento(id) {
  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Movimiento_logistico/editar/" + id, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      console.log("Datos para editar:", this.responseText);
      const data = JSON.parse(this.responseText);

      document.getElementById("id_movimiento").value = data.id_tipo_movimiento;
      form.nombre_movimiento.value = data.nombre;
      form.tipo.value = data.tipo;
      form.moneda.value = data.moneda;

      modal.show();
    }
  };
}

function eliminarTipoMovimiento(id) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const http = new XMLHttpRequest();
      http.open("GET", base_url + "Movimiento_logistico/eliminar/" + id, true);
      http.send();

      http.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
          console.log("Respuesta al eliminar:", this.responseText);
          const res = JSON.parse(this.responseText);

          if (res.status === "success") {
            listarTiposMovimiento();
          }

          Swal.fire("Aviso", res.msg.toUpperCase(), res.status);
        }
      };
    }
  });
}

const inputBuscar = document.getElementById("buscarMovimiento");
const sugerenciasMovimiento = document.getElementById("sugerenciasMovimiento");

inputBuscar.addEventListener("keyup", function () {
  const termino = this.value.trim();

  if (termino === "") {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
    listarTiposMovimiento(); // Cargar todos si se borra el input
    return;
  }

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Movimiento_logistico/buscar?term=" + encodeURIComponent(termino), true);
  http.send();

  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
     console.log("Sugerencias recibidas:", http.responseText);
      const data = JSON.parse(http.responseText);
      
     console.log("Sugerencias recibidas:", http.responseText);

      sugerenciasMovimiento.innerHTML = "";
      tabla.innerHTML = "";

      if (data.length === 0) {
        sugerenciasMovimiento.style.display = "none";
        return;
      }

      data.forEach((mov) => {
        // Sugerencia
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = mov.nombre;
        item.type = "button";
        item.onclick = () => {
          inputBuscar.value = mov.nombre;
          sugerenciasMovimiento.innerHTML = "";
          sugerenciasMovimiento.style.display = "none";
          listarTiposMovimientoFiltrados(mov.nombre);
        };
        sugerenciasMovimiento.appendChild(item);
      });

      sugerenciasMovimiento.style.display = "block";
    }
  };
});

// Cerrar sugerencias si se hace clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar.contains(e.target) && !sugerenciasMovimiento.contains(e.target)) {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  }
});
function listarTiposMovimientoFiltrados(nombre) {
  const url = base_url + "Movimiento_logistico/buscar?term=" + encodeURIComponent(nombre);
  const http = new XMLHttpRequest();
  http.open("GET", url, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const data = JSON.parse(this.responseText);
      tabla.innerHTML = "";

      if (data.length === 0) {
        tabla.innerHTML = `<tr><td colspan="4" class="text-center">No hay resultados</td></tr>`;
        return;
      }

      data.forEach((mov) => {
        const tr = document.createElement("tr");
        tr.classList.add("text-center");
        tr.innerHTML = `
          <td>${mov.nombre}</td>
          <td>${mov.tipo || "-"}</td>
          <td>${mov.moneda || "-"}</td>
          <td>
            <button class="btn btn-sm btn-info" onclick="editarTipoMovimiento(${mov.id_tipo_movimiento})"><i class="fas fa-edit"></i> Editar</button>
            <button class="btn btn-sm btn-danger" onclick="eliminarTipoMovimiento(${mov.id_tipo_movimiento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
          </td>
        `;
        tabla.appendChild(tr);
      });
    }
  };
}

 
