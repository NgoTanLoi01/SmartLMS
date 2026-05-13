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
                if (strlen(trim($chunk)) < 50) {
                    continue;
                }

                $embedding = $this->getGeminiEmbedding($chunk);

                if ($embedding) {
                    $vectorString = '[' . implode(',', $embedding) . ']';

                    \App\Models\DocumentChunk::create([
                        'course_id' => $courseId,
                        'document_name' => $documentName,
                        'content' => $chunk,
                        'embedding' => $vectorString,
                    ]);
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
            // $apiKey = env('GOOGLE_API_KEY');
            $apiKey = config('services.gemini.key');
            
            // SỬ DỤNG CHÍNH XÁC MODEL MÀ GOOGLE ĐÃ CẤP QUYỀN
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                    'model' => 'models/gemini-embedding-001',
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('embedding.values'); // Trả về mảng 768 chiều
            } else {
                \Log::error('Lỗi từ Gemini API: ' . $response->body());
            }
        } catch (Exception $e) {
            \Log::error('Lỗi kết nối mạng đến Gemini: ' . $e->getMessage());
        }

        return null;
    }
}
