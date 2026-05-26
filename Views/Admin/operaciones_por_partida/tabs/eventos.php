<style>
    /* =========================================================
   EVENTOS OP. POR PARTIDA — SOLO ANCHOS / LEGIBILIDAD
   No cambia colores ni efectos, solo espaciamiento/ancho.
   ========================================================= */

    /* Evita que el navegador “apriete” columnas */
    #tablaEventosOpPartida {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        /* clave: respeta min-width y permite scroll horizontal */
    }

    /* Base: un poco más de padding + no wraps */
    #tablaEventosOpPartida th,
    #tablaEventosOpPartida td {
        padding: .55rem .75rem;
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Asegura que el wrapper permita scroll horizontal si hay muchas columnas */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* ====== ANCHOS DE COLUMNAS FIJAS ====== */
    :root {
        --evOpPartida-col-op-w: 170px;
        --evOpPartida-col-cli-w: 240px;
        --evOpPartida-col-est-w: 170px;
        --evOpPartida-col-des-w: 190px;
        --evOpPartida-col-tra-w: 200px;
        --evOpPartida-col-fer-w: 180px;
        --evOpPartida-col-evt-w: 140px;
    }

    /* Fijas */
    #tablaEventosOpPartida th:nth-child(1),
    #tablaEventosOpPartida td:nth-child(1) {
        min-width: var(--evOpPartida-col-op-w);
        width: var(--evOpPartida-col-op-w);
    }

    #tablaEventosOpPartida th:nth-child(2),
    #tablaEventosOpPartida td:nth-child(2) {
        min-width: var(--evOpPartida-col-cli-w);
        width: var(--evOpPartida-col-cli-w);
    }

    #tablaEventosOpPartida th:nth-child(3),
    #tablaEventosOpPartida td:nth-child(3) {
        min-width: var(--evOpPartida-col-est-w);
        width: var(--evOpPartida-col-est-w);
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

    /* Dinámicas (eventos): desde la columna 7 en adelante */
    #tablaEventosOpPartida thead th:nth-child(n+7),
    #tablaEventosOpPartida tbody td:nth-child(n+7) {
        min-width: var(--evOpPartida-col-evt-w);
        width: var(--evOpPartida-col-evt-w);
        text-align: center;
    }
</style>

<div class="container py-4 col-md-12">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i data-feather="file-text" class="me-1"></i> Eventos Operaciones por Partida
            </h5>
        </div>

        <div class="card-body">

            <!-- Filtros superiores -->
            <div class="row g-3 align-items-end mb-3">
                <!-- Operación con sugerencias -->
                <div class="col-md-3 d-none">
                    <label for="eventosOpPartidaFiltroOpNombre" class="form-label mb-1">Operación</label>
                    <div class="position-relative">
                        <input type="hidden" id="eventosOpPartidaFiltroOpId">
                        <input type="text" id="eventosOpPartidaFiltroOpNombre" class="form-control"
                            placeholder="Escribe para buscar" autocomplete="off">
                        <div id="eventosOpPartidaFiltroOpSugerencias" class="list-group"
                            style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                    </div>
                    <div class="form-text" id="eventosOpPartidaFiltroOpMeta"></div>
                </div>

                <div class="col-md-2">
                    <label for="eventosOpPartidaFiltroFactura">Factura</label>
                    <input type="text" id="eventosOpPartidaFiltroFactura" name="eventosOpPartidaFiltroFactura" class="form-control"
                        placeholder="Escribe para buscar" autocomplete="off">
                </div>

                <div class="col-md-2">
                    <label for="eventosOpPartidaFiltroFerro">Ferro / Caja</label>
                    <input type="text" id="eventosOpPartidaFiltroFerro" name="eventosOpPartidaFiltroFerro" class="form-control"
                        placeholder="FXEU..." autocomplete="off">
                </div>

                <div class="col-md-2">
                    <label for="eventosOpPartidaFiltroTransportista">Transportista</label>
                    <select class="form-control" id="eventosOpPartidaFiltroTransportista" name="eventosOpPartidaFiltroTransportista">
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
                    <label for="eventosOpPartidaFiltroDestino">Destino</label>
                    <select class="form-control" id="eventosOpPartidaFiltroDestino" name="eventosOpPartidaFiltroDestino">
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

                <!-- Exportaciones -->
                <div class="row d-flex col-md-12 align-items-end justify-content-end">
                    <div class="col-md-1 d-flex">
                        <button class="btn btn-sm btn-outline-success" id="btnExportarExcelEventosLogisticosOpPartida">
                            <i data-feather="file-text" class="me-1"></i> Excel
                        </button>
                    </div>
                </div>

                <!-- perPage -->
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
            <div class="table-responsive">
                <table class="table align-middle table-bordered-pacific-p" id="tablaEventosOpPartida">
                    <thead class="table-dark">
                        <tr id="theadEventosOpPartida" class="text-center">
                            <th style="min-width: 140px;" class="text-center">Operación</th>
                            <th style="min-width: 180px;" class="text-center">Caja / Ferro</th>
                            <!-- Dinámicos (JS): una <th> por cada tipo de evento -->
                        </tr>
                    </thead>
                    <tbody id="tbodyEventosOpPartida">
                        <!-- JS: filas dinámicas -->
                    </tbody>
                </table>

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
</div>

<!-- MODAL: Crear / Editar Evento Logístico -->
<div class="modal fade" id="modalDetallesLogisticosOpPartida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i data-feather="plus-square" class="me-2"></i>
                    <span id="modalTituloDetallesOpPartida">Registrar Evento</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="formEventosLogisticosOpPartida" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" id="idEventoOpPartida" name="idEventoOpPartida" value="">
                    <input type="hidden" id="eventoContenedorTipoOpPartida">

                    <div class="row g-3 mb-2">
                        <!-- Operación con sugerencias -->
                        <div class="col-md-6 d-none">
                            <label for="eventoOperacionNombreOpPartida" class="form-label">Operación</label>
                            <div class="position-relative">
                                <input type="hidden" id="eventoOperacionIdOpPartida" name="eventoOperacionIdOpPartida">
                                <input type="text" id="eventoOperacionNombreOpPartida" class="form-control"
                                    placeholder="Escribe para buscar" autocomplete="off" required>
                                <div id="eventoOperacionSugerenciasOpPartida" class="list-group"
                                    style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                            </div>
                            <div class="form-text" id="eventoOperacionMetaOpPartida"></div>
                        </div>

                        <!-- Factura -->
                        <div class="col-md-6">
                            <label for="eventoFacturaNombreOpPartida" class="form-label">Factura</label>
                            <div class="position-relative">
                                <input type="hidden" id="eventoFacturaOperacionIdOpPartida" name="eventoFacturaOperacionIdOpPartida">
                                <input type="text" id="eventoFacturaNombreOpPartida" class="form-control"
                                    placeholder="Escribe para buscar" autocomplete="off" readonly>
                                <div id="eventoFacturaSugerenciasOpPartida" class="list-group"
                                    style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                            </div>
                            <div class="form-text">Se listan las facturas de la operación seleccionada.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="tipoEventoIdOpPartida" class="form-label">Tipo de evento</label>
                            <select id="tipoEventoIdOpPartida" name="tipoEventoIdOpPartida" class="form-control">
                                <option value="">Selecciona...</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="fechaEventoLogisticoOpPartida" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="fechaEventoLogisticoOpPartida"
                                name="fechaEventoLogisticoOpPartida" required>
                        </div>

                        <div class="col-md-4">
                            <label for="comentarioEventoLogisticoOpPartida" class="form-label">Comentarios</label>
                            <input type="text" id="comentarioEventoLogisticoOpPartida" name="comentarioEventoLogisticoOpPartida"
                                class="form-control" placeholder="Opcional">
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x-circle" class="me-1"></i> Cancelar
                        </button>
                        <button type="submit" id="btnSubmitEventoLogisticoOpPartida" class="btn btn-primary">
                            <i data-feather="save" class="me-1"></i> Guardar
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- MODAL celda -->
<div class="modal fade" id="modalEvtCellOpPartida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalEvtCellTitleOpPartida">Evento</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEvtCellOpPartida">
                <div class="modal-body">
                    <input type="hidden" id="cellOpIdOpPartida">
                    <input type="hidden" id="cellCfoIdOpPartida">
                    <input type="hidden" id="cellEvtIdOpPartida">
                    <input type="hidden" id="cellIdEventoOpPartida">

                    <div class="mb-2">
                        <label class="form-label">Operación</label>
                        <input id="cellOpTxtOpPartida" class="form-control" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Ferro/Caja / Marítimo</label>
                        <input id="cellCtnTxtOpPartida" class="form-control" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tipo de evento</label>
                        <input id="cellEvtTxtOpPartida" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Fecha</label>
                        <input type="date" id="cellFechaOpPartida" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Comentario</label>
                        <input id="cellComentarioOpPartida" class="form-control" placeholder="Opcional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnCellDeleteOpPartida" class="btn btn-outline-danger d-none">Eliminar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<script>
    function forzarMayusculas(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener("input", function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    }

    // Uso
    forzarMayusculas("eventoOperacionNombreOpPartida");
</script>