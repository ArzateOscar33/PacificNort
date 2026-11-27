<!-- ======================================
     PACIFICNORT - RASTREO DE CARGA
     ====================================== -->

<section class="section pn-tracking"
  id="rastreo"
  aria-labelledby="pn-tracking-title">

  <div class="container">

    <!-- Buscador principal -->

    <div class="pn-tracking-panel" data-aos="fade-up">

      <div class="row align-items-center g-4">

        <!-- Presentación -->

        <div class="col-lg-5">

          <span class="pn-tracking-eyebrow">
            PacificNort Tracking
          </span>

          <h2 id="pn-tracking-title">
            ¿Dónde está
            <span>tu carga?</span>
          </h2>

          <p class="pn-tracking-description">
            Consulta el estatus de tus operaciones
            y contenedores desde nuestra plataforma
            de seguimiento logístico.
          </p>

          <div class="pn-tracking-features">

            <span>
              <i class="bi bi-shield-check"></i>
              Consulta segura
            </span>

            <span>
              <i class="bi bi-box-seam"></i>
              Seguimiento de contenedores
            </span>

          </div>

        </div>

        <!-- Formulario -->

        <div class="col-lg-7">

          <form
            id="pnTrackingForm"
            class="pn-tracking-search"
            data-url="<?= BASE_URL ?>Rastreo/buscarOperacion">

            <label for="inputNumeroGuia">
              Número de operación o BL
            </label>

            <div class="pn-tracking-input-group">

              <i class="bi bi-upc-scan"
                aria-hidden="true"></i>

              <input
                type="text"
                id="inputNumeroGuia"
                name="numero_operacion"
                placeholder="Ingresa tu número de operación"
                autocomplete="off"
                spellcheck="false"
                required>

              <button
                type="submit"
                id="btnRastrearEnvio">

                <i class="bi bi-search"
                  aria-hidden="true"></i>

                <span id="pnTrackingButtonText">
                  Rastrear
                </span>

                <span
                  id="pnTrackingSpinner"
                  class="spinner-border spinner-border-sm d-none"
                  aria-hidden="true">
                </span>

              </button>

            </div>

            <div class="pn-tracking-help">

              <span>
                <i class="bi bi-info-circle"></i>
                Consulta por operación o BL.
              </span>

              <button
                type="button"
                id="btnLimpiarRastreo"
                class="d-none">

                <i class="bi bi-x-circle"></i>
                Limpiar

              </button>

            </div>

          </form>

        </div>

      </div>

    </div>

    <!-- Mensajes de búsqueda -->

    <div
      id="pnTrackingMessage"
      class="pn-tracking-message d-none"
      role="status"
      aria-live="polite">

      <div class="pn-tracking-message-icon">
        <i id="pnTrackingMessageIcon"
          class="bi bi-info-circle"></i>
      </div>

      <div class="pn-tracking-message-content">

        <h3 id="pnTrackingMessageTitle"></h3>

        <p id="pnTrackingMessageText"></p>

        <a
          href="<?= BASE_URL ?>admin"
          id="pnTrackingLogin"
          class="pn-tracking-login d-none">

          Iniciar sesión
          <i class="bi bi-arrow-right"></i>

        </a>

      </div>

    </div>

    <!-- Resultados -->

    <div
      id="bloqueResultadoMaritimo"
      class="pn-tracking-results d-none">

      <div class="pn-tracking-results-header">

        <div>

          <span class="pn-tracking-result-label">
            Resultado de la consulta
          </span>

          <h3 id="lblOperacionMaritima"></h3>

        </div>

        <span id="pnTrackingCount"
          class="pn-tracking-count">
        </span>

      </div>

      <!-- Tarjetas generadas por JavaScript -->

      <div
        id="pnTrackingCards"
        class="row g-4">

      </div>

      <div class="pn-tracking-disclaimer">

        <i class="bi bi-info-circle"></i>

        La información corresponde a los últimos
        registros disponibles en nuestro sistema logístico.

      </div>

    </div>

  </div>

</section>

<script src="<?= BASE_URL ?>Assets/Js/rastreo.js"></script>