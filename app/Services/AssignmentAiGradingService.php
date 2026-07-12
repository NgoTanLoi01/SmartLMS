<?php

namespace App\Services;

use App\Models\AssignmentSubmission;

class AssignmentAiGradingService
{
    public function __construct(
        private DeepSeekService $deepSeek,
        private SubmissionTextExtractor $textExtractor,
    ) {}

    public function analyze(AssignmentSubmission $submission): array
    {
        $submission->loadMissing(['assignment.course', 'user']);
        $assignment = $submission->assignment;
        if (!$assignment->ai_grading_enabled) {
            throw new \RuntimeException('AI hỗ trợ chấm đang tắt cho bài tập này.');
        }

        $fileResult = $submission->file_path ? $this->textExtractor->extract($submission) : ['text' => '', 'source' => null, 'message' => null];
        $textAnswer = trim((string) $submission->text_answer);
        $fileText = trim((string) ($fileResult['text'] ?? ''));
        if ($textAnswer === '' && $fileText === '') {
            throw new \RuntimeException($fileResult['message'] ?: 'AI chưa có nội dung văn bản để phân tích bài nộp này.');
        }

        $combined = trim(implode("\n\n", array_filter([
            $textAnswer !== '' ? "Nội dung tự luận học sinh nhập:\n{$textAnswer}" : null,
            $fileText !== '' ? "Nội dung trích xuất từ file {$fileResult['source']}:\n{$fileText}" : null,
        ])));

        $result = $this->deepSeek->analyzeAssignmentSubmission([
            'assignment' => [
                'title' => $assignment->title,
                'type' => $assignment->type ?? 'file',
                'instructions' => trim(strip_tags($assignment->instructions)),
                'grading_rubric' => trim((string) $assignment->grading_rubric),
                'grading_scale' => $assignment->grading_scale ?? 10,
                'due_date' => $assignment->due_date?->format('d/m/Y H:i'),
                'course' => $assignment->course->title,
            ],
            'student' => [
                'name' => $submission->user?->name,
                'email' => $submission->user?->email,
                'submitted_at' => $submission->formatSubmittedAt('d/m/Y H:i:s'),
            ],
            'submission' => [
                'text_answer' => $combined,
                'has_file' => !empty($submission->file_path),
                'file_text_extracted' => $fileText !== '',
                'file_text_source' => $fileResult['source'] ?? null,
                'current_grade' => $submission->grade,
                'current_feedback' => $submission->feedback,
            ],
        ]);
        if (!($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'AI chưa phân tích được bài làm.');
        }

        $analysis = $result['analysis'] ?? [];
        $history = collect($submission->ai_analysis_history ?? [])->prepend([
            'analyzed_at' => now()->toDateTimeString(),
            'suggested_score' => $analysis['suggested_score'] ?? null,
            'feedback' => $analysis['feedback'] ?? null,
            'rubric_breakdown' => $analysis['rubric_breakdown'] ?? [],
            'strengths' => $analysis['strengths'] ?? [],
            'improvements' => $analysis['improvements'] ?? [],
            'review_flags' => $analysis['review_flags'] ?? [],
            'grading_notes' => $analysis['grading_notes'] ?? null,
        ])->take(10)->values()->all();

        $submission->update([
            'ai_suggested_score' => $analysis['suggested_score'] ?? null,
            'ai_feedback' => $analysis['feedback'] ?? null,
            'ai_rubric_breakdown' => $analysis['rubric_breakdown'] ?? null,
            'ai_review_flags' => $analysis['review_flags'] ?? null,
            'ai_grading_notes' => $analysis['grading_notes'] ?? null,
            'ai_analyzed_at' => now(),
            'ai_analysis_history' => $history,
        ]);
        $analysis['analysis_history_count'] = count($history);

        return ['analysis' => $analysis, 'usage' => $result['_usage'] ?? []];
    }
}
