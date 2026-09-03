import { Calendar } from '@fullcalendar/core';
import viLocale from '@fullcalendar/core/locales/vi';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('student-calendar');
    const detailModalEl = document.getElementById('scheduleDetailModal');
    if (!calendarEl || !detailModalEl || typeof bootstrap === 'undefined') return;

    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    const detailModal = new bootstrap.Modal(detailModalEl);
    const errorAlert = document.getElementById('studentScheduleError');
    const showError = () => errorAlert?.classList.remove('d-none');

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin],
        locales: [viLocale],
        locale: 'vi',
        initialView: isMobile ? 'dayGridMonth' : 'timeGridWeek',
        headerToolbar: {
            left: isMobile ? 'prev,next' : 'prev,next today',
            center: 'title',
            right: isMobile ? 'today' : 'timeGridWeek,dayGridMonth',
        },
        buttonText: { today: 'Hôm nay', week: 'Tuần', month: 'Tháng' },
        allDaySlot: false,
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        events: {
            url: calendarEl.dataset.eventsUrl,
            failure: showError,
        },
        eventClick(info) {
            const event = info.event;
            const props = event.extendedProps || {};
            const start = event.start?.toLocaleString('vi-VN', {
                hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric',
            }) || '';
            const end = event.end?.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) || '';

            document.getElementById('scheduleDetailTitle').textContent = event.title;
            document.getElementById('scheduleDetailCourse').textContent = props.course || '—';
            document.getElementById('scheduleDetailClass').textContent = props.class || '—';
            document.getElementById('scheduleDetailTime').textContent = end ? `${start} - ${end}` : start;
            document.getElementById('scheduleDetailRoom').textContent = props.room || 'Chưa cập nhật';

            const note = props.note || '';
            document.getElementById('scheduleDetailNote').textContent = note;
            document.getElementById('scheduleDetailNoteWrap').classList.toggle('d-none', !note);
            detailModal.show();
        },
    });

    calendar.render();
});
