<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParticipantResolverTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $role, ?Department $dept): User
    {
        static $n = 0;
        $n++;
        $u = User::create([
            'name' => "T {$role} {$n}", 'nrp' => "T-{$role}-{$n}", 'jabatan' => $role,
            'department_id' => $dept?->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_sop_reviewer_own_dept_and_approver_pjo_only(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $gl = $this->makeUser('group_leader', $ictmd);
        $shIct = $this->makeUser('section_head', $ictmd);
        $shShe = $this->makeUser('section_head', $she);

        $doc = app(DocumentService::class)->createDraft($gl, $type, $ictmd, 'SOP Matriks');

        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');
        $this->assertTrue($reviewers->contains($shIct->id), 'SH dept sendiri jadi peninjau SOP');
        $this->assertFalse($reviewers->contains($shShe->id), 'SH dept lain BUKAN peninjau SOP');

        // Approver SOP = PJO saja (SH dept tidak boleh)
        $this->assertFalse($resolver->approverCandidates($doc)->pluck('id')->contains($shIct->id));
        $this->assertTrue($resolver->approverCandidates($doc)->every(fn ($u) => $u->hasRole('pimpinan')));
    }

    public function test_jsa_other_dept_includes_she_plant_heads_but_not_gl(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $plant = Department::where('code', 'PLANT')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $gl = $this->makeUser('group_leader', $ictmd);
        $dhIct = $this->makeUser('departemen_head', $ictmd);
        $shShe = $this->makeUser('section_head', $she);
        $glPlant = $this->makeUser('group_leader', $plant);

        $doc = app(DocumentService::class)->createDraft($gl, $type, $ictmd, 'JSA Matriks');
        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');

        $this->assertTrue($reviewers->contains($dhIct->id), 'DH dept sendiri');
        $this->assertTrue($reviewers->contains($shShe->id), 'SH SHE');
        // v2 rev 3a: peninjau JSA hanya SH/DH (+PJO) — GL SHE/Plant tidak lagi.
        $this->assertFalse($reviewers->contains($glPlant->id), 'GL Plant BUKAN peninjau JSA');
    }

    /**
     * PJO tak pernah jadi PENINJAU. PJO = penyetuju SOP & JSA; tapi IK & SP disetujui
     * SH/DH dept sendiri SAJA (satu orang meninjau sekaligus menyetujui — PJO tak
     * terlibat), referensi PPA-ADRO-SP/IK.
     */
    public function test_pjo_approver_for_sop_jsa_but_ik_sp_use_dept_heads(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = $this->makeUser('group_leader', $ictmd);
        $shIct = $this->makeUser('section_head', $ictmd);
        $pjo = User::where('nrp', 'PJO-0001')->firstOrFail();

        // PJO tak pernah jadi peninjau untuk jenis apa pun.
        foreach (['SOP', 'IK', 'SP', 'JSA'] as $code) {
            $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', $code)->firstOrFail(), $ictmd, "{$code} PJO");
            $this->assertFalse($resolver->reviewerCandidates($doc)->pluck('id')->contains($pjo->id), "PJO BUKAN peninjau {$code}");
        }

        // SOP & JSA: PJO adalah penyetuju.
        foreach (['SOP', 'JSA'] as $code) {
            $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', $code)->firstOrFail(), $ictmd, "{$code} approve");
            $this->assertTrue($resolver->approverCandidates($doc)->pluck('id')->contains($pjo->id), "PJO penyetuju {$code}");
        }

        // IK & SP: penyetuju = SH/DH dept sendiri SAJA; PJO tidak.
        foreach (['IK', 'SP'] as $code) {
            $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', $code)->firstOrFail(), $ictmd, "{$code} dual");
            $approvers = $resolver->approverCandidates($doc)->pluck('id');
            $this->assertFalse($approvers->contains($pjo->id), "PJO BUKAN penyetuju {$code}");
            $this->assertTrue($approvers->contains($shIct->id), "SH dept sendiri penyetuju {$code}");
        }
    }

    /** JSA: penyetuju = PJO atau SH/DH SHE & Plant — SH/DH dept sendiri hanya PENINJAU. */
    public function test_jsa_approver_is_pjo_or_she_plant_heads_only(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $gl = $this->makeUser('group_leader', $ictmd);
        $shIct = $this->makeUser('section_head', $ictmd);
        $shShe = $this->makeUser('section_head', $she);

        $doc = app(DocumentService::class)->createDraft($gl, $type, $ictmd, 'JSA Approver');
        $approvers = $resolver->approverCandidates($doc)->pluck('id');

        $this->assertTrue($approvers->contains($shShe->id), 'SH SHE boleh menyetujui JSA');
        $this->assertFalse($approvers->contains($shIct->id), 'SH dept sendiri hanya peninjau JSA, bukan penyetuju');
        // ...tetapi tetap boleh meninjau.
        $this->assertTrue($resolver->reviewerCandidates($doc)->pluck('id')->contains($shIct->id), 'SH dept sendiri peninjau JSA');
    }

    public function test_jsa_in_she_only_she_plant_heads(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $glShe = $this->makeUser('group_leader', $she);
        $shShe = $this->makeUser('section_head', $she);
        $shIct = $this->makeUser('section_head', $ictmd);

        $doc = app(DocumentService::class)->createDraft($glShe, $type, $she, 'JSA di SHE');
        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');

        $this->assertTrue($reviewers->contains($shShe->id), 'SH SHE jadi peninjau');
        $this->assertFalse($reviewers->contains($shIct->id), 'SH ICTMD BUKAN peninjau JSA di SHE');
    }
}
