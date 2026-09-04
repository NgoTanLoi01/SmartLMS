<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    public const GRADING_PENDING = 'pending';

    public const GRADING_DRAFT = 'draft';

    public const GRADING_PUBLISHED = 'published';

    private const DISPLAY_TIMEZONE = 'Asia/Ho_Chi_Minh';

    protected $fillable = ['assignment_id', 'user_id', 'file_path', 'file_disk', 'original_filename', 'mime_type', 'file_size', 'checksum_sha256', 'text_answer', 'grade', 'feedback', 'grading_status', 'rubric_scores', 'grade_published_at', 'ai_suggested_score', 'ai_feedback', 'ai_rubric_breakdown', 'ai_review_flags', 'ai_grading_notes', 'ai_analyzed_at', 'ai_analysis_history', 'submitted_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
        'rubric_scores' => 'array',
        'grade_published_at' => 'datetime',
        'ai_suggested_score' => 'decimal:2',
        'ai_rubric_breakdown' => 'array',
        'ai_review_flags' => 'array',
        'ai_analysis_history' => 'array',
        'ai_analyzed_at' => 'datetime',
    ];

    public function scopePublishedGrades($query)
    {
        return $query->where('grading_status', self::GRADING_PUBLISHED)->whereNotNull('grade');
    }

    public function isGradePublished(): bool
    {
        return $this->grade !== null && $this->grading_status === self::GRADING_PUBLISHED;
    }

    public function assignment()
    {
        return $this->belongsTo(Assignments::class, 'assignment_id');
    }

    public function submittedAtLocal()
    {
        return $this->submitted_at?->copy()->timezone(self::DISPLAY_TIMEZONE);
    }

    public function formatSubmittedAt(string $format = 'd/m/Y H:i:s'): ?string
    {
        return $this->submittedAtLocal()?->format($format);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
