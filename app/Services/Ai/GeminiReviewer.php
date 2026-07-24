<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/** Google Gemini implementation (D10). Only the HTTP call lives here. */
class GeminiReviewer extends AbstractAiReviewer
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    protected function callProvider(string $prompt): string
    {
        $response = Http::timeout(45)
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post(sprintf(self::ENDPOINT, $this->model), [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 3000, 'responseMimeType' => 'application/json'],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException((string) $response->status());
        }

        return (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }
}
