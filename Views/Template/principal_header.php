<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asesoría y Logística Internacional Pacificnort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Assets/Css/aos.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Assets/Css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>Assets/Css/pn-navbar.css">

    <!-- Fase 11.1: conectar anticipadamente y agrupar las fuentes existentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora&amp;family=Montserrat:wght@400;700&amp;family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <link rel="stylesheet"
        href="<?= BASE_URL ?>Assets/Css/pn-suite.css">

    <script src="<?= BASE_URL ?>Assets/Js/pn-suite.js" defer></script>


    <link rel="stylesheet"
        href="<?= BASE_URL ?>Assets/Css/pn-gallery.css">

    <script src="<?= BASE_URL ?>Assets/Js/pn-gallery.js" defer></script>
    <link rel="stylesheet"
        href="<?= BASE_URL ?>Assets/Css/pn-contact-footer.css">

    <link rel="manifest" href="<?php echo BASE_URL; ?>favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo BASE_URL; ?>favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

</head>


<!-- Navegación principal PacificNort -->
<header class="pn-header" id="pn-header">

    <!-- Barra superior -->
    <div class="pn-topbar">
        <div class="container">

            <div class="pn-topbar-content">

                <div class="pn-topbar-contact">

                    <a href="mailto:operaciones@pacificnort.com">
                        <i class="bi bi-envelope"></i>
                        operaciones@pacificnort.com
                    </a>

                    <a href="tel:+16197512720">
                        <i class="bi bi-telephone"></i>
                        +1 (619) 751-2720
                    </a>

                </div>

                <div class="pn-topbar-location">
                    <i class="bi bi-geo-alt"></i>
                    Tijuana, Baja California, México
                </div>

            </div>

        </div>
    </div>

    <!-- Menú principal -->
    <nav class="pn-nav navbar-expand-xl"
        aria-label="Navegación principal">

        <div class="container pn-nav-container">

            <!-- Logotipo -->
            <a class="pn-brand" href="<?= BASE_URL ?>">
                <img
                    src="<?= BASE_URL ?>Assets/img/logo-web-360.webp"
                    alt="PacificNort"
                    fetchpriority="high"
                    loading="eager">
            </a>

            <!-- Botón móvil -->
            <button
                class="navbar-toggler pn-menu-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#pnNavbar"
                aria-controls="pnNavbar"
                aria-expanded="false"
                aria-label="Abrir menú de navegación">

                <i class="bi bi-list"></i>

            </button>

            <!-- Enlaces -->
            <div class="collapse navbar-collapse"
                id="pnNavbar">

                <ul class="navbar-nav pn-nav-links">

                    <li class="nav-item">
                        <a class="nav-link active" href="#hero">
                            Inicio
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#nosotros">
                            Nosotros
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#servicios">
                            Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tecnologia">
                            Tecnología
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#galeria">
                            Galería
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#contacto">
                            Contacto
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#ubicacion">
                            Ubicación
                        </a>
                    </li>

                </ul>

                <!-- Acciones principales -->
                <div class="pn-nav-actions">

                    <a href="#rastreo" class="pn-nav-tracking">
                        <i class="bi bi-search"></i>
                        Rastreo
                    </a>

                    <a href="<?= BASE_URL ?>admin"
                        class="pn-nav-login">

                        <i class="bi bi-person-circle"></i>
                        Iniciar sesión

                    </a>

                </div>

            </div>

        </div>

    </nav>

</header>