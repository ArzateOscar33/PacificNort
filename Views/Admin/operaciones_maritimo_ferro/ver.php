<?php // MF.02.5: perfil ligero solo en esta vista.
$pnMFCompactAssets = true;
include 'Views/Template/admin_header.php'; ?>

<div class="container mt-4 col-md-12">


    <ul class="nav nav-tabs" id="operacionTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link " id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab" aria-controls="resumen" aria-selected="false"><i data-feather="zap"></i>Resumen</a>
        </li>
        <!--  MARITIMA -->
        <li class="nav-item">
            <a class="nav-link active" id="crear_operacions-tab" data-bs-toggle="tab" href="#crear_operacions" role="tab" aria-controls="crear_operacions" aria-selected="true"><i data-feather="anchor"></i>Operaciones</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="Eventos_Logisticos-tab" data-bs-toggle="tab" href="#Eventos_Logisticos" role="tab" aria-controls="Eventos_Logisticos" aria-selected="false"><i data-feather="calendar"></i>Eventos Maritimos</a>
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

    <!-- MF.02.2 R2: URL base independiente de los paneles ocultos -->
    <script>
        window.BASE_URL = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <!-- MF.02.2: ciclo de vida de pestanas -->
    <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-tab-lifecycle.js?v=mf022"></script>
    <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/mf-export-loader.js?v=mf023"></script>
    <style>
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
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