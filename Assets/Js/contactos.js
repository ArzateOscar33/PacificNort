document.addEventListener("DOMContentLoaded", function () {
  const formulario = document.querySelector("#contactForm");

  if (!formulario) {
    return;
  }

  const nombre = formulario.querySelector("#name");
  const email = formulario.querySelector("#email");
  const subject = formulario.querySelector("#subject");
  const message = formulario.querySelector("#message");
  const boton = formulario.querySelector("#btnContactos");

  if (!nombre || !email || !subject || !message || !boton) {
    console.error(
      "No se encontraron todos los elementos del formulario de contacto.",
    );
    return;
  }

  formulario.addEventListener("submit", async function (e) {
    e.preventDefault();

    const nombreValor = nombre.value.trim();
    const emailValor = email.value.trim();
    const asuntoValor = subject.value.trim();
    const mensajeValor = message.value.trim();

    /*
     * Validar campos vacíos.
     */
    if (
      nombreValor === "" ||
      emailValor === "" ||
      asuntoValor === "" ||
      mensajeValor === ""
    ) {
      alertas("Todos los campos son requeridos.", "warning");
      return;
    }

    /*
     * Validar el formato del correo mediante el input type="email".
     */
    if (!email.validity.valid) {
      alertas("El correo electrónico no es válido.", "warning");
      email.focus();
      return;
    }

    const data = new FormData(formulario);

    boton.disabled = true;
    boton.textContent = "Enviando...";

    try {
      const respuestaHttp = await fetch(base_url + "contactos/index", {
        method: "POST",
        body: data,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      });

      const contenido = await respuestaHttp.text();

      let resultado;

      try {
        resultado = JSON.parse(contenido);
      } catch (error) {
        console.error("Respuesta inválida del servidor:", contenido);

        throw new Error("El servidor no devolvió una respuesta JSON válida.");
      }

      /*
       * Mostrar el mensaje específico devuelto por PHP.
       */
      alertas(
        resultado.msg || "Respuesta desconocida del servidor.",
        resultado.icono || "info",
      );

      /*
       * Limpiar el formulario solamente cuando el mensaje principal
       * sí fue recibido por la empresa.
       */
      if (resultado.enviado === true) {
        formulario.reset();
      }

      /*
       * Registrar errores HTTP para diagnóstico.
       * No se muestra otra alerta porque PHP ya devolvió el mensaje.
       */
      if (!respuestaHttp.ok) {
        console.error(`Error HTTP ${respuestaHttp.status}:`, resultado);
      }
    } catch (error) {
      console.error("Error al enviar el formulario de contacto:", error);

      alertas(
        "No fue posible comunicarse con el servidor. Inténtalo nuevamente.",
        "error",
      );
    } finally {
      boton.disabled = false;
      boton.textContent = "Enviar Mensaje";
    }
  });
});

function alertas(msg, icono) {
  Swal.fire({
    title: "Aviso",
    text: msg,
    icon: icono,
    confirmButtonText: "Aceptar",
  });
}
