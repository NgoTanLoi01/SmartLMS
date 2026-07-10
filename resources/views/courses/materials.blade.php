@extends('layouts.app')

@section('content')
    <style>
        .materials-page {
            background: #f4f7fb;
            min-height: calc(100vh - 70px);
            padding: 28px 0 48px;
        }

        .materials-shell {
            max-width: 1320px;
        }

        .materials-hero,
        .materials-panel,
        .material-card {
            background: #fff;
            border: 1px solid #e5ebf5;
            border-radius: 20px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }

        .materials-hero {
            align-items: center;
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-bottom: 18px;
            padding: 24px;
        }

        .materials-kicker {
            align-items: center;
            color: #2f6fed;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 8px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .materials-title {
            color: #111827;
            font-size: clamp(26px, 3vw, 40px);
            font-weight: 950;
            letter-spacing: 0;
            line-height: 1.15;
            margin: 8px 0 6px;
        }

        .materials-subtitle {
            color: #64748b;
            font-size: 15px;
            line-height: 1.55;
            margin: 0;
            max-width: 760px;
        }

        .materials-back {
            align-items: center;
            background: #eef4ff;
            border-radius: 999px;
            color: #2f6fed;
            display: inline-flex;
            font-weight: 900;
            gap: 8px;
            padding: 11px 15px;
            text-decoration: none;
            white-space: nowrap;
        }

        .materials-panel {
            margin-bottom: 18px;
            padding: 20px;
        }

        .materials-panel-title {
            align-items: center;
            color: #111827;
            display: flex;
            font-size: 18px;
            font-weight: 950;
            gap: 10px;
            margin: 0 0 16px;
        }

        .materials-panel-title i {
            color: #2f6fed;
        }

        .materials-form-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .mf-col-3 {
            grid-column: span 3;
        }

        .mf-col-4 {
            grid-column: span 4;
        }

        .mf-col-6 {
            grid-column: span 6;
        }

        .mf-col-12 {
            grid-column: span 12;
        }

        .materials-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .materials-input {
            border: 1px solid #dce5f2;
            border-radius: 12px;
            font-size: 14px;
            min-height: 44px;
        }

        .materials-input:focus {
            border-color: #2f6fed;
            box-shadow: 0 0 0 .2rem rgba(47, 111, 237, .12);
        }

        .materials-submit {
            background: #2f6fed;
            border: 0;
            border-radius: 12px;
            color: #fff;
            font-weight: 950;
            min-height: 44px;
            padding: 0 18px;
        }

        .materials-submit:hover {
            background: #1f56c8;
            color: #fff;
        }

        .material-list {
            display: grid;
            gap: 14px;
        }

        .material-card {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 18px;
        }

        .material-main {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .material-icon {
            align-items: center;
            background: #eaf1ff;
            border-radius: 16px;
            color: #2f6fed;
            display: inline-flex;
            flex: 0 0 48px;
            font-size: 20px;
            height: 48px;
            justify-content: center;
            width: 48px;
        }

        .material-name {
            color: #111827;
            font-size: 17px;
            font-weight: 950;
            line-height: 1.35;
            margin: 0;
        }

        .material-meta {
            color: #64748b;
            display: flex;
            flex-wrap: wrap;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            margin-top: 7px;
        }

        .material-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            padding: 5px 9px;
        }

        .material-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .material-btn {
            border-radius: 10px;
            font-size: 13px;
            font-weight: 900;
            padding: 8px 11px;
        }

        .empty-materials {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            color: #64748b;
            font-weight: 800;
            padding: 32px;
            text-align: center;
        }

        @media (max-width: 991.98px) {

            .materials-hero,
            .material-card {
                grid-template-columns: 1fr;
            }

            .materials-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .mf-col-3,
            .mf-col-4,
            .mf-col-6 {
                grid-column: span 12;
            }

            .material-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="materials-page">
        <div class="container materials-shell">
            <div class="materials-hero">
                <div>
                    <div class="materials-kicker">
                        <i class="fas fa-folder-open"></i> Kho học liệu
                    </div>
                    <h1 class="materials-title">{{ $course->title }}</h1>
                    <p class="materials-subtitle">
                        Quản lý PDF, slide, link video, website tham khảo, file code mẫu và học liệu theo từng bài học.
                        Một file có thể dùng lại cho nhiều lớp mà không cần upload lại.
                    </p>
                </div>
                <a class="materials-back" href="{{ route('courses.show', $course->id) }}">
                    <i class="fas fa-arrow-left"></i> Quay lại khóa học
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success rounded-4 border-0 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($isManager)
                <div class="row g-3">
                    <div class="col-xl-7">
                        <div class="materials-panel">
                            <h2 class="materials-panel-title">
                                <i class="fas fa-cloud-arrow-up"></i> Thêm học liệu mới
                            </h2>
                            <form action="{{ route('courses.materials.store', $course->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="materials-form-grid">
                                    <div class="mf-col-6">
                                        <label class="materials-label">Tên học liệu</label>
                                        <input type="text" name="title" class="form-control materials-input"
                                            placeholder="VD: Slide HTML cơ bản">
                                    </div>
                                    <div class="mf-col-3">
                                        <label class="materials-label">Loại</label>
                                        <select name="type" class="form-select materials-input" required>
                                            @foreach ($typeOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-3">
                                        <label class="materials-label">Nguồn</label>
                                        <select name="source_type" class="form-select materials-input" required>
                                            <option value="file">Upload file</option>
                                            <option value="link">Link ngoài</option>
                                        </select>
                                    </div>
                                    <div class="mf-col-6">
                                        <label class="materials-label">File upload</label>
                                        <input type="file" name="file" class="form-control materials-input">
                                    </div>
                                    <div class="mf-col-6">
                                        <label class="materials-label">URL nếu là link</label>
                                        <input type="url" name="url" class="form-control materials-input"
                                            placeholder="https://...">
                                    </div>
                                    <div class="mf-col-4">
                                        <label class="materials-label">Lớp áp dụng</label>
                                        <select name="class_id" class="form-select materials-input">
                                            <option value="">Tất cả lớp của khóa</option>
                                            @foreach ($classes as $classroom)
                                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-4">
                                        <label class="materials-label">Gắn với bài học</label>
                                        <select name="lesson_id" class="form-select materials-input">
                                            <option value="">Học liệu chung của khóa</option>
                                            @foreach ($lessons as $lesson)
                                                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-4">
                                        <label class="materials-label">Mở khi tới bài</label>
                                        <select name="unlock_when_lesson_id" class="form-select materials-input">
                                            <option value="">Không khóa theo bài</option>
                                            @foreach ($lessons as $lesson)
                                                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-4">
                                        <label class="materials-label">Mở từ thời điểm</label>
                                        <input type="datetime-local" name="available_from"
                                            class="form-control materials-input">
                                    </div>
                                    <div class="mf-col-4">
                                        <label class="materials-label">Trạng thái</label>
                                        <select name="status" class="form-select materials-input">
                                            <option value="published">Published - mở</option>
                                            <option value="hidden">Hidden - ẩn</option>
                                        </select>
                                    </div>
                                    <div class="mf-col-4 d-flex align-items-end">
                                        <button class="materials-submit w-100" type="submit">
                                            <i class="fas fa-plus me-1"></i> Thêm học liệu
                                        </button>
                                    </div>
                                    <div class="mf-col-12">
                                        <label class="materials-label">Mô tả ngắn</label>
                                        <textarea name="description" rows="2" class="form-control materials-input"
                                            placeholder="Ghi chú cho giáo viên/học sinh nếu cần"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="materials-panel">
                            <h2 class="materials-panel-title">
                                <i class="fas fa-link"></i> Gắn học liệu đã có
                            </h2>
                            <form action="{{ route('courses.materials.attach', $course->id) }}" method="POST">
                                @csrf
                                <div class="materials-form-grid">
                                    <div class="mf-col-12">
                                        <label class="materials-label">Chọn học liệu</label>
                                        <select name="learning_material_id" class="form-select materials-input" required>
                                            <option value="">-- Chọn học liệu đã upload/link --</option>
                                            @foreach ($availableMaterials as $material)
                                                <option value="{{ $material->id }}">
                                                    {{ $material->title }} · {{ $material->typeLabel() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-6">
                                        <label class="materials-label">Lớp áp dụng</label>
                                        <select name="class_id" class="form-select materials-input">
                                            <option value="">Tất cả lớp</option>
                                            @foreach ($classes as $classroom)
                                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-6">
                                        <label class="materials-label">Bài học</label>
                                        <select name="lesson_id" class="form-select materials-input">
                                            <option value="">Học liệu chung</option>
                                            @foreach ($lessons as $lesson)
                                                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-6">
                                        <label class="materials-label">Mở khi tới bài</label>
                                        <select name="unlock_when_lesson_id" class="form-select materials-input">
                                            <option value="">Không khóa theo bài</option>
                                            @foreach ($lessons as $lesson)
                                                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mf-col-6">
                                        <label class="materials-label">Mở từ thời điểm</label>
                                        <input type="datetime-local" name="available_from"
                                            class="form-control materials-input">
                                    </div>
                                    <div class="mf-col-12">
                                        <button class="materials-submit w-100" type="submit">
                                            <i class="fas fa-layer-group me-1"></i> Gắn vào khóa học
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="materials-panel">
                <h2 class="materials-panel-title">
                    <i class="fas fa-box-archive"></i> Học liệu đang dùng
                </h2>

                @if ($assignments->isEmpty())
                    <div class="empty-materials">
                        <i class="fas fa-folder-open me-2"></i> Chưa có học liệu nào trong khóa học này.
                    </div>
                @else
                    <div class="material-list">
                        @foreach ($assignments as $assignment)
                            @php($material = $assignment->material)
                            @continue(!$material)
                            <div class="material-card">
                                <div class="material-main">
                                    <span class="material-icon">
                                        <i class="fas {{ $material->iconClass() }}"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="material-name">{{ $material->title }}</h3>
                                        <div class="material-meta">
                                            <span class="material-pill">{{ $material->typeLabel() }}</span>
                                            <span class="material-pill">{{ $material->humanSize() }}</span>
                                            <span class="material-pill">
                                                {{ $assignment->classroom?->name ?? 'Tất cả lớp' }}
                                            </span>
                                            <span class="material-pill">
                                                {{ $assignment->lesson?->title ? 'Bài: ' . $assignment->lesson->title : 'Học liệu chung' }}
                                            </span>
                                            @if ($assignment->lockLabel())
                                                <span class="material-pill">{{ $assignment->lockLabel() }}</span>
                                            @endif
                                        </div>
                                        @if ($material->description)
                                            <div class="text-muted small fw-semibold mt-2">{{ $material->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="material-actions">
                                    <a href="{{ $material->downloadUrl($assignment) }}"
                                        target="{{ $material->isLink() ? '_blank' : '_self' }}"
                                        @if ($material->isFile()) data-no-page-transition @endif
                                        class="btn btn-primary material-btn">
                                        <i
                                            class="fas {{ $material->isLink() ? 'fa-up-right-from-square' : 'fa-download' }} me-1"></i>
                                        {{ $material->isLink() ? 'Mở' : 'Tải' }}
                                    </a>

                                    @if ($isManager)
                                        <button class="btn btn-outline-secondary material-btn" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#edit-material-{{ $assignment->id }}">
                                            <i class="fas fa-sliders me-1"></i> Điều kiện
                                        </button>
                                        <form
                                            action="{{ route('courses.materials.assignments.destroy', [$course->id, $assignment->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Bỏ học liệu này khỏi khóa học? File gốc vẫn được giữ.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger material-btn" type="submit">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                @if ($isManager)
                                    <div class="collapse" id="edit-material-{{ $assignment->id }}"
                                        style="grid-column:1 / -1;">
                                        <form class="materials-form-grid pt-3 border-top"
                                            action="{{ route('courses.materials.assignments.update', [$course->id, $assignment->id]) }}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="mf-col-3">
                                                <label class="materials-label">Lớp</label>
                                                <select name="class_id" class="form-select materials-input">
                                                    <option value="">Tất cả lớp</option>
                                                    @foreach ($classes as $classroom)
                                                        <option value="{{ $classroom->id }}" @selected((int) $assignment->class_id === (int) $classroom->id)>
                                                            {{ $classroom->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mf-col-3">
                                                <label class="materials-label">Bài học</label>
                                                <select name="lesson_id" class="form-select materials-input">
                                                    <option value="">Học liệu chung</option>
                                                    @foreach ($lessons as $lesson)
                                                        <option value="{{ $lesson->id }}" @selected((int) $assignment->lesson_id === (int) $lesson->id)>
                                                            {{ $lesson->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mf-col-3">
                                                <label class="materials-label">Mở khi tới bài</label>
                                                <select name="unlock_when_lesson_id" class="form-select materials-input">
                                                    <option value="">Không khóa theo bài</option>
                                                    @foreach ($lessons as $lesson)
                                                        <option value="{{ $lesson->id }}" @selected((int) $assignment->unlock_when_lesson_id === (int) $lesson->id)>
                                                            {{ $lesson->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mf-col-3">
                                                <label class="materials-label">Mở từ</label>
                                                <input type="datetime-local" name="available_from"
                                                    value="{{ $assignment->available_from?->format('Y-m-d\TH:i') }}"
                                                    class="form-control materials-input">
                                            </div>
                                            <div class="mf-col-3">
                                                <label class="materials-label">Trạng thái</label>
                                                <select name="status" class="form-select materials-input">
                                                    <option value="published" @selected($assignment->status === 'published')>Published
                                                    </option>
                                                    <option value="hidden" @selected($assignment->status === 'hidden')>Hidden</option>
                                                </select>
                                            </div>
                                            <div class="mf-col-3 d-flex align-items-end">
                                                <button class="materials-submit w-100" type="submit">
                                                    <i class="fas fa-save me-1"></i> Lưu
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
