<?php include 'Views/Template/admin_header.php'; ?>

<div class="container-fluid px-4">
    <!-- Header con mejor jerarquía visual -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
        <div>
            <h1 class="h2 mb-1 fw-bold text-dark">Dashboard Principal</h1>
            <p class="text-muted mb-0">Resumen de operaciones y actividad logística</p>
        </div>
        <div class="text-end">
            <small class="text-muted">Última actualización: <strong>Hoy 14:32</strong></small>
        </div>
    </div>

    <!-- KPIs mejorados con mejor layout -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 h-2 bg-primary"></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="activity" class="text-primary"
                                        style="width: 20px; height: 20px;"></i>
                                </div>
                                <span class="text-muted fw-medium">Operaciones Activas</span>
                            </div>
                            <div class="fs-2 fw-bold text-dark mb-1">24</div>
                            <small class="text-success">
                                <i data-feather="trending-up" style="width: 14px; height: 14px;"></i> +12% vs mes
                                anterior
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 h-2 bg-success"></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="check-circle" class="text-success"
                                        style="width: 20px; height: 20px;"></i>
                                </div>
                                <span class="text-muted fw-medium">Finalizadas</span>
                            </div>
                            <div class="fs-2 fw-bold text-dark mb-1">145</div>
                            <small class="text-success">
                                <i data-feather="trending-up" style="width: 14px; height: 14px;"></i> +8% vs mes
                                anterior
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 h-2 bg-warning"></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="truck" class="text-warning" style="width: 20px; height: 20px;"></i>
                                </div>
                                <span class="text-muted fw-medium">En Tránsito</span>
                            </div>
                            <div class="fs-2 fw-bold text-dark mb-1">6</div>
                            <small class="text-muted">
                                <i data-feather="minus" style="width: 14px; height: 14px;"></i> Sin cambios
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 h-2 bg-danger"></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="alert-triangle" class="text-danger"
                                        style="width: 20px; height: 20px;"></i>
                                </div>
                                <span class="text-muted fw-medium">Alertas</span>
                            </div>
                            <div class="fs-2 fw-bold text-dark mb-1">3</div>
                            <small class="text-danger">
                                <i data-feather="alert-circle" style="width: 14px; height: 14px;"></i> Requiere atención
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas mejoradas -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">
                            <i data-feather="pie-chart" class="me-2 text-primary"></i>
                            Estatus de Contenedores
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                data-bs-toggle="dropdown">
                                <i data-feather="more-horizontal" style="width: 16px; height: 16px;"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Exportar</a></li>
                                <li><a class="dropdown-item" href="#">Ver detalles</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="graficoContenedores" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">
                            <i data-feather="bar-chart-2" class="me-2 text-primary"></i>
                            Costos por Operación
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                data-bs-toggle="dropdown">
                                <i data-feather="more-horizontal" style="width: 16px; height: 16px;"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Exportar</a></li>
                                <li><a class="dropdown-item" href="#">Ver detalles</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="graficoCostos" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de información importante -->
    <div class="row g-4 mb-5">
        <!-- Próximos Movimientos mejorado -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">
                            <i data-feather="calendar" class="me-2 text-primary"></i>
                            Próximos Movimientos
                        </h5>
                        <a href="#" class="btn btn-outline-primary btn-sm">Ver todos</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="package" class="text-primary"
                                        style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">CMAU7519482</div>
                                    <small class="text-muted">Lázaro Cárdenas</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold text-primary">06/08</div>
                                    <small class="text-muted">2 días</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="package" class="text-success"
                                        style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">TGBU9245545</div>
                                    <small class="text-muted">Origen: Bodega A</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold text-success">08/08</div>
                                    <small class="text-muted">4 días</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="package" class="text-warning"
                                        style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">TGBU9999999</div>
                                    <small class="text-muted">Puerto Veracruz</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold text-warning">10/08</div>
                                    <small class="text-muted">6 días</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas mejoradas -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">
                            <i data-feather="alert-circle" class="me-2 text-danger"></i>
                            Alertas y Pendientes
                        </h5>
                        <span class="badge bg-danger rounded-pill">3</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 py-3 bg-danger bg-opacity-5">
                            <div class="d-flex align-items-start">
                                <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3 mt-1">
                                    <i data-feather="alert-triangle" class="text-danger"
                                        style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">PCIU8910245</div>
                                    <small class="text-danger">Sin documentos desde 5 días</small>
                                </div>
                                <button class="btn btn-outline-danger btn-sm">Acción</button>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-3 bg-warning bg-opacity-5">
                            <div class="d-flex align-items-start">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3 mt-1">
                                    <i data-feather="clock" class="text-warning" style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">TGBU1234567</div>
                                    <small class="text-warning">No actualizado desde 8 días</small>
                                </div>
                                <button class="btn btn-outline-warning btn-sm">Revisar</button>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-3 bg-danger bg-opacity-5">
                            <div class="d-flex align-items-start">
                                <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3 mt-1">
                                    <i data-feather="calendar-x" class="text-danger"
                                        style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">CMAU1234567</div>
                                    <small class="text-danger">Fecha de entrega vencida</small>
                                </div>
                                <button class="btn btn-outline-danger btn-sm">Urgente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Operaciones Recientes mejorada -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white border-0 py-4">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-semibold">
                    <i data-feather="clock" class="me-2 text-primary"></i>
                    Operaciones Recientes
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm">
                        <i data-feather="filter" style="width: 16px; height: 16px;"></i>
                    </button>
                    <a href="#" class="btn btn-outline-primary btn-sm">Ver todas</a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 py-3 px-4 fw-semibold text-muted">Cliente</th>
                            <th class="border-0 py-3 fw-semibold text-muted">Contenedor</th>
                            <th class="border-0 py-3 fw-semibold text-muted">Fecha</th>
                            <th class="border-0 py-3 fw-semibold text-muted">Estatus</th>
                            <th class="border-0 py-3 fw-semibold text-muted text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-bottom">
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                        <i data-feather="user" class="text-primary"
                                            style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">CP DANNY</div>
                                        <small class="text-muted">Cliente Premium</small>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="fw-semibold text-dark">CMAU7519482</span>
                            </td>
                            <td class="py-3">
                                <div class="text-dark">06/08/2025</div>
                                <small class="text-muted">Hace 2 horas</small>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                    <i data-feather="truck" style="width: 12px; height: 12px;"></i> En tránsito
                                </span>
                            </td>
                            <td class="py-3 text-end">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        <i data-feather="more-horizontal" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">Ver detalles</a></li>
                                        <li><a class="dropdown-item" href="#">Editar</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="#">Eliminar</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                        <i data-feather="user" class="text-success"
                                            style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">SANDRA</div>
                                        <small class="text-muted">Cliente Regular</small>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="fw-semibold text-dark">TGBU9245545</span>
                            </td>
                            <td class="py-3">
                                <div class="text-dark">18/07/2025</div>
                                <small class="text-muted">Hace 18 días</small>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                    <i data-feather="check-circle" style="width: 12px; height: 12px;"></i> Entregado
                                </span>
                            </td>
                            <td class="py-3 text-end">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        <i data-feather="more-horizontal" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">Ver detalles</a></li>
                                        <li><a class="dropdown-item" href="#">Editar</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="#">Eliminar</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3">
                                        <i data-feather="user" class="text-danger"
                                            style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">DAVID</div>
                                        <small class="text-muted">Cliente VIP</small>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="fw-semibold text-dark">PCIU8910245</span>
                            </td>
                            <td class="py-3">
                                <div class="text-dark">29/07/2025</div>
                                <small class="text-muted">Hace 7 días</small>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                    <i data-feather="alert-triangle" style="width: 12px; height: 12px;"></i> Sin
                                    documentos
                                </span>
                            </td>
                            <td class="py-3 text-end">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        <i data-feather="more-horizontal" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">Ver detalles</a></li>
                                        <li><a class="dropdown-item" href="#">Editar</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="#">Eliminar</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Inicializar Feather Icons
    feather.replace();
    // Configuración mejorada para gráfico de contenedores
    const ctx1 = document.getElementById('graficoContenedores');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['Entregados', 'En Tránsito', 'Sin Documentos'],
            datasets: [{
                data: [12, 7, 3],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2,
                cutout: '60%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true
                }
            }
        }
    });
    // Configuración mejorada para gráfico de costos
    const ctx2 = document.getElementById('graficoCostos');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'Costos (MXN)',
                data: [3200, 2700, 1800, 2200, 4100, 3000, 3500],
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString('es-MX');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-MX');
                        },
                        color: 'rgba(0, 0, 0, 0.6)',
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: 'rgba(0, 0, 0, 0.6)',
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
</script>

<?php include 'Views/Template/admin_footer.php'; ?>