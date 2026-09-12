<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use LogicException;

class ScheduleWriteLockService
{
    /**
     * Serialize schedule writes touching the same class, teacher or room.
     * Call this inside the transaction and before checking conflicts.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     */
    public function acquire(array $candidates): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Khóa tài nguyên lịch phải được lấy bên trong transaction.');
        }

        $activeCandidates = collect($candidates)
            ->filter(fn (array $candidate) => ($candidate['status'] ?? Schedule::STATUS_ACTIVE) === Schedule::STATUS_ACTIVE)
            ->values();

        if ($activeCandidates->isEmpty()) {
            return;
        }

        $teacherIds = Classroom::query()
            ->whereKey($activeCandidates->pluck('class_id')->filter()->unique()->all())
            ->pluck('teacher_id', 'id');
        $keys = $activeCandidates
            ->flatMap(function (array $candidate) use ($teacherIds): array {
                $classId = (int) ($candidate['class_id'] ?? 0);
                $teacherId = (int) ($teacherIds[$classId] ?? 0);
                $room = $this->normalizeRoom($candidate['room'] ?? null);
                $keys = $classId > 0 ? ["class:{$classId}"] : [];

                if ($teacherId > 0) {
                    $keys[] = "teacher:{$teacherId}";
                }
                if ($room !== null) {
                    $keys[] = 'room:'.hash('sha256', $room);
                }

                return $keys;
            })
            ->unique()
            ->sort()
            ->values();

        $now = now();
        DB::table('schedule_resource_locks')->insertOrIgnore(
            $keys->map(fn (string $key) => [
                'resource_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        DB::table('schedule_resource_locks')
            ->whereIn('resource_key', $keys->all())
            ->orderBy('resource_key')
            ->lockForUpdate()
            ->get();
    }

    private function normalizeRoom(mixed $room): ?string
    {
        $room = mb_strtolower((string) preg_replace('/\s+/', ' ', trim((string) $room)));

        return $room !== '' ? $room : null;
    }
}
