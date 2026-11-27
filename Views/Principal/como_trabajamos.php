<?php

$pnProceso = [

    [
        'icono' => 'bi-chat-dots',
        'titulo' => 'Asesoría',
        'descripcion' => 'Conocemos tus necesidades y analizamos los requerimientos de tu operación logística.'
    ],

    [
        'icono' => 'bi-clipboard-check',
        'titulo' => 'Planeación',
        'descripcion' => 'Definimos las alternativas de transporte y diseñamos una estrategia adaptada a tu mercancía.'
    ],

    [
        'icono' => 'bi-truck',
        'titulo' => 'Coordinación',
        'descripcion' => 'Coordinamos los medios de transporte y los procesos necesarios para el traslado de tu carga.'
    ],

    [
        'icono' => 'bi-geo-alt',
        'titulo' => 'Seguimiento',
        'descripcion' => 'Damos seguimiento a tu operación y mantenemos comunicación durante el proceso logístico.'
    ],

    [
        'icono' => 'bi-box-seam',
        'titulo' => 'Entrega',
        'descripcion' => 'Coordinamos la llegada de tu mercancía a su destino y el cierre de la operación.'
    ]

];

?>

<!-- ======================================
     PACIFICNORT - CÓMO TRABAJAMOS
     ====================================== -->

<section class="section pn-process"
    id="como-trabajamos"
    aria-labelledby="pn-process-title">

    <div class="container">

        <!-- Encabezado -->

        <div class="pn-process-header"
            data-aos="fade-up">

            <span class="pn-process-eyebrow">
                Nuestro proceso
            </span>

            <h2 id="pn-process-title">
                De origen a destino,
                <span>contigo en cada etapa.</span>
            </h2>

            <div class="pn-process-line"></div>

            <p>
                Simplificamos la complejidad de la logística
                internacional mediante una planificación
                organizada y un acompañamiento personalizado.
            </p>

        </div>

        <!-- Proceso logístico -->

        <ol class="pn-process-timeline">

            <?php foreach ($pnProceso as $index => $paso): ?>

                <li class="pn-process-step"
                    data-aos="fade-up"
                    data-aos-delay="<?= $index * 100 ?>">

                    <!-- Icono -->

                    <div class="pn-process-icon-wrapper">

                        <div class="pn-process-icon">

                            <i
                                class="bi <?= htmlspecialchars($paso['icono'], ENT_QUOTES, 'UTF-8') ?>"
                                aria-hidden="true">
                            </i>

                        </div>

                        <!-- Número de etapa -->

                        <span class="pn-process-number"
                            aria-hidden="true">

                            <?= str_pad(
                                $index + 1,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>

                        </span>

                    </div>

                    <!-- Información -->

                    <div class="pn-process-content">

                        <h3>
                            <?= htmlspecialchars(
                                $paso['titulo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $paso['descripcion'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    </div>

                </li>

            <?php endforeach; ?>

        </ol>

        <!-- Mensaje de confianza -->

        <div class="pn-process-footer"
            data-aos="fade-up">

            <div class="pn-process-footer-icon">

                <i class="bi bi-shield-check"
                    aria-hidden="true"></i>

            </div>

            <div class="pn-process-footer-text">

                <strong>
                    Tu confianza mueve nuestro compromiso.
                </strong>

                <p>
                    Comunicación, coordinación y atención
                    personalizada en cada etapa de tu operación.
                </p>

            </div>

            <a href="#contacto"
                class="pn-process-contact">

                Hablemos de tu operación

                <i class="bi bi-arrow-right"
                    aria-hidden="true"></i>

            </a>

        </div>

    </div>

</section>