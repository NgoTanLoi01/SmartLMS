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
        .modal {
            z-index: 1060 !important;
        }

        .modal-content {
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, .1);
        }

        .modal-header {
            padding: 20px 24px 0;
            border: none;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .modal-title i {
            color: #2563eb;
            margin-right: 7px;
        }

        .modal-body {
            padding: 18px 24px;
        }

        .modal-footer {
            padding: 0 24px 20px;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-lbl {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .055em;
            color: #94a3b8;
            display: block;
            margin-bottom: 5px;
        }

        .modal-row {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
        }

        .modal-row .modal-grp {
            flex: 1;
            min-width: 0;
            margin-bottom: 0;
        }

        .modal-grp {
            margin-bottom: 14px;
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

            .modal-header {
                padding: 18px 18px 0;
            }

            .modal-body {
                padding: 14px 18px;
            }

            .modal-footer {
                padding: 0 18px 18px;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .modal-row {
                flex-direction: column;
                gap: 0;
            }

            .modal-row .modal-grp {
                margin-bottom: 14px;
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

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="sch-page">

        {{-- Header --}}
        <div class="sch-header">
            <h1><i class="fas fa-calendar-alt"></i>Lịch giảng dạy</h1>
            <p>Nhấp vào ô trống để thêm, nhấp vào lịch để sửa hoặc xóa</p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="sch-alert">{{ $errors->first() }}</div>
        @endif

        {{-- Copy panel --}}
        <div class="sch-panel">
            <div class="sch-panel-head">
                <div class="sch-panel-icon"><i class="fas fa-copy"></i></div>
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
                    <i class="fas fa-clone"></i> Sao chép
                </button>
            </form>
        </div>

        {{-- Import panel --}}
        <div class="sch-panel">
            <div class="sch-panel-head">
                <div class="sch-panel-icon"><i class="fas fa-file-import"></i></div>
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
                    <i class="fas fa-upload"></i> Nhập lịch
                </button>
            </form>
        </div>

        {{-- Calendar --}}
        <div class="sch-cal-card">
            <div id="sch-calendar"></div>
        </div>

    </div>{{-- /sch-page --}}

    {{-- ── Modal ── --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="modalTitle" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-calendar-plus"></i>Thêm lịch học mới
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="schedule_id">

                    <div class="modal-grp">
                        <label class="modal-lbl" for="class_id">Lớp học</label>
                        <select class="sch-ctrl w-100" id="class_id" required>
                            <option value="">-- Chọn lớp học --</option>
                            @foreach ($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="modal-grp">
                        <label class="modal-lbl" for="course_id">Khóa học</label>
                        <select class="sch-ctrl w-100" id="course_id" required disabled>
                            <option value="">Vui lòng chọn lớp trước...</option>
                        </select>
                    </div>

                    <div class="modal-row">
                        <div class="modal-grp">
                            <label class="modal-lbl" for="schedule_date">Ngày học</label>
                            <input type="date" class="sch-ctrl w-100" id="schedule_date" required>
                        </div>
                        <div class="modal-grp">
                            <label class="modal-lbl" for="room">Phòng học</label>
                            <input type="text" class="sch-ctrl w-100" id="room"
                                placeholder="VD: Phòng 302, Online">
                        </div>
                    </div>

                    <div class="modal-row" style="margin-bottom:0;">
                        <div class="modal-grp">
                            <label class="modal-lbl" for="start_time">Giờ bắt đầu</label>
                            <input type="time" class="sch-ctrl w-100" id="start_time" required>
                        </div>
                        <div class="modal-grp">
                            <label class="modal-lbl" for="end_time">Giờ kết thúc</label>
                            <input type="time" class="sch-ctrl w-100" id="end_time" required>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" value="1" id="note_exam">
                        <label class="form-check-label" for="note_exam">
                            Thi kết thúc môn
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="sch-btn sch-btn-danger d-none" id="btnDelete">
                        <i class="fas fa-archive"></i> Lưu trữ lịch
                    </button>
                    <div class="footer-r">
                        <button type="button" class="sch-btn sch-btn-ghost" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="sch-btn sch-btn-primary" id="btnSave">
                            <i class="fas fa-check"></i> Lưu lịch
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('sch-calendar');
            const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const isMobile = window.matchMedia('(max-width: 767.98px)').matches;

            /* ── Calendar ── */
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: isMobile ? 'dayGridMonth' : 'timeGridWeek',
                headerToolbar: {
                    left: isMobile ? 'prev,next' : 'prev,next today',
                    center: 'title',
                    right: isMobile ? 'today' : 'timeGridWeek,dayGridMonth',
                },
                buttonText: {
                    today: 'Hôm nay',
                    week: 'Tuần',
                    month: 'Tháng'
                },
                slotMinTime: '07:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: false,
                locale: 'vi',
                events: '/schedules',
                selectable: true,
                eventColor: '#2563eb',

                select: function(info) {
                    resetForm();
                    document.getElementById('modalTitle').innerHTML =
                        '<i class="fas fa-calendar-plus"></i>Thêm lịch học mới';
                    document.getElementById('btnDelete').classList.add('d-none');

                    const dateStr = info.startStr.split('T')[0];
                    const startT = info.startStr.split('T')[1]?.substring(0, 5) || '07:30';
                    const endT = info.endStr.split('T')[1]?.substring(0, 5) || '09:00';

                    document.getElementById('schedule_date').value = dateStr;
                    document.getElementById('start_time').value = startT;
                    document.getElementById('end_time').value = endT;

                    scheduleModal.show();
                },

                eventClick: function(info) {
                    const ev = info.event;
                    document.getElementById('schedule_id').value = ev.id;
                    document.getElementById('modalTitle').innerHTML =
                        '<i class="fas fa-calendar-edit"></i>Cập nhật lịch học';
                    document.getElementById('btnDelete').classList.remove('d-none');

                    document.getElementById('class_id').value = ev.extendedProps.class_id;
                    document.getElementById('schedule_date').value = ev.startStr.split('T')[0];
                    document.getElementById('start_time').value = ev.startStr.split('T')[1].substring(0,
                        5);
                    document.getElementById('end_time').value = ev.endStr ?
                        ev.endStr.split('T')[1].substring(0, 5) : '';
                    document.getElementById('room').value = ev.extendedProps.room || '';
                    document.getElementById('note_exam').checked = ev.extendedProps.note === 'Thi kết thúc môn';

                    fetchCourses(ev.extendedProps.class_id, ev.extendedProps.course_id);
                    scheduleModal.show();
                },
            });

            calendar.render();

            /* ── Lớp → Khóa học (modal) ── */
            document.getElementById('class_id').addEventListener('change', function() {
                fetchCourses(this.value);
            });

            /* ── Lớp → Khóa học (import) ── */
            const importClassSel = document.getElementById('import_class_id');
            if (importClassSel) {
                importClassSel.addEventListener('change', function() {
                    fetchImportCourses(this.value);
                });
                if (importClassSel.value) {
                    fetchImportCourses(importClassSel.value, '{{ old('default_course_id') }}');
                }
            }

            function fetchCourses(classId, selectedCourseId = null) {
                const sel = document.getElementById('course_id');
                if (!classId) {
                    sel.innerHTML = '<option value="">Vui lòng chọn lớp trước...</option>';
                    sel.disabled = true;
                    return;
                }
                sel.innerHTML = '<option value="">Đang tải...</option>';
                sel.disabled = true;

                fetch(`/schedules/get-courses/${classId}`)
                    .then(r => r.json())
                    .then(courses => {
                        sel.innerHTML = '<option value="">-- Chọn khóa học --</option>';
                        courses.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.title;
                            if (selectedCourseId == c.id) opt.selected = true;
                            sel.appendChild(opt);
                        });
                        sel.disabled = false;
                    });
            }

            function fetchImportCourses(classId, selectedCourseId = null) {
                const sel = document.getElementById('default_course_id');
                if (!sel) return;
                if (!classId) {
                    sel.innerHTML = '<option value="">Tự khớp theo tên môn...</option>';
                    sel.disabled = true;
                    return;
                }
                sel.innerHTML = '<option value="">Đang tải...</option>';
                sel.disabled = true;

                fetch(`/schedules/get-courses/${classId}`)
                    .then(r => r.json())
                    .then(courses => {
                        sel.innerHTML = '<option value="">Tự khớp theo tên môn...</option>';
                        courses.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.title;
                            if (selectedCourseId == c.id) opt.selected = true;
                            sel.appendChild(opt);
                        });
                        sel.disabled = false;
                    });
            }

            /* ── Lưu ── */
            document.getElementById('btnSave').addEventListener('click', function() {
                const id = document.getElementById('schedule_id').value;
                const url = id ? `/schedules/${id}` : '/schedules';
                const method = id ? 'PUT' : 'POST';

                const data = {
                    class_id: document.getElementById('class_id').value,
                    course_id: document.getElementById('course_id').value,
                    schedule_date: document.getElementById('schedule_date').value,
                    start_time: document.getElementById('start_time').value,
                    end_time: document.getElementById('end_time').value,
                    room: document.getElementById('room').value,
                    note: document.getElementById('note_exam').checked ? 'Thi kết thúc môn' : '',
                };

                if (!data.class_id || !data.course_id || !data.schedule_date ||
                    !data.start_time || !data.end_time) {
                    alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                    return;
                }

                fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(data),
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            scheduleModal.hide();
                            calendar.refetchEvents();
                        }
                    });
            });

            /* ── Xóa ── */
            document.getElementById('btnDelete').addEventListener('click', function() {
                if (!confirm('Lưu trữ lịch học này? Lịch sẽ không còn hiển thị nhưng dữ liệu vẫn được giữ lại.')) return;
                const id = document.getElementById('schedule_id').value;

                fetch(`/schedules/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            scheduleModal.hide();
                            calendar.refetchEvents();
                        }
                    });
            });

            /* ── Reset form ── */
            function resetForm() {
                ['schedule_id', 'schedule_date', 'start_time', 'end_time', 'room'].forEach(id => {
                    document.getElementById(id).value = '';
                });
                document.getElementById('note_exam').checked = false;
                document.getElementById('class_id').value = '';
                const sel = document.getElementById('course_id');
                sel.innerHTML = '<option value="">Vui lòng chọn lớp trước...</option>';
                sel.disabled = true;
            }
        });
    </script>
@endsection
