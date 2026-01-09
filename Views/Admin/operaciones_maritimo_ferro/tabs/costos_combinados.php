<div class="container py-4 col-md-12">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
      <i data-feather="package" class="me-2"></i>Costos por Contenedor (Marítimo + FO)
    </h4>
  </div>

  <!-- Filtros -->
  <div class="row g-3 align-items-end mb-4">

    <!-- Contenedor -->
    <div class="col-md-3">
      <label for="inputContenedorBuscarCostosCombinados" class="form-label">Contenedor marítimo</label>
      <input
        type="text"
        id="inputContenedorBuscarCostosCombinados"
        class="form-control"
        placeholder="Ej. TRHU6818550"
        autocomplete="off">
    </div>

    <!-- Rango de fechas -->
    <div class="col-md-2">
      <label for="filtroFechaInicioCostosContCostosCombinados" class="form-label">Desde</label>
      <input type="date" id="filtroFechaInicioCostosContCostosCombinados" class="form-control">
    </div>

    <div class="col-md-2">
      <label for="filtroFechaFinCostosContCostosCombinados" class="form-label">Hasta</label>
      <input type="date" id="filtroFechaFinCostosContCostosCombinados" class="form-control">
    </div>

    <!-- Por página -->
    <div class="col-md-2">
      <label class="form-label">Por página</label>
      <select id="perPageCostosCostosCombinados" class="form-control">
        <option>10</option>
        <option>20</option>
        <option>50</option>
      </select>
    </div>

    <!-- Moneda vista + TC -->
    <div class="col-md-1">
      <label class="form-label">Moneda vista</label>
      <select id="costosOperacionMonedaVistaCostosCombinados" class="form-control">
        <option value="MXN">MXN (pesos)</option>
        <option value="USD">USD (dólares)</option>
      </select>
    </div>

    <div class="col-md-1">
      <label class="form-label">Tipo de cambio</label>
      <div class="input-group">
        <span class="input-group-text">$</span>
        <input
          type="number"
          step="0.0001"
          min="0"
          id="costosOperacionTipoCambioCostosCombinados"
          class="form-control"
          value="17.00">
      </div>
    </div>

    <!-- Botones export -->
    <div class="col-md-1 d-flex gap-2 justify-content-start">
      <button class="btn btn-sm btn-outline-success" id="btnExportarExcelCostosContenedorCostosCombinados">
        <i data-feather="file-text" class="me-1"></i> Excel
      </button>
      <button class="btn btn-sm btn-outline-warning" id="btnExportarPDFCostosContenedorCostosCombinados">
        <i data-feather="file" class="me-1"></i> PDF
      </button>
    </div>

  </div>

  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-bordered align-middle" id="tablaCostosContenedoresCostosCombinados">
      <thead class="table-light">
        <tr>
          <th>Operacion De Origen</th> 
          <th>Contenedor</th>
          <th>Cliente</th>
          <th>Concepto</th>
          <th style="width: 160px;">Monto</th>
        </tr>
      </thead>
      <tbody id="tbodyCostosContenedoresCostosCombinados">
        <!-- Se llena desde JS -->
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
      <div id="metaResumenCostosCostosCombinados" class="text-muted small">Mostrando 0–0 de 0</div>
      <nav aria-label="Paginación de costos">
        <ul id="paginacionCostosCostosCombinados" class="pagination pagination-sm mb-0"></ul>
      </nav>
    </div>
  </div>

</div>

<script>
  feather.replace();
</script>

 <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/costos_combinados.js"></script>  
