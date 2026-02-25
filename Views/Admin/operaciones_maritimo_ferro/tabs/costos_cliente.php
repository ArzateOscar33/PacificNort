<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <!-- Encabezado -->
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-3">
                <div>
                    <h3 class="mb-1">Costos por Cliente</h3>
                    <small class="text-muted">
                        Consulta todos los costos por operación de un cliente específico .
                    </small>
                </div>


            </div>

            <!-- Filtros -->
            <div class="row g-2 align-items-end mb-3">



                <!-- Cliente (buscar) 
                <div class="col-12 col-md-4 position-relative">
                    <label class="form-label mb-1">Cliente</label>
                    <input type="text" class="form-control" id="costosCliente_clienteTerm"
                        placeholder="Buscar cliente (Nombre)">
                    <input type="hidden" id="costosCliente_clienteId" value="">

                      //Sugerencias  
                    <div class="list-group position-absolute w-100 shadow-sm d-none"
                        id="costosCliente_clienteSug"
                        style="z-index: 1050; max-height: 260px; overflow:auto;">
                         
                    </div>

                </div> -->

                <div class="col-12 col-md-4 position-relative">
                    <label class="form-label mb-1">Cliente</label>
                    <select id="clienteId_cc" name="clienteId_cc" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php if (!empty($data['clientes'])): ?>
                            <?php foreach ($data['clientes'] as $c): ?>
                                <option value="<?= (int)$c['id_cliente']; ?>">
                                    <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Rango fechas -->
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">Fecha inicio</label>
                    <input type="date" class="form-control" id="costosCliente_fechaInicio">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">Fecha fin</label>
                    <input type="date" class="form-control" id="costosCliente_fechaFin">
                </div>
                <div class="d-flex gap-2 justify-content-end align-items-center col-md-4">
                    <button class="btn btn-outline-secondary" id="costosCliente_btnLimpiar">
                        <i data-feather="x-circle"></i> Limpiar Filtros
                    </button>

                </div>
                <div class="col-12 col-md-12 row">
                    <!-- Broker -->
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Broker</label>
                        <label class="form-label">Broker</label>
                        <select id="brokerId_cc" name="brokerId_cc" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php if (!empty($data['brokers'])): ?>
                                <?php foreach ($data['brokers'] as $b): ?>
                                    <option value="<?= (int)$b['id_broker']; ?>">
                                        <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Transportista -->
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Transportista</label>
                        <select id="transportistaId_cc" name="transportistaId_cc" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php if (!empty($data['transportistas'])): ?>
                                <?php foreach ($data['transportistas'] as $t): ?>
                                    <option value="<?= (int)$t['id_transportista']; ?>">
                                        <?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Estatus pago -->
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Estatus</label>
                        <select class="form-control" id="costosCliente_estatusPago">
                            <option value="">Todos</option>
                            <option value="0">Pendientes</option>
                            <option value="1">Pagados</option>
                        </select>
                    </div>

                    <!-- Buscar texto (op / contenedor / concepto) -->
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">Buscar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i data-feather="filter"></i></span>
                            <input type="text" class="form-control" id="costosCliente_term"
                                placeholder="Operación / Contenedor / Concepto">
                        </div>
                    </div>
                    <!-- Per page -->
                    <div class=" col-md-2">
                        <label class="form-label mb-1">Mostrar</label>
                        <select class="form-control" id="costosCliente_perPage">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="10000000">Todos</option>
                        </select>
                    </div>

                </div>
                <!-- Resumen -->
                <div class="col-12 col-md-12">
                    <div class="alert alert-light border mb-0 py-2">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                            <span class="badge bg-secondary text-white" id="costosCliente_metaTotalOps">Ops: 0</span>
                            <span class="badge bg-primary text-white" id="costosCliente_metaTotalConceptos">Conceptos: 0</span>
                            <span class="badge bg-warning text-dark" id="costosCliente_metaPendientes">Pendientes: $0</span>
                            <span class="badge bg-success text-white" id="costosCliente_metaPagados">Pagados: $0</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tabla -->
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover align-middle mb-0 table-bordered" id="costosCliente_table">
                    <thead class="table-light">
                        <tr class="text-nowrap">
                            <th style="min-width:120px;">Operación</th>
                            <th style="min-width:140px;">Contenedor</th>
                            <th style="min-width:160px;">Transportista</th>
                            <th style="min-width:160px;">Broker</th>
                            <th style="min-width:120px;">Estatus</th>
                            <th style="min-width:140px;">Cita Puerto</th>
                            <th style="min-width:90px;" class="text-center">ISF</th>

                            <th style="min-width:220px;">Concepto</th>
                            <th style="min-width:120px;" class="text-end">Monto</th>
                            <th style="min-width:110px;" class="text-center">Pagado</th>
                            <th style="min-width:120px;" class="text-end">Moneda</th>
                            <th style="min-width:160px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="costosCliente_tbody">



                        <!-- Placeholder vacío -->
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i data-feather="inbox"></i>
                                <div class="mt-2">Sin datos. Aplica filtros y presiona “Buscar”.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                <div class="small text-muted" id="costosCliente_metaPaginacion">Mostrando 0 de 0</div>
                <ul class="pagination pagination-sm mb-0" id="costosCliente_paginacion"></ul>
            </div>

        </div>
    </div>
</div>


<!-- Modal: Nuevo / Editar Concepto (solo vista) -->
<div class="modal fade" id="modalCostoClienteConcepto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-success text-white">
                <div class="d-flex align-items-center gap-2">
                    <i data-feather="dollar-sign"></i>
                    <div class="lh-1">
                        <h5 class="modal-title mb-1">Concepto de Costo</h5>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="costosCliente_modal_operacionId" value="">
                <input type="hidden" id="costosCliente_modal_conceptoId" value="">

                <div class="row g-2">
                    <div class="col-12 col-md-7">
                        <label class="form-label mb-1">Concepto</label>
                        <input type="text" class="form-control" id="costosCliente_modal_concepto"
                            placeholder="Ej. Maniobra, Demoras, Flete, Agencia, etc.">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Monto</label>
                        <input type="number" step="0.01" class="form-control text-end" id="costosCliente_modal_monto"
                            placeholder="0.00">
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Pagado</label>
                        <select class="form-control" id="costosCliente_modal_pagado">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-1">Notas</label>
                        <textarea class="form-control" id="costosCliente_modal_notas" rows="3"
                            placeholder="Notas opcionales..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i data-feather="x"></i> Cancelar
                </button>
                <button class="btn btn-success" id="costosCliente_modal_guardar">
                    <i data-feather="save"></i> Guardar
                </button>
            </div>

        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/costos_clientes.js"></script>
<script>