<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingContract extends Model
{
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'teacher_id',
        'contract_number',
        'signed_date',
        'total_amount',
        'received_amount',
        'status',
        'received_date',
        'evidence_url',
        'note',
    ];

    protected $casts = [
        'signed_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_UNPAID => 'Chưa nhận',
            self::STATUS_PARTIAL => 'Nhận một phần',
            self::STATUS_RECEIVED => 'Đã nhận',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_ARCHIVED => 'Đã lưu trữ',
        ];
    }

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable().'.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhereNotIn($statusColumn, [self::STATUS_CANCELLED, self::STATUS_ARCHIVED]);
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teachingRecords()
    {
        return $this->belongsToMany(TeachingRecord::class, 'teaching_contract_record')
            ->withTimestamps();
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->received_amount);
    }
}
