<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Http;
use App\Models\DocumentChunk;
use Exception;

class DocumentProcessingService
{
    public function processAndStorePdf($filePath, $documentName, $courseId = null)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                \Log::error('File PDF rỗng hoặc là ảnh scan.');
                return false;
            }

            $chunks = $this->chunkText($text, 800);

            foreach ($chunks as $chunk) {
                $embedding = $this->getGeminiEmbedding($chunk);

                if ($embedding) {
                    // Định dạng chuỗi vector chuẩn cho Postgres: [0.1,0.2,...]
                    $vectorString = '[' . implode(',', $embedding) . ']';

                    try {
                        $pdo = \DB::connection('pgsql')->getPdo();
                        $sql = "INSERT INTO document_chunks (course_id, document_name, content, embedding, created_at, updated_at)
                    VALUES (?, ?, ?, ?::vector, NOW(), NOW())";

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $courseId,
                            $documentName,
                            $chunk,
                            $vectorString, // Chuỗi này sẽ được ép kiểu trực tiếp tại câu SQL
                        ]);

                        \Log::info("Đã lưu thành công đoạn cho: $documentName");
                    } catch (\Exception $e) {
                        \Log::error('Lỗi khi lưu vào Postgres: ' . $e->getMessage());
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('Lỗi xử lý PDF: ' . $e->getMessage());
            return false;
        }
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
            $apiKey = env('GOOGLE_API_KEY');

            // Quay lại dùng gemini-embedding-001 vì thầy đã test cURL thành công với nó
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                    'model' => 'models/gemini-embedding-001',
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('embedding.values');
            } else {
                \Log::error('Lỗi từ Gemini API: ' . $response->body());
            }
        } catch (Exception $e) {
            \Log::error('Lỗi kết nối mạng đến Gemini: ' . $e->getMessage());
        }
        return null;
    }
}
