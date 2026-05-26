<style>
    /* =========================================================
       EVENTOS OP. POR PARTIDA — TABLA TIPO EXCEL
       Vista limpia sin modales.
       ========================================================= */

    :root {
        --evOpPartida-col-op-w: 170px;
        --evOpPartida-col-fact-w: 180px;
        --evOpPartida-col-cli-w: 240px;
        --evOpPartida-col-des-w: 190px;
        --evOpPartida-col-tra-w: 200px;
        --evOpPartida-col-fer-w: 180px;
        --evOpPartida-col-evt-w: 145px;
    }

    .eventos-op-partida-wrapper {
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }

    #tablaEventosOpPartida {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        min-width: 100%;
    }

    #tablaEventosOpPartida th,
    #tablaEventosOpPartida td {
        padding: .55rem .75rem;
        white-space: nowrap;
        vertical-align: middle;
    }

    #tablaEventosOpPartida th:nth-child(1),
    #tablaEventosOpPartida td:nth-child(1) {
        min-width: var(--evOpPartida-col-op-w);
        width: var(--evOpPartida-col-op-w);
    }

    #tablaEventosOpPartida th:nth-child(2),
    #tablaEventosOpPartida td:nth-child(2) {
        min-width: var(--evOpPartida-col-fact-w);
        width: var(--evOpPartida-col-fact-w);
    }

    #tablaEventosOpPartida th:nth-child(3),
    #tablaEventosOpPartida td:nth-child(3) {
        min-width: var(--evOpPartida-col-cli-w);
        width: var(--evOpPartida-col-cli-w);
    }

    #tablaEventosOpPartida th:nth-child(4),
    #tablaEventosOpPartida td:nth-child(4) {
        min-width: var(--evOpPartida-col-des-w);
        width: var(--evOpPartida-col-des-w);
    }

    #tablaEventosOpPartida th:nth-child(5),
    #tablaEventosOpPartida td:nth-child(5) {
        min-width: var(--evOpPartida-col-tra-w);
        width: var(--evOpPartida-col-tra-w);
    }

    #tablaEventosOpPartida th:nth-child(6),
    #tablaEventosOpPartida td:nth-child(6) {
        min-width: var(--evOpPartida-col-fer-w);
        width: var(--evOpPartida-col-fer-w);
    }

    #tablaEventosOpPartida thead th:nth-child(n+7),
    #tablaEventosOpPartida tbody td:nth-child(n+7) {
        min-width: var(--evOpPartida-col-evt-w);
        width: var(--evOpPartida-col-evt-w);
        text-align: center;
    }

    /* =========================================================
       CELDAS EDITABLES TIPO EXCEL
       El JS usará estas clases.
       ========================================================= */

    .evop-cell {
        cursor: pointer;
        position: relative;
        transition: background-color .15s ease, box-shadow .15s ease;
    }

    .evop-cell:hover {
        background-color: rgba(13, 110, 253, .08);
    }

    .evop-cell.is-editing {
        padding: .25rem !important;
        background-color: #fff;
        box-shadow: inset 0 0 0 2px rgba(13, 110, 253, .45);
    }

    .evop-cell.is-saving {
        opacity: .65;
        pointer-events: none;
    }

    .evop-cell.is-error {
        background-color: rgba(220, 53, 69, .12);
        box-shadow: inset 0 0 0 2px rgba(220, 53, 69, .45);
    }

    .evop-cell-empty {
        color: #adb5bd;
        font-weight: 600;
    }

    .evop-cell-date {
        font-weight: 600;
        color: #212529;
    }

    .evop-cell-input {
        width: 100%;
        min-width: 128px;
        border: 0;
        outline: none;
        background: transparent;
        text-align: center;
        font-size: .875rem;
        padding: .25rem;
    }

    .evop-cell-input:focus {
        outline: none;
        box-shadow: none;
    }

    .evop-help-text {
        font-size: .78rem;
        color: #6c757d;
    }

    /* =========================================================
   CELDAS EDITABLES TIPO EXCEL
   MISMO ESTILO QUE EVENTOS TERRESTRES
   ========================================================= */

    .evfer-date-cell {
        cursor: cell;
        position: relative;
        transition:
            background-color .15s ease,
            box-shadow .15s ease,
            color .15s ease;
    }

    .evfer-date-cell:hover {
        background-color: rgba(13, 110, 253, .08);
        box-shadow: inset 0 0 0 1px rgba(13, 110, 253, .35);
    }

    .evfer-date-cell:focus {
        outline: none;
        background-color: rgba(13, 110, 253, .10);
        box-shadow: inset 0 0 0 2px rgba(13, 110, 253, .65);
    }

    .evfer-date-cell.evfer-empty {
        color: #6c757d;
        font-style: italic;
    }

    .evfer-date-cell.evfer-saving {
        background-color: rgba(255, 193, 7, .18);
        box-shadow: inset 0 0 0 2px rgba(255, 193, 7, .55);
    }

    .evfer-date-cell.evfer-saved {
        background-color: rgba(25, 135, 84, .12);
        box-shadow: inset 0 0 0 2px rgba(25, 135, 84, .45);
    }

    .evfer-date-cell.evfer-error {
        background-color: rgba(220, 53, 69, .12);
        box-shadow: inset 0 0 0 2px rgba(220, 53, 69, .55);
    }

    .evfer-date-input {
        width: 100%;
        min-width: 110px;
        height: 30px;
        border: 1px solid #0d6efd;
        border-radius: .375rem;
        padding: .15rem .35rem;
        font-size: .85rem;
        text-align: center;
        outline: none;
    }

    .evfer-date-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .18);
    }

    .evfer-cell-status {
        position: absolute;
        right: 4px;
        bottom: 2px;
        font-size: .65rem;
        line-height: 1;
        opacity: .75;
        pointer-events: none;
    }
</style>

<div class="container py-4 col-md-12">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">
                <i data-feather="file-text" class="me-1"></i>
                Eventos Operaciones por Partida
            </h5>

            <div class="evop-help-text text-white-50">
                Click para editar · Enter guarda · Tab guarda y avanza · Delete limpia
            </div>
        </div>

        <div class="card-body">

            <!-- Filtros superiores -->
            <div class="row g-3 align-items-end mb-3">

                <!-- Filtro oculto por envío/operación. Se conserva para compatibilidad con JS. -->
                <input type="hidden" id="eventosOpPartidaFiltroOpId" value="">

                <div class="col-md-2">
                    <label for="eventosOpPartidaFiltroFactura" class="form-label mb-1">Factura</label>
                    <input
                        type="text"
                        id="eventosOpPartidaFiltroFactura"
                        name="eventosOpPartidaFiltroFactura"
                        class="form-control"
                        placeholder="Escribe para buscar"
                        autocomplete="off">
                </div>

                <div class="col-md-2">
                    <label for="eventosOpPartidaFiltroFerro" class="form-label mb-1">Ferro / Caja</label>
                    <input
                        type="text"
                        id="eventosOpPartidaFiltroFerro"
                        name="eventosOpPartidaFiltroFerro"
                        class="form-control"
                        placeholder="FXEU..."
                        autocomplete="off">
                </div>

                <div class="col-md-2">
                    <label for="eventosOpPartidaFiltroTransportista" class="form-label mb-1">Transportista</label>
                    <select
                        class="form-control"
                        id="eventosOpPartidaFiltroTransportista"
                        name="eventosOpPartidaFiltroTransportista">
                        <option value="">Transportista (Todos)</option>

                        <?php if (!empty($data['transportistas'])): ?>
                            <?php foreach ($data['transportistas'] as $st): ?>
                                <option value="<?= (int)$st['id_transportista']; ?>">
                                    <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="eventosOpPartidaFiltroDestino" class="form-label mb-1">Destino</label>
                    <select
                        class="form-control"
                        id="eventosOpPartidaFiltroDestino"
                        name="eventosOpPartidaFiltroDestino">
                        <option value="">Destino (Todos)</option>

                        <?php if (!empty($data['ciudades'])): ?>
                            <?php foreach ($data['ciudades'] as $c): ?>
                                <option value="<?= (int)$c['id_ciudad']; ?>">
                                    <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex justify-content-md-end gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success"
                        id="btnExportarExcelEventosLogisticosOpPartida">
                        <i data-feather="file-text" class="me-1"></i>
                        Excel
                    </button>
                </div>

                <div class="col-12 d-flex align-items-center justify-content-end gap-2">
                    <label for="evOpPartidaPerPage" class="mb-0 small text-muted">Mostrar</label>

                    <select id="evOpPartidaPerPage" class="form-control" style="width: 90px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                        <option value="10000000">Todos</option>
                    </select>

                    <span class="small text-muted">por página</span>
                </div>
            </div>

            <!-- Tabla -->
            <div class="eventos-op-partida-wrapper">
                <table class="table align-middle table-bordered-pacific-p" id="tablaEventosOpPartida">
                    <thead class="table-dark">
                        <tr id="theadEventosOpPartida" class="text-center">
                            <th class="text-center">Operación</th>
                            <th class="text-center">Factura</th>
                            <th class="text-center">Cliente</th>
                            <th class="text-center">Destino</th>
                            <th class="text-center">Transportista</th>
                            <th class="text-center">Caja / Ferro</th>
                            <!-- JS: columnas dinámicas de tipos de evento -->
                        </tr>
                    </thead>

                    <tbody id="tbodyEventosOpPartida">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Cargando eventos...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación + resumen -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                <div class="small text-muted">
                    <span id="evOpPartidaMetaResumen">Mostrando 0–0 de 0</span>
                </div>

                <nav aria-label="Paginación de eventos operación por partida">
                    <ul id="evOpPartidaPaginacion" class="pagination pagination-sm mb-0">
                        <!-- JS -->
                    </ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<script>
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
</script>