<?php // MF.02.5: perfil ligero solo en esta vista.
$pnMFCompactAssets = true;
?>
<!DOCTYPE html>
<html lang="es">
<?php include 'Views/Template/admin_header.php'; ?>

<!-- MF.03.1 R1: perfil movil local, sin alterar la plantilla compartida -->
<script>
    document.getElementById('main-wrapper')?.classList.add('pn-mf-mobile-shell');
</script>
<div class="container mt-4 col-md-12" id="mf-module-root" role="main" aria-label="Operaciones marítimo-ferroviarias" tabindex="-1">


    <!-- MF.03.1: selector móvil y navegación Bootstrap existente -->
    <div id="mf-module-nav">
        <div id="mf-mobile-nav" hidden>
            <label for="mf-section-select">Sección del módulo</label>
            <select id="mf-section-select" class="form-control"></select>
        </div>
    <ul class="nav nav-tabs" id="operacionTabs" role="tablist" aria-label="Secciones del módulo" aria-orientation="horizontal">
        <li class="nav-item">
            <a class="nav-link " id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab" aria-controls="resumen" aria-selected="false"><i data-feather="zap"></i>Resumen</a>
        </li>
        <!--  MARITIMA -->
        <li class="nav-item">
            <a class="nav-link active" id="crear_operacions-tab" data-bs-toggle="tab" href="#crear_operacions" role="tab" aria-controls="crear_operacions" aria-selected="true"><i data-feather="anchor"></i>Operaciones</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="Eventos_Logisticos-tab" data-bs-toggle="tab" href="#Eventos_Logisticos" role="tab" aria-controls="Eventos_Logisticos" aria-selected="false"><i data-feather="calendar"></i>Eventos Marítimos</a>
        </li>
        <!-- FIN MARITMA -->

        <!-- FERRO/TERRESTRE -->
        <li class="nav-item">
            <a class="nav-link" id="en-piso-tab" data-bs-toggle="tab" href="#en-piso" role="tab" aria-controls="en-piso" aria-selected="false"><i data-feather="home"></i>En Bodega</a>
        </li>

        <li class="nav-item">
            <a class="nav-link" id="Eventos_Logisticos_ferro-tab" data-bs-toggle="tab" href="#Eventos_Logisticos_ferro" role="tab" aria-controls="Eventos_Logisticos_ferro" aria-selected="false"><i data-feather="calendar"></i>Eventos Terrestres</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="trazabilidad-tab" data-bs-toggle="tab" href="#trazabilidad" role="tab" aria-controls="trazabilidad" aria-selected="false">
                <i data-feather="shuffle"></i> Trazabilidad
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" id="documentos-tab" data-bs-toggle="tab" href="#documentos" role="tab" aria-controls="documentos" aria-selected="false"><i data-feather="file-plus"></i>Documentos</a>
        </li>




        <!-- FIN FERRO/TERRESTRE -->
        <li class="nav-item">
            <a class="nav-link" id="costos-operaciones-tab" data-bs-toggle="tab" href="#costos_operacion" role="tab" aria-controls="costos_operacion" aria-selected="false"><i data-feather="dollar-sign"></i>Costos </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" id="costos-operaciones-clientes-tab" data-bs-toggle="tab" href="#costos_operacion_clientes" role="tab" aria-controls="costos_operacion_clientes" aria-selected="false"><i data-feather="dollar-sign"></i>Costos Totales</a>
        </li>
    </ul>
    </div>

    <!-- MF.02.2 R2: URL base independiente de los paneles ocultos -->
    <script>
        window.BASE_URL = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <!-- MF.02.2: ciclo de vida de pestanas -->
    <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-tab-lifecycle.js?v=mf022"></script>
    <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-export-loader.js?v=mf023"></script>
    <style>
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        /* MF.03.1 R1: el modo full de la plantilla no debe reservar 260px en móviles. */
        #mf-sidebar-backdrop { display: none; }
        @media (max-width: 767.98px) {
            #main-wrapper.pn-mf-mobile-shell .page-wrapper {
                margin-left: 0 !important;
                width: 100%; min-width: 0; max-width: 100%;
            }
            #main-wrapper.pn-mf-mobile-shell .topbar .top-navbar .navbar-header {
                width: 100% !important; max-width: 100%;
            }
            #main-wrapper.pn-mf-mobile-shell .topbar .top-navbar .navbar-collapse {
                margin-left: 0 !important;
            }
            #main-wrapper.pn-mf-mobile-shell .left-sidebar {
                position: fixed; left: -260px !important; width: 260px !important;
                max-width: 90vw; visibility: hidden; pointer-events: none;
                background: #fff; z-index: 10;
            }
            #main-wrapper.pn-mf-mobile-shell.show-sidebar .left-sidebar {
                left: 0 !important; visibility: visible; pointer-events: auto;
            }
            #main-wrapper.pn-mf-mobile-shell.show-sidebar > #mf-sidebar-backdrop {
                display: block; position: fixed; inset: 80px 0 0;
                z-index: 9; background: rgba(23, 32, 51, .38);
                border: 0; padding: 0; margin: 0; width: 100%; cursor: pointer;
            }
            #mf-module-root { width: 100%; min-width: 0; max-width: 100%; padding-left: 12px; padding-right: 12px; }
            #mf-module-root > .tab-content, #mf-module-root > .tab-content > .tab-pane { min-width: 0; max-width: 100%; }
            #mf-module-root .table-responsive, #mf-module-root .mf-top-scroll { max-width: 100%; }
        }
        /* MF.03.1: reglas locales de navegación, no alteran las tablas. */
        #mf-module-nav { min-width: 0; max-width: 100%; }
        #mf-module-nav #operacionTabs { display: flex; flex-wrap: wrap; }
        #mf-module-nav #operacionTabs .nav-item { flex: 0 0 auto; max-width: 100%; }
        #mf-module-nav #operacionTabs .nav-link { white-space: nowrap; }
        #mf-mobile-nav, #mf-mobile-nav[hidden] { display: none; }
        #mf-module-nav #operacionTabs .nav-link:focus-visible,
        #mf-section-select:focus-visible { outline: 2px solid #1b2256; outline-offset: 2px; }
        @media (max-width: 767.98px) {
            #mf-module-nav[data-mf-nav-ready="true"] #operacionTabs { display: none; }
            #mf-module-nav[data-mf-nav-ready="true"] #mf-mobile-nav:not([hidden]) {
                display: block;
                padding: .75rem;
                border: 1px solid #b8c2d6;
                border-radius: .375rem;
                background: #fff;
            }
            #mf-mobile-nav label { display: block; color: #36445b; font-weight: 600; margin-bottom: .35rem; }
            #mf-section-select { width: 100%; min-height: 44px; font-size: 16px; color: #25324b; border-color: #8492a9; }
            #mf-module-nav #operacionTabs .nav-link { white-space: normal; min-height: 44px; }
        }
    </style>
    <link rel="stylesheet" href="<?= BASE_URL ?>Assets/Css/ModulosAdmin/mf-column-selector.css?v=mf033">
    <link rel="stylesheet" href="<?= BASE_URL ?>Assets/Css/ModulosAdmin/mf-table-layout.css?v=mf035">
    <link rel="stylesheet" href="<?= BASE_URL ?>Assets/Css/ModulosAdmin/mf-responsive-modals.css?v=mf036">
    <link rel="stylesheet" href="<?= BASE_URL ?>Assets/Css/ModulosAdmin/mf-accessibility.css?v=mf037">
    <div class="tab-content mt-3">
        <div class="tab-pane fade " id="resumen" role="tabpanel" aria-labelledby="resumen-tab">
            <!-- MF.02.4 D: contenido bajo demanda -->
        </div>
        <div class="tab-pane fade show active" id="crear_operacions" role="tabpanel" aria-labelledby="crear_operacions-tab">
            <?php include 'tabs/operaciones_mar.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos_operacion" role="tabpanel" aria-labelledby="costos-operaciones-tab">
            <!-- MF.02.4 C: contenido bajo demanda -->
        </div>
        <div class="tab-pane fade" id="Eventos_Logisticos" role="tabpanel" aria-labelledby="Eventos_Logisticos-tab">
            <!-- MF.02.4 B: contenido bajo demanda -->
        </div>
        <div class="tab-pane fade" id="Eventos_Logisticos_ferro" role="tabpanel" aria-labelledby="Eventos_Logisticos_ferro-tab">
            <!-- MF.02.4 B: contenido bajo demanda -->
        </div>
        <div class="tab-pane fade" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
            <!-- MF.02.4 C: contenido bajo demanda -->
        </div>

        <div class="tab-pane fade" id="trazabilidad" role="tabpanel" aria-labelledby="trazabilidad-tab">
            <!-- MF.02.4 C: contenido bajo demanda -->
        </div>
        <div class="tab-pane fade" id="en-piso" role="tabpanel" aria-labelledby="en-piso-tab">
            <!-- MF.02.4 A: contenido bajo demanda -->
        </div>
        <div class="tab-pane fade" id="costos_operacion_clientes" role="tabpanel" aria-labelledby="costos-operaciones-clientes-tab">
            <!-- MF.02.4 A: contenido bajo demanda -->
        </div>
    </div>
</div>


<!--<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script> -->




<!--<script src="https://cdn.jsdelivr.net/npm/jspdf/dist/jspdf.umd.min.js"></script> -->

<!--<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable/dist/jspdf.plugin.autotable.min.js"></script> -->

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-exportar-tablas.js"></script>
<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-panel-loader.js?v=mf024d"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-responsive-nav.js?v=mf031"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-mobile-shell.js?v=mf031r1"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-column-selector.js?v=mf033" data-mf-user="<?= (int) ($_SESSION['id_usuario'] ?? 0) ?>"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-table-layout.js?v=mf035"></script>
<!-- Refuerzo de comportamiento por si algún conflicto impide los tabs -->
<script>
    const triggerTabList = document.querySelectorAll('#operacionTabs a');
    triggerTabList.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            let instance = new bootstrap.Tab(this);
            instance.show();
        });
    });
</script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-accessibility.js?v=mf037"></script>
