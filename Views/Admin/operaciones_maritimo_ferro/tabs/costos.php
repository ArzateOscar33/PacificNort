<div class="container py-4 col-md-12">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Costos por Contenedor</h4>
    <button class="btn btn-success" data-bs-toggle="modal" id="btnNuevoCostoContenedor"
      data-bs-target="#modalAgregarCosto">
      <i data-feather="plus"></i> Añadir Costo
    </button>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <label for="buscar" class="form-label">Buscar por cliente o costo</label>
      <input type="text" id="buscarCostoContenedor" class="form-control" placeholder="Buscar...">
    </div>
    <div class="col-md-4">
      <label for="filtro_moneda" class="form-label">Tipo de Moneda</label>
      <select id="filtroMonedaCostoContenedor" name="filtroMonedaCostoContenedor" class="form-control">
        <option value="">Todas</option>
        <option value="Pesos">Pesos</option>
        <option value="Dólares">Dólares</option>

      </select>
    </div>
    <div class="col-md-4">
      <label for="filtroNaturalezaCostoContenedor" class="form-label">Naturaleza</label>
      <select id="filtroNaturalezaCostoContenedor" class="form-control">
        <option value="">Todos</option>
        <option value="GASTO">Gasto</option>
        <option value="ABONO">Abono</option>
      </select>
    </div>
    <div class="col-md-4">
      <label for="filtro_tipo" class="form-label">Tipo de Costo</label>
      <select id="filtroTipoCostoContenedor" name="filtroTipoCostoContenedor" class="form-control">
        <!-- Se llena desde JS -->
      </select>
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
      <div class="d-flex align-items-center gap-2">
        <label class="mb-0">Por página:</label>
        <select id="perPageCostos" class="form-control form-control-sm" style="width: 90px;">
          <option>10</option>
          <option>20</option>
          <option>50</option>
        </select>
      </div>
      <div class="col-md-12 d-flex  mt-1 align-items-space-between gap-3">
        <div class="">
          <button class="btn btn-sm btn-outline-success" id="btnExportarExcelCostosContenedor">
            <i data-feather="file-text" class="me-1"></i> Excel
          </button>
        </div>
        <div class="">
          <button class="btn btn-sm btn-outline-warning" id="btnExportarPDFCostosContenedor">
            <i data-feather="file" class="me-1"></i> PDF
          </button>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered align-middle" id="tablaCostosContenedores">
        <thead class="table-light">
          <tr>
            <th>Operación</th>
            <th>Contenedor</th>
            <th>Tipo de Costo</th>
            <th>Monto</th>
            <th>Moneda</th>
            <th>Comentarios</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyCostosContenedores">
          <!-- Se llena desde JS -->
        </tbody>
      </table>

      <div id="metaResumenCostos" class="text-muted small">Mostrando 0–0 de 0</div>

      <nav class="d-flex justify-content-end" aria-label="Paginación de costos">
        <ul id="paginacionCostos" class="pagination pagination-sm mb-0"></ul>
      </nav>

    </div>
  </div>
</div>

 
 
<script>
  feather.replace();
</script>