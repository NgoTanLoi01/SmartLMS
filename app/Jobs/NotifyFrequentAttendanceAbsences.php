<?php

namespace App\Jobs;

use App\Models\AttendanceData;
use App\Services\NotificationCenter;
use App\Support\AttendanceStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyFrequentAttendanceAbsences implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 300;

    /** @param list<int> $userIds */
    public function __construct(
        public int $courseId,
        public array $userIds,
    ) {
        sort($this->userIds);
        $this->userIds = array_values(array_unique(array_map('intval', $this->userIds)));
        $this->onQueue('notifications');
    }

    public function uniqueId(): string
    {
        return $this->courseId.':'.hash('sha256', implode(',', $this->userIds));
    }

    /** @return array<int> */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(NotificationCenter $notifications): void
    {
        if ($this->userIds === []) {
            return;
        }

        $valuesByUser = AttendanceData::query()
            ->join('attendance_columns', 'attendance_data.attendance_column_id', '=', 'attendance_columns.id')
            ->where('attendance_columns.course_id', $this->courseId)
            ->where('attendance_columns.type', 'attendance')
            ->whereIn('attendance_data.user_id', $this->userIds)
            ->get(['attendance_data.user_id', 'attendance_data.value'])
            ->groupBy('user_id');

        foreach ($this->userIds as $userId) {
            $absenceCount = $valuesByUser->get($userId, collect())
                ->filter(fn (object $row): bool => AttendanceStatus::isAbsent($row->value))
                ->count();

            if ($absenceCount < 3) {
                continue;
            }

            $notifications->notifyUser(
                $userId,
                'attendance_warning',
                'Cảnh báo chuyên cần',
                "Bạn đã có {$absenceCount} lượt vắng/nghỉ trong khóa học. Hãy trao đổi với giáo viên nếu cần hỗ trợ.",
                route('attendance.show', $this->courseId),
                ['course_id' => $this->courseId, 'absence_count' => $absenceCount],
                "attendance-warning:{$this->courseId}:{$userId}:{$absenceCount}",
            );
        }
    }
}
