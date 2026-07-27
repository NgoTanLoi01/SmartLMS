<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\QuestionDifficultyAnalyticsService;
use Illuminate\Console\Command;

class RecalculateQuestionDifficulty extends Command
{
    protected $signature = 'questions:recalculate-difficulty {--course= : Chỉ tính câu hỏi của một khóa học}';

    protected $description = 'Tính lại độ khó thực tế của câu hỏi từ dữ liệu bài thi đã nộp';

    public function handle(QuestionDifficultyAnalyticsService $analytics): int
    {
        $query = Question::query()->notArchived()->orderBy('id');
        if ($this->option('course')) {
            $query->where('course_id', (int) $this->option('course'));
        }

        $processed = 0;
        $query->select('id')->chunkById(200, function ($questions) use ($analytics, &$processed) {
            $analytics->refreshForQuestionIds($questions->pluck('id'));
            $processed += $questions->count();
            $this->output->write('.');
        });

        $this->newLine();
        $this->info("Đã cập nhật thống kê độ khó cho {$processed} câu hỏi.");

        return self::SUCCESS;
    }
}
