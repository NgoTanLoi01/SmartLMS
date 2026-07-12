<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Http;
use App\Models\DocumentChunk;
use Exception;
use Illuminate\Support\Facades\DB;

class DocumentProcessingService
{
    public function processAndStorePdf($filePath, $documentName, $courseId = null, ?int $uploadedBy = null)
    {
        $parser = new Parser();
        $text = $parser->parseFile($filePath)->getText();
        if (empty(trim($text))) {
            throw new \RuntimeException('File PDF rỗng hoặc là ảnh scan, không thể tạo embedding.');
        }

        $chunks = $this->chunkText($text, 800);
        $connection = DB::connection('pgsql');
        $connection->table('document_chunks')
            ->where('course_id', $courseId)
            ->where('document_name', $documentName)
            ->where('uploaded_by', $uploadedBy)
            ->delete();

        $stored = 0;
        foreach ($chunks as $chunk) {
            $embedding = $this->getGeminiEmbedding($chunk);
            $connection->insert(
                'INSERT INTO document_chunks (course_id, uploaded_by, document_name, content, embedding, created_at, updated_at) VALUES (?, ?, ?, ?, ?::vector, NOW(), NOW())',
                [$courseId, $uploadedBy, $documentName, $chunk, '[' . implode(',', $embedding) . ']']
            );
            $stored++;
        }

        return [
            'document_name' => $documentName,
            'chunks' => $stored,
            'characters' => mb_strlen($text),
            'estimated_tokens' => (int) ceil(mb_strlen($text) / 4),
        ];
    }

    private function chunkText($text, $chunkSize)
    {
        $cleanText = preg_replace('/\s+/', ' ', trim($text));
        $wrapped = wordwrap($cleanText, $chunkSize, '|||');
        return explode('|||', $wrapped);
    }

    private function getGeminiEmbedding($text)
    {
        try {
            $apiKey = config('services.gemini.key');
            if (!$apiKey) {
                throw new \RuntimeException('Chưa cấu hình GOOGLE_API_KEY.');
            }

            // Quay lại dùng gemini-embedding-001 vì thầy đã test cURL thành công với nó
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                    'model' => 'models/gemini-embedding-001',
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Gemini embedding API lỗi HTTP ' . $response->status());
            }
            $values = $response->json('embedding.values');
            if (!is_array($values) || $values === []) {
                throw new \RuntimeException('Gemini không trả về vector embedding hợp lệ.');
            }
            return $values;
        } catch (Exception $e) {
            throw new \RuntimeException('Lỗi tạo embedding: ' . $e->getMessage(), 0, $e);
        }
    }
}
