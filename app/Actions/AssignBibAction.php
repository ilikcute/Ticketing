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
        return DB::transaction(function () use ($pinCode, $bibNumber, $officer, $device) {
            // lockForUpdate mengunci baris ini sampai transaction selesai,
            // sehingga request dari loket lain untuk PIN yang sama harus menunggu.
            $participant = Participant::query()
                ->where('pin_code', $pinCode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($participant->status !== ParticipantStatus::Unclaimed) {
                throw new RuntimeException(
                    "PIN ini sudah ditukar sebelumnya oleh {$participant->claimedBy?->name} pada {$participant->claimed_at}."
                );
            }

            // Unique constraint di DB adalah lapisan pertahanan terakhir,
            // tapi kita cek dulu di sini supaya error message-nya jelas untuk petugas.
            $bibTaken = Participant::query()
                ->where('bib_number', $bibNumber)
                ->lockForUpdate()
                ->exists();

            if ($bibTaken) {
                throw new RuntimeException("Nomor BIB {$bibNumber} sudah dipakai peserta lain.");
            }

            $participant->update([
                'bib_number' => $bibNumber,
                'status' => ParticipantStatus::Claimed,
                'claimed_by' => $officer->id,
                'claimed_at' => now(),
                'claimed_device' => $device,
            ]);

            BibAssignmentLog::create([
                'participant_id' => $participant->id,
                'bib_number' => $bibNumber,
                'action' => 'assign',
                'performed_by' => $officer->id,
            ]);

            return $participant->fresh();
        });
    }
}
