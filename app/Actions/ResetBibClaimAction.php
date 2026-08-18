<?php

namespace App\Actions;

use App\Enums\ParticipantStatus;
use App\Models\BibAssignmentLog;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Menangani proses Reset Claim / Pembatalan Sengketa PIN oleh Admin
 * saat ada kesalahan pencatatan atau klaim tidak sah.
 */
class ResetBibClaimAction
{
    public function execute(string $pinCode, string $adminPassword, User $currentUser, string $reason): Participant
    {
        return DB::transaction(function () use ($pinCode, $adminPassword, $currentUser, $reason) {
            // Verifikasi Password Admin
            $isAdminVerified = false;
            $adminUser = null;

            $userRole = is_object($currentUser->role) ? $currentUser->role->value : (string) $currentUser->role;
            if ($userRole === 'admin') {
                if (Hash::check($adminPassword, $currentUser->password)) {
                    $isAdminVerified = true;
                    $adminUser = $currentUser;
                }
            }

            if (! $isAdminVerified) {
                // Cari admin aktif lain untuk mencocokkan password otorisasi
                $adminUser = User::where('role', 'admin')->where('is_active', true)->get()->first(function ($user) use ($adminPassword) {
                    return Hash::check($adminPassword, $user->password);
                });

                if (! $adminUser) {
                    throw new RuntimeException("Password Otorisasi Admin tidak valid. Mohon periksa kembali.");
                }
            }

            $participant = Participant::query()
                ->where('pin_code', $pinCode)
                ->lockForUpdate()
                ->firstOrFail();

            $oldBib = $participant->bib_number ?? 'N/A';
            $oldClaimedBy = $participant->claimedBy?->name ?? 'Petugas';
            $oldClaimedAt = $participant->claimed_at ?? now();

            // Reset status peserta kembali ke unclaimed agar siap di-assign ulang
            $participant->update([
                'bib_number' => null,
                'status' => ParticipantStatus::Unclaimed,
                'claimed_by' => null,
                'claimed_at' => null,
                'claimed_device' => null,
            ]);

            // Catat Log Audit Sengketa / Revoke
            BibAssignmentLog::create([
                'participant_id' => $participant->id,
                'bib_number' => $oldBib,
                'action' => 'revoke',
                'performed_by' => $adminUser->id,
                'notes' => "SENGKETA RESET: Disetujui Admin {$adminUser->name}. PIN {$pinCode} dibatalkan dari BIB #{$oldBib} (Claim awal oleh: {$oldClaimedBy} pd {$oldClaimedAt}). Alasan: {$reason}",
            ]);

            return $participant->fresh();
        });
    }
}
