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

    private function format(DocumentType $type, Department $department, int $seq): string
    {
        return sprintf('%s-%s-%s-%02d', self::PREFIX, $type->code, $department->code, $seq);
    }

    /**
     * Nomor SEMENTARA (saat pembuatan/review; boleh bolong).
     *
     * Mengambil nomor urut TERKECIL yang belum terpakai. Dulu memakai count()+1,
     * tetapi itu bentrok begitu ada dokumen dihapus atau bernomor manual: nomornya
     * tak ikut terhitung padahal masih dipakai, sehingga draft baru bisa memperoleh
     * nomor yang SAMA dengan dokumen yang sudah ada.
     */
    public function generateTemp(DocumentType $type, Department $department): string
    {
        return $this->format($type, $department, $this->firstUnusedSeq(
            $this->usedSequences($type, $department)
        ));
    }

    /**
     * Nomor FINAL (dikunci saat approved; tak bolong). Mengambil nomor urut
     * TERKECIL yang belum terpakai pada jenis+dept, sehingga bila ada dokumen
     * lama dihapus (mis. cleanup non-staff), celah nomornya terisi kembali dan
     * tidak menimbulkan bentrok (v2 Fase D).
     */
    public function generateFinal(DocumentType $type, Department $department): string
    {
        $used = Document::where('document_type_id', $type->id)
            ->where('department_id', $department->id)
            ->whereNotNull('doc_number_final')
            ->pluck('doc_number_final')
            ->map(fn ($n) => $this->seqOf($n))
            ->filter()
            ->all();

        return $this->format($type, $department, $this->firstUnusedSeq($used));
    }

    /**
     * Semua nomor urut yang SUDAH terpakai pada jenis+dept — termasuk dokumen
     * ter-soft-delete dan bernomor manual, sebab nomornya tetap "milik" dokumen itu.
     */
    private function usedSequences(DocumentType $type, Department $department): array
    {
        return Document::withTrashed()
            ->where('document_type_id', $type->id)
            ->where('department_id', $department->id)
            ->get(['doc_number', 'doc_number_temp', 'doc_number_final'])
            ->flatMap(fn (Document $d) => [$d->doc_number, $d->doc_number_temp, $d->doc_number_final])
            ->map(fn ($n) => $this->seqOf($n))
            ->filter()
            ->unique()
            ->all();
    }

    /** Nomor urut (angka di akhir) dari sebuah nomor dokumen. */
    private function seqOf(?string $number): int
    {
        return preg_match('/(\d+)$/', (string) $number, $m) ? (int) $m[1] : 0;
    }

    /** Nomor urut terkecil yang belum terpakai. */
    private function firstUnusedSeq(array $used): int
    {
        $seq = 1;
        while (in_array($seq, $used, true)) {
            $seq++;
        }

        return $seq;
    }

    /** Backward-compatible alias (dipakai StoreDocumentRequest/preview). */
    public function generate(DocumentType $type, Department $department): string
    {
        return $this->generateTemp($type, $department);
    }

    public function isUnique(string $number, ?int $ignoreId = null): bool
    {
        return ! Document::where(fn ($q) => $q->where('doc_number_temp', $number)->orWhere('doc_number_final', $number))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
