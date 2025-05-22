<!DOCTYPE html>
<html lang="es">

<?php include_once 'Views/Template/principal_header.php'; ?>

<div class="collapse navbar-collapse" id="navbarNav">
  <ul class="navbar-nav ms-auto">
    <li class="nav-item"><a class="nav-link active" href="#hero">Inicio</a></li>
    <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
    <li class="nav-item"><a class="nav-link" href="">Servicios</a></li>
    <li class="nav-item"><a class="nav-link" href="#galeria">Galería</a></li>
    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
    <li class="nav-item"><a class="nav-link" href="#ubicacion">Ubicacion</a></li>
  </ul>
</div>
</div>
</nav>

<!-- Hero -->
<section class="hero-section" id="hero">
  <div class="container">
    <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
      <h1>Expertos en Logística Internacional</h1>
      <p class="lead">Soluciones confiables para tu comercio global</p>
      <a href="#servicios" class="btn btn-primary hero-btn pulse">Nuestros
        Servicios</a>
    </div>
  </div>
</section>

<!-- nosotros -->
 <?php include_once 'Views/Principal/nosotros.php'; ?>
 <!-- Servicios -->
 <?php include_once 'Views/Principal/servicios.php'; ?>
<!-- Galería -->
<?php include_once 'Views/Principal/galeria.php'; ?>
<!-- Contacto -->
<?php include_once 'Views/Principal/contacto.php'; ?>
<!-- Ubicación -->
<?php include_once 'Views/Principal/ubicacion.php'; ?>

<!-- Footer -->
<?php include_once 'Views/Template/principal_footer.php'; ?>
 
</html>
</footer>