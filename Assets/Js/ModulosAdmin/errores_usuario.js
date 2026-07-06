document.addEventListener("DOMContentLoaded", function () {
  const frm = document.getElementById("frmErroresUsuario");

  if (frm) {
    frm.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarReporteError();
    });
  }
});

function registrarReporteError() {
  const frm = document.getElementById("frmErroresUsuario");
  const tipoErrorId = document.getElementById("tipo_error_id");
  const moduloId = document.getElementById("modulo_id");
  const description = document.getElementById("description");
  const proposedValue = document.getElementById("proposed_value");
  const reason = document.getElementById("reason");

  if (!tipoErrorId || tipoErrorId.value.trim() === "") {
    Swal.fire({
      icon: "warning",
      title: "Campo requerido",
      text: "Seleccione un tipo de error",
    });
    tipoErrorId.focus();
    return;
  }

  if (!moduloId || moduloId.value.trim() === "") {
    Swal.fire({
      icon: "warning",
      title: "Campo requerido",
      text: "Seleccione un módulo",
    });
    moduloId.focus();
    return;
  }

  if (!description || description.value.trim() === "") {
    Swal.fire({
      icon: "warning",
      title: "Campo requerido",
      text: "La descripción del error es obligatoria",
    });
    description.focus();
    return;
  }

  const data = new FormData(frm);
  const url = base_url + "ErroresUsuario/registrar";
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
            frm.reset();

            if (tipoErrorId) tipoErrorId.value = "";
            if (moduloId) moduloId.value = "";
            if (description) description.value = "";
            if (proposedValue) proposedValue.value = "";
            if (reason) reason.value = "";
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.msg,
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
