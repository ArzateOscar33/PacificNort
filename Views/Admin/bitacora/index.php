<?php include 'Views/Template/admin_header.php'; ?>

<div class="container py-4 col-md-12" id="bitacoraOperacionesWrapper">
    <div class="card shadow-sm">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i data-feather="activity" class="me-1"></i> Bitácora de Operaciones
            </h5>
            <button class="btn btn-light btn-sm" id="btnRefrescarBitacora">
                <i data-feather="refresh-cw" class="me-1"></i> Refrescar
            </button>
        </div>

        <div class="card-body">

            <!-- Filtros (estilo igual al módulo marítimo) -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <!-- Usuario -->
                <input id="buscarUsuarioLog"
                       class="form-control"
                       style="max-width:220px;"
                       placeholder="Buscar por usuario">

                <!-- Operación -->
                <input id="buscarOperacionLog"
                       class="form-control"
                       style="max-width:220px;"
                       placeholder="Buscar por ID de operación / folio">

                <!-- Acción -->
                <select id="filtroAccionLog" class="form-control" style="max-width:200px;">
                    <option value="">Acción (Todas)</option>
                    <option value="creacion">Creación</option>
                    <option value="actualizacion">Actualización</option>
                    <option value="eliminacion">Eliminación</option>
                </select>

                <!-- (Opcional) Exportar -->
                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-success" id="btnExportarExcelLog">
                        <i data-feather="file-text" class="me-1"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="btnExportarPDFLog">
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
                           id="filtroFechaInicioLog"
                           name="filtroFechaInicioLog"
                           class="form-control"
                           style="max-width:165px;"
                           aria-label="Fecha inicio bitácora">

                    <input type="date"
                           id="filtroFechaFinLog"
                           name="filtroFechaFinLog"
                           class="form-control"
                           style="max-width:165px;"
                           aria-label="Fecha fin bitácora">
                </div>

                <!-- “por página” alineado a la derecha (IDs únicos para este módulo) -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <label for="perPageLog" class="mb-0 small text-muted">Mostrar</label>
                    <select id="perPageLog" class="form-control" style="width: 90px;">
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
                <table class="table table-hover align-middle" id="tablaOperacionesLog">
                    <thead class="table-primary">
                        <tr class="text-center">
                            <th style="width:90px;">
                                <i data-feather="hash" class="me-1"></i> ID Log
                            </th>
                            <th style="width:110px;">
                                <i data-feather="file-text" class="me-1"></i> Operación
                            </th>
                            <th style="min-width:160px;">
                                <i data-feather="user" class="me-1"></i> Usuario
                            </th>
                            <th style="width:140px;">
                                <i data-feather="zap" class="me-1"></i> Acción
                            </th>
                            <th>
                                <i data-feather="align-left" class="me-1"></i> Descripción
                            </th>
                            <th style="width:190px;">
                                <i data-feather="clock" class="me-1"></i> Fecha / Hora
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tbodyOperacionesLog">
                        <!-- EJEMPLOS de la tabla operaciones_log (se reemplazan por datos reales con JS) -->
                        <tr>
                            <td class="text-center">1</td>
                            <td class="text-center">1</td>
                            <td>admin (id 1)</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i data-feather="plus-circle" class="me-1" style="width:14px;height:14px;"></i>
                                    creacion
                                </span>
                            </td>
                            <td>Operación creada</td>
                            <td>2025-10-28 16:47:57</td>
                        </tr>
                        <tr>
                            <td class="text-center">8</td>
                            <td class="text-center">5</td>
                            <td>admin (id 1)</td>
                            <td class="text-center">
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i data-feather="edit-3" class="me-1" style="width:14px;height:14px;"></i>
                                    actualizacion
                                </span>
                            </td>
                            <td>Operación actualizada (incluye bultos)</td>
                            <td>2025-10-28 17:00:58</td>
                        </tr>
                        <!-- FIN EJEMPLOS -->
                    </tbody>
                </table>

                <!-- Resumen + paginación (mismo estilo que marítimo) -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                    <div class="small text-muted">
                        <span id="metaResumenLog">Mostrando 0–0 de 0</span>
                    </div>

                    <nav aria-label="Paginación bitácora">
                        <ul id="paginacionLog" class="pagination pagination-sm mb-0">
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
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/bitacora.js"></script>
<script src="<?= BASE_URL ?>assets/js/modulosAdmin/exportarTablas.js"></script>
