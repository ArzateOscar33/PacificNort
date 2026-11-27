<?php include_once 'Views/Template/principal_header.php'; ?>






<!-- Hero -->
<section class="hero-section" id="hero">

  <!-- Video de fondo -->
  <video
    class="hero-video"
    autoplay
    muted
    loop
    playsinline
    preload="auto"
    aria-hidden="true"
    poster="<?= BASE_URL ?>Assets/Videos/pacificnort_poster.jpg">

    <source
      src="<?= BASE_URL ?>Assets/Videos/pacificnort.mp4"
      type="video/mp4">

  </video>

  <!-- Capa de contraste -->
  <div class="hero-overlay"></div>

  <!-- Contenido principal -->
  <div class="container hero-container">

    <div class="hero-content"
      data-aos="fade-up"
      data-aos-duration="1000">

      <h1>Expertos en Logística Internacional</h1>

      <p class="lead">
        Soluciones confiables para tu comercio global
      </p>

      <a href="#servicios" class="btn btn-primary hero-btn pulse">
        Nuestros Servicios
      </a>

    </div>

  </div>

</section>

<!-- rastreo -->
<?php include_once 'Views/Principal/rastreo.php'; ?>
<!-- Nosotros -->

<?php include_once 'Views/Principal/nosotros.php'; ?>
<!-- Servicios -->
<?php include_once 'Views/Principal/servicios.php'; ?>
<!-- Cómo trabajamos -->
<?php include_once 'Views/Principal/como_trabajamos.php'; ?>
<!-- PacificNort Suite -->
<?php include_once 'Views/Principal/tecnologia.php'; ?>

<!-- Galería -->
<?php include_once 'Views/Principal/galeria.php'; ?>
<!-- Contacto -->
<?php include_once 'Views/Principal/contacto.php'; ?>

<!-- Ubicación -->
<?php include_once 'Views/Principal/ubicacion.php'; ?>



<!-- Footer -->
<?php include_once 'Views/Template/principal_footer.php'; ?>

<script src="<?php echo BASE_URL; ?>Assets/Js/index.js" defer></script>
