
            @php
                $pendingGrades = $data['pending_grades'] ?? 0;
                $todaySchedules = $data['today_schedules_count'] ?? 0;
                $attentionCount = $data['attention_students_count'] ?? 0;
                $nextSchedule = $data['next_schedule'] ?? null;
                $prioritySubmissions = $data['priority_submissions'] ?? collect();
                $prioritySuggestions = $data['teacher_priority_suggestions'] ?? [];
            @endphp

            {{-- 1. TODAY'S PRIORITY ACTIONS --}}
            <div class="section-heading anim-2">Việc cần làm hôm nay</div>
            <div class="teacher-priority-grid anim-2">
                <a href="{{ route('assignments.index') }}" class="teacher-priority-card teacher-priority-card--danger">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-pen"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Cần chấm</span>
                        <span class="teacher-priority-card__value">{{ $pendingGrades }}</span>
                        <span class="teacher-priority-card__hint">Bài nộp đang chờ giáo viên phản hồi.</span>
                    </span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('schedules.index') }}" class="teacher-priority-card teacher-priority-card--blue">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-calendar-day"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Lịch hôm nay</span>
                        <span class="teacher-priority-card__value">{{ $todaySchedules }}</span>
                        <span class="teacher-priority-card__hint">Ca dạy cần chuẩn bị trong ngày.</span>
                    </span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('classes.index') }}" class="teacher-priority-card teacher-priority-card--amber">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-user-clock"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Cần chú ý</span>
                        <span class="teacher-priority-card__value">{{ $attentionCount }}</span>
                        <span class="teacher-priority-card__hint">Học sinh nên được theo dõi sát hơn.</span>
                    </span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('classes.index') }}" class="teacher-priority-card teacher-priority-card--green">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-user-graduate"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Học sinh</span>
                        <span class="teacher-priority-card__value">{{ $data['total_students'] }}</span>
                        <span class="teacher-priority-card__hint">Học sinh trong các lớp đang phụ trách.</span>
                    </span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
            </div>

            {{-- 2. QUICK ACTIONS --}}
            <div class="teacher-action-strip anim-2">
                <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fa-solid fa-plus-circle"></i> Tạo
                    bài tập</a>
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fa-solid fa-book-open"></i> Mở khóa học</a>
                <a href="{{ route('schedules.index') }}" class="quick-action"><i class="fa-solid fa-calendar-days"></i> Xem lịch dạy</a>
                <a href="{{ route('classes.index') }}" class="quick-action"><i class="fa-solid fa-users"></i> Xem lớp</a>
            </div>

            <div class="teacher-command-grid anim-3">
                <div class="teacher-next-class">
                    <div class="teacher-next-class__eyebrow">Lớp sắp dạy</div>
                    @if ($nextSchedule)
                        @php
                            $nextStart = \Carbon\Carbon::parse(
                                $nextSchedule->schedule_date . ' ' . $nextSchedule->start_time,
                            );
                            $nextEnd = \Carbon\Carbon::parse(
                                $nextSchedule->schedule_date . ' ' . $nextSchedule->end_time,
                            );
                        @endphp
                        <h3 class="teacher-next-class__title">{{ $nextSchedule->course_title }}</h3>
                        <div class="teacher-next-class__meta">
                            <span><i class="fa-solid fa-school"></i>{{ $nextSchedule->class_name }}</span>
                            <span><i class="fa-regular fa-calendar"></i>{{ $nextStart->format('d/m/Y') }}</span>
                            <span><i class="fa-regular fa-clock"></i>{{ $nextStart->format('H:i') }} -
                                {{ $nextEnd->format('H:i') }}</span>
                            <span><i class="fa-solid fa-map-marker-alt"></i>{{ $nextSchedule->room ?? 'Online' }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.show', $nextSchedule->course_id) }}" class="btn-xs btn-xs--primary"><i class="fa-solid fa-book-open"></i> Vào khóa học</a>
                            <a href="{{ route('attendance.show', $nextSchedule->course_id) }}" class="btn-xs btn-xs--ghost"><i class="fa-solid fa-user-check"></i> Điểm danh</a>
                            <a href="{{ route('courses.show', ['course' => $nextSchedule->course_id, 'presentation' => 1]) }}" class="btn-xs btn-xs--ghost"><i class="fa-solid fa-display"></i> Trình chiếu</a>
                        </div>
                    @else
                        <h3 class="teacher-next-class__title">Chưa có ca dạy sắp tới</h3>
                        <div class="teacher-next-class__meta">
                            <span><i class="fa-solid fa-circle-check"></i>Không có lịch cần chuẩn bị ngay</span>
                        </div>
                        <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">
                            <i class="fa-solid fa-book-open"></i> Chuẩn bị khóa học
                        </a>
                    @endif
                </div>

                <div class="teacher-ai-panel">
                    <div class="teacher-ai-panel__eyebrow">Gợi ý ưu tiên</div>
                    <h3 class="teacher-ai-panel__title">Ưu tiên dựa trên dữ liệu hiện tại</h3>
                    @forelse ($prioritySuggestions as $suggestion)
                        <div class="teacher-ai-suggestion teacher-ai-suggestion--{{ $suggestion['type'] ?? 'primary' }}">
                            <span class="teacher-ai-suggestion__icon">
                                <i class="{{ $suggestion['icon'] ?? 'fa-solid fa-lightbulb' }}"></i>
                            </span>
                            <div>
                                <div class="teacher-ai-suggestion__title">{{ $suggestion['title'] }}</div>
                                <div class="teacher-ai-suggestion__body">{{ $suggestion['body'] }}</div>
                                <a href="{{ $suggestion['action_url'] }}" class="btn-xs btn-xs--ghost">
                                    {{ $suggestion['action_label'] }} <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:1.25rem 1rem">
                            <div class="empty-icon"><i class="fa-solid fa-lightbulb"></i></div>
                            <p>Chưa có gợi ý mới từ dữ liệu hiện tại.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- MANAGED CLASSES + STUDENTS NEEDING ATTENTION --}}
            <div class="section-heading anim-4">Lớp học &amp; học sinh cần theo dõi</div>
            <div class="row g-3 mb-4 anim-4">
                <div class="col-12 col-xl-7">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-school"></i></span>
                                Lớp phụ trách
                            </h6>
                            <a href="{{ route('classes.index') }}" class="btn-xs btn-xs--primary">Quản lý lớp</a>
                        </div>
                        <div class="row g-0">
                            @forelse ($data['teacher_classes'] ?? [] as $class)
                                <div class="col-12 col-lg-6">
                                    <div class="compact-card h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold" style="font-size:.85rem">{{ $class->name }}</div>
                                                <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                                    <span style="font-family:var(--font-mono)">{{ $class->code }}</span>
                                                    · {{ $class->courses->count() }} khóa học
                                                </div>
                                            </div>
                                            <span class="bdg bdg--primary">{{ $class->students_count }} HS</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <a href="{{ route('classes.progress', $class->id) }}"
                                                class="btn-xs btn-xs--primary">
                                                <i class="fa-solid fa-chart-line"></i> Tiến độ
                                            </a>
                                            <a href="{{ route('classes.students.index', $class->id) }}"
                                                class="btn-xs btn-xs--ghost">
                                                <i class="fa-solid fa-user-graduate"></i> Học sinh
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-school"></i></div>
                                        <p>Thầy / Cô chưa được phân công lớp.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--amber"><i class="fa-solid fa-user-clock"></i></span>
                                Học sinh cần chú ý
                            </h6>
                            <span class="bdg bdg--warning">Theo dõi</span>
                        </div>
                        @forelse ($data['attention_students'] ?? [] as $student)
                            <div class="compact-card">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div style="display:flex;align-items:center;gap:.6rem">
                                        <span class="user-avatar"
                                            style="flex-shrink:0">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-bold" style="font-size:.85rem">{{ $student->name }}</div>
                                            <div class="text-muted" style="font-size:.75rem">{{ $student->class_name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end" style="flex-shrink:0">
                                        <div
                                            class="bdg {{ $student->avg_grade !== null && $student->avg_grade < 5 ? 'bdg--danger' : 'bdg--muted' }} mb-1">
                                            TB {{ $student->avg_grade !== null ? round($student->avg_grade, 1) : 'N/A' }}
                                        </div>
                                        <a href="{{ route('classes.students.show', ['classId' => $student->class_id, 'studentId' => $student->id]) }}"
                                            class="btn-xs btn-xs--primary">
                                            Hồ sơ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon" style="color:var(--success)"><i class="fa-solid fa-circle-check"></i>
                                </div>
                                <p>Chưa có học sinh cần ưu tiên theo dõi.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 5. ASSIGNMENTS REQUIRING GRADING --}}
            <div class="section-heading anim-5">Bài cần chấm</div>
            <div class="row g-3 mb-4 anim-5">
                <div class="col-md-8">
                    <div class="priority-submissions">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--red"><i class="fa-solid fa-inbox"></i></span>
                                Bài cần chấm ưu tiên
                            </h6>
                            <span class="bdg bdg--danger">{{ $prioritySubmissions->count() }} bài</span>
                        </div>
                        <div class="teacher-list-scroll">
                            @forelse ($prioritySubmissions as $sub)
                                @php
                                    $dueDate = $sub->due_date ? \Carbon\Carbon::parse($sub->due_date) : null;
                                    $submittedAt = $sub->submitted_at
                                        ? \Carbon\Carbon::parse($sub->submitted_at)
                                        : \Carbon\Carbon::parse($sub->created_at);
                                    $isOverdue = $dueDate && $dueDate->isPast();
                                @endphp
                                <div class="priority-submission">
                                    <div class="priority-submission__main">
                                        <span
                                            class="feed-item__avatar">{{ mb_strtoupper(mb_substr($sub->student_name, 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <div class="priority-submission__title">{{ $sub->assignment_title ?? 'N/A' }}
                                            </div>
                                            <div class="priority-submission__meta">
                                                {{ $sub->student_name }} · {{ $sub->course_title ?? 'N/A' }}
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <span class="bdg {{ $isOverdue ? 'bdg--danger' : 'bdg--warning' }}">
                                                    {{ $isOverdue ? 'Quá hạn' : 'Đến hạn' }}
                                                    {{ $dueDate ? $dueDate->format('d/m H:i') : 'chưa rõ' }}
                                                </span>
                                                <span class="bdg bdg--muted">
                                                    Nộp {{ $submittedAt->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('assignments.submissions.review', $sub->id) }}"
                                        class="btn-xs btn-xs--danger">
                                        <i class="fa-solid fa-pen"></i> Chấm ngay
                                    </a>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon" style="color:var(--success)"><i
                                            class="fa-solid fa-circle-check"></i></div>
                                    <p>Tuyệt vời! Thầy / Cô đã chấm hết bài.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-chart-pie"></i></span>
                                Tiến độ chấm bài
                            </h6>
                        </div>
                        <div class="chart-wrap d-flex justify-content-center align-items-center" style="min-height:260px">
                            <div id="teacherChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. WEEKLY TEACHING SCHEDULE --}}
            <div class="section-heading">Lịch dạy tuần này</div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-calendar-days"></i></span>
                                Lịch dạy tuần này
                            </h6>
                            <span class="bdg bdg--primary">Tuần này · {{ $data['dashboard_week_label'] ?? '' }}</span>
                        </div>
                        <div class="table-responsive teacher-schedule-table">
                            @php
                                $days = [
                                    'Monday' => 'Thứ Hai',
                                    'Tuesday' => 'Thứ Ba',
                                    'Wednesday' => 'Thứ Tư',
                                    'Thursday' => 'Thứ Năm',
                                    'Friday' => 'Thứ Sáu',
                                    'Saturday' => 'Thứ Bảy',
                                    'Sunday' => 'Chủ Nhật',
                                ];
                            @endphp

                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Giờ dạy</th>
                                        <th>Môn / Lớp</th>
                                        <th>Phòng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['week_schedule'] ?? [] as $slot)
                                        @php
                                            $d = \Carbon\Carbon::parse($slot->schedule_date);
                                            $today = $d->isToday();
                                            $past = $d->isPast() && !$today;
                                            $isExamSchedule = ($slot->note ?? null) === 'Thi kết thúc môn';
                                        @endphp

                                        <tr class="{{ $today ? 'is-today' : ($past ? 'is-past' : '') }}">
                                            <td>
                                                <div class="fw-bold" style="font-size:.85rem">
                                                    {{ $d->format('d/m/Y') }}
                                                </div>
                                                <div style="font-size:.75rem;color:var(--text-muted)">
                                                    {{ $days[$d->format('l')] ?? $d->format('l') }}
                                                </div>
                                            </td>

                                            <td>
                                                <span
                                                    style="font-size:.84rem;font-weight:700;color:{{ $isExamSchedule ? 'var(--danger)' : 'var(--brand)' }};font-family:var(--font-mono)">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                                    –
                                                    {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="fw-bold"
                                                    style="font-size:.85rem;color:{{ $isExamSchedule ? 'var(--danger)' : 'inherit' }}">
                                                    {{ $slot->course_title }}
                                                </div>
                                                @if ($isExamSchedule)
                                                    <span class="bdg bdg--danger mb-1">Thi kết thúc môn</span>
                                                @endif
                                                <div style="font-size:.75rem;color:var(--text-muted)">
                                                    Lớp: {{ $slot->class_name }}
                                                </div>
                                            </td>

                                            <td>
                                                @if ($today)
                                                    <span class="bdg bdg--success mb-1">Hôm nay</span><br>
                                                @elseif ($past)
                                                    <span class="bdg bdg--muted mb-1">Đã dạy</span><br>
                                                @endif

                                                <span class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--muted' }}">
                                                    {{ $slot->room ?? 'Online' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-icon">
                                                        <i class="fa-solid fa-calendar-times"></i>
                                                    </div>
                                                    <p>
                                                        <strong>Chưa có lịch dạy.</strong><br>
                                                        Lịch giảng dạy sẽ hiển thị tại đây.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="teacher-schedule-list">
                            @forelse ($data['week_schedule'] ?? [] as $slot)
                                @php
                                    $d = \Carbon\Carbon::parse($slot->schedule_date);
                                    $today = $d->isToday();
                                    $past = $d->isPast() && !$today;
                                    $isExamSchedule = ($slot->note ?? null) === 'Thi kết thúc môn';
                                @endphp
                                <div class="teacher-schedule-card">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <div class="fw-bold" style="font-size:.9rem">{{ $slot->course_title }}</div>
                                            <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                                {{ $d->format('d/m/Y') }} · {{ $d->translatedFormat('l') }}
                                            </div>
                                        </div>
                                        @if ($today)
                                            <span class="bdg bdg--success">Hôm nay</span>
                                        @elseif ($past)
                                            <span class="bdg bdg--muted">Đã dạy</span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--primary' }}">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                            –
                                            {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                        </span>
                                        <span class="bdg bdg--muted">{{ $slot->class_name }}</span>
                                        <span class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--muted' }}">
                                            {{ $slot->room ?? 'Online' }}
                                        </span>
                                    </div>
                                    @if ($isExamSchedule)
                                        <div class="mt-2">
                                            <span class="bdg bdg--danger">Thi kết thúc môn</span>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fa-solid fa-calendar-times"></i>
                                    </div>
                                    <p>
                                        <strong>Chưa có lịch dạy.</strong><br>
                                        Lịch giảng dạy sẽ hiển thị tại đây.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
             STUDENT VIEW
        ══════════════════════════════════════ --}}
