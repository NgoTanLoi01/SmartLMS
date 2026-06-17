@php
    $record = $record ?? null;
    $statuses = \App\Models\TeachingRecord::statuses();
@endphp

<div class="teaching-form">
    <div class="row g-3">
        @if (auth()->user()->role === 'admin')
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Giáo viên</label>
                <select name="teacher_id" class="form-select" required>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $record?->teacher_id ?? auth()->id()) == $teacher->id)>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Course liên kết</label>
            <select name="course_id" class="form-select">
                <option value="">Tự khớp theo tên môn hoặc không liên kết</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" data-title="{{ $course->title }}" @selected(old('course_id', $record?->course_id) == $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Tên môn học</label>
            <input type="text" name="subject_name" class="form-control"
                value="{{ old('subject_name', $record?->subject_name) }}" required placeholder="VD: Lập trình Web Frontend">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Class liên kết</label>
            <select name="class_id" class="form-select">
                <option value="">Tự khớp theo tên/mã lớp hoặc không liên kết</option>
                @foreach ($classes as $classroom)
                    <option value="{{ $classroom->id }}" data-name="{{ $classroom->name }}" @selected(old('class_id', $record?->class_id) == $classroom->id)>
                        {{ $classroom->name }}{{ $classroom->code ? ' · ' . $classroom->code : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Lớp</label>
            <input type="text" name="class_name" class="form-control"
                value="{{ old('class_name', $record?->class_name) }}" placeholder="VD: T24TH01-02C">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Trung tâm</label>
            <input type="text" name="center_name" class="form-control"
                value="{{ old('center_name', $record?->center_name) }}" placeholder="VD: Trà Cú">
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Khóa</label>
            <input type="text" name="term_code" class="form-control"
                value="{{ old('term_code', $record?->term_code) }}" placeholder="VD: K20">
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Số buổi</label>
            <input type="number" name="planned_sessions" min="0" max="999" class="form-control"
                value="{{ old('planned_sessions', $record?->planned_sessions ?? 0) }}">
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $record?->status ?? 'teaching') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Ngày bắt đầu</label>
            <input type="date" name="start_date" class="form-control"
                value="{{ old('start_date', $record?->start_date?->format('Y-m-d')) }}">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Ngày kết thúc</label>
            <input type="date" name="end_date" class="form-control"
                value="{{ old('end_date', $record?->end_date?->format('Y-m-d')) }}">
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú thêm nếu có">{{ old('note', $record?->note) }}</textarea>
        </div>
    </div>
</div>
