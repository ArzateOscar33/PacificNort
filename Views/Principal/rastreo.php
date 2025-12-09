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
        Consulta  el estatus y ubicación de tus contenedores.
      </p>
    </div>

    <!-- BUSCADOR -->
    <div class="row justify-content-center">
      <div class="col-lg-8 col-md-10" data-aos="fade-up" data-aos-delay="100">

        <div class="input-group mt-2">
          
          <!-- Icono -->
          <span class="input-group-text d-flex align-items-center">
            <i class="bi bi-upc-scan"></i>
          </span>

          <!-- Input -->
          <input
            type="text"
            class="form-control"
            id="inputNumeroGuia"
            placeholder="Ingresa tu número de operación (ej. FO-03)">

          <!-- Botón -->
          <button class="btn btn-primary d-flex align-items-center" type="button" id="btnRastrearEnvio">
            <i class="bi bi-search me-1"></i> Rastrear
          </button>

        </div>

      </div>
    </div>


    <!-- CONTENEDOR DE RUTAS (SIN CARD) -->
    <div class="row mt-3">
      <div class="col-lg-12">

        <!-- Encabezado simple -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="mb-0">
              <i class="bi bi-signpost-split me-2"></i>Rutas de la Operación
            </h5>
            <small class="text-muted" id="lblOperacionSeleccionada">
              Operación: <span class="fw-semibold">FO-03</span> • Contenedor/Caja:
              <span class="fw-semibold">585</span>
            </small>
          </div>
        </div>

        <!-- Tabla dentro de un contenedor responsive -->
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" id="tablaRutasOperacion">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Transportista</th>
                <th style="width: 150px;">Fecha / Hora</th>
                <th>Comentario</th>
              </tr>
            </thead>
            <tbody id="tbodyRutasOperacion">
              <!-- EJEMPLOS ESTÁTICOS -->
              <tr>
                <td>1</td>
                <td>Pantaco</td>
                <td>Mexicali</td>
                <td>COMANDOS</td>
                <td>2025-12-07 08:30</td>
                <td>Salida de patio ferroviario.</td>
              </tr>
              <tr>
                <td>2</td>
                <td>Mexicali</td>
                <td>Tijuana</td>
                <td>TRANSP. NORTE</td>
                <td>2025-12-08 11:10</td>
                <td>Llegada a patio Tijuana.</td>
              </tr>
              <!-- FIN EJEMPLOS -->
            </tbody>
          </table>
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
<script src="<?php echo BASE_URL; ?>assets/js/rastreo.js"></script>