<?php

namespace App\Support;

use Illuminate\Support\Str;

final class AttendanceStatus
{
    public static function normalize(mixed $value): string
    {
        $normalized = Str::of((string) $value)->lower()->ascii()->trim()->toString();

        if (in_array($normalized, ['absent', 'v', 'vang', 'nghi', '0', 'no', 'false'], true)) {
            return 'absent';
        }
        if (in_array($normalized, ['late', 'muon', 'di muon'], true)) {
            return 'late';
        }
        if (in_array($normalized, ['excused', 'phep', 'co phep', 'vang co phep'], true)) {
            return 'excused';
        }

        return 'present';
    }

    public static function isAbsent(mixed $value): bool
    {
        $normalized = Str::of((string) $value)->lower()->ascii()->trim()->toString();

        return in_array($normalized, ['0', 'no', 'false', 'abs', 'absent', 'v', 'vang', 'nghi'], true)
            || str_contains(Str::lower((string) $value), 'vắng')
            || str_contains(Str::lower((string) $value), 'nghỉ');
    }
}
