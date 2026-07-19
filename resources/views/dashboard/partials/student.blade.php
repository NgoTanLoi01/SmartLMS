
            @php
                $studentNextSchedule = $data['next_schedule'] ?? null;
                $continueCourse = $data['continue_course'] ?? null;
                $recentFeedback = $data['recent_feedback'] ?? collect();
            @endphp

            <div class="section-heading anim-2">Hôm nay của em</div>
            <div class="teacher-command-grid anim-2 mb-4">
                <div class="teacher-next-class">
                    <div class="teacher-next-class__eyebrow">Lịch học tiếp theo</div>
                    @if ($studentNextSchedule)
                        @php
                            $studentNextStart = \Carbon\Carbon::parse($studentNextSchedule->schedule_date . ' ' . $studentNextSchedule->start_time);
                            $studentNextEnd = \Carbon\Carbon::parse($studentNextSchedule->schedule_date . ' ' . $studentNextSchedule->end_time);
                        @endphp
                        <h3 class="teacher-next-class__title">{{ $studentNextSchedule->course_title }}</h3>
                        <div class="teacher-next-class__meta">
                            <span><i class="fa-solid fa-school"></i>{{ $studentNextSchedule->class_name }}</span>
                            <span><i class="fa-regular fa-calendar"></i>{{ $studentNextStart->format('d/m/Y') }}</span>
                            <span><i class="fa-regular fa-clock"></i>{{ $studentNextStart->format('H:i') }} - {{ $studentNextEnd->format('H:i') }}</span>
                            <span><i class="fa-solid fa-location-dot"></i>{{ $studentNextSchedule->room ?? 'Online' }}</span>
                        </div>
                        <a href="{{ route('courses.show', $studentNextSchedule->course_id) }}" class="btn-xs btn-xs--primary"><i class="fa-solid fa-arrow-right"></i> Mở khóa học</a>
                    @else
                        <h3 class="teacher-next-class__title">Chưa có lịch học sắp tới</h3>
                        <div class="teacher-next-class__meta"><span><i class="fa-solid fa-calendar-check"></i>Em có thể tiếp tục bài học đang dở.</span></div>
                    @endif
                </div>

                <div class="teacher-ai-panel">
                    <div class="teacher-ai-panel__eyebrow">Tiếp tục học</div>
                    @if ($continueCourse)
                        <h3 class="teacher-ai-panel__title">{{ $continueCourse->title }}</h3>
                        <div class="progress-line mb-2"><span style="width:{{ $continueCourse->progress }}%"></span></div>
                        <div class="text-muted small mb-3">Đã hoàn thành {{ $continueCourse->lesson_completed }}/{{ $continueCourse->lesson_total }} bài · {{ $continueCourse->progress }}%</div>
                        <a href="{{ route('courses.show', $continueCourse->id) }}" class="btn-xs btn-xs--primary">Tiếp tục học <i class="fa-solid fa-arrow-right"></i></a>
                    @else
                        <div class="empty-state" style="padding:1.25rem 1rem"><div class="empty-icon"><i class="fa-solid fa-book-open"></i></div><p>Chưa có khóa học để tiếp tục.</p></div>
                    @endif
                </div>
            </div>

            <div class="row g-3 mb-4 anim-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card__icon"><i class="fa-solid fa-book-open"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Khóa học đang học</div>
                            <div class="stat-card__value">{{ $data['total_courses'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--amber">
                        <div class="stat-card__icon"><i class="fa-solid fa-file-signature"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bài tập còn thiếu</div>
                            <div class="stat-card__value">{{ $data['missing_assignments_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--violet">
                        <div class="stat-card__icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Quiz chưa làm</div>
                            <div class="stat-card__value">{{ $data['pending_quizzes_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($recentFeedback->isNotEmpty())
                <div class="row g-3 mb-4 anim-4">
                    <div class="col-12">
                        <div class="panel">
                            <div class="panel__header">
                                <h6 class="panel__title"><span class="icon-dot idot--green"><i class="fa-solid fa-comment-dots"></i></span>Điểm và nhận xét mới</h6>
                                <a href="{{ route('students.grades') }}" class="btn-xs btn-xs--primary">Xem tất cả</a>
                            </div>
                            <div class="row g-0">
                                @foreach ($recentFeedback as $feedback)
                                    <div class="col-12 col-lg-6">
                                        <div class="compact-card h-100">
                                            <div class="d-flex justify-content-between gap-2 mb-1">
                                                <div class="fw-bold" style="font-size:.85rem">{{ $feedback->assignment_title }}</div>
                                                @if ($feedback->grade !== null)<span class="bdg bdg--success">{{ round($feedback->grade, 1) }} điểm</span>@endif
                                            </div>
                                            <div class="text-muted" style="font-size:.75rem">{{ $feedback->course_title }}</div>
                                            @if ($feedback->feedback)<div class="mt-2" style="font-size:.8rem">{{ \Illuminate\Support\Str::limit($feedback->feedback, 120) }}</div>@endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-3 mb-4 anim-4">
                <div class="col-md-4">
                    <div class="score-hero">
                        <div class="score-hero__label">Điểm Quiz trung bình</div>
                        <div class="score-hero__ring">
                            <div class="score-hero__value" style="position:relative;z-index:1">
                                {{ $data['average_score'] }}</div>
                        </div>
                        <div class="score-hero__sub">/ 10 điểm</div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--amber"><i class="fa-solid fa-clock"></i></span>
                                Deadline & Bài kiểm tra
                            </h6>
                        </div>
                        <div style="max-height:300px;overflow-y:auto">
                            @php
                                $deadlines = $data['upcoming_deadlines'] ?? [];
                                $quizzes = $data['pending_quizzes'] ?? [];
                            @endphp

                            @foreach ($deadlines as $dl)
                                <div class="todo-item">
                                    <div>
                                        <span class="bdg bdg--warning mb-1">Bài tập</span>
                                        <div class="todo-item__label">{{ $dl->title }}</div>
                                        <div class="todo-item__sub"><i
                                                class="fa-solid fa-book me-1"></i>{{ $dl->course_title ?? 'N/A' }}</div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="todo-item__deadline">
                                            <i class="fa-solid fa-hourglass-half me-1"></i>
                                            {{ \Carbon\Carbon::parse($dl->due_date)->format('H:i - d/m/Y') }}
                                        </div>
                                        <a href="{{ route('courses.show', $dl->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--warning">Nộp bài</a>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($quizzes as $quiz)
                                <div class="todo-item todo-item--quiz">
                                    <div>
                                        <span class="bdg bdg--primary mb-1">Kiểm tra</span>
                                        <div class="todo-item__label" style="color:var(--brand)">{{ $quiz->title }}
                                        </div>
                                        <div class="todo-item__sub"><i
                                                class="fa-solid fa-book me-1"></i>{{ $quiz->course_title ?? 'N/A' }}</div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="todo-item__time-limit">
                                            <i class="fa-solid fa-stopwatch me-1"></i>{{ $quiz->time_limit }} phút
                                        </div>
                                        <a href="{{ route('courses.show', $quiz->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--primary">Làm ngay</a>
                                    </div>
                                </div>
                            @endforeach

                            @if (count($deadlines) === 0 && count($quizzes) === 0)
                                <div class="empty-state">
                                    <div class="empty-icon" style="color:var(--success)"><i
                                            class="fa-solid fa-glass-cheers"></i></div>
                                    <p>Tuyệt vời! Bạn đã hoàn thành hết các nhiệm vụ.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4 anim-5">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-chart-line"></i></span>
                                Tiến độ khóa học của tôi
                            </h6>
                            <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">Vào học</a>
                        </div>
                        <div class="row g-0">
                            @forelse ($data['course_progress'] ?? [] as $course)
                                <div class="col-12 col-lg-6">
                                    <div class="compact-card">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold" style="font-size:.85rem">{{ $course->title }}</div>
                                                <div class="text-muted" style="font-size:.75rem;margin-top:.15rem">
                                                    {{ $course->lesson_completed }}/{{ $course->lesson_total }} bài học
                                                    hoàn thành
                                                </div>
                                            </div>
                                            <span class="bdg bdg--primary">{{ $course->progress }}%</span>
                                        </div>
                                        <div class="progress-line mb-3">
                                            <span style="width: {{ $course->progress }}%"></span>
                                        </div>
                                        <a href="{{ route('courses.show', $course->id) }}"
                                            class="btn-xs btn-xs--primary">
                                            Tiếp tục học
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-book-open"></i></div>
                                        <p>Bạn chưa được gán khóa học.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--green"><i class="fa-solid fa-calendar-check"></i></span>
                                Lịch học tuần này
                            </h6>
                            <span class="bdg bdg--success">Tuần này · {{ $data['dashboard_week_label'] ?? '' }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Giờ học</th>
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
                                                <div class="fw-bold" style="font-size:.85rem">{{ $d->format('d/m/Y') }}
                                                </div>
                                                <div style="font-size:.75rem;color:var(--text-muted)">
                                                    {{ $d->translatedFormat('l') }}</div>
                                            </td>
                                            <td>
                                                <span
                                                    style="font-size:.84rem;font-weight:700;color:{{ $isExamSchedule ? 'var(--danger)' : 'var(--brand)' }};font-family:var(--font-mono)">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} –
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
                                                <div style="font-size:.75rem;color:var(--text-muted)">Lớp:
                                                    {{ $slot->class_name }}</div>
                                            </td>
                                            <td>
                                                @if ($today)
                                                    <span class="bdg bdg--success mb-1">Hôm nay</span><br>
                                                @elseif ($past)
                                                    <span class="bdg bdg--muted mb-1">Đã học</span><br>
                                                @endif
                                                <span
                                                    class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--muted' }}">{{ $slot->room ?? 'Online' }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-icon"><i class="fa-solid fa-calendar-times"></i></div>
                                                    <p><strong>Chưa có lịch học.</strong><br>Lịch học sẽ hiển thị tại đây.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--violet"><i class="fa-solid fa-chart-bar"></i></span>
                                Điểm các bài kiểm tra gần đây
                            </h6>
                        </div>
                        <div class="chart-wrap">
                            @if (!empty($data['chart_quiz_data']))
                                <div id="studentChart"></div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-chart-bar"></i></div>
                                    <p>Bạn chưa làm bài kiểm tra nào.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
