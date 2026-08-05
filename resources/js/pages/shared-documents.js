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
    const filterToggle = document.querySelector('.document-filter-toggle');
    const filters = document.getElementById('documentFilters');

    const setFiltersOpen = (open) => {
        if (!filterToggle || !filters) return;
        filters.classList.toggle('is-open', open);
        filterToggle.classList.toggle('is-open', open);
        filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    filterToggle?.addEventListener('click', () => {
        setFiltersOpen(filterToggle.getAttribute('aria-expanded') !== 'true');
    });

    const applyView = (view) => {
        const selectedView = view === 'list' ? 'list' : 'grid';

        if (collection) {
            collection.dataset.view = selectedView;
            collection.classList.toggle('is-list', selectedView === 'list');
        }

        viewButtons.forEach((button) => {
            const active = button.dataset.documentView === selectedView;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    let preferredView = 'grid';
    try {
        preferredView = window.localStorage.getItem('smartlms-shared-documents-view') || 'grid';
    } catch {
        preferredView = 'grid';
    }
    applyView(preferredView);

    viewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const view = button.dataset.documentView;
            applyView(view);
            try {
                window.localStorage.setItem('smartlms-shared-documents-view', view);
            } catch {
                // Giao diện vẫn hoạt động nếu trình duyệt không cho phép lưu tùy chọn.
            }
        });
    });

    const editModal = document.getElementById('editDocumentModal');
    const editForm = document.getElementById('editDocumentForm');
    const editTitle = document.getElementById('editDocumentTitle');
    const editDescription = document.getElementById('editDocumentDescription');
    const editFolder = document.getElementById('editDocumentFolder');
    const editVisibility = document.getElementById('editDocumentVisibility');
    const editFileName = document.getElementById('editDocumentFileName');

    editModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement) || !trigger.matches('.document-edit-button')) return;

        if (editForm) editForm.action = trigger.dataset.updateUrl || '';
        if (editTitle) editTitle.value = trigger.dataset.documentTitle || '';
        if (editDescription) editDescription.value = trigger.dataset.documentDescription || '';
        if (editFolder) editFolder.value = trigger.dataset.documentFolder || '';
        if (editVisibility) editVisibility.value = trigger.dataset.documentVisibility || 'teachers';
        if (editFileName) editFileName.textContent = trigger.dataset.documentName || 'Tài liệu';
    });

    const previewModal = document.getElementById('previewDocumentModal');
    const previewTitle = document.getElementById('previewDocumentTitle');
    const previewImage = document.getElementById('documentPreviewImage');
    const previewFrame = document.getElementById('documentPreviewFrame');
    const previewLoading = document.getElementById('documentPreviewLoading');
    const previewLoadingText = previewLoading?.querySelector('span');
    const previewOpen = document.getElementById('documentPreviewOpen');
    const previewDownload = document.getElementById('documentPreviewDownload');

    const setPreviewLoading = (message = 'Đang mở tài liệu...') => {
        if (previewLoadingText) previewLoadingText.textContent = message;
        if (previewLoading) previewLoading.hidden = false;
    };

    const clearPreview = () => {
        previewImage?.removeAttribute('src');
        previewFrame?.removeAttribute('src');
        if (previewImage) previewImage.hidden = true;
        if (previewFrame) previewFrame.hidden = true;
        setPreviewLoading();
    };

    previewModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement)) return;

        const url = trigger.dataset.previewUrl;
        const type = trigger.dataset.previewType;
        const title = trigger.dataset.previewTitle || 'Tài liệu';
        const downloadUrl = trigger.dataset.downloadUrl || '#';
        if (!url || !['pdf', 'image'].includes(type)) return;

        clearPreview();
        if (previewTitle) previewTitle.textContent = title;
        if (previewOpen) previewOpen.href = url;
        if (previewDownload) previewDownload.href = downloadUrl;

        if (type === 'image' && previewImage) {
            previewImage.alt = `Xem trước ${title}`;
            previewImage.hidden = false;
            previewImage.src = url;
            return;
        }

        if (previewFrame) {
            previewFrame.title = `Xem trước ${title}`;
            previewFrame.hidden = false;
            previewFrame.src = url;
        }
    });

    previewImage?.addEventListener('load', () => {
        if (previewLoading) previewLoading.hidden = true;
    });
    previewImage?.addEventListener('error', () => {
        setPreviewLoading('Không thể hiển thị ảnh. Bạn vẫn có thể tải tài liệu xuống.');
    });
    previewFrame?.addEventListener('load', () => {
        if (previewLoading) previewLoading.hidden = true;
    });
    previewModal?.addEventListener('hidden.bs.modal', clearPreview);

    const uploadModal = document.getElementById('uploadDocumentModal');
    const uploadForm = document.getElementById('uploadDocumentForm');
    const uploadSubmit = document.getElementById('uploadDocumentSubmit');
    const dropzone = document.getElementById('documentDropzone');
    const input = document.getElementById('documentFilesInput');
    const picker = document.getElementById('documentFilePicker');
    const previews = document.getElementById('documentFilePreviews');
    const status = document.getElementById('documentDropzoneStatus');

    if (!dropzone || !input || !picker || !previews || !status) return;

    const maxFiles = 10;
    const maxFileSize = 20 * 1024 * 1024;
    const allowedExtensions = new Set([
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'html', 'htm', 'txt', 'csv', 'zip',
        'jpg', 'jpeg', 'png', 'webp',
    ]);
    let selectedFiles = [];

    const fileKey = (file) => `${file.name}:${file.size}:${file.lastModified}`;
    const fileExtension = (filename) => filename.split('.').pop()?.toLowerCase() || '';
    const formatSize = (bytes) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const fileIcon = (filename) => {
        const extension = fileExtension(filename);
        if (extension === 'pdf') return 'fa-file-pdf';
        if (['doc', 'docx'].includes(extension)) return 'fa-file-word';
        if (['xls', 'xlsx', 'csv'].includes(extension)) return 'fa-file-excel';
        if (['ppt', 'pptx'].includes(extension)) return 'fa-file-powerpoint';
        if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) return 'fa-file-image';
        if (extension === 'zip') return 'fa-file-zipper';
        if (['html', 'htm'].includes(extension)) return 'fa-file-code';
        return 'fa-file-lines';
    };

    const setStatus = (message, isError = false) => {
        status.textContent = message;
        status.classList.toggle('is-error', isError);
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
            icon.className = `fa-solid ${fileIcon(file.name)}`;
            icon.setAttribute('aria-hidden', 'true');

            const details = document.createElement('span');
            const name = document.createElement('strong');
            name.textContent = file.name;
            name.title = file.name;
            const size = document.createElement('small');
            size.textContent = formatSize(file.size);
            details.append(name, size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Bỏ ${file.name}`);
            remove.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
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
        setStatus(count > 0 ? `Đã chọn ${count}/${maxFiles} tài liệu` : 'Chưa chọn tài liệu');
    };

    const addFiles = (files) => {
        const knownFiles = new Set(selectedFiles.map(fileKey));
        const rejected = [];

        for (const file of files) {
            if (selectedFiles.length >= maxFiles) {
                rejected.push('Chỉ được chọn tối đa 10 tài liệu.');
                break;
            }
            if (knownFiles.has(fileKey(file))) continue;
            if (!allowedExtensions.has(fileExtension(file.name))) {
                rejected.push(`${file.name}: định dạng chưa được hỗ trợ.`);
                continue;
            }
            if (file.size > maxFileSize) {
                rejected.push(`${file.name}: vượt quá 20 MB.`);
                continue;
            }

            selectedFiles.push(file);
            knownFiles.add(fileKey(file));
        }

        syncInput();
        renderPreviews();
        if (rejected.length) setStatus(rejected[0], true);
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

    uploadForm?.addEventListener('submit', (event) => {
        if (!selectedFiles.length) {
            event.preventDefault();
            setStatus('Vui lòng chọn ít nhất một tài liệu.', true);
            dropzone.focus();
            return;
        }

        if (uploadSubmit) {
            uploadSubmit.setAttribute('disabled', 'disabled');
            uploadSubmit.setAttribute('aria-busy', 'true');
            uploadSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>Đang tải lên';
        }
    });

    uploadModal?.addEventListener('hidden.bs.modal', () => {
        selectedFiles = [];
        syncInput();
        renderPreviews();
        if (uploadSubmit) {
            uploadSubmit.removeAttribute('disabled');
            uploadSubmit.removeAttribute('aria-busy');
            uploadSubmit.innerHTML = '<i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>Tải lên';
        }
    });
});
