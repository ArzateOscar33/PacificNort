 <div class="container mt-4 col-md-12">
     <div class="card p-4 shadow-sm">
         <div class="d-flex justify-content-between align-items-center mb-3">
             <h3 class="fw-bold">Resumen de Operación</h3>
             <div class="d-flex gap-2">
                 <div class="col-md-12 position-relative">
                     <input type="text" id="buscarOperacionResumen" name="buscarOperacionResumen" class="form-control "
                         placeholder="Buscar Operacion" autocomplete="off">
                     <div id="sugerenciasOperacionResumen" class="list-group position-absolute w-100"
                         style="z-index:999; display:none; top:100%; left:0;"></div>
                 </div>
             </div>
         </div>

         <!-- Contenido con datos de ejemplo -->
         <div id="contenidoOperacion" style="display:block;">
             <div class="row g-3">
                 <!-- Contenedor Info -->
                 <div class="col-md-4" style="border:1px solid red;">
                     <div class="mb-2">
                         <label for="selectContenedorResumen" class="form-label">Seleccionar Contenedor:</label>
                         <div class="d-flex gap-2">
                             <select class="form-control" id="selectContenedorResumen">
                                <option value=""  >-- Selecciona una Operación --</option>
                             </select>
                             <button class="btn btn-outline-secondary" id="btnRefrescarResumen" title="Refrescar">
                                 <i data-feather="refresh-cw"></i>
                             </button>
                         </div>
                     </div>

                     <div class="border rounded p-3" id="cardInfoContenedor">
                         <div class="d-flex justify-content-between align-items-start mb-2">
                             <div>
                                 <div class="small text-muted">Contenedor</div>
                                 <div id="nombreContenedorResumen">—</div>
                             </div>
                            
                         </div>

                         <!-- ===== Vista MARÍTIMO ===== -->
                         <div id="bloqueMaritimo" class="mt-2">
                             <div class="mb-2">
                                 <div class="small text-muted">Puerto</div>
                                 <div id="puertoResumen">—</div>
                             </div>
                             <div class="mb-2">
                                 <div class="small text-muted">ETA</div>
                                 <div id="etaContenedor">—</div>
                             </div>
                             <div class="mb-2">
                                 <div class="small text-muted">ETD</div>
                                 <div id="etdContenedor">—</div>
                             </div>
                             <div class="mb-2">
                                 <div class="small text-muted">No. BL</div>
                                 <div id="blContenedor">—</div>
                             </div>
                             <div class="mb-2">
                                 <div class="small text-muted">Comentarios</div>
                                 <div id="comentarioContenedor">—</div>
                             </div>
                         </div>

                         <!-- ===== Vista FÍSICO / FERRO ===== -->
                         <div id="bloqueFerro" class="mt-2 d-none">
                             <div class="mb-2">
                                 <div class="small text-muted">Arribo a puerto</div>
                                 <div id="arriboPuerto">—</div>
                             </div>
                             <div class="mb-2">
                                 <div class="small text-muted">Bultos</div>
                                 <div id="bultos">—</div>
                             </div>
                         </div>

                         <div class="d-flex flex-wrap gap-2 mt-3">
                             <button class="btn btn-sm btn-outline-warning">
                                 <i data-feather="file" class="me-1"></i> PDF
                             </button>
                             <button class="btn btn-sm btn-outline-success">
                                 <i data-feather="file-text" class="me-1"></i> Excel
                             </button>
                         </div>
                     </div>
                 </div>

                 <div class="row g-3 col-md-8">
                     <!-- Docs pendientes -->
                     <div class="col-md-3">
                         <div
                             class="bg-warning text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="file-text" class="mb-1"></i>
                             <h5 class="fw-bold" id="docsPendientesResumen">3</h5>
                             <p class="mb-0">Docs pendientes</p>
                         </div>
                     </div>

                     <!-- Costos -->
                     <div class="col-md-3">
                         <div
                             class="bg-danger text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="dollar-sign" class="mb-1"></i>
                             <h5 class="fw-bold">$24,800</h5>
                             <p class="mb-0">Costos</p>
                         </div>
                     </div>

                     <!-- Utilidad -->
                     <div class="col-md-3">
                         <div
                             class="bg-success text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="trending-up" class="mb-1"></i>
                             <h5 class="fw-bold">$6,200</h5>
                             <p class="mb-0">Utilidad</p>
                         </div>
                     </div>

                     <!-- Eventos completados -->
                     <div class="col-md-3">
                         <div
                             class="bg-info text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="check-circle" class="mb-1"></i>
                             <h5 class="fw-bold">4 / 6</h5>
                             <p class="mb-0">Eventos</p>
                         </div>
                     </div>

                 </div>

             </div>

             <!-- Avance + Costos -->
             <div class="row mt-4 g-3">
                 <!-- Avance -->
                 <div class="col-md-6" style="border: red solid 1px;">
                     <div class="col-md-12">
                         <h6 class="fw-bold mb-2"><i data-feather="clock" class="me-1"></i> Línea de tiempo</h6>
                         <canvas id="timelineChart" class="w-100 h-100"></canvas>
                     </div>

                 </div>

                 <!-- Costos -->
                 <div class="col-md-6" style="border:1px solid red;">
                     <h6 class="fw-bold mb-2"><i data-feather="dollar-sign" class="me-1"></i> Costos del contenedor</h6>
                     <div class="d-flex">
                         <canvas id="costosChart" class="w-50 h-50 mt-3 "></canvas>
                         <ul class="list-unstyled mt-3" id="costosLeyenda">
                             <!-- La leyenda se llenará con JS -->
                         </ul>
                     </div>
                 </div>
             </div>

             <!-- Documentos + Trazabilidad -->
             <div class="row mt-4 g-3">
                 <!-- Checklist de Documentos -->
                <!-- ===== Documentos faltantes por contenedor ===== -->
                <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Documentos faltantes</strong><br>
                        <small id="dfContenedorInfo" class="text-muted">Seleccione un contenedor…</small>
                    </div>
                    <span class="badge bg-warning text-dark" id="dfBadgeCount">0</span>
                    </div>
                    <div class="card-body">
                    <!-- Placeholder Cargando -->
                    <div id="dfLoading" class="text-center text-muted" style="display:none;">
                        Cargando pendientes…
                    </div>

                    <!-- Placeholder Sin pendientes -->
                    <div id="dfEmpty" class="alert alert-success py-2" style="display:none;">
                        No hay documentos pendientes para este contenedor.
                    </div>

                    <!-- Lista de faltantes -->
                    <ul id="dfLista" class="list-group list-group-flush" style="display:none;"></ul>
                    </div>
                </div>
                </div>

<!-- Hidden helpers que poblará JS cuando selecciones operación/contenedor -->
<input type="hidden" id="dfOperacionId" value="">
<input type="hidden" id="dfContenedorId" value="">
<input type="hidden" id="dfContenedorTipo" value=""> <!-- 'F' o 'M' -->


                 <!-- Trazabilidad (tabla) -->
                 <div class="col-md-6">
                     <div class="border rounded p-3">
                         <h6 class="mb-2"><i data-feather="clock" class="me-1"></i> Trazabilidad</h6>
                         <div class="table-responsive">
                             <table class="table table-bordered table-striped mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th>Fecha</th>
                                         <th>Evento</th>
                                     </tr>
                                 </thead>
                                 <tbody id="tablaTrazabilidad">
                                     <tr>
                                         <td>31/08/2025 09:10</td>
                                         <td>Salida de origen</td>
                                     </tr>
                                     <tr>
                                         <td>02/09/2025 13:45</td>
                                         <td>Transbordo</td>
                                     </tr>
                                     <tr>
                                         <td>06/09/2025 22:00</td>
                                         <td>Arribo estimado</td>
                                     </tr>
                                     <tr>
                                         <td>07/09/2025 08:00</td>
                                         <td>Descarga programada</td>
                                     </tr>
                                     <tr>
                                         <td>08/09/2025 12:30</td>
                                         <td>Previo/Inspección</td>
                                     </tr>
                                 </tbody>
                             </table>
                         </div>
                         <div class="text-end mt-2">
                             <button class="btn btn-sm btn-outline-primary">
                                 <i data-feather="more-horizontal" class="me-1"></i> Ver todos
                             </button>
                         </div>
                     </div>
                 </div>

             </div>

         </div>
     </div>
 </div>

 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
 <script>
     const operaciones = [{
         id: "FXEU237648",
         cliente: "-",
         destino: "GDL",
         ganancia: 120000,
         contenedores: [{
                 id: "CMAU9166054",
                 status: "En tránsito",
                 fecha: "06/08/2025",
                 buitos: 10,
                 ausos: 10,
                 trazabilidad: [{
                         fecha: "08/08/2025",
                         evento: "Carga a barco",
                         origen: "Los Angeles",
                         destino: "Lázaro"
                     },
                     {
                         fecha: "07/08/2025",
                         evento: "Entrega en puerto",
                         origen: "David",
                         destino: "Lázaro"
                     },
                     {
                         fecha: "06/08/2025",
                         evento: "Salida a ferrocarril",
                         origen: "San Diego",
                         destino: "David"
                     }
                 ]
             },
             {
                 id: "MSCU1234567",
                 status: "En tránsito",
                 fecha: "06/08/2025",
                 buitos: 12,
                 ausos: 8,
                 trazabilidad: [{
                         fecha: "08/08/2025",
                         evento: "Zarpado",
                         origen: "Long Beach",
                         destino: "Manzanillo"
                     },
                     {
                         fecha: "07/08/2025",
                         evento: "Inspección",
                         origen: "Long Beach",
                         destino: "Long Beach"
                     }
                 ]
             }
         ]
     }];
     /*
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
             trazabilidadTable.innerHTML +=
                 `<tr><td>${row.fecha}</td><td>${row.evento}</td><td>${row.origen}</td><td>${row.destino}</td></tr>`;
         });
     });
     */
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
             labels: ['Transbordo', 'Flete Local', 'Carga a barco', 'Trabajo', 'Otros'],
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
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('timelineChart')?.getContext('2d');
  if (!ctx) return;

  // Etiquetas = tu secuencia de eventos (fecha + nombre con salto de línea)
  const labels = [
    '31/08 09:10\nSalida de origen',
    '02/09 13:45\nTransbordo',
    '06/09 22:00\nArribo estimado',
    '07/09 08:00\nDescarga prog.',
    '08/09 12:30\nPrevio/Inspección'
  ];

  // Todos los valores a 0 -> línea horizontal (timeline)
  const values = new Array(labels.length).fill(0);

  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Eventos',
        data: values,
        showLine: true,
        tension: 0.35,
        borderColor: 'rgba(13,110,253,0.5)',   // azul translúcido
        borderWidth: 2,
        fill: false,
        segment: { borderDash: [4, 4] },       // línea punteada (opcional)
        pointStyle: 'circle',
        pointRadius: 6,
        pointHoverRadius: 8,
        pointBorderWidth: 2,
        pointBorderColor: 'rgba(13,110,253,0.9)',
        pointBackgroundColor: 'rgba(13,110,253,0.15)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      layout: { padding: { top: 10, right: 8, bottom: 8, left: 8 } },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            autoSkip: false,            // muestra todos los eventos
            maxRotation: 0,
            callback: function(v) {     // respeta saltos de línea con \n
              const txt = this.getLabelForValue(v);
              return txt.split('\n');
            }
          }
        },
        y: {                            // eje Y oculto (timeline plano)
          display: false,
          min: -1, max: 1
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: (items) => items[0].label.replace('\n', ' · '),
            label: () => ''             // solo título en el tooltip
          }
        }
      }
    }
  });

  if (window.feather) feather.replace();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('costosChart')?.getContext('2d');
  if (!ctx) return;

  // Ejemplo de datos (simula lo que sacarías de la BD)
  const conceptos = ['Flete marítimo', 'Transbordo', 'Maniobras', 'Otros'];
  const valores   = [12000, 8000, 3000, 1800];

  // Colores (puedes elegir de Bootstrap o propios)
  const colores = ['#0d6efd','#198754','#ffc107','#dc3545'];

  // Crea el gráfico
  const costosChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: conceptos,
      datasets: [{
        data: valores,
        backgroundColor: colores,
        borderWidth: 1,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false }, // usamos leyenda propia abajo
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const label = ctx.label || '';
              const value = ctx.raw || 0;
              return `${label}: $${value.toLocaleString()}`;
            }
          }
        }
      },
      cutout: '65%' // dona más delgada
    }
  });

  // Leyenda personalizada debajo del gráfico
  const leyenda = document.getElementById('costosLeyenda');
  const total = valores.reduce((a,b) => a+b, 0);
  conceptos.forEach((c, i) => {
    const li = document.createElement('li');
    const pct = ((valores[i]/total)*100).toFixed(1);
    li.innerHTML = `
      <span class="me-2 rounded-circle d-inline-block" style="width:12px;height:12px;background:${colores[i]}"></span>
      ${c}: <strong>$${valores[i].toLocaleString()}</strong> (${pct}%)
    `;
    leyenda.appendChild(li);
  });

  if (window.feather) feather.replace();
});
</script>

<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/operaciones_maritimas/resumen.js"></script>
