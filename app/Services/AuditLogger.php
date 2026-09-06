<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Throwable;

class AuditLogger
{
    public const GRADE_UPDATED = 'grade_updated';

    public const GRADES_IMPORTED = 'grades_imported';

    public const GRADES_BULK_STATUS_UPDATED = 'grades_bulk_status_updated';

    public const AI_ASSIGNMENT_ANALYZED = 'ai_assignment_analyzed';

    public const AI_LEARNING_ANALYZED = 'ai_learning_analyzed';

    public const STUDENTS_IMPORTED = 'students_imported';

    public const SCHEDULE_CREATED = 'schedule_created';

    public const SCHEDULE_UPDATED = 'schedule_updated';

    public const SCHEDULE_ARCHIVED = 'schedule_archived';

    public const SCHEDULE_COPIED = 'schedule_copied';

    public const SCHEDULE_IMPORTED = 'schedule_imported';

    public const SCHEDULE_SERIES_CREATED = 'schedule_series_created';

    public const SCHEDULE_SERIES_UPDATED = 'schedule_series_updated';

    public const SCHEDULE_SERIES_ARCHIVED = 'schedule_series_archived';

    public const CONTRACT_CREATED = 'contract_created';

    public const CONTRACT_UPDATED = 'contract_updated';

    public const CONTRACT_ARCHIVED = 'contract_archived';

    public const CONTRACT_IMPORTED = 'contract_imported';

    public const ACCOUNT_LIFECYCLE_UPDATED = 'account_lifecycle_updated';

    public const ACCOUNT_PROFILE_UPDATED = 'account_profile_updated';

    public const CONTENT_CLONED = 'content_cloned';

    public const QUESTIONS_IMPORTED = 'questions_imported';

    public const QUESTIONS_BULK_UPDATED = 'questions_bulk_updated';

    public const TRASH_RESTORED = 'trash_restored';

    public const TRASH_PERMANENTLY_DELETED = 'trash_permanently_deleted';

    public const AUDIT_LOGS_ARCHIVED = 'audit_logs_archived';

    public const AUDIT_INTEGRITY_VERIFIED = 'audit_integrity_verified';

    public const AUDIT_INTEGRITY_FAILED = 'audit_integrity_failed';

    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $metadata = [],
        ?string $description = null
    ): void {
        try {
            $request = app()->bound('request') ? request() : null;
            $actor = $request?->user() ?? auth()->user();

            app(AuditIntegrityService::class)->append([
                'user_id' => $actor?->id,
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_email' => $actor?->email,
                'action' => $action,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'description' => $description,
                'old_values' => self::clean($oldValues),
                'new_values' => self::clean($newValues),
                'metadata' => self::clean($metadata),
                'ip_address' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 1000),
            ]);
        } catch (Throwable $e) {
            app(AuditFailureReporter::class)->report($action, $e);
        }
    }

    public static function snapshot(Model $model, array $only = []): array
    {
        $attributes = $model->getAttributes();

        return self::clean($only ? Arr::only($attributes, $only) : $attributes) ?? [];
    }

    public static function sanitize(mixed $value, string|int|null $key = null): mixed
    {
        if ($key !== null && self::isSensitiveKey((string) $key)) {
            return '[REDACTED]';
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Enumerable) {
            $value = $value->all();
        } elseif (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $cleaned = [];
            foreach ($value as $nestedKey => $nestedValue) {
                $cleaned[$nestedKey] = self::sanitize($nestedValue, $nestedKey);
            }

            return $cleaned;
        }

        return (string) $value;
    }

    private static function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return self::sanitize($values);
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $key) ?? $key);

        foreach ([
            'password', 'passwd', 'pwd', 'token', 'remember_token', 'access_token', 'refresh_token',
            'api_token', 'api_key', 'authorization', 'cookie', 'secret', 'client_secret',
            'csrf_token', 'xsrf_token',
        ] as $sensitive) {
            if ($normalized === $sensitive
                || str_ends_with($normalized, '_'.$sensitive)
                || str_starts_with($normalized, $sensitive.'_')) {
                return true;
            }
        }

        return false;
    }
}
