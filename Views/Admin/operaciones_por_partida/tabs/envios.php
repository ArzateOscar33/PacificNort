<style>
    .modal-xxl-wide {
        max-width: min(1650px, calc(100vw - 2rem));
    }

    .partidas_transito_panel {
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        background: #fff;
        height: 100%;
    }

    .partidas_transito_panel .panel-head {
        background: #1d324a;
        color: #fff;
        padding: .75rem 1rem;
        border-top-left-radius: .5rem;
        border-top-right-radius: .5rem;
        font-weight: 600;
        font-size: .95rem;
    }

    .partidas_transito_panel .panel-body {
        padding: 1rem;
    }

    .partidas_transito_producto_item {
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        padding: .75rem;
        margin-bottom: .75rem;
        background: #fafbfc;
    }

    .partidas_transito_producto_item:last-child {
        margin-bottom: 0;
    }

    .partidas_transito_producto_item .titulo {
        font-weight: 600;
        color: #1d324a;
    }

    .partidas_transito_producto_item .meta {
        font-size: .85rem;
        color: #6c757d;
    }

    .partidas_transito_producto_item.active {
        border-color: #198754;
        background: #f3fbf6;
    }

    .partidas_transito_resumen_chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .6rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
        background: #eef3f8;
        color: #1d324a;
    }

    .partidas_transito_box_tabla {
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        overflow: hidden;
    }

    .partidas_transito_tabla_detalle td,
    .partidas_transito_tabla_detalle th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .partidas_transito_sticky_top {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #fff;
    }

    .partidas_transito_modal_scroll {
        max-height: 58vh;
        overflow: auto;
    }

    .partidas_transito_fake-badge {
        font-size: .78rem;
        padding: .35rem .5rem;
        border-radius: .35rem;
    }

    .form-select {
        display: block;
        width: 100%;
        padding: .375rem 2.25rem .375rem .75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right .75rem center;
        background-size: 16px 12px;
        border: 1px solid #ced4da;
        border-radius: .375rem;
        appearance: none;
    }
</style>

<div class="container py-4 col-md-12">
    <div class="card shadow-sm">

        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i data-feather="truck" class="me-1"></i> Envíos por Ferro / Caja
            </h5>

            <button
                class="btn btn-success btn-sm"
                id="partidas_transito_btnNuevoEnvio"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#modalPartidasTransitoEnvio"
                data-partidas-transito-nuevo="1">
                <i data-feather="plus-circle" class="me-1"></i> Registrar nuevo envío
            </button>
        </div>

        <div class="card-body">

            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label for="partidas_transito_filtroFerro" class="form-label">Ferro / Caja</label>
                    <input type="text" id="partidas_transito_filtroFerro" class="form-control" placeholder="Buscar ferro...">
                </div>

                <div class="col-md-3">
                    <label for="partidas_transito_filtroFactura" class="form-label">Factura</label>
                    <input type="text" id="partidas_transito_filtroFactura" class="form-control" placeholder="Buscar factura...">
                </div>

                <div class="col-md-2">
                    <label for="partidas_transito_filtroTransportista" class="form-label">Transportista</label>
                    <select id="partidas_transito_filtroTransportista" class="form-control">
                        <option value="">Todos</option>
                        <?php if (!empty($data['transportistas'])): ?>
                            <?php foreach ($data['transportistas'] as $st): ?>
                                <option value="<?= (int)$st['id_transportista']; ?>">
                                    <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="partidas_transito_Estatus" class="form-label">Estatus</label>
                    <select id="partidas_transito_filtroEstatus" class="form-select">
                        <option value="" selected>Todos</option>
                        <option value="En camino">En camino</option>
                        <option value="Entregado">Entregado</option>
                        <option value="Programado">Programado</option>
                        <option value="Disponible en destino">Disponible en Destino</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label for="partidas_transito_perPage" class="form-label">Mostrar</label>
                    <select id="partidas_transito_perPage" class="form-control">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex justify-content-end">
                    <button class="btn btn-outline-secondary w-100" id="partidas_transito_btnRefrescar" type="button">
                        <i data-feather="refresh-cw" class="me-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive partidas_transito_box_tabla">
            <table class="table align-middle mb-0 partidas_transito_tabla_detalle" id="partidas_transito_tablaEnvios">
                <thead class="table-dark">
                    <tr class="text-center">
                        <th style="min-width:130px;">Ferro / Caja</th>
                        <th style="min-width:180px;">Cliente</th>
                        <th style="min-width:180px;">Transportista</th>
                        <th style="width:130px;">Fecha envío</th>
                        <th style="min-width:160px;">Destino</th>
                        <th style="width:130px;">Estatus</th>
                        <th style="min-width:180px;">Factura(s)</th>
                        <th style="min-width:260px;">Productos</th>
                        <th style="width:120px;">Cajas</th>
                        <th style="min-width:180px;">Notas</th>
                        <th style="width:120px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="partidas_transito_tbodyEnvios"></tbody>
            </table>
        </div>

        <div class="row align-items-center mt-3 g-2 px-3 pb-3">
            <div class="col-md-6">
                <div id="partidas_transito_metaResumen" class="small text-muted">
                    Mostrando 0 a 0 de 0 registros
                </div>
            </div>

            <div class="col-md-6">
                <nav class="d-flex justify-content-md-end justify-content-center">
                    <ul class="pagination pagination-sm mb-0" id="partidas_transito_paginacion"></ul>
                </nav>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalPartidasTransitoEnvio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xxl-wide modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <div class="d-flex flex-column">
                    <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
                        <i data-feather="send"></i>
                        <span>Registrar nuevo envío</span>
                    </h5>
                    <div class="small text-white-50 mt-1">
                        Captura encabezado del envío y agrega productos desde una factura
                    </div>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <form id="partidas_transito_formEnvio" autocomplete="off">

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="partidas_transito_panel">
                                <div class="panel-head">
                                    Datos del envío
                                </div>
                                <div class="panel-body">
                                    <input type="hidden" id="partidas_transito_id_envio" name="id_envio" value="">
                                    <div class="mb-3">
                                        <label for="partidas_transito_fisico_txt" class="form-label">Ferro / Caja</label>
                                        <div class="position-relative">
                                            <input type="hidden" id="partidas_transito_fisico_id">
                                            <input
                                                type="text"
                                                id="partidas_transito_fisico_txt"
                                                class="form-control"
                                                placeholder="Buscar ferro/caja...">
                                            <div
                                                id="partidas_transito_fisico_sug"
                                                class="list-group position-absolute w-100 z-3 d-none"
                                                style="z-index:999;"></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="partidas_transito_transportista_id" class="form-label">Transportista</label>
                                        <select id="partidas_transito_transportista_id" class="form-control">
                                            <option value="">Selecciona...</option>
                                            <?php if (!empty($data['transportistas'])): ?>
                                                <?php foreach ($data['transportistas'] as $st): ?>
                                                    <option value="<?= (int)$st['id_transportista']; ?>">
                                                        <?= htmlspecialchars($st['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="partidas_transito_fecha_envio" class="form-label">Fecha envío</label>
                                        <input type="date" id="partidas_transito_fecha_envio" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label for="partidas_transito_destino_id" class="form-label">Destino</label>
                                        <select id="partidas_transito_destino_id" class="form-control">
                                            <option value="">Selecciona...</option>
                                            <?php if (!empty($data['ciudades'])): ?>
                                                <?php foreach ($data['ciudades'] as $c): ?>
                                                    <option value="<?= (int)$c['id_ciudad']; ?>">
                                                        <?= htmlspecialchars($c['nombre_ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="partidas_transito_estatus" class="form-label">Estatus</label>
                                        <select id="partidas_transito_estatus" class="form-select">
                                            <option value="En camino" selected>En camino</option>
                                            <option value="Entregado">Entregado</option>
                                            <option value="Programado">Programado</option>
                                            <option value="Disponible en destino">Disponible en Destino</option>
                                            <option value="Cancelado">Cancelado</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="partidas_transito_nota" class="form-label">Notas</label>
                                        <textarea
                                            id="partidas_transito_nota"
                                            class="form-control"
                                            rows="3"
                                            placeholder="Observaciones generales del envío..."></textarea>
                                    </div>

                                    <div class="border rounded p-3 bg-light">
                                        <div class="small text-muted mb-2">Resumen del envío</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="partidas_transito_resumen_chip">
                                                <i data-feather="package" style="width:14px;height:14px;"></i>
                                                <span id="partidas_transito_resumenProductos">0</span> productos
                                            </span>

                                            <span class="partidas_transito_resumen_chip">
                                                <i data-feather="layers" style="width:14px;height:14px;"></i>
                                                <span id="partidas_transito_resumenCajas">0</span> cajas
                                            </span>

                                            <span class="partidas_transito_resumen_chip">
                                                <i data-feather="file-text" style="width:14px;height:14px;"></i>
                                                <span id="partidas_transito_resumenFacturas">0</span> factura(s)
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="partidas_transito_panel">
                                <div class="panel-head">
                                    Selección de factura y productos
                                </div>
                                <div class="panel-body">

                                    <div class="row g-3 mb-3 partidas_transito_sticky_top pb-2">
                                        <div class="col-md-5">
                                            <label for="partidas_transito_buscarFactura" class="form-label">Buscar factura</label>
                                            <div class="position-relative">
                                                <input type="hidden" id="partidas_transito_factura_id">
                                                <input
                                                    type="text"
                                                    id="partidas_transito_buscarFactura"
                                                    class="form-control"
                                                    placeholder="Escribe factura...">
                                                <div
                                                    id="partidas_transito_sugerenciasFacturas"
                                                    class="list-group position-absolute w-100 z-3 d-none"
                                                    style="z-index:999;"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="partidas_transito_factura_proveedor" class="form-label">Proveedor / referencia</label>
                                            <input type="text" id="partidas_transito_factura_proveedor" class="form-control" readonly>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="partidas_transito_factura_cajas" class="form-label">Cajas disponibles</label>
                                            <input type="text" id="partidas_transito_factura_cajas" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <div
                                        class="partidas_transito_modal_scroll pe-1"
                                        id="partidas_transito_listaProductos">
                                        <div class="text-center text-muted py-4">
                                            Selecciona una factura para ver sus productos.
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="fw-semibold mb-2">Productos agregados al envío</div>

                                        <div class="table-responsive border rounded">
                                            <table class="table align-middle mb-0">
                                                <thead class="table-dark">
                                                    <tr class="text-center">
                                                        <th style="min-width:130px;">Factura</th>
                                                        <th style="min-width:260px;" class="text-start">Producto</th>
                                                        <th style="width:150px;">UPC</th>
                                                        <th style="width:120px;">Cajas</th>
                                                        <th style="min-width:180px;">Notas</th>
                                                        <th style="width:90px;">Quitar</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="partidas_transito_tbodyDetalleSeleccion">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">
                                                            No has agregado productos al envío.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x-circle" class="me-1"></i> Cancelar
                </button>

                <button type="button" class="btn btn-success" id="partidas_transito_btnGuardarEnvio">
                    <i data-feather="save" class="me-1"></i> Guardar envío
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_envios_catalogo.js"></script>
<script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_por_partida/operaciones_partida_envios_registrar.js"></script>