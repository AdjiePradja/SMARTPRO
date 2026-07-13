<?php

namespace App\Services\Ai;

use App\Models\Document;
use Illuminate\Support\Facades\Log;

/**
 * Shared LLM reviewer logic (prompt building + response parsing). Concrete
 * providers (Gemini, OpenRouter, …) only implement callProvider() — the HTTP
 * call. Adding a new provider = one small subclass + one binding case.
 */
abstract class AbstractAiReviewer implements AiReviewerInterface
{
    public function __construct(
        protected readonly ?string $apiKey,
        protected readonly string $model,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->apiKey;
    }

    public function review(Document $document, array $contentMap): array
    {
        if (! $this->isEnabled()) {
            return ['summary' => 'AI tidak dikonfigurasi (API key kosong).', 'findings' => []];
        }

        try {
            $text = $this->callProvider($this->buildPrompt($document, $contentMap));

            return $this->parse($text);
        } catch (\Throwable $e) {
            Log::warning('AI review failed', ['provider' => static::class, 'message' => $e->getMessage()]);

            return [
                'summary' => "Gagal memanggil AI ({$e->getMessage()}). Reviewer dapat melanjutkan secara manual.",
                'findings' => [],
            ];
        }
    }

    /** Send the prompt to the provider and return the raw text reply. */
    abstract protected function callProvider(string $prompt): string;

    protected function buildPrompt(Document $document, array $contentMap): string
    {
        $body = '';
        foreach ($contentMap as $key => $value) {
            $body .= "### Section: {$key}\n".$this->stringify($value)."\n\n";
        }

        return <<<PROMPT
Anda adalah ahli dokumentasi mutu pertambangan (ISO 9001 / SMKP) yang meninjau dokumen internal
perusahaan. Jenis dokumen: {$document->type->code} — "{$document->title}".

Tinjau isi dokumen di bawah ini. Berikan:
1. Ringkasan singkat isi dokumen (2-3 kalimat, Bahasa Indonesia).
2. Daftar temuan bagian yang tidak sesuai, tidak jelas, atau bisa dioptimalkan.

Balas HANYA dalam format JSON valid dengan struktur berikut:
{
  "summary": "string",
  "findings": [
    { "section_key": "string (gunakan nama section di atas)", "severity": "info|minor|major|critical", "issue": "apa masalahnya", "suggestion": "saran perbaikan konkret" }
  ]
}

Jika tidak ada temuan, kembalikan findings berupa array kosong.

ISI DOKUMEN:
{$body}
PROMPT;
    }

    protected function stringify(mixed $value): string
    {
        return is_string($value) ? $value : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '');
    }

    /** @return array{summary: string, findings: array} */
    protected function parse(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? '');
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return ['summary' => $text ?: 'Respons AI tidak dapat diparse.', 'findings' => []];
        }

        $findings = [];
        foreach (($decoded['findings'] ?? []) as $f) {
            $findings[] = [
                'section_key' => $f['section_key'] ?? '',
                'severity' => in_array($f['severity'] ?? '', ['info', 'minor', 'major', 'critical'], true) ? $f['severity'] : 'minor',
                'issue' => $f['issue'] ?? '',
                'suggestion' => $f['suggestion'] ?? '',
            ];
        }

        return ['summary' => $decoded['summary'] ?? '', 'findings' => $findings];
    }
}
