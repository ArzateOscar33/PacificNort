<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title mt-3 mb-3 text-white">Tipos de Documentos</h3>
        </div>

        <div class="card-body">
          <!-- Buscador + botón -->
          <div class="row g-2 align-items-start mb-3">
            <div class="col-12 col-md-9 position-relative">
              <input
                type="text"
                class="form-control"
                id="buscarTipoDocumento"
                name="buscarTipoDocumento"
                placeholder="Buscar Tipo de Documento"
                autocomplete="off">
              <div id="sugerenciasTipoDocumento"
                   class="list-group position-absolute w-100"
                   style="z-index:999;"></div>
            </div>

            <div class="col-12 col-md-3 text-md-end">
              <button id="btnAgregarTipoDocumento"
                      class="btn btn-primary w-100 w-md-auto"
                      data-bs-toggle="modal"
                      data-bs-target="#modalRegistrarTipoDocumento">
                <i class="fas fa-plus"></i> Agregar Tipo de Documento
              </button>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-primary text-center">
                <tr>
                  <th>Clave</th>
                  <th>Nombre</th>
                  <th>Aplica Sobre</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaTipoDocumentos" class="text-center">
                <!-- Contenido dinámico -->
              </tbody>
            </table>
          </div>
        </div> <!-- /card-body -->
      </div> <!-- /card -->
    </div> <!-- /col-12 -->
  </div> <!-- /row -->
</div> <!-- /container -->

 
<div class="modal fade" id="modalRegistrarTipoDocumento" data-bs-backdrop="static" data-bs-keyboard="false"
    tabindex="-1" aria-labelledby="modalRegistrarTipoDocumentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Encabezado -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRegistrarTipoDocumentoLabel">
                    <i data-feather="file-plus" class="me-2"></i> Registrar Tipo de Documento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <form id="formTipoDocumento" method="POST" action="#">
                    <input type="hidden" name="idTipoDocumento" id="idTipoDocumento" value="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Clave</label>
                        <input type="text" name="clave" id="clave" class="form-control" required
                            placeholder="Ej. Encomienda,Garantía,Factura">
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombreDocumento" id="nombreDocumento" class="form-control" required
                            placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Descripcion</label>
                        <input type="text" name="descripcionDocumento" id="descripcionDocumento" class="form-control"
                            placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Aplica Sobre</label>
                        <select name="aplicaSobre" id="aplicaSobre" class="form-control">
                            <option value="operacion">Operacion</option>
                            <option value="contenedor_fisico">Contenedor Fisico</option>
                            <option value="contenedor_maritimo">Contenedor Maritimo</option>
                            <option value="operaciones_por_partida">Operaciones Por Partida</option>
                            <option value="cualquiera">Cualquiera</option>
                        </select>

                    </div>

                    <!-- Pie del modal -->
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
<script src="<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/tipos_documento.js"></script>