<section id="rastreo" class="py-5 bg-light">
  <!-- Reset LOCAL solo para esta sección -->
  <style>
    /* Quitar posiciones raras heredadas de style.css solo dentro de #rastreo */
    #rastreo .card,
    #rastreo .card-body,
    #rastreo .row,
    #rastreo [class*="col-"],
    #rastreo .list-group,
    #rastreo .list-group-item {
      position: static !important;
      float: none !important;
      top: auto !important;
      left: auto !important;
      right: auto !important;
    }
  </style>

  <div class="container">

    <!-- Título -->
    <div class="row justify-content-center mb-4">
      <div class="col-md-8 text-center">
        <h2 class="h3 mb-2">
          <i class="bi bi-geo-alt me-2"></i>Rastreo de Carga
        </h2>
        <p class="text-muted mb-0">
          Consulta en tiempo real el estatus y ubicación de tus envíos.
        </p>
      </div>
    </div>

    <!-- Tarjeta principal -->
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm border-0">
          <div class="card-body">

            <!-- Buscador -->
            <div class="row g-2 align-items-center mb-4">
              <div class="col-md-9">
                <div class="input-group">
                  <span class="input-group-text bg-white">
                    <i class="bi bi-upc-scan"></i>
                  </span>
                  <input
                    type="text"
                    id="inputGuia"
                    class="form-control"
                    placeholder="Ingresa tu número de guía"
                    aria-label="Número de guía">
                </div>
                <small class="form-text text-muted">
                  Ejemplo: JMX000865070272
                </small>
              </div>
              <div class="col-md-3 d-grid">
                <button class="btn btn-danger" id="btnRastrear">
                  <i class="bi bi-search me-1"></i>Rastrear
                </button>
              </div>
            </div>

            <div class="row g-4">
              <!-- Lista de guías -->
              <div class="col-md-4">
                <h6 class="fw-semibold mb-2">Números de guía</h6>
                <div class="list-group small" id="listaGuias">
                  <button type="button"
                    class="list-group-item list-group-item-action active js-chip-guia">
                    JMX000865070272
                  </button>
                  <button type="button"
                    class="list-group-item list-group-item-action js-chip-guia">
                    JMX000865070273
                  </button>
                  <button type="button"
                    class="list-group-item list-group-item-action js-chip-guia">
                    JMX000865070274
                  </button>
                </div>
              </div>

              <!-- Detalle -->
              <div class="col-md-8">
                <!-- Info básica -->
                <div class="border rounded p-3 mb-3 bg-light">
                  <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                      <h6 class="mb-1 fw-semibold">Información de guía</h6>
                      <small class="text-muted" id="labelGuiaActual">
                        <i class="bi bi-tag me-1"></i>JMX000865070272
                      </small>
                    </div>
                    <div class="text-end small">
                      <div>
                        <span class="fw-semibold">Origen: </span>
                        <span class="text-primary" id="origenGuia">El Salto</span>
                      </div>
                      <div>
                        <span class="fw-semibold">Destino: </span>
                        <span class="text-success" id="destinoGuia">Tijuana</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Timeline simple usando list-group -->
                <ul class="list-group small" id="timelineGuia">
                  <li class="list-group-item border-start border-3 border-success">
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">2025-12-08 15:04:33</span>
                      <span class="badge bg-success">Entregado</span>
                    </div>
                    <div class="mt-1">
                      <strong>[Tijuana]</strong> Envío entregado. La persona que recibió el envío fue Claudia M.
                      Si requieres mayor información, contáctanos al 55 7100 0147.
                    </div>
                  </li>

                  <li class="list-group-item border-start border-3 border-danger">
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">2025-12-08 09:39:18</span>
                      <span class="badge bg-danger">En ruta</span>
                    </div>
                    <div class="mt-1">
                      <strong>[Tijuana]</strong> El mensajero se encuentra en camino a tu domicilio.
                    </div>
                  </li>

                  <li class="list-group-item border-start border-3 border-danger">
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">2025-12-07 04:05:20</span>
                      <span class="badge bg-danger">En tránsito</span>
                    </div>
                    <div class="mt-1">
                      <strong>[Tijuana]</strong> Tu envío ha salido del centro de distribución.
                    </div>
                  </li>
                </ul>
              </div>
            </div>

          </div><!-- card-body -->
        </div><!-- card -->
      </div>
    </div>

  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const chips = document.querySelectorAll('.js-chip-guia');
    const labelGuia = document.getElementById('labelGuiaActual');
    const inputGuia = document.getElementById('inputGuia');
    const btnRastrear = document.getElementById('btnRastrear');

    chips.forEach(chip => {
      chip.addEventListener('click', function() {
        chips.forEach(c => c.classList.remove('active'));
        this.classList.add('active');

        const guiaNum = this.textContent.trim();
        labelGuia.innerHTML = `<i class="bi bi-tag me-1"></i>${guiaNum}`;
      });
    });

    btnRastrear.addEventListener('click', function() {
      const guia = inputGuia.value.trim();
      if (guia) {
        //console.log('Rastrear:', guia);
        // lógica real aquí
      }
    });
  });
</script>