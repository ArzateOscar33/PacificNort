
<?php include 'Views/Template/admin_header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4"><i data-feather="activity" class="text-primary"></i> Registro de Operaciones (LOG)</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" placeholder="Buscar usuario...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Operación</label>
            <input type="text" class="form-control" placeholder="Buscar operación...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Acción</label>
            <select class="form-control">
                <option value="">Todas</option>
                <option>Insertar</option>
                <option>Actualizar</option>
                <option>Eliminar</option>
                <option>Login</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle bg-white shadow-sm">
            <thead class="table-primary text-center">
                <tr>
                    <th><i data-feather="user"></i> Usuario</th>
                    <th><i data-feather="hash"></i> Operación</th>
                    <th><i data-feather="zap"></i> Acción</th>
                    <th><i data-feather="clock"></i> Fecha</th>
                    <th><i data-feather="file-text"></i> Detalles</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>admin</td>
                    <td>JL-46</td>
                    <td><span class="badge bg-success"><i data-feather="plus" class="me-1"></i>Insertar</span></td>
                    <td>2025-08-04 12:23</td>
                    <td>Se registró nuevo contenedor CMAU9196054</td>
                </tr>
                <tr>
                    <td>oscar</td>
                    <td>JL-55</td>
                    <td><span class="badge bg-warning text-dark"><i data-feather="edit" class="me-1"></i>Actualizar</span></td>
                    <td>2025-08-03 10:00</td>
                    <td>Actualización en peso y ETA del contenedor EGSU9265481</td>
                </tr>
                <tr>
                    <td>karla</td>
                    <td>JL-61</td>
                    <td><span class="badge bg-danger"><i data-feather="trash-2" class="me-1"></i>Eliminar</span></td>
                    <td>2025-08-02 14:17</td>
                    <td>Se eliminó el documento de carta encomienda</td>
                </tr>
                <tr>
                    <td>oscar</td>
                    <td>-</td>
                    <td><span class="badge bg-info text-dark"><i data-feather="log-in" class="me-1"></i>Login</span></td>
                    <td>2025-08-01 08:43</td>
                    <td>Inicio de sesión exitoso</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    feather.replace();
</script>

</body>
</html>

<?php include 'Views/Template/admin_footer.php'; ?>