<?php include 'Views/Template/admin_header.php'; ?>

<div class="container mt-4 col-md-12">

  <ul class="nav nav-tabs" id="operacionTabs" role="tablist">

    <li class="nav-item" role="presentation">
      <a class="nav-link active"
        id="crear_operaciones-tab"
        data-bs-toggle="tab"
        data-bs-target="#crear_operaciones"
        type="button"
        role="tab"
        aria-controls="crear_operaciones"
        aria-selected="true">
        <i data-feather="navigation"></i> Operación Por Partida
      </a>
    </li>



    <li class="nav-item" role="presentation">
      <a class="nav-link"
        id="registrar-envio-tab"
        data-bs-toggle="tab"
        data-bs-target="#registrar-envio"
        type="button"
        role="tab"
        aria-controls="registrar-envio"
        aria-selected="false">
        <i data-feather="send"></i> Registrar Envio
      </a>
    </li>



    <li class="nav-item" role="presentation">
      <a class="nav-link"
        id="eventos-tab"
        data-bs-toggle="tab"
        data-bs-target="#eventos"
        type="button"
        role="tab"
        aria-controls="eventos"
        aria-selected="false">
        <i data-feather="calendar"></i> Eventos
      </a>
    </li>

    <li class="nav-item" role="presentation">
      <a class="nav-link"
        id="documentos-tab"
        data-bs-toggle="tab"
        data-bs-target="#documentos"
        type="button"
        role="tab"
        aria-controls="documentos"
        aria-selected="false">
        <i data-feather="file-plus"></i> Documentos
      </a>
    </li>

  </ul>

  <!-- IMPORTANTE: tab-content -->
  <div class="tab-content pt-3" id="operacionTabsContent">

    <div class="tab-pane fade show active"
      id="crear_operaciones"
      role="tabpanel"
      aria-labelledby="crear_operaciones-tab">
      <?php include 'tabs/operaciones_partida.php'; ?>
    </div>



    <div class="tab-pane fade"
      id="registrar-envio"
      role="tabpanel"
      aria-labelledby="registrar-envio-tab">
      <?php include 'tabs/envios.php'; ?>
    </div>


    <div class="tab-pane fade"
      id="eventos"
      role="tabpanel"
      aria-labelledby="eventos-tab">
      <?php include 'tabs/eventos.php'; ?>
    </div>

    <div class="tab-pane fade"
      id="documentos"
      role="tabpanel"
      aria-labelledby="documentos-tab">
      <?php include 'tabs/documentos.php'; ?>
    </div>

  </div>
</div>


<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.umd.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/librerias/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/exportarTablas.js"></script>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_eventos_catalogo.js"></script>