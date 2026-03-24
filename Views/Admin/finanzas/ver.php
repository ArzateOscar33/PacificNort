<?php include 'Views/Template/admin_header.php'; ?>
<style>
    td,
    th {
        text-transform: uppercase;
    }
</style>
<script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";
</script>
<div class="container mt-4 col-md-12">


    <ul class="nav nav-tabs" id="operacionTabs" role="tablist">


        </li>
        <li class="nav-item">
            <a class="nav-link active" id="costos-operaciones-tab" data-bs-toggle="tab" href="#costos_operacion" role="tab" aria-controls="costos_operacion" aria-selected="true"><i data-feather="dollar-sign"></i>Costos </a>
        </li>




        <li class="nav-item">
            <a class="nav-link" id="costos-domesticos-tab" data-bs-toggle="tab" href="#costos_domesticos" role="tab" aria-controls="costos_domesticos" aria-selected="false"><i data-feather="dollar-sign"></i>Costos Domésticos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="costos-operaciones-clientes-tab" data-bs-toggle="tab" href="#costos_operacion_clientes" role="tab" aria-controls="costos_operacion_clientes" aria-selected="false"><i data-feather="dollar-sign"></i>Costos Totales</a>
        </li>
    </ul>

    <div class="tab-content mt-3">

        <div class="tab-pane fade show active" id="costos_operacion" role="tabpanel" aria-labelledby="costos_operacion-tab">
            <?php include 'tabs/costos_operacion.php'; ?>
        </div>


        <div class="tab-pane fade" id="costos_operacion_clientes" role="tabpanel" aria-labelledby="costos_operacion_clientes-tab">
            <?php include 'tabs/costos_cliente.php'; ?>
        </div>
        <div class="tab-pane fade" id="costos_domesticos" role="tabpanel" aria-labelledby="costos-domesticos-tab">
            <?php include 'tabs/costos_domesticos.php'; ?>
        </div>
    </div>
</div>

<!--<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/xlsx.full.min.js"></script>



<!--<script src="https://cdn.jsdelivr.net/npm/jspdf/dist/jspdf.umd.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.umd.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable/dist/jspdf.plugin.autotable.min.js"></script> -->
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/exportarTablas.js"></script>
<?php include 'Views/Template/admin_footer.php'; ?>
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