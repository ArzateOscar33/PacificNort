<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Relación Marítimo - Ferro</h4>
            <a href="<?= BASE_URL ?>contenedor_maritimo_ferro/nuevo" class="btn btn-light">
                <i data-feather="plus"></i> Agregar Relación
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Contenedor Marítimo</th>
                            <th>Contenedor Físico (Ferro)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody> 
                            <tr>
                                <td> </td>
                                <td> </td>
                                <td> </td>
                                <td>
                                    <a href=" #" class="btn btn-danger btn-sm">
                                        <i data-feather="trash-2"></i> Eliminar
                                    </a>
                                </td>
                            </tr> 
                            <tr><td colspan="4" class="text-center">No hay relaciones registradas.</td></tr>
                         
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
