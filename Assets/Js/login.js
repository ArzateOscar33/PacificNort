const frm = document.querySelector("#formulario");
const email = document.querySelector("#email");
const clave = document.querySelector("#clave");

document.addEventListener("DOMContentLoaded", function () {
  frm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (email.value === "" || clave.value === "") {
      alertas("Todos los campos son requeridos", "warning");
      return;
    }

    const data = new FormData(this);
    const url = base_url + "admin/validar";
    const http = new XMLHttpRequest();

    http.open("POST", url, true);
    http.send(data);

    http.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status !== 200) {
          alertas("Error de servidor. Intenta nuevamente.", "error");
          return;
        }

        let res;
        try {
          res = JSON.parse(this.responseText);
        } catch (e) {
          // console.log(this.responseText);
          alertas("Respuesta inválida del servidor.", "error");
          return;
        }

        // ✅ muestra alerta
        alertas(res.msg, res.icono);

        // ✅ redirect por rol (lo decide PHP)
        if (res.icono === "success") {
          const destino = res.redirect ? res.redirect : base_url + "admin/home";
          setTimeout(() => {
            window.location = destino;
          }, 1000); // puedes dejar 2000 si quieres
        }
      }
    };
  });
});

function alertas(msg, icono) {
  Swal.fire("Aviso", (msg || "").toUpperCase(), icono);
}
