<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Services\GradebookMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiscoverGradebookCommand extends Command
{
    protected $signature = 'smartlms:gradebook-discover {--course= : Course ID bắt buộc} {--output= : File manifest JSON đích}';

    protected $description = 'Khám phá nguồn điểm legacy và tạo manifest Gradebook chưa được duyệt';

    public function handle(GradebookMigrationService $service): int
    {
        $courseId = (int) $this->option('course');
        if ($courseId <= 0) {
            $this->error('--course là bắt buộc.');

            return self::INVALID;
        }

        $course = Course::findOrFail($courseId);
        $manifest = $service->discover($course);
        $path = $this->option('output') ?: storage_path("app/gradebook/course-{$course->id}-manifest.json");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('Đã tạo manifest discovery; approved=false, chưa có dữ liệu Gradebook được ghi.');
        $this->line("Manifest: {$path}");
        $this->line('Legacy columns: '.count($manifest['discovery']['legacy_grade_columns']));
        $this->line('Assignments: '.count($manifest['discovery']['assignments']));
        $this->line('Quizzes: '.count($manifest['discovery']['quizzes']));

        return self::SUCCESS;
    }
}
