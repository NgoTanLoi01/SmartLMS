@extends('layouts.app')

@section('title', 'Quản lý Thời khóa biểu')
<style>
    .modal.show .modal-dialog {
        transform: none;
        padding-top: 50px !important;
    }
</style>
@section('content')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Lịch giảng dạy</h3>
                <p class="text-muted mb-0 mt-1">Nhấp vào để thêm, sửa, xóa lịch!</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div id='calendar'></div>
        </div>
    </div>

    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-light border-bottom-0 pb-3">
                    <h5 class="modal-title fw-bold text-primary" id="modalTitle">Thêm lịch học mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-2">
                    <form id="scheduleForm">
                        <input type="hidden" id="schedule_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Lớp học</label>
                            <select class="form-select form-select-lg fs-6" id="class_id" required>
                                <option value="">-- Chọn lớp học trước --</option>
                                @foreach ($classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Khóa học</label>
                            <select class="form-select form-select-lg fs-6 bg-light" id="course_id" required disabled>
                                <option value="">Vui lòng chọn Lớp học...</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase">Ngày học</label>
                                <input type="date" class="form-control" id="schedule_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase">Phòng học</label>
                                <input type="text" class="form-control" id="room"
                                    placeholder="Vd: Phòng 302, Online">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase">Giờ bắt đầu</label>
                                <input type="time" class="form-control" id="start_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase">Giờ kết thúc</label>
                                <input type="time" class="form-control" id="end_time" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger d-none fw-bold px-4" id="btnDelete"><i
                            class="fas fa-trash me-2"></i>Xóa</button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSave"><i
                                class="fas fa-save me-2"></i>Lưu lịch</button>
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

            // Khởi tạo FullCalendar
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today', // Thêm nút bấm chuyển tuần/tháng
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

                // CLICK ĐỂ THÊM MỚI
                select: function(info) {
                    document.getElementById('scheduleForm').reset();
                    document.getElementById('schedule_id').value = '';
                    document.getElementById('modalTitle').innerText = 'Thêm lịch học mới';
                    document.getElementById('btnDelete').classList.add('d-none');

                    // Xử lý Ngày và Giờ (Tự động điền theo vị trí Click)
                    let dateStr = info.startStr.split('T')[0];
                    let startT = info.startStr.split('T')[1]?.substring(0, 5) || '07:30';
                    let endT = info.endStr.split('T')[1]?.substring(0, 5) || '09:00';

                    document.getElementById('schedule_date').value = dateStr;
                    document.getElementById('start_time').value = startT;
                    document.getElementById('end_time').value = endT;

                    // Reset dropdown khóa học
                    document.getElementById('course_id').innerHTML =
                        '<option value="">Vui lòng chọn Lớp học...</option>';
                    document.getElementById('course_id').disabled = true;

                    scheduleModal.show();
                },

                // CLICK ĐỂ SỬA/XÓA LỊCH ĐÃ CÓ
                eventClick: function(info) {
                    let ev = info.event;
                    document.getElementById('schedule_id').value = ev.id;
                    document.getElementById('modalTitle').innerText = 'Cập nhật lịch học';
                    document.getElementById('btnDelete').classList.remove('d-none');

                    document.getElementById('class_id').value = ev.extendedProps.class_id;
                    document.getElementById('schedule_date').value = ev.startStr.split('T')[0];
                    document.getElementById('start_time').value = ev.startStr.split('T')[1].substring(0,
                        5);
                    document.getElementById('end_time').value = ev.endStr ? ev.endStr.split('T')[1]
                        .substring(0, 5) : '';
                    document.getElementById('room').value = ev.extendedProps.room || '';

                    // Lọc lại Khóa học bằng AJAX trước khi gán giá trị
                    fetchCourses(ev.extendedProps.class_id, ev.extendedProps.course_id);

                    scheduleModal.show();
                }
            });

            calendar.render();

            // AJAX CHỌN LỚP -> HIỂN THỊ KHÓA HỌC
            document.getElementById('class_id').addEventListener('change', function() {
                fetchCourses(this.value);
            });

            function fetchCourses(classId, selectedCourseId = null) {
                let courseSelect = document.getElementById('course_id');

                if (!classId) {
                    courseSelect.innerHTML = '<option value="">Vui lòng chọn Lớp học...</option>';
                    courseSelect.disabled = true;
                    return;
                }

                courseSelect.innerHTML = '<option value="">Đang tải...</option>';
                courseSelect.disabled = true;

                fetch(`/schedules/get-courses/${classId}`)
                    .then(res => res.json())
                    .then(courses => {
                        courseSelect.innerHTML = '<option value="">-- Chọn khóa học --</option>';
                        courses.forEach(c => {
                            let selected = (selectedCourseId == c.id) ? 'selected' : '';
                            courseSelect.innerHTML +=
                                `<option value="${c.id}" ${selected}>${c.title}</option>`;
                        });
                        courseSelect.disabled = false;
                        courseSelect.classList.remove('bg-light');
                    });
            }

            // XỬ LÝ LƯU LỊCH
            document.getElementById('btnSave').addEventListener('click', function() {
                let id = document.getElementById('schedule_id').value;
                let url = id ? `/schedules/${id}` : '/schedules';
                let method = id ? 'PUT' : 'POST';

                let data = {
                    class_id: document.getElementById('class_id').value,
                    course_id: document.getElementById('course_id').value,
                    schedule_date: document.getElementById('schedule_date').value,
                    start_time: document.getElementById('start_time').value,
                    end_time: document.getElementById('end_time').value,
                    room: document.getElementById('room').value,
                };

                if (!data.class_id || !data.course_id) {
                    alert('Vui lòng chọn đầy đủ Lớp và Khóa học!');
                    return;
                }

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                }).then(res => res.json()).then(data => {
                    if (data.status === 'success') {
                        scheduleModal.hide();
                        calendar.refetchEvents();
                    }
                });
            });

            // XỬ LÝ XÓA
            document.getElementById('btnDelete').addEventListener('click', function() {
                if (confirm('Bạn có chắc chắn muốn xóa lịch này?')) {
                    let id = document.getElementById('schedule_id').value;
                    fetch(`/schedules/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.status === 'success') {
                            scheduleModal.hide();
                            calendar.refetchEvents();
                        }
                    });
                }
            });
        });
    </script>
@endsection
