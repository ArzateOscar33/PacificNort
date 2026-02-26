<?php include 'Views/Template/admin_header.php'; ?>

<div class="container mt-4 col-md-12">


    <ul class="nav nav-tabs" id="operacionTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link " id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab" aria-controls="resumen" aria-selected="true"><i data-feather="zap"></i>Resumen</a>
        </li>
        <!--  MARITIMA -->
        <li class="nav-item">
            <a class="nav-link active" id="crear_operacions-tab" data-bs-toggle="tab" href="#crear_operacions" role="tab" aria-controls="crear_operacions" aria-selected="false"><i data-feather="anchor"></i>Operaciones</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="Eventos_Logisticos-tab" data-bs-toggle="tab" href="#Eventos_Logisticos" role="tab" aria-controls="Eventos_Logisticos" aria-selected="false"><i data-feather="calendar"></i>Eventos Maritimos</a>
        </li>
        <!-- FIN MARITMA -->

        <!-- FERRO/TERRESTRE -->
        <li class="nav-item">
            <a class="nav-link" id="en-piso-tab" data-bs-toggle="tab" href="#en-piso" role="tab" aria-controls="en-piso" aria-selected="false"><i data-feather="home"></i>En Bodega</a>
        </li>
        <!--
        <li class="nav-item">
            <a class="nav-link" id="crear_operaciones_ferro-tab" data-bs-toggle="tab" href="#crear_operaciones_ferro" role="tab" aria-controls="crear_operaciones_ferro" aria-selected="false"><i data-feather="truck"></i>En Transito</a>
        </li> -->

        <li class="nav-item">
            <a class="nav-link" id="Eventos_Logisticos_ferro-tab" data-bs-toggle="tab" href="#Eventos_Logisticos_ferro" role="tab" aria-controls="Eventos_Logisticos_ferro" aria-selected="false"><i data-feather="calendar"></i>Eventos Terrestres</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#trazabilidad">
                <i data-feather="shuffle"></i> Trazabilidad
            </a>

        <li class="nav-item">
            <a class="nav-link" id="documentos-tab" data-bs-toggle="tab" href="#documentos" role="tab" aria-controls="documentos" aria-selected="false"><i data-feather="file-plus"></i>Documentos</a>
        </li>




        <!-- FIN FERRO/TERRESTRE -->

        </li>
        <li class="nav-item">
            <a class="nav-link" id="costos-operaciones-tab" data-bs-toggle="tab" href="#costos_operacion" role="tab" aria-controls="costos_operacion" aria-selected="false"><i data-feather="dollar-sign"></i>Costos </a>
        </li>
        <!--
        <li class="nav-item">
            <a class="nav-link" id="costos-tab" data-bs-toggle="tab" href="#costos" role="tab" aria-controls="costos" aria-selected="false"><i data-feather="dollar-sign"></i>Costos Contenedor</a>
        </li> -->

        <li class="nav-item">
            <a class="nav-link" id="costos-operaciones-clientes-tab" data-bs-toggle="tab" href="#costos_operacion_clientes" role="tab" aria-controls="costos_operacion_clientes" aria-selected="false"><i data-feather="dollar-sign"></i>Costos Clientes</a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade " id="resumen" role="tabpanel" aria-labelledby="resumen-tab">
            <?php include 'tabs/resumen.php'; ?>
        </div>
        <div class="tab-pane fade show active" id="crear_operacions" role="tabpanel" aria-labelledby="crear_operacions-tab">
            <?php include 'tabs/operaciones_mar.php'; ?>
        </div>
        <div class="tab-pane fade" id="crear_operaciones_ferro" role="tabpanel" aria-labelledby="crear_operaciones_ferro-tab">
            <?php include 'tabs/operaciones_ferro.php'; ?>
        </div>
        <!-- <div class="tab-pane fade" id="costos" role="tabpanel" aria-labelledby="costos-tab">
            <? //php include 'tabs/costos_combinados.php'; 
            ?>
        </div> -->
        <div class="tab-pane fade" id="costos_operacion" role="tabpanel" aria-labelledby="costos_operacion-tab">
            <?php include 'tabs/costos_operacion.php'; ?>
        </div>
        <div class="tab-pane fade" id="Eventos_Logisticos" role="tabpanel" aria-labelledby="Eventos_Logisticos-tab">
            <?php include 'tabs/Eventos_Logisticos_mar.php'; ?>
        </div>
        <div class="tab-pane fade" id="Eventos_Logisticos_ferro" role="tabpanel" aria-labelledby="Eventos_Logisticos_ferro-tab">
            <?php include 'tabs/Eventos_Logisticos_ferro.php'; ?>
        </div>
        <div class="tab-pane fade" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
            <?php include 'tabs/documentos.php'; ?>
        </div>

        <div class="tab-pane fade" id="log" role="tabpanel" aria-labelledby="log-tab">
            <?php include 'tabs/log.php'; ?>
        </div>
        <div class="tab-pane fade" id="trazabilidad" role="tabpanel" aria-labelledby="trazabilidad-tab">
            <?php include 'tabs/trazabilidad.php'; ?>
        </div>
        <div class="tab-pane fade" id="detalles-logisticos" role="tabpanel" aria-labelledby="detalles-logisticos-tab">
            <?php include 'tabs/detalles_logisticos.php'; ?>
        </div>
        <div class="tab-pane fade" id="en-piso" role="tabpanel" aria-labelledby="en-piso-tab">
            <?php include 'tabs/en_piso.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos-contenedor" role="tabpanel" aria-labelledby="log-tab">
            <?php include 'tabs/costos.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos_operacion_clientes" role="tabpanel" aria-labelledby="costos_operacion_clientes-tab">
            <?php include 'tabs/costos_cliente.php'; ?>
        </div>
    </div>
</div>

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
<!--<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/xlsx.full.min.js"></script>



<!--<script src="https://cdn.jsdelivr.net/npm/jspdf/dist/jspdf.umd.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.umd.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable/dist/jspdf.plugin.autotable.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/exportarTablas.js"></script>
<?php include 'Views/Template/admin_footer.php'; ?>