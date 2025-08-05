<?php include 'Views/Template/admin_header.php'; ?>
<div class="container col-md-12 mt-4">
    <div class="card">
        <div class="card-header bg-primary">
            <h3 class="card-title text-white">Operaciones Terrestres</h3>
        </div>
        <div class="card-body">

            <!-- Buscador -->
            <div class="d-flex justify-content-between mb-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" placeholder="Buscar operación terrestre...">
                </div>
                <div class="col-md-2 text-end">
                    <a href="<?php echo BASE_URL . 'operacionTerrestre/nueva'; ?>" class="btn btn-primary"><i data-feather="plus"></i> Agregar Operación</a>
                </div>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th># Operación</th>
                            <th>Contenedor</th>
                            <th>Tipo Movimiento</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Transportista</th>
                            <th>Estatus</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
 
                                <tr>
                                    <td> </td>
                                    <td> </td>
                                    <td> </td>
                                    <td> </td>
                                    <td> </td>
                                    <td> </td>
                                    <td>
                                        <span class="badge bg-success">
                                            Finalizado
                                        </span>
                                    </td>
                                    <td>17/08/2025</td>
                                    <td>
                                        <a href=" #" class="btn btn-sm btn-warning"><i data-feather="edit"></i></a>
                                        <a href=" #" class="btn btn-sm btn-danger"><i data-feather="trash-2"></i></a>
                                    </td>
                                </tr>
 
                            <tr><td colspan="9" class="text-center">No hay operaciones terrestres registradas.</td></tr>
        
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
