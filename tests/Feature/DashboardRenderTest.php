<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class DashboardRenderTest extends TestCase {
    use DatabaseTransactions;
    public function test_dashboard_renders_for_active_user(): void {
        $u = User::where('status','active')->firstOrFail();
        $this->actingAs($u)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dokumen Overview')
            ->assertSee('Distribusi Dokumen')
            ->assertSee('Log Aktivitas');
    }
}
