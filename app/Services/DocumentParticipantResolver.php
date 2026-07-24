<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Support\Collection;

/**
 * Kandidat PENINJAU & PENYETUJU sesuai matriks docs/aturan-alur-v2.md.
 *
 * Terpusat di satu tempat agar mudah diubah: SOP/IK/SP memakai dept sendiri;
 * JSA memperlakukan dept SHE & Plant secara khusus (lintas-departemen).
 */
class DocumentParticipantResolver
{
    private const HEAD_ROLES = [Roles::ROLE_SECTION_HEAD, Roles::ROLE_DEPARTEMEN_HEAD];

    /** ID departemen SHE & Plant (khusus JSA). */
    private function specialDeptIds(): array
    {
        return Department::whereIn('code', ['SHE', 'PLANT'])->pluck('id')->all();
    }

    /** SH/DH pada departemen tertentu. */
    private function heads(array $deptIds): Collection
    {
        return User::with('department')->where('status', 'active')
            ->whereIn('department_id', array_values(array_filter($deptIds)))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::HEAD_ROLES))
            ->get();
    }

    /** Pimpinan (PJO). */
    private function pjo(): Collection
    {
        return User::with('department')->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('name', Roles::ROLE_PIMPINAN))
            ->get();
    }

    private function sortUnique(Collection $c): Collection
    {
        return $c->unique('id')->sortBy('name')->values();
    }

    /**
     * Kandidat PENINJAU. PJO TIDAK PERNAH meninjau — dia hanya penyetuju.
     */
    public function reviewerCandidates(Document $document): Collection
    {
        $dept = $document->department_id;

        // JSA → SH/DH SHE & Plant selalu ikut meninjau (lintas-departemen).
        // Dibuat dept SHE/Plant → cukup SH/DH SHE & Plant (dept sendiri = salah satunya).
        if ($document->type->code === 'JSA') {
            $special = $this->specialDeptIds();

            return $this->sortUnique($this->heads(
                in_array($dept, $special, true) ? $special : array_merge([$dept], $special)
            ));
        }

        // SOP / IK / SP → SH/DH dept sendiri.
        return $this->sortUnique($this->heads([$dept]));
    }

    /** Kandidat PENYETUJU. PJO selalu boleh menyetujui dokumen apa pun. */
    public function approverCandidates(Document $document): Collection
    {
        $code = $document->type->code;

        // SOP → PJO saja.
        if ($code === 'SOP') {
            return $this->sortUnique($this->pjo());
        }

        // JSA → PJO atau SH/DH SHE & Plant (berlaku utk JSA dept mana pun).
        if ($code === 'JSA') {
            return $this->sortUnique($this->heads($this->specialDeptIds())->merge($this->pjo()));
        }

        // IK / SP → SH/DH dept sendiri SAJA (satu orang meninjau sekaligus
        // menyetujui; PJO tidak terlibat) — referensi PPA-ADRO-SP/IK.
        return $this->sortUnique($this->heads([$document->department_id]));
    }

    /** Validasi: apakah $userId kandidat sah sebagai peninjau/penyetuju? */
    public function isValidReviewer(Document $document, ?int $userId): bool
    {
        return $userId !== null && $this->reviewerCandidates($document)->contains('id', $userId);
    }

    public function isValidApprover(Document $document, ?int $userId): bool
    {
        return $userId !== null && $this->approverCandidates($document)->contains('id', $userId);
    }
}
