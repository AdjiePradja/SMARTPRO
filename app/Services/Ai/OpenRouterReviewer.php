<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * OpenRouter implementation (D10). OpenAI-compatible chat completions endpoint
 * that fronts many models (set OPENROUTER_MODEL). Only the HTTP call lives here.
 */
class OpenRouterReviewer extends AbstractAiReviewer
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    protected function callProvider(string $prompt): string
    {
        $response = Http::timeout(45)
            ->withToken($this->apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda meninjau dokumen mutu pertambangan. Balas hanya JSON valid.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException((string) $response->status());
        }

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }
}
