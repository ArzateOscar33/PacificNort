<?php include 'Views/Template/admin_header.php'; ?>

<div class="container col-md-12 mt-3">

    <!-- Formulario para registrar nueva operación -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Registrar nueva operación</h4>
        </div>
        <div class="card-body">
            <form id="formOperacion" method="POST" class="row g-3">

                <div class="col-md-4">
                    <label for="numero_operacion" class="form-label">Número de operación</label>
                    <input type="text" name="numero_operacion" id="numero_operacion" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label for="etd" class="form-label">ETD</label>
                    <input type="date" name="etd" id="etd" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label for="eta" class="form-label">ETA</label>
                    <input type="date" name="eta" id="eta" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="numero_bl" class="form-label">Número de BL</label>
                    <input type="text" name="numero_bl" id="numero_bl" class="form-control">
                </div>

                <div class="col-md-6">
                    <label for="isf" class="form-label">ISF</label>
                    <input type="text" name="isf" id="isf" class="form-control">
                </div>

                <div class="col-md-6">
                    <label for="shipper_id" class="form-label">Shipper</label>
                    <select name="shipper_id" id="shipper_id" class="form-control" required>
                        <option value="">Seleccione</option>
                        <!-- Opciones dinámicas desde BD -->
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="estado_operacion" class="form-label">Estado de operación</label>
                    <select name="estado_operacion" id="estado_operacion" class="form-control" required>
                        <option value="">Seleccione</option>
                        <!-- Opciones dinámicas desde BD -->
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="puerto_arribo_id" class="form-label">Puerto de arribo</label>
                    <select name="puerto_arribo_id" id="puerto_arribo_id" class="form-control" required>
                        <option value="">Seleccione</option>
                        <!-- Opciones dinámicas desde BD -->
                    </select>
                </div>

                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar operación
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Tabla de operaciones generales -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Lista de operaciones</h4>
        </div>
        <div class="card-body">

            <div class="mb-3">
                <input type="text" class="form-control" placeholder="Buscar operación...">
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Número de Operación</th>
                            <th>Contenedor</th>
                            <th>ETD</th>
                            <th>ETA</th>
                            <th>Número BL</th>
                            <th>Cliente</th>
                            <th>ISF</th>
                            <th>Shipper</th>
                            <th>Estado</th>
                            <th>Puerto de arribo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabla de operaciones logísticas -->
    <div class="card mb-5">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Operaciones logísticas</h4>
        </div>
        <div class="card-body">

            <div class="mb-3">
                <input type="text" class="form-control" placeholder="Buscar operación logística...">
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Contenedor Marino</th>
                            <th>Bultos</th>
                            <th>División Bultos</th>
                            <th>Contenedor Terrestre</th>
                            <th>Documentos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'Views/Template/admin_footer.php'; ?>
