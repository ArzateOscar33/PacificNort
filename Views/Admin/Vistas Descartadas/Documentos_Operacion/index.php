<!-- subir.php -->
<?php include 'Views/Template/admin_header.php'; ?>
<div class="container mt-4 col-md-12">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Subir Documento a Operación</h4>
        </div>
        <div class="card-body">
            <form method="post" action="#" enctype="multipart/form-data">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Operación</label>
                        <select name="operacion_id" class="form-control" required>
                            <option value="">Seleccione operación</option>
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Tipo de documento</label>
                        <select name="tipo" class="form-control" required>
                            <option value="factura">Factura</option>
                            <option value="bl">BL (Bill of Lading)</option>
                            <option value="guia">Guía</option>
                            <option value="manifiesto">Manifiesto</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Seleccionar archivo (PDF, JPG, PNG, DOC)</label>
                        <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i data-feather="upload"></i> Subir Documento
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
<?php include 'Views/Template/admin_footer.php'; ?>
