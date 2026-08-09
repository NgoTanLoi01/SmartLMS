@extends('layouts.app')

@section('content')
    @php
        $gradebookMappedColumns = $gradebookMappedColumns ?? [];
        $gradebookLockedCells = $gradebookLockedCells ?? [];
    @endphp

    @push('styles')
        @vite('resources/css/pages/attendance-show.css')
    @endpush

    <div class="att-page">

        {{-- ── TOOLBAR ── --}}
        <div class="att-toolbar">
            <div class="att-title-block">
                <div class="att-title-copy">
                    <h5><i class="fa-solid fa-clipboard-check"></i> Điểm danh & Điểm số</h5>
                    <small>{{ $course->title }}{{ $isStudentView ? ' · Dữ liệu của bạn' : '' }}</small>
                </div>
                <a href="{{ route('courses.show', $course->id) }}" class="chip-btn chip-neutral att-back-btn"
                    data-testid="attendance-back-to-course">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>Quay lại khóa học</span>
                </a>
            </div>

            <div class="att-actions">
                @unless ($isStudentView)
                <div class="att-primary-actions">
                    <div class="att-search">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="filterName" placeholder="Tìm tên học viên...">
                    </div>
                    <a href="{{ route('attendance.export', $course->id) }}" class="chip-btn chip-green"
                        data-no-page-transition data-file-download>
                        <i class="fa-solid fa-file-excel"></i> Xuất Excel
                    </a>
                    <button type="button" class="chip-btn chip-green" id="markAllPresentBtn">
                        <i class="fa-solid fa-user-check"></i> Buổi mới nhất: tất cả có mặt
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
                        <i class="fa-solid fa-plus"></i> Thêm cột
                    </button>
                </form>

                @endunless
            </div>
        </div>

        @if(!$isStudentView && $gradebookMappedColumns !== [])
            <div class="alert alert-info py-2" role="status"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Các cột có nhãn “Đồng bộ Sổ điểm” sẽ tự cập nhật Gradebook sau khi lưu. Ô đã chốt hoặc thành phần đã khóa không thể sửa tại đây.</div>
        @endif

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
                                            data-update-url="{{ route('attendance.updateColumn', $col->id) }}" @endunless>{{ $col->name }}</span>
                                        @if ($col->type === 'attendance' && $col->schedule)
                                            <small class="attendance-column-meta">
                                                {{ substr($col->schedule->start_time, 0, 5) }}
                                            </small>
                                        @endif
                                        @if(isset($gradebookMappedColumns[$col->id]))<small class="badge text-bg-light border mt-1">Đồng bộ Sổ điểm</small>@endif
                                        @unless ($isStudentView)
                                        <i class="fa-solid fa-times btn-delete-col"
                                            data-delete-url="{{ route('attendance.deleteColumn', $col->id) }}"
                                            data-column-name="{{ $col->name }}"></i>
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
                                                    data-initial-value="{{ $status }}"
                                                    name="data[{{ $col->id }}][{{ $student->id }}]" value="{{ $status }}">
                                                <button type="button" class="attendance-status-btn status-{{ $status }}"
                                                    data-status="{{ $status }}" @disabled($isStudentView)>
                                                    <i class="fa-solid fa-{{ $statusIcons[$status] ?? 'check' }}"></i>
                                                    <span>{{ $statusLabels[$status] ?? 'Có mặt' }}</span>
                                                </button>
                                                <input type="hidden" id="{{ $noteId }}"
                                                    data-initial-value="{{ $note }}"
                                                    name="notes[{{ $col->id }}][{{ $student->id }}]" value="{{ $note }}">
                                                <button type="button" class="attendance-note-btn {{ $note ? 'has-note' : '' }}"
                                                    data-note-input="{{ $noteId }}" title="{{ $note ?: 'Thêm ghi chú' }}" @disabled($isStudentView)>
                                                    <i class="fa-solid fa-note-sticky"></i>
                                                </button>
                                            </div>
                                        @else
                                            @php($gradebookLock = $gradebookLockedCells[$col->id][$student->id] ?? null)
                                            <input type="text" name="data[{{ $col->id }}][{{ $student->id }}]"
                                                data-initial-value="{{ $attendanceData[$student->id][$col->id] ?? '' }}"
                                                value="{{ $attendanceData[$student->id][$col->id] ?? '' }}"
                                                placeholder="{{ $ph }}" @if ($isStudentView || $gradebookLock) readonly aria-label="{{ $col->name }}" @endif
                                                @if($gradebookLock) title="{{ $gradebookLock }}" @endif>
                                            @if($gradebookLock)<small class="d-block text-muted px-2 pb-1">{{ $gradebookLock }}</small>@endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 2 }}">
                                    <div class="att-empty">
                                        <i class="fa-solid fa-user-slash d-block"></i>
                                        <p>Chưa có học viên nào trong khóa học này</p>
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
                    <i class="fa-solid fa-{{ $isStudentView ? 'circle-info' : 'lightbulb' }}"></i>
                    {{ $isStudentView ? 'Dữ liệu do giáo viên cập nhật và chỉ bạn có thể xem dòng này.' : 'Click vào tên cột để đổi tên. Hover vào cột để xóa.' }}
                </div>
                @unless ($isStudentView)
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-save"></i> Lưu bảng điểm
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
    <div class="save-flash" id="saveFlash"><i class="fa-solid fa-circle-check"></i> Đã lưu thành công!</div>

    @push('scripts')
        @vite('resources/js/pages/attendance.js')
    @endpush
    @endunless
@endsection
