<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Asesoría y Logística Internacional Pacificnort</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/aos.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/navbar.css">  
  <link href="https://fonts.googleapis.com/css2?family=Lora&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>



<nav>
  <div class="wrapper">
    <div class="logo"><a href="<?php echo BASE_URL; ?>"><img  class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="logo"></a></div>
    <input type="radio" name="slider" id="menu-btn"> 
    <input type="radio" name="slider" id="close-btn">
    <ul class="nav-links">
      <label for="close-btn" class="btn close-btn"><i class="fas fa-times"></i></label>
      <li><a href="#">Inicio</a></li>
      <li><a href="#nosotros">Acerca de Nosotros</a></li>
      <li>
        <a href="#servicios" class="desktop-item"><i class="fas fa-truck"></i> Servicios</a>
        <input type="checkbox" id="showDrop">
        <label for="showDrop" class="mobile-item">Servicios</label>
        <ul class="drop-menu">
          <!-- <li><a href="#">Transporte Internacional</a></li>
          <li><a href="#">Gestiones Aduanales</a></li>
          <li><a href="#">Consultoría Empresarial</a></li>
          <li><a href="#">Almacenaje y Distribución</a></li>
          <li><a href="#">Seguros de Carga</a></li> !-->
        </ul>

      <li><a href="#">Iniciar Sesión</a></li>
    </ul>
    <label for="menu-btn" class="btn menu-btn" id="menu-btn"><i class="fas fa-bars fa-2x"></i></label>
  </div>
</nav>

 


<div class="collapse navbar-collapse" id="navbarNav">
  <ul class="navbar-nav ms-auto">
    <li class="nav-item"><a class="nav-link active" href="#hero">Inicio</a></li>
    <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
    <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
    <li class="nav-item"><a class="nav-link" href="#galeria">Galería</a></li>
    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
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
      <a href="#servicios" class="btn btn-primary hero-btn pulse">Nuestros Servicios</a>
    </div>
  </div>
</section>

<!-- Nosotros -->
<section class="section" id="nosotros">
  <div class="container">
    <div class="section-title" data-aos="fade-up">
      <h2>Nosotros</h2>
    </div>
    <div class="row">
      <div class="col-lg-8 col-md-10 mx-auto">
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100">Somos una empresa mexicana
          especializada en brindar asesoría y servicios de logística internacional, garantizando eficacia y cumplimiento
          en cada etapa del proceso.</p>
      </div>
    </div>
    <div class="row mt-5">
      <div class="col-lg-10 mx-auto">
        <div class="about-content">
          <div class="about-text" data-aos="fade-right" data-aos-delay="200">

            <div class="grid-container">
              <!-- Mision -->
              <div class="holographic-container">
                <div class="holographic-card">
                  <div class="card-body">
                    <img class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/objetivo.png" alt="mision">

                    <h2>Nuestra Misión</h2>
                    <p class="card-text">
                    <p>Facilitar el comercio internacional de nuestros clientes a través de soluciones logísticas
                      integrales,
                      brindando un servicio personalizado y de alta calidad que optimice sus operaciones y maximice su
                      rentabilidad.</p>
                  </div>
                </div>
              </div>

              <!-- Vision -->
              <div class="holographic-container">
                <div class="holographic-card">
                  <div class="card-body">
                    <img  class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/ojo.png" alt="vision">

                    <h2>Nuestra Visión</h2>
                    <p class="card-text">
                    <p>Ser reconocidos como el socio estratégico preferido en el comercio internacional, destacándonos
                      por
                      nuestra profesionalidad, innovación y compromiso con la excelencia.</p>
                  </div>
                </div>
              </div>
              <!-- Valores -->
              <div class="holographic-container">
                <div class="holographic-card">
                  <div class="card-body">
                    <img  class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/diamante.png" alt="valores">

                    <h2>Nuestros Valores</h2>
                    <ul class="card-text">
                      <li>Integridad en cada acción</li>
                      <li>Compromiso con la excelencia</li>
                      <li>Orientación al cliente</li>
                      <li>Innovación continua</li>
                      <li>Responsabilidad social</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="about-image" data-aos="fade-left" data-aos-delay="300">
              <img class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/equipo01.jpg" alt="Nuestro equipo de trabajo">
            </div>



            <!-- Contacto -->
            <section class="section contact-section" id="contacto">
              <div class="container">
                <div class="section-title" data-aos="fade-up">
                  <h2>Contáctanos</h2>
                </div>
                <div class="row">
                  <div class="col-lg-8 col-md-10 mx-auto">
                    <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100">Ponte en contacto con nuestro
                      equipo para conocer más sobre nuestros servicios o solicitar una cotización personalizada.</p>
                  </div>
                </div>
                <div class="row mt-5">
                  <div class="col-lg-4 col-md-6 col-12" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-info">
                      <h4 class="mb-4">Información de Contacto</h4>
                      <div class="contact-item">
                        <div class="contact-icon">
                          <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="contact-text">
                          <h5>Ubicación</h5>
                          <p>Av. Alejandro Von Humboldt, Garita de Otay, 22430 Tijuana, B.C., México</p>
                        </div>
                      </div>
                      <div class="contact-item">
                        <div class="contact-icon">
                          <i class="bi bi-telephone"></i>
                        </div>
                        <div class="contact-text">
                          <h5>Teléfono</h5>
                          <p>+52 (646) 123-4567</p>
                        </div>
                      </div>
                      <div class="contact-item">
                        <div class="contact-icon">
                          <i class="bi bi-envelope"></i>
                        </div>
                        <div class="contact-text">
                          <h5>Email</h5>
                          <p>info@pacificnort.com</p>
                        </div>
                      </div>
                      <div class="contact-item">
                        <div class="contact-icon">
                          <i class="bi bi-clock"></i>
                        </div>
                        <div class="contact-text">
                          <h5>Horario</h5>
                          <p>Lunes a Viernes: 9:00 AM - 6:00 PM</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-8" data-aos="fade-up" data-aos-delay="300">
                    <div class="contact-form">
                      <h4 class="mb-4">Envíanos un Mensaje</h4>
                      <form id="contactForm">
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group">
                              <input type="text" class="form-control" placeholder="Nombre completo" required>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group">
                              <input type="email" class="form-control" placeholder="Correo electrónico" required>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <input type="text" class="form-control" placeholder="Asunto" required>
                        </div>
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Mensaje" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Enviar Mensaje</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <!-- Footer -->


            <script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
            <script src="<?php echo BASE_URL; ?>assets/js/aos.js"></script>
            <script src="<?php echo BASE_URL; ?>assets/js/index.js"></script>


</html>
</footer>