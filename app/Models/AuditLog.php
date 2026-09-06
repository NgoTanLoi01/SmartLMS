<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use LogicException;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'actor_name',
        'actor_email',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'chain_position',
        'previous_hash',
        'entry_hash',
        'integrity_version',
        'archived_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'archived_at' => 'datetime',
        'chain_position' => 'integer',
        'integrity_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log): void {
            if (Schema::hasColumn($log->getTable(), 'entry_hash') && blank($log->entry_hash)) {
                throw new LogicException('Audit log phải được tạo qua AuditLogger để có chữ ký toàn vẹn.');
            }
        });

        static::updating(function (): never {
            throw new LogicException('Audit log là dữ liệu append-only và không được phép cập nhật.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit log là dữ liệu append-only và không được phép xóa.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->whereNull($query->getModel()->qualifyColumn('archived_at'));
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull($query->getModel()->qualifyColumn('archived_at'));
    }
}
