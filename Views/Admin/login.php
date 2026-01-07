<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="<?php echo BASE_URL; ?>Assets/plugins/simplebar/Css/simplebar.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>Assets/plugins/metismenu/Css/metisMenu.min.css" rel="stylesheet" />
    <!-- loader-->
    <link href="<?php echo BASE_URL; ?>Assets/Css/pace.min.css" rel="stylesheet" />
    <script src="<?php echo BASE_URL; ?>Assets/Js/pace.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="<?php echo BASE_URL; ?>Assets/Css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>Assets/Css/bootstrap-extended.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>Assets/Css/app.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>Assets/Css/icons.css" rel="stylesheet">
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
 
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo BASE_URL; ?>/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo BASE_URL; ?>/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <title><?php echo $data['title']; ?></title>    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/dist/css/style.min.css" rel="stylesheet">
 
</head>

<body>
    <div class="main-wrapper">
        <!-- ============================================================== -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- ============================================================== -->
        <div class="preloader">
            <div class="lds-ripple">
                <div class="lds-pos"></div>
                <div class="lds-pos"></div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative"
            style="background:url(<?php echo BASE_URL; ?>/Assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row">
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(<?php echo BASE_URL; ?>/Assets/img/log4.jpg);">
                </div>
                <div class="col-lg-5 col-md-7 bg-white">
                    <div class="p-3">
                        <div class="text-center">
                            <img src="<?php echo BASE_URL; ?>/Assets/images/big/icon.png" alt="wrapkit">
                        </div>
                        <h2 class="mt-3 text-center">Iniciar Sesion</h2>
                         <div class="text-center">
                                    <?php if (!empty($_SESSION['msg_error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?= $_SESSION['msg_error'] ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['msg_error']); ?>
                                    <?php endif; ?>
                         </div>
                        <p class="text-center">Ingresa tu correo y contraseña para acceder al panel de administracion.</p>
                        <form class="mt-4" id="formulario" name="formulario">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="uname">Correo Electronico</label>
                                        <input class="form-control" id="email" name="email" type="text"
                                            placeholder="Correo Electronico">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="pwd">Contraseña</label>
                                        <input class="form-control" id="clave" name="clave" type="password"
                                            placeholder="Contraseña">
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button type="submit" class="btn btn-block btn-dark">Iniciar Sesion</button>
                                </div>
                                <div class="col-lg-12 text-center mt-5">
                                  ¿Aun no tienes cuenta? <a href="<?php echo BASE_URL.'admin/registro'; ?>" class="text-danger">Registrarse</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
 
        <!-- Login box.scss -->
 
    </div>
 
    <!-- All Required js -->
 
    <script src="<?php echo BASE_URL; ?>/Assets/libs/jquery/dist/jquery.min.js "></script>
       <!-- Bootstrap JS -->
    <script src="<?php echo BASE_URL; ?>Assets/Js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="<?php echo BASE_URL; ?>Assets/Js/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Assets/plugins/simplebar/Js/simplebar.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Assets/plugins/metismenu/Js/metisMenu.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="<?php echo BASE_URL; ?>Assets/libs/popper.js/dist/umd/popper.min.js "></script>
    <script src="<?php echo BASE_URL; ?>Assets/libs/bootstrap/dist/js/bootstrap.min.js "></script>
    <!-- ============================================================== -->
    <!-- This page plugin js -->
    <!-- ============================================================== -->

    
    <script>
        $(".preloader ").fadeOut();
    </script>
</body>
   <script>
        const base_url = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>Assets/Js/sweetalert2.all.min.js"></script>
        <script src="<?php echo BASE_URL; ?>Assets/Js/login.js"></script>
</html>