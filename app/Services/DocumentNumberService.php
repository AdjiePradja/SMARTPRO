<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;

/**
 * Automatic document numbering (PRD §7.1).
 * Format: PPA-ADRO-{JENIS}-{DEPT}-{NN}  e.g. PPA-ADRO-SOP-ICTMD-01
 */
class DocumentNumberService
{
    public const PREFIX = 'PPA-ADRO';

    public function generate(DocumentType $type, Department $department): string
    {
        $seq = Document::where('document_type_id', $type->id)
            ->where('department_id', $department->id)
            ->where('doc_number_manual', false)
            ->count() + 1;

        return sprintf('%s-%s-%s-%02d', self::PREFIX, $type->code, $department->code, $seq);
    }

    public function isUnique(string $number, ?int $ignoreId = null): bool
    {
        return ! Document::where('doc_number', $number)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
