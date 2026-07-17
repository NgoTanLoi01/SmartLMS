@extends('layouts.app')

@section('content')
    @push('styles')
        @vite('resources/css/pages/attendance-show.css')
    @endpush

    <div class="att-page">

        {{-- ── TOOLBAR ── --}}
        <div class="att-toolbar">
            <div class="att-title-block">
                <h5><i class="fas fa-clipboard-check"></i> Điểm danh & Điểm số</h5>
                <small>{{ $course->title }}{{ $isStudentView ? ' · Dữ liệu của bạn' : '' }}</small>
            </div>

            <div class="att-actions">
                @unless ($isStudentView)
                <div class="att-primary-actions">
                    <div class="att-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="filterName" placeholder="Tìm tên học sinh...">
                    </div>
                    <a href="{{ route('attendance.export', $course->id) }}" class="chip-btn chip-green"
                        data-no-page-transition data-file-download>
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                    <button type="button" class="chip-btn chip-green" id="markAllPresentBtn">
                        <i class="fas fa-user-check"></i> Buổi mới nhất: tất cả có mặt
                    </button>
                </div>

                {{-- Add column --}}
                <form action="{{ route('attendance.addColumn', $course->id) }}" method="POST" class="add-col-form">
                    @csrf
                    <input type="text" name="name" placeholder="Tên cột (có thể để trống)">
                    <select name="type" id="newColumnType">
                        <option value="attendance">Điểm danh</option>
                        <option value="grade">Điểm số</option>
                        <option value="note">Ghi chú</option>
                    </select>
                    <input type="date" name="attendance_date" value="{{ now()->format('Y-m-d') }}"
                        class="attendance-only-field" aria-label="Ngày điểm danh">
                    <select name="schedule_id" class="attendance-only-field" aria-label="Liên kết lịch học">
                        <option value="">Không liên kết lịch</option>
                        @foreach ($schedules as $schedule)
                            <option value="{{ $schedule->id }}" data-date="{{ $schedule->schedule_date }}">
                                {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }} ·
                                {{ substr($schedule->start_time, 0, 5) }} · {{ $schedule->class_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="chip-btn chip-blue">
                        <i class="fas fa-plus"></i> Thêm cột
                    </button>
                </form>

                @endunless
            </div>
        </div>

        {{-- ── TABLE ── --}}
        <form action="{{ route('attendance.save', $course->id) }}" method="POST" id="att-form">
            @csrf
            <div class="att-table-wrap">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th class="col-stt">STT</th>
                            <th class="col-name">Họ và Tên</th>
                            @foreach ($columns as $col)
                                @php
                                    $typeClass = match ($col->type) {
                                        'attendance' => 'col-attendance-h',
                                        'grade' => 'col-grade-h',
                                        default => 'col-note-h',
                                    };
                                @endphp
                                <th class="{{ $typeClass }}">
                                    <div class="col-header-inner">
                                        <span class="editable-name" @unless ($isStudentView) contenteditable="true"
                                            data-col-id="{{ $col->id }}" onblur="updateColumnName(this)" @endunless>{{ $col->name }}</span>
                                        @if ($col->type === 'attendance' && $col->schedule)
                                            <small class="attendance-column-meta">
                                                {{ substr($col->schedule->start_time, 0, 5) }}
                                            </small>
                                        @endif
                                        @unless ($isStudentView)
                                        <i class="fas fa-times btn-delete-col"
                                            onclick="deleteColumn({{ $col->id }}, '{{ addslashes($col->name) }}')"></i>
                                        @endunless
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="studentList">
                        @forelse ($students as $index => $student)
                            <tr class="student-row">
                                <td class="col-stt">
                                    <div class="stt-cell">{{ $index + 1 }}</div>
                                </td>
                                <td class="col-name">
                                    <div class="name-cell">{{ $student->name }}</div>
                                </td>
                                @foreach ($columns as $col)
                                    @php
                                        $cellClass = match ($col->type) {
                                            'attendance' => 'col-attendance-cell',
                                            'grade' => 'col-grade-cell',
                                            default => 'col-note-cell',
                                        };
                                        $ph = match ($col->type) {
                                            'attendance' => '—',
                                            'grade' => '0',
                                            default => '',
                                        };
                                    @endphp
                                    <td class="{{ $cellClass }}" style="padding:0;">
                                        @if ($col->type === 'attendance')
                                            @php
                                                $status = $attendanceData[$student->id][$col->id] ?? 'present';
                                                $statusLabels = ['present' => 'Có mặt', 'absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Có phép'];
                                                $statusIcons = ['present' => 'check', 'absent' => 'xmark', 'late' => 'clock', 'excused' => 'file-circle-check'];
                                                $note = $attendanceNotes[$student->id][$col->id] ?? '';
                                                $noteId = "attendance-note-{$col->id}-{$student->id}";
                                            @endphp
                                            <div class="attendance-control">
                                                <input type="hidden" class="attendance-value"
                                                    data-column-id="{{ $col->id }}"
                                                    name="data[{{ $col->id }}][{{ $student->id }}]" value="{{ $status }}">
                                                <button type="button" class="attendance-status-btn status-{{ $status }}"
                                                    data-status="{{ $status }}" @disabled($isStudentView)>
                                                    <i class="fas fa-{{ $statusIcons[$status] ?? 'check' }}"></i>
                                                    <span>{{ $statusLabels[$status] ?? 'Có mặt' }}</span>
                                                </button>
                                                <input type="hidden" id="{{ $noteId }}"
                                                    name="notes[{{ $col->id }}][{{ $student->id }}]" value="{{ $note }}">
                                                <button type="button" class="attendance-note-btn {{ $note ? 'has-note' : '' }}"
                                                    data-note-input="{{ $noteId }}" title="{{ $note ?: 'Thêm ghi chú' }}" @disabled($isStudentView)>
                                                    <i class="fas fa-note-sticky"></i>
                                                </button>
                                            </div>
                                        @else
                                            <input type="text" name="data[{{ $col->id }}][{{ $student->id }}]"
                                                value="{{ $attendanceData[$student->id][$col->id] ?? '' }}"
                                                placeholder="{{ $ph }}" @if ($isStudentView) readonly aria-label="{{ $col->name }}" @endif>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 2 }}">
                                    <div class="att-empty">
                                        <i class="fas fa-user-slash d-block"></i>
                                        <p>Chưa có học sinh nào trong khóa học này</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── FOOTER ── --}}
            <div class="att-footer">
                <div class="att-hint">
                    <i class="fas fa-{{ $isStudentView ? 'circle-info' : 'lightbulb' }}"></i>
                    {{ $isStudentView ? 'Dữ liệu do giáo viên cập nhật và chỉ bạn có thể xem dòng này.' : 'Click vào tên cột để đổi tên. Hover vào cột để xóa.' }}
                </div>
                @unless ($isStudentView)
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Lưu bảng điểm
                </button>
                @endunless
            </div>
        </form>

    </div>

    {{-- Hidden delete form --}}
    @unless ($isStudentView)
    <form id="delete-column-form" method="POST" style="display:none;">
        @csrf @method('DELETE')
    </form>

    {{-- Save flash --}}
    <div class="save-flash" id="saveFlash"><i class="fas fa-check-circle"></i> Đã lưu thành công!</div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // ── Update column name on blur ──
        function updateColumnName(el) {
            const colId = el.getAttribute('data-col-id');
            const newName = el.innerText.trim();
            if (!newName) return;

            axios.post(`/attendance/column/${colId}/update`, {
                    name: newName
                })
                .then(() => {
                    el.style.color = 'var(--green-600)';
                    setTimeout(() => el.style.color = '', 900);
                })
                .catch(() => {
                    alert('Không thể cập nhật tên cột');
                    location.reload();
                });
        }

        // ── Delete column ──
        function deleteColumn(id, name) {
            if (!confirm(`Xóa cột "${name}" và toàn bộ dữ liệu bên dưới?`)) return;
            const form = document.getElementById('delete-column-form');
            form.action = `/attendance/column/${id}`;
            form.submit();
        }

        // ── Filter students ──
        document.getElementById('filterName').addEventListener('input', function() {
            const val = this.value.toLowerCase();
            document.querySelectorAll('.student-row').forEach(row => {
                const name = row.querySelector('.col-name').innerText.toLowerCase();
                row.style.display = name.includes(val) ? '' : 'none';
            });
        });

        // ── Select all on focus ──
        const attendanceStates = {
            present: { label: 'Có mặt', icon: 'fa-check' },
            absent: { label: 'Vắng', icon: 'fa-xmark' },
            late: { label: 'Đi muộn', icon: 'fa-clock' },
            excused: { label: 'Có phép', icon: 'fa-file-circle-check' },
        };
        const attendanceOrder = ['present', 'absent', 'late', 'excused'];

        function setAttendanceStatus(button, status) {
            const input = button.closest('.attendance-control').querySelector('.attendance-value');
            const state = attendanceStates[status] || attendanceStates.present;
            input.value = status;
            button.dataset.status = status;
            button.className = `attendance-status-btn status-${status}`;
            button.querySelector('i').className = `fas ${state.icon}`;
            button.querySelector('span').textContent = state.label;
        }

        document.querySelectorAll('.attendance-status-btn:not(:disabled)').forEach(button => {
            button.addEventListener('click', function() {
                const currentIndex = attendanceOrder.indexOf(this.dataset.status);
                setAttendanceStatus(this, attendanceOrder[(currentIndex + 1) % attendanceOrder.length]);
            });
        });

        document.querySelectorAll('.attendance-note-btn:not(:disabled)').forEach(button => {
            button.addEventListener('click', function() {
                const noteInput = document.getElementById(this.dataset.noteInput);
                const note = window.prompt('Ghi chú riêng cho học sinh trong buổi này:', noteInput.value);
                if (note === null) return;
                noteInput.value = note.trim();
                this.classList.toggle('has-note', noteInput.value !== '');
                this.title = noteInput.value || 'Thêm ghi chú';
            });
        });

        document.getElementById('markAllPresentBtn')?.addEventListener('click', function() {
            const inputs = [...document.querySelectorAll('.attendance-value')];
            if (!inputs.length) {
                alert('Chưa có buổi điểm danh nào.');
                return;
            }
            const latestColumnId = inputs[inputs.length - 1].dataset.columnId;
            inputs.filter(input => input.dataset.columnId === latestColumnId).forEach(input => {
                setAttendanceStatus(input.closest('.attendance-control').querySelector('.attendance-status-btn'), 'present');
            });
        });

        const columnType = document.getElementById('newColumnType');
        const attendanceFields = document.querySelectorAll('.attendance-only-field');
        const toggleAttendanceFields = () => attendanceFields.forEach(field => {
            const hidden = columnType.value !== 'attendance';
            field.hidden = hidden;
            field.disabled = hidden;
        });
        columnType?.addEventListener('change', toggleAttendanceFields);
        toggleAttendanceFields();

        const scheduleSelect = document.querySelector('select[name="schedule_id"]');
        scheduleSelect?.addEventListener('change', function() {
            const date = this.selectedOptions[0]?.dataset.date;
            if (date) document.querySelector('input[name="attendance_date"]').value = date;
        });

        document.querySelectorAll('.att-table input[type="text"]').forEach(inp => {
            inp.addEventListener('focus', () => inp.select());

            // Tab/Enter navigation
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const inputs = [...document.querySelectorAll('.att-table input[type="text"]')];
                    const idx = inputs.indexOf(this);
                    if (idx >= 0 && idx < inputs.length - 1) inputs[idx + 1].focus();
                }
            });
        });

        // ── Save flash ──
        document.getElementById('att-form').addEventListener('submit', function() {
            const flash = document.getElementById('saveFlash');
            flash.classList.add('show');
            setTimeout(() => flash.classList.remove('show'), 2200);
        });
    </script>
    @endunless
@endsection
