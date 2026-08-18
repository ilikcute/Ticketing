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
     * Dipanggil via AJAX/Inertia saat petugas scan barcode / input PIN, No. HP (phone), atau NIK (id_card_number).
     * Mengembalikan detail peserta untuk verifikasi manual (FR-05, FR-06).
     */
    public function lookup(Request $request, ?string $pinCode = null)
    {
        $term = trim($request->input('search') ?? $request->input('q') ?? $pinCode ?? $request->input('pinCode') ?? '');
        $type = $request->input('type', 'all'); // 'all', 'pin', 'phone', 'id_card'

        if ($term === '') {
            return response()->json(['message' => 'Silakan masukkan PIN, No. HP, atau NIK untuk mencari peserta.'], 422);
        }

        $query = Participant::with(['category', 'claimedBy']);

        if ($type === 'pin') {
            $query->where(function ($q) use ($term) {
                $q->where('pin_code', $term)
                  ->orWhereRaw('LOWER(pin_code) = ?', [strtolower($term)])
                  ->orWhere('pin_code', 'like', "%{$term}%");
            });
        } elseif ($type === 'phone') {
            $cleanDigits = preg_replace('/[^0-9]/', '', $term);
            $query->where(function ($q) use ($term, $cleanDigits) {
                $q->where('phone', $term);
                if (!empty($cleanDigits)) {
                    $q->orWhere('phone', $cleanDigits)
                      ->orWhere('phone', 'like', "%{$cleanDigits}%");
                    
                    if (str_starts_with($cleanDigits, '62')) {
                        $alt08 = '0' . substr($cleanDigits, 2);
                        $q->orWhere('phone', $alt08)->orWhere('phone', 'like', "%{$alt08}%");
                    } elseif (str_starts_with($cleanDigits, '0')) {
                        $alt62 = '62' . substr($cleanDigits, 1);
                        $q->orWhere('phone', $alt62)->orWhere('phone', "+{$alt62}")->orWhere('phone', 'like', "%{$alt62}%");
                    }
                }
            });
        } elseif ($type === 'id_card') {
            $query->where(function ($q) use ($term) {
                $q->where('id_card_number', $term);
                if (strlen($term) >= 3) {
                    $q->orWhere('id_card_number', 'like', "%{$term}%");
                }
            });
        } else {
            // 'all' / Auto-detect
            $cleanDigits = preg_replace('/[^0-9]/', '', $term);
            $query->where(function ($q) use ($term, $cleanDigits) {
                // Exact or Case-insensitive PIN
                $q->where('pin_code', $term)
                  ->orWhereRaw('LOWER(pin_code) = ?', [strtolower($term)]);

                // NIK ID Card
                $q->orWhere('id_card_number', $term);

                // Phone
                $q->orWhere('phone', $term);
                if (!empty($cleanDigits)) {
                    $q->orWhere('phone', $cleanDigits)
                      ->orWhere('phone', 'like', "%{$cleanDigits}%");
                    
                    if (str_starts_with($cleanDigits, '62')) {
                        $alt08 = '0' . substr($cleanDigits, 2);
                        $q->orWhere('phone', $alt08)->orWhere('phone', 'like', "%{$alt08}%");
                    } elseif (str_starts_with($cleanDigits, '0')) {
                        $alt62 = '62' . substr($cleanDigits, 1);
                        $q->orWhere('phone', $alt62)->orWhere('phone', "+{$alt62}")->orWhere('phone', 'like', "%{$alt62}%");
                    }
                }

                // Partial matching if length is at least 3
                if (strlen($term) >= 3) {
                    $q->orWhere('pin_code', 'like', "%{$term}%")
                      ->orWhere('id_card_number', 'like', "%{$term}%");
                }
            });
        }

        $participants = $query->limit(25)->get();

        if ($participants->isEmpty()) {
            return response()->json(['message' => "Data peserta tidak ditemukan berdasarkan kata kunci '{$term}'."], 404);
        }

        // Check if there is an exact single match on PIN or if count is 1
        $exactPinMatch = $participants->first(fn ($p) => strcasecmp($p->pin_code, $term) === 0);
        $singleParticipant = null;

        if ($participants->count() === 1) {
            $singleParticipant = $participants->first();
        } elseif ($exactPinMatch && ($type === 'pin' || $type === 'all')) {
            $singleParticipant = $exactPinMatch;
        }

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
                    'id_card_number' => $p->id_card_number,
                    'phone' => $p->phone,
                    'category_name' => $p->category?->name ?? '-',
                    'gender' => $p->gender,
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
}
