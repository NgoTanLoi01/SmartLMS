@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        .modal-backdrop {
            z-index: 1050 !important;
        }

        /* Đảm bảo cửa sổ Modal nằm trên cùng */
        .modal {
            z-index: 1060 !important;
        }

        /* Nếu bạn có sidebar sticky, hãy hạ thấp z-index của nó xuống khi cần */
        .sticky-top {
            z-index: 1000 !important;
        }

        /* Tối ưu Sidebar */
        .lesson-item-wrapper {
            position: relative;
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

        /* Ẩn hiện các nút điều khiển khi hover */
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .lesson-item-wrapper:hover .action-buttons,
        .accordion-item:hover .action-buttons {
            opacity: 1;
        }

        /* Style cho các nút Sửa/Xóa */
        .btn-action {
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 12px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-edit {
            color: #ffc107;
        }

        .btn-edit:hover {
            background: #fff4d5;
        }

        .btn-delete {
            color: #dc3545;
        }

        .btn-delete:hover {
            background: #ffe8e8;
        }

        /* Xử lý text quá dài */
        .text-truncate-custom {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 75%;
        }

        .accordion-button:not(.collapsed) {
            background-color: #ffffff;
            color: #0d6efd;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 px-3">
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $course->title }}</h3>
                <p class="text-muted mb-0 small">Giảng viên: {{ $course->teacher->name }}</p>
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
                                    <div class="d-flex align-items-center pe-2">
                                        <button
                                            class="accordion-button {{ $index == 0 ? '' : 'collapsed' }} py-3 fw-bold flex-grow-1 shadow-none"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#module-{{ $module->id }}">
                                            <span class="text-truncate-custom">{{ $module->title }}</span>
                                        </button>

                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                            <div class="action-buttons d-flex">
                                                <a href="javascript:void(0)" class="btn-action btn-edit edit-module-btn"
                                                    data-id="{{ $module->id }}" data-title="{{ $module->title }}"
                                                    data-bs-toggle="modal" data-bs-target="#editModuleModal">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('modules.destroy', $module->id) }}" method="POST"
                                                    class="d-inline">
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
                                                    <div
                                                        class="list-group-item border-0 px-3 py-2 lesson-item-wrapper d-flex align-items-center justify-content-between shadow-none">
                                                        <a href="javascript:void(0)"
                                                            class="lesson-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                            data-id="{{ $lesson->id }}"
                                                            data-content="{{ $lesson->content }}"
                                                            data-title="{{ $lesson->title }}"
                                                            data-video="{{ $lesson->video_url }}"
                                                            data-module="{{ $module->id }}">
                                                            <i class="far fa-play-circle me-2 text-primary"></i>
                                                            <span
                                                                class="small text-truncate-custom">{{ $lesson->title }}</span>
                                                        </a>

                                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                            <div class="action-buttons d-flex">
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
                                                                    method="POST" class="d-inline">
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

            <div class="col-md-8 col-lg-9">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    <div class="card-body p-4" id="lesson-content-area">
                        <h2 id="lesson-title" class="fw-bold mb-3 text-dark">{{ $course->title }}</h2>
                        <hr>
                        <div id="lesson-body" class="lh-lg text-secondary">
                            <p>{{ $course->description }}</p>
                            <div class="text-center py-5">
                                <i class="fas fa-book-reader fa-3x text-light mb-3 d-block"></i>
                                <h5 class="text-muted">Hãy chọn bài học để bắt đầu</h5>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top p-4 d-flex justify-content-between align-items-center">
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm" id="btn-prev" disabled>Bài
                            trước</button>
                        <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" id="btn-complete">Hoàn thành
                            bài học</button>
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm" id="btn-next" disabled>Bài
                            tiếp theo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <button type="submit" class="btn btn-warning text-white rounded-pill px-4 w-100">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

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
                        <input type="text" name="title" class="form-control bg-light border-0"
                            placeholder="Tiêu đề bài học..." required>
                    </div>
                    <div class="mb-3">
                        <input type="url" name="video_url" class="form-control bg-light border-0"
                            placeholder="Link Youtube...">
                    </div>
                    <textarea name="content" class="form-control bg-light border-0" rows="4" placeholder="Nội dung chi tiết..."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Lưu bài học</button>
                </div>
            </form>
        </div>
    </div>

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
                        <input type="text" name="title" id="editLessonTitle" class="form-control bg-light border-0"
                            required>
                    </div>
                    <div class="mb-3">
                        <input type="url" name="video_url" id="editLessonVideo"
                            class="form-control bg-light border-0">
                    </div>
                    <textarea name="content" id="editLessonContent" class="form-control bg-light border-0" rows="4"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning text-white rounded-pill px-4 w-100">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.lesson-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.lesson-item-wrapper').forEach(li => li.classList.remove(
                        'active'));
                    this.closest('.lesson-item-wrapper').classList.add('active');

                    const title = this.getAttribute('data-title');
                    const content = this.getAttribute('data-content');
                    const videoUrl = this.getAttribute('data-video');

                    document.getElementById('lesson-title').innerText = title;
                    document.getElementById('lesson-body').innerHTML = content;

                    const videoContainer = document.getElementById('video-container');
                    const iframe = document.getElementById('lesson-video');

                    if (videoUrl && (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be'))) {
                        let videoId = videoUrl.includes('v=') ? videoUrl.split('v=')[1].split('&')[0] : videoUrl
                            .split('/').pop();
                        iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                        videoContainer.classList.remove('d-none');
                    } else {
                        videoContainer.classList.add('d-none');
                        iframe.src = '';
                    }
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
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
