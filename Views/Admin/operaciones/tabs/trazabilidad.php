<div class="container p-4 col-md-12">
    <h4 class="mb-4">Trazabilidad</h4>

    <div class="mb-4">
        <label for="contenedor" class="form-label">Contenedor</label>
        <select id="contenedor" class="form-control">
            <option selected>BSLU1234567</option>
            <option>TRLU7654321</option>
        </select>
    </div>

    <ul class="timeline mb-5">
        <li class="mb-4">
            <div class="d-flex align-items-center">
                <div class="me-3 text-primary"><i data-feather="truck"></i></div>
                <div>
                    <strong>Entrega</strong>
                    <div class="text-muted small">23 de abril de 2024</div>
                    <div class="text-muted small">Chicago, IL</div>
                </div>
            </div>
        </li>
        <li class="mb-4">
            <div class="d-flex align-items-center">
                <div class="me-3 text-secondary"><i data-feather="info"></i></div>
                <div>
                    <strong>Inspección</strong>
                    <div class="text-muted small">21 de abril de 2024</div>
                </div>
            </div>
        </li>
        <li>
            <div class="d-flex align-items-center">
                <div class="me-3 text-info"><i data-feather="arrow-up"></i></div>
                <div>
                    <strong>Carga</strong>
                    <div class="text-muted small">Los Angeles, CA</div>
                </div>
            </div>
        </li>
    </ul>

    <h5 class="mb-3">Añadir Evento</h5>
    <form id="formEvento">
        <div class="mb-3">
            <label for="tipo_evento" class="form-label">Tipo de Evento</label>
            <select id="tipo_evento" name="tipo_evento" class="form-control" onchange="toggleUbicacionInputs()">
                <option value="">Selecciona un evento</option>
                <option value="Carga">Carga</option>
                <option value="Inspección">Inspección</option>
                <option value="Entrega">Entrega</option>
                <option value="Cambio de Ruta">Cambio de Ruta</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="fecha_evento" class="form-label">Fecha</label>
            <input type="date" id="fecha_evento" name="fecha_evento" class="form-control">
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="2" class="form-control"></textarea>
        </div>

        <div class="row" id="ubicacionFields" style="display: none;">
            <div class="col-md-6 mb-3">
                <label for="origen" class="form-label">Origen</label>
                <input type="text" id="origen" name="origen" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="destino" class="form-label">Destino</label>
                <input type="text" id="destino" name="destino" class="form-control">
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-2">
            <i data-feather="save" class="me-1"></i>Guardar Evento
        </button>
    </form>
</div>

<style>
    .timeline {
        list-style: none;
        padding-left: 0;
        border-left: 2px solid #dee2e6;
        margin-left: 1.5rem;
    }
    .timeline li {
        position: relative;
        padding-left: 1rem;
    }
    .timeline li::before {
        content: "";
        position: absolute;
        top: 0.3rem;
        left: -9px;
        width: 12px;
        height: 12px;
        background-color: #0d6efd;
        border-radius: 50%;
    }
</style>

<script>
    feather.replace();

    function toggleUbicacionInputs() {
        const tipo = document.getElementById("tipo_evento").value;
        const ubicacion = document.getElementById("ubicacionFields");
        if (tipo === "Carga" || tipo === "Entrega" || tipo === "Cambio de Ruta") {
            ubicacion.style.display = "flex";
        } else {
            ubicacion.style.display = "none";
        }
    }
</script>
