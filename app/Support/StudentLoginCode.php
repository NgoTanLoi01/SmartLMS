<?php

namespace App\Support;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Str;

class StudentLoginCode
{
    public static function generateForClass(Classroom $classroom): string
    {
        return self::generate('HS', $classroom->students()->count() + 1);
    }

    public static function generateFromName(string $name, ?string $studentCode = null): string
    {
        $base = self::normalizeName($name);

        if (!User::where('username', $base)->exists()) {
            return $base;
        }

        $normalizedStudentCode = self::normalizeStudentCode($studentCode);
        if ($normalizedStudentCode) {
            $withCode = $base . '-' . $normalizedStudentCode;
            if (!User::where('username', $withCode)->exists()) {
                return $withCode;
            }
        }

        $sequence = 2;
        do {
            $code = sprintf('%s-%02d', $base, $sequence);
            $sequence++;
        } while (User::where('username', $code)->exists());

        return $code;
    }

    public static function generate(string $prefix, int $startSequence = 1): string
    {
        $base = self::normalizePrefix($prefix);
        $sequence = max($startSequence, 1);

        do {
            $code = sprintf('%s-%02d', $base, $sequence);
            $sequence++;
        } while (User::where('username', $code)->exists());

        return $code;
    }

    public static function emailFromUsername(string $username): string
    {
        $localPart = Str::lower(Str::replace('-', '.', $username));

        return $localPart . '@student.smartlms.local';
    }

    private static function normalizePrefix(string $prefix): string
    {
        $normalized = Str::upper(Str::slug($prefix, ''));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?: 'HS';

        return Str::limit($normalized, 12, '');
    }

    public static function normalizeStudentCode(?string $studentCode): ?string
    {
        if (!$studentCode) {
            return null;
        }

        $normalized = Str::lower(Str::slug($studentCode, ''));
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);

        return $normalized ?: null;
    }

    private static function normalizeName(string $name): string
    {
        $normalized = Str::lower(Str::slug($name, ''));
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);

        return $normalized ?: 'hocsinh';
    }
}
