@extends('layouts.app')

@section('title', 'Lịch giảng dạy')

@section('content')
    <style>
        /* ── FullCalendar overrides ── */
        #calendar {
            font-family: 'DM Sans', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 17px;
            font-weight: 600;
            color: #0f172a;
        }

        .fc .fc-button {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #334155;
            border-radius: 8px !important;
            font-size: 13px;
            font-weight: 500;
            padding: 6px 14px;
            box-shadow: none !important;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }

        .fc .fc-button:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
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
            border-radius: 8px 0 0 8px !important;
        }

        .fc .fc-button-group .fc-button:last-child {
            border-radius: 0 8px 8px 0 !important;
        }

        .fc .fc-col-header-cell-cushion {
            font-size: 12.5px;
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
            border-radius: 7px;
            border: none;
            padding: 3px 6px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
        }

        .fc .fc-daygrid-event {
            border-radius: 6px;
            font-size: 12px;
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

        /* ── Calendar card ── */
        .calendar-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            padding: 20px 20px 16px;
        }

        /* ── Page header ── */
        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: #64748b;
            margin: 0 0 24px;
        }

        /* ── Modal ── */
        .modal {
            z-index: 1060 !important;
        }

        .modal-content {
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 24px 0;
            border: none;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 600;
            color: #0f172a;
        }

        .modal-title i {
            color: #2563eb;
            margin-right: 8px;
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

        .form-label-sm {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            display: block;
            margin-bottom: 5px;
        }

        .form-ctrl {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: #0f172a;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
            appearance: auto;
        }

        .form-ctrl:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            outline: none;
        }

        .form-ctrl:disabled {
            background: #f8fafc;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .form-row {
            display: flex;
            gap: 14px;
            margin-bottom: 14px;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 0;
        }

        .form-group {
            margin-bottom: 14px;
        }

        /* Buttons */
        .btn-save {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 22px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        .btn-save i {
            font-size: 12px;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #334155;
            border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-delete:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .btn-delete i {
            font-size: 12px;
        }

        .btn-delete.d-none {
            display: none !important;
        }

        .footer-right {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: auto;
        }
    </style>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <h1 class="page-title">
        <i class="fas fa-calendar-alt" style="color:#2563eb; font-size:18px; margin-right:10px;"></i>Lịch giảng dạy
    </h1>
    <p class="page-subtitle">Nhấp vào ô trống để thêm, nhấp vào lịch để sửa hoặc xóa</p>

    <div class="calendar-card">
        <div id="calendar"></div>
    </div>

    {{-- ── Modal ── --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-calendar-plus"></i>Thêm lịch học mới
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="schedule_id">

                    <div class="form-group">
                        <label class="form-label-sm">Lớp học</label>
                        <select class="form-ctrl" id="class_id" required>
                            <option value="">-- Chọn lớp học --</option>
                            @foreach ($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label-sm">Khóa học</label>
                        <select class="form-ctrl" id="course_id" required disabled>
                            <option value="">Vui lòng chọn lớp trước...</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label-sm">Ngày học</label>
                            <input type="date" class="form-ctrl" id="schedule_date" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label-sm">Phòng học</label>
                            <input type="text" class="form-ctrl" id="room" placeholder="VD: Phòng 302, Online">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:0;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label-sm">Giờ bắt đầu</label>
                            <input type="time" class="form-ctrl" id="start_time" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label-sm">Giờ kết thúc</label>
                            <input type="time" class="form-ctrl" id="end_time" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-delete d-none" id="btnDelete">
                        <i class="fas fa-trash"></i> Xóa lịch
                    </button>
                    <div class="footer-right">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn-save" id="btnSave">
                            <i class="fas fa-check"></i> Lưu lịch
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,dayGridMonth'
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
                    document.getElementById('end_time').value = ev.endStr ? ev.endStr.split('T')[1]
                        .substring(0, 5) : '';
                    document.getElementById('room').value = ev.extendedProps.room || '';

                    fetchCourses(ev.extendedProps.class_id, ev.extendedProps.course_id);
                    scheduleModal.show();
                }
            });

            calendar.render();

            document.getElementById('class_id').addEventListener('change', function() {
                fetchCourses(this.value);
            });

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
                };

                if (!data.class_id || !data.course_id || !data.schedule_date || !data.start_time || !data
                    .end_time) {
                    alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                    return;
                }

                fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            scheduleModal.hide();
                            calendar.refetchEvents();
                        }
                    });
            });

            document.getElementById('btnDelete').addEventListener('click', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa lịch này?')) return;
                const id = document.getElementById('schedule_id').value;
                fetch(`/schedules/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            scheduleModal.hide();
                            calendar.refetchEvents();
                        }
                    });
            });

            function resetForm() {
                document.getElementById('schedule_id').value = '';
                document.getElementById('class_id').value = '';
                document.getElementById('schedule_date').value = '';
                document.getElementById('start_time').value = '';
                document.getElementById('end_time').value = '';
                document.getElementById('room').value = '';
                const sel = document.getElementById('course_id');
                sel.innerHTML = '<option value="">Vui lòng chọn lớp trước...</option>';
                sel.disabled = true;
            }
        });
    </script>
@endsection
