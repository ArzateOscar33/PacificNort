<?php include 'Views/Template/admin_header.php'; ?>

<div class="container mt-4 col-md-12">
 

    <ul class="nav nav-tabs" id="operacionTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab" aria-controls="resumen" aria-selected="true">Resumen</a>
        </li>
         <li class="nav-item">
            <a class="nav-link" id="crear_operacions-tab" data-bs-toggle="tab" href="#crear_operacions" role="tab" aria-controls="crear_operacions" aria-selected="false">Crear Operación Maritima-Ferroviaria</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="crear_operaciones_ferro-tab" data-bs-toggle="tab" href="#crear_operaciones_ferro" role="tab" aria-controls="crear_operaciones_ferro" aria-selected="false">Ferros en Operación</a>
        </li> 
        
        <!-- <li class="nav-item">
            <a class="nav-link" id="costos-tab" data-bs-toggle="tab" href="#costos" role="tab" aria-controls="costos" aria-selected="false">Costos Contenedor</a>
        </li> -->
        <li class="nav-item">
            <a class="nav-link" id="costos-operaciones-tab" data-bs-toggle="tab" href="#costos_operacion" role="tab" aria-controls="costos_operacion" aria-selected="false">Costos Operaciones</a>
        </li>
 
        <li class="nav-item">
            <a class="nav-link" id="Eventos_Logisticos-tab" data-bs-toggle="tab" href="#Eventos_Logisticos" role="tab" aria-controls="Eventos_Logisticos" aria-selected="false">Eventos Logisticos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="documentos-tab" data-bs-toggle="tab" href="#documentos" role="tab" aria-controls="documentos" aria-selected="false">Documentos</a>
        </li>
 
 
         <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#trazabilidad">
            <i data-feather="file-text"></i> Trazabilidad
        </a>
        </li>  
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="resumen" role="tabpanel" aria-labelledby="resumen-tab">
            <?php include 'tabs/resumen.php'; ?>
        </div>
        <div class="tab-pane fade" id="crear_operacions" role="tabpanel" aria-labelledby="crear_operacions-tab">
            <?php include 'tabs/operaciones_mar.php'; ?>
        </div>
        <div class="tab-pane fade" id="crear_operaciones_ferro" role="tabpanel" aria-labelledby="crear_operaciones_ferro-tab">
            <?php include 'tabs/operaciones_ferro.php'; ?>
        </div> 
        <div class="tab-pane fade" id="costos" role="tabpanel" aria-labelledby="costos-tab">
            <?php include 'tabs/costos.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos_operacion" role="tabpanel" aria-labelledby="costos_operacion-tab">
            <?php include 'tabs/costos_operacion.php'; ?>
        </div>
        <div class="tab-pane fade" id="Eventos_Logisticos" role="tabpanel" aria-labelledby="Eventos_Logisticos-tab">
            <?php include 'tabs/Eventos_Logisticos.php'; ?>
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
