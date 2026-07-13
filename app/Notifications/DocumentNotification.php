<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification for the document workflow (PRD v2 §8).
 * Stored in the notifications table; rendered by the navbar bell.
 */
class DocumentNotification extends Notification
{
    public function __construct(
        public Document $document,
        public string $message,
        public string $icon = 'bi-bell',
        public string $routeName = 'documents.index',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'doc_number' => $this->document->doc_number,
            'title' => $this->document->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'route' => $this->routeName,
        ];
    }
}
