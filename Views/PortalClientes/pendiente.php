<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PacificNort Suite | Portal Cliente</title>

    <!-- Bootstrap 5.3.x -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f6f7fb;
        }

        .pn-topnav {
            background: #0f172a;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(226, 232, 240, .12);
        }

        .pn-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #e2e8f0;
            text-decoration: none;
        }

        .pn-brand .kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: .9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(226, 232, 240, .12);
            background: rgba(148, 163, 184, .10);
            color: #38bdf8;
        }

        .pn-muted {
            color: #64748b;
        }

        .pn-muted-inv {
            color: rgba(226, 232, 240, .75);
        }

        .pn-topbar {
            background: #ffffff;
            border-bottom: 1px solid rgba(15, 23, 42, .08);
        }

        .kpi-card {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
            background: #fff;
        }

        .kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: .9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(15, 23, 42, .08);
            background: #f8fafc;
        }

        .wait-card {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1.25rem;
            background: #fff;
        }

        .wait-hero {
            width: 64px;
            height: 64px;
            border-radius: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(15, 23, 42, .10);
            background: rgba(2, 132, 199, .08);
            color: #075985;
        }

        .search-hint {
            font-size: .9rem;
            color: #64748b;
        }
    </style>
</head>

<body>

    <!-- NAV SUPERIOR -->
    <header class="pn-topnav" id="pnTopnav">
        <div class="container-fluid py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

                <!-- Brand -->
                <a class="pn-brand" href="#" id="pnBrand" onclick="return false;">
                    <span class="kpi-icon"><i data-feather="anchor"></i></span>
                    <span>
                        <span class="fw-bold d-block" style="line-height:1.05;">PacificNort Suite</span>
                        <span class="small pn-muted-inv">Portal Cliente</span>
                    </span>
                </a>

                <!-- Usuario + Cerrar sesión -->
                <div class="d-flex flex-wrap align-items-center gap-2" id="pnUserActions">
                    <div class="text-end me-1" id="pnUserInfo">
                        <div class="fw-semibold" style="line-height:1.1;" id="lblClienteTop">
                            Cliente: <span class="pn-muted-inv">Pendiente</span>
                        </div>
                        <div class="small pn-muted-inv" id="lblUsuarioTop">
                            Usuario: <?php echo $data['nombre_usuario'] ?? ''; ?>
                        </div>
                    </div>

                    <!-- ✅ IMPORTANTE: apunta a tu controlador PortalClientes/salir -->
                    <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>PortalClientes/salir">
                        <i data-feather="log-out" class="me-1"></i> Cerrar sesión
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- CONTENIDO -->
    <main class="pn-content" id="pnContent">

        <!-- Topbar blanca -->
        <div class="pn-topbar" id="pnTopbar">
            <div class="container-fluid py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-0" id="pageTitle">Cuenta en espera</h4>
                        <div class="search-hint" id="pageHint">
                            Tu usuario ya fue registrado. Falta que un administrador lo vincule a un cliente.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid py-4" id="pnContainer">

            <div class="row justify-content-center">
                <div class="col-12 col-lg-7 col-xl-6">

                    <div class="wait-card shadow-sm p-4 p-md-5">

                        <div class="d-flex align-items-start gap-3">
                            <div class="wait-hero">
                                <i data-feather="clock"></i>
                            </div>

                            <div class="flex-grow-1">
                                <h5 class="mb-1">Tu cuenta está pendiente de vinculación</h5>
                                <div class="pn-muted">
                                    Para acceder al Portal Cliente, un administrador debe vincular tu usuario con un cliente registrado en el sistema.
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-start gap-2 mb-0" role="alert">
                                    <i data-feather="info" class="mt-1"></i>
                                    <div>
                                        <div class="fw-semibold">¿Qué puedes hacer mientras tanto?</div>
                                        <div class="small mb-0">
                                            Si ya solicitaste el acceso, solo espera a que el administrador complete el vínculo.
                                            Puedes cerrar sesión y volver a iniciar más tarde.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card border-0 rounded-4" style="background:#f8fafc; border:1px solid rgba(15,23,42,.08) !important;">
                                    <div class="card-body">
                                        <div class="fw-semibold mb-2">Datos de tu sesión</div>

                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge rounded-pill text-bg-light border">
                                                <span class="pn-muted">Usuario:</span>
                                                <span class="fw-semibold ms-1"><?php echo $data['nombre_usuario'] ?? '-'; ?></span>
                                            </span>

                                            <span class="badge rounded-pill text-bg-light border">
                                                <span class="pn-muted">Estado:</span>
                                                <span class="fw-semibold ms-1">Pendiente</span>
                                            </span>

                                            <span class="badge rounded-pill text-bg-light border">
                                                <span class="pn-muted">Acceso:</span>
                                                <span class="fw-semibold ms-1">Restringido</span>
                                            </span>
                                        </div>

                                        <div class="small pn-muted mt-3">
                                            Cuando el vínculo esté listo, al iniciar sesión verás tus operaciones y documentos.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-grid d-md-flex gap-2 justify-content-end mt-2">
                                <!-- Puedes dejar este botón como "refrescar" simple -->
                                <button class="btn btn-outline-secondary" type="button" id="btnRevisarEstado">
                                    <i data-feather="refresh-cw" class="me-1"></i> Revisar estado
                                </button>

                                <!-- ✅ Cerrar sesión al controlador correcto -->
                                <a class="btn btn-dark" href="<?php echo BASE_URL; ?>PortalClientes/salir">
                                    <i data-feather="log-out" class="me-1"></i> Cerrar sesión
                                </a>
                            </div>
                        </div>

                    </div><!-- /wait-card -->

                </div>
            </div>

        </div>
    </main>

    <!-- Bootstrap + Feather -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/sweetalert2.all.min.js"></script>


    <script>
        window.BASE_URL = "<?php echo BASE_URL; ?>";
    </script>



    <script>
        feather.replace();

        // ===== Config =====
        const ENDPOINT_ESTADO = window.BASE_URL + 'PortalClientes/verificarEstado';

        // Evita spamear requests (click + polling)
        let checking = false;

        function verificarEstado(silencioso = false) {
            if (checking) return;
            checking = true;

            const xhr = new XMLHttpRequest();
            xhr.open('GET', ENDPOINT_ESTADO, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = function() {
                if (xhr.readyState !== 4) return;

                checking = false;

                // Si el server redirigió/mandó HTML por whitelist mal configurada,
                // esto te ayuda a detectarlo rápido.
                const raw = (xhr.responseText || '').trim();

                if (xhr.status !== 200) {
                    if (!silencioso) mostrarError();
                    return;
                }

                // Intentar parsear JSON
                let data = null;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    // 🔥 Casi siempre pasa cuando el endpoint regresa HTML (pendiente) por bloqueo del __construct
                    if (!silencioso) {
                        Swal.fire({
                            title: 'No se pudo validar el estado',
                            html: 'El servidor no devolvió JSON. <br><small class="text-muted">Tip: revisa que <b>PortalClientes/verificarEstado</b> esté en whitelist del __construct().</small>',
                            icon: 'warning',
                            showConfirmButton: true,
                            confirmButtonText: 'Recargar',
                            showCancelButton: true,
                            cancelButtonText: 'Cerrar',
                            confirmButtonColor: '#0f172a',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) window.location.reload();
                        });
                    }
                    return;
                }

                // Validación defensiva
                if (data && data.vinculado === true) {
                    // 🚀 Ya está vinculado → al portal
                    window.location.href = window.BASE_URL + 'PortalClientes';
                    return;
                }

                // Sigue pendiente
                if (!silencioso) {
                    Swal.fire({
                        title: 'Aún pendiente',
                        text: 'Tu cuenta todavía no ha sido vinculada a un cliente.',
                        icon: 'info',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#0f172a'
                    });
                }
            };

            xhr.onerror = function() {
                checking = false;
                if (!silencioso) mostrarError();
            };

            xhr.send();
        }

        function mostrarError() {
            Swal.fire({
                title: 'Estado pendiente de vinculación',
                text: 'No se pudo verificar el estado. Intenta recargar la página.',
                icon: 'warning',
                showConfirmButton: true,
                confirmButtonText: 'Recargar',
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#0f172a',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) window.location.reload();
            });
        }

        // Botón manual
        const btn = document.getElementById('btnRevisarEstado');
        if (btn) {
            btn.addEventListener('click', function() {
                verificarEstado(false);
            });
        }

        // Polling automático cada 30s (silencioso)
        setInterval(function() {
            verificarEstado(true);
        }, 30000);
    </script>


</body>

</html>