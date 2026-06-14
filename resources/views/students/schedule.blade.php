@extends('layouts.app')

@section('title', 'Lịch học cá nhân')

@section('content')
    <style>
        .student-schedule {
            color: #0f172a;
        }

        .ss-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .ss-title {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: 0;
        }

        .ss-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .ss-filter {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .ss-filter label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
        }

        .ss-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .ss-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }

        .ss-stat-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 8px;
        }

        .ss-stat-value {
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
        }

        .ss-stat-note {
            margin-top: 8px;
            color: #94a3b8;
            font-size: 12px;
        }

        .ss-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 18px;
            align-items: start;
        }

        .ss-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .ss-panel-head {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ss-panel-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ss-calendar-wrap {
            padding: 14px;
        }

        #student-calendar {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 16px;
            font-weight: 800;
        }

        .fc .fc-button {
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            color: #334155 !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            box-shadow: none !important;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #fff !important;
        }

        .fc .fc-event {
            border: 0 !important;
            border-radius: 6px !important;
            padding: 2px 5px !important;
            font-size: 12px !important;
            cursor: pointer;
        }

        .fc td,
        .fc th,
        .fc .fc-scrollgrid {
            border-color: #eef2f7 !important;
        }

        .ss-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .ss-item {
            padding: 14px 16px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
        }

        .ss-item:first-child {
            border-top: 0;
        }

        .ss-date {
            width: 58px;
            min-width: 58px;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
            text-align: center;
            padding: 7px 4px;
            font-weight: 800;
            line-height: 1.15;
        }

        .ss-date span {
            display: block;
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            margin-top: 2px;
        }

        .ss-date.exam {
            background: #fef2f2;
            color: #dc2626;
        }

        .ss-item-title {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .ss-item-meta {
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
        }

        .ss-note {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #dc2626;
            font-weight: 700;
        }

        .ss-empty {
            padding: 28px 16px;
            text-align: center;
            color: #64748b;
        }

        .ss-empty i {
            display: block;
            font-size: 26px;
            opacity: .35;
            margin-bottom: 8px;
        }

        @media (max-width: 1199.98px) {
            .ss-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991.98px) {
            .ss-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .ss-title {
                font-size: 20px;
            }

            .ss-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .ss-filter .form-select,
            .ss-filter .btn {
                width: 100%;
            }

            .ss-stat-grid {
                grid-template-columns: 1fr;
            }

            .ss-calendar-wrap {
                padding: 10px;
            }

            .fc .fc-toolbar {
                flex-direction: column;
                gap: 10px;
            }

            .fc .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }

            .fc .fc-toolbar-title {
                font-size: 14px;
                text-align: center;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    @php
        $formatScheduleDate = function ($schedule) {
            return \Illuminate\Support\Carbon::parse($schedule->schedule_date);
        };
        $formatTime = function ($time) {
            return \Illuminate\Support\Carbon::parse($time)->format('H:i');
        };
    @endphp

    <div class="student-schedule">
        <div class="ss-header">
            <div>
                <h1 class="ss-title">Lịch học cá nhân</h1>
                <p class="ss-subtitle">Theo dõi lịch học theo ngày, tuần và các buổi thi kết thúc môn.</p>
            </div>
        </div>

        <form action="{{ route('students.schedule') }}" method="GET" class="ss-filter">
            <div class="flex-grow-1">
                <label for="course_id">Khóa học</label>
                <select name="course_id" id="course_id" class="form-select">
                    <option value="">Tất cả khóa học</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? null) === $course->id)>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i>Lọc
            </button>
            <a href="{{ route('students.schedule') }}" class="btn btn-light border">
                <i class="fas fa-rotate-left"></i>
            </a>
        </form>

        <div class="ss-stat-grid">
            <div class="ss-stat">
                <div class="ss-stat-label">Hôm nay</div>
                <div class="ss-stat-value">{{ $todaySchedules->count() }}</div>
                <div class="ss-stat-note">buổi học</div>
            </div>
            <div class="ss-stat">
                <div class="ss-stat-label">Từ nay đến cuối tuần</div>
                <div class="ss-stat-value">{{ $weekSchedules->count() }}</div>
                <div class="ss-stat-note">buổi học</div>
            </div>
            <div class="ss-stat">
                <div class="ss-stat-label">Sắp tới</div>
                <div class="ss-stat-value">{{ $upcomingSchedules->count() }}</div>
                <div class="ss-stat-note">buổi gần nhất</div>
            </div>
            <div class="ss-stat">
                <div class="ss-stat-label">Ngày thi</div>
                <div class="ss-stat-value text-danger">{{ $examSchedules->count() }}</div>
                <div class="ss-stat-note">mốc cần chú ý</div>
            </div>
        </div>

        <div class="ss-layout">
            <div class="ss-panel">
                <div class="ss-panel-head">
                    <h2 class="ss-panel-title"><i class="fas fa-calendar text-primary"></i>Lịch học</h2>
                    <span class="badge bg-danger-subtle text-danger rounded-pill">Đỏ: có ghi chú/thi</span>
                </div>
                <div class="ss-calendar-wrap">
                    <div id="student-calendar"></div>
                </div>
            </div>

            <div class="d-flex flex-column gap-3">
                <div class="ss-panel">
                    <div class="ss-panel-head">
                        <h2 class="ss-panel-title"><i class="fas fa-clock text-primary"></i>Hôm nay</h2>
                    </div>
                    @if ($todaySchedules->isEmpty())
                        <div class="ss-empty">
                            <i class="fas fa-mug-hot"></i>
                            Hôm nay chưa có lịch học.
                        </div>
                    @else
                        <ul class="ss-list">
                            @foreach ($todaySchedules as $schedule)
                                @include('students.partials.schedule-item', ['schedule' => $schedule])
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="ss-panel">
                    <div class="ss-panel-head">
                        <h2 class="ss-panel-title"><i class="fas fa-list-check text-success"></i>Lịch sắp tới</h2>
                    </div>
                    @if ($upcomingSchedules->isEmpty())
                        <div class="ss-empty">
                            <i class="fas fa-calendar-xmark"></i>
                            Chưa có lịch học sắp tới.
                        </div>
                    @else
                        <ul class="ss-list">
                            @foreach ($upcomingSchedules as $schedule)
                                @include('students.partials.schedule-item', ['schedule' => $schedule])
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="ss-panel">
                    <div class="ss-panel-head">
                        <h2 class="ss-panel-title"><i class="fas fa-triangle-exclamation text-danger"></i>Ngày cần chú ý</h2>
                    </div>
                    @if ($examSchedules->isEmpty())
                        <div class="ss-empty">
                            <i class="fas fa-circle-check"></i>
                            Chưa có ghi chú thi kết thúc môn sắp tới.
                        </div>
                    @else
                        <ul class="ss-list">
                            @foreach ($examSchedules as $schedule)
                                @include('students.partials.schedule-item', ['schedule' => $schedule])
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scheduleDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="scheduleDetailTitle">Chi tiết lịch học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2 small">
                        <div><strong>Khóa học:</strong> <span id="scheduleDetailCourse"></span></div>
                        <div><strong>Lớp:</strong> <span id="scheduleDetailClass"></span></div>
                        <div><strong>Thời gian:</strong> <span id="scheduleDetailTime"></span></div>
                        <div><strong>Phòng học:</strong> <span id="scheduleDetailRoom"></span></div>
                        <div id="scheduleDetailNoteWrap" class="text-danger fw-bold d-none">
                            <i class="fas fa-triangle-exclamation me-1"></i><span id="scheduleDetailNote"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('student-calendar');
            if (!calendarEl) return;

            const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
            const detailModal = new bootstrap.Modal(document.getElementById('scheduleDetailModal'));
            const eventUrl = @json(route('students.schedule', array_filter(['course_id' => $filters['course_id'] ?? null])));

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: isMobile ? 'dayGridMonth' : 'timeGridWeek',
                headerToolbar: {
                    left: isMobile ? 'prev,next' : 'prev,next today',
                    center: 'title',
                    right: isMobile ? 'today' : 'timeGridWeek,dayGridMonth',
                },
                buttonText: {
                    today: 'Hôm nay',
                    week: 'Tuần',
                    month: 'Tháng',
                },
                locale: 'vi',
                allDaySlot: false,
                slotMinTime: '07:00:00',
                slotMaxTime: '22:00:00',
                events: eventUrl,
                eventClick: function(info) {
                    const event = info.event;
                    const props = event.extendedProps || {};
                    const start = event.start ? event.start.toLocaleString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit',
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                    }) : '';
                    const end = event.end ? event.end.toLocaleTimeString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }) : '';

                    document.getElementById('scheduleDetailTitle').textContent = event.title;
                    document.getElementById('scheduleDetailCourse').textContent = props.course || '—';
                    document.getElementById('scheduleDetailClass').textContent = props.class || '—';
                    document.getElementById('scheduleDetailTime').textContent = end ? `${start} - ${end}` : start;
                    document.getElementById('scheduleDetailRoom').textContent = props.room || 'Chưa cập nhật';

                    const noteWrap = document.getElementById('scheduleDetailNoteWrap');
                    const note = props.note || '';
                    document.getElementById('scheduleDetailNote').textContent = note;
                    noteWrap.classList.toggle('d-none', !note);

                    detailModal.show();
                },
            });

            calendar.render();
        });
    </script>
@endpush
