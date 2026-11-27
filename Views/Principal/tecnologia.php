<?php
// PacificNort Suite: escaparate comercial, sin consultas ni datos de producción.
$pnSuiteViews = [
    ['id'=>'operaciones','icono'=>'bi-globe2','titulo'=>'Operaciones','texto'=>'Consulta contenedores, fechas ETD y ETA, estatus y ubicaciones. Personaliza las columnas y exporta el listado a Excel.','imagen'=>'operaciones-demo.webp','alt'=>'Vista ilustrativa del listado de operaciones con indicadores y tabla de datos ficticios.'],
    ['id'=>'partidas','icono'=>'bi-box-seam','titulo'=>'Por partida','texto'=>'Localiza facturas, productos y envíos mediante filtros por estatus, fechas, destino y transportista.','imagen'=>'partidas-demo.webp','alt'=>'Vista ilustrativa de operaciones por partida con indicadores y tabla de facturas ficticias.'],
    ['id'=>'factura','icono'=>'bi-receipt','titulo'=>'Facturas','texto'=>'Consulta los productos, cantidades y fotografías de mercancía vinculadas a tus facturas, cuando estén disponibles.','imagen'=>'factura-demo.webp','alt'=>'Detalle ilustrativo de una factura con productos y cantidades ficticias.'],
    ['id'=>'envio','icono'=>'bi-truck','titulo'=>'Envíos','texto'=>'Consulta cajas o ferros, transportista, destino, facturas vinculadas y evidencia fotográfica disponible.','imagen'=>'envio-demo.webp','alt'=>'Detalle ilustrativo de un envío con facturas, cajas y notas ficticias.'],
    ['id'=>'dispositivos','icono'=>'bi-phone','titulo'=>'Multidispositivo','texto'=>'Consulta tu información desde computadora, tablet o teléfono, con una interfaz adaptada a cada pantalla.','imagen'=>'partidas-demo.webp','alt'=>'Vista ilustrativa del portal en un teléfono.'],
];
$pnSuiteMobile = [
    'operaciones'=>'operaciones-movil-demo.webp',
    'partidas'=>'movil-demo.webp',
    'factura'=>'factura-movil-demo.webp',
    'envio'=>'envio-movil-demo.webp',
    'dispositivos'=>'listado-movil-demo.webp',
];
$pnSuiteFeatures = [
    ['bi-geo-alt','Seguimiento operativo','Consulta estatus, fechas y ubicaciones registradas.'],
    ['bi-file-earmark-text','Información centralizada','Facturas, productos, envíos y evidencia disponible.'],
    ['bi-sliders','Control de tu consulta','Filtros, columnas personalizables y vista ampliada.'],
    ['bi-shield-lock','Acceso de clientes','Ingresa a tu portal con tus credenciales asignadas.'],
];
$pnSuiteEscape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$pnSuiteImg = BASE_URL . 'Assets/img/portal/';
?>
<section class="pn-suite" id="tecnologia" aria-labelledby="pnSuiteTitle">
    <div class="container pn-suite-container">
        <div class="pn-suite-intro">
            <div class="pn-suite-copy">
                <div class="pn-suite-eyebrow"><span></span> TECNOLOGÍA PROPIA</div>
                <div class="pn-suite-brand"><span class="pn-suite-anchor"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="5" r="3"/><path d="M12 8v13M5 12H2a10 10 0 0 0 20 0h-3M8 10h8"/></svg></span><span>PacificNort <strong>Suite</strong><small>Portal de clientes</small></span></div>
                <h2 id="pnSuiteTitle">Tu logística.<br> Tu información.<br> <span>En un solo lugar.</span></h2>
                <p>Conoce nuestra plataforma de gestión y consulta logística. Accede a la información de tus operaciones, facturas y envíos desde cualquier dispositivo.</p>
                <div class="pn-suite-actions">
                    <a class="pn-suite-primary" href="<?= $pnSuiteEscape(BASE_URL . 'admin') ?>"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Acceso al portal</a>
                    <a class="pn-suite-secondary" href="#contacto">Solicitar información <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="pn-suite-showcase">
                <div class="pn-suite-showcase-label"><span><i class="bi bi-display" aria-hidden="true"></i> Explora la plataforma</span><span class="pn-suite-demo-label">DEMO</span></div>
                <div class="pn-suite-tabs" role="tablist" aria-label="Vistas de PacificNort Suite" hidden>
                    <?php foreach ($pnSuiteViews as $pnSuiteIndex => $pnSuiteView): ?>
                    <button type="button" role="tab" id="pnSuiteTab-<?= $pnSuiteView['id'] ?>" aria-controls="pnSuitePanel-<?= $pnSuiteView['id'] ?>" aria-selected="<?= $pnSuiteIndex === 0 ? 'true' : 'false' ?>" tabindex="<?= $pnSuiteIndex === 0 ? '0' : '-1' ?>"><i class="bi <?= $pnSuiteEscape($pnSuiteView['icono']) ?>" aria-hidden="true"></i><span><?= $pnSuiteEscape($pnSuiteView['titulo']) ?></span></button>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($pnSuiteViews as $pnSuiteIndex => $pnSuiteView): ?>
                <div class="pn-suite-panel" id="pnSuitePanel-<?= $pnSuiteView['id'] ?>" role="tabpanel" aria-labelledby="pnSuiteTab-<?= $pnSuiteView['id'] ?>" tabindex="0" <?= $pnSuiteIndex === 0 ? '' : 'hidden' ?>>
                    <div class="pn-suite-device-stage">
                        <figure class="pn-suite-laptop">
                            <div class="pn-suite-laptop-lid">
                                <span class="pn-suite-camera" aria-hidden="true"></span>
                                <div class="pn-suite-laptop-screen">
                                    <img src="<?= $pnSuiteEscape($pnSuiteImg . $pnSuiteView['imagen']) ?>" width="2160" height="1275" alt="<?= $pnSuiteEscape('Vista de escritorio: ' . $pnSuiteView['titulo'] . '. Datos ficticios.') ?>" loading="lazy" decoding="async">
                                </div>
                            </div>
                            <div class="pn-suite-laptop-base" aria-hidden="true"></div>
                        </figure>
                        <figure class="pn-suite-handset">
                            <div class="pn-suite-handset-screen">
                                <img src="<?= $pnSuiteEscape($pnSuiteImg . $pnSuiteMobile[$pnSuiteView['id']]) ?>" width="585" height="1170" alt="<?= $pnSuiteEscape('Vista móvil: ' . $pnSuiteView['titulo'] . '. Datos ficticios.') ?>" loading="lazy" decoding="async">
                            </div>
                            <span class="pn-suite-handset-camera" aria-hidden="true"></span>
                        </figure>
                    </div>
                    <div class="pn-suite-panel-caption"><p><?= $pnSuiteEscape($pnSuiteView['texto']) ?></p><button type="button" class="pn-suite-zoom" data-pn-zoom="<?= $pnSuiteEscape($pnSuiteView['id']) ?>" aria-label="Ampliar vista de <?= $pnSuiteEscape($pnSuiteView['titulo']) ?>" hidden><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ampliar</button></div>
                </div>
                <?php endforeach; ?>
                <p class="pn-suite-disclaimer"><i class="bi bi-info-circle" aria-hidden="true"></i> Vistas ilustrativas con datos ficticios. No contienen información de clientes reales.</p>
            </div>
        </div>
        <div class="pn-suite-features">
            <?php foreach ($pnSuiteFeatures as $pnSuiteFeature): ?>
                <article><span class="pn-suite-feature-icon"><i class="bi <?= $pnSuiteEscape($pnSuiteFeature[0]) ?>" aria-hidden="true"></i></span><h3><?= $pnSuiteEscape($pnSuiteFeature[1]) ?></h3><p><?= $pnSuiteEscape($pnSuiteFeature[2]) ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
    <dialog class="pn-suite-dialog" id="pnSuiteDialog" aria-labelledby="pnSuiteDialogTitle"><div class="pn-suite-dialog-header"><h3 id="pnSuiteDialogTitle">Vista ilustrativa</h3><button type="button" class="pn-suite-dialog-close" aria-label="Cerrar vista ampliada"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div><div class="pn-suite-dialog-body"></div><p class="pn-suite-dialog-note">Demostración · Datos ficticios</p></dialog>
</section>
