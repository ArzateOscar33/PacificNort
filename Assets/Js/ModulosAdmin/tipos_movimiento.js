const tabla = document.getElementById("tablaTiposMovimiento");
const form = document.getElementById("formTipoMovimiento");
const modal = new bootstrap.Modal(document.getElementById("modalRegistrarTipoMovimiento"));


document.getElementById("btnAgregarTipoMovimiento").addEventListener("click", () => {
  form.reset();
  idEditar = null;

  document.getElementById("id_movimiento").value = "";

  document.getElementById("modalRegistrarTipoMovimientoLabel").textContent = "Registrar Tipo de Operación";

  const btnSubmit = document.getElementById("btnSubmit");
  btnSubmit.innerHTML = '<i data-feather="check-circle" class="me-1"></i> Agregar';

  feather.replace(); // Refresca íconos
});




function listarTiposMovimiento() {
  const tabla = document.getElementById("tablaTiposMovimiento");
  const url = base_url + "Movimiento_logistico/listar";
  const http = new XMLHttpRequest();

  http.open("GET", url, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      //console.log("Respuesta del servidor:", this.responseText); // 👈 Aquí lo ves en consola
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




 
form.addEventListener("submit", function (e) {
  e.preventDefault();

  let data = new FormData(this);
  const url = base_url + "Movimiento_logistico/registrar";
  const http = new XMLHttpRequest();
  http.open("POST", url, true);
  http.send(data);

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
         //console.log("Respuesta del servidor:", this.responseText);
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
     // console.log("Datos para editar:", this.responseText);
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
         // console.log("Respuesta al eliminar:", this.responseText);
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


//Funciones de busqueda 
const inputBuscar = document.getElementById("buscarMovimiento");
const sugerenciasMovimiento = document.getElementById("sugerenciasMovimiento");

inputBuscar.addEventListener("keyup", function () {
  const termino = this.value.trim();

  if (termino === "") {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
    listarTiposMovimiento(); // Restaurar todos
    return;
  }

  const http = new XMLHttpRequest();
  http.open("GET", base_url + "Movimiento_logistico/buscar?term=" + encodeURIComponent(termino), true);
  http.send();

  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
      const data = JSON.parse(http.responseText);
      sugerenciasMovimiento.innerHTML = "";
      tabla.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        sugerenciasMovimiento.style.display = "none";
        return;
      }

      // 👉 Actualizar tabla automáticamente mientras se escribe
      renderizarTablaFiltrada(data);

      // 👉 Mostrar sugerencias
      data.forEach((mov) => {
        const item = document.createElement("button");
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = mov.nombre;
        item.type = "button";
        item.onclick = () => {
          inputBuscar.value = mov.nombre;
          sugerenciasMovimiento.innerHTML = "";
          sugerenciasMovimiento.style.display = "none";
          listarTiposMovimientoFiltrados(mov.nombre); // Refresca con nombre exacto
        };
        sugerenciasMovimiento.appendChild(item);
      });

      sugerenciasMovimiento.style.display = "block";
    }
  };
});

function renderizarTablaFiltrada(data) {
  tabla.innerHTML = "";
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

// Cerrar sugerencias si se hace clic fuera
document.addEventListener("click", function (e) {
  if (!inputBuscar.contains(e.target) && !sugerenciasMovimiento.contains(e.target)) {
    sugerenciasMovimiento.innerHTML = "";
    sugerenciasMovimiento.style.display = "none";
  }
});

//llenar select de tipo de movimiento
function llenarSelectTipoMovimiento() {
  const selectTipoMovimiento = document.getElementById("tipoMovimiento");
  const url = base_url + "Movimiento_logistico/listarTipos";
  const http = new XMLHttpRequest();

  http.open("GET", url, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      //console.log("Respuesta del servidor:", this.responseText);
      const data = JSON.parse(this.responseText);

      selectTipoMovimiento.innerHTML = '<option value="">Seleccione un tipo de movimiento</option>';
      data.forEach((tipo) => {
        const option = document.createElement("option");
        option.value = tipo.id_tipo_movimiento;
        console.log(tipo.id_tipo_movimiento);
        option.textContent = tipo.tipo;
        selectTipoMovimiento.appendChild(option);
      });
    }
  };
}



//Funciones de busqueda por tipo de movimiento
function buscarFiltroTipo(tipo) {
  const url = base_url + "Movimiento_logistico/buscarFiltroTipo/"+ tipo;
  const http = new XMLHttpRequest(); 
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
      console.log("Datos filtrados por tipo:", this.responseText);
      console.log(url);
      const data = JSON.parse(this.responseText);
      renderizarTablaFiltrada(data);

   
    }
  };
}

document.addEventListener("DOMContentLoaded", function () {
  listarTiposMovimiento();
  llenarSelectTipoMovimiento();
  llenarSelectMonedaMovimiento();

  document.getElementById("tipoMovimiento").addEventListener("change", function () {
    const tipoSeleccionado = this.value;
    if (tipoSeleccionado) {
      console.log("Tipo seleccionado:", tipoSeleccionado);
      buscarFiltroTipo(tipoSeleccionado); 
      //console.log("Tipo seleccionaddsao:", tipoSeleccionado);
    } else {
      listarTiposMovimiento(); // Restaurar todos
    }
  });
  document.getElementById("monedaMovimiento").addEventListener("change", function () {
    const monedaSeleccionada = this.value;
    if (monedaSeleccionada) {
      console.log("Moneda seleccionada:", monedaSeleccionada);
      buscarFiltroMoneda(monedaSeleccionada);
    } else {
      listarTiposMovimiento(); // Restaurar todos
    }
  });
});
//funciones de busqueda por moneda

function llenarSelectMonedaMovimiento() { 
  const selectMonedaMovimiento = document.getElementById("monedaMovimiento");
  const url = base_url + "Movimiento_logistico/listarMonedas";
  const http = new XMLHttpRequest();

  http.open("GET", url, true);
  http.send();

  http.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      console.log("Respuesta del servidor moneda:", this.responseText);
       
      const data = JSON.parse(this.responseText);

      selectMonedaMovimiento.innerHTML = '<option value="">Seleccione una moneda</option>';
      data.forEach((moneda) => {
        const option = document.createElement("option");
        option.value = moneda.id_tipo_movimiento;
        option.textContent = moneda.moneda;
        selectMonedaMovimiento.appendChild(option);
      });
    }
  };
}

function buscarFiltroMoneda(moneda) {
  const url = base_url + "Movimiento_logistico/buscarFiltroMoneda/"+ moneda;
  const http = new XMLHttpRequest(); 
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (http.readyState === 4 && http.status === 200) {
      console.log("Datos filtrados por moneda:", this.responseText);
      console.log(url);
      const data = JSON.parse(this.responseText);
      renderizarTablaFiltrada(data);

   
    }
  };
}