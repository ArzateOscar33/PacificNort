<?php
$current = $_SERVER['REQUEST_URI'] ?? '';
$isOperaciones = (strpos($current, 'PortalClientes/index') !== false);
$isPartidas    = (strpos($current, 'PortalClientesPartidas/index') !== false);
?>
<!-- BOOTSTRAP / FEATHER -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>

<style>
    .pn-topnav {
        background: linear-gradient(135deg, #0f172a 0%, #162033 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        position: sticky;
        top: 0;
        z-index: 1030;
    }

    .pn-topnav-inner {
        min-height: 82px;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    /* =========================
   BRAND
========================= */
    .pn-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fff;
    }

    .pn-brand:hover {
        color: #fff;
        opacity: 0.96;
    }

    .pn-brand-badge {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(14, 165, 233, 0.25);
        flex-shrink: 0;
    }

    .pn-brand-badge i {
        width: 22px;
        height: 22px;
        color: #fff;
    }

    .pn-brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
    }

    .pn-brand-title {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .pn-brand-subtitle {
        font-size: 0.78rem;
        color: rgba(255, 255, 255, 0.72);
        margin-top: 2px;
    }

    /* =========================
   NAV
========================= */
    .pn-nav {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 6px;
    }

    .pn-nav-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 12px;
        color: rgba(255, 255, 255, 0.82);
        text-decoration: none;
        font-size: 0.94rem;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .pn-nav-link i {
        width: 17px;
        height: 17px;
    }

    .pn-nav-link:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
        transform: translateY(-1px);
    }

    .pn-nav-link.active {
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
        color: #fff;
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.22);
    }

    /* =========================
   USER CARD
========================= */
    .pn-user-card {
        min-width: 230px;
        padding: 10px 14px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #fff;
        line-height: 1.15;
    }

    .pn-user-card-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: rgba(255, 255, 255, 0.58);
        margin-bottom: 3px;
    }

    .pn-user-card-value {
        font-size: 0.96rem;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .pn-user-card-sub {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.74);
    }

    /* =========================
   LOGOUT BUTTON
========================= */
    .pn-btn-logout {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 18px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(220, 38, 38, 0.14);
        color: #fff;
        font-weight: 600;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .pn-btn-logout:hover {
        background: rgba(220, 38, 38, 0.22);
        border-color: rgba(255, 255, 255, 0.22);
        color: #fff;
        transform: translateY(-1px);
    }

    .pn-btn-logout i {
        width: 17px;
        height: 17px;
    }

    /* =========================
   RESPONSIVE
========================= */
    @media (max-width: 991.98px) {
        .pn-topnav-inner {
            min-height: auto;
        }

        .pn-user-card {
            min-width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .pn-brand-title {
            font-size: 0.95rem;
        }

        .pn-nav {
            width: 100%;
        }

        .pn-nav-link {
            justify-content: center;
            flex: 1 1 auto;
        }
    }
</style>
<header class="pn-topnav shadow-sm">
    <div class="container-fluid px-4 px-lg-5">
        <div class="pn-topnav-inner d-flex align-items-center justify-content-between flex-wrap gap-3">

            <!-- IZQUIERDA -->
            <div class="d-flex align-items-center gap-4 flex-wrap">

                <!-- Brand -->
                <a href="<?php echo BASE_URL; ?>PortalClientes/index" class="pn-brand text-decoration-none">
                    <div class="pn-brand-badge">
                        <i data-feather="anchor"></i>
                    </div>
                    <div class="pn-brand-text">
                        <span class="pn-brand-title">PacificNort Suite</span>
                        <span class="pn-brand-subtitle">Portal Cliente</span>
                    </div>
                </a>

                <!-- Navegación -->
                <nav class="pn-nav d-flex align-items-center gap-2 flex-wrap">
                    <a class="pn-nav-link <?php echo $isOperaciones ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>PortalClientes/index">
                        <i data-feather="package"></i>
                        <span>Operaciones</span>
                    </a>

                    <a class="pn-nav-link <?php echo $isPartidas ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>PortalClientesPartidas/index">
                        <i data-feather="navigation"></i>
                        <span>Operaciones por Partida</span>
                    </a>
                </nav>
            </div>

            <!-- DERECHA -->
            <div class="d-flex align-items-center gap-3 flex-wrap ms-auto">

                <div class="pn-user-card">
                    <div class="pn-user-card-label">Cliente</div>
                    <div class="pn-user-card-value">
                        <?php echo htmlspecialchars($data['nombre_cliente'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="pn-user-card-sub">
                        Usuario: <?php echo htmlspecialchars($data['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

                <a class="btn pn-btn-logout"
                    href="<?php echo BASE_URL; ?>admin/salir">
                    <i data-feather="log-out" class="me-2"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>

        </div>
    </div>
</header>