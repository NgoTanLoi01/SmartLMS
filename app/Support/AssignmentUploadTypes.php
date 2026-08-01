<?php

namespace App\Support;

use InvalidArgumentException;

final class AssignmentUploadTypes
{
    public const DEFAULT = 'pdf,docx,txt,md,html,htm,css,js,png,jpg,jpeg';

    private const ALLOWED = [
        'pdf', 'doc', 'docx', 'zip',
        'txt', 'md', 'html', 'htm', 'css', 'js',
        'png', 'jpg', 'jpeg', 'gif', 'webp',
    ];

    public static function normalize(?string $extensions): string
    {
        $requested = self::parse($extensions ?: self::DEFAULT);
        $unsupported = array_values(array_diff($requested, self::ALLOWED));

        if ($unsupported !== []) {
            throw new InvalidArgumentException(
                'Định dạng không được phép: '.implode(', ', $unsupported).'.'
            );
        }

        if ($requested === []) {
            throw new InvalidArgumentException('Phải có ít nhất một định dạng tệp được phép.');
        }

        return implode(',', $requested);
    }

    /**
     * Return only server-approved extensions for legacy assignment records.
     *
     * @return list<string>
     */
    public static function safeExtensions(?string $extensions): array
    {
        return array_values(array_intersect(
            self::parse($extensions ?: self::DEFAULT),
            self::ALLOWED
        ));
    }

    /** @return list<string> */
    private static function parse(string $extensions): array
    {
        $normalized = array_map(
            static fn (string $extension) => strtolower(ltrim(trim($extension), '.')),
            explode(',', $extensions)
        );

        return array_values(array_unique(array_filter($normalized)));
    }
}
