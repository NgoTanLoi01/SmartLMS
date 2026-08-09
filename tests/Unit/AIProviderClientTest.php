<?php

namespace Tests\Unit;

use App\Services\AIProviderClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIProviderClientTest extends TestCase
{
    public function test_it_preserves_provider_endpoints_authentication_and_model_payload(): void
    {
        config([
            'services.deepseek.key' => 'provider-test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'services.deepseek.model' => 'provider-test-model',
        ]);
        Http::fake(['*' => Http::response(['choices' => []])]);

        $client = app(AIProviderClient::class);
        $client->chat(['messages' => []], 60);
        $client->versionedChat(['messages' => [], 'response_format' => ['type' => 'json_object']], 120);
        $client->resilientChat(['messages' => [], 'max_tokens' => 7000], 10, 180);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://deepseek.test/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer provider-test-key')
            && $request['model'] === 'provider-test-model');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://deepseek.test/v1/chat/completions'
            && $request['response_format'] === ['type' => 'json_object']
            && $request['model'] === 'provider-test-model');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://deepseek.test/chat/completions'
            && ($request->data()['max_tokens'] ?? null) === 7000
            && $request['model'] === 'provider-test-model');
    }
}
