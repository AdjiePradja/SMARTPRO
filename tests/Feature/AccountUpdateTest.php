<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_update_phone_email_and_photo(): void
    {
        Storage::fake('public');

        $user = User::where('nrp', 'STF-0001')->firstOrFail();

        $this->actingAs($user)
            ->put(route('account.update'), [
                'nomor_hp' => '081234567890',
                'email' => 'staff.baru@ppa.test',
                'photo' => UploadedFile::fake()->image('avatar.png', 200, 200),
            ])
            ->assertRedirect(route('account.info'));

        $user->refresh();
        $this->assertSame('081234567890', $user->nomor_hp);
        $this->assertSame('staff.baru@ppa.test', $user->email);
        $this->assertNotNull($user->photo_path, 'foto profil tersimpan');
        Storage::disk('public')->assertExists($user->photo_path);
    }

    public function test_user_can_remove_photo(): void
    {
        Storage::fake('public');

        $user = User::where('nrp', 'STF-0001')->firstOrFail();
        $user->update(['photo_path' => UploadedFile::fake()->image('old.png')->store('avatars', 'public')]);

        $this->actingAs($user)
            ->put(route('account.update'), ['remove_photo' => '1'])
            ->assertRedirect(route('account.info'));

        $this->assertNull($user->refresh()->photo_path, 'foto profil dihapus');
    }
}
