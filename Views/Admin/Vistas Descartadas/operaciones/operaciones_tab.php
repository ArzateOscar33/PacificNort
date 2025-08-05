<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4 col-md-12">
    <h3>Detalle de Operación # </h3>
    <ul class="nav nav-tabs" id="operacionTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="tab-detalles" data-bs-toggle="tab" href="#detalles" role="tab">Detalles Generales</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-contenedores" data-bs-toggle="tab" href="#contenedores" role="tab">Contenedores</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-costos" data-bs-toggle="tab" href="#costos" role="tab">Costos por Contenedor</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-trazabilidad" data-bs-toggle="tab" href="#trazabilidad" role="tab">Trazabilidad / Mov.</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-documentos" data-bs-toggle="tab" href="#documentos" role="tab">Documentos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-eventos" data-bs-toggle="tab" href="#eventos" role="tab">Eventos Logísticos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-log" data-bs-toggle="tab" href="#log" role="tab">Bitácora / Log</a>
        </li>
    </ul>

    <div class="tab-content" id="operacionTabsContent">
        <!-- Aquí se cargará el contenido de cada tab -->
    </div>
</div>
<div class="tab-pane fade show active p-3" id="detalles" role="tabpanel">
    <h5>Información General</h5>
    <div class="row">
        <div class="col-md-4"><strong>Tipo de operación:</strong> Exportación</div>
        <div class="col-md-4"><strong>Cliente:</strong> Contenedores SA</div>
        <div class="col-md-4"><strong>Fecha:</strong> 2025-08-04</div>
    </div>
    <hr>
    <h6>Detalles logísticos</h6>
    <p><strong>Ruta:</strong> Puerto Veracruz → Laredo</p>
    <p><strong>Transportista:</strong> Logística Express</p>
    <p><strong>Observaciones:</strong> Entrega programada para el 08 de agosto</p>
</div>
<div class="tab-pane fade p-3" id="contenedores" role="tabpanel">
    <h5>Contenedores Asignados</h5>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Cliente</th>
                <th>Dimensiones</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>CONT-001</td>
                <td>Marítimo</td>
                <td>Contenedores SA</td>
                <td>40ft HQ</td>
                <td>En tránsito</td>
            </tr>
            <!-- Más contenedores... -->
        </tbody>
    </table>
</div>
<div class="tab-pane fade p-3" id="costos" role="tabpanel">
    <h5>Costos por Contenedor</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Contenedor</th>
                <th>Tipo Costo</th>
                <th>Monto</th>
                <th>Moneda</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>CONT-001</td>
                <td>Flete</td>
                <td>$4,500</td>
                <td>MXN</td>
                <td>Ruta Veracruz - Laredo</td>
            </tr>
        </tbody>
    </table>
</div>
<div class="tab-pane fade p-3" id="trazabilidad" role="tabpanel">
    <h5>Trazabilidad del Contenedor</h5>
    <ul class="timeline">
        <li><strong>01 Ago</strong> - Salida de bodega origen</li>
        <li><strong>02 Ago</strong> - En ruta hacia puerto</li>
        <li><strong>03 Ago</strong> - Ingreso a puerto Veracruz</li>
        <li><strong>04 Ago</strong> - Embarque confirmado</li>
    </ul>
</div>
<div class="tab-pane fade p-3" id="documentos" role="tabpanel">
    <h5>Documentos Adjuntos</h5>
    <ul>
        <li><a href="#">Factura_123.pdf</a></li>
        <li><a href="#">BL_456.pdf</a></li>
        <li><a href="#">PackingList_789.pdf</a></li>
    </ul>
</div>
<div class="tab-pane fade p-3" id="eventos" role="tabpanel">
    <h5>Eventos Logísticos</h5>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Evento</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>2025-08-02</td>
                <td>Carga completada</td>
                <td>Contenedor lleno en origen</td>
            </tr>
            <tr>
                <td>2025-08-03</td>
                <td>Salida de puerto</td>
                <td>Confirmado por aduana</td>
            </tr>
        </tbody>
    </table>
</div>
<div class="tab-pane fade p-3" id="log" role="tabpanel">
    <h5>Bitácora de Cambios</h5>
    <ul>
        <li><strong>[2025-08-01]</strong> - Usuario admin creó la operación.</li>
        <li><strong>[2025-08-02]</strong> - Se asignó transportista: Logística Express.</li>
        <li><strong>[2025-08-03]</strong> - Agregado documento: BL_456.pdf.</li>
    </ul>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>