<?php

namespace App\Http\Controllers;

use App\Models\BibCheckLog;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BibCheckController extends Controller
{
    /**
     * Halaman kiosk publik, tanpa login (FR-12 s.d. FR-16 di BRD).
     */
    public function index(): Response
    {
        $event = Event::where('status', 'active')->latest()->first() ?? Event::latest()->first();

        $formattedDate = '30 Agustus 2026';
        if ($event && $event->event_date) {
            $months = [
                'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
            ];
            
            $dateStr = is_string($event->event_date) 
                ? date('d F Y', strtotime($event->event_date)) 
                : $event->event_date->format('d F Y');
                
            $formattedDate = strtr($dateStr, $months);
        }

        return Inertia::render('BibCheck/Kiosk', [
            'event' => $event ? [
                'id' => $event->id,
                'name' => strtoupper($event->name),
                'date' => strtoupper($formattedDate),
                'location' => $event->location ?? 'Parkir Barat Stadion Mandala Krida, Yogyakarta',
            ] : null,
        ]);
    }

    public function check(string $code)
    {
        $participant = Participant::with('category')
            ->where('pin_code', $code)
            ->orWhere('bib_number', $code)
            ->first();

        if (! $participant) {
            return response()->json([
                'message' => 'Data tidak ditemukan. Pastikan nomor BIB atau PIN sudah benar, atau silakan hubungi Panitia untuk memastikan Nomor BIB Anda.'
            ], 404);
        }

        BibCheckLog::create([
            'participant_id' => $participant->id,
            'checked_at' => now(),
            'device_info' => request()->userAgent(),
        ]);

        $isClaimed = !empty($participant->bib_number) || $participant->status->value !== 'unclaimed';

        return response()->json([
            'id' => $participant->id,
            'pin_code' => $participant->pin_code,
            'full_name' => $participant->full_name,
            'bib_name' => $participant->bib_name ?: $participant->full_name,
            'jersey_size' => $participant->jersey_size ?? '-',
            'gender' => $participant->gender,
            'bib_number' => $participant->bib_number ?? '—',
            'category' => $participant->category?->name ?? '-',
            'status' => $participant->status->value,
            'is_claimed' => $isClaimed,
            'status_label' => $isClaimed ? 'RACEPACK SUDAH DIAMBIL' : 'RACEPACK BELUM DIAMBIL',
        ]);
    }

    /**
     * Memperbarui Nama Tampil di BIB jika 1 pembeli membeli banyak tiket secara kolektif.
     */
    public function updateBibName(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'bib_name' => ['required', 'string', 'max:100'],
        ]);

        $code = trim($request->string('code')->toString());
        $newBibName = trim($request->string('bib_name')->toString());

        $participant = Participant::where('pin_code', $code)
            ->orWhere('bib_number', $code)
            ->orWhere('id', $request->input('id'))
            ->first();

        if (!$participant) {
            return response()->json(['message' => 'Data peserta tidak ditemukan.'], 404);
        }

        $participant->update([
            'bib_name' => $newBibName,
        ]);

        return response()->json([
            'message' => 'Nama Tampil di BIB berhasil diperbarui!',
            'bib_name' => $participant->bib_name,
            'full_name' => $participant->full_name,
        ]);
    }
}
