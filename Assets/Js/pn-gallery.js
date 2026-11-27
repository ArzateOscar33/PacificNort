/* PacificNort — Fase 8. Visor de imágenes sin dependencias. */
(function () {
    'use strict';
    const root = document.querySelector('#galeria.pn-gallery');
    if (!root) return;
    const dialog = root.querySelector('#pnGalleryViewer');
    const photos = Array.from(root.querySelectorAll('[data-pn-photo]'));
    if (!dialog || !photos.length || typeof dialog.showModal !== 'function') return;
    const image = dialog.querySelector('#pnGalleryLarge');
    const title = dialog.querySelector('#pnGalleryViewerTitle');
    const description = dialog.querySelector('#pnGalleryViewerDescription');
    const counter = dialog.querySelector('#pnGalleryCounter');
    const error = dialog.querySelector('#pnGalleryError');
    const prev = dialog.querySelector('[data-pn-prev]');
    const next = dialog.querySelector('[data-pn-next]');
    const close = dialog.querySelector('[data-pn-close]');
    let current = 0;
    let opener = null;
    let priorOverflow = '';
    let locked = false;

    function render(index) {
        current = (index + photos.length) % photos.length;
        const photo = photos[current];
        title.textContent = photo.querySelector('.pn-gallery-name').textContent;
        description.textContent = photo.querySelector('.pn-gallery-description').textContent;
        counter.textContent = `${current + 1} / ${photos.length}`;
        image.hidden = false;
        error.hidden = true;
        image.alt = title.textContent;
        image.src = photo.href;
        prev.disabled = next.disabled = photos.length < 2;
    }
    image.addEventListener('error', () => {
        if (!dialog.open) return;
        image.hidden = true;
        error.hidden = false;
    });
    photos.forEach((photo, index) => {
        photo.addEventListener('click', event => {
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            if (dialog.open) return;
            // If the browser cannot open the dialog, preserve normal link navigation.
            try { dialog.showModal(); } catch (_) { return; }
            event.preventDefault();
            opener = photo;
            priorOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            locked = true;
            render(index);
            close.focus();
        });
    });
    prev.addEventListener('click', () => render(current - 1));
    next.addEventListener('click', () => render(current + 1));
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('keydown', event => {
        if (photos.length < 2) return;
        if (event.key === 'ArrowLeft') { event.preventDefault(); render(current - 1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); render(current + 1); }
    });
    dialog.addEventListener('click', event => {
        if (event.target === dialog) {
            const r = dialog.getBoundingClientRect();
            if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        if (dialog.open) return;
        if (locked) document.body.style.overflow = priorOverflow;
        locked = false;
        image.removeAttribute('src');
        if (opener) opener.focus({preventScroll:true});
        opener = null;
    });
})();
