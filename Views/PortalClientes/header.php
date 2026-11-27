<?php

// ==========================================
// PACIFICNORT SUITE - HEADER RESPONSIVE
// FASE 7.1
// ==========================================

// Detectar la ruta actual
$ruta = $_GET['url'] ?? '';

if (!is_string($ruta) || $ruta === '') {
    $ruta = parse_url(
        $_SERVER['REQUEST_URI'] ?? '',
        PHP_URL_PATH
    ) ?: '';
}

$ruta = trim($ruta, '/');

// Detectar módulo activo
$isOperaciones = preg_match(
    '~(?:^|/)PortalClientes(?:/index)?$~i',
    $ruta
) === 1;

$isPartidas = preg_match(
    '~(?:^|/)(?:PortalClientesPartidas(?:/index)?|PortalClientes/op_partidas)$~i',
    $ruta
) === 1;

// Datos del usuario
$nombreCliente = htmlspecialchars(
    $data['nombre_cliente'] ?? 'Cliente',
    ENT_QUOTES,
    'UTF-8'
);

$nombreUsuario = htmlspecialchars(
    $data['nombre_usuario'] ?? 'Usuario',
    ENT_QUOTES,
    'UTF-8'
);

?>

<!-- Dependencias existentes -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/feather-icons"></script>

<!-- Estilos del nuevo encabezado -->

<link
    rel="stylesheet"
    href="<?= BASE_URL ?>Assets/Css/PortalClientes/header.css">

<!-- ======================================
     HEADER PRINCIPAL
     ====================================== -->

<header id="pnClientHeader"
    class="pn-client-header">

    <div class="pn-client-header-inner">

        <!-- IDENTIDAD DEL SISTEMA -->

        <a href="<?= BASE_URL ?>PortalClientes/index"
            class="pn-client-brand">

            <div class="pn-client-brand-icon">
                <i data-feather="anchor"></i>
            </div>

            <div class="pn-client-brand-text">

                <span class="pn-client-brand-title">
                    PacificNort Suite
                </span>

                <span class="pn-client-brand-subtitle">
                    Portal Cliente
                </span>

            </div>

        </a>

        <!-- NAVEGACIÓN PRINCIPAL -->

        <nav
            id="pnClientNavigation"
            class="pn-client-nav collapse"
            aria-label="Navegación del portal">

            <div class="pn-client-nav-inner">

                <!-- Operaciones -->

                <a
                    href="<?= BASE_URL ?>PortalClientes/index"
                    class="pn-client-nav-link <?= $isOperaciones ? 'active' : '' ?>"
                    <?= $isOperaciones ? 'aria-current="page"' : '' ?>>

                    <i data-feather="package"></i>

                    <span>Operaciones</span>

                </a>

                <!-- Operaciones por partida -->

                <a
                    href="<?= BASE_URL ?>PortalClientesPartidas/index"
                    class="pn-client-nav-link <?= $isPartidas ? 'active' : '' ?>"
                    <?= $isPartidas ? 'aria-current="page"' : '' ?>>

                    <i data-feather="navigation"></i>

                    <span>Operaciones por Partida</span>

                </a>

            </div>

        </nav>


        <!-- PERFIL DEL CLIENTE -->

        <details class="pn-client-account" id="pnClientAccount">

            <summary
                id="pnClientAccountButton"
                class="pn-client-account-button"
                aria-label="Mi cuenta">

                <span class="pn-client-avatar">
                    <i data-feather="user"></i>
                </span>

                <span class="pn-client-account-text">

                    <span class="pn-client-account-name">
                        <?= $nombreCliente ?>
                    </span>

                    <span class="pn-client-account-caption">
                        Mi cuenta
                    </span>

                </span>

                <i data-feather="chevron-down"
                    class="pn-client-account-chevron">
                </i>

            </summary>

            <!-- Contenido del perfil -->

            <div class="pn-client-account-menu">

                <div class="pn-client-profile-info">

                    <div class="pn-client-profile-avatar">
                        <i data-feather="user"></i>
                    </div>

                    <span class="pn-client-profile-label">
                        Cliente
                    </span>

                    <strong>
                        <?= $nombreCliente ?>
                    </strong>

                    <div class="pn-client-profile-user">

                        <i data-feather="user-check"></i>

                        <span>
                            <?= $nombreUsuario ?>
                        </span>

                    </div>

                </div>

                <hr class="dropdown-divider">

                <!-- Cerrar sesión -->

                <a
                    href="<?= BASE_URL ?>admin/salir"
                    class="pn-client-logout">

                    <i data-feather="log-out"></i>

                    <span>Cerrar sesión</span>

                </a>

            </div>

        </details>


        <!-- BOTÓN DEL MENÚ MÓVIL -->

        <button
            type="button"
            id="pnClientMenuButton"
            class="pn-client-menu-button"
            data-bs-toggle="collapse"
            data-bs-target="#pnClientNavigation"
            aria-controls="pnClientNavigation"
            aria-expanded="false"
            aria-label="Mostrar u ocultar navegación">

            <span class="pn-client-menu-open">
                <i data-feather="menu"></i>
            </span>

            <span class="pn-client-menu-close">
                <i data-feather="x"></i>
            </span>

        </button>

    </div>

</header>


<script>
    document.addEventListener("DOMContentLoaded", function() {

        const header = document.getElementById("pnClientHeader");

        if (!header) return;

        const navigation = document.getElementById("pnClientNavigation");
        const account = document.getElementById("pnClientAccount");
        const accountButton = document.getElementById("pnClientAccountButton");
        const menuButton = document.getElementById("pnClientMenuButton");

        if (!navigation || !account || !accountButton || !menuButton) {
            return;
        }

        const mobileQuery = window.matchMedia(
            "(max-width: 991.98px)"
        );

        // Menú de navegación Bootstrap

        const navigationCollapse =
            typeof bootstrap !== "undefined" ?
            bootstrap.Collapse.getOrCreateInstance(
                navigation, {
                    toggle: false
                }
            ) :
            null;

        // Cerrar perfil al abrir navegación

        navigation.addEventListener(
            "show.bs.collapse",
            function() {
                account.open = false;
            }
        );

        // Cerrar navegación al abrir perfil

        account.addEventListener("toggle", function() {

            if (account.open && mobileQuery.matches) {

                navigationCollapse?.hide();

            }

        });

        // Cerrar perfil al pulsar fuera

        document.addEventListener("click", function(event) {

            if (account.open && !account.contains(event.target)) {

                account.open = false;

            }

        });

        // Cerrar menús con Escape

        header.addEventListener("keydown", function(event) {

            if (event.key !== "Escape") return;

            if (account.open) {

                account.open = false;
                accountButton.focus();

            } else if (
                mobileQuery.matches &&
                navigation.classList.contains("show")
            ) {

                navigationCollapse?.hide();
                menuButton.focus();

            }

        });

        // Restablecer menús al cambiar de resolución

        mobileQuery.addEventListener("change", function() {

            account.open = false;

            navigationCollapse?.hide();

        });

        // Inicializar iconos

        if (typeof feather !== "undefined") {

            feather.replace();

        }

    });
</script>