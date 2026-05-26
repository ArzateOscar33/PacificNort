<div class="container py-4 col-md-12">
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <!-- Encabezado + botón -->
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
                <div>
                    <h3 class="mb-1">Costos por Operación por Partida y Domésticos</h3>
                    <small class="text-muted">Consulta y administra los costos a nivel factura de operación por partida y domésticos.</small>
                </div>

                <div class="ms-auto col-md-12 d-flex justify-content-end mb-3">
                    <button
                        class="btn btn-success"
                        id="costosPartidaBtnNuevo"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCostoPartida">
                        <i data-feather="plus"></i> Añadir Costo
                    </button>
                </div>

                <div class="container col-md-12">

                    <!-- Filtros -->
                    <div class="row justify-content-end align-items-center mb-2">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
                            <div class="d-flex gap-2">
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    id="costosPartidaBuscar"
                                    name="costosPartidaBuscar"
                                    placeholder="Buscar concepto o comentario…">

                                <select
                                    id="costosPartidaFiltroMoneda"
                                    name="costosPartidaFiltroMoneda"
                                    class="form-control"
                                    style="max-width:140px;">
                                    <option value="">Moneda: Todas</option>
                                    <option value="PESOS">PESOS</option>
                                    <option value="DLLS">DLLS</option>
                                </select>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <label class="small mb-0" for="costosPartidaPerPage">Por página:</label>
                                <select
                                    id="costosPartidaPerPage"
                                    name="costosPartidaPerPage"
                                    class="form-control form-control-sm"
                                    style="width:90px;">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="gap-2 col-md-12 d-flex align-items-center justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-success"
                                        id="btnExportarExcelCostosPartida">
                                        <i data-feather="file-text" class="me-1"></i> Excel
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        id="btnExportarPDFCostosPartida">
                                        <i data-feather="file" class="me-1"></i> PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sugerencia de factura -->
                    <div class="row flex-wrap gap-2 align-items-center mb-2">
                        <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
                            <label for="costosPartidaFiltroFacturaNombre" class="form-label mb-1">Factura / Operación por partida</label>
                            <div class="position-relative">
                                <input type="hidden" id="costosPartidaFiltroFacturaId" name="costosPartidaFiltroFacturaId">
                                <input
                                    type="text"
                                    id="costosPartidaFiltroFacturaNombre"
                                    name="costosPartidaFiltroFacturaNombre"
                                    class="form-control"
                                    placeholder="Escribe para buscar factura"
                                    autocomplete="off">
                                <div
                                    id="costosPartidaFiltroFacturaSugerencias"
                                    class="list-group"
                                    style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                            </div>
                            <div class="form-text" id="costosPartidaFiltroFacturaMeta"></div>
                        </div>
                    </div>

                    <!-- Caja/Ferro de la factura -->
                    <div class="row flex-wrap gap-2 align-items-center mb-2">
                        <div class="w-100 w-md-auto col-md-12" style="min-width:320px;">
                            <label for="costosPartidaFiltroFerroId" class="form-label mb-1">Caja/Ferro</label>
                            <select
                                id="costosPartidaFiltroFerroId"
                                name="costosPartidaFiltroFerroId"
                                class="form-control">
                                <option value="">Seleccione una factura primero</option>
                            </select>
                            <div class="form-text" id="costosPartidaFiltroFerroMeta">
                                Selecciona la factura para cargar sus ferros/cajas.
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de vista de totales -->
                    <div class="row flex-wrap gap-2 justify-content-end align-items-center mb-2">
                        <div class="d-flex flex-wrap align-items-end mb-2 gap-2">
                            <div>
                                <label class="form-label small mb-1" for="costosPartidaMonedaVista">Mostrar totales en</label>
                                <select
                                    id="costosPartidaMonedaVista"
                                    name="costosPartidaMonedaVista"
                                    class="form-control form-control-sm"
                                    style="width:140px;">
                                    <option value="MXN">MXN (pesos)</option>
                                    <option value="USD">USD (dólares)</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label small mb-1" for="costosPartidaTipoCambio">Tipo de cambio</label>
                                <div class="input-group input-group-sm" style="width:160px;">
                                    <span class="input-group-text">$</span>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        id="costosPartidaTipoCambio"
                                        name="costosPartidaTipoCambio"
                                        class="form-control"
                                        value="17.00">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Totales -->
            <div class="row g-3 mb-4" id="costosPartidaCards">
                <div class="col-12 col-md-6">
                    <div class="bg-primary text-white p-3 rounded shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-uppercase small">Total factura</h6>
                            <i data-feather="dollar-sign"></i>
                        </div>

                        <div class="mt-2 h3 mb-1" id="costosPartidaTotalOperacion">$ 0.00</div>
                        <small class="opacity-75 d-block mb-2">Costos registrados a la factura</small>

                        <div class="d-flex justify-content-between small">
                            <span class="opacity-75">Abonos</span>
                            <strong id="costosPartidaAbonosOperacion">$ 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="opacity-75">Balance</span>
                            <span><span id="costosPartidaBalanceOperacion" class="badge bg-light text-dark">$ 0.00</span></span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="bg-success text-white p-3 rounded shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-uppercase small">Total general</h6>
                            <i data-feather="trending-up"></i>
                        </div>

                        <div class="mt-2 h3 mb-1" id="costosPartidaTotalGeneral">$ 0.00</div>
                        <small class="opacity-75 d-block mb-2">Ganancia neta</small>

                        <div class="d-flex justify-content-between small">
                            <span class="opacity-75">Abonos totales</span>
                            <strong id="costosPartidaTotalAbonosGeneral">$ 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="opacity-75">Costos totales</span>
                            <strong id="costosPartidaTotalCostosGeneral">$ 0.00</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle table-bordered-pacific" id="tablaCostosPartidaExportar">
                    <thead class="table-light">
                        <tr>
                            <th style="width:110px;">Fecha</th>
                            <th>Concepto</th>
                            <th class="text-end" style="width:140px;">Monto</th>
                            <th class="text-center" style="width:120px;">Estatus</th>
                            <th class="text-center" style="width:180px;">Comentario</th>
                            <th class="text-center" style="width:120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCostosPartida">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Selecciona una factura para ver sus costos.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small" id="costosPartidaMeta">Mostrando 0-0 de 0</div>
                <nav>
                    <ul id="costosPartidaPaginacion" class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Agregar / Editar Costo -->
<div class="modal fade" id="modalCostoPartida" tabindex="-1" aria-labelledby="modalCostoPartidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCostoPartidaLabel">
                    <i data-feather="plus-circle" class="me-1"></i> Añadir Costo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="formCostoPartida">
                <div class="modal-body">
                    <input type="hidden" id="costosPartidaRowId" name="row_id">

                    <div class="mb-3">
                        <div class="position-relative">
                            <label for="costosPartidaFacturaNombre" class="form-label">Factura / Operación por partida</label>
                            <input type="hidden" id="costosPartidaFacturaId" name="factura_id">
                            <input
                                type="text"
                                id="costosPartidaFacturaNombre"
                                name="costosPartidaFacturaNombre"
                                class="form-control"
                                placeholder="Escribe para buscar factura..."
                                autocomplete="off">
                            <div
                                id="costosPartidaSugerenciasFacturas"
                                class="list-group"
                                style="position:absolute; z-index:1061; width:100%; display:none;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="costosPartidaFerroId" class="form-label">Caja/Ferro ligado</label>
                        <select
                            id="costosPartidaFerroId"
                            name="contenedor_fisico_id"
                            class="form-control"
                            required>
                            <option value="">Seleccione una factura primero</option>
                        </select>
                        <div class="form-text" id="costosPartidaFerroMeta">
                            Selecciona una factura para cargar sus ferros/cajas.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="costosPartidaTipoMovimientoId" class="form-label">Tipo de Costo</label>
                        <select
                            id="costosPartidaTipoMovimientoId"
                            name="tipo_movimiento_id"
                            class="form-control"
                            required>
                            <option value="">Seleccione un tipo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="costosPartidaMonto" class="form-label">Monto</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="costosPartidaMonto"
                            name="monto"
                            class="form-control"
                            required
                            placeholder="Ej: 500.00">
                    </div>

                    <div class="mb-3">
                        <label for="costosPartidaMoneda" class="form-label">Moneda</label>
                        <select
                            id="costosPartidaMoneda"
                            name="costosPartidaMoneda"
                            class="form-control"
                            readonly
                            disabled>
                            <option value="">Seleccione</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="costosPartidaPagado" class="form-label">Estatus</label>
                        <select
                            id="costosPartidaPagado"
                            name="costosContenedoresPagado"
                            class="form-control">
                            <option value="0">Pendiente</option>
                            <option value="1">Pagado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="costosPartidaComentario" class="form-label">Comentarios (opcional)</label>
                        <textarea
                            id="costosPartidaComentario"
                            name="comentario"
                            rows="2"
                            class="form-control"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        id="btnCancelarCostoPartida"
                        data-bs-dismiss="modal">
                        <i data-feather="x"></i> Cancelar
                    </button>

                    <button
                        type="button"
                        id="btnGuardarCostoPartida"
                        class="btn btn-success">
                        <i data-feather="save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_costos_catalogo.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_costos_registrar.js"></script>
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

    forzarMayusculas("costosPartidaFiltroFacturaNombre");
    forzarMayusculas("costosPartidaFacturaNombre");
</script>