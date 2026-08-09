const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

onReady(() => {
    const page = document.querySelector('.att-page');
    if (!page || page.dataset.attendanceInitialized === 'true') return;
    page.dataset.attendanceInitialized = 'true';

    const attendanceStates = {
        present: { label: 'Có mặt', icon: 'fa-check' },
        absent: { label: 'Vắng', icon: 'fa-xmark' },
        late: { label: 'Đi muộn', icon: 'fa-clock' },
        excused: { label: 'Có phép', icon: 'fa-file-circle-check' },
    };
    const attendanceOrder = ['present', 'absent', 'late', 'excused'];

    const setAttendanceStatus = (button, status) => {
        const control = button.closest('.attendance-control');
        const input = control?.querySelector('.attendance-value');
        const icon = button.querySelector('i');
        const label = button.querySelector('span');
        if (!input || !icon || !label) return;

        const state = attendanceStates[status] || attendanceStates.present;
        input.value = status;
        button.dataset.status = status;
        button.className = `attendance-status-btn status-${status}`;
        icon.className = `fa-solid ${state.icon}`;
        label.textContent = state.label;
    };

    page.querySelectorAll('.editable-name[data-update-url]').forEach((element) => {
        element.addEventListener('blur', () => {
            const newName = element.innerText.trim();
            if (!newName) return;

            window.axios.post(element.dataset.updateUrl, { name: newName })
                .then(() => {
                    element.style.color = 'var(--green-600)';
                    window.setTimeout(() => { element.style.color = ''; }, 900);
                })
                .catch(() => {
                    window.alert('Không thể cập nhật tên cột');
                    window.location.reload();
                });
        });
    });

    const deleteForm = document.getElementById('delete-column-form');
    page.querySelectorAll('.btn-delete-col[data-delete-url]').forEach((button) => {
        button.addEventListener('click', () => {
            const name = button.dataset.columnName || '';
            if (!window.confirm(`Xóa cột "${name}" và toàn bộ dữ liệu bên dưới?`) || !deleteForm) return;
            deleteForm.action = button.dataset.deleteUrl;
            deleteForm.submit();
        });
    });

    document.getElementById('filterName')?.addEventListener('input', (event) => {
        const value = event.currentTarget.value.toLowerCase();
        page.querySelectorAll('.student-row').forEach((row) => {
            const name = row.querySelector('.col-name')?.innerText.toLowerCase() || '';
            row.style.display = name.includes(value) ? '' : 'none';
        });
    });

    page.querySelectorAll('.attendance-status-btn:not(:disabled)').forEach((button) => {
        button.addEventListener('click', () => {
            const currentIndex = attendanceOrder.indexOf(button.dataset.status);
            setAttendanceStatus(button, attendanceOrder[(currentIndex + 1) % attendanceOrder.length]);
        });
    });

    page.querySelectorAll('.attendance-note-btn:not(:disabled)').forEach((button) => {
        button.addEventListener('click', () => {
            const noteInput = document.getElementById(button.dataset.noteInput);
            if (!noteInput) return;
            const note = window.prompt('Ghi chú riêng cho học viên trong buổi này:', noteInput.value);
            if (note === null) return;
            noteInput.value = note.trim();
            button.classList.toggle('has-note', noteInput.value !== '');
            button.title = noteInput.value || 'Thêm ghi chú';
        });
    });

    document.getElementById('markAllPresentBtn')?.addEventListener('click', () => {
        const inputs = [...page.querySelectorAll('.attendance-value')];
        if (!inputs.length) {
            window.alert('Chưa có buổi điểm danh nào.');
            return;
        }

        const latestColumnId = inputs[inputs.length - 1].dataset.columnId;
        inputs.filter((input) => input.dataset.columnId === latestColumnId).forEach((input) => {
            const button = input.closest('.attendance-control')?.querySelector('.attendance-status-btn');
            if (button) setAttendanceStatus(button, 'present');
        });
    });

    const columnType = document.getElementById('newColumnType');
    const attendanceFields = page.querySelectorAll('.attendance-only-field');
    if (columnType) {
        const toggleAttendanceFields = () => attendanceFields.forEach((field) => {
            const hidden = columnType.value !== 'attendance';
            field.hidden = hidden;
            field.disabled = hidden;
        });
        columnType.addEventListener('change', toggleAttendanceFields);
        toggleAttendanceFields();
    }

    const scheduleSelect = page.querySelector('select[name="schedule_id"]');
    scheduleSelect?.addEventListener('change', () => {
        const date = scheduleSelect.selectedOptions[0]?.dataset.date;
        const attendanceDate = page.querySelector('input[name="attendance_date"]');
        if (date && attendanceDate) attendanceDate.value = date;
    });

    page.querySelectorAll('.att-table input[type="text"]').forEach((input) => {
        input.addEventListener('focus', () => input.select());
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const inputs = [...page.querySelectorAll('.att-table input[type="text"]')];
            const index = inputs.indexOf(input);
            if (index >= 0 && index < inputs.length - 1) inputs[index + 1].focus();
        });
    });

    document.getElementById('att-form')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        const dataInputs = [...form.querySelectorAll('[name^="data["]')];
        const notesByCell = new Map([...form.querySelectorAll('[name^="notes["]')].map((input) => {
            const match = input.name.match(/^notes\[([^\]]+)]\[([^\]]+)]$/);
            return [match ? `${match[1]}:${match[2]}` : '', input];
        }));

        dataInputs.forEach((input) => {
            const match = input.name.match(/^data\[([^\]]+)]\[([^\]]+)]$/);
            const note = match ? notesByCell.get(`${match[1]}:${match[2]}`) : null;
            const dirty = input.value !== input.dataset.initialValue
                || (note && note.value !== note.dataset.initialValue);
            input.disabled = !dirty;
            if (note) note.disabled = !dirty;
        });

        const flash = document.getElementById('saveFlash');
        flash?.classList.add('show');
        window.setTimeout(() => flash?.classList.remove('show'), 2200);
    });
});
