@extends('layouts.app')

@section('title', 'Lịch giảng dạy')

@section('content')
    <style>
        /* ── Reset & Base ── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        .sch-page {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        /* ── Page Header ── */
        .sch-header {
            margin-bottom: 20px;
        }

        .sch-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 3px;
        }

        .sch-header h1 i {
            color: #2563eb;
            font-size: 19px;
        }

        .sch-header p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        /* ── Panel ── */
        .sch-panel {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }

        .sch-panel-head {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-bottom: 14px;
        }

        .sch-panel-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-top: 1px;
        }

        .sch-panel-title {
            font-size: 13.5px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 2px;
        }

        .sch-panel-sub {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }

        .sch-fields {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .sch-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sch-field label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .055em;
            color: #94a3b8;
        }

        .sch-ctrl {
            height: 34px;
            padding: 0 11px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            font-size: 13px;
            font-family: 'Be Vietnam Pro', sans-serif;
            color: #0f172a;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
            appearance: auto;
        }

        .sch-ctrl:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            outline: none;
        }

        .sch-ctrl:disabled {
            background: #f8fafc;
            color: #94a3b8;
            cursor: not-allowed;
        }

        input[type="file"].sch-ctrl {
            height: auto;
            padding: 5px 11px;
            font-size: 12.5px;
        }

        /* ── Buttons ── */
        .sch-btn {
            height: 34px;
            padding: 0 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: background .15s;
        }

        .sch-btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .sch-btn-primary:hover {
            background: #1d4ed8;
        }

        .sch-btn-ghost {
            background: #f1f5f9;
            color: #334155;
        }

        .sch-btn-ghost:hover {
            background: #e2e8f0;
        }

        .sch-btn-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .sch-btn-danger:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* ── Calendar Card ── */
        .sch-cal-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            padding: 18px 20px 16px;
        }

        /* ── FullCalendar Overrides ── */
        #sch-calendar {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .fc .fc-button {
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            color: #334155 !important;
            border-radius: 9px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 5px 13px !important;
            box-shadow: none !important;
            transition: background .15s, border-color .15s !important;
        }

        .fc .fc-button:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #fff !important;
        }

        .fc .fc-button-group .fc-button {
            border-radius: 0 !important;
        }

        .fc .fc-button-group .fc-button:first-child {
            border-radius: 9px 0 0 9px !important;
        }

        .fc .fc-button-group .fc-button:last-child {
            border-radius: 0 9px 9px 0 !important;
        }

        .fc .fc-col-header-cell-cushion {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .fc .fc-timegrid-slot-label-cushion {
            font-size: 11.5px;
            color: #94a3b8;
        }

        .fc .fc-event {
            border-radius: 6px !important;
            border: none !important;
            padding: 2px 6px !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            cursor: pointer;
        }

        .fc .fc-daygrid-event {
            border-radius: 6px !important;
            font-size: 12px !important;
        }

        .fc .fc-highlight {
            background: #eff6ff !important;
        }

        .fc td,
        .fc th {
            border-color: #f1f5f9 !important;
        }

        .fc .fc-scrollgrid {
            border-color: #e8edf3 !important;
        }

        .fc .fc-today-button {
            font-weight: 600 !important;
        }

        .fc-direction-ltr .fc-toolbar>*> :not(:first-child) {
            margin-left: 6px;
        }

        /* ── Modal ── */
        #scheduleModal .modal-dialog {
            max-width: 720px;
        }

        #scheduleModal .modal-content {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .2);
        }

        #scheduleModal .modal-header {
            align-items: flex-start;
            padding: 22px 26px 18px;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(135deg, #f8fbff 0%, #fff 72%);
        }

        .sch-modal-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .sch-modal-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border-radius: 12px;
            color: #2563eb;
            background: #eaf2ff;
            font-size: 17px;
        }

        #scheduleModal .modal-title {
            margin: 0 0 3px;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.35;
        }

        .sch-modal-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        #scheduleModal .btn-close {
            width: 32px;
            height: 32px;
            margin: 2px 0 0 auto;
            padding: 0;
            border-radius: 9px;
            background-size: 11px;
            opacity: .55;
        }

        #scheduleModal .btn-close:hover {
            background-color: #eef2f7;
            opacity: .85;
        }

        #scheduleModal .modal-body {
            padding: 20px 26px 22px;
        }

        #scheduleModal .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 15px 26px;
            border-top: 1px solid #eef2f7;
            background: #f8fafc;
        }

        .modal-lbl {
            display: flex;
            align-items: center;
            gap: 3px;
            margin-bottom: 7px;
            color: #475569;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.3;
        }

        .modal-required {
            color: #ef4444;
        }

        #scheduleModal .sch-ctrl {
            height: 42px;
            border-color: #dbe3ee;
            border-radius: 10px;
            padding: 0 12px;
            font-size: 13.5px;
            background-color: #fff;
        }

        #scheduleModal .sch-ctrl:hover:not(:disabled) {
            border-color: #b8c5d6;
        }

        #scheduleModal .sch-ctrl:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .11);
        }

        .sch-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px 14px;
        }

        .modal-grp {
            min-width: 0;
        }

        .modal-grp--wide {
            grid-column: 1 / -1;
        }

        .sch-exam-option {
            display: flex;
            align-items: center;
            gap: 11px;
            grid-column: 1 / -1;
            min-height: 52px;
            margin-top: 1px;
            padding: 10px 12px;
            border: 1px solid #dbe3ee;
            border-radius: 11px;
            background: #f8fafc;
            cursor: pointer;
            transition: border-color .15s, background-color .15s, box-shadow .15s;
        }

        .sch-exam-option:hover {
            border-color: #b9cdf4;
            background: #f3f7ff;
        }

        .sch-exam-option:has(input:checked) {
            border-color: #93b4f8;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .07);
        }

        .sch-exam-option .form-check-input {
            width: 17px;
            height: 17px;
            flex: 0 0 17px;
            margin: 0;
        }

        .sch-exam-option__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border-radius: 8px;
            color: #d97706;
            background: #fff7ed;
        }

        .sch-exam-option__copy {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .sch-exam-option__copy strong {
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .sch-exam-option__copy small {
            color: #7c8ba1;
            font-size: 11.5px;
        }

        .sch-recurrence {
            grid-column: 1 / -1;
            overflow: hidden;
            border: 1px solid #dbe3ee;
            border-radius: 13px;
            background: #f8fafc;
        }

        .sch-recurrence-toggle {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            padding: 12px 14px;
            cursor: pointer;
        }

        .sch-recurrence-toggle .form-check-input {
            width: 34px;
            height: 18px;
            flex: 0 0 34px;
            margin: 0;
        }

        .sch-recurrence-toggle__copy {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .sch-recurrence-toggle__copy strong {
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .sch-recurrence-toggle__copy small {
            color: #7c8ba1;
            font-size: 11.5px;
        }

        .sch-recurrence-body {
            padding: 14px;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }

        .sch-recurrence-fields {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .sch-recurrence-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 13px;
        }

        .sch-skip-option {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #475569;
            font-size: 12px;
            cursor: pointer;
        }

        .sch-preview {
            max-height: 210px;
            overflow: auto;
            margin-top: 13px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .sch-preview-summary {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 9px 11px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            background: #f8fafc;
            font-size: 11.5px;
            font-weight: 600;
        }

        .sch-preview-item {
            display: grid;
            grid-template-columns: 34px minmax(135px, .8fr) minmax(0, 1.4fr);
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11.5px;
        }

        .sch-preview-item:last-child {
            border-bottom: 0;
        }

        .sch-preview-position {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 27px;
            height: 27px;
            border-radius: 8px;
            color: #2563eb;
            background: #eff6ff;
            font-weight: 700;
        }

        .sch-preview-item--conflict .sch-preview-position {
            color: #b45309;
            background: #fff7ed;
        }

        .sch-preview-date {
            color: #334155;
            font-weight: 600;
        }

        .sch-preview-status {
            color: #15803d;
        }

        .sch-preview-item--conflict .sch-preview-status {
            color: #b45309;
        }

        .sch-series-scope {
            grid-column: 1 / -1;
            padding: 13px 14px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
        }

        .sch-series-scope__title {
            margin: 0 0 8px;
            color: #1e3a8a;
            font-size: 12.5px;
            font-weight: 700;
        }

        .sch-series-scope__options {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .sch-series-scope__options label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            padding: 7px 11px;
            border: 1px solid #dbeafe;
            border-radius: 9px;
            color: #475569;
            background: #fff;
            font-size: 12px;
            cursor: pointer;
        }

        .footer-r {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: auto;
        }

        /* ── Alert ── */
        .sch-alert {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .sch-alert--success {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #047857;
        }

        /* ── Responsive ── */
        @media (max-width: 767.98px) {
            .sch-panel {
                padding: 14px 14px;
            }

            .sch-cal-card {
                padding: 12px;
                border-radius: 12px;
            }

            .sch-fields {
                flex-direction: column;
                align-items: stretch;
            }

            .sch-btn {
                justify-content: center;
            }

            .fc .fc-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .fc .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }

            .fc .fc-toolbar-title {
                font-size: 14px;
                text-align: center;
            }

            .fc .fc-button {
                padding: 5px 9px !important;
                font-size: 12px !important;
            }

            #scheduleModal .modal-dialog {
                max-width: none;
                margin: 10px;
            }

            #scheduleModal .modal-content {
                min-height: auto;
                border-radius: 16px;
            }

            #scheduleModal .modal-header {
                padding: 18px;
            }

            #scheduleModal .modal-body {
                padding: 18px;
            }

            #scheduleModal .modal-footer {
                padding: 14px 18px;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .sch-modal-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .modal-grp--wide,
            .sch-exam-option,
            .sch-recurrence,
            .sch-series-scope {
                grid-column: auto;
            }

            .sch-recurrence-fields {
                grid-template-columns: 1fr;
            }

            .sch-recurrence-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .sch-preview-item {
                grid-template-columns: 30px minmax(0, 1fr);
            }

            .sch-preview-status {
                grid-column: 2;
            }

            .footer-r {
                margin-left: 0;
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .sch-btn-danger,
            .sch-btn-ghost,
            .sch-btn-primary {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div class="sch-page">

        {{-- Header --}}
        <div class="sch-header">
            <h1><i class="fa-solid fa-calendar-days"></i>Lịch giảng dạy</h1>
            <p>Nhấp vào ô trống để thêm, nhấp vào lịch để sửa hoặc xóa</p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="sch-alert">{{ $errors->first() }}</div>
        @endif

        {{-- Copy panel --}}
        <div class="sch-panel">
            <div class="sch-panel-head">
                <div class="sch-panel-icon"><i class="fa-solid fa-copy"></i></div>
                <div>
                    <p class="sch-panel-title">Sao chép lịch theo ngày</p>
                    <p class="sch-panel-sub">Chọn ngày đã có lịch và dán sang một ngày khác</p>
                </div>
            </div>
            <form method="POST" action="{{ route('schedules.copyDay') }}" class="sch-fields">
                @csrf
                <div class="sch-field">
                    <label for="source_date">Ngày nguồn</label>
                    <input type="date" class="sch-ctrl" id="source_date" name="source_date"
                        value="{{ old('source_date') }}" required>
                </div>
                <div class="sch-field">
                    <label for="target_date">Ngày đích</label>
                    <input type="date" class="sch-ctrl" id="target_date" name="target_date"
                        value="{{ old('target_date') }}" required>
                </div>
                <button type="submit" class="sch-btn sch-btn-primary">
                    <i class="fa-solid fa-clone"></i> Sao chép
                </button>
            </form>
        </div>

        {{-- Import panel --}}
        <div class="sch-panel">
            <div class="sch-panel-head">
                <div class="sch-panel-icon"><i class="fa-solid fa-file-import"></i></div>
                <div>
                    <p class="sch-panel-title">Nhập lịch từ Excel</p>
                    <p class="sch-panel-sub">
                        Hỗ trợ cột: Lớp, Ngày, Giờ học, Tên môn học, Phòng học.
                        Nhiều lớp trong một ô cách nhau bằng dấu chấm phẩy.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('schedules.import') }}" enctype="multipart/form-data" class="sch-fields">
                @csrf
                <div class="sch-field">
                    <label for="import_class_id">Lớp mặc định</label>
                    <select class="sch-ctrl" id="import_class_id" name="import_class_id" style="min-width:140px;">
                        <option value="">Tự lấy từ cột Lớp...</option>
                        @foreach ($classes as $cls)
                            <option value="{{ $cls->id }}" @selected(old('import_class_id') == $cls->id)>
                                {{ $cls->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sch-field">
                    <label for="default_course_id">Khóa mặc định</label>
                    <select class="sch-ctrl" id="default_course_id" name="default_course_id" style="min-width:160px;"
                        disabled>
                        <option value="">Tự khớp theo tên môn...</option>
                    </select>
                </div>
                <div class="sch-field">
                    <label for="schedule_file">File Excel</label>
                    <input type="file" class="sch-ctrl" id="schedule_file" name="file" accept=".xlsx,.xls,.csv"
                        required>
                </div>
                <button type="submit" class="sch-btn sch-btn-primary">
                    <i class="fa-solid fa-upload"></i> Nhập lịch
                </button>
            </form>
        </div>

        {{-- Calendar --}}
        <div id="scheduleFeedback" class="sch-alert d-none" role="alert"></div>
        <div class="sch-cal-card">
            <div id="sch-calendar"
                data-events-url="{{ route('schedules.index') }}"
                data-store-url="{{ route('schedules.store') }}"
                data-series-store-url="{{ route('schedules.series.store') }}"
                data-series-preview-url="{{ route('schedules.series.preview') }}"
                data-update-url-template="{{ route('schedules.update', '__ID__') }}"
                data-delete-url-template="{{ route('schedules.destroy', '__ID__') }}"
                data-courses-url-template="{{ url('/schedules/get-courses/__ID__') }}"
                data-old-import-course-id="{{ old('default_course_id') }}"></div>
        </div>

    </div>{{-- /sch-page --}}

    {{-- ── Modal ── --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="modalTitle" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <div class="sch-modal-heading">
                        <span class="sch-modal-icon" id="scheduleModalIcon" aria-hidden="true">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </span>
                        <div>
                            <h5 class="modal-title" id="modalTitle">Thêm lịch học mới</h5>
                            <p class="sch-modal-subtitle" id="scheduleModalSubtitle">
                                Khai báo lớp, khóa học và khung giờ cho buổi học.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="schedule_id">
                    <div id="scheduleModalError" class="sch-alert d-none" role="alert"></div>

                    <div class="sch-modal-grid">
                        <div class="modal-grp modal-grp--wide">
                            <label class="modal-lbl" for="class_id">
                                Lớp học <span class="modal-required">*</span>
                            </label>
                            <select class="sch-ctrl w-100" id="class_id" required>
                                <option value="">-- Chọn lớp học --</option>
                                @foreach ($classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="modal-grp modal-grp--wide">
                            <label class="modal-lbl" for="course_id">
                                Khóa học <span class="modal-required">*</span>
                            </label>
                            <select class="sch-ctrl w-100" id="course_id" required disabled>
                                <option value="">Vui lòng chọn lớp trước...</option>
                            </select>
                        </div>

                        <div class="modal-grp">
                            <label class="modal-lbl" for="schedule_date">
                                Ngày học <span class="modal-required">*</span>
                            </label>
                            <input type="date" class="sch-ctrl w-100" id="schedule_date" required>
                        </div>
                        <div class="modal-grp">
                            <label class="modal-lbl" for="room">Phòng học</label>
                            <input type="text" class="sch-ctrl w-100" id="room"
                                placeholder="VD: Phòng 302, Online">
                        </div>

                        <div class="modal-grp">
                            <label class="modal-lbl" for="start_time">
                                Giờ bắt đầu <span class="modal-required">*</span>
                            </label>
                            <input type="time" class="sch-ctrl w-100" id="start_time" required>
                        </div>
                        <div class="modal-grp">
                            <label class="modal-lbl" for="end_time">
                                Giờ kết thúc <span class="modal-required">*</span>
                            </label>
                            <input type="time" class="sch-ctrl w-100" id="end_time" required>
                        </div>

                        <label class="sch-exam-option" for="note_exam">
                            <input class="form-check-input" type="checkbox" value="1" id="note_exam">
                            <span class="sch-exam-option__icon" aria-hidden="true">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </span>
                            <span class="sch-exam-option__copy">
                                <strong>Đây là buổi thi kết thúc môn</strong>
                                <small>Lịch sẽ được làm nổi bật để học viên dễ nhận biết.</small>
                            </span>
                        </label>

                        <div class="sch-recurrence" id="recurrenceCreateSection">
                            <label class="sch-recurrence-toggle" for="repeat_enabled">
                                <input class="form-check-input" type="checkbox" role="switch" id="repeat_enabled">
                                <span class="sch-recurrence-toggle__copy">
                                    <strong>Lặp lại lịch học</strong>
                                    <small>Tạo lịch hàng tuần hoặc cách tuần và kiểm tra trùng trước khi lưu.</small>
                                </span>
                            </label>
                            <div class="sch-recurrence-body d-none" id="recurrenceBody">
                                <div class="sch-recurrence-fields">
                                    <div class="modal-grp">
                                        <label class="modal-lbl" for="repeat_interval">Tần suất</label>
                                        <select class="sch-ctrl w-100" id="repeat_interval">
                                            <option value="1">Hàng tuần</option>
                                            <option value="2">Cách tuần</option>
                                        </select>
                                    </div>
                                    <div class="modal-grp">
                                        <label class="modal-lbl" for="end_mode">Kết thúc theo</label>
                                        <select class="sch-ctrl w-100" id="end_mode">
                                            <option value="count">Số buổi</option>
                                            <option value="date">Ngày kết thúc</option>
                                        </select>
                                    </div>
                                    <div class="modal-grp" id="occurrenceCountGroup">
                                        <label class="modal-lbl" for="occurrence_count">Tổng số buổi</label>
                                        <input type="number" class="sch-ctrl w-100" id="occurrence_count" min="2"
                                            max="104" value="8">
                                    </div>
                                    <div class="modal-grp d-none" id="repeatUntilGroup">
                                        <label class="modal-lbl" for="repeat_until">Ngày kết thúc</label>
                                        <input type="date" class="sch-ctrl w-100" id="repeat_until">
                                    </div>
                                </div>
                                <div class="sch-recurrence-actions">
                                    <label class="sch-skip-option" for="skip_conflicts">
                                        <input class="form-check-input" type="checkbox" id="skip_conflicts">
                                        Bỏ qua các buổi bị trùng khi tạo
                                    </label>
                                    <button type="button" class="sch-btn sch-btn-ghost" id="btnPreviewSeries">
                                        <i class="fa-solid fa-list-check"></i> Xem trước chuỗi
                                    </button>
                                </div>
                                <div class="sch-preview d-none" id="seriesPreview" aria-live="polite">
                                    <div class="sch-preview-summary" id="seriesPreviewSummary"></div>
                                    <div id="seriesPreviewList"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sch-series-scope d-none" id="seriesEditSection">
                            <p class="sch-series-scope__title">
                                <i class="fa-solid fa-link me-1"></i> Buổi học này thuộc một chuỗi lặp lại
                            </p>
                            <div class="sch-series-scope__options">
                                <label for="series_scope_occurrence">
                                    <input class="form-check-input" type="radio" name="series_scope"
                                        id="series_scope_occurrence" value="occurrence" checked>
                                    Chỉ buổi này
                                </label>
                                <label for="series_scope_all">
                                    <input class="form-check-input" type="radio" name="series_scope"
                                        id="series_scope_all" value="series">
                                    Cả chuỗi
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="sch-btn sch-btn-danger d-none" id="btnDelete">
                        <i class="fa-solid fa-archive"></i> Lưu trữ lịch
                    </button>
                    <div class="footer-r">
                        <button type="button" class="sch-btn sch-btn-ghost" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="sch-btn sch-btn-primary" id="btnSave">
                            <i class="fa-solid fa-check"></i> Lưu lịch
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/pages/schedules.js')
@endpush
