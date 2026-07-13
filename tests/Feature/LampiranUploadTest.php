<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LampiranUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lampiran_image_is_stored_and_pathed(): void
    {
        Storage::fake('public');

        $staff = User::where('nrp', 'STF-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'Uji Lampiran');
        $doc->update(['current_step' => 2]);

        $this->actingAs($staff)
            ->post(route('documents.saveStep', $doc), [
                'step' => 2,
                'action' => 'save',
                'sections' => ['lampiran' => [['judul' => 'Foto Perangkat', 'isi' => '']]],
                'files' => ['lampiran' => [['isi' => UploadedFile::fake()->image('foto.png', 300, 200)]]],
            ])
            ->assertRedirect();

        $doc->refresh();
        $isi = $doc->contentMap()['lampiran'][0]['isi'] ?? null;

        $this->assertNotNull($isi, 'lampiran isi should not be null after upload');
        $this->assertStringStartsWith('lampiran/', (string) $isi, 'isi should be a stored image path');
        Storage::disk('public')->assertExists($isi);
        $this->assertSame(1, $doc->attachments()->count(), 'an attachment row should be created');
    }
}
