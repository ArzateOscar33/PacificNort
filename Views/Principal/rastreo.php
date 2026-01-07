<?php
// Views/Principal/rastreo.php
?>

<section class="section" id="rastreo">
  <div class="container">

    <!-- TÍTULO -->
    <div class="section-title" data-aos="fade-up">
      <h2 class="mb-1">
        <i class="bi bi-geo-alt me-2"></i>Rastreo de Carga
      </h2>
      <p class="section-subtitle mb-0">
        Consulta el estatus y ubicación de tus contenedores.
      </p>
    </div>

    <!-- BUSCADOR -->
    <div class="row justify-content-center">
      <div class="col-lg-8 col-md-10" data-aos="fade-up" data-aos-delay="100">

        <div class="input-group mt-2">
          <span class="input-group-text d-flex align-items-center">
            <i class="bi bi-upc-scan"></i>
          </span>

          <input
            type="text"
            class="form-control"
            id="inputNumeroGuia"
            placeholder="Ingresa tu número de operación (ej. FO-03, LBMF-01, LC-02)">

          <button class="btn btn-primary d-flex align-items-center" type="button" id="btnRastrearEnvio">
            <i class="bi bi-search me-1"></i> Rastrear
          </button>
        </div>

        <small class="text-muted d-block mt-2">
          Puedes buscar operaciones FO (ferro/terrestre) o marítimas (LBMF, LC, etc.).
        </small>

      </div>
    </div>


    <!-- ========================= -->
    <!-- RESULTADO FO (RUTAS/TRAMOS) -->
    <!-- ========================= -->
    <div class="row mt-3" id="bloqueResultadoFO">
      <div class="col-lg-12">

        <!-- Encabezado simple -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="mb-0">
              <i class="bi bi-signpost-split me-2"></i>Rutas de la Operación
            </h5>
            <small class="text-muted" id="lblOperacionSeleccionada">
              Operación: <span class="fw-semibold">—</span> • Contenedor/Caja:
              <span class="fw-semibold">—</span>
            </small>
          </div>
        </div>

        <!-- Tabla dentro de contenedor responsive -->
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" id="tablaRutasOperacion">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Transportista</th>
                <th style="width: 190px;">Fecha</th>
                <th>Comentario</th>
              </tr>
            </thead>
            <tbody id="tbodyRutasOperacion">
              <tr>
                <td colspan="6" class="text-center text-muted">Sin datos para mostrar.</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>


    <!-- ========================= -->
    <!-- RESULTADO MARÍTIMO (ESTATUS ACTUAL) -->
    <!-- ========================= -->
    <div class="row mt-3 d-none" id="bloqueResultadoMaritimo">
      <div class="col-lg-12">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="mb-0">
              <i class="bi bi-ship me-2"></i>Estatus de la Operación Marítima
            </h5>
            <small class="text-muted" id="lblOperacionMaritima">
              Operación: <span class="fw-semibold">—</span>
            </small>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" id="tablaOperacionMaritima">
            <thead class="table-light">
              <tr>
                <th style="width: 160px;">Operación</th>
                <th style="width: 180px;">Contenedor</th>
                <th style="width: 180px;">Estatus actual</th>
                <th>Comentario</th> 
              </tr>
            </thead>
            <tbody id="tbodyOperacionMaritima">
              <tr>
                <td colspan="5" class="text-center text-muted">Sin datos para mostrar.</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>


    <!-- Estado vacío general (opcional para cuando no hay búsqueda o no hay resultados) -->
    <div class="row mt-3 d-none" id="rastreoVacio">
      <div class="col-lg-12">
        <div class="alert alert-light border text-muted mb-0">
          Ingresa un número de operación para ver resultados.
        </div>
      </div>
    </div>

  </div>
</section>

<style>
/* Asegura que todos los elementos del input-group tengan la misma altura */
.input-group .input-group-text,
.input-group .form-control,
.input-group .btn {
  height: 48px;
  display: flex;
  align-items: center;
}

.input-group .input-group-text {
  padding: 0 1rem;
}

.input-group .btn {
  padding: 0 1.5rem;
  white-space: nowrap;
}
</style>
<script src="<?php echo BASE_URL; ?>Assets/Js/rastreo.js"></script>
