

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
 
    <link rel="manifest" href="<?php echo BASE_URL; ?>/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo BASE_URL; ?>/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <title><?php echo $data['title']; ?></title>
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <!-- Custom CSS -->
   <!-- <script src="https://unpkg.com/feather-icons"></script>  -->
    <script src="<?= BASE_URL ?>assets/js/modulosAdmin/librerias/feather.min.js"></script>

    <link href="<?php echo BASE_URL; ?>/dist/css/style.min.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

    <!--<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>-->
    <!--<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>-->
    <!-- SweetAlert2 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
</head>

<body>

    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">

        <header class="topbar" data-navbarbg="skin6">
            <nav class="navbar top-navbar navbar-expand-md">
                <div class="navbar-header" data-logobg="skin6">
                    <!-- This is for the sidebar toggle which is visible on mobile only -->
                    <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i
                            class="ti-menu ti-close"></i></a>
                    <!-- ============================================================== -->
                    <!-- Logo -->
                    <!-- ============================================================== -->
                    <div class="navbar-brand">
                        <!-- Logo icon -->
                        <a href="<?php echo BASE_URL . 'admin'; ?>">
                            <b class="logo-icon">
                                <!-- Dark Logo icon -->
                                <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="homepage"
                                    class="dark-logo img-fluid" />
                                <!-- Light Logo icon -->
                                <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="homepage"
                                    class="light-logo" />
                            </b>
                            <!--End Logo icon -->
                            <!-- Logo text -->

                        </a>
                    </div>
                    <!-- End Logo -->
                    <!-- Toggle which is visible on mobile only -->
                    <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)"
                        data-toggle="collapse" data-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i
                            class="ti-more"></i></a>
                </div>

                <!-- End Logo -->

                <div class="navbar-collapse collapse" id="navbarSupportedContent">

                    <!-- toggle and nav items -->

                    <ul class="navbar-nav float-left mr-auto ml-3 pl-1">
                        <!-- Notification -->

                        <!-- End Notification -->
                        <!-- ============================================================== -->
                        <!-- create new -->
                        <!-- ============================================================== -->

                    </ul>
                    <!-- ============================================================== -->
                    <!-- Right side toggle and nav items -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav float-right">
                        <!-- ============================================================== -->
                        <!-- Search -->
                        <!-- ============================================================== -->

                        <!-- ============================================================== -->
                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="<?php echo BASE_URL; ?>assets/images/users/1.jpg" alt="user"
                                    class="rounded-circle" width="40">
                                <span class="ml-2 d-none d-lg-inline-block"><span>Hola,</span> <span
                                        class="text-dark"><?php echo $_SESSION['nombre_usuario']; ?></span> <i
                                        data-feather="chevron-down" class="svg-icon"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated flipInY">
 
                                <a class="dropdown-item" href="<?php echo BASE_URL . 'admin/salir'; ?>"><i
                                        data-feather="power" class="svg-icon mr-2 ml-1"></i>
                                    Cerrar Sesion</a>
                                  

                            </div>
                        </li>
                        <!-- ============================================================== -->
                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                    </ul>
                </div>
            </nav>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">

                        <!-- Dashboard -->
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="<?= BASE_URL ?>admin/home">
                                <i data-feather="home" class="feather-icon"></i>
                                <span class="hide-menu">Dashboard</span>
                            </a>
                        </li>

                        <!-- CATÁLOGOS -->
                        <li class="list-divider"></li>
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="folder"></i>
                                <span class="hide-menu">Catálogos</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                  <?php if ($_SESSION['rol_usuario'] == 1): ?>
                            <li class="sidebar-item"><a href="<?= BASE_URL ?>departamentos" class="sidebar-link"><i
                                            data-feather="grid"></i><span class="hide-menu">Departamentos</span></a>
                                </li>
                                 <?php endif; ?>
                                   <?php if ($_SESSION['rol_usuario'] == 1): ?>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>puestos" class="sidebar-link"><i
                                            data-feather="briefcase"></i><span class="hide-menu">Puestos</span></a></li>
                                <?php endif; ?>
                                   <?php if ($_SESSION['rol_usuario'] == 1): ?>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>roles" class="sidebar-link"><i
                                            data-feather="shield"></i><span class="hide-menu">Roles</span></a></li>
                                            <?php endif; ?>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>tipos_operacion"
                                        class="sidebar-link"><i data-feather="shuffle"></i><span class="hide-menu">Tipos
                                            de Operación</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>subtipoOperacion"
                                        class="sidebar-link"><i data-feather="refresh-cw"></i><span class="hide-menu">Tipos
                                            de SubOperación</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>movimiento_logistico"
                                        class="sidebar-link"><i data-feather="repeat"></i><span class="hide-menu">Tipos
                                            de Movimiento</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>tipos_eventos_logisticos"
                                        class="sidebar-link"><i data-feather="activity"></i><span
                                            class="hide-menu">Tipos de Evento Logístico</span></a></li>                               
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>Tipos_documentos"
                                        class="sidebar-link"><i data-feather="file-text"></i><span
                                            class="hide-menu">Tipos de Documento</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>estatus" class="sidebar-link"><i
                                            data-feather="tag"></i><span class="hide-menu">Estatus</span></a></li>
                                          <?php if ($_SESSION['rol_usuario'] == 1): ?>   
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>permisos" class="sidebar-link"><i
                                            data-feather="key"></i><span class="hide-menu">Permisos de
                                            Operación</span></a></li>
                                            <?php endif; ?>
                            </ul>
                        </li>

                        <!-- GEOGRAFÍA -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="globe"></i>
                                <span class="hide-menu">Geografía</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>estados" class="sidebar-link"><i
                                            data-feather="map"></i><span class="hide-menu">Estados</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>ciudades" class="sidebar-link"><i
                                            data-feather="map-pin"></i><span class="hide-menu">Ciudades</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>puertos" class="sidebar-link"><i
                                            data-feather="anchor"></i><span class="hide-menu">Puertos</span></a></li>
                            </ul>
                        </li>

                        <!-- CLIENTES Y USUARIOS -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="users"></i>
                                <span class="hide-menu">Clientes y Usuarios</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <?php if ($_SESSION['rol_usuario'] == 1): ?>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>usuarios" class="sidebar-link"><i
                                            data-feather="user"></i><span class="hide-menu">Usuarios</span></a></li>
                                            <?php endif; ?>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>clientes" class="sidebar-link"><i
                                            data-feather="users"></i><span class="hide-menu">Clientes</span></a></li>
                            </ul>
                        </li>

                        <!-- LOGÍSTICA -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="truck"></i>
                                <span class="hide-menu">Logística</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>bodegas" class="sidebar-link"><i
                                            data-feather="package"></i><span class="hide-menu">Bodegas</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>brokers" class="sidebar-link"><i
                                            data-feather="user-check"></i><span class="hide-menu">Brokers</span></a>
                                </li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>transportistas" class="sidebar-link"><i
                                            data-feather="truck"></i><span class="hide-menu">Transportistas</span></a>
                                </li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>navieras" class="sidebar-link"><i
                                            data-feather="navigation"></i><span class="hide-menu">Navieras</span></a>
                                </li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>shippers" class="sidebar-link"><i
                                            data-feather="send"></i><span class="hide-menu">Shippers</span></a></li>
                                            
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>forwarders" class="sidebar-link"><i
                                            data-feather="send"></i><span class="hide-menu">Forwarders</span></a></li>
                            </ul>
                        </li>

                        <!-- CONTENEDORES -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="box"></i>
                                <span class="hide-menu">Contenedores</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>contenedores_fisicos"
                                        class="sidebar-link"><i data-feather="box"></i><span
                                            class="hide-menu">Contenedores Físicos</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>contenedores_maritimos"
                                        class="sidebar-link"><i data-feather="package"></i><span
                                            class="hide-menu">Contenedores Marítimos</span></a></li>
                          <!--      <li class="sidebar-item"><a href="<?= BASE_URL ?>contenedores_en_operacion"
                                        class="sidebar-link"><i data-feather="layers"></i><span
                                            class="hide-menu">Contenedores en Operación</span></a></li> -->
                              
                               <!-- <li class="sidebar-item"><a href="<?= BASE_URL ?>trazabilidad" class="sidebar-link"><i
                                            data-feather="trending-up"></i><span
                                            class="hide-menu">Trazabilidad</span></a></li> -->
                            </ul>
                        </li>

                        <!-- OPERACIONES -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="settings"></i>
                                <span class="hide-menu">Operaciones</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item"><a href="<?= BASE_URL .'operaciones_maritimas/ver' ?>" class="sidebar-link"><i
                                            data-feather="anchor"></i><span class="hide-menu">
                                            Operaciones Maritimas</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>Operaciones_maritimo_ferro/ver"
                                        class="sidebar-link"><i data-feather="truck"></i><span
                                            class="hide-menu">Operaciones Maritimo-Ferro</span></a></li>
                               <!-- <li class="sidebar-item"><a href="<?= BASE_URL ?>operaciones/maritimo"
                                        class="sidebar-link"><i data-feather="navigation"></i><span
                                            class="hide-menu">Operaciones Marítimas</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>documentos_operacion"
                                        class="sidebar-link"><i data-feather="file-text"></i><span
                                            class="hide-menu">Documentos</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>operaciones_log"
                                        class="sidebar-link"><i data-feather="file"></i><span class="hide-menu">Log de
                                            Operaciones</span></a></li> -->
                            </ul>
                        </li>

                        <!-- FINANZAS Y SEGUIMIENTO 
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <i data-feather="dollar-sign"></i>
                                <span class="hide-menu">Finanzas y Seguimiento</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>costos_operacion"
                                        class="sidebar-link"><i data-feather="dollar-sign"></i><span
                                            class="hide-menu">Costos Operación</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>finanzas/costos_contenedor_operacion"
                                        class="sidebar-link"><i data-feather="credit-card"></i><span
                                            class="hide-menu">Costos por Contenedor</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>finanzas/costos_logisticos"
                                        class="sidebar-link"><i data-feather="trending-down"></i><span
                                            class="hide-menu">Costos Logísticos</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>eventos_logisticos"
                                        class="sidebar-link"><i data-feather="calendar"></i><span
                                            class="hide-menu">Eventos Logísticos</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>movimiento_logistico"
                                        class="sidebar-link"><i data-feather="repeat"></i><span
                                            class="hide-menu">Movimientos Logísticos</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>movimientos_contenedor"
                                        class="sidebar-link"><i data-feather="move"></i><span
                                            class="hide-menu">Movimientos Contenedor</span></a></li>
                                <li class="sidebar-item"><a href="<?= BASE_URL ?>detalles_logisticos"
                                        class="sidebar-link"><i data-feather="file-plus"></i><span
                                            class="hide-menu">Detalles Logísticos</span></a></li>
                            </ul>
                        </li>-->

                        <!-- AUDITORÍA -->
                         <?php if ($_SESSION['rol_usuario'] == 1): ?>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="<?= BASE_URL ?>bitacora">
                                <i data-feather="clipboard" class="feather-icon"></i>
                                <span class="hide-menu">Bitácora</span>
                            </a>
                        </li>
                        <?php endif; ?>

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">

            <!-- Bread crumb and right sidebar toggle -->

            <!-- <div class="page-breadcrumb">
                <div class="row">
                    <div class="col-7 align-self-center">
                        <h3 class="page-title text-truncate text-dark font-weight-medium mb-1">Bienvenido a nuestro
                            panel de administracion</h3>
                        <div class="d-flex align-items-center">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb m-0 p-0">
                                    <li class="breadcrumb-item"><a href="index.html">Dashboard</a>
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <div class="col-5 align-self-center">
                        <div class="customize-input float-right">
                            <select
                                class="custom-select custom-select-set form-control bg-white border-0 custom-shadow custom-radius">
                                <option selected>Aug 19</option>
                                <option value="1">July 19</option>
                                <option value="2">Jun 19</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>-->

            <!-- End Bread crumb and right sidebar toggle -->

            <!-- Script para animaciones -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Inicializar iconos de Feather
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                    // Animación de entrada escalonada
                    const sidebarItems = document.querySelectorAll('.sidebar-item');
                    sidebarItems.forEach((item, index) => {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(-20px)';
                        item.style.transition = 'all 0.3s ease';
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateX(0)';
                        }, index * 50);
                    });
                    // Animación hover para grupos
                    const groups = {
                        'group-catalogos': '#ffd700',
                        'group-geografia': '#32cd32',
                        'group-usuarios': '#ff6b6b',
                        'group-logistica': '#4ecdc4',
                        'group-contenedores': '#45b7d1',
                        'group-operaciones': '#f9ca24',
                        'group-finanzas': '#6c5ce7',
                        'group-auditoria': '#fd79a8'
                    };
                    Object.keys(groups).forEach(groupClass => {
                        const items = document.querySelectorAll('.' + groupClass);
                        items.forEach(item => {
                            const link = item.querySelector('.sidebar-link');
                            const icon = item.querySelector('.feather-icon');
                            if (link && icon) {
                                link.addEventListener('mouseenter', function() {
                                    icon.style.color = groups[groupClass];
                                    icon.style.transform = 'scale(1.1)';
                                    icon.style.transition = 'all 0.3s ease';
                                });
                                link.addEventListener('mouseleave', function() {
                                    icon.style.color = '';
                                    icon.style.transform = 'scale(1)';
                                });
                            }
                        });
                    });
                    // Efecto de ondas en los enlaces
                    const sidebarLinks = document.querySelectorAll('.sidebar-link');
                    sidebarLinks.forEach(link => {
                        link.addEventListener('mouseenter', function() {
                            this.style.transform = 'translateX(5px)';
                            this.style.transition = 'all 0.3s ease';
                        });
                        link.addEventListener('mouseleave', function() {
                            this.style.transform = 'translateX(0)';
                        });
                    });
                });
            </script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll(".sidebar-link");

        links.forEach(link => {
            const href = link.getAttribute("href");

            if (href && currentPath.includes(href)) {
                // Activar hijo
                link.classList.add("active-link"); // color especial para link actual
                const li = link.closest("li");
                if (li) li.classList.add("active-item");

                // Si es parte de submenú, activa el padre también
                const submenu = link.closest("ul.collapse");
                if (submenu) {
                    submenu.classList.add("in");
                    const parentLi = submenu.closest("li.sidebar-item");
                    if (parentLi) {
                        parentLi.classList.add("active-parent"); // padre resaltado diferente
                        const parentLink = parentLi.querySelector(".has-arrow");
                        if (parentLink) parentLink.setAttribute("aria-expanded", "true");
                    }
                }
            }
        });
    });
</script>

<!-- Chartist CSS -->
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chartist@0.11.4/dist/chartist.min.css"> -->
<link href="<?= BASE_URL ?>assets/js/modulosAdmin/librerias/chartist.min.css" rel="stylesheet">


<!-- Chartist JS -->
<!--<script src="https://cdn.jsdelivr.net/npm/chartist@0.11.4/dist/chartist.min.js"></script> -->
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/librerias/chartist.min.js"></script>

<!-- Luego tu dashboard -->
<script src="<?php echo BASE_URL; ?>/assets/libs/chartist/dist/chartist.min.js"></script>
 
 <script>
     const base_url = '<?php echo BASE_URL; ?>';
 </script>