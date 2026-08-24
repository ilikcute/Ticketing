<?php

namespace App\Actions;

use App\Enums\ParticipantStatus;
use App\Models\BibAssignmentLog;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menangani proses assign Nomor BIB ke seorang peserta di loket.
 *
 * PENTING: dibungkus DB::transaction() + lockForUpdate() agar tidak ada
 * dua petugas loket yang bisa memproses PIN yang sama secara bersamaan
 * (FR-10 di BRD), dan agar Nomor BIB tidak pernah dobel (FR-11).
 */
class AssignBibAction
{
    public function execute(string $pinCode, string $bibNumber, User $officer, string $device): Participant
    {
        $bibNumber = trim($bibNumber);

        if ($bibNumber === '' || !ctype_digit($bibNumber)) {
            throw new RuntimeException("Nomor BIB tidak boleh kosong dan hanya boleh berupa karakter angka (0-9).");
        }

        return DB::transaction(function () use ($pinCode, $bibNumber, $officer, $device) {
            // lockForUpdate mengunci baris peserta ini sampai transaksi selesai,
            // sehingga request paralel dari loket lain untuk PIN yang sama wajib menunggu antrean.
            $participant = Participant::query()
                ->where('pin_code', $pinCode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($participant->status !== ParticipantStatus::Unclaimed) {
                throw new RuntimeException(
                    "PIN ini sudah ditukar sebelumnya oleh {$participant->claimedBy?->name} pada {$participant->claimed_at}."
                );
            }

            // Pengecekan cepat awal di application layer (tanpa FOR UPDATE pada baris non-existent agar tidak memicu gap-lock)
            $bibTaken = Participant::query()
                ->where('bib_number', $bibNumber)
                ->exists();

            if ($bibTaken) {
                throw new RuntimeException("Nomor BIB {$bibNumber} sudah dipakai peserta lain.");
            }

            try {
                $participant->update([
                    'bib_number' => $bibNumber,
                    'status' => ParticipantStatus::Claimed,
                    'claimed_by' => $officer->id,
                    'claimed_at' => now(),
                    'claimed_device' => $device,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $errorCode = $e->getCode();
                $errorMsg = $e->getMessage();

                // Klasifikasi spesifik integrity constraint violation (SQLSTATE 23000 / MySQL 1062)
                if ($errorCode === '23000' || str_contains($errorMsg, '1062')) {
                    if (str_contains($errorMsg, 'bib_number')) {
                        throw new RuntimeException("Nomor BIB #{$bibNumber} baru saja diambil oleh loket lain. Silakan gunakan nomor BIB fisik yang lain.");
                    }
                    if (str_contains($errorMsg, 'pin_code')) {
                        throw new RuntimeException("PIN #{$pinCode} baru saja diproses oleh loket lain.");
                    }
                    throw new RuntimeException("Terjadi pelanggaran integritas data transaksi: duplikasi record.");
                }

                // Lempar exception jika bukan duplicate constraint
                throw $e;
            }

            BibAssignmentLog::create([
                'participant_id' => $participant->id,
                'bib_number' => $bibNumber,
                'action' => 'assign',
                'performed_by' => $officer->id,
            ]);

            return $participant->fresh();
        }, attempts: 3);
    }
}
