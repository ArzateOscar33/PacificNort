<div class="container py-4 col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i data-feather="box" class="me-2"></i>Ferros en Operación</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFerroOP">
            <i data-feather="plus"></i>Añadir Ferro
        </button>
    </div>

    <!-- Filtros -->
    <div class="row g-3 align-items-end mb-4">
        <div class="col-12 col-md-4">
            <label for="buscarFerroOP" class="form-label">Buscar (Operación / Cliente / Ferro / Marítimo /
                Transportista)</label>
            <input type="text" id="buscarFerroOP" class="form-control"
                placeholder="Ej. LB-01, Juan, FX001, MG001">
        </div>

        <div class="col-12 col-md-5">
            <label class="form-label d-flex justify-content-between">
                <span>Rango de fechas</span>
            </label>
            <div class="d-flex gap-2 flex-wrap">
                <input type="date" id="fechaDesdeFerroOP" class="form-control w-50" aria-label="Desde">
                <input type="date" id="fechaHastaFerroOP" class="form-control w-50" aria-label="Hasta">
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="d-flex flex-wrap justify-content-between justify-content-md-end align-items-center gap-2">
                <div class="btn-group" role="group" aria-label="Exportaciones">
                    <button class="btn btn-sm btn-outline-success" id="btnExcelFerroOP">
                        <i data-feather="file-text" class="me-1"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="btnPdfFerroOP">
                        <i data-feather="file" class="me-1"></i> PDF
                    </button>
                </div>

                <div class="d-flex align-items-center ms-md-2">
                    <label for="perPageFerroOP" class="mb-0 small text-muted me-2">Mostrar</label>
                    <select id="perPageFerroOP" class="form-control" style="width: 90px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle" id="tablaFerroOP">
            <thead class="table-light">
                <tr>
                    <th>Número de Operación</th>
                    <th>Contenedor Marítimo</th>
                    <th>Bultos (Marítimo)</th>
                    <th>Cliente</th> 
                    <th>Transportista</th>
                    <th>Caja / Ferro</th>
                    <th>División de Bultos</th>
                    <th>Destino</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyFerroOP"></tbody>
        </table>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="small text-muted">
                <span id="metaResumenFerroOP">Mostrando 0-0 de 0</span>
            </div>
            <nav aria-label="Paginación Ferros en Operación">
                <ul id="paginacionFerroOP" class="pagination pagination-sm mb-0"></ul>
            </nav>
        </div>
    </div>

    <!-- Modal: Agregar Contenedor a la Operación -->
    <div class="modal fade" id="modalFerroOP" tabindex="-1" aria-labelledby="modalFerroOPLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalFerroOPLabel">
                        <i data-feather="plus-circle" class="me-1"></i> Añadir Ferro a la Operación
                    </h5>
                </div>
                <div class="modal-body">
                    <form id="formFerroOP" autocomplete="off"> 

                        <input type="hidden" id="rowIdFerroOP" name="rowIdFerroOP">

                        <!-- Operación + Cliente -->
                        <div class="row mb-3">
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Operación</label>
                                <input type="hidden" id="operacionIdFerroOP" name="operacionIdFerroOP">
                                <input type="text" id="operacionNombreFerroOP" class="form-control"
                                    placeholder="Escribe para buscar operación…">
                                <div id="sugOperacionesFerroOP" class="list-group"
                                    style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                <small class="text-muted">Sugerencia: número de operación, BL o cliente.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <input type="hidden" id="clienteIdFerroOP" name="clienteIdFerroOP">
                                <input type="text" id="clienteNombreFerroOP" name="clienteNombreFerroOP"
                                    class="form-control" placeholder="" readonly>
                            </div>
                        </div>

                        <!-- Marítimo + totales -->
                        <div class="row mb-3">
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Contenedor Marítimo</label>
                                <input type="hidden" id="contenedorMaritimoIdFerroOP"
                                    name="contenedorMaritimoIdFerroOP">
                                <input type="text" id="contenedorMaritimoNombreFerroOP" class="form-control"
                                    placeholder="Escribe para buscar marítimo…">
                                <div id="sugMaritimosFerroOP" class="list-group"
                                    style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                <small class="text-muted">Filtra por número (ej. MG001…)</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Bultos (Marítimo)</label>
                                <input type="number" id="bultosMaritimoFerroOP" class="form-control" placeholder="0"
                                    readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bultos restantes</label>
                                <input type="number" id="bultosRestantesFerroOP" class="form-control" placeholder="0"
                                    readonly>
                            </div>
                        </div>

                        <!-- Ferro/caja + asignación -->
                        <div class="row mb-3">
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Caja / Ferro</label>
                                <input type="hidden" id="contenedorFerroIdFerroOP" name="contenedorFerroIdFerroOP">
                                <input type="text" id="contenedorFerroNombreFerroOP" class="form-control"
                                    placeholder="Escribe para buscar ferro/caja…">
                                <div id="sugFerrosFerroOP" class="list-group"
                                    style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                <small class="text-muted">Ej. FX001…</small>
                            </div>

                            <div class="col-md-3">
                                <label for="bultosAsignadosFerroOP" class="form-label">Bultos (asignados al
                                    ferro)</label>
                                <input type="number" id="bultosAsignadosFerroOP" name="bultosAsignadosFerroOP"
                                    class="form-control" min="0" placeholder="0">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label d-block">Validación</label>
                                <span id="badgeSaldoFerroOP" class="badge bg-secondary text-white">Saldo: 0</span>
                            </div>
                        </div>

                        
                        <div class="row mb-3">
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Transportista</label>
                                <input type="hidden" id="transportistaIdFerroOP" name="transportistaIdFerroOP">
                                <input type="text" id="transportistaNombreFerroOP" name="transportistaNombreFerroOP"
                                    class="form-control" placeholder="Escribe para buscar transportista…">
                                <div id="sugTransportistasFerroOP" class="list-group"
                                    style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                
                            </div>
                            <div class="col-md-6">
                                 <div class="col-md-12 position-relative">
                                <label class="form-label">Destino</label>
                                <input type="hidden" id="destinoIdFerroOP" name="destinoIdFerroOP">
                                <input type="text" id="destinoNombreFerroOP" name="destinoNombreFerroOP"
                                    class="form-control" placeholder="Escribe para buscar Destino…">
                                <div id="destinoFerroOP" class="list-group"
                                    style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                
                            </div>

                            </div>
                        </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="comentariosFerroOP" class="form-label">Comentarios</label>
                                        <textarea id="comentariosFerroOP" name="comentariosFerroOP" class="form-control"
                                            rows="3"></textarea>
                                    </div>
                                </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i data-feather="x"></i> Cancelar
                    </button>
                    <button type="submit" form="formFerroOP" class="btn btn-primary">
                        <i data-feather="save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



    <script>
        
        feather.replace();
       
    </script>
    <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/ferrosOperacion.js"></script>
    <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/ferroscatalogo.js"></script>