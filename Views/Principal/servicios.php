<!DOCTYPE html>
<html lang="es">

<?php include_once 'Views/Template/principal_header.php'; ?>

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

 

 
 

<!-- Footer -->
<?php include_once 'Views/Template/principal_footer.php'; ?>
<script>
  const base_url = "<?php echo BASE_URL; ?>";
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/aos.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/servicios.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/sweetalert2.all.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/contactos.js"></script>

</html>
</footer>