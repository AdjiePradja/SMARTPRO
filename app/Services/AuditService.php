<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Mandatory audit trail (D11 mitigation). Every consequential action —
 * especially admin actions — must be recorded here to keep the system
 * defensible under external audit.
 */
class AuditService
{
    public function log(string $action, ?int $documentId = null, array $meta = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'document_id' => $documentId,
            'action' => $action,
            'meta_json' => $meta ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
