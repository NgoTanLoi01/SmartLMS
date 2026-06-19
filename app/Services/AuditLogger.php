<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public const GRADE_UPDATED = 'grade_updated';
    public const AI_ASSIGNMENT_ANALYZED = 'ai_assignment_analyzed';
    public const AI_LEARNING_ANALYZED = 'ai_learning_analyzed';
    public const STUDENTS_IMPORTED = 'students_imported';
    public const SCHEDULE_UPDATED = 'schedule_updated';
    public const SCHEDULE_ARCHIVED = 'schedule_archived';
    public const SCHEDULE_COPIED = 'schedule_copied';
    public const SCHEDULE_IMPORTED = 'schedule_imported';
    public const CONTRACT_CREATED = 'contract_created';
    public const CONTRACT_UPDATED = 'contract_updated';
    public const CONTRACT_ARCHIVED = 'contract_archived';
    public const CONTRACT_IMPORTED = 'contract_imported';

    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $metadata = [],
        ?string $description = null
    ): void {
        try {
            $request = request();

            AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => $action,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'description' => $description,
                'old_values' => self::clean($oldValues),
                'new_values' => self::clean($newValues),
                'metadata' => self::clean($metadata),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Không thể ghi audit log', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function snapshot(Model $model, array $only = []): array
    {
        $attributes = $model->getAttributes();

        return self::clean($only ? Arr::only($attributes, $only) : $attributes) ?? [];
    }

    private static function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return collect($values)
            ->reject(fn ($value, $key) => in_array($key, ['password', 'remember_token'], true))
            ->map(function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                }

                if ($value instanceof \BackedEnum) {
                    return $value->value;
                }

                return $value;
            })
            ->all();
    }
}
