<!-- PacificNort - Fase 9: Contacto -->
<section id="contacto" class="section pn-contact" aria-labelledby="pn-contact-title">
  <div class="container">
    <div class="pn-close-heading" data-aos="fade-up">
      <span class="pn-close-kicker">Estamos para atenderte</span>
      <h2 id="pn-contact-title">Hablemos de tu próxima operación.</h2>
      <p>Conoce cómo nuestro equipo puede ayudarte a coordinar tus necesidades de logística internacional.</p>
    </div>
    <div class="pn-contact-grid">
      <div class="pn-contact-info" data-aos="fade-up">
        <span class="pn-contact-tag"><i class="bi bi-headset" aria-hidden="true"></i> Atención personalizada</span>
        <h3>Conecta con nosotros</h3>
        <p class="pn-contact-intro">Encuentra el canal de comunicación que mejor se adapte a tus necesidades.</p>
        <div class="pn-contact-items">
          <div class="pn-contact-item"><span class="pn-contact-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span><div><span>Teléfono</span><a href="tel:+16197512720">+1 (619) 751-2720</a></div></div>
          <div class="pn-contact-item"><span class="pn-contact-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span><div><span>Correo electrónico</span><a href="mailto:operaciones@pacificnort.com">operaciones@pacificnort.com</a></div></div>
          <div class="pn-contact-item"><span class="pn-contact-icon"><i class="bi bi-clock" aria-hidden="true"></i></span><div><span>Horario de atención</span><strong>Lunes a viernes · 9:00 AM – 5:00 PM</strong></div></div>
          <div class="pn-contact-item"><span class="pn-contact-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span><div><span>Oficinas</span><strong>Tijuana, Baja California, México</strong><a class="pn-contact-location-link" href="#ubicacion">Ver ubicación <i class="bi bi-arrow-down-right" aria-hidden="true"></i></a></div></div>
        </div>
        <div class="pn-contact-note"><i class="bi bi-chat-dots" aria-hidden="true"></i><span>Comunicación directa con nuestro equipo de operaciones.</span></div>
      </div>
      <div class="pn-contact-form" data-aos="fade-up" data-aos-delay="100">
        <h3>Envíanos un mensaje</h3>
        <p>Completa el formulario y cuéntanos cómo podemos ayudarte.</p>
        <form id="contactForm" method="post" action="<?= htmlspecialchars(BASE_URL . 'contactos/index', ENT_QUOTES, 'UTF-8') ?>">
          <div class="pn-contact-fields">
            <div class="pn-contact-field"><label for="name">Nombre completo <span aria-hidden="true">*</span></label><input type="text" class="form-control" id="name" name="name" autocomplete="name" placeholder="Tu nombre" required></div>
            <div class="pn-contact-field"><label for="email">Correo electrónico <span aria-hidden="true">*</span></label><input type="email" class="form-control" id="email" name="email" autocomplete="email" placeholder="nombre@empresa.com" required></div>
            <div class="pn-contact-field pn-field-full"><label for="subject">Asunto <span aria-hidden="true">*</span></label><input type="text" class="form-control" id="subject" name="subject" placeholder="¿En qué podemos ayudarte?" required></div>
            <div class="pn-contact-field pn-field-full"><label for="message">Mensaje <span aria-hidden="true">*</span></label><textarea class="form-control" id="message" name="message" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea></div>
          </div>
          <div class="pn-form-actions"><small>Todos los campos son obligatorios.</small><button type="submit" id="btnContactos" class="pn-close-button pn-close-orange">Enviar Mensaje</button></div>
          <noscript><p class="pn-no-js">Para enviar el formulario, habilita JavaScript o escríbenos por correo electrónico.</p></noscript>
        </form>
      </div>
    </div>
  </div>
</section>
