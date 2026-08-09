<?php

namespace App\Console\Commands;

use App\Models\AttendanceColumn;
use App\Models\AttendanceData;
use App\Support\Utf8MojibakeRepair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairAttendanceEncoding extends Command
{
    protected $signature = 'smartlms:repair-attendance-encoding
        {--apply : Ghi các chuỗi đã sửa vào database; mặc định chỉ dry-run}';

    protected $description = 'Phát hiện và sửa tiêu đề, nội dung ghi chú điểm danh cũ bị double-encode UTF-8';

    public function handle(Utf8MojibakeRepair $repairer): int
    {
        $apply = (bool) $this->option('apply');
        $candidates = [];

        AttendanceColumn::query()
            ->select(['id', 'course_id', 'name'])
            ->orderBy('id')
            ->chunkById(200, function ($columns) use (&$candidates, $repairer): void {
                foreach ($columns as $column) {
                    $repaired = $repairer->repair($column->name);
                    if ($repaired !== null && $repaired !== $column->name) {
                        $candidates[] = [
                            'source' => 'column_name',
                            'id' => $column->id,
                            'course_id' => $column->course_id,
                            'before' => $column->name,
                            'after' => $repaired,
                        ];
                    }
                }
            });

        AttendanceData::query()
            ->select([
                'attendance_data.id',
                'attendance_data.attendance_column_id',
                'attendance_data.value',
                'attendance_columns.course_id',
                'attendance_columns.type as column_type',
            ])
            ->join('attendance_columns', 'attendance_columns.id', '=', 'attendance_data.attendance_column_id')
            ->whereNotNull('attendance_data.value')
            ->orderBy('attendance_data.id')
            ->chunkById(200, function ($rows) use (&$candidates, $repairer): void {
                foreach ($rows as $row) {
                    $repaired = $repairer->repair($row->value);
                    if ($repaired !== null && $repaired !== $row->value) {
                        $candidates[] = [
                            'source' => $row->column_type.'_value',
                            'id' => $row->id,
                            'course_id' => $row->course_id,
                            'before' => $row->value,
                            'after' => $repaired,
                        ];
                    }
                }
            }, 'attendance_data.id', 'id');

        if ($candidates === []) {
            $this->info('Không phát hiện dữ liệu điểm danh bị lỗi encoding.');

            return self::SUCCESS;
        }

        $this->table(
            ['Loại dữ liệu', 'ID', 'Khóa học', 'Trước', 'Sau'],
            array_map(fn (array $row): array => [
                match ($row['source']) {
                    'column_name' => 'Tên cột',
                    'note_value' => 'Nội dung ghi chú',
                    'grade_value' => 'Giá trị điểm',
                    default => 'Giá trị điểm danh',
                },
                $row['id'],
                $row['course_id'] ?? '-',
                $row['before'],
                $row['after'],
            ], $candidates)
        );

        if (! $apply) {
            $this->warn('Dry-run: phát hiện '.count($candidates).' bản ghi; chưa thay đổi dữ liệu. Chạy lại với --apply sau khi backup.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($candidates, &$updated, &$skipped): void {
            foreach ($candidates as $candidate) {
                $changed = $candidate['source'] === 'column_name'
                    ? AttendanceColumn::query()
                        ->whereKey($candidate['id'])
                        ->where('name', $candidate['before'])
                        ->update(['name' => $candidate['after']])
                    : AttendanceData::query()
                        ->whereKey($candidate['id'])
                        ->where('value', $candidate['before'])
                        ->update(['value' => $candidate['after']]);

                if ($changed !== 1) {
                    $skipped++;

                    continue;
                }

                $updated++;
                Log::notice('Đã sửa encoding dữ liệu điểm danh.', [
                    'source' => $candidate['source'],
                    'record_id' => $candidate['id'],
                    'course_id' => $candidate['course_id'],
                    'before' => $candidate['before'],
                    'after' => $candidate['after'],
                ]);
            }
        });

        $this->info("Đã sửa {$updated} bản ghi; bỏ qua {$skipped} bản ghi đã thay đổi đồng thời.");

        return $skipped === 0 ? self::SUCCESS : self::FAILURE;
    }
}
