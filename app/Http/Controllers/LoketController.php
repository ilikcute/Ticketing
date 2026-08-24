<?php

namespace App\Http\Controllers;

use App\Actions\AssignBibAction;
use App\Actions\ResetBibClaimAction;
use App\Http\Requests\AssignBibRequest;
use App\Models\Participant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class LoketController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Loket/Scan');
    }

    /**
     * Dipanggil via AJAX/Inertia saat petugas scan barcode / input PIN, No. HP, NIK, atau Nama.
     * Menggunakan Canonical Input Normalization & Branching Search Strategy.
     */
    public function lookup(Request $request, ?string $pinCode = null)
    {
        $rawTerm = trim($request->input('search') ?? $request->input('q') ?? $pinCode ?? $request->input('pinCode') ?? '');
        $type = $request->input('type', 'all'); // 'all', 'pin', 'phone', 'id_card'

        if ($rawTerm === '') {
            return response()->json(['message' => 'Silakan masukkan PIN, No. HP, atau NIK untuk mencari peserta.'], 422);
        }

        $participants = collect();

        if ($type === 'pin') {
            // Mode A: Exact PIN Search (Normalized to Upper)
            $cleanPin = strtoupper($rawTerm);
            $participants = Participant::with(['category', 'claimedBy'])
                ->where('pin_code', $cleanPin)
                ->limit(1)
                ->get();
        } elseif ($type === 'phone') {
            // Mode B: Exact Phone Search (Menggunakan Index Phone)
            $cleanDigits = preg_replace('/[^0-9]/', '', $rawTerm);
            $variants = array_unique(array_filter([
                $rawTerm,
                $cleanDigits,
                str_starts_with($cleanDigits, '62') ? '0' . substr($cleanDigits, 2) : null,
                str_starts_with($cleanDigits, '0') ? '62' . substr($cleanDigits, 1) : null,
                str_starts_with($cleanDigits, '0') ? '+62' . substr($cleanDigits, 1) : null,
            ]));

            $participants = Participant::with(['category', 'claimedBy'])
                ->whereIn('phone', $variants)
                ->limit(25)
                ->get();
        } elseif ($type === 'id_card') {
            // Mode C: Exact NIK Search (Menggunakan Index NIK)
            $participants = Participant::with(['category', 'claimedBy'])
                ->where('id_card_number', $rawTerm)
                ->limit(25)
                ->get();
        } else {
            // Mode D: 'all' Canonical Branching Router (Deterministic Priority)

            // 1. Prioritas 1: Exact PIN Lookup (O(log N) Indexed Unique)
            $cleanPin = strtoupper($rawTerm);
            $exactPin = Participant::with(['category', 'claimedBy'])
                ->where('pin_code', $cleanPin)
                ->first();

            if ($exactPin) {
                $participants = collect([$exactPin]);
            } else {
                // 2. Prioritas 2: NIK Format (15-18 digit angka)
                if (preg_match('/^[0-9]{15,18}$/', $rawTerm)) {
                    $nikMatches = Participant::with(['category', 'claimedBy'])
                        ->where('id_card_number', $rawTerm)
                        ->limit(25)
                        ->get();
                    if ($nikMatches->isNotEmpty()) {
                        $participants = $nikMatches;
                    }
                }

                // 3. Prioritas 3: No. HP Format (9-15 digit angka)
                if ($participants->isEmpty() && preg_match('/^[0-9+]{9,15}$/', $rawTerm)) {
                    $cleanDigits = preg_replace('/[^0-9]/', '', $rawTerm);
                    $variants = array_unique(array_filter([
                        $rawTerm,
                        $cleanDigits,
                        str_starts_with($cleanDigits, '62') ? '0' . substr($cleanDigits, 2) : null,
                        str_starts_with($cleanDigits, '0') ? '62' . substr($cleanDigits, 1) : null,
                    ]));

                    $phoneMatches = Participant::with(['category', 'claimedBy'])
                        ->whereIn('phone', $variants)
                        ->limit(25)
                        ->get();
                    if ($phoneMatches->isNotEmpty()) {
                        $participants = $phoneMatches;
                    }
                }

                // 4. Prioritas 4: Name Search Fallback (Explicitly non-exact, hanya dieksekusi jika bukan PIN/NIK/HP)
                if ($participants->isEmpty() && strlen($rawTerm) >= 2) {
                    $participants = Participant::with(['category', 'claimedBy'])
                        ->where('full_name', 'like', "%{$rawTerm}%")
                        ->orWhere('bib_name', 'like', "%{$rawTerm}%")
                        ->limit(25)
                        ->get();
                }
            }
        }

        if ($participants->isEmpty()) {
            return response()->json(['message' => "Data peserta tidak ditemukan berdasarkan kata kunci '{$rawTerm}'."], 404);
        }

        // Single participant match detection
        $singleParticipant = $participants->count() === 1 ? $participants->first() : null;

        if ($singleParticipant) {
            $isClaimed = $singleParticipant->status->value !== 'unclaimed' || !empty($singleParticipant->bib_number);
            $claimedDate = $singleParticipant->claimed_at 
                ? $singleParticipant->claimed_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' 
                : ($singleParticipant->updated_at ? $singleParticipant->updated_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' : '-');
            $officerName = $singleParticipant->claimedBy?->name ?? 'Petugas Loket';

            return response()->json([
                'matched_count' => 1,
                'participant' => $singleParticipant,
                'is_claimed' => $isClaimed,
                'claimed_by_name' => $officerName,
                'claimed_at_formatted' => $claimedDate,
                'claimed_device' => $singleParticipant->claimed_device ?? 'Loket-01',
                'suggested_bib' => $singleParticipant->category?->nextSuggestedBibNumber(),
            ]);
        }

        // Return multiple matches
        return response()->json([
            'matched_count' => $participants->count(),
            'participants' => $participants->map(function ($p) {
                $isClaimed = $p->status->value !== 'unclaimed' || !empty($p->bib_number);
                return [
                    'id' => $p->id,
                    'pin_code' => $p->pin_code,
                    'full_name' => $p->full_name,
                    'bib_name' => $p->bib_name ?: $p->full_name,
                    'id_card_number' => $p->id_card_number,
                    'phone' => $p->phone,
                    'category_name' => $p->category?->name ?? '-',
                    'gender' => $p->gender,
                    'jersey_size' => $p->jersey_size,
                    'bib_number' => $p->bib_number,
                    'status' => $p->status->value,
                    'is_claimed' => $isClaimed,
                    'claimed_at_formatted' => $p->claimed_at 
                        ? $p->claimed_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' 
                        : '-',
                    'claimed_by_name' => $p->claimedBy?->name ?? '-',
                ];
            }),
        ]);
    }

    public function assign(AssignBibRequest $request, AssignBibAction $action)
    {
        try {
            $participant = $action->execute(
                pinCode: $request->string('pin_code')->toString(),
                bibNumber: $request->string('bib_number')->toString(),
                officer: $request->user(),
                device: $request->user()->counter_number ?? 'unknown',
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['bib_number' => $e->getMessage()]);
        }

        return back()->with('success', "BIB {$participant->bib_number} berhasil di-assign ke {$participant->full_name}.");
    }

    /**
     * Menangani proses Otorisasi Admin untuk Reset Sengketa Claim PIN
     */
    public function resetClaim(Request $request, ResetBibClaimAction $action)
    {
        $request->validate([
            'pin_code' => ['required', 'string'],
            'admin_password' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $participant = $action->execute(
                pinCode: $request->string('pin_code')->toString(),
                adminPassword: $request->string('admin_password')->toString(),
                currentUser: $request->user(),
                reason: $request->string('reason')->toString(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['admin_password' => $e->getMessage()]);
        }

        return back()->with('success', "Sengketa/Reset Claim PIN {$participant->pin_code} BERHASIL! Status peserta {$participant->full_name} dikembalikan ke SIAP ASSIGN.");
    }

    /**
     * Update Nama Tampil di BIB dari Loket POS
     */
    public function updateBibName(Request $request)
    {
        $request->validate([
            'pin_code' => ['required', 'string'],
            'bib_name' => ['required', 'string', 'max:100'],
        ]);

        $participant = Participant::where('pin_code', $request->string('pin_code')->toString())
            ->orWhere('id', $request->input('id'))
            ->firstOrFail();

        $participant->update([
            'bib_name' => trim($request->string('bib_name')->toString()),
        ]);

        return response()->json([
            'message' => 'Nama Tampil di BIB berhasil diperbarui!',
            'bib_name' => $participant->bib_name,
            'full_name' => $participant->full_name,
        ]);
    }
}
