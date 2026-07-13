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
        $staff = User::where('nrp', 'STF-0001')->firstOrFail();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        // Clean slate (rolled back by DatabaseTransactions) so counts are deterministic.
        foreach ([$staff, $gl, $pimpinan] as $u) {
            $u->notifications()->delete();
        }

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP Notif');
        $doc->update(['reviewer_id' => $gl->id, 'approver_id' => $pimpinan->id, 'current_step' => 2]);

        // Submit -> reviewer notified.
        $this->actingAs($staff)->post(route('documents.submit', $doc));
        $this->assertSame(1, $gl->fresh()->unreadNotifications()->count());

        // Review approve -> approver notified.
        $this->actingAs($gl)->post(route('review.store', $doc), ['decision' => 'approve']);
        $this->assertSame(1, $pimpinan->fresh()->unreadNotifications()->count());

        // Final approve -> creator notified (Berlaku).
        $this->actingAs($pimpinan)->post(route('approvals.store', $doc), ['decision' => 'approve']);
        $this->assertSame(1, $staff->fresh()->unreadNotifications()->count());

        // Bell "mark all read".
        $this->actingAs($staff)->post(route('notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $staff->fresh()->unreadNotifications()->count());
    }
}
