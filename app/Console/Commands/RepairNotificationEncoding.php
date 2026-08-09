<?php

namespace App\Console\Commands;

use App\Models\SmartNotification;
use App\Support\Utf8MojibakeRepair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairNotificationEncoding extends Command
{
    protected $signature = 'smartlms:repair-notification-encoding
        {--apply : Ghi các chuỗi đã sửa vào database; mặc định chỉ dry-run}';

    protected $description = 'Phát hiện và sửa tiêu đề, nội dung thông báo cũ bị double-encode UTF-8';

    public function handle(Utf8MojibakeRepair $repairer): int
    {
        $candidates = [];

        SmartNotification::query()
            ->select(['id', 'user_id', 'title', 'message'])
            ->orderBy('id')
            ->chunkById(200, function ($notifications) use (&$candidates, $repairer): void {
                foreach ($notifications as $notification) {
                    $title = $repairer->repair($notification->title) ?? $notification->title;
                    $message = $repairer->repair($notification->message) ?? $notification->message;

                    if ($title === $notification->title && $message === $notification->message) {
                        continue;
                    }

                    $candidates[] = [
                        'id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'before_title' => $notification->title,
                        'after_title' => $title,
                        'before_message' => $notification->message,
                        'after_message' => $message,
                    ];
                }
            });

        if ($candidates === []) {
            $this->info('Không phát hiện dữ liệu thông báo bị lỗi encoding.');

            return self::SUCCESS;
        }

        $titleCount = count(array_filter($candidates, fn (array $row): bool => $row['before_title'] !== $row['after_title']));
        $messageCount = count(array_filter($candidates, fn (array $row): bool => $row['before_message'] !== $row['after_message']));

        $this->table(['Thông báo', 'Tiêu đề cần sửa', 'Nội dung cần sửa'], [[count($candidates), $titleCount, $messageCount]]);
        $this->table(
            ['Số lượng', 'Tiêu đề trước', 'Tiêu đề sau'],
            collect($candidates)
                ->filter(fn (array $row): bool => $row['before_title'] !== $row['after_title'])
                ->groupBy(fn (array $row): string => $row['before_title']."\0".$row['after_title'])
                ->map(fn ($rows): array => [
                    $rows->count(),
                    $rows->first()['before_title'],
                    $rows->first()['after_title'],
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
                $changed = SmartNotification::query()
                    ->whereKey($candidate['id'])
                    ->where('title', $candidate['before_title'])
                    ->where('message', $candidate['before_message'])
                    ->update([
                        'title' => $candidate['after_title'],
                        'message' => $candidate['after_message'],
                    ]);

                if ($changed !== 1) {
                    $skipped++;

                    continue;
                }

                $updated++;
                Log::notice('Đã sửa encoding dữ liệu thông báo.', [
                    'notification_id' => $candidate['id'],
                    'user_id' => $candidate['user_id'],
                    'before_title' => $candidate['before_title'],
                    'after_title' => $candidate['after_title'],
                    'before_message' => $candidate['before_message'],
                    'after_message' => $candidate['after_message'],
                ]);
            }
        });

        $this->info("Đã sửa {$updated} thông báo; bỏ qua {$skipped} thông báo đã thay đổi đồng thời.");

        return $skipped === 0 ? self::SUCCESS : self::FAILURE;
    }
}
