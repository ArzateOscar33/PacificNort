<?php include 'Views/Template/admin_header.php'; ?>
<?php
// Si tu motor NO hace extract($data):
$tiposMovimiento = $data['tiposMovimiento'] ?? [];
?>
<div class="container mt-4 col-md-12">
 

    <ul class="nav nav-tabs" id="operacionTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab" aria-controls="resumen" aria-selected="true"><i data-feather="zap"></i> Resumen</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="crear_operacions-tab" data-bs-toggle="tab" href="#crear_operacions" role="tab" aria-controls="crear_operacions" aria-selected="false"><i data-feather="activity"></i> Operaciones</a>
        </li>
 

        <li class="nav-item">
            <a class="nav-link" id="costos_operacion-tab"
            data-bs-toggle="tab"
            href="#costos_operacion"
            role="tab"
            aria-controls="costos_operacion"
            aria-selected="false"><i data-feather="dollar-sign"></i>Costos por Operación</a>
        </li>
 
        <li class="nav-item">
            <a class="nav-link" id="documentos-tab" data-bs-toggle="tab" href="#documentos" role="tab" aria-controls="documentos" aria-selected="false"><i data-feather="file-plus"></i>Documentos</a>
        </li>

        <!--<li class="nav-item">
            <a class="nav-link" id="documentos-tab" data-bs-toggle="tab" href="#costos-contenedor" role="tab" aria-controls="documentos" aria-selected="false"><i data-feather="file-plus"></i>Costos Contenedor</a>
        </li> -->
 
 
        <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#detalles-logisticos">
              <i data-feather="calendar"></i>Eventos Logísticos
        </a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="resumen" role="tabpanel" aria-labelledby="resumen-tab">
            <?php include 'tabs/resumen.php'; ?>
        </div>
        <div class="tab-pane fade" id="crear_operacions" role="tabpanel" aria-labelledby="crear_operacions-tab">
            <?php include 'tabs/operaciones.php'; ?>
        </div>
        <div class="tab-pane fade" id="contenedores" role="tabpanel" aria-labelledby="contenedores-tab">
            <?php include 'tabs/ferros.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos" role="tabpanel" aria-labelledby="costos-tab">
            <?php include 'tabs/costos.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos_operacion" role="tabpanel" aria-labelledby="costos_operacion-tab">
            <?php include __DIR__ . '/tabs/costos_operacion.php'; ?>
        </div>
        <div class="tab-pane fade" id="trazabilidad" role="tabpanel" aria-labelledby="trazabilidad-tab">
            <?php include 'tabs/trazabilidad.php'; ?>
        </div>
        <div class="tab-pane fade" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
            <?php include 'tabs/documentos.php'; ?>
        </div>
 
       <!-- <div class="tab-pane fade" id="costos-contenedor" role="tabpanel" aria-labelledby="log-tab">
            <?//php include 'tabs/costos.php'; ?>
        </div> -->
        <div class="tab-pane fade" id="log" role="tabpanel" aria-labelledby="log-tab">
            <?php include 'tabs/log.php'; ?>
        </div> 

        <div class="tab-pane fade" id="detalles-logisticos" role="tabpanel" aria-labelledby="detalles-logisticos-tab">
            <?php include 'tabs/eventos_logisticos.php'; ?>
        </div> 
    </div>
</div>

<!-- Refuerzo de comportamiento por si algún conflicto impide los tabs -->
<script>
    const triggerTabList = document.querySelectorAll('#operacionTabs a');
    triggerTabList.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            let instance = new bootstrap.Tab(this);
            instance.show();
        });
    });
</script>


<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/Operaciones_maritimas/catalogos/operaciones_maritimas_llenado_catalogos.js">
</script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/Operaciones_maritimas/operaciones_maritimas_registrar_operaciones.js">
</script>
<!--<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/Operaciones_maritimas/contenedores_operacion.js">
</script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/Operaciones_maritimas/costos_contenedor.js">
</script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/Operaciones_maritimas/catalogos/costos_contenedor_catalogos.js">
</script>
<!--<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/xlsx.full.min.js"></script>
 
<!--<script src="https://cdn.jsdelivr.net/npm/jspdf/dist/jspdf.umd.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.umd.min.js"></script>

<!--<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable/dist/jspdf.plugin.autotable.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.plugin.autotable.min.js"></script>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/exportarTablas.js">
</script>


