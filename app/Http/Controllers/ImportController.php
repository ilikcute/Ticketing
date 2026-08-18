<?php

namespace App\Http\Controllers;

use App\Actions\ImportParticipantsAction;
use App\Enums\ImportMode;
use App\Http\Requests\ImportParticipantsRequest;
use App\Models\Event;
use App\Models\ImportBatch;
use App\Services\SpreadsheetReaderService;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Import/Upload', [
            'events' => Event::select('id', 'name')->get(),
            'recentBatches' => ImportBatch::with('uploadedBy')->latest()->limit(10)->get(),
        ]);
    }

    /**
     * FR-03a s.d. FR-03f: upload CSV/Excel (termasuk file Tiket BRImo dan IDM),
     * validasi PIN duplikat, ringkasan hasil batch import.
     */
    public function store(
        ImportParticipantsRequest $request,
        ImportParticipantsAction $action,
        SpreadsheetReaderService $reader
    ) {
        $event = Event::findOrFail($request->integer('event_id'));
        $file = $request->file('file');

        $rows = $reader->read(
            filePath: $file->getRealPath(),
            originalExtension: $file->getClientOriginalExtension()
        );

        $batch = $action->execute(
            rows: $rows,
            event: $event,
            uploader: $request->user(),
            fileName: $file->getClientOriginalName(),
            mode: ImportMode::from($request->string('mode')->toString()),
        );

        return redirect()
            ->route('import.result', $batch)
            ->with('success', "Import selesai: {$batch->success_count} sukses terinsert/terupdate, {$batch->skipped_duplicate_count} duplikat dilewati, {$batch->failed_count} gagal.");
    }

    public function result(ImportBatch $batch): Response
    {
        $batch->load('errors');

        $formattedErrors = $batch->errors->map(function ($err) {
            $raw = $err->raw_data['raw'] ?? $err->raw_data;
            $pin = $err->raw_data['pin_code'] ?? ($raw['KodePIN'] ?? ($raw['Ticket Number'] ?? ($raw['pin_code'] ?? '-')));
            $name = $err->raw_data['full_name'] ?? ($raw['nama'] ?? ($raw['full_name'] ?? ($raw['First Name'] ? trim(($raw['First Name'] ?? '') . ' ' . ($raw['Last Name'] ?? '')) : '-')));
            $phone = $err->raw_data['phone'] ?? ($raw['noHP'] ?? ($raw['noTelp'] ?? ($raw['Phone'] ?? ($raw['phone_number'] ?? '-'))));
            $trx = $err->raw_data['transaction_id'] ?? ($raw['KOdeBooking'] ?? ($raw['TrxIDtoko'] ?? ($raw['transaction_id'] ?? '-')));
            $category = $err->raw_data['category'] ?? ($raw['NamaPertunjukan'] ?? ($raw['kelas'] ?? ($raw['Ticket Type'] ?? '-')));

            return [
                'id' => $err->id,
                'row_number' => $err->row_number,
                'reason' => $err->reason->value,
                'reason_label' => $err->reason->label(),
                'message' => $err->message,
                'pin_code' => $pin ?: '-',
                'full_name' => $name ?: '-',
                'phone' => $phone ?: '-',
                'transaction_id' => $trx ?: '-',
                'category' => $category ?: '-',
            ];
        });

        $duplicateCount = $batch->errors->where('reason.value', 'duplicate_pin')->count();
        $missingCount = $batch->errors->where('reason.value', 'missing_required_field')->count();

        return Inertia::render('Import/Result', [
            'batch' => $batch,
            'errorsList' => $formattedErrors,
            'duplicateCount' => $duplicateCount,
            'missingCount' => $missingCount,
        ]);
    }

    /**
     * Download Laporan Khusus Rekap PIN Duplikat (.CSV siap buka di Excel)
     */
    public function downloadDuplicates(ImportBatch $batch)
    {
        $batch->load('errors');
        $duplicateErrors = $batch->errors->filter(fn ($e) => $e->reason->value === 'duplicate_pin');

        $headers = [
            'No',
            'Baris di File',
            'Kode PIN Duplikat',
            'Nama Peserta',
            'Nomor Telepon / WA',
            'NIK / ID Card',
            'Kategori / Kelas',
            'Kode Booking / Trx ID',
            'Status',
            'Keterangan',
        ];

        $handle = fopen('php://temp', 'r+');
        // Tulis UTF-8 BOM agar Microsoft Excel langsung membaca kolom dengan rapi
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        $no = 1;
        foreach ($duplicateErrors as $err) {
            $raw = $err->raw_data['raw'] ?? $err->raw_data;
            $pin = $err->raw_data['pin_code'] ?? ($raw['KodePIN'] ?? ($raw['Ticket Number'] ?? ($raw['pin_code'] ?? '-')));
            $name = $err->raw_data['full_name'] ?? ($raw['nama'] ?? ($raw['full_name'] ?? ($raw['First Name'] ? trim(($raw['First Name'] ?? '') . ' ' . ($raw['Last Name'] ?? '')) : '-')));
            $phone = $err->raw_data['phone'] ?? ($raw['noHP'] ?? ($raw['noTelp'] ?? ($raw['Phone'] ?? ($raw['phone_number'] ?? '-'))));
            $idCard = $err->raw_data['id_card_number'] ?? ($raw['id_card_number'] ?? ($raw['nik'] ?? ($raw['ID Number (NIK / KTP / KITAS) or Passport'] ?? '-')));
            $category = $err->raw_data['category'] ?? ($raw['NamaPertunjukan'] ?? ($raw['kelas'] ?? ($raw['Ticket Type'] ?? '-')));
            $trx = $err->raw_data['transaction_id'] ?? ($raw['KOdeBooking'] ?? ($raw['TrxIDtoko'] ?? ($raw['transaction_id'] ?? '-')));

            fputcsv($handle, [
                $no++,
                $err->row_number > 0 ? "Baris #{$err->row_number}" : "Duplikat Dalam Batch",
                $pin,
                $name,
                $phone,
                $idCard,
                $category,
                $trx,
                'DUPLIKAT_PIN (DILEWATI)',
                'Kode PIN ganda ditemukan pada file Excel / sudah terdaftar di database',
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($batch->file_name, PATHINFO_FILENAME));

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=Laporan_PIN_Duplikat_{$safeFileName}_Batch_{$batch->id}.csv",
        ]);
    }

    /**
     * Download Semua Error Log (.CSV siap buka di Excel)
     */
    public function downloadErrors(ImportBatch $batch)
    {
        $batch->load('errors');

        $headers = [
            'No',
            'Baris di File',
            'Kode PIN',
            'Nama Peserta',
            'Nomor Telepon / WA',
            'NIK / ID Card',
            'Kategori / Kelas',
            'Kode Booking / Trx ID',
            'Jenis Masalah',
            'Keterangan Detail',
        ];

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        $no = 1;
        foreach ($batch->errors as $err) {
            $raw = $err->raw_data['raw'] ?? $err->raw_data;
            $pin = $err->raw_data['pin_code'] ?? ($raw['KodePIN'] ?? ($raw['Ticket Number'] ?? ($raw['pin_code'] ?? '-')));
            $name = $err->raw_data['full_name'] ?? ($raw['nama'] ?? ($raw['full_name'] ?? ($raw['First Name'] ? trim(($raw['First Name'] ?? '') . ' ' . ($raw['Last Name'] ?? '')) : '-')));
            $phone = $err->raw_data['phone'] ?? ($raw['noHP'] ?? ($raw['noTelp'] ?? ($raw['Phone'] ?? ($raw['phone_number'] ?? '-'))));
            $idCard = $err->raw_data['id_card_number'] ?? ($raw['id_card_number'] ?? ($raw['nik'] ?? ($raw['ID Number (NIK / KTP / KITAS) or Passport'] ?? '-')));
            $category = $err->raw_data['category'] ?? ($raw['NamaPertunjukan'] ?? ($raw['kelas'] ?? ($raw['Ticket Type'] ?? '-')));
            $trx = $err->raw_data['transaction_id'] ?? ($raw['KOdeBooking'] ?? ($raw['TrxIDtoko'] ?? ($raw['transaction_id'] ?? '-')));

            fputcsv($handle, [
                $no++,
                $err->row_number > 0 ? "Baris #{$err->row_number}" : "-",
                $pin,
                $name,
                $phone,
                $idCard,
                $category,
                $trx,
                $err->reason->label(),
                $err->message,
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($batch->file_name, PATHINFO_FILENAME));

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=Laporan_Semua_Error_{$safeFileName}_Batch_{$batch->id}.csv",
        ]);
    }

    public function downloadTemplate()
    {
        $header = "pin_code,full_name,identity_number,category_name,gender,email,phone_number,emergency_contact\n";
        $samples = [
            "PIN-10001,\"Budi Santoso\",3171012345670001,5K,M,budi@example.com,081234567890,081987654321",
            "PIN-10002,\"Siti Aminah\",3171012345670002,5K,F,siti@example.com,081234567891,081987654322",
            "PIN-10003,\"Rahmat Hidayat\",3171012345670003,5K,M,rahmat@example.com,081234567892,081987654323",
        ];

        $csv = "\xEF\xBB\xBF" . $header . implode("\n", $samples) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=template_import_peserta_bib.csv',
        ]);
    }
}
