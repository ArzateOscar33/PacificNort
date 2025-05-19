const nombre = document.querySelector("#name");
const email = document.querySelector("#email");
const subject = document.querySelector("#subject");
const message = document.querySelector("#message");
const btn = document.querySelector("#contactForm");

document.addEventListener("DOMContentLoaded", function () {
  btn.addEventListener("submit", function (e) {
    e.preventDefault();

    if (
      nombre.value.trim() === "" ||
      email.value.trim() === "" ||
      subject.value.trim() === "" ||
      message.value.trim() === ""
    ) {
      alertas("Todos los campos son requeridos", "warning");
      return;
    }

    let data = new FormData();
    data.append("name", nombre.value.trim());
    data.append("email", email.value.trim());
    data.append("subject", subject.value.trim());
    data.append("message", message.value.trim());

    const url = base_url + "contactos/index";
    const http = new XMLHttpRequest();
    http.open("POST", url, true);
    http.send(data);

    http.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        try {
          const res = JSON.parse(this.responseText);
          alertas(res.msg, res.icono);
          if (res.icono === "success") {
            setTimeout(() => {
              window.location = base_url;
            }, 2000);
          }
        } catch (err) {
          console.error("Respuesta inválida:", this.responseText);
          alertas("Error al procesar la respuesta del servidor", "error");
        }
      }
    };
  });
});

function alertas(msg, icono) {
  Swal.fire("¿Aviso?", msg.toUpperCase(), icono);
}
