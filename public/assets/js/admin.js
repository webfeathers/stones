/**
 * Admin panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // File upload: show button + preview names
    // ============================================
    var uploadInput = document.getElementById('photo-upload');
    if (uploadInput) {
        var uploadBtn = uploadInput.closest('form') ? uploadInput.closest('form').querySelector('.upload-btn') : null;
        var uploadLabel = uploadInput.closest('.upload-area, .upload-area-inline');
        var labelText = uploadLabel ? uploadLabel.querySelector('.upload-label span:first-of-type') : null;
        var previewGrid = document.getElementById('photo-preview');

        uploadInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                if (uploadBtn) uploadBtn.style.display = 'inline-block';
                if (labelText) {
                    var names = [];
                    for (var i = 0; i < Math.min(this.files.length, 5); i++) {
                        names.push(this.files[i].name);
                    }
                    if (this.files.length > 5) {
                        names.push('... and ' + (this.files.length - 5) + ' more');
                    }
                    labelText.textContent = names.join(', ');
                }
                // Show thumbnail previews
                if (previewGrid) {
                    previewGrid.innerHTML = '';
                    for (var i = 0; i < this.files.length; i++) {
                        if (this.files[i].type.startsWith('image/')) {
                            var img = document.createElement('img');
                            img.file = this.files[i];
                            previewGrid.appendChild(img);
                            var reader = new FileReader();
                            reader.onload = (function(aImg) { return function(e) { aImg.src = e.target.result; }; })(img);
                            reader.readAsDataURL(this.files[i]);
                        }
                    }
                }
            }
        });

        // Also allow camera capture on mobile - add capture attribute
        if (/Mobi|Android|iPhone/i.test(navigator.userAgent)) {
            // Don't set capture attribute - let the user choose between camera and gallery
            // The accept attribute already handles this well on mobile
        }
    }

    // ============================================
    // Drag and drop file upload
    // ============================================
    var uploadArea = document.querySelector('.upload-area, .upload-area-inline');
    if (uploadArea && uploadInput) {
        ['dragenter', 'dragover'].forEach(function (event) {
            uploadArea.addEventListener(event, function (e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#6d28d9';
                uploadArea.style.background = '#f5f3ff';
            });
        });

        ['dragleave', 'drop'].forEach(function (event) {
            uploadArea.addEventListener(event, function (e) {
                e.preventDefault();
                uploadArea.style.borderColor = '';
                uploadArea.style.background = '';
            });
        });

        uploadArea.addEventListener('drop', function (e) {
            uploadInput.files = e.dataTransfer.files;
            uploadInput.dispatchEvent(new Event('change'));
        });
    }

    // ============================================
    // Sortable rows (simple drag-to-reorder)
    // ============================================
    initSortable('.photo-grid.sortable', '.photo-card');
    initSortableTable('#fields-table');

    // ============================================
    // Auto-dismiss alerts after 5 seconds
    // ============================================
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 300);
        }, 5000);
    });
});

/**
 * Simple drag-to-reorder for grid items
 */
function initSortable(containerSelector, itemSelector) {
    var container = document.querySelector(containerSelector);
    if (!container) return;

    var dragging = null;

    container.querySelectorAll(itemSelector).forEach(function (item) {
        item.draggable = true;

        item.addEventListener('dragstart', function (e) {
            dragging = this;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function () {
            this.style.opacity = '';
            dragging = null;
            saveOrder(container);
        });

        item.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        item.addEventListener('drop', function (e) {
            e.preventDefault();
            if (dragging !== this) {
                var items = Array.from(container.querySelectorAll(itemSelector));
                var fromIdx = items.indexOf(dragging);
                var toIdx = items.indexOf(this);
                if (fromIdx < toIdx) {
                    this.parentNode.insertBefore(dragging, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(dragging, this);
                }
            }
        });
    });
}

/**
 * Simple drag-to-reorder for table rows
 */
function initSortableTable(tableSelector) {
    var table = document.querySelector(tableSelector);
    if (!table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var dragging = null;

    tbody.querySelectorAll('tr').forEach(function (row) {
        var handle = row.querySelector('.drag-handle');
        if (!handle) return;

        row.draggable = true;

        row.addEventListener('dragstart', function (e) {
            dragging = this;
            this.style.opacity = '0.5';
        });

        row.addEventListener('dragend', function () {
            this.style.opacity = '';
            dragging = null;
            saveTableOrder(table);
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
        });

        row.addEventListener('drop', function (e) {
            e.preventDefault();
            if (dragging !== this) {
                var rows = Array.from(tbody.querySelectorAll('tr'));
                var fromIdx = rows.indexOf(dragging);
                var toIdx = rows.indexOf(this);
                if (fromIdx < toIdx) {
                    tbody.insertBefore(dragging, this.nextSibling);
                } else {
                    tbody.insertBefore(dragging, this);
                }
            }
        });
    });
}

/**
 * Save grid item order via AJAX
 */
function saveOrder(container) {
    var url = container.dataset.reorderUrl;
    if (!url) return;

    var items = container.querySelectorAll('[data-id]');
    var order = {};
    items.forEach(function (item, idx) {
        order[idx] = item.dataset.id;
    });

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order=' + encodeURIComponent(JSON.stringify(order))
    });
}

/**
 * Save table row order via AJAX
 */
function saveTableOrder(table) {
    var url = table.dataset.reorderUrl;
    if (!url) return;

    var rows = table.querySelectorAll('tbody tr[data-id]');
    var order = {};
    rows.forEach(function (row, idx) {
        order[idx] = row.dataset.id;
    });

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order=' + encodeURIComponent(JSON.stringify(order))
    });
}
