<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4 col-md-12">
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h4 class="card-title">Movimientos por Contenedor</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID Movimiento</th>
                            <th>Contenedor Físico</th>
                            <th>Tipo Movimiento</th>
                            <th>Monto</th>
                            <th>Moneda</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí van los registros desde PHP -->
                    </tbody>
                </table>
            </div>
            <a href="#" class="btn btn-primary mt-3">
                <i data-feather="plus"></i> Agregar Movimiento
            </a>
        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
