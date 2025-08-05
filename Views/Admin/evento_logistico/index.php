<?php include 'Views/Template/admin_header.php';
?>
<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary ">
                    <h3 class="card-title mt-3 mb-3 text-white">Eventos logisticos por contenedor</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col-md-3">
                            <select name="contenedor" class="form-control">
                                <option>Contenedor</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" value="" class="form-control" placeholder="Cliente">
                        </div>

                        <div class="col-md-3">
                            <button class="btn btn-primary white" style="font-size: 15px;">
                                <i class="fas fa-plus"></i> Evento Logístico
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <div class="container text-center mt-5 mb-4">
        <div class="row justify-content-center align-items-center">
            <!-- ORIGEN -->
            <div class="col-md-3 position-relative">
                <div class="mb-2">
                    <strong>Origen</strong>
                </div>
                <div id="circulo-origen"
                    class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto"
                    style="width: 70px; height: 70px; font-size: 18px;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="mt-2">
                    <span class="text-muted">Tijuana, Baja California</span>
                </div>
            </div>

            <!-- Flecha entre Origen y Tránsito -->
            <div id="flecha-1" class="col-md-1 d-flex justify-content-center align-items-center">
                <i class="fas fa-long-arrow-alt-right fa-2x text-secondary"></i>
            </div>

            <!-- TRANSITO -->
            <div class="col-md-3 position-relative">
                <div class="mb-2">
                    &nbsp;
                </div>
                <div id="circulo-transito"
                    class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mx-auto"
                    style="width: 70px; height: 70px; font-size: 16px;">
                    En Tránsito
                </div>
                <div class="mt-2">
                    <!-- Puedes poner fecha del evento o estado -->
                </div>
            </div>

            <!-- Flecha entre Tránsito y Destino -->
            <div id="flecha-2" class="col-md-1 d-flex justify-content-center align-items-center">
                <i class="fas fa-long-arrow-alt-right fa-2x text-secondary"></i>
            </div>

            <!-- DESTINO -->
            <div class="col-md-3 position-relative">
                <div class="mb-2">
                    <strong>Destino</strong>
                </div>
                <div id="circulo-destino"
                    class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto"
                    style="width: 70px; height: 70px; font-size: 16px;">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="mt-2">
                    <span class="text-muted">Guadalajara, Jalisco</span>
                </div>
            </div>

        </div>
        <!-- /.row -->

    </div>
    <div class="row col-md-12">

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Evento</th>
                        <th>Estado</th>
                        <th>Usuario que registro el evento</th>
                        <th>Folio de Operacion</th>
                    </tr>
                </thead>
                <tbody>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tbody>
            </table>
        </div>

        <!-- /.col -->
    </div>
    <!-- /.container -->

</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title mt-3 mb-3 text-white">Eventos Logísticos por fecha</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control " placeholder="Buscar Evento">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control">
                            <option>Fecha</option>
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control">
                            <option>Divisa</option>
                            <option>DLLS</option>
                            <option>MXN</option>
                        </select>
                    </div>

                </div>
                <!-- /.d-flex -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha</th>
                                <th>Divisa</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tbody>
                    </table>
                </div>
                <!-- /.table-responsive -->
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.col -->
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title mt-3 mb-3 text-white">Eventos Logísticos por tipo</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div class="col-md-3">
                        <select name="tipo" class="form-control">
                            <option>Tipo</option>
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" value="" class="form-control" placeholder="Cliente">
                    </div>

                </div>
            </div>
        </div>
        <!-- /.card -->
    </div>
    <!-- /.col -->
</div>
<div class="row col-md-12">

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Evento</th>
                    <th>Estado</th>
                    <th>Usuario que registro el evento</th>
                    <th>Folio de Operacion</th>
                </tr>
            </thead>
            <tbody>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tbody>
        </table>
    </div>

    <!-- /.col -->
</div>

</div>

<!-- Script para manejar el estado actual del evento logístico -->
<script>
    //const estadoActual = '<?php echo $estado; ?>';
</script>
<script>
    //PASAR ESTE CODIGO A UN CODIGO DE JAVASCRIPT APARTE 
    // Este valor debería venir de PHP dinámicamente
    
    const estadoActual = 'entregado'; // otros valores: 'origen', 'entregado'

    // Estilos aplicados con base al estado actual
    if (estadoActual === 'origen') {
        document.getElementById('circulo-origen').classList.replace('bg-secondary', 'bg-primary');
        alert('El estado actual es Origen');
    }

    if (estadoActual === 'transito') {
        document.getElementById('circulo-origen').classList.replace('bg-secondary', 'bg-primary');
        document.getElementById('flecha-1').classList.replace('text-secondary', 'text-primary');
        document.getElementById('circulo-transito').classList.replace('bg-secondary', 'bg-warning');
        alert('El estado actual es transito');
    }

    if (estadoActual === 'entregado') {
        document.getElementById('circulo-origen').classList.replace('bg-secondary', 'bg-primary');
        document.getElementById('flecha-1').classList.replace('text-secondary', 'text-primary');
        document.getElementById('circulo-transito').classList.replace('bg-secondary', 'bg-warning');
        document.getElementById('flecha-2').classList.replace('text-secondary', 'text-success');
        document.getElementById('circulo-destino').classList.replace('bg-secondary', 'bg-success');
        alert('El estado actual es Entregado');
    }
    
</script>

<?php include 'Views/Template/admin_footer.php'; ?>