<?php

namespace App\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

class AuditHash
{
    public const VERSION = 1;

    private const SIGNED_FIELDS = [
        'chain_position',
        'previous_hash',
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
        'created_at',
        'integrity_version',
    ];

    public static function digest(array $attributes): string
    {
        $payload = [];

        foreach (self::SIGNED_FIELDS as $field) {
            $value = $attributes[$field] ?? null;
            if (in_array($field, ['old_values', 'new_values', 'metadata'], true)) {
                $value = self::decodeJson($value);
            }
            if ($field === 'created_at' && $value !== null) {
                $value = Carbon::parse($value)->format('Y-m-d H:i:s');
            }

            $payload[$field] = self::canonicalize($value);
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $encoded, self::signingKey());
    }

    private static function signingKey(): string
    {
        $key = (string) config('audit.signing_key', config('app.key'));
        if ($key === '') {
            throw new RuntimeException('Chưa cấu hình khóa ký audit log.');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || $decoded === '') {
                throw new RuntimeException('Khóa ký audit log dạng base64 không hợp lệ.');
            }

            return $decoded;
        }

        return $key;
    }

    private static function decodeJson(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private static function canonicalize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize($item);
        }

        return $value;
    }
}
