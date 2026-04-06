<?php include 'Views/Template/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="mb-1">Administración de Reportes de Error</h3>
                    <p class="text-muted mb-0">Consulta, revisa y resuelve los reportes enviados por los usuarios.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form id="frmFiltroErroresAdmin" class="row g-3">
                <div class="col-md-3">
                    <label for="filtro_estatus" class="form-label">Estatus</label>
                    <select class="form-control" id="filtro_estatus" name="filtro_estatus">
                        <option value="">-- Todos --</option>
                        <option value="0">Sin resolver</option>
                        <option value="1">Resuelto</option>
                        <option value="2">Rechazado</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtro_modulo" class="form-label">Módulo</label>
                    <select class="form-control" id="filtro_modulo" name="filtro_modulo">
                        <option value="">-- Todos --</option>
                        <?php if (!empty($data['modulos_error'])) { ?>
                            <?php foreach ($data['modulos_error'] as $modulo) { ?>
                                <option value="<?php echo $modulo['id_modulo_error']; ?>">
                                    <?php echo htmlspecialchars($modulo['nombre']); ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtro_tipo" class="form-label">Tipo de Error</label>
                    <select class="form-control" id="filtro_tipo" name="filtro_tipo">
                        <option value="">-- Todos --</option>
                        <?php if (!empty($data['tipos_error'])) { ?>
                            <?php foreach ($data['tipos_error'] as $tipo) { ?>
                                <option value="<?php echo $tipo['id_tipo_error']; ?>">
                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtro_busqueda" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="filtro_busqueda" name="filtro_busqueda" placeholder="Descripción, razón o usuario">
                </div>

                <div class="col-md-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-primary" id="btnBuscarErroresAdmin">
                        <i data-feather="search"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltrosErroresAdmin">
                        <i data-feather="rotate-ccw"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Listado de Reportes</h5>
            <span class="badge bg-primary" id="totalReportesErrores">0 reportes</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="tblErroresAdmin">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Tipo</th>
                            <th>Módulo</th>
                            <th>Descripción</th>
                            <th>Reportado por</th>
                            <th>Fecha Reporte</th>
                            <th>Resuelto por</th>
                            <th>Fecha Resolución</th>
                            <th style="width: 130px;">Estatus</th>
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyErroresAdmin">
                        <tr>
                            <td colspan="10" class="text-center text-muted">No hay reportes para mostrar</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle / resolución -->
<div class="modal fade" id="modalResolverError" tabindex="-1" aria-labelledby="modalResolverErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="frmResolverErrorAdmin">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalResolverErrorLabel">Detalle del Reporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="id_reporte_admin" name="id_reporte">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Error</label>
                            <input type="text" class="form-control" id="detalle_tipo_error" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Módulo</label>
                            <input type="text" class="form-control" id="detalle_modulo_error" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Reportado por</label>
                            <input type="text" class="form-control" id="detalle_reportado_por" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha Reporte</label>
                            <input type="text" class="form-control" id="detalle_fecha_reporte" readonly>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Descripción del Error</label>
                            <textarea class="form-control" id="detalle_descripcion" rows="3" readonly></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Valor Propuesto</label>
                            <input type="text" class="form-control" id="detalle_valor_propuesto" readonly>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Razón del Error</label>
                            <textarea class="form-control" id="detalle_razon_error" rows="3" readonly></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="estatus_nuevo" class="form-label">Cambiar Estatus</label>
                            <select class="form-control" id="estatus_nuevo" name="estatus_nuevo" required>
                                <option value="">-- Seleccione --</option>
                                <option value="1">Resuelto</option>
                                <option value="2">Rechazado</option>
                            </select>
                        </div>


                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">
                        <i data-feather="check-circle"></i> Guardar Resolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src='<?php echo BASE_URL; ?>Assets/Js/ModulosAdmin/errores_admin.js'></script>
<?php include 'Views/Template/admin_footer.php'; ?>