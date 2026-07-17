const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

onReady(() => {
    const collection = document.getElementById('documentCollection');
    const viewButtons = [...document.querySelectorAll('[data-document-view]')];

    const applyView = (view) => {
        const selectedView = view === 'list' ? 'list' : 'grid';

        if (collection) {
            collection.dataset.view = selectedView;
            collection.classList.toggle('is-list', selectedView === 'list');
        }

        viewButtons.forEach((button) => {
            const active = button.dataset.documentView === selectedView;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    applyView('grid');

    viewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const view = button.dataset.documentView;
            applyView(view);
        });
    });

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const numberFormatter = new Intl.NumberFormat('vi-VN');

    document.querySelectorAll('[data-document-count]').forEach((counter) => {
        const target = Number.parseInt(counter.dataset.documentCount || '0', 10);
        if (!Number.isFinite(target) || target <= 0 || reducedMotion) {
            counter.textContent = numberFormatter.format(Math.max(0, target || 0));
            return;
        }

        const duration = 750;
        const start = performance.now();
        counter.textContent = '0';

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = numberFormatter.format(Math.round(target * eased));

            if (progress < 1) {
                window.requestAnimationFrame(tick);
            }
        };

        window.requestAnimationFrame(tick);
    });

    const dropzone = document.getElementById('documentDropzone');
    const input = document.getElementById('documentFilesInput');
    const picker = document.getElementById('documentFilePicker');
    const previews = document.getElementById('documentFilePreviews');
    const status = document.getElementById('documentDropzoneStatus');

    if (!dropzone || !input || !picker || !previews || !status) {
        return;
    }

    const maxFiles = 10;
    let selectedFiles = [];

    const fileKey = (file) => `${file.name}:${file.size}:${file.lastModified}`;
    const formatSize = (bytes) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const fileIcon = (filename) => {
        const extension = filename.split('.').pop()?.toLowerCase();
        if (extension === 'pdf') return 'fa-file-pdf';
        if (['doc', 'docx'].includes(extension)) return 'fa-file-word';
        if (['xls', 'xlsx', 'csv'].includes(extension)) return 'fa-file-excel';
        if (['ppt', 'pptx'].includes(extension)) return 'fa-file-powerpoint';
        if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) return 'fa-file-image';
        if (['zip'].includes(extension)) return 'fa-file-zipper';
        if (['html', 'htm'].includes(extension)) return 'fa-file-code';
        return 'fa-file-lines';
    };

    const syncInput = () => {
        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    };

    const renderPreviews = () => {
        previews.replaceChildren();

        selectedFiles.forEach((file, index) => {
            const chip = document.createElement('div');
            chip.className = 'document-file-chip';

            const icon = document.createElement('i');
            icon.className = `fas ${fileIcon(file.name)}`;

            const details = document.createElement('span');
            const name = document.createElement('strong');
            name.textContent = file.name;
            name.title = file.name;
            const size = document.createElement('small');
            size.textContent = formatSize(file.size);
            details.append(name, size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Xóa ${file.name}`);
            remove.title = 'Bỏ file này';
            remove.innerHTML = '<i class="fas fa-xmark"></i>';
            remove.addEventListener('click', (event) => {
                event.stopPropagation();
                selectedFiles.splice(index, 1);
                syncInput();
                renderPreviews();
            });

            chip.append(icon, details, remove);
            previews.appendChild(chip);
        });

        const count = selectedFiles.length;
        dropzone.classList.toggle('has-files', count > 0);
        status.textContent = count > 0 ? `Đã chọn ${count}/${maxFiles} file` : `Tối đa ${maxFiles} file`;
    };

    const addFiles = (files) => {
        const knownFiles = new Set(selectedFiles.map(fileKey));

        for (const file of files) {
            if (selectedFiles.length >= maxFiles) break;
            if (knownFiles.has(fileKey(file))) continue;

            selectedFiles.push(file);
            knownFiles.add(fileKey(file));
        }

        syncInput();
        renderPreviews();
    };

    picker.addEventListener('click', (event) => {
        event.stopPropagation();
        input.click();
    });

    dropzone.addEventListener('click', (event) => {
        if (event.target.closest('.document-file-chip, .document-file-picker')) return;
        input.click();
    });

    dropzone.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        input.click();
    });

    input.addEventListener('change', () => addFiles(input.files));

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });

    dropzone.addEventListener('drop', (event) => addFiles(event.dataTransfer?.files || []));

    document.getElementById('uploadDocumentModal')?.addEventListener('hidden.bs.modal', () => {
        selectedFiles = [];
        syncInput();
        renderPreviews();
    });
});
