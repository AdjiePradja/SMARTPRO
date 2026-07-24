<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bell_notifications_fire_through_workflow(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        // Clean slate (rolled back by DatabaseTransactions) so counts are deterministic.
        foreach ([$gl, $sh, $pimpinan] as $u) {
            $u->notifications()->delete();
        }

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Notif');
        $doc->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id, 'current_step' => 2]);

        // Submit -> reviewer notified.
        $this->actingAs($gl)->post(route('documents.submit', $doc));
        $this->assertSame(1, $sh->fresh()->unreadNotifications()->count());

        // Reviewer opens (waiting_for_review -> in_review) then approves -> approver notified.
        $this->actingAs($sh)->get(route('review.show', $doc));
        $this->actingAs($sh)->post(route('review.store', $doc), ['decision' => 'approve']);
        $this->assertSame(1, $pimpinan->fresh()->unreadNotifications()->count());

        // Final approve -> creator notified (Berlaku).
        $this->actingAs($pimpinan)->post(route('approvals.store', $doc), ['decision' => 'approve']);
        $this->assertSame(1, $gl->fresh()->unreadNotifications()->count());

        // Bell "mark all read".
        $this->actingAs($gl)->post(route('notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $gl->fresh()->unreadNotifications()->count());
    }
}
