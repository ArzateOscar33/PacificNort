<!DOCTYPE html>
<html lang="es">

<?php include_once 'Views/Template/principal_header.php'; ?>

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
                    <img class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/ojo.png" alt="vision">

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
                    <img class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/diamante.png" alt="valores">

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
              <img class="img-fluid" src="<?php echo BASE_URL; ?>assets/img/equipo01.jpg"
                alt="Nuestro equipo de trabajo">
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
                          <img src="<?php echo BASE_URL; ?>assets/img/log1.jpg" class="d-block w-100"
                            alt="Soluciones logísticas">
                          <div class="carousel-caption">
                            <h5>Transporte Terrestre</h5>
                            <p>Soluciones eficientes para el transporte de mercancías por carretera</p>
                          </div>
                        </div>
                        <div class="carousel-item">
                          <img src="<?php echo BASE_URL; ?>assets/img/log2.jpg" class="d-block w-100"
                            alt="Transporte marítimo">
                          <div class="carousel-caption">
                            <h5>Transporte Marítimo</h5>
                            <p>Conectamos negocios a través de los océanos</p>
                          </div>
                        </div>
                        <div class="carousel-item">
                          <img src="<?php echo BASE_URL; ?>assets/img/log3.jpg" class="d-block w-100"
                            alt="Almacenamiento">
                          <div class="carousel-caption">
                            <h5>Almacenamiento y Distribución</h5>
                            <p>Infraestructura moderna para el manejo de mercancías</p>
                          </div>
                        </div>
                        <div class="carousel-item">
                          <img src="<?php echo BASE_URL; ?>assets/img/log4.jpg" class="d-block w-100"
                            alt="Consultoría">
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
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100">Ponte encontactocon
          nuestroequipo para conocer más sobre nuestros servicios o solicitar una cotización personalizada.</p>
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
                  <input type="text" class="form-control" placeholder="Nombre completo" id="name" name="name" >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <input type="email" class="form-control" placeholder="Correo electrónico" id="email" name="email" >
                </div>
              </div>
            </div>
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Asunto" id="subject" name="subject" >
            </div>
            <div class="form-group">
              <textarea class="form-control" placeholder="Mensaje"  id="message" name="message" ></textarea>
            </div>
            <button type="submit" class="submit-btn" id="btnContactos">Enviar Mensaje</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>


 <!-- Footer -->
<?//php include_once 'Views/Template/principal_footer.php'; ?>
<script>
  const base_url = "<?php echo BASE_URL; ?>";
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/aos.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/index.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/sweetalert2.all.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/contactos.js"></script>
</html>
</footer>