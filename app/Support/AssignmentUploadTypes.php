<?php

namespace App\Support;

use InvalidArgumentException;

final class AssignmentUploadTypes
{
    public const DEFAULT = 'pdf,docx,txt,md,html,htm,css,js,png,jpg,jpeg,ppt,pptx,xls,xlsx,zip,rar';

    private const GROUPS = [
        'document' => [
            'label' => 'Văn bản',
            'extensions' => [
                'pdf' => 'PDF',
                'doc' => 'Word DOC',
                'docx' => 'Word DOCX',
                'txt' => 'Văn bản TXT',
                'md' => 'Markdown',
            ],
        ],
        'presentation' => [
            'label' => 'Trình chiếu',
            'extensions' => [
                'ppt' => 'PowerPoint PPT',
                'pptx' => 'PowerPoint PPTX',
            ],
        ],
        'spreadsheet' => [
            'label' => 'Bảng tính',
            'extensions' => [
                'xls' => 'Excel XLS',
                'xlsx' => 'Excel XLSX',
            ],
        ],
        'web' => [
            'label' => 'Web và mã nguồn',
            'extensions' => [
                'html' => 'HTML',
                'htm' => 'HTM',
                'css' => 'CSS',
                'js' => 'JavaScript',
            ],
        ],
        'image' => [
            'label' => 'Hình ảnh',
            'extensions' => [
                'png' => 'PNG',
                'jpg' => 'JPG',
                'jpeg' => 'JPEG',
                'gif' => 'GIF',
                'webp' => 'WebP',
            ],
        ],
        'archive' => [
            'label' => 'Tệp nén',
            'extensions' => [
                'zip' => 'ZIP',
                'rar' => 'RAR',
            ],
        ],
    ];

    /** @return array<string, array{label: string, extensions: array<string, string>}> */
    public static function groups(): array
    {
        return self::GROUPS;
    }

    /** @return list<string> */
    public static function defaultExtensions(): array
    {
        return self::parse(self::DEFAULT);
    }

    public static function normalize(string|array|null $extensions): string
    {
        $requested = self::parse($extensions ?: self::DEFAULT);
        $unsupported = array_values(array_diff($requested, self::allowedExtensions()));

        if ($unsupported !== []) {
            throw new InvalidArgumentException('Định dạng không được phép: '.implode(', ', $unsupported).'.');
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
        return array_values(array_intersect(self::parse($extensions ?: self::DEFAULT), self::allowedExtensions()));
    }

    /** @return list<string> */
    private static function allowedExtensions(): array
    {
        $groups = array_values(array_map(
            static fn (array $group): array => array_keys($group['extensions']),
            self::GROUPS
        ));

        return array_values(array_merge(...$groups));
    }

    /** @return list<string> */
    private static function parse(string|array $extensions): array
    {
        $values = is_array($extensions) ? $extensions : explode(',', $extensions);
        $normalized = array_map(
            static fn (mixed $extension): string => strtolower(ltrim(trim((string) $extension), '.')),
            $values
        );

        return array_values(array_unique(array_filter($normalized)));
    }
}
