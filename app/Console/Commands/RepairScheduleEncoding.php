<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Support\Utf8MojibakeRepair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairScheduleEncoding extends Command
{
    protected $signature = 'smartlms:repair-schedule-encoding
        {--apply : Ghi các chuỗi đã sửa vào database; mặc định chỉ dry-run}';

    protected $description = 'Phát hiện và sửa phòng học, ghi chú lịch cũ bị double-encode UTF-8';

    public function handle(Utf8MojibakeRepair $repairer): int
    {
        $candidates = [];

        Schedule::query()
            ->select(['id', 'room', 'note'])
            ->orderBy('id')
            ->chunkById(200, function ($schedules) use (&$candidates, $repairer): void {
                foreach ($schedules as $schedule) {
                    $room = $schedule->room === null
                        ? null
                        : ($repairer->repair($schedule->room) ?? $schedule->room);
                    $note = $schedule->note === null
                        ? null
                        : ($repairer->repair($schedule->note) ?? $schedule->note);

                    if ($room === $schedule->room && $note === $schedule->note) {
                        continue;
                    }

                    $candidates[] = [
                        'id' => $schedule->id,
                        'before_room' => $schedule->room,
                        'after_room' => $room,
                        'before_note' => $schedule->note,
                        'after_note' => $note,
                    ];
                }
            });

        if ($candidates === []) {
            $this->info('Không phát hiện dữ liệu lịch bị lỗi encoding.');

            return self::SUCCESS;
        }

        $roomCount = count(array_filter($candidates, fn (array $row): bool => $row['before_room'] !== $row['after_room']));
        $noteCount = count(array_filter($candidates, fn (array $row): bool => $row['before_note'] !== $row['after_note']));

        $this->table(['Lịch cần sửa', 'Phòng học cần sửa', 'Ghi chú cần sửa'], [[count($candidates), $roomCount, $noteCount]]);
        $this->table(
            ['Số lượng', 'Phòng học trước', 'Phòng học sau'],
            collect($candidates)
                ->filter(fn (array $row): bool => $row['before_room'] !== $row['after_room'])
                ->groupBy(fn (array $row): string => $row['before_room']."\0".$row['after_room'])
                ->map(fn ($rows): array => [
                    $rows->count(),
                    $rows->first()['before_room'],
                    $rows->first()['after_room'],
                ])
                ->values()
                ->all()
        );

        if (! $this->option('apply')) {
            $this->warn('Dry-run: chưa thay đổi dữ liệu. Chạy lại với --apply sau khi backup.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($candidates, &$updated, &$skipped): void {
            foreach ($candidates as $candidate) {
                $changed = Schedule::query()
                    ->whereKey($candidate['id'])
                    ->where('room', $candidate['before_room'])
                    ->where('note', $candidate['before_note'])
                    ->update([
                        'room' => $candidate['after_room'],
                        'note' => $candidate['after_note'],
                    ]);

                if ($changed !== 1) {
                    $skipped++;

                    continue;
                }

                $updated++;
                Log::notice('Đã sửa encoding dữ liệu lịch.', [
                    'schedule_id' => $candidate['id'],
                    'before_room' => $candidate['before_room'],
                    'after_room' => $candidate['after_room'],
                    'before_note' => $candidate['before_note'],
                    'after_note' => $candidate['after_note'],
                ]);
            }
        });

        $this->info("Đã sửa {$updated} lịch; bỏ qua {$skipped} lịch đã thay đổi đồng thời.");

        return $skipped === 0 ? self::SUCCESS : self::FAILURE;
    }
}
