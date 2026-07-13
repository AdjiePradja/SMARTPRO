<?php

namespace App\Services;

use App\Models\DocumentType;

/**
 * Schema engine (D1). Reads a document type's schema definition and exposes
 * its steps and sections. The SAME schema drives the form, the preview, and
 * the PDF — so structural inconsistency between them is impossible.
 *
 * The engine never guesses layout; it renders exactly what the schema declares.
 */
class SchemaService
{
    private array $schema;

    public function __construct(DocumentType $type)
    {
        $this->schema = $type->schema_json;
    }

    public static function for(DocumentType $type): self
    {
        return new self($type);
    }

    public function raw(): array
    {
        return $this->schema;
    }

    public function docType(): string
    {
        return $this->schema['doc_type'] ?? '';
    }

    public function header(): ?string
    {
        return $this->schema['header'] ?? null;
    }

    public function footer(): ?string
    {
        return $this->schema['footer'] ?? null;
    }

    /** @return array<int, array> */
    public function steps(): array
    {
        return $this->schema['steps'] ?? [];
    }

    public function stepCount(): int
    {
        return count($this->steps());
    }

    /** Sections for a given 1-based step number. */
    public function sectionsForStep(int $step): array
    {
        foreach ($this->steps() as $s) {
            if (($s['step'] ?? null) === $step) {
                return $s['sections'] ?? [];
            }
        }

        return [];
    }

    public function stepTitle(int $step): string
    {
        foreach ($this->steps() as $s) {
            if (($s['step'] ?? null) === $step) {
                return $s['title'] ?? "Langkah {$step}";
            }
        }

        return "Langkah {$step}";
    }

    /** Flat list of every section across all steps. */
    public function allSections(): array
    {
        $out = [];
        foreach ($this->steps() as $s) {
            foreach (($s['sections'] ?? []) as $section) {
                $out[] = $section;
            }
        }

        return $out;
    }

    public function findSection(string $key): ?array
    {
        foreach ($this->allSections() as $section) {
            if (($section['key'] ?? null) === $key) {
                return $section;
            }
        }

        return null;
    }
}
