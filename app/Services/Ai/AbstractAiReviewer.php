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

        // Persona & metode audit dari file instruksi (docs/ai/Instruksi AI.md).
        $instruction = $this->auditorInstruction();

        return <<<PROMPT
{$instruction}

---
Konteks output: Anda mengaudit dokumen jenis **{$document->type->code}** berjudul "{$document->title}".

ATURAN KEDALAMAN AUDIT (WAJIB):
- Audit HARUS MENYELURUH — periksa SETIAP aspek pada instruksi di atas: kelengkapan,
  struktur & sistematika, konsistensi istilah/penomoran/format, tata bahasa & kalimat
  ambigu, kejelasan tujuan/ruang lingkup/definisi/referensi/aktivitas/tanggung jawab/
  lampiran, kesesuaian & kelogisan alur kerja, langkah yang hilang, risiko operasional,
  risiko HSE (bila relevan), kepatuhan best practice, dan kemudahan implementasi.
- Laporkan SETIAP masalah nyata sebagai SATU temuan terpisah. Jumlah temuan mengikuti
  JUMLAH MASALAH yang ditemukan — JANGAN diringkas menjadi satu atau dua temuan. Untuk
  dokumen yang lemah, wajar bila temuan berjumlah banyak (mis. 5-15).
- DILARANG memberi hasil/saran sepanjang satu kalimat saja. Setiap "issue" dan
  "suggestion" harus dijelaskan memadai (beberapa kalimat).

Balas HANYA JSON valid (tanpa teks lain). Severity mengikuti instruksi
(Critical/High/Medium/Low/Observation → critical|major|minor|info). Semua teks Bahasa Indonesia.
{
  "summary": "Ringkasan audit LENGKAP 5-10 kalimat: penilaian umum, KELEBIHAN dokumen, kekurangan utama, dan KESIMPULAN kelayakan dokumen.",
  "findings": [
    {
      "section_key": "nama section terkait (lihat daftar di bawah)",
      "severity": "info|minor|major|critical",
      "issue": "Jelaskan spesifik: APA yang kurang/salah DAN MENGAPA itu menjadi masalah (2-4 kalimat).",
      "suggestion": "Rekomendasi konkret, spesifik, dan dapat langsung diterapkan (boleh beberapa kalimat atau poin bernomor)."
    }
  ]
}
Jika benar-benar tidak ada masalah, findings = [] dan jelaskan alasannya pada summary.

ISI DOKUMEN:
{$body}
PROMPT;
    }

    /** Muat instruksi auditor dari docs/ai; fallback ke persona ringkas. */
    protected function auditorInstruction(): string
    {
        foreach (['docs/ai/Instruksi AI.md', 'docs/ai/AI.md'] as $path) {
            $full = base_path($path);
            if (is_file($full)) {
                return trim(file_get_contents($full));
            }
        }

        return 'Anda adalah AI Document Auditor profesional (QMS/HSE) yang mengaudit dokumen mutu pertambangan secara objektif berbasis best practice.';
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
