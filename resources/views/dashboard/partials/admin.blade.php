

            <div class="section-heading anim-2">Cần xử lý</div>
            <div class="teacher-priority-grid anim-2 mb-4">
                <a href="{{ route('courses.index', ['status' => 'draft']) }}" class="teacher-priority-card teacher-priority-card--amber">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-file-pen"></i></span>
                    <span><span class="teacher-priority-card__label">Khóa học bản nháp</span><span class="teacher-priority-card__value">{{ $data['draft_courses_count'] ?? 0 }}</span><span class="teacher-priority-card__hint">Nội dung chưa được xuất bản.</span></span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('classes.index') }}" class="teacher-priority-card teacher-priority-card--danger">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-user-slash"></i></span>
                    <span><span class="teacher-priority-card__label">Chưa phân công</span><span class="teacher-priority-card__value">{{ $data['classes_without_teacher_count'] ?? 0 }}</span><span class="teacher-priority-card__hint">Lớp chưa có giáo viên phụ trách.</span></span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('classes.index') }}" class="teacher-priority-card teacher-priority-card--blue">
                    <span class="teacher-priority-card__icon"><x-ui.icon name="book" /></span>
                    <span><span class="teacher-priority-card__label">Lớp chưa có khóa</span><span class="teacher-priority-card__value">{{ $data['classes_without_courses_count'] ?? 0 }}</span><span class="teacher-priority-card__hint">Cần gắn nội dung học tập.</span></span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('courses.index', ['status' => 'archived']) }}" class="teacher-priority-card teacher-priority-card--green">
                    <span class="teacher-priority-card__icon"><i class="fa-solid fa-box-archive"></i></span>
                    <span><span class="teacher-priority-card__label">Đã lưu trữ</span><span class="teacher-priority-card__value">{{ $data['archived_courses_count'] ?? 0 }}</span><span class="teacher-priority-card__hint">Khóa học đang được lưu trữ.</span></span>
                    <i class="fa-solid fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
            </div>

            <div class="row g-3 mb-4 anim-3">
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card__icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Học sinh</div>
                            <div class="stat-card__value">{{ $data['total_students'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--teal">
                        <div class="stat-card__icon"><i class="fa-solid fa-chalkboard-teacher"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Giáo viên</div>
                            <div class="stat-card__value">{{ $data['total_teachers'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--green">
                        <div class="stat-card__icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Khóa / Lớp</div>
                            <div class="stat-card__value">
                                {{ $data['total_courses'] }}<span
                                    style="font-size:1rem;font-weight:600;color:var(--text-muted)">/{{ $data['total_classes'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--amber">
                        <div class="stat-card__icon"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bài chờ chấm</div>
                            <div class="stat-card__value">{{ $data['pending_grades'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 anim-4">
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-user-plus"></i></span>
                                Người dùng mới
                            </h6>
                            <a href="{{ route('users.index') }}" class="btn-xs btn-xs--ghost"><i
                                    class="fa-solid fa-arrow-right"></i> Xem tất cả</a>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Ngày tham gia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['recent_users'] as $user)
                                        @php $r = $user->role; @endphp
                                        <tr>
                                            <td>
                                                <div style="display:flex;align-items:center">
                                                    <span
                                                        class="user-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                                    <span class="fw-bold">{{ $user->name }}</span>
                                                </div>
                                            </td>
                                            <td
                                                style="color:var(--text-muted);font-family:var(--font-mono);font-size:.78rem">
                                                {{ $user->email }}</td>
                                            <td>
                                                <span
                                                    class="bdg {{ $r === 'teacher' ? 'bdg--info' : ($r === 'admin' ? 'bdg--dark' : 'bdg--primary') }}">
                                                    {{ strtoupper($r) }}
                                                </span>
                                            </td>
                                            <td
                                                style="color:var(--text-muted);font-size:.78rem;font-family:var(--font-mono)">
                                                {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                                    <p>Chưa có người dùng mới.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--green"><i class="fa-solid fa-chart-pie"></i></span>
                                Tỷ lệ người dùng
                            </h6>
                        </div>
                        <div class="chart-wrap d-flex justify-content-center align-items-center" style="min-height:260px">
                            <div id="adminChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1 anim-5">
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--amber"><i class="fa-solid fa-calendar-day"></i></span>
                                Lịch học hôm nay
                            </h6>
                            <span class="bdg bdg--warning">{{ ($data['today_schedules'] ?? collect())->count() }}
                                ca</span>
                        </div>
                        @forelse ($data['today_schedules'] ?? [] as $slot)
                            <div class="compact-card">
                                <div class="fw-bold" style="font-size:.85rem">{{ $slot->course_title }}</div>
                                <div class="text-muted" style="font-size:.76rem;margin-top:.2rem">
                                    {{ $slot->class_name }} · <span
                                        style="font-family:var(--font-mono)">{{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                        – {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-calendar-check"></i></div>
                                <p>Hôm nay chưa có lịch học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-school"></i></span>
                                Lớp nổi bật
                            </h6>
                            <a href="{{ route('classes.index') }}" class="btn-xs btn-xs--primary">Xem lớp</a>
                        </div>
                        @forelse ($data['class_overview'] ?? [] as $class)
                            <div class="compact-card">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div>
                                        <div class="fw-bold" style="font-size:.85rem">{{ $class->name }}</div>
                                        <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                            {{ $class->teacher->name ?? 'Chưa phân công' }}</div>
                                    </div>
                                    <span class="bdg bdg--primary">{{ $class->students_count }} HS</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-school"></i></div>
                                <p>Chưa có lớp học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--green"><i class="fa-solid fa-book-open"></i></span>
                                Khóa học mới
                            </h6>
                            <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">Xem khóa</a>
                        </div>
                        @forelse ($data['recent_courses'] ?? [] as $course)
                            <div class="compact-card">
                                <div class="fw-bold" style="font-size:.85rem">{{ $course->title }}</div>
                                <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                    {{ $course->teacher->name ?? 'Chưa rõ giáo viên' }}</div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-book"></i></div>
                                <p>Chưa có khóa học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
             TEACHER VIEW
        ══════════════════════════════════════ --}}
