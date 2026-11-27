<!-- PacificNort - Fase 9: Footer -->
<footer id="pnFooter" class="pn-site-footer" aria-label="Información de PacificNort">
  <div class="container">
    <div class="pn-footer-main">
      <div class="pn-footer-brand">
        <a class="pn-footer-wordmark" href="#hero" aria-label="PacificNort, inicio">Pacific<span>nort</span></a>
        <p>Soluciones integrales para el comercio internacional. Coordinación logística y tecnología propia al servicio de nuestros clientes.</p>
        <a class="pn-footer-whatsapp" id="social-link-whatsapp" href="https://wa.me/526644913156?text=Hola%20PacificNort%2C%20me%20gustar%C3%ADa%20obtener%20informaci%C3%B3n%20sobre%20sus%20servicios%20log%C3%ADsticos." target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
      </div>
      <div class="pn-footer-group">
        <h2>Explora</h2>
        <ul>
          <li><a href="#hero">Inicio</a></li>
          <li><a href="#nosotros">Nosotros</a></li>
          <li><a href="#servicios">Servicios</a></li>
          <li><a href="#como-trabajamos">Cómo trabajamos</a></li>
          <li><a href="#galeria">Galería</a></li>
        </ul>
      </div>
      <div class="pn-footer-group">
        <h2>Tecnología</h2>
        <ul>
          <li><a href="#tecnologia">PacificNort Suite</a></li>
          <li><a href="#rastreo">Rastreo de carga</a></li>
          <li><a href="<?= htmlspecialchars(BASE_URL . 'admin', ENT_QUOTES, 'UTF-8') ?>">Portal de clientes <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></li>
          <li><a href="#contacto">Solicitar información</a></li>
        </ul>
      </div>
      <div class="pn-footer-group pn-footer-contact">
        <h2>Contacto</h2>
        <ul>
          <li><a href="tel:+16197512720"><i class="bi bi-telephone" aria-hidden="true"></i><span>+1 (619) 751-2720</span></a></li>
          <li><a href="mailto:operaciones@pacificnort.com"><i class="bi bi-envelope" aria-hidden="true"></i><span>operaciones@pacificnort.com</span></a></li>
          <li><a href="#ubicacion"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Blvd. Agua Caliente 10535, Tijuana, B.C.</span></a></li>
          <li><span class="pn-footer-hours"><i class="bi bi-clock" aria-hidden="true"></i><span>Lunes a viernes<br>9:00 AM – 5:00 PM</span></span></li>
        </ul>
      </div>
    </div>
    <div class="pn-footer-bottom">
      <p>&copy; <?= date('Y') ?> Asesoría y Logística Internacional Pacificnort S.A. de C.V. Todos los derechos reservados.</p>
      <a href="#hero" class="pn-footer-top">Volver al inicio <i class="bi bi-arrow-up" aria-hidden="true"></i></a>
    </div>
  </div>
</footer>

<!-- Conservar dependencias del footer original -->
<script>
  const base_url = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>Assets/Js/bootstrap.bundle.min.js" defer></script>
<script src="<?= BASE_URL ?>Assets/Js/aos.js" defer></script>
<script src="<?= BASE_URL ?>Assets/Js/servicios.js" defer></script>
<script src="<?= BASE_URL ?>Assets/Js/sweetalert2.all.min.js" defer></script>
<script src="<?= BASE_URL ?>Assets/Js/contactos.js" defer></script>
