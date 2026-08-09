<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AIProviderClient
{
    /** @param array<string, mixed> $payload */
    public function chat(array $payload, int $timeoutSeconds): Response
    {
        return Http::withToken(config('services.deepseek.key'))
            ->timeout($timeoutSeconds)
            ->post(config('services.deepseek.base_url', 'https://api.deepseek.com').'/chat/completions', $this->withModel($payload));
    }

    /** @param array<string, mixed> $payload */
    public function versionedChat(array $payload, int $timeoutSeconds): Response
    {
        return Http::withToken(config('services.deepseek.key'))
            ->timeout($timeoutSeconds)
            ->post(rtrim(config('services.deepseek.base_url', 'https://api.deepseek.com'), '/').'/v1/chat/completions', $this->withModel($payload));
    }

    /** @param array<string, mixed> $payload */
    public function resilientChat(array $payload, int $connectTimeoutSeconds, int $timeoutSeconds): Response
    {
        return Http::withToken(config('services.deepseek.key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout($connectTimeoutSeconds)
            ->timeout($timeoutSeconds)
            ->post(rtrim((string) config('services.deepseek.base_url', 'https://api.deepseek.com'), '/').'/chat/completions', $this->withModel($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withModel(array $payload): array
    {
        return ['model' => config('services.deepseek.model', 'deepseek-v4-flash')] + $payload;
    }
}
