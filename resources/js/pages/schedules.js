import { Calendar } from '@fullcalendar/core';
import viLocale from '@fullcalendar/core/locales/vi';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('sch-calendar');
    const modalEl = document.getElementById('scheduleModal');
    if (!calendarEl || !modalEl || typeof bootstrap === 'undefined') return;

    const scheduleModal = new bootstrap.Modal(modalEl);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    const modalError = document.getElementById('scheduleModalError');
    const pageFeedback = document.getElementById('scheduleFeedback');
    const saveButton = document.getElementById('btnSave');
    const deleteButton = document.getElementById('btnDelete');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('scheduleModalSubtitle');
    const modalIcon = document.querySelector('#scheduleModalIcon i');
    let modalCoursesRequest;
    let importCoursesRequest;

    const routeFromTemplate = (template, value) => template.replace('__ID__', encodeURIComponent(value));

    const errorMessage = (payload, fallback) => {
        const validationMessages = Object.values(payload?.errors || {}).flat().filter(Boolean);
        return validationMessages[0] || payload?.message || fallback;
    };

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...options.headers,
            },
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(errorMessage(payload, 'Không thể xử lý yêu cầu lịch học.'));
        }

        return payload;
    };

    const showModalError = (message = '') => {
        if (!modalError) return;
        modalError.textContent = message;
        modalError.classList.toggle('d-none', !message);
    };

    const showPageFeedback = (message, type = 'danger') => {
        if (!pageFeedback) return;
        pageFeedback.textContent = message;
        pageFeedback.className = `sch-alert sch-alert--${type}`;
        pageFeedback.classList.remove('d-none');
    };

    const setBusy = (button, busy, busyLabel) => {
        if (!button) return;
        if (busy) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>${busyLabel}`;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalHtml || button.innerHTML;
        }
    };

    const dateInputValue = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const timeInputValue = (date) => `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

    const setModalMode = (editing) => {
        modalTitle.textContent = editing ? 'Cập nhật lịch học' : 'Thêm lịch học mới';
        modalSubtitle.textContent = editing
            ? 'Điều chỉnh thông tin và lưu lại thay đổi của buổi học.'
            : 'Khai báo lớp, khóa học và khung giờ cho buổi học.';
        modalIcon.className = editing ? 'fa-solid fa-calendar-pen' : 'fa-solid fa-calendar-plus';
        deleteButton?.classList.toggle('d-none', !editing);
    };

    const calendar = new Calendar(calendarEl, {
        plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin],
        locales: [viLocale],
        locale: 'vi',
        initialView: isMobile ? 'dayGridMonth' : 'timeGridWeek',
        headerToolbar: {
            left: isMobile ? 'prev,next' : 'prev,next today',
            center: 'title',
            right: isMobile ? 'today' : 'timeGridWeek,dayGridMonth',
        },
        buttonText: { today: 'Hôm nay', week: 'Tuần', month: 'Tháng' },
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        events: {
            url: calendarEl.dataset.eventsUrl,
            failure: () => showPageFeedback('Không tải được dữ liệu lịch. Vui lòng thử lại.'),
        },
        selectable: true,
        eventColor: '#2563eb',
        select(info) {
            resetForm();
            setModalMode(false);
            document.getElementById('schedule_date').value = dateInputValue(info.start);
            document.getElementById('start_time').value = info.allDay ? '07:30' : timeInputValue(info.start);
            document.getElementById('end_time').value = info.allDay ? '09:00' : timeInputValue(info.end);
            scheduleModal.show();
        },
        eventClick(info) {
            const event = info.event;
            document.getElementById('schedule_id').value = event.id;
            setModalMode(true);
            document.getElementById('class_id').value = event.extendedProps.class_id;
            document.getElementById('schedule_date').value = dateInputValue(event.start);
            document.getElementById('start_time').value = timeInputValue(event.start);
            document.getElementById('end_time').value = event.end ? timeInputValue(event.end) : '';
            document.getElementById('room').value = event.extendedProps.room || '';
            document.getElementById('note_exam').checked = event.extendedProps.note === 'Thi kết thúc môn';
            fetchCourses(event.extendedProps.class_id, event.extendedProps.course_id);
            scheduleModal.show();
        },
    });

    calendar.render();

    document.getElementById('class_id')?.addEventListener('change', function () {
        fetchCourses(this.value);
    });

    const importClassSelect = document.getElementById('import_class_id');
    importClassSelect?.addEventListener('change', function () {
        fetchImportCourses(this.value);
    });
    if (importClassSelect?.value) {
        fetchImportCourses(importClassSelect.value, calendarEl.dataset.oldImportCourseId || null);
    }

    async function loadCourses(classId, select, selectedCourseId, requestType) {
        if (!classId) {
            select.innerHTML = `<option value="">${requestType === 'import' ? 'Tự khớp theo tên môn...' : 'Vui lòng chọn lớp trước...'}</option>`;
            select.disabled = true;
            return;
        }

        if (requestType === 'import') importCoursesRequest?.abort();
        else modalCoursesRequest?.abort();
        const controller = new AbortController();
        if (requestType === 'import') importCoursesRequest = controller;
        else modalCoursesRequest = controller;

        select.innerHTML = '<option value="">Đang tải...</option>';
        select.disabled = true;

        try {
            const coursesUrl = routeFromTemplate(calendarEl.dataset.coursesUrlTemplate, classId);
            const courses = await requestJson(coursesUrl, { signal: controller.signal });
            select.innerHTML = `<option value="">${requestType === 'import' ? 'Tự khớp theo tên môn...' : '-- Chọn khóa học --'}</option>`;
            courses.forEach((course) => {
                const option = document.createElement('option');
                option.value = course.id;
                option.textContent = course.title;
                option.selected = String(selectedCourseId) === String(course.id);
                select.appendChild(option);
            });
            select.disabled = false;
        } catch (error) {
            if (error.name === 'AbortError') return;
            select.innerHTML = '<option value="">Không tải được khóa học</option>';
            showModalError(error.message);
        }
    }

    function fetchCourses(classId, selectedCourseId = null) {
        return loadCourses(classId, document.getElementById('course_id'), selectedCourseId, 'modal');
    }

    function fetchImportCourses(classId, selectedCourseId = null) {
        return loadCourses(classId, document.getElementById('default_course_id'), selectedCourseId, 'import');
    }

    saveButton?.addEventListener('click', async () => {
        showModalError();
        const id = document.getElementById('schedule_id').value;
        const data = {
            class_id: document.getElementById('class_id').value,
            course_id: document.getElementById('course_id').value,
            schedule_date: document.getElementById('schedule_date').value,
            start_time: document.getElementById('start_time').value,
            end_time: document.getElementById('end_time').value,
            room: document.getElementById('room').value,
            note: document.getElementById('note_exam').checked ? 'Thi kết thúc môn' : '',
        };

        if (!data.class_id || !data.course_id || !data.schedule_date || !data.start_time || !data.end_time) {
            showModalError('Vui lòng điền đầy đủ thông tin bắt buộc.');
            return;
        }

        const url = id ? routeFromTemplate(calendarEl.dataset.updateUrlTemplate, id) : calendarEl.dataset.storeUrl;
        setBusy(saveButton, true, 'Đang lưu...');
        try {
            const result = await requestJson(url, {
                method: id ? 'PUT' : 'POST',
                body: JSON.stringify(data),
            });
            scheduleModal.hide();
            calendar.refetchEvents();
            showPageFeedback(result.message || 'Đã lưu lịch học.', 'success');
        } catch (error) {
            showModalError(error.message);
        } finally {
            setBusy(saveButton, false);
        }
    });

    deleteButton?.addEventListener('click', async () => {
        if (!confirm('Lưu trữ lịch học này? Lịch sẽ không còn hiển thị nhưng dữ liệu vẫn được giữ lại.')) return;
        const id = document.getElementById('schedule_id').value;
        if (!id) return;

        showModalError();
        setBusy(deleteButton, true, 'Đang lưu trữ...');
        try {
            const result = await requestJson(routeFromTemplate(calendarEl.dataset.deleteUrlTemplate, id), {
                method: 'DELETE',
            });
            scheduleModal.hide();
            calendar.refetchEvents();
            showPageFeedback(result.message || 'Đã lưu trữ lịch học.', 'success');
        } catch (error) {
            showModalError(error.message);
        } finally {
            setBusy(deleteButton, false);
        }
    });

    function resetForm() {
        ['schedule_id', 'schedule_date', 'start_time', 'end_time', 'room'].forEach((id) => {
            document.getElementById(id).value = '';
        });
        document.getElementById('note_exam').checked = false;
        document.getElementById('class_id').value = '';
        const courseSelect = document.getElementById('course_id');
        courseSelect.innerHTML = '<option value="">Vui lòng chọn lớp trước...</option>';
        courseSelect.disabled = true;
        showModalError();
    }
});
