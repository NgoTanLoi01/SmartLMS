<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiEmbeddingService
{
    public function __construct(private AiPiiSanitizer $piiSanitizer) {}

    public function embed(string $text): array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            throw new \RuntimeException('Chưa cấu hình GOOGLE_API_KEY.');
        }

        $model = config('services.gemini.embedding_model', 'gemini-embedding-001');
        $baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $response = Http::timeout(30)
            ->retry(2, 300, throw: false)
            ->post("{$baseUrl}/models/{$model}:embedContent?key={$apiKey}", [
                'model' => "models/{$model}",
                'content' => [
                    'parts' => [['text' => $this->piiSanitizer->redactText($text)]],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini embedding API lỗi HTTP '.$response->status());
        }

        $values = $response->json('embedding.values');
        if (! is_array($values) || $values === []) {
            throw new \RuntimeException('Gemini không trả về vector embedding hợp lệ.');
        }

        $expectedDimensions = max(1, (int) config('ai.embedding.dimensions', 3072));
        if (count($values) !== $expectedDimensions) {
            throw new \RuntimeException(sprintf(
                'Embedding có %d chiều, không khớp vector(%d) trong PostgreSQL.',
                count($values),
                $expectedDimensions,
            ));
        }

        return array_map('floatval', $values);
    }
}
