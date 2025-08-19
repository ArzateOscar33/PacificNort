 
<div class="container mt-4 col-md-12">
    <div class="card p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold">Resumen de Operación</h3>
            <select class="form-control w-auto" id="selectOperacion">
                <option value="">Seleccione una Operación</option>
                <!-- Opciones cargadas desde PHP/JS -->
            </select>
        </div>

        <div id="contenidoOperacion" style="display: none;">
            <div class="row g-3">
                <!-- Contenedor Info -->
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="selectContenedor" class="form-label">Seleccionar Contenedor:</label>
                        <select class="form-control" id="selectContenedor">
                            <!-- Contenedores cargados dinámicamente -->
                        </select>
                    </div>
                    <div class="border rounded p-3">
                        <h6><strong id="contenedorId">Contenedor</strong> <span class="badge bg-success">En tránsito</span></h6>
                        <p class="mb-1"><strong>Tipo de Contenedor:</strong> Marítimo</p>
                        <p class="mb-1"><strong>ETA:</strong> 07/08/2025</p>
                        <p class="mb-1"><strong>Bultos:</strong> <span id="buitos">10</span></p>
                        <p class="mb-1"><strong>Peso Toneladas:</strong> <span id="ausos">10</span></p>
                        <p class="mb-0"><i data-feather="anchor"></i> <strong id="fechaContenedor">06/08/2025</strong></p>
                    </div>
                </div>

                <!-- Indicadores -->
                <div class="col-md-2">
                    <div class="bg-warning text-white rounded p-3 text-center">
                        <i data-feather="file-text"></i>
                        <h5 class="fw-bold">3</h5>
                        <p class="mb-0">Pendientes</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="bg-danger text-white rounded p-3 text-center">
                        <i data-feather="dollar-sign"></i>
                        <h5 class="fw-bold" id="gananciaEstim">$0</h5>
                        <p class="mb-0">Costos</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="bg-primary text-white rounded p-3 text-center">
                        <i data-feather="database"></i>
                        <h5 class="fw-bold">$0</h5>
                        <p class="mb-0">Ganancias</p>
                    </div>
                </div>
      
            </div>

            <div class="row mt-4">
                <!-- Avance -->
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <h6>Avance de la Operación</h6>
                        <canvas id="avanceOperacionChart"></canvas>
                    </div>
                </div>
                <!-- Costos -->
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <h6>Costos</h6>
                        <canvas id="costosChart"></canvas>
                        <ul class="list-unstyled mt-2">
                            <li><i class="me-2" style="color: #1f77b4">●</i> Transbordo 60%</li>
                            <li><i class="me-2" style="color: #aec7e8">●</i> Flete Local 40%</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Trazabilidad -->
            <div class="mt-4">
                <h6><i data-feather="clock"></i> Trazabilidad</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Evento</th>
                                <th>Origen</th>
                                <th>Destino</th>
                            </tr>
                        </thead>
                        <tbody id="tablaTrazabilidad">
                            <!-- Datos cargados desde JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const operaciones = [
        {
            id: "FXEU237648",
            cliente: "QUICK ADAN",
            destino: "GDL",
            ganancia: 120000,
            contenedores: [
                {
                    id: "CMAU9166054",
                    status: "En tránsito",
                    fecha: "06/08/2025",
                    buitos: 10,
                    ausos: 10,
                    trazabilidad: [
                        { fecha: "08/08/2025", evento: "Carga a barco", origen: "Los Angeles", destino: "Lázaro" },
                        { fecha: "07/08/2025", evento: "Entrega en puerto", origen: "David", destino: "Lázaro" },
                        { fecha: "06/08/2025", evento: "Salida a ferrocarril", origen: "San Diego", destino: "David" }
                    ]
                },
                {
                    id: "MSCU1234567",
                    status: "En tránsito",
                    fecha: "06/08/2025",
                    buitos: 12,
                    ausos: 8,
                    trazabilidad: [
                        { fecha: "08/08/2025", evento: "Zarpado", origen: "Long Beach", destino: "Manzanillo" },
                        { fecha: "07/08/2025", evento: "Inspección", origen: "Long Beach", destino: "Long Beach" }
                    ]
                }
            ]
        }
    ];

    const selectOperacion = document.getElementById("selectOperacion");
    const selectContenedor = document.getElementById("selectContenedor");
    const contenido = document.getElementById("contenidoOperacion");
    const trazabilidadTable = document.getElementById("tablaTrazabilidad");

    let contenedoresActivos = [];

    operaciones.forEach(op => {
        const option = document.createElement("option");
        option.value = op.id;
        option.textContent = `${op.id} - ${op.cliente}`;
        selectOperacion.appendChild(option);
    });

    selectOperacion.addEventListener("change", e => {
        const op = operaciones.find(o => o.id === e.target.value);
        if (!op) return;

        document.getElementById("gananciaEstim").textContent = `$${op.ganancia.toLocaleString()}`;
        contenedoresActivos = op.contenedores;

        // cargar contenedores
        selectContenedor.innerHTML = "";
        contenedoresActivos.forEach((c, i) => {
            const opt = document.createElement("option");
            opt.value = i;
            opt.textContent = c.id;
            selectContenedor.appendChild(opt);
        });

        selectContenedor.dispatchEvent(new Event('change'));
        contenido.style.display = 'block';
    });

    selectContenedor.addEventListener("change", e => {
        const index = parseInt(e.target.value);
        const cont = contenedoresActivos[index];
        if (!cont) return;

        document.getElementById("contenedorId").textContent = `Contenedor ${cont.id}`;
        document.getElementById("buitos").textContent = cont.buitos;
        document.getElementById("ausos").textContent = cont.ausos;
        document.getElementById("fechaContenedor").textContent = cont.fecha;

        trazabilidadTable.innerHTML = "";
        cont.trazabilidad.forEach(row => {
            trazabilidadTable.innerHTML += `<tr><td>${row.fecha}</td><td>${row.evento}</td><td>${row.origen}</td><td>${row.destino}</td></tr>`;
        });
    });

    new Chart(document.getElementById('avanceOperacionChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Salida', 'En tránsito', 'Arribo'],
            datasets: [{
                label: 'Avance',
                data: [30, 50, 80],
                borderColor: '#007bff',
                tension: 0.3,
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: value => value + '%'
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('costosChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Transbordo', 'Flete Local'],
            datasets: [{
                data: [60, 40],
                backgroundColor: ['#1f77b4', '#aec7e8']
            }]
        },
        options: {
            responsive: true,
            cutout: '70%'
        }
    });

    feather.replace();
</script> 