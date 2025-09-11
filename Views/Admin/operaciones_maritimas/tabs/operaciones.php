<div class="container py-4 col-md-12">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i data-feather="anchor" class="me-1"></i> Operaciones Marítimas
            </h5>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalOperacionMaritima"
                id="btnNuevaOperacion">
                <i data-feather="plus-circle" class="me-1"></i> Nueva Operación
            </button>
        </div>

        <div class="card-body">

            <!-- Filtros (sin botón) -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <select id="filtroSubtipo" name="filtroSubtipo" class="form-control" style="max-width:240px;">
                    <option value="">Subtipo (Todos)</option>
                    <?php if (!empty($data['subtipos'])): ?>
                    <?php foreach ($data['subtipos'] as $st): ?>
                    <option value="<?= (int)$st['id_subtipo']; ?>">
                        <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <input id="buscarOperacion" class="form-control" style="max-width:260px;"
                    placeholder="Buscar por código, BL o contenedor">
                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-success" id="btnExportarExcelOperaciones">
                        <i data-feather="file-text" class="me-1"></i> Excel
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="btnExportarPDFOperaciones">
                        <i data-feather="file" class="me-1"></i> PDF
                    </button>
                </div>
                <!-- NUEVO: “por página” alineado a la derecha -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <label for="perPage" class="mb-0 small text-muted">Mostrar</label>
                    <select id="perPage" class="form-control" style="width: 90px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="small text-muted">por página</span>
                </div>

            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table   table-hover align-middle" id="tablaOperacionesMaritimasExportar">
                    <thead class="table-primary">
                        <tr class="text-center">
                            <th style="width:140px;">Código</th>
                            <th style="width:160px;">Subtipo</th>
                            <th style="width:120px;">ETA</th>
                            <th style="min-width:220px;">Contenedores</th>
                            <th style="width:180px;">BL</th>
                            <th>Puerto</th>
                            <th>Cliente</th>
                            <th>Naviera</th>
                            <th>Forwarder</th>

                            <th>Estatus</th>
                            <th style="width:120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaOperacionesMaritimas"></tbody>
                </table>
                <!-- Barra de paginación y totales (abajo) -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                    <!-- Resumen opcional -->
                    <div class="small text-muted">
                        <span id="metaResumen">Mostrando 0–0 de 0</span>
                    </div>

                    <nav aria-label="Paginación de operaciones">
                        <ul id="paginacion" class="pagination pagination-sm mb-0">
                            <!-- Se llena desde JS -->
                        </ul>
                    </nav>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- MODAL: Crear / Editar Operación Marítima -->
<div class="modal fade" id="modalOperacionMaritima" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i data-feather="plus-square" class="me-2"></i>
                    <span id="tituloModalOperacion">Nueva Operación Marítima</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <form id="formOperacionMaritima" autocomplete="off">
                    <input type="hidden" id="id_operacion" name="id_operacion" value="">

                    <div class="row g-3">
                        <!-- SUBTIPO (obligatorio) -->
                        <div class="col-md-4">
                            <label class="form-label">Subtipo</label>
                            <select id="subtipoOperacion" name="subtipo_operacion_id" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <?php if (!empty($data['subtipos'])): ?>
                                <?php 
                                // Si vienes en modo edición, puedes tener algo como:
                                $subtipoActual = isset($data['operacion']['subtipo_operacion_id']) 
                                                ? (int)$data['operacion']['subtipo_operacion_id'] 
                                                : 0;
                                foreach ($data['subtipos'] as $st): 
                                    $id  = (int)$st['id_subtipo'];
                                    $txt = htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8');
                                    $sel = ($subtipoActual === $id) ? ' selected' : '';
                                ?>
                                <option value="<?= $id; ?>" <?= $sel; ?>
                                    <?php // data-* opcionales (para usarlos luego sin XHR) ?>
                                    data-req-naviera="<?= isset($st['requiere_naviera']) ? (int)$st['requiere_naviera'] : 0; ?>"
                                    data-req-forwarder="<?= isset($st['requiere_forwarder']) ? (int)$st['requiere_forwarder'] : 0; ?>"
                                    data-puerto-default="<?= isset($st['puerto_arribo_default_id']) ? (int)$st['puerto_arribo_default_id'] : 0; ?>">
                                    <?= $txt; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Código interno -->
                        <div class="col-md-4">
                            <label class="form-label">Número de Operación</label>
                            <input type="text" id="numeroOperacion" name="numero_operacion" class="form-control"
                                placeholder="JL-61"  >
                        </div>

                        <!-- Estado (Estatus en BD) -->
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select id="estatusId" name="estatus_id" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <?php
                            $estatusActual = isset($data['operacion']['estatus_id'])
                                            ? (int)$data['operacion']['estatus_id']
                                            : 9; // default "Abierta" en operaciones
                            if (!empty($data['estatus'])):
                                foreach ($data['estatus'] as $es):
                                $id  = (int)$es['id_estatus'];
                                $txt = htmlspecialchars($es['nombre'], ENT_QUOTES, 'UTF-8');
                                $sel = ($estatusActual === $id) ? ' selected' : '';
                            ?>
                                <option value="<?= $id; ?>" <?= $sel; ?>><?= $txt; ?></option>
                                <?php
                                endforeach;
                            endif;
                            ?>
                            </select>
                        </div>

                        <!-- Fechas -->
                        <div class="col-md-3">
                            <label class="form-label">ETD</label>
                            <input type="date" id="etd" name="etd" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ETA</label>
                            <input type="date" id="eta" name="eta" class="form-control">
                        </div>

                        <!-- BL -->
                        <div class="col-md-3">
                            <label class="form-label">BL</label>
                            <input type="text" id="numeroBL" name="numero_bl" class="form-control"
                                      autocomplete="off" inputmode="latin" maxlength="40"
       pattern="[A-Za-z0-9]+"
       title="Solo letras y números, sin espacios ni caracteres especiales.">
                        </div>

                        <!-- Puerto de Arribo -->
                        <div class="col-md-3">
                            <label class="form-label">Puerto de Arribo</label>
                            <select id="puertoArribo" name="puerto_arribo_id" class="form-control" readonly disabled>
                                <option value="">Seleccione...</option>
                                <?php
                                $puertoActual = isset($data['operacion']['puerto_arribo_id'])
                                                ? (int)$data['operacion']['puerto_arribo_id']
                                                : 0;
                                if (!empty($data['puertos'])):
                                    foreach ($data['puertos'] as $p):
                                    $id  = (int)$p['id_puerto'];
                                    $txt = htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8');
                                    $sel = ($puertoActual === $id) ? ' selected' : '';
                                ?>
                                <option value="<?= $id; ?>" <?= $sel; ?>><?= $txt; ?></option>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </div>

                        <!-- Cliente -->
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <input type="hidden" id="clienteId" name="cliente_id">
                            <input type="text" id="clienteNombre" class="form-control"
                                placeholder="Escribe para buscar cliente...">
                            <div id="sugerenciasCliente" class="list-group"
                                style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                        </div>

                        <!-- Condicional: Naviera -->
                        <div class="col-md-3 " id="campoNaviera">
                            <label class="form-label">Naviera</label>
                            <select id="navieraId" name="naviera_id" class="form-control">
                                <option value="">Seleccione...</option>
                                <?php
                                $navieraActual = isset($data['operacion']['naviera_id'])
                                                ? (int)$data['operacion']['naviera_id']
                                                : 0;
                                if (!empty($data['navieras'])):
                                    foreach ($data['navieras'] as $n):
                                    $id  = (int)$n['id_naviera'];
                                    $txt = htmlspecialchars($n['nombre'], ENT_QUOTES, 'UTF-8');
                                    $sel = ($navieraActual === $id) ? ' selected' : '';
                                ?>
                                <option value="<?= $id; ?>" <?= $sel; ?>><?= $txt; ?></option>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </div>

                        <!-- Condicional: Forwarder -->
                        <div class="col-md-3 " id="campoForwarder">
                            <label class="form-label">Forwarder</label>
                            <select id="forwarderId" name="forwarder_id" class="form-control">
                                <option value="">Seleccione...</option>
                                <?php
                                    $forwarderActual = isset($data['operacion']['forwarder_id'])
                                                        ? (int)$data['operacion']['forwarder_id']
                                                        : 0;
                                    if (!empty($data['forwarders'])):
                                        foreach ($data['forwarders'] as $fw):
                                        $id  = (int)$fw['id_forwarder'];
                                        $txt = htmlspecialchars($fw['nombre'], ENT_QUOTES, 'UTF-8');
                                        $sel = ($forwarderActual === $id) ? ' selected' : '';
                                    ?>
                                <option value="<?= $id; ?>" <?= $sel; ?>><?= $txt; ?></option>
                                <?php
                                        endforeach;
                                    endif;
                                    ?>
                            </select>
                        </div>

                        <!-- ====== Repetidor de Contenedores Marítimos (INPUT + Sugerencias) ====== -->
                        <div class="col-6">
                            <label class="form-label d-flex align-items-center justify-content-between">
                                <span>Contenedores Marítimos</span>

                            </label>

                            <div id="contenedoresRepeater" class="vstack gap-2">
                                <!-- Item inicial -->
                                <div class="contenedor-item position-relative">
                                    <input type="hidden" name="contenedores_maritimos_ids[]"
                                        name="contenedores_maritimos_ids[]" class="contenedor-id">
                                    <input type="text" class="form-control contenedor-input"
                                        placeholder="Ej. MSKU1234567">
                                    <div class="list-group sugerencias-contenedor"
                                        style="position:absolute; z-index:1055; width:100%; display:none;"></div>

                                </div>
                            </div>

                            <!-- Plantilla oculta -->
                            <template id="contenedorTemplate">
                                <div class="contenedor-item position-relative">
                                    <input type="hidden" name="contenedores_maritimos_ids[]" class="contenedor-id">
                                    <input type="text" class="form-control contenedor-input"
                                        placeholder="Ej. MSKU1234567">
                                    <div class="list-group sugerencias-contenedor"
                                        style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                    <div class="mt-1 d-flex gap-2">

                                        <small class="text-muted flex-grow-1">Escribe para buscar. Si no existe, podrás
                                            registrarlo.</small>
                                    </div>
                                </div>
                            </template>

                        </div>
                        <div class="col-6">

                            <div id="" class="vstack gap-2">
                                <!-- Item inicial -->
                                <div class="col-md-6 ">

                                    <label class="form-label">Shipper</label>
                                    <select id="shipperId" name="shipper_id" class="form-control">
                                        <option value="">Seleccione...</option>
                                        <?php if (!empty($data['shippers'])): ?>
                                        <?php foreach ($data['shippers'] as $s): ?>
                                        <option value="<?= (int)$s['id_shipper']; ?>">
                                            <?= htmlspecialchars($s['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Plantilla oculta -->
                                <template id="contenedorTemplate">
                                    <div class="contenedor-item position-relative">
                                        <input type="hidden" name="contenedores_maritimos_ids[]" class="contenedor-id">
                                        <input type="text" class="form-control contenedor-input"
                                            placeholder="Ej. MSKU1234567">
                                        <div class="list-group sugerencias-contenedor"
                                            style="position:absolute; z-index:1055; width:100%; display:none;"></div>
                                        <div class="mt-1 d-flex gap-2">

                                            <small class="text-muted flex-grow-1">Escribe para buscar. Si no existe,
                                                podrás
                                                registrarlo.</small>
                                        </div>
                                    </div>
                                </template>

                            </div>
                        </div>
                            <!-- Notas -->
                            <div class="col-md-12">
                                <label class="form-label">Notas</label>
                                <textarea id="notas" name="notas" class="form-control" rows="2"
                                    placeholder="Observaciones generales"></textarea>
                            </div>
                        </div>
                </form>
            </div>

            <div class="modal-footer d-flex justify-content-between">

                <div class="d-flex gap-2 ">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i data-feather="x-circle" class="me-1"></i> Cancelar
                    </button>
                    <button type="button" id="btnGuardarOperacion" class="btn btn-primary" disabled>
                        <i data-feather="save" class="me-1"></i> Guardar
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
