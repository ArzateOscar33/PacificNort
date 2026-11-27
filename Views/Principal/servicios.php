<?php

$pnServicios = [

  [
    'titulo' => 'Transporte marítimo',
    'categoria' => 'MARÍTIMO',
    'icono' => 'bi-water',
    'imagen' => 'log2.jpg',
    'descripcion' => 'Coordinación de operaciones marítimas internacionales, facilitando el traslado de mercancías entre puertos y mercados globales.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Transporte ferroviario',
    'categoria' => 'FERROVIARIO',
    'icono' => 'bi-train-front',
    'imagen' => 'servicio_ferroviario.jpg',
    'descripcion' => 'Soluciones de transporte ferroviario e intermodal para optimizar el traslado de mercancías y contenedores.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Transporte terrestre',
    'categoria' => 'TERRESTRE',
    'icono' => 'bi-truck',
    'imagen' => 'log1.jpg',
    'descripcion' => 'Coordinación de transporte por carretera, distribución de mercancías y conexión entre puntos logísticos.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Transporte aéreo',
    'categoria' => 'AÉREO',
    'icono' => 'bi-airplane',
    'imagen' => 'servicio_aereo.jpg',
    'descripcion' => 'Alternativas de transporte aéreo internacional adaptadas a las necesidades y tiempos de cada operación.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Gestiones aduanales',
    'categoria' => 'COMERCIO EXTERIOR',
    'icono' => 'bi-file-earmark-check',
    'imagen' => 'servicio_aduanal.jpg',
    'descripcion' => 'Asesoría y coordinación de trámites relacionados con procesos de importación, exportación y cumplimiento normativo.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Almacenaje y distribución',
    'categoria' => 'ALMACENAJE',
    'icono' => 'bi-box-seam',
    'imagen' => 'log3.jpg',
    'descripcion' => 'Soluciones para el almacenamiento, manejo y distribución de mercancías a través de la cadena de suministro.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Consultoría y análisis',
    'categoria' => 'ASESORÍA',
    'icono' => 'bi-graph-up-arrow',
    'imagen' => 'log4.jpg',
    'descripcion' => 'Asesoría especializada y análisis de información para optimizar procesos, costos y decisiones logísticas.',
    'enlace' => '#contacto',
    'boton' => 'Más información'
  ],

  [
    'titulo' => 'Tecnología y rastreo',
    'categoria' => 'TECNOLOGÍA',
    'icono' => 'bi-geo-alt',
    'imagen' => 'servicio_rastreo.jpg',
    'descripcion' => 'Consulta el estado de tus operaciones y contenedores mediante nuestras herramientas digitales de seguimiento logístico.',
    'enlace' => '#rastreo',
    'boton' => 'Rastrear carga'
  ]

];

?>

<!-- ======================================
     SERVICIOS PACIFICNORT
     ====================================== -->

<section class="section pn-services"
  id="servicios"
  aria-labelledby="pn-services-title">

  <div class="container">

    <!-- Encabezado de la sección -->

    <div class="pn-services-header"
      data-aos="fade-up">

      <span class="pn-services-eyebrow">
        Nuestras soluciones
      </span>

      <h2 id="pn-services-title">
        Logística integral,
        <span>sin fronteras.</span>
      </h2>

      <div class="pn-services-line"></div>

      <p>
        Conectamos cada etapa de tu cadena de suministro
        mediante soluciones logísticas adaptadas a las
        necesidades de tu negocio.
      </p>

    </div>

    <!-- Tarjetas de servicios -->

    <div class="row g-4">

      <?php foreach ($pnServicios as $index => $servicio): ?>

        <?php

        $imagenExiste = is_file(
          dirname(__DIR__, 2)
            . '/Assets/img/'
            . $servicio['imagen']
        );

        ?>

        <div class="col-12 col-md-6 col-xl-3"
          data-aos="fade-up"
          data-aos-delay="<?= ($index % 4) * 80 ?>">

          <article class="pn-service-card">

            <!-- Imagen del servicio -->

            <div class="pn-service-media">

              <?php if ($imagenExiste): ?>

                <img
                  src="<?= BASE_URL ?>Assets/img/<?= htmlspecialchars($servicio['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                  alt=""
                  loading="lazy"
                  decoding="async"
                  width="640"
                  height="360">

              <?php else: ?>

                <!-- Respaldo si todavía no existe la imagen -->

                <div class="pn-service-placeholder"
                  aria-hidden="true">

                  <i class="bi <?= htmlspecialchars($servicio['icono'], ENT_QUOTES, 'UTF-8') ?>"></i>

                </div>

              <?php endif; ?>

              <div class="pn-service-overlay"></div>

              <span class="pn-service-category">

                <?= htmlspecialchars($servicio['categoria'], ENT_QUOTES, 'UTF-8') ?>

              </span>

            </div>

            <!-- Información -->

            <div class="pn-service-body">

              <div class="pn-service-icon"
                aria-hidden="true">

                <i class="bi <?= htmlspecialchars($servicio['icono'], ENT_QUOTES, 'UTF-8') ?>"></i>

              </div>

              <h3>
                <?= htmlspecialchars($servicio['titulo'], ENT_QUOTES, 'UTF-8') ?>
              </h3>

              <p>
                <?= htmlspecialchars($servicio['descripcion'], ENT_QUOTES, 'UTF-8') ?>
              </p>

              <a
                href="<?= htmlspecialchars($servicio['enlace'], ENT_QUOTES, 'UTF-8') ?>"
                class="pn-service-link"
                aria-label="<?= htmlspecialchars($servicio['boton'] . ': ' . $servicio['titulo'], ENT_QUOTES, 'UTF-8') ?>">

                <?= htmlspecialchars($servicio['boton'], ENT_QUOTES, 'UTF-8') ?>

                <i class="bi bi-arrow-right"
                  aria-hidden="true"></i>

              </a>

            </div>

          </article>

        </div>

      <?php endforeach; ?>

    </div>

    <!-- Llamada a la acción -->

    <div class="pn-services-cta"
      data-aos="fade-up">

      <div class="pn-services-cta-content">

        <span>
          Estamos para ayudarte
        </span>

        <h3>
          Tu operación, nuestra prioridad.
        </h3>

        <p>
          Descubre cómo podemos ayudarte a optimizar
          tus procesos de logística internacional.
        </p>

      </div>

      <a href="#contacto"
        class="pn-services-cta-btn">

        <i class="bi bi-chat-dots"
          aria-hidden="true"></i>

        Contacta con nosotros

        <i class="bi bi-arrow-right"
          aria-hidden="true"></i>

      </a>

    </div>

  </div>

</section>