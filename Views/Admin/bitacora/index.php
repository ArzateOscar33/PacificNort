<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12 mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Bitácora</h3>
                </div>

                <!-- /.card-header -->
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control " placeholder="Buscar Bitácora">
                        </div>
                        <div class="  d-flex   col-md-4">
                            <select name="select" id="select" class="form-control"
                                aria-placeholder="Filtrar por modulo">
                                <option value="">Filtrar por Modulo</option>
                                <option value="">Modulo 1</option>
                                <option value="">Modulo 2</option>
                                <option value="">Modulo 3</option>
                                <option value="">Modulo 4</option>
                            </select>
                        </div>
                        <div class="  d-flex   col-md-4">
                            <select name="select" id="select" class="form-control"
                                aria-placeholder="Filtrar por modulo">
                                <option value="">Filtrar por Usuario</option>
                                <option value="">Modulo 1</option>
                                <option value="">Modulo 2</option>
                                <option value="">Modulo 3</option>
                                <option value="">Modulo 4</option>
                            </select>
                        </div>

                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>

                                    <th>Número de Bitácora</th>
                                    <th>Usuario</th>
                                    <th>Modulo</th>
                                    <th>Acción</th>
                                    <th>Entidad</th>
                                    <th>Entidad ID</th>
                                    <th>Fecha</th>
                                    <th>Detalle</th>
                                    <th>Acciones</th>

                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
<!-- /.row -->

</div>

<?php include 'Views/Template/admin_footer.php'; ?>