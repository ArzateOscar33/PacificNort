<?php include 'Views/Template/admin_header.php'; ?>

<div class="container py-4 col-md-12" id="bitacoraOpPartidaWrapper">
    <div class="card shadow-sm">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i data-feather="activity" class="me-1"></i> Bitácora de Operaciones por Partida
            </h5>
            <button class="btn btn-light btn-sm" id="btnRefrescarBitacoraOpPartida">
                <i data-feather="refresh-cw" class="me-1"></i> Refrescar
            </button>
        </div>

        <div class="card-body">

            <!-- FILTROS -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <!-- Usuario -->
                <input id="buscarUsuarioBitacoraPartida"
                    class="form-control"
                    style="max-width:220px;"
                    placeholder="Buscar por usuario">

                <!-- Entidad / ID / referencia -->
                <input id="buscarEntidadBitacoraPartida"
                    class="form-control"
                    style="max-width:240px;"
                    placeholder="Buscar por entidad / ID / referencia">

                <!-- Módulo -->
                <select id="filtroModuloBitacoraPartida" class="form-control" style="max-width:220px;">
                    <option value="">Módulo (Todos)</option>
                    <option value="op_partida_facturas">Facturas</option>
                    <option value="op_partida_envios">Envíos</option>
                    <option value="op_partida_costos">Costos</option>
                    <option value="op_partida_productos">Productos</option>
                    <option value="op_partida_evidencias">Evidencias</option>
                </select>

                <!-- Acción -->
                <select id="filtroAccionBitacoraPartida" class="form-control" style="max-width:200px;">
                    <option value="">Acción (Todas)</option>
                    <option value="crear">Creación</option>
                    <option value="actualizacion">Actualización</option>
                    <option value="baja_logica">Baja lógica</option>
                    <option value="reactivacion">Reactivación</option>
                    <option value="subir_imagen">Subir imagen</option>
                    <option value="eliminar_imagen">Eliminar imagen</option>
                </select>

                <!-- Exportar -->
                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-success" id="btnExportarExcelBitacoraPartida">
                        <i data-feather="file-text" class="me-1"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="btnExportarPDFBitacoraPartida">
                        <i data-feather="file" class="me-1"></i> PDF
                    </button>
                </div>

                <!-- Rango de fechas -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i data-feather="calendar"></i>
                        <span class="small text-muted">Rango:</span>
                    </div>

                    <input type="date"
                        id="filtroFechaInicioBitacoraPartida"
                        name="filtroFechaInicioBitacoraPartida"
                        class="form-control"
                        style="max-width:165px;"
                        aria-label="Fecha inicio bitácora op partida">

                    <input type="date"
                        id="filtroFechaFinBitacoraPartida"
                        name="filtroFechaFinBitacoraPartida"
                        class="form-control"
                        style="max-width:165px;"
                        aria-label="Fecha fin bitácora op partida">
                </div>

                <!-- Por página -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <label for="perPageBitacoraPartida" class="mb-0 small text-muted">Mostrar</label>
                    <select id="perPageBitacoraPartida" class="form-control" style="width: 90px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="small text-muted">por página</span>
                </div>
            </div>

            <!-- TABLA -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tablaBitacoraOpPartida">
                    <thead class="table-primary">
                        <tr class="text-center">
                            <th style="width:90px;">
                                <i data-feather="hash" class="me-1"></i> ID
                            </th>
                            <th style="width:170px;">
                                <i data-feather="grid" class="me-1"></i> Módulo
                            </th>
                            <th style="width:150px;">
                                <i data-feather="zap" class="me-1"></i> Acción
                            </th>
                            <th style="width:170px;">
                                <i data-feather="tag" class="me-1"></i> Entidad
                            </th>
                            <th style="width:100px;">
                                <i data-feather="hash" class="me-1"></i> ID Entidad
                            </th>
                            <th style="min-width:160px;">
                                <i data-feather="user" class="me-1"></i> Usuario
                            </th>
                            <th>
                                <i data-feather="align-left" class="me-1"></i> Detalle
                            </th>
                            <th style="width:190px;">
                                <i data-feather="clock" class="me-1"></i> Fecha / Hora
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBitacoraOpPartida">
                        <!-- Se llena con JS -->
                    </tbody>
                </table>

                <!-- RESUMEN + PAGINACIÓN -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                    <div class="small text-muted">
                        <span id="metaResumenBitacoraPartida">Mostrando 0–0 de 0</span>
                    </div>

                    <nav aria-label="Paginación bitácora op partida">
                        <ul id="paginacionBitacoraPartida" class="pagination pagination-sm mb-0">
                            <!-- Se llenará desde JS -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<?php include 'Views/Template/admin_footer.php'; ?>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/bitacora_op_partida.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/exportarTablas.js"></script>