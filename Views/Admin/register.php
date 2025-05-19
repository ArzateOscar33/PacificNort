<!DOCTYPE html>
<html dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/assets/images/favicon.png">
    <title><?php echo $data['title']; ?></title>
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/dist/css/style.min.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
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
            style="background:url(<?php echo BASE_URL; ?>/assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row text-center">
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(<?php echo BASE_URL; ?>/assets/img/log5.jpg);">
                </div>
                <div class="col-lg-5 col-md-7 bg-white">
                    <div class="p-3">
                        <img src="<?php echo BASE_URL; ?>/assets/images/big/icon.png" alt="wrapkit">
                        <h2 class="mt-3 text-center">Registrate Gratis</h2>
                        <form class="mt-4" id="frmRegistro" name="frmRegistro">
                            <div class="row">
                                
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" type="text" placeholder="Nombre" id="nombre" name="nombre">
                                    </div>
                                </div>
                                  <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" type="text" placeholder="Apellido" id="apellido" name="apellido">
                                    </div>
                                </div>
                                 <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" type="email" placeholder="Correo Electronico" id="correo" name="correo">
                                    </div>
                                </div>
                
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" type="password" placeholder="Contraseña" name="clave" id="clave">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" type="text" placeholder="664 XXX XXXX" id="phone" name="phone">
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button type="submit" class="btn btn-block btn-dark">Registrarse</button>
                                </div>
                                <div class="col-lg-12 text-center mt-5">
                                    ¿Ya tienes una cuenta con nosotros? <a href="<?php echo BASE_URL.'admin'; ?>" class="text-danger">Iniciar Sesion</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div> 
    </div> 
    <script src="<?php echo BASE_URL; ?>/assets/libs/jquery/dist/jquery.min.js "></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/libs/popper.js/dist/umd/popper.min.js "></script>
    <script src="<?php echo BASE_URL; ?>/assets/libs/bootstrap/dist/js/bootstrap.min.js "></script>
    <!-- ============================================================== -->
    <!-- This page plugin js -->
    <!-- ============================================================== -->
    <script>
        $(".preloader ").fadeOut();
    </script>
       <script>
        const base_url = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/sweetalert2.all.min.js"></script>
        <script src="<?php echo BASE_URL; ?>assets/js/registro.js"></script>
</body>

</html>