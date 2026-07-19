@php
    $scheduleDate = \Illuminate\Support\Carbon::parse($schedule->schedule_date);
    $startTime = \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i');
    $endTime = \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i');
    $hasNote = trim((string) ($schedule->note ?? '')) !== '';
@endphp

<li class="ss-item">
    <div class="ss-date {{ $hasNote ? 'exam' : '' }}">
        {{ $scheduleDate->format('d/m') }}
        <span>{{ $scheduleDate->locale('vi')->isoFormat('ddd') }}</span>
    </div>
    <div class="min-w-0">
        <div class="ss-item-title">{{ $schedule->course_title }}</div>
        <div class="ss-item-meta">
            <div><i class="fa-solid fa-clock me-1"></i>{{ $startTime }} - {{ $endTime }}</div>
            <div><i class="fa-solid fa-users me-1"></i>{{ $schedule->class_name }}</div>
            <div><i class="fa-solid fa-location-dot me-1"></i>{{ $schedule->room ?: 'Chưa cập nhật phòng học' }}</div>
            @if ($hasNote)
                <div class="ss-note"><i class="fa-solid fa-triangle-exclamation"></i>{{ $schedule->note }}</div>
            @endif
        </div>
    </div>
</li>
