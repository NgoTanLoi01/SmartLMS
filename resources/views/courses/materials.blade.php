@extends('layouts.app')

@section('title', 'Học liệu - ' . $course->title)

@section('content')
    <style>
        .materials-page {
            min-height: calc(100vh - 70px);
            padding: 0 0 48px;
        }

        .materials-shell {
            max-width: 1480px !important;
            padding-inline: 0;
        }

        .materials-hero,
        .materials-panel,
        .material-card {
            background: #fff;
            border: 1px solid #D6DED6;
            border-radius: 20px;
            box-shadow: 0 12px 34px rgba(56, 86, 82, .07);
        }

        .materials-hero {
            align-items: center;
            background:
                radial-gradient(circle at 84% 0%, rgba(251, 206, 90, .24), transparent 17%),
                radial-gradient(circle at 96% 88%, rgba(152, 171, 155, .28), transparent 28%),
                linear-gradient(125deg, #FFFFFF 8%, #F4F7F2 52%, #DDEEE9 100%);
            display: flex;
            gap: 30px;
            justify-content: space-between;
            margin-bottom: 20px;
            min-height: 196px;
            overflow: hidden;
            padding: clamp(24px, 3vw, 38px);
            position: relative;
        }

        .materials-hero::after {
            border: 22px solid rgba(255, 255, 255, .38);
            border-radius: 50%;
            content: '';
            height: 190px;
            pointer-events: none;
            position: absolute;
            right: -54px;
            top: -72px;
            width: 190px;
        }

        .materials-hero__main {
            align-items: center;
            display: flex;
            gap: 20px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .materials-hero__icon {
            align-items: center;
            background: #54726E;
            border: 5px solid rgba(255, 255, 255, .76);
            border-radius: 22px;
            box-shadow: 0 14px 28px rgba(56, 86, 82, .2);
            color: #fff;
            display: inline-flex;
            flex: 0 0 72px;
            font-size: 28px;
            height: 72px;
            justify-content: center;
            transform: rotate(-2deg);
            width: 72px;
        }

        .materials-hero__copy {
            min-width: 0;
        }

        .materials-kicker {
            align-items: center;
            color: #54726E;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 8px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .materials-title {
            color: #263A37;
            font-size: clamp(24px, 2.5vw, 36px);
            font-weight: 900;
            letter-spacing: -.035em;
            line-height: 1.18;
            margin: 7px 0 8px;
        }

        .materials-subtitle {
            color: #61736F;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
            max-width: 720px;
        }

        .materials-hero__aside {
            align-items: flex-end;
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        .materials-summary {
            align-items: center;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(84, 114, 110, .14);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(56, 86, 82, .08);
            display: flex;
            gap: 10px;
            min-width: 280px;
            padding: 12px;
        }

        .materials-summary__item {
            align-items: center;
            display: flex;
            flex: 1 1 0;
            gap: 9px;
            min-width: 0;
        }

        .materials-summary__item + .materials-summary__item {
            border-left: 1px solid #D9DDD3;
            padding-left: 12px;
        }

        .materials-summary__item > i {
            align-items: center;
            background: #E4F1ED;
            border-radius: 10px;
            color: #54726E;
            display: inline-flex;
            flex: 0 0 34px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .materials-summary__item strong,
        .materials-summary__item span {
            display: block;
        }

        .materials-summary__item strong {
            color: #263A37;
            font-size: 14px;
            line-height: 1.2;
        }

        .materials-summary__item span {
            color: #71807D;
            font-size: 10px;
            font-weight: 700;
            margin-top: 2px;
        }

        .materials-back {
            align-items: center;
            background: rgba(255, 255, 255, .66);
            border: 1px solid rgba(84, 114, 110, .18);
            border-radius: 999px;
            color: #54726E;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 8px;
            min-height: 40px;
            padding: 9px 15px;
            text-decoration: none;
            transition: background .2s ease, color .2s ease, transform .2s ease;
            white-space: nowrap;
        }

        .materials-back:hover {
            background: #54726E;
            color: #fff;
            transform: translateY(-1px);
        }

        .materials-panel {
            margin-bottom: 20px;
            padding: clamp(18px, 2vw, 24px);
        }

        .materials-panel--library {
            min-height: 280px;
        }

        .materials-panel-head {
            align-items: center;
            border-bottom: 1px solid #E4E9E2;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 15px;
        }

        .materials-panel-title {
            align-items: center;
            color: #263A37;
            display: flex;
            font-size: 18px;
            font-weight: 950;
            gap: 10px;
            margin: 0;
        }

        .materials-panel-title i {
            color: #54726E;
        }

        .materials-panel-count {
            background: #EEF5F2;
            border: 1px solid #D7E5DF;
            border-radius: 999px;
            color: #54726E;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 10px;
            white-space: nowrap;
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
            color: #61736F;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .materials-input {
            background-color: #FEFEFC;
            border: 1px solid #D9DDD3;
            border-radius: 12px;
            font-size: 14px;
            min-height: 44px;
        }

        .materials-input:focus {
            border-color: #54726E;
            box-shadow: 0 0 0 .2rem rgba(84, 114, 110, .13);
        }

        .materials-submit {
            background: #54726E;
            border: 0;
            border-radius: 12px;
            color: #fff;
            font-weight: 950;
            min-height: 44px;
            padding: 0 18px;
        }

        .materials-submit:hover {
            background: #385652;
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
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .material-card:hover {
            border-color: #B7C9C1;
            box-shadow: 0 14px 32px rgba(56, 86, 82, .1);
            transform: translateY(-2px);
        }

        .material-main {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .material-icon {
            align-items: center;
            background: #EEF5F2;
            border-radius: 16px;
            color: #54726E;
            display: inline-flex;
            flex: 0 0 48px;
            font-size: 20px;
            height: 48px;
            justify-content: center;
            width: 48px;
        }

        .material-name {
            color: #263A37;
            font-size: 17px;
            font-weight: 950;
            line-height: 1.35;
            margin: 0;
        }

        .material-meta {
            color: #61736F;
            display: flex;
            flex-wrap: wrap;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            margin-top: 7px;
        }

        .material-pill {
            background: #F7F7F2;
            border: 1px solid #D9DDD3;
            border-radius: 999px;
            color: #61736F;
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
            align-items: center;
            background:
                radial-gradient(circle at 50% 0%, rgba(176, 218, 210, .28), transparent 34%),
                #FBFCF9;
            border: 1px dashed #BCC8BF;
            border-radius: 20px;
            color: #61736F;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 220px;
            padding: 34px 24px;
            text-align: center;
        }

        .empty-materials__icon {
            align-items: center;
            background: #E4F1ED;
            border: 6px solid rgba(255, 255, 255, .88);
            border-radius: 20px;
            box-shadow: 0 12px 28px rgba(56, 86, 82, .13);
            color: #54726E;
            display: inline-flex;
            font-size: 25px;
            height: 68px;
            justify-content: center;
            margin-bottom: 18px;
            width: 68px;
        }

        .empty-materials h3 {
            color: #263A37;
            font-size: 17px;
            font-weight: 850;
            margin: 0 0 7px;
        }

        .empty-materials p {
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            max-width: 500px;
        }

        .empty-materials__formats {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            justify-content: center;
            margin-top: 16px;
        }

        .empty-materials__formats span {
            background: #fff;
            border: 1px solid #D9DDD3;
            border-radius: 999px;
            color: #61736F;
            font-size: 10px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .empty-materials__action {
            align-items: center;
            background: #54726E;
            border-radius: 11px;
            color: #fff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 7px;
            margin-top: 18px;
            min-height: 40px;
            padding: 9px 14px;
            text-decoration: none;
        }

        .empty-materials__action:hover {
            background: #385652;
            color: #fff;
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

            .materials-hero__aside {
                align-items: flex-start;
                flex-direction: row-reverse;
                justify-content: space-between;
                width: 100%;
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

        @media (max-width: 575.98px) {
            .materials-shell {
                padding-inline: 0;
            }

            .materials-hero {
                border-radius: 18px;
                gap: 22px;
                min-height: 0;
                padding: 20px;
            }

            .materials-hero__main {
                align-items: flex-start;
                gap: 13px;
            }

            .materials-hero__icon {
                border-radius: 15px;
                border-width: 3px;
                flex-basis: 48px;
                font-size: 19px;
                height: 48px;
                width: 48px;
            }

            .materials-kicker {
                font-size: 10px;
            }

            .materials-title {
                font-size: 20px;
            }

            .materials-hero__aside {
                align-items: stretch;
                flex-direction: column-reverse;
            }

            .materials-summary {
                min-width: 0;
                width: 100%;
            }

            .materials-back {
                align-self: flex-start;
            }

            .materials-panel-head {
                align-items: flex-start;
            }

            .material-card {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="materials-page">
        <div class="container materials-shell">
            <div class="materials-hero">
                <div class="materials-hero__main">
                    <span class="materials-hero__icon" aria-hidden="true">
                        <i class="fa-solid fa-folder-tree"></i>
                    </span>
                    <div class="materials-hero__copy">
                        <div class="materials-kicker">
                            <i class="fa-solid fa-book-open"></i> Kho học liệu khóa học
                        </div>
                        <h1 class="materials-title">{{ $course->title }}</h1>
                        <p class="materials-subtitle">
                            PDF, slide, video, website tham khảo và tài liệu học tập được sắp xếp tập trung theo từng bài học.
                        </p>
                    </div>
                </div>
                <div class="materials-hero__aside">
                    <div class="materials-summary" aria-label="Tổng quan kho học liệu">
                        <div class="materials-summary__item">
                            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            <div>
                                <strong>{{ $assignments->count() }}</strong>
                                <span>Học liệu đang dùng</span>
                            </div>
                        </div>
                        <div class="materials-summary__item">
                            <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                            <div>
                                <strong>{{ $lessons->count() }}</strong>
                                <span>Bài học trong khóa</span>
                            </div>
                        </div>
                    </div>
                    <a class="materials-back" href="{{ route('courses.show', $course->id) }}">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại khóa học
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($isManager)
                <div class="row g-3">
                    <div class="col-xl-7">
                        <div class="materials-panel" id="add-course-material">
                            <div class="materials-panel-head">
                                <h2 class="materials-panel-title">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Thêm học liệu mới
                                </h2>
                                <span class="materials-panel-count">Tệp mới hoặc liên kết</span>
                            </div>
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
                                            <option value="file">Tải tệp lên</option>
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
                                            <option value="published">Đã xuất bản - đang mở</option>
                                            <option value="hidden">Tạm ẩn</option>
                                        </select>
                                    </div>
                                    <div class="mf-col-4 d-flex align-items-end">
                                        <button class="materials-submit w-100" type="submit">
                                            <i class="fa-solid fa-plus me-1"></i> Thêm học liệu
                                        </button>
                                    </div>
                                    <div class="mf-col-12">
                                        <label class="materials-label">Mô tả ngắn</label>
                                        <textarea name="description" rows="2" class="form-control materials-input"
                                            placeholder="Ghi chú cho giáo viên/học viên nếu cần"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="materials-panel">
                            <div class="materials-panel-head">
                                <h2 class="materials-panel-title">
                                    <i class="fa-solid fa-link"></i> Gắn học liệu đã có
                                </h2>
                                <span class="materials-panel-count">Tái sử dụng</span>
                            </div>
                            <form action="{{ route('courses.materials.attach', $course->id) }}" method="POST">
                                @csrf
                                <div class="materials-form-grid">
                                    <div class="mf-col-12">
                                        <label class="materials-label">Chọn học liệu</label>
                                        <input type="search" id="materialLibrarySearch" class="form-control materials-input mb-2"
                                            placeholder="Tìm theo tên học liệu hoặc tên file..." autocomplete="off">
                                        <select name="learning_material_id" id="existingMaterialSelect" class="form-select materials-input" required>
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
                                            <i class="fa-solid fa-layer-group me-1"></i> Gắn vào khóa học
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="materials-panel materials-panel--library">
                <div class="materials-panel-head">
                    <h2 class="materials-panel-title">
                        <i class="fa-solid fa-box-archive"></i> Học liệu đang dùng
                    </h2>
                    <span class="materials-panel-count">{{ $assignments->count() }} mục</span>
                </div>

                @if ($assignments->isEmpty())
                    <div class="empty-materials">
                        <span class="empty-materials__icon" aria-hidden="true">
                            <i class="fa-solid fa-folder-open"></i>
                        </span>
                        <h3>Kho học liệu đang trống</h3>
                        <p>
                            @if ($isManager)
                                Thêm tài liệu mới hoặc gắn lại học liệu có sẵn để học viên có thể xem trực tiếp trong khóa học.
                            @else
                                Hiện chưa có học liệu nào được phát hành cho bạn trong khóa học này.
                            @endif
                        </p>
                        <div class="empty-materials__formats" aria-label="Các định dạng học liệu được hỗ trợ">
                            <span>PDF</span>
                            <span>Slide</span>
                            <span>Video</span>
                            <span>Hình ảnh</span>
                            <span>Website</span>
                            <span>Mã nguồn</span>
                        </div>
                        @if ($isManager)
                            <a class="empty-materials__action" href="#add-course-material">
                                <i class="fa-solid fa-plus"></i> Thêm học liệu đầu tiên
                            </a>
                        @endif
                    </div>
                @else
                    <div class="material-list">
                        @foreach ($assignments as $assignment)
                            @php($material = $assignment->material)
                            @continue(!$material)
                            @php($previewType = $material->previewType())
                            <div class="material-card">
                                <div class="material-main">
                                    <span class="material-icon">
                                        <i class="fa-solid {{ $material->iconClass() }}"></i>
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
                                    @if ($material->isLink())
                                        <a href="{{ $material->url }}" target="_blank" rel="noopener"
                                            class="btn btn-primary material-btn">
                                            <i class="fa-solid fa-up-right-from-square me-1"></i>Mở
                                        </a>
                                    @elseif ($previewType)
                                        <button class="btn btn-primary material-btn" type="button"
                                            data-bs-toggle="modal" data-bs-target="#materialPreviewModal"
                                            data-material-preview
                                            data-preview-url="{{ route('materials.preview', $assignment) }}"
                                            data-preview-type="{{ $previewType }}"
                                            data-preview-title="{{ $material->title }}">
                                            <i class="fa-solid fa-eye me-1"></i>Xem
                                        </button>
                                    @else
                                        <a href="{{ route('materials.download', $assignment) }}"
                                            data-no-page-transition class="btn btn-primary material-btn">
                                            <i class="fa-solid fa-download me-1"></i>Tải
                                        </a>
                                    @endif

                                    @if ($isManager)
                                        <button class="btn btn-outline-secondary material-btn" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#edit-material-{{ $assignment->id }}">
                                            <i class="fa-solid fa-sliders me-1"></i> Điều kiện
                                        </button>
                                        <form
                                            action="{{ route('courses.materials.assignments.destroy', [$course->id, $assignment->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Bỏ học liệu này khỏi khóa học? File gốc vẫn được giữ.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger material-btn" type="submit">
                                                <i class="fa-solid fa-trash"></i>
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
                                                    <option value="published" @selected($assignment->status === 'published')>Đã xuất bản
                                                    </option>
                                                    <option value="hidden" @selected($assignment->status === 'hidden')>Tạm ẩn</option>
                                                </select>
                                            </div>
                                            <div class="mf-col-3 d-flex align-items-end">
                                                <button class="materials-submit w-100" type="submit">
                                                    <i class="fa-solid fa-save me-1"></i> Lưu
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

    @include('courses.partials.material-preview-modal')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('materialLibrarySearch');
            const materialSelect = document.getElementById('existingMaterialSelect');
            if (!searchInput || !materialSelect) return;

            let searchTimer;
            let requestController;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(async function () {
                    requestController?.abort();
                    requestController = new AbortController();
                    materialSelect.disabled = true;
                    materialSelect.innerHTML = '<option value="">Đang tìm học liệu...</option>';

                    try {
                        const url = new URL(@json(route('materials.library.search')), window.location.origin);
                        if (searchInput.value.trim()) url.searchParams.set('q', searchInput.value.trim());
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            signal: requestController.signal
                        });
                        if (!response.ok) throw new Error('Không thể tải danh sách học liệu.');
                        const payload = await response.json();
                        materialSelect.innerHTML = '<option value="">-- Chọn học liệu --</option>';
                        payload.data.forEach(function (material) {
                            const option = document.createElement('option');
                            option.value = material.id;
                            option.textContent = `${material.title} · ${material.type} · ${material.size}`;
                            materialSelect.appendChild(option);
                        });
                        if (!payload.data.length) {
                            materialSelect.innerHTML = '<option value="">Không tìm thấy học liệu phù hợp</option>';
                        }
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            materialSelect.innerHTML = '<option value="">Không thể tải học liệu, vui lòng thử lại</option>';
                        }
                    } finally {
                        materialSelect.disabled = false;
                    }
                }, 300);
            });
        });
    </script>
@endpush
