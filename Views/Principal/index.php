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

</head>
 
  

<nav>
  <div class="wrapper">
    <div class="logo"><a href="<?php echo BASE_URL; ?>"><img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="logo"></a></div>
    <input type="radio" name="slider" id="menu-btn">
    <input type="radio" name="slider" id="close-btn">
    <ul class="nav-links">
      <label for="close-btn" class="btn close-btn"><i class="fas fa-times"></i></label>
      <li><a href="#">Inicio</a></li>
      <li><a href="#nosotros">Acerca de Nosotros</a></li>
      <li>
        <a href="#servicios" class="desktop-item">Servicios</a>
        <input type="checkbox" id="showDrop">
        <label for="showDrop" class="mobile-item">Dropdown Menu</label>
        <ul class="drop-menu">
          <li><a href="#">Transporte Internacional</a></li>
          <li><a href="#">Gestiones Aduanales</a></li>
          <li><a href="#">Consultoría Empresarial</a></li>
          <li><a href="#">Almacenaje y Distribución</a></li>
          <li><a href="#">Seguros de Carga</a></li>
        </ul>
  
      <li><a href="#">Feedback</a></li>
    </ul>
    <label for="menu-btn" class="btn menu-btn"><i class="fas fa-bars"></i></label>
  </div>
</nav>

<div class="body-text">
  <div class="title">Responsive Dropdown and Mega Menu</div>
  <div class="sub-title">using only HTML & CSS</div>
</div>
 

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
            <h3>Nuestra Misión</h3>
            <p>Facilitar el comercio internacional de nuestros clientes a través de soluciones logísticas integrales,
              brindando un servicio personalizado y de alta calidad que optimice sus operaciones y maximice su
              rentabilidad.</p>
            <h3 class="mt-4">Nuestra Visión</h3>
            <p>Ser reconocidos como el socio estratégico preferido en el comercio internacional, destacándonos por
              nuestra profesionalidad, innovación y compromiso con la excelencia.</p>
            <h3 class="mt-4">Nuestros Valores</h3>
            <ul>
              <li>Integridad en cada acción</li>
              <li>Compromiso con la excelencia</li>
              <li>Orientación al cliente</li>
              <li>Innovación continua</li>
              <li>Responsabilidad social</li>
            </ul>
          </div>
          <div class="about-image" data-aos="fade-left" data-aos-delay="300">
            <img src="https://source.unsplash.com/800x600/?logistics,business" alt="Nuestro equipo de trabajo">
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Servicios -->
<section class="section services-section" id="servicios">
  <div class="container">
    <div class="section-title" data-aos="fade-up">
      <h2>Nuestros Servicios</h2>
    </div>
    <div class="row">
      <div class="col-lg-8 col-md-10 mx-auto">
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100">Ofrecemos soluciones logísticas
          integrales adaptadas a las necesidades específicas de cada cliente, garantizando eficiencia, seguridad y
          cumplimiento normativo.</p>
      </div>
    </div>
    <div class="row mt-5">
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi bi-truck"></i>
          </div>
          <h4>Transporte Internacional</h4>
          <p>Coordinación de envíos por aire, mar y tierra, optimizando rutas y tiempos de entrega para garantizar la
            eficiencia en cada operación.</p>
          <a href="#contacto" class="btn btn-outline-primary">Saber más</a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi bi-globe2"></i>
          </div>
          <h4>Gestiones Aduanales</h4>
          <p>Asesoría y trámites ante aduanas para importación y exportación, asegurando el cumplimiento de requisitos
            legales y regulatorios.</p>
          <a href="#contacto" class="btn btn-outline-primary">Saber más</a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi bi-briefcase"></i>
          </div>
          <h4>Consultoría Empresarial</h4>
          <p>Diagnósticos, capacitación y estrategias para tu negocio global, orientados a maximizar la eficiencia y
            reducir costos operativos.</p>
          <a href="#contacto" class="btn btn-outline-primary">Saber más</a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi bi-box-seam"></i>
          </div>
          <h4>Almacenaje y Distribución</h4>
          <p>Soluciones de almacenamiento y distribución que optimizan el flujo de mercancías y reducen tiempos de
            entrega.</p>
          <a href="#contacto" class="btn btn-outline-primary">Saber más</a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <h4>Seguros de Carga</h4>
          <p>Protección integral para tus mercancías durante todo el proceso de transporte, desde origen hasta destino
            final.</p>
          <a href="#contacto" class="btn btn-outline-primary">Saber más</a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="700">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi bi-clipboard-data"></i>
          </div>
          <h4>Análisis de Datos</h4>
          <p>Información estratégica basada en análisis de datos para optimizar la cadena de suministro y tomar
            decisiones informadas.</p>
          <a href="#contacto" class="btn btn-outline-primary">Saber más</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Galería -->
<section class="section gallery-section" id="galeria">
  <div class="container">
    <div class="section-title" data-aos="fade-up">
      <h2>Galería</h2>
    </div>
    <div class="row">
      <div class="col-lg-8 col-md-10 mx-auto">
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100">Conoce nuestros proyectos y
          operaciones a través de nuestra galería de imágenes.</p>
      </div>
    </div>
    <div class="row mt-5">
      <div class="col-12" data-aos="fade-up" data-aos-delay="200">
        <div id="galleryCarousel" class="carousel slide gallery-carousel" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="2"></button>
            <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="https://source.unsplash.com/1200x500/?logistics,truck" class="d-block w-100"
                alt="Soluciones logísticas">
              <div class="carousel-caption">
                <h5>Transporte Terrestre</h5>
                <p>Soluciones eficientes para el transporte de mercancías por carretera</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="https://source.unsplash.com/1200x500/?cargo,shipping" class="d-block w-100"
                alt="Transporte marítimo">
              <div class="carousel-caption">
                <h5>Transporte Marítimo</h5>
                <p>Conectamos negocios a través de los océanos</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="https://source.unsplash.com/1200x500/?warehouse,logistics" class="d-block w-100"
                alt="Almacenamiento">
              <div class="carousel-caption">
                <h5>Almacenamiento y Distribución</h5>
                <p>Infraestructura moderna para el manejo de mercancías</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="https://source.unsplash.com/1200x500/?global,business" class="d-block w-100" alt="Consultoría">
              <div class="carousel-caption">
                <h5>Consultoría Especializada</h5>
                <p>Asesoría experta para optimizar tus operaciones internacionales</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
            <span class="visually-hidden">Anterior</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
            <span class="visually-hidden">Siguiente</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

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
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="contact-info">
          <h4 class="mb-4">Información de Contacto</h4>
          <div class="contact-item">
            <div class="contact-icon">
              <i class="bi bi-geo-alt"></i>
            </div>
            <div class="contact-text">
              <h5>Ubicación</h5>
              <p>Blvd. Costero 1234, Zona Centro, Ensenada, B.C., México</p>
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
<footer>
  <div class="container">
    <div class="row">
      <div class="col-lg-4 col-md-6 mb-5">
        <a href="#" class="footer-logo">Pacific<span>nort</span></a>
        <p class="footer-text">Somos especialistas en logística internacional, ofreciendo soluciones integrales para
          optimizar la cadena de suministro de nuestros clientes.</p>
        <div class="footer-social">
          <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
          <a href="#" class="social-link"><i class="bi bi-linkedin"></i></a>
          <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 mb-5">
        <h4 class="footer-title">Enlaces</h4>
        <ul class="footer-links">
          <li><a href="#hero"><i class="bi bi-chevron-right"></i> Inicio</a></li>
          <li><a href="#nosotros"><i class="bi bi-chevron-right"></i> Nosotros</a></li>
          <li><a href="#servicios"><i class="bi bi-chevron-right"></i> Servicios</a></li>
          <li><a href="#galeria"><i class="bi bi-chevron-right"></i> Galería</a></li>
          <li><a href="#contacto"><i class="bi bi-chevron-right"></i> Contacto</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6 mb-5">
        <h4 class="footer-title">Servicios</h4>
        <ul class="footer-links">
          <li><a href="#"><i class="bi bi-chevron-right"></i> Transporte Internacional</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Gestiones Aduanales</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Consultoría Empresarial</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Almacenaje y Distribución</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Seguros de Carga</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6 mb-5">
        <h4 class="footer-title">Boletín</h4>
        <p class="footer-text">Suscríbete a nuestro boletín para recibir noticias y actualizaciones sobre comercio
          internacional y logística.</p>
        <form class="mt-4">
          <div class="input-group">
            <input type="email" class="form-control" placeholder="Tu email" required>
            <button class="btn btn-primary" type="submit">Suscribirse</button>
          </div>
        </form>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 Asesoría y Logística Internacional Pacificnort S.A. de C.V. Todos los derechos reservados.</p>
      <div class="footer-menu">
        <a href="#">Términos y Condiciones</a>
        <a href="#">Política de Privacidad</a>
        <a href="#">Mapa del Sitio</a>
      </div>
    </div>
  </div>
</footer>

<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/aos.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/index.js"></script>

 
</html>
</footer>