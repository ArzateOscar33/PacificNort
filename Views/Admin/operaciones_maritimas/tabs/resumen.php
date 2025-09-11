 <div class="container mt-4 col-md-12" id="informeOperacionResumen">
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
                 <div class="col-md-4"  >
                     <div class="mb-2">
                         <label for="selectContenedorResumen" class="form-label">Seleccionar Contenedor:</label>
                         <div class="d-flex gap-2">
                             <select class="form-control" id="selectContenedorResumen">
                                 <option value="">-- Selecciona una Operación --</option>
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
                             <button class="btn btn-sm btn-outline-warning" id="btnExportPdfResumen">
                                 <i data-feather="file" class="me-1"></i> PDF
                             </button>

                         </div>
                     </div>
                 </div>

                 <div class="row g-3 col-md-8">
                     <!-- Docs pendientes -->
                     <div class="col-md-4">
                         <div
                             class="bg-warning text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="file-text" class="mb-1"></i>
                             <h5 class="fw-bold" id="docsPendientesResumen">0</h5>
                             <p class="mb-0">Docs pendientes</p>
                         </div>
                     </div>

                     <!-- Costos -->
                     <div class="col-md-4">
                         <div
                             class="bg-danger text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="dollar-sign" class="mb-1"></i>
                             <h5 class="fw-bold" id="badgeTotalCostos">$0</h5>
                             <p class="mb-0">Costos</p>
                         </div>
                     </div>

              

                     <!-- Eventos completados -->
                     <div class="col-md-4">
                         <div
                             class="bg-info text-white rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                             <i data-feather="check-circle" class="mb-1"></i>
                             <h5 class="fw-bold" id="badgeEventosResumen">0 / 0</h5>
                             <p class="mb-0">Eventos</p>
                         </div>
                     </div>

                 </div>

             </div>

             <!-- Avance + Costos -->
             <div class="row mt-4 g-3">
                 <!-- Avance -->
                 <div class="col-md-6"  >
                     <div class="col-md-12">
                         <h6 class="fw-bold mb-2"><i data-feather="clock" class="me-1"></i> Línea de tiempo</h6>
                         <canvas id="timelineChart" class="w-100 h-100"></canvas>
                     </div>

                 </div>

                 <!-- Costos -->
                 <div class="col-md-6"  >
                     <h6 class="fw-bold mb-2"><i data-feather="dollar-sign" class="me-1"></i> Costos del contenedor</h6>
                     <div class="row flex-wrap gap-2 justify-content-end align-items-center mb-2">
                         <div class="d-flex flex-wrap align-items-end mb-2">
                             <div>
                                 <label class="form-label small mb-1">Mostrar totales en</label>
                                 <select id="costosResumenMonedaVista" class="form-control form-control-sm"
                                     style="width:140px;">
                                     <option value="MXN">MXN (pesos)</option>
                                     <option value="USD">USD (dólares)</option>
                                 </select>
                             </div>
                             <div class="ms-2">
                                 <label class="form-label small mb-1">Tipo de cambio</label>
                                 <div class="input-group input-group-sm" style="width:160px;">
                                     <span class="input-group-text">$</span>
                                     <input type="number" step="0.0001" min="0" id="costosResumenTipoCambio"
                                         class="form-control mt-1" value="17.00">
                                 </div>
                             </div>
                         </div>
                     </div>

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
                                 <tbody id="tablaEventosLogisticos">
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">
                                                Seleccione un contenedor para ver su trazabilidad.
                                            </td>
                                        </tr>
                                 </tbody>
                             </table>
                         </div>
                   
                     </div>
                 </div>

             </div>

         </div>
     </div>
 </div>

 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
 
 
 

<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/operaciones_maritimas/resumen.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/operaciones_maritimas/resumen_graficos.js"></script>
<!-- Librerías requeridas para exportar a PDF (cargar después de Chart.js) -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
 
<script src="<?php echo BASE_URL; ?>assets/js/modulosAdmin/operaciones_maritimas/resumen_exportar.js"></script>