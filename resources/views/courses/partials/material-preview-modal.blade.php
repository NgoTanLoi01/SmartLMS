@once
    <style>
        .material-preview-dialog { max-width: min(1180px, calc(100vw - 28px)); }
        .material-preview-shell { border: 0; border-radius: 18px; overflow: hidden; }
        .material-preview-head { align-items: flex-start; border-bottom: 1px solid #e5e7eb; display: flex; gap: 14px; padding: 16px 20px; }
        .material-preview-head__icon { align-items: center; background: #eef4ff; border-radius: 11px; color: #2f6fed; display: inline-flex; flex: 0 0 40px; height: 40px; justify-content: center; }
        .material-preview-title { color: #111827; font-size: 17px; font-weight: 800; margin: 0; overflow-wrap: anywhere; }
        .material-preview-note { color: #64748b; font-size: 12px; margin: 3px 0 0; }
        .material-preview-body { align-items: center; background: #f1f5f9; display: flex; justify-content: center; min-height: 68vh; padding: 16px; position: relative; }
        .material-preview-loading,
        .material-preview-error { align-items: center; color: #64748b; display: flex; flex-direction: column; gap: 10px; justify-content: center; min-height: 240px; text-align: center; }
        .material-preview-error { color: #b91c1c; }
        .material-preview-error i { font-size: 28px; }
        .material-preview-media { background: #fff; border: 0; border-radius: 10px; box-shadow: 0 8px 24px rgba(15, 23, 42, .1); max-height: calc(100vh - 190px); max-width: 100%; }
        .material-preview-frame { height: 68vh; width: 100%; }
        .material-preview-image { object-fit: contain; }
        .material-preview-video { background: #0f172a; width: min(100%, 980px); }
        .material-preview-media[hidden],
        .material-preview-loading[hidden],
        .material-preview-error[hidden] { display: none !important; }
        @media (max-width: 575.98px) {
            .material-preview-body { min-height: 62vh; padding: 8px; }
            .material-preview-frame { height: 62vh; }
        }
    </style>

    <div class="modal fade" id="materialPreviewModal" tabindex="-1" aria-labelledby="materialPreviewTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered material-preview-dialog">
            <div class="modal-content material-preview-shell">
                <div class="material-preview-head">
                    <span class="material-preview-head__icon"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
                    <div class="flex-grow-1 min-w-0">
                        <h2 class="material-preview-title" id="materialPreviewTitle">Xem trước học liệu</h2>
                        <p class="material-preview-note">Nội dung được mở trực tiếp, không cần tải file về thiết bị.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body material-preview-body">
                    <div class="material-preview-loading" id="materialPreviewLoading" role="status" aria-live="polite">
                        <span class="spinner-border text-primary" aria-hidden="true"></span>
                        <span>Đang tải học liệu...</span>
                    </div>
                    <div class="material-preview-error" id="materialPreviewError" hidden>
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <span>Không thể hiển thị học liệu. Vui lòng đóng cửa sổ và thử lại.</span>
                    </div>
                    <img class="material-preview-media material-preview-image" id="materialPreviewImage" alt="" hidden>
                    <video class="material-preview-media material-preview-video" id="materialPreviewVideo" controls playsinline preload="metadata" hidden></video>
                    <iframe class="material-preview-media material-preview-frame" id="materialPreviewFrame" title="Nội dung học liệu" referrerpolicy="no-referrer" hidden></iframe>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('materialPreviewModal');
                if (!modal) return;

                const title = document.getElementById('materialPreviewTitle');
                const loading = document.getElementById('materialPreviewLoading');
                const error = document.getElementById('materialPreviewError');
                const image = document.getElementById('materialPreviewImage');
                const video = document.getElementById('materialPreviewVideo');
                const frame = document.getElementById('materialPreviewFrame');

                const reset = () => {
                    image.removeAttribute('src');
                    image.alt = '';
                    image.hidden = true;
                    video.pause();
                    video.removeAttribute('src');
                    video.load();
                    video.hidden = true;
                    frame.removeAttribute('src');
                    frame.hidden = true;
                    error.hidden = true;
                    loading.hidden = false;
                };
                const loaded = element => {
                    loading.hidden = true;
                    element.hidden = false;
                };
                const failed = () => {
                    loading.hidden = true;
                    error.hidden = false;
                };

                image.addEventListener('load', () => loaded(image));
                image.addEventListener('error', failed);
                video.addEventListener('loadedmetadata', () => loaded(video));
                video.addEventListener('error', failed);
                frame.addEventListener('load', () => loaded(frame));

                modal.addEventListener('show.bs.modal', event => {
                    const trigger = event.relatedTarget;
                    if (!trigger?.matches('[data-material-preview]')) return;

                    reset();
                    title.textContent = trigger.dataset.previewTitle || 'Xem trước học liệu';
                    const url = trigger.dataset.previewUrl;
                    const type = trigger.dataset.previewType;

                    if (!url) {
                        failed();
                    } else if (type === 'image') {
                        image.alt = trigger.dataset.previewTitle || 'Hình ảnh học liệu';
                        image.src = url;
                    } else if (type === 'video') {
                        video.src = url;
                        video.load();
                    } else {
                        frame.src = url;
                    }
                });

                modal.addEventListener('hidden.bs.modal', reset);
            });
        </script>
    @endpush
@endonce
