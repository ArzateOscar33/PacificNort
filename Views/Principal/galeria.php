<!DOCTYPE html>
<html lang="es">

<?php include_once 'Views/Template/principal_header.php'; ?>

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
              <img src="<?php echo BASE_URL; ?>assets/img/log1.jpg" class="d-block w-100" alt="Soluciones logísticas">
              <div class="carousel-caption">
                <h5>Transporte Terrestre</h5>
                <p>Soluciones eficientes para el transporte de mercancías por carretera</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="<?php echo BASE_URL; ?>assets/img/log2.jpg" class="d-block w-100" alt="Transporte marítimo">
              <div class="carousel-caption">
                <h5>Transporte Marítimo</h5>
                <p>Conectamos negocios a través de los océanos</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="<?php echo BASE_URL; ?>assets/img/log3.jpg" class="d-block w-100" alt="Almacenamiento">
              <div class="carousel-caption">
                <h5>Almacenamiento y Distribución</h5>
                <p>Infraestructura moderna para el manejo de mercancías</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="<?php echo BASE_URL; ?>assets/img/log4.jpg" class="d-block w-100" alt="Consultoría">
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

 
 

 


</html>
</footer>