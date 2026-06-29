<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use ZipArchive;

class SubmissionTextExtractor
{
    private const TEXT_LIMIT = 20000;

    public function extract(AssignmentSubmission $submission): array
    {
        if (!$submission->file_path) {
            return [
                'success' => false,
                'text' => '',
                'source' => null,
                'message' => 'Bài nộp không có file đính kèm.',
            ];
        }

        $diskName = $submission->file_disk ?: 'public';
        $disk = Storage::disk($diskName);

        if (!$disk->exists($submission->file_path)) {
            return [
                'success' => false,
                'text' => '',
                'source' => null,
                'message' => 'Không tìm thấy file bài nộp.',
            ];
        }

        $name = $submission->original_filename ?: basename($submission->file_path);
        $extension = Str::lower(pathinfo($name, PATHINFO_EXTENSION));

        try {
            $content = $disk->get($submission->file_path);
            $text = $this->extractTextFromContent($content, $extension);
            $text = Str::limit($this->plainText($text), self::TEXT_LIMIT, '');

            if ($text === '') {
                return [
                    'success' => false,
                    'text' => '',
                    'source' => $name,
                    'message' => 'AI chưa đọc được nội dung văn bản từ file này. Nếu là PDF scan/ảnh, cần OCR ở giai đoạn sau.',
                ];
            }

            return [
                'success' => true,
                'text' => $text,
                'source' => $name,
                'message' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Không đọc được text từ file bài nộp', [
                'submission_id' => $submission->id,
                'disk' => $diskName,
                'path' => $submission->file_path,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'text' => '',
                'source' => $name,
                'message' => 'Không đọc được nội dung file bài nộp.',
            ];
        }
    }

    private function extractTextFromContent(string $content, string $extension): string
    {
        return match ($extension) {
            'txt', 'md', 'css', 'js', 'php' => $content,
            'html', 'htm' => strip_tags($content),
            'pdf' => $this->extractPdfText($content),
            'docx' => $this->extractDocxText($content),
            default => '',
        };
    }

    private function extractPdfText(string $content): string
    {
        try {
            return (new Parser())->parseContent($content)->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    private function extractDocxText(string $content): string
    {
        if (!class_exists(ZipArchive::class)) {
            return '';
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'smartlms-submission-docx-');

        if (!$tempPath) {
            return '';
        }

        try {
            file_put_contents($tempPath, $content);

            $zip = new ZipArchive();
            if ($zip->open($tempPath) !== true) {
                return '';
            }

            $xml = $zip->getFromName('word/document.xml') ?: '';
            $zip->close();

            if ($xml === '') {
                return '';
            }

            $xml = preg_replace('/<\/w:p>/', "\n", $xml);

            return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        } finally {
            @unlink($tempPath);
        }
    }

    private function plainText(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: '';
        }

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }
}
