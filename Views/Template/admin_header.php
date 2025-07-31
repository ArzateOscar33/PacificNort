<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo BASE_URL; ?>/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo BASE_URL; ?>/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo BASE_URL; ?>/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo BASE_URL; ?>/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo BASE_URL; ?>/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo BASE_URL; ?>/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo BASE_URL; ?>/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo BASE_URL; ?>/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo BASE_URL; ?>/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/favicon/favicon-16x16.png">
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
    <link href="<?php echo BASE_URL; ?>/dist/css/style.min.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

    <!--<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>-->
    <!--<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>-->

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
                                <a class="dropdown-item" href="javascript:void(0)"><i data-feather="user"
                                        class="svg-icon mr-2 ml-1"></i>
                                    Mi Perfil</a>

                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL . 'admin/salir'; ?>"><i
                                        data-feather="power" class="svg-icon mr-2 ml-1"></i>
                                    Cerrar Sesion</a>
                                <div class="dropdown-divider"></div>

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
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <!-- Sidebar navigation-->
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="sidebar-item"> <a class="sidebar-link sidebar-link"
                                href="<?php echo BASE_URL . 'admin/home'; ?>" aria-expanded="false"><i
                                    data-feather="home" class="feather-icon"></i><span
                                    class="hide-menu">Dashboard</span></a></li>
                        <li class="list-divider"></li>
                        <li class="nav-small-cap"><span class="hide-menu">Catalogos</span></li>

                        <!-- CATÁLOGOS -->
                        <li class="sidebar-item"><a class="sidebar-link"
                                href="<?php echo BASE_URL . 'departamentos'; ?>"><i data-feather="layers"></i><span
                                    class="hide-menu">Departamentos</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'puestos'; ?>"><i
                                    data-feather="user-check"></i><span class="hide-menu">Puestos</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'roles'; ?>"><i
                                    data-feather="shield"></i><span class="hide-menu">Roles</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href="<?php echo BASE_URL . 'tipos_operacion'; ?> "><i data-feather="shuffle"></i><span
                                    class="hide-menu">Tipos de Operación</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'Estatus'; ?> "><i
                                    data-feather="tag"></i><span class="hide-menu">Estatus</span></a></li>

                        <li class="nav-small-cap"><span class="hide-menu">Geografía</span></li>

                        <!-- UBICACIÓN -->
                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'Estados'; ?> "><i
                                    data-feather="map"></i><span class="hide-menu">Estados</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'Ciudades'; ?>"><i
                                    data-feather="map-pin"></i><span class="hide-menu">Ciudades</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'Puertos'; ?> "><i
                                    data-feather="anchor"></i><span class="hide-menu">Puertos</span></a></li>

                        <li class="nav-small-cap"><span class="hide-menu">Clientes y Usuarios</span></li>

                        <!-- USUARIOS Y CLIENTES -->
                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'Usuarios'; ?>"><i
                                    data-feather="users"></i><span class="hide-menu">Usuarios</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'Clientes'; ?> "><i
                                    data-feather="briefcase"></i><span class="hide-menu">Clientes</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'Shippers'; ?>"><i
                                    data-feather="truck"></i><span class="hide-menu">Shippers</span></a></li>

                        <li class="nav-small-cap"><span class="hide-menu">Logística</span></li>

                        <!-- LOGÍSTICA -->
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'Bodegas'; ?>"><i
                                    data-feather="home"></i><span class="hide-menu">Bodegas</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'Brokers'; ?>"><i
                                    data-feather="briefcase"></i><span class="hide-menu">Brokers</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href=" <?php echo BASE_URL . 'Transportistas'; ?>"><i data-feather="truck"></i><span
                                    class="hide-menu">Transportistas</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'Navieras'; ?> "><i
                                    data-feather="anchor"></i><span class="hide-menu">Navieras</span></a></li>
                        <li class="nav-small-cap"><span class="hide-menu">Contenedores</span></li>

                        <!-- CONTENEDORES -->
                        <li class="sidebar-item"><a class="sidebar-link"
                                href=" <?php echo BASE_URL . 'contenedores_fisicos'; ?>"><i
                                    data-feather="package"></i><span class="hide-menu">Ferros / Físicos</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href=" <?php echo BASE_URL . 'contenedores_maritimos'; ?>"><i
                                    data-feather="box"></i><span class="hide-menu">Marítimos</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="#?>"><i data-feather="box"></i><span
                                    class="hide-menu">En Operacion</span></a></li>
                        <li class="nav-small-cap"><span class="hide-menu">Operaciones</span></li>

                        <!-- OPERACIONES -->
                        <li class="sidebar-item"><a class="sidebar-link"
                                href=" <?php echo BASE_URL . 'operaciones'; ?>"><i data-feather="file-text"></i><span
                                    class="hide-menu">Crear Operación</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href="<?php echo BASE_URL . 'operaciones/terrestre'; ?>"><i
                                    data-feather="file-text"></i><span class="hide-menu">Terrestre</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href="<?php echo BASE_URL . 'operaciones/maritimo'; ?>"><i
                                    data-feather="file-text"></i><span class="hide-menu">Marítimo</span></a></li>
 
                        <li class="sidebar-item"><a class="sidebar-link" href="# "><i data-feather="file-plus"></i><span
                                    class="hide-menu">Documentos</span></a></li>

                        <li class="nav-small-cap"><span class="hide-menu">Finanzas y Seguimiento</span></li>

                        <!-- FINANZAS -->
                        <li class="sidebar-item"><a class="sidebar-link" href="<?php echo BASE_URL . 'finanzas'; ?> "><i
                                    data-feather="dollar-sign"></i><span class="hide-menu">Costos Operación</span></a>
                        </li>
                        <li class="sidebar-item"><a class="sidebar-link" href=" #"><i
                                    data-feather="dollar-sign"></i><span class="hide-menu">Costos por
                                    Contenedor</span></a></li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href="<?php echo BASE_URL . 'movimientos_financieros'; ?> "><i
                                    data-feather="bar-chart-2"></i><span class="hide-menu">Movimientos
                                    Financieros</span></a></li>
                        <li class="nav-small-cap"><span class="hide-menu">Rastreo</span></li>

                        <!-- MOVIMIENTOS Y TRAZABILIDAD -->
                        <li class="sidebar-item"><a class="sidebar-link"
                                href="<?php echo BASE_URL . 'movimiento_logistico'; ?>"><i
                                    data-feather="repeat"></i><span class="hide-menu">Movimientos Logísticos</span></a>
                        </li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href=" <?php echo BASE_URL . 'eventos_logisticos'; ?>"><i
                                    data-feather="activity"></i><span class="hide-menu">Eventos Logísticos</span></a>
                        </li>
                        <li class="sidebar-item"><a class="sidebar-link"
                                href=" <?php echo BASE_URL . 'trazabilidad'; ?>"><i data-feather="map"></i><span
                                    class="hide-menu">Trazabilidad</span></a></li>

                        <li class="nav-small-cap"><span class="hide-menu">Auditoría</span></li>

                        <li class="sidebar-item"><a class="sidebar-link" href=" <?php echo BASE_URL . 'bitacora'; ?>"><i
                                    data-feather="save"></i><span class="hide-menu">Bitácora</span></a></li>

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