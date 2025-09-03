<div class="container py-4 col-md-12">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Contenedores en Operación</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarContenedor">
      <i data-feather="plus"></i> Añadir Contenedor
    </button>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <label for="filtro_tipo" class="form-label">Tipo de Contenedor</label>
      <select id="filtro_tipo" class="form-control">
        <option value="">Todos</option>
        <option value="maritimo">Marítimo</option>
        <option value="terrestre">Terrestre</option>
      </select>
    </div>
    <div class="col-md-4">
      <label for="buscar" class="form-label">Buscar Cliente o Contenedor</label>
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <label for="perPageCont" class="mb-0 small text-muted">Mostrar</label>
      <select id="perPageCont" class="form-control" style="width: 90px;">
        <option value="10" selected>10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <span class="small text-muted">por página</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered align-middle" id="tablaContenedores">
      <thead class="table-light">
        <tr>

          <th>Tipo</th>
          <th>Operacion</th>
          <th>Contenedor</th>
          <th>Cliente</th>
          <th>Bultos</th>
          <th>ETA</th>
          <th>ETD</th> 
          <th>Shipper</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
      <!-- Resumen -->
      <div class="small text-muted">
        <span id="metaResumenCont">Mostrando 0-0 de 0</span>
      </div>

      <!-- Paginación -->
      <nav aria-label="Paginación de contenedores">
        <ul id="paginacionCont" class="pagination pagination-sm mb-0">
          <!-- Se llena desde JS -->
        </ul>
      </nav>
    </div>
  </div>
  </div>

<!-- Modal: Agregar Contenedor a la Operación -->
<div class="modal fade" id="modalAgregarContenedor" tabindex="-1" aria-labelledby="modalAgregarContenedorLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalAgregarContenedorLabel">
          <i data-feather="plus-circle" class="me-1"></i> Añadir Contenedor a la Operación
        </h5>
      </div>
      <div class="modal-body">
        <form id="formAgregarContenedor" autocomplete="off">
          <input type="hidden" id="row_id" name="row_id">
          <div class="row mb-3">
            <div class="col-md-6 position-relative">
              <label class="form-label">Operación</label>
              <input type="hidden" id="operacion_id" name="operacion_id">
              <input type="text" id="operacionNombre" class="form-control"
                placeholder="Escribe para buscar operación...">
              <div id="sugOperaciones" class="list-group"
                style="position:absolute; z-index:1055; width:100%; display:none;"></div>
              <small class="text-muted">Sugerencia: escribe número de operación, BL o cliente.</small>
            </div>
            <div class="col-md-6 position-relative">
              <label class="form-label">Cliente</label>
              <input type="hidden" id="cliente_id" name="cliente_id">
              <input type="text" id="clienteNombreContenedores" name="clienteNombreContenedores" class="form-control"
                placeholder="" readonly>
              <div id="sugClientes" class="list-group"
                style="position:absolute; z-index:1055; width:100%; display:none;"></div>

            </div>

          </div>
          <div class="row mb-3">

            <div class="col-md-6 position-relative">
              <label class="form-label">Contenedor Físico</label>
              <input type="hidden" id="contenedor_id" name="contenedor_id">
              <input type="text" id="contenedorNombre" class="form-control"
                placeholder="Escribe para buscar contenedor...">
              <div id="sugContenedores" class="list-group"
                style="position:absolute; z-index:1055; width:100%; display:none;"></div>
              <small class="text-muted">Sugerencia: escribe parte del número (ej. WHUS...).</small>
            </div>
            <div class="col-md-6">
              <label for="bultos" class="form-label">Bultos</label>
              <input type="number" id="bultosContenedores" name="bultosContenedores" class="form-control">
            </div>

          </div>
          <div class="row mb-3">

            <div class="mb-3 col-md-6">
              <div class="col-md-12">
                <label for="comentarios" class="form-label">Comentarios</label>
                <textarea id="comentarios" name="comentarios" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i data-feather="x"></i> Cancelar
        </button>
        <button type="submit" form="formAgregarContenedor" class="btn btn-primary">
          <i data-feather="save"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>
<script>
  feather.replace();
</script>

<!-- Inyectamos los catálogos como arrays JS, generados por PHP -->
<script> 
  window.CAT_OPERACIONES = <?= json_encode(array_map(function($r){
      return [
        'id'          => (int)$r['id_operacion'],
        'label'       => trim(($r['numero_operacion'] ?? '')),
        'cliente_id'  => isset($r['cliente_id']) ? (int)$r['cliente_id'] : 0,
        'cliente'     => trim(($r['cliente'] ?? '')),
      ];
  }, $data['ops'] ?? []), JSON_UNESCAPED_UNICODE); ?>;

 


  window.CAT_FISICOS = <?= json_encode(array_map(function($r){
      return ['id' => (int)$r['id_fisico'], 'label' => $r['numero_ferro'] ?? ''];
  }, $data['fisicos'] ?? []), JSON_UNESCAPED_UNICODE); ?>;

  window.CAT_SHIPPERS = <?= json_encode(array_map(function($r){
      return ['id' => (int)$r['id_shipper'], 'label' => $r['nombre'] ?? ''];
  }, $data['shippers'] ?? []), JSON_UNESCAPED_UNICODE); ?>;
</script>

 
