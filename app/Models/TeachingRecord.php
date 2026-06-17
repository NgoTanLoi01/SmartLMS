<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingRecord extends Model
{
    public const STATUS_TEACHING = 'teaching';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'teacher_id',
        'course_id',
        'class_id',
        'subject_name',
        'class_name',
        'center_name',
        'term_code',
        'planned_sessions',
        'start_date',
        'end_date',
        'status',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'planned_sessions' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_TEACHING => 'Đang dạy',
            self::STATUS_COMPLETED => 'Đã hoàn thành',
            self::STATUS_PAUSED => 'Tạm hoãn',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
