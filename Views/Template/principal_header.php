<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asesoría y Logística Internacional Pacificnort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/aos.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora&family=Montserrat:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<nav>
    <div class="wrapper">
        <div class="logo"><a href="<?php echo BASE_URL; ?>"><img class="img-fluid"
                    src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="logo"></a></div>
        <input type="radio" name="slider" id="menu-btn">
        <input type="radio" name="slider" id="close-btn">
        <ul class="nav-links">
            <label for="close-btn" class="btn close-btn"><i class="fas fa-times"></i></label>
            <li><a href="#">Inicio</a></li>
            <li><a href="#nosotros">Acerca de Nosotros</a></li>
            <li>
                <a href="#servicios" class="desktop-item"><i class="fas fa-truck"></i> Servicios</a>
                <input type="checkbox" id="showDrop">
                <label for="showDrop" class="mobile-item">Servicios</label>
                <ul class="drop-menu">
    <!-- <li><a href="#">Transporte Internacional</a></li>
          <li><a href="#">Gestiones Aduanales</a></li>
          <li><a href="#">Consultoría Empresarial</a></li>
          <li><a href="#">Almacenaje y Distribución</a></li>
          <li><a href="#">Seguros de Carga</a></li> !-->
                </ul>

            <li><a href="<?php echo BASE_URL.'admin'; ?>">Iniciar Sesión</a></li>
        </ul>
        <label for="menu-btn" class="btn menu-btn" id="menu-btn"><i class="fas fa-bars fa-2x"></i></label>
    </div>
</nav>