/**
 * Public site JavaScript
 * Lightbox, filters, keyboard nav
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // Lightbox for detail page
    // ============================================
    var mainPhotoImg = document.getElementById('main-photo-img');
    if (mainPhotoImg) {
        // Build lightbox DOM
        var overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.innerHTML = '<img src="" alt="">' +
            '<button class="lightbox-close" aria-label="Close">✕</button>' +
            '<button class="lightbox-nav lightbox-prev" aria-label="Previous">‹</button>' +
            '<button class="lightbox-nav lightbox-next" aria-label="Next">›</button>' +
            '<span class="lightbox-counter"></span>';
        document.body.appendChild(overlay);

        var lbImg = overlay.querySelector('img');
        var lbCounter = overlay.querySelector('.lightbox-counter');
        var lbPrev = overlay.querySelector('.lightbox-prev');
        var lbNext = overlay.querySelector('.lightbox-next');
        var thumbBtns = Array.from(document.querySelectorAll('.thumb-btn'));
        var currentIndex = 0;

        function getPhotos() {
            if (thumbBtns.length === 0) {
                return [{ src: mainPhotoImg.src, caption: '' }];
            }
            return thumbBtns.map(function (btn) {
                return { src: btn.dataset.original, caption: btn.dataset.caption || '' };
            });
        }

        function openLightbox(index) {
            var photos = getPhotos();
            currentIndex = index;
            lbImg.src = photos[currentIndex].src;
            lbCounter.textContent = (currentIndex + 1) + ' / ' + photos.length;
            lbPrev.style.display = photos.length > 1 ? '' : 'none';
            lbNext.style.display = photos.length > 1 ? '' : 'none';
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        function navigate(dir) {
            var photos = getPhotos();
            currentIndex = (currentIndex + dir + photos.length) % photos.length;
            lbImg.src = photos[currentIndex].src;
            lbCounter.textContent = (currentIndex + 1) + ' / ' + photos.length;
            // Also update main photo + thumbs
            if (thumbBtns[currentIndex]) {
                thumbBtns[currentIndex].click();
            }
        }

        // Click main photo to open lightbox
        mainPhotoImg.addEventListener('click', function () {
            var activeThumb = document.querySelector('.thumb-btn.active');
            var idx = activeThumb ? thumbBtns.indexOf(activeThumb) : 0;
            openLightbox(Math.max(0, idx));
        });

        // Close
        overlay.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeLightbox();
        });

        // Nav
        lbPrev.addEventListener('click', function (e) { e.stopPropagation(); navigate(-1); });
        lbNext.addEventListener('click', function (e) { e.stopPropagation(); navigate(1); });

        // Keyboard
        document.addEventListener('keydown', function (e) {
            if (!overlay.classList.contains('active')) {
                // Arrow keys on detail page (no lightbox)
                if (thumbBtns.length > 1) {
                    var active = document.querySelector('.thumb-btn.active');
                    if (!active) return;
                    var idx = thumbBtns.indexOf(active);
                    if (e.key === 'ArrowLeft' && idx > 0) thumbBtns[idx - 1].click();
                    if (e.key === 'ArrowRight' && idx < thumbBtns.length - 1) thumbBtns[idx + 1].click();
                }
                return;
            }
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') navigate(-1);
            if (e.key === 'ArrowRight') navigate(1);
        });

        // Touch swipe support
        var touchStartX = 0;
        overlay.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        overlay.addEventListener('touchend', function (e) {
            var diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 50) {
                navigate(diff > 0 ? -1 : 1);
            }
        }, { passive: true });
    }

    // ============================================
    // Multi-select filter checkboxes
    // ============================================
    document.querySelectorAll('.multi-filter-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var group = cb.closest('.filter-checkboxes');
            var hidden = group.querySelector('.multi-filter-hidden');
            var checked = group.querySelectorAll('.multi-filter-cb:checked');
            var values = [];
            checked.forEach(function (c) { values.push(c.value); });
            hidden.value = values.join(',');
        });
    });
});
