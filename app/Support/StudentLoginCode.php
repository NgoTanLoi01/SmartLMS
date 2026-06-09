<?php

namespace App\Support;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Str;

class StudentLoginCode
{
    public static function generateForClass(Classroom $classroom): string
    {
        return self::generate($classroom->code ?: 'HS', $classroom->students()->count() + 1);
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
}
