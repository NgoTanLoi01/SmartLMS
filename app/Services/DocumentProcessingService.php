<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;

class DocumentProcessingService
{
    public function __construct(private GeminiEmbeddingService $embeddingService) {}

    public function processAndStorePdf(string $filePath, string $documentName, ?int $courseId = null, ?int $uploadedBy = null): array
    {
        $extraction = $this->extractPages($filePath);
        $chunks = $this->chunkPages($extraction['pages']);
        if ($chunks === []) {
            throw new \RuntimeException('PDF không có nội dung có thể lập chỉ mục.');
        }

        $connection = DB::connection('pgsql');
        $ingestionId = (string) Str::uuid();

        try {
            foreach ($chunks as $index => $chunk) {
                $embedding = $this->embeddingService->embed($chunk['content']);
                $connection->table('document_chunks')->insert([
                    'course_id' => $courseId,
                    'uploaded_by' => $uploadedBy,
                    'document_name' => $documentName,
                    'content' => $chunk['content'],
                    'embedding' => DB::raw("'[".implode(',', $embedding)."]'::vector"),
                    'ingestion_id' => $ingestionId,
                    'chunk_index' => $index,
                    'page_number' => $chunk['page_number'],
                    'content_hash' => hash('sha256', $chunk['content']),
                    'is_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $connection->transaction(function () use ($connection, $courseId, $uploadedBy, $documentName, $ingestionId) {
                $documentIdentity = json_encode([$courseId, $uploadedBy, $documentName], JSON_UNESCAPED_UNICODE) ?: $documentName;
                $connection->selectOne(
                    'SELECT pg_advisory_xact_lock(hashtextextended(?, 0))',
                    [$documentIdentity],
                );

                $this->documentQuery($connection, $courseId, $uploadedBy, $documentName)
                    ->where('is_active', true)
                    ->delete();

                $connection->table('document_chunks')
                    ->where('ingestion_id', $ingestionId)
                    ->update(['is_active' => true, 'updated_at' => now()]);
            });
        } catch (\Throwable $e) {
            $connection->table('document_chunks')->where('ingestion_id', $ingestionId)->delete();
            throw $e;
        }

        $characters = collect($chunks)->sum(fn ($chunk) => mb_strlen($chunk['content']));

        return [
            'document_name' => $documentName,
            'ingestion_id' => $ingestionId,
            'chunks' => count($chunks),
            'pages' => count($extraction['pages']),
            'characters' => $characters,
            'estimated_tokens' => (int) ceil($characters / 4),
            'ocr_used' => $extraction['ocr_used'],
        ];
    }

    private function documentQuery(ConnectionInterface $connection, ?int $courseId, ?int $uploadedBy, string $documentName)
    {
        return $connection->table('document_chunks')
            ->where('course_id', $courseId)
            ->where('document_name', $documentName)
            ->where('uploaded_by', $uploadedBy);
    }

    private function extractPages(string $filePath): array
    {
        try {
            $document = (new Parser)->parseFile($filePath);
            $pages = collect($document->getPages())
                ->mapWithKeys(fn ($page, int $index) => [$index + 1 => $this->normalizeText($page->getText())])
                ->filter()
                ->all();
        } catch (\Throwable) {
            // PDF ảnh scan hoặc PDF có cấu trúc lỗi vẫn có thể xử lý bằng OCR.
            $pages = [];
        }
        $minimum = max(1, (int) config('ai.ocr.minimum_text_characters', 80));

        if (mb_strlen(implode(' ', $pages)) >= $minimum) {
            return ['pages' => $pages, 'ocr_used' => false];
        }

        if (! config('ai.ocr.enabled', true)) {
            throw new \RuntimeException('PDF là ảnh scan và OCR đang bị tắt.');
        }

        $ocrPages = $this->ocrPages($filePath);
        if ($ocrPages === []) {
            throw new \RuntimeException('Không thể nhận dạng nội dung PDF scan bằng OCR.');
        }

        return ['pages' => $ocrPages, 'ocr_used' => true];
    }

    private function ocrPages(string $filePath): array
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'smartlms-ocr-'.Str::uuid();
        File::ensureDirectoryExists($directory);

        try {
            $maxPages = max(1, (int) config('ai.ocr.max_pages', 50));
            $render = new Process([
                'pdftoppm', '-png', '-r', '150', '-f', '1', '-l', (string) $maxPages,
                $filePath, $directory.DIRECTORY_SEPARATOR.'page',
            ]);
            $render->setTimeout(max(120, $maxPages * 15));
            $render->mustRun();

            $images = glob($directory.DIRECTORY_SEPARATOR.'page-*.png') ?: [];
            natsort($images);
            $pages = [];
            foreach (array_values($images) as $index => $image) {
                $ocr = new Process([
                    'tesseract', $image, 'stdout', '-l', (string) config('ai.ocr.languages', 'vie+eng'), '--psm', '6',
                ]);
                $ocr->setTimeout(max(10, (int) config('ai.ocr.timeout_seconds_per_page', 60)));
                $ocr->mustRun();
                $text = $this->normalizeText($ocr->getOutput());
                if ($text !== '') {
                    $pages[$index + 1] = $text;
                }
            }

            return $pages;
        } catch (\Throwable $e) {
            throw new \RuntimeException('OCR PDF thất bại: '.$e->getMessage(), 0, $e);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function chunkPages(array $pages): array
    {
        $chunks = [];
        foreach ($pages as $pageNumber => $text) {
            foreach ($this->chunkText($text) as $content) {
                $chunks[] = ['page_number' => (int) $pageNumber, 'content' => $content];
            }
        }

        return $chunks;
    }

    private function chunkText(string $text): array
    {
        $chunkSize = max(400, (int) config('ai.embedding.chunk_size', 1200));
        $overlap = max(0, min($chunkSize - 100, (int) config('ai.embedding.chunk_overlap', 200)));
        $words = preg_split('/\s+/u', $this->normalizeText($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = trim($current.' '.$word);
            if ($current !== '' && mb_strlen($candidate) > $chunkSize) {
                $chunks[] = $current;
                $current = $overlap > 0 ? trim(mb_substr($current, -$overlap).' '.$word) : $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter($chunks, fn ($chunk) => mb_strlen($chunk) >= 20));
    }

    private function normalizeText(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: '';
        }

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
