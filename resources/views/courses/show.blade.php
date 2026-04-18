@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        .modal-backdrop {
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        .sticky-top {
            z-index: 1000 !important;
        }

        /* Tối ưu Sidebar & Hover */
        .lesson-item-wrapper {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .lesson-item-wrapper:hover {
            background-color: #f8f9fa !important;
            border-left-color: #0d6efd;
        }

        .lesson-item-wrapper.active {
            background-color: #e7f1ff !important;
            border-left-color: #0d6efd;
        }

        /* Sửa lỗi UI Icon đè text */
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s ease;
            flex-shrink: 0;
            background: #fff;
            padding-left: 8px;
        }

        .lesson-item-wrapper:hover .action-buttons,
        .module-header-wrapper:hover .action-buttons {
            opacity: 1;
        }

        .lesson-item-wrapper.active .action-buttons {
            background: #e7f1ff;
        }

        .btn-action {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-edit {
            color: #f59f00;
        }

        .btn-edit:hover {
            background: #fff4d5;
        }

        .btn-delete {
            color: #fa5252;
        }

        .btn-delete:hover {
            background: #ffe8e8;
        }

        /* Giúp text cắt gọn gàng (dấu 3 chấm) */
        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            min-width: 0;
        }

        .accordion-button:not(.collapsed) {
            background-color: #ffffff;
            color: #0d6efd;
        }

        .accordion-button {
            padding-right: 3rem;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 px-3">
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $course->title }}</h3>
                <p class="text-muted mb-0 small">Giảng viên: {{ $course->teacher->name }}</p>
                @if (auth()->user()->role === 'student')
                    <div class="mt-3" style="max-width: 400px;">
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted fw-medium">Tiến độ học tập</span>
                            <span class="text-primary fw-bold" id="progress-text">{{ $completedCount }}/{{ $totalLessons }}
                                bài ({{ $progress }}%)</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <div id="progress-bar" class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ $progress }}%; transition: width 0.5s ease;"></div>
                        </div>
                    </div>
                @endif

            </div>

            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addModuleModal">
                        <i class="fas fa-folder-plus me-1"></i> Chương
                    </button>
                    <button class="btn btn-primary rounded-pill px-3 shadow-sm btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addLessonModal" {{ $course->modules->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-plus me-1"></i> Bài học
                    </button>
                </div>
            @endif
        </div>

        <div class="row g-4">
            <!-- Sidebar Danh sách bài học -->
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold small text-uppercase text-muted"><i class="fas fa-list-ol me-2"></i>Nội dung
                            học tập</h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 75vh; overflow-y: auto;">
                        <div class="accordion accordion-flush" id="courseAccordion">
                            @forelse ($course->modules as $index => $module)
                                <div class="accordion-item border-bottom">
                                    <div class="position-relative module-header-wrapper d-flex align-items-center">
                                        <button
                                            class="accordion-button {{ $index == 0 ? '' : 'collapsed' }} py-3 fw-bold flex-grow-1 shadow-none"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#module-{{ $module->id }}">
                                            <span class="text-truncate-custom me-4">{{ $module->title }}</span>
                                        </button>

                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                            <div
                                                class="action-buttons position-absolute end-0 me-5 d-flex align-items-center">
                                                <a href="javascript:void(0)" class="btn-action btn-edit edit-module-btn"
                                                    data-id="{{ $module->id }}" data-title="{{ $module->title }}"
                                                    data-bs-toggle="modal" data-bs-target="#editModuleModal">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('modules.destroy', $module->id) }}" method="POST"
                                                    class="d-inline mb-0">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="btn-action btn-delete border-0 bg-transparent"
                                                        onclick="return confirm('Xóa chương này?')"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    <div id="module-{{ $module->id }}"
                                        class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}"
                                        data-bs-parent="#courseAccordion">
                                        <div class="accordion-body p-0">
                                            <div class="list-group list-group-flush">
                                                @forelse ($module->lessons as $lesson)
                                                    {{-- ✅ SỬA: Kiểm tra trạng thái hoàn thành đúng vị trí trong vòng lặp --}}
                                                    @php
                                                        $isCompleted = in_array($lesson->id, $completedLessonIds);
                                                    @endphp
                                                    <div
                                                        class="list-group-item border-0 px-3 py-2 lesson-item-wrapper d-flex align-items-center justify-content-between shadow-none">

                                                        <a href="javascript:void(0)"
                                                            class="lesson-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                            style="min-width: 0;" data-id="{{ $lesson->id }}"
                                                            data-content="{{ $lesson->content }}"
                                                            data-title="{{ $lesson->title }}"
                                                            data-video="{{ $lesson->video_url }}"
                                                            data-module="{{ $module->id }}">
                                                            {{-- ✅ SỬA: Icon thay đổi theo trạng thái hoàn thành --}}
                                                            <i class="{{ $isCompleted ? 'fas fa-check-circle text-success' : 'far fa-play-circle text-primary' }} me-2 flex-shrink-0 lesson-icon"
                                                                id="icon-lesson-{{ $lesson->id }}"></i>
                                                            <span
                                                                class="small text-truncate-custom">{{ $lesson->title }}</span>
                                                        </a>

                                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                            <div class="action-buttons d-flex ms-2">
                                                                <a href="javascript:void(0)"
                                                                    class="btn-action btn-edit edit-lesson-btn"
                                                                    data-id="{{ $lesson->id }}"
                                                                    data-title="{{ $lesson->title }}"
                                                                    data-content="{{ $lesson->content }}"
                                                                    data-video="{{ $lesson->video_url }}"
                                                                    data-module="{{ $module->id }}"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editLessonModal">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form action="{{ route('lessons.destroy', $lesson->id) }}"
                                                                    method="POST" class="d-inline mb-0">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn-action btn-delete border-0 bg-transparent"
                                                                        onclick="return confirm('Xóa bài này?')"><i
                                                                            class="fas fa-times"></i></button>
                                                                </form>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div class="py-2 px-4 text-muted small fst-italic">Trống</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted small">Chưa có nội dung.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khu vực hiển thị Video & Nội dung -->
            <div class="col-md-8 col-lg-9">
                <div class="card border-0 shadow-sm overflow-hidden h-100 d-flex flex-column">

                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    <div id="external-link-container"
                        class="bg-primary bg-opacity-10 p-4 text-center d-none border-bottom">
                        <i class="fas fa-external-link-alt fa-3x text-primary mb-3"></i>
                        <h5 class="fw-bold text-dark">Tài liệu / Video tham khảo ngoài</h5>
                        <p class="text-muted small mb-3">Bài học này chứa một liên kết ngoài hệ thống. Vui lòng bấm nút bên
                            dưới để truy cập.</p>
                        <a href="#" id="external-link-btn" target="_blank"
                            class="btn btn-primary rounded-pill px-4 shadow-sm">
                            Truy cập liên kết ngay
                        </a>
                    </div>

                    <div class="card-body p-4 flex-grow-1" id="lesson-content-area">
                        <h2 id="lesson-title" class="fw-bold mb-3 text-dark">{{ $course->title }}</h2>
                        <hr>
                        <div id="lesson-body" class="lh-lg text-secondary">
                            <p>{{ $course->description }}</p>
                            <div class="text-center py-5" id="welcome-placeholder">
                                <i class="fas fa-book-reader fa-3x text-light mb-3 d-block"></i>
                                <h5 class="text-muted">Hãy chọn bài học để bắt đầu</h5>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ SỬA: Card footer đã được dọn sạch, xóa thẻ <a> nhầm lẫn bên trong #btn-complete --}}
                    <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm fw-medium" id="btn-prev"
                            disabled>
                            <i class="fas fa-arrow-left me-1"></i> Bài trước
                        </button>

                        <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold d-none" id="btn-complete">
                            <i class="fas fa-check-circle me-1"></i> Hoàn thành bài học
                        </button>

                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm fw-medium" id="btn-next"
                            disabled>
                            Bài tiếp theo <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CÁC MODALS -->
    <!-- Modal Thêm Chương -->
    <div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('modules.store') }}" method="POST" class="modal-content border-0">
                @csrf
                <input type="hidden" name="course_id" value="{{ $course->id }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm chương mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="text" name="title" class="form-control bg-light border-0"
                        placeholder="Tên chương học..." required>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Chương -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editModuleForm" method="POST" class="modal-content border-0">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-warning">Sửa chương</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="text" name="title" id="editModuleTitle" class="form-control bg-light border-0"
                        required>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 w-100">Cập
                        nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Thêm Bài Học -->
    <div class="modal fade" id="addLessonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('lessons.store') }}" method="POST" class="modal-content border-0">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm bài học mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Chọn chương</label>
                        <select name="module_id" class="form-select bg-light border-0" required>
                            @foreach ($course->modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Tiêu đề bài học</label>
                        <input type="text" name="title" class="form-control bg-light border-0"
                            placeholder="Tiêu đề bài học..." required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Link (Youtube, Google Drive, Zoom...)</label>
                        <input type="url" name="video_url" class="form-control bg-light border-0"
                            placeholder="https://...">
                    </div>
                    <label class="small fw-bold">Nội dung chi tiết</label>
                    <textarea name="content" class="form-control bg-light border-0" rows="4" placeholder="Nhập nội dung..."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Lưu bài học</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Bài Học -->
    <div class="modal fade" id="editLessonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editLessonForm" method="POST" class="modal-content border-0">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-warning">Sửa bài học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Chương</label>
                        <select name="module_id" id="editLessonModule" class="form-select bg-light border-0" required>
                            @foreach ($course->modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Tiêu đề bài học</label>
                        <input type="text" name="title" id="editLessonTitle" class="form-control bg-light border-0"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Link (Youtube, Google Drive, Zoom...)</label>
                        <input type="url" name="video_url" id="editLessonVideo"
                            class="form-control bg-light border-0">
                    </div>
                    <label class="small fw-bold">Nội dung chi tiết</label>
                    <textarea name="content" id="editLessonContent" class="form-control bg-light border-0" rows="4"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 w-100">Cập
                        nhật</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let totalLessonsCount = {{ $totalLessons ?? 0 }};
            let currentCompletedCount = {{ $completedCount ?? 0 }};

            function getYoutubeId(url) {
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                const match = url.match(regExp);
                return (match && match[2].length === 11) ? match[2] : null;
            }

            let currentLessonIndex = -1;
            const lessons = Array.from(document.querySelectorAll('.lesson-item'));
            let currentLessonId = null;

            function updateNavButtons() {
                document.getElementById('btn-prev').disabled = (currentLessonIndex <= 0);
                document.getElementById('btn-next').disabled = (currentLessonIndex === -1 || currentLessonIndex >= lessons
                    .length - 1);

                const btnComplete = document.getElementById('btn-complete');
                if (currentLessonIndex !== -1) {
                    btnComplete.classList.remove('d-none');
                    btnComplete.classList.replace('btn-secondary', 'btn-success');
                    btnComplete.innerHTML = '<i class="fas fa-check-circle me-1"></i> Hoàn thành bài học';
                    btnComplete.disabled = false;
                }
            }

            lessons.forEach((item, index) => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();

                    document.querySelectorAll('.lesson-item-wrapper').forEach(li => li.classList.remove(
                        'active'));
                    this.closest('.lesson-item-wrapper').classList.add('active');

                    currentLessonId = this.getAttribute('data-id');
                    currentLessonIndex = index;
                    updateNavButtons();

                    const title = this.getAttribute('data-title');
                    const content = this.getAttribute('data-content');
                    const videoUrl = this.getAttribute('data-video');

                    document.getElementById('lesson-title').innerText = title;
                    document.getElementById('lesson-body').innerHTML = content ||
                        '<p class="text-muted fst-italic">Không có nội dung văn bản.</p>';

                    const placeholder = document.getElementById('welcome-placeholder');
                    if (placeholder) placeholder.style.display = 'none';

                    const videoContainer = document.getElementById('video-container');
                    const externalContainer = document.getElementById('external-link-container');
                    const iframe = document.getElementById('lesson-video');
                    const externalBtn = document.getElementById('external-link-btn');

                    const ytId = videoUrl ? getYoutubeId(videoUrl) : null;

                    if (ytId) {
                        iframe.src = `https://www.youtube.com/embed/${ytId}?autoplay=1`;
                        videoContainer.classList.remove('d-none');
                        externalContainer.classList.add('d-none');
                    } else if (videoUrl && videoUrl.trim() !== '') {
                        iframe.src = '';
                        videoContainer.classList.add('d-none');
                        externalBtn.href = videoUrl;
                        externalContainer.classList.remove('d-none');
                    } else {
                        iframe.src = '';
                        videoContainer.classList.add('d-none');
                        externalContainer.classList.add('d-none');
                    }

                    document.getElementById('lesson-content-area').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            document.getElementById('btn-prev').addEventListener('click', () => {
                if (currentLessonIndex > 0) lessons[currentLessonIndex - 1].click();
            });

            document.getElementById('btn-next').addEventListener('click', () => {
                if (currentLessonIndex < lessons.length - 1) lessons[currentLessonIndex + 1].click();
            });

            document.getElementById('btn-complete').addEventListener('click', function() {
                if (!currentLessonId) return;

                axios.post(`/lessons/${currentLessonId}/complete`)
                    .then(response => {
                        this.classList.replace('btn-success', 'btn-secondary');
                        this.innerHTML = '<i class="fas fa-check me-1"></i> Đã hoàn thành';
                        this.disabled = true;

                        const icon = document.getElementById('icon-lesson-' + currentLessonId);
                        if (icon && !icon.classList.contains('fa-check-circle')) {
                            icon.className = 'fas fa-check-circle text-success me-2 flex-shrink-0 lesson-icon';

                            currentCompletedCount++;
                            let newProgress = Math.round((currentCompletedCount / totalLessonsCount) * 100);

                            const progressText = document.getElementById('progress-text');
                            const progressBar = document.getElementById('progress-bar');

                            if (progressText) progressText.innerText =
                                `${currentCompletedCount}/${totalLessonsCount} bài (${newProgress}%)`;
                            if (progressBar) progressBar.style.width = newProgress + '%';
                        }

                        setTimeout(() => {
                            document.getElementById('btn-next').click();
                        }, 1000);
                    });
            });

            document.querySelectorAll('.edit-module-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('editModuleForm').action =
                        `/modules/${this.getAttribute('data-id')}`;
                    document.getElementById('editModuleTitle').value = this.getAttribute('data-title');
                });
            });

            document.querySelectorAll('.edit-lesson-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('editLessonForm').action = `/lessons/${id}`;
                    document.getElementById('editLessonTitle').value = this.getAttribute('data-title');
                    document.getElementById('editLessonContent').value = this.getAttribute('data-content');
                    document.getElementById('editLessonVideo').value = this.getAttribute('data-video');
                    document.getElementById('editLessonModule').value = this.getAttribute('data-module');
                });
            });
        </script>
    @endpush
@endsection
