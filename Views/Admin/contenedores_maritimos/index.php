<?php include 'Views/Template/admin_header.php'; ?>
<div class="container col-md-12">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Contenedores Marítimos</h3>
        </div>

<div class="card-body">
  <!-- Fila superior: buscador + botón -->
  <div class="row g-2 align-items-center mb-3">
    <!-- Buscador -->
    <div class="col-12 col-lg-10 position-relative">
      <input type="text" class="form-control" id="buscarContenedorMaritimo" name="buscarContenedorMaritimo"
             placeholder="Buscar Contenedor Marítimo" autocomplete="off">
      <div id="sugerenciasContenedores"
           class="list-group position-absolute top-100 start-0 w-100"
           style="display:none; z-index:999;"></div>
    </div>

    <!-- Botón Agregar alineado a la derecha -->
    <div class="col-12 col-lg-2 d-flex justify-content-lg-end">
      <button id="btnAgregarContenedorMaritimo" class="btn btn-primary w-100 w-lg-auto ms-lg-2"
              data-bs-toggle="modal" data-bs-target="#modalRegistrarContenedorMaritimo">
        <i class="fas fa-plus"></i> Agregar Contenedor Marítimo
      </button>
    </div>
  </div> <!-- /row -->

  <!-- Selector 25/50 (bloque aparte, MISMO id) -->
  <div class="d-flex flex-wrap justify-content-end align-items-center mb-2 gap-2">
    <label for="perPageSelect" class="mb-0 small text-muted">Mostrar</label>
    <select id="perPageSelect" class="form-control form-control-sm" style="width:auto">
      <option value="25" selected>25</option>
      <option value="50">50</option>
    </select>
    <span class="small text-muted">por página</span>
  </div>

  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-hover">
      <thead class="table-primary text-center">
        <tr>
          <th>Número de Contenedor</th>
          <th>Tipo</th>
          <th>Observaciones</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tablaContenedoresMaritimos"></tbody>
    </table>
  </div>

  <!-- Paginación -->
  <nav aria-label="Paginación de contenedores" class="mt-3">
    <ul class="pagination justify-content-end" id="paginacion"></ul>
  </nav>
</div>


      </div>
    </div>
  </div>
</div>

<!-- Modal: Crear / Editar -->
<div class="modal fade" id="modalRegistrarContenedorMaritimo" data-bs-backdrop="static" data-bs-keyboard="false"
  tabindex="-1" aria-labelledby="modalRegistrarContenedorMaritimoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarContenedorMaritimoLabel">
          <i data-feather="plus-circle" class="me-2"></i> Registrar Contenedor Marítimo
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="formContenedorMaritimo" method="POST" action="#">
          <input type="hidden" name="id_contenedor" id="id_contenedor" value="">

          <div class="mb-3">
            <label for="numero_contenedor" class="form-label">Número de Contenedor</label>
            <input type="text" class="form-control" name="numero_contenedor" id="numero_contenedor" required>
          </div>

          <div class="mb-3">
            <label for="tipo" class="form-label">Tipo</label>
            <input type="text" class="form-control" name="tipo" id="tipo" placeholder="Ej: 40HQ, 20STD" >
          </div>

          <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
          </div>

          <div class="modal-footer px-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i data-feather="x-circle" class="me-1"></i> Cancelar
            </button>
            <button type="submit" id="btnSubmit" class="btn btn-primary">
              <i data-feather="check-circle" class="me-1"></i> Agregar
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/contenedores_maritimos.js"></script>