<?php
// Galería corporativa PacificNort. Agregar fotografías autorizadas a este catálogo.
$pnGaleriaCatalogo = [
    ['archivo' => 'log2.jpg', 'categoria' => 'Marítimo', 'titulo' => 'Transporte marítimo', 'descripcion' => 'Conexiones logísticas para el comercio internacional.'],
    ['archivo' => 'log1.jpg', 'categoria' => 'Terrestre', 'titulo' => 'Transporte terrestre', 'descripcion' => 'Movimiento de mercancías por carretera.'],
    ['archivo' => 'log3.jpg', 'categoria' => 'Almacenaje', 'titulo' => 'Almacenamiento y distribución', 'descripcion' => 'Manejo y distribución de mercancías.'],
    ['archivo' => 'log4.jpg', 'categoria' => 'Asesoría', 'titulo' => 'Consultoría especializada', 'descripcion' => 'Planeación y coordinación de operaciones logísticas.'],
    ['archivo' => 'servicio_ferroviario.jpg', 'categoria' => 'Ferroviario', 'titulo' => 'Transporte ferroviario', 'descripcion' => 'Alternativas de transporte terrestre por ferrocarril.'],
    ['archivo' => 'servicio_aereo.jpg', 'categoria' => 'Aéreo', 'titulo' => 'Transporte aéreo', 'descripcion' => 'Soluciones de transporte para mercancías.'],
    ['archivo' => 'servicio_aduanal.jpg', 'categoria' => 'Aduanas', 'titulo' => 'Gestiones aduanales', 'descripcion' => 'Coordinación documental para el comercio exterior.'],
];
$pnGaleriaDir = dirname(__DIR__, 2) . '/Assets/img/';
$pnGaleriaFotos = array_values(array_filter($pnGaleriaCatalogo, static function ($foto) use ($pnGaleriaDir) {
    return is_file($pnGaleriaDir . $foto['archivo']);
}));
$pnGaleriaEsc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>
<section class="section pn-gallery" id="galeria" aria-labelledby="pnGalleryTitle">
    <div class="container">
        <div class="pn-gallery-intro">
            <span class="pn-gallery-kicker"><i class="bi bi-camera" aria-hidden="true"></i> Nuestra galería</span>
            <h2 id="pnGalleryTitle">La logística <span>en movimiento.</span></h2>
            <p>Conoce las actividades que forman parte de nuestras soluciones logísticas integrales.</p>
        </div>
        <?php if ($pnGaleriaFotos): ?>
            <div class="pn-gallery-grid" data-pn-count="<?= count($pnGaleriaFotos) ?>">
                <?php foreach ($pnGaleriaFotos as $pnFoto): ?>
                    <a class="pn-gallery-item" href="<?= $pnGaleriaEsc(BASE_URL . 'Assets/img/' . rawurlencode($pnFoto['archivo'])) ?>" data-pn-photo aria-label="<?= $pnGaleriaEsc('Ampliar: ' . $pnFoto['titulo']) ?>">
                        <img src="<?= $pnGaleriaEsc(BASE_URL . 'Assets/img/' . rawurlencode($pnFoto['archivo'])) ?>" alt="<?= $pnGaleriaEsc($pnFoto['titulo']) ?>" loading="lazy" decoding="async">
                        <div class="pn-gallery-overlay">
                            <div class="pn-gallery-copy">
                                <span class="pn-gallery-category"><?= $pnGaleriaEsc($pnFoto['categoria']) ?></span>
                                <h3 class="pn-gallery-name"><?= $pnGaleriaEsc($pnFoto['titulo']) ?></h3>
                                <p class="pn-gallery-description"><?= $pnGaleriaEsc($pnFoto['descripcion']) ?></p>
                            </div>
                            <span class="pn-gallery-expand" aria-hidden="true"><i class="bi bi-arrows-angle-expand"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="pn-gallery-note"><i class="bi bi-images" aria-hidden="true"></i> Selecciona una fotografía para ampliarla. Imágenes ilustrativas de las actividades logísticas.</div>
        <?php else: ?>
            <p class="pn-gallery-note">Próximamente, nuevas fotografías de nuestra galería.</p>
        <?php endif; ?>
        <div class="pn-gallery-cta">
            <div><h3>Conoce cómo podemos ayudarte.</h3><p>Estamos para acompañarte en cada etapa de tu operación.</p></div>
            <a href="#contacto" class="pn-gallery-contact">Contáctanos <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>
    <?php if ($pnGaleriaFotos): ?>
        <dialog id="pnGalleryViewer" class="pn-gallery-viewer" aria-labelledby="pnGalleryViewerTitle">
            <div class="pn-gallery-viewer-inner">
                <div class="pn-gallery-viewer-header">
                    <div class="pn-gallery-viewer-meta"><span id="pnGalleryCounter" aria-live="polite"></span><h3 id="pnGalleryViewerTitle">Galería de operaciones</h3></div>
                    <button type="button" data-pn-close class="pn-gallery-viewer-button" aria-label="Cerrar visor" autofocus><i class="bi bi-x-lg" aria-hidden="true"></i><span aria-hidden="true">×</span></button>
                </div>
                <div class="pn-gallery-stage">
                    <button type="button" data-pn-prev class="pn-gallery-viewer-button pn-gallery-prev" aria-label="Fotografía anterior">‹</button>
                    <img id="pnGalleryLarge" alt="">
                    <p id="pnGalleryError" hidden role="status">No se pudo cargar la fotografía.</p>
                    <button type="button" data-pn-next class="pn-gallery-viewer-button pn-gallery-next" aria-label="Fotografía siguiente">›</button>
                </div>
                <p id="pnGalleryViewerDescription" class="pn-gallery-viewer-description"></p>
            </div>
        </dialog>
    <?php endif; ?>
</section>
