<?php

namespace App\Actions;

use App\Enums\ImportErrorReason;
use App\Enums\ImportMode;
use App\Enums\ParticipantStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Import massal peserta dari CSV/Excel (FR-03a s.d. FR-03f di BRD).
 * Mendukung format file standar CSV, format Tiket BRImo, dan format Tiket Indomaret (IDM).
 */
class ImportParticipantsAction
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  LazyCollection<int, array<string, mixed>>  $rows  baris hasil parsing CSV/Excel
     */
    public function execute(
        LazyCollection $rows,
        Event $event,
        User $uploader,
        string $fileName,
        ImportMode $mode = ImportMode::InsertOnly,
    ): ImportBatch {
        $batch = ImportBatch::create([
            'event_id' => $event->id,
            'file_name' => $fileName,
            'uploaded_by' => $uploader->id,
            'mode' => $mode,
            'status' => 'processing',
        ]);

        $categories = $event->categories()->get();
        $categoriesByName = $categories->keyBy(
            fn (Category $c) => mb_strtolower($c->name)
        );

        $totalRows = 0;
        $successCount = 0;
        $skippedDuplicateCount = 0;
        $failedCount = 0;
        $seenPinsAcrossBatches = [];

        // Proses per-chunk supaya tidak membebani memori/DB untuk file besar.
        foreach ($rows->chunk(self::CHUNK_SIZE) as $chunk) {
            $chunkArray = $chunk->values()->all();
            $totalRows += count($chunkArray);

            $pinCodesInChunk = collect($chunkArray)->pluck('pin_code')->filter()->all();

            // Query TUNGGAL untuk cek semua PIN yang sudah ada di database
            $existingPins = Participant::query()
                ->whereIn('pin_code', $pinCodesInChunk)
                ->pluck('id', 'pin_code');

            $toInsert = [];

            foreach ($chunkArray as $index => $row) {
                $rowNumber = $totalRows - count($chunkArray) + $index + 1;

                $validationError = $this->validateRow($row, $categoriesByName, $event);
                if ($validationError) {
                    $this->logError($batch, $rowNumber, $row, $validationError);
                    $failedCount++;

                    continue;
                }

                $pin = $row['pin_code'];

                // Cek apakah PIN sudah ada di DB atau sudah muncul di baris sebelumnya dalam file yang sama
                if ($existingPins->has($pin) || isset($seenPinsAcrossBatches[$pin])) {
                    if ($mode === ImportMode::UpdateExisting && $existingPins->has($pin)) {
                        $this->updateExisting($existingPins[$pin], $row, $categoriesByName, $event);
                        $successCount++;
                    } else {
                        $this->logError($batch, $rowNumber, $row, ImportErrorReason::DuplicatePin);
                        $skippedDuplicateCount++;
                    }

                    continue;
                }

                $seenPinsAcrossBatches[$pin] = true;
                $category = $this->resolveCategory($row, $categoriesByName, $event);

                $toInsert[] = [
                    'event_id' => $event->id,
                    'category_id' => $category ? $category->id : $categories->first()?->id,
                    'pin_code' => $pin,
                    'transaction_id' => $row['transaction_id'] ?? null,
                    'full_name' => $row['full_name'],
                    'bib_name' => $row['bib_name'] ?? $row['full_name'],
                    'id_card_number' => $row['id_card_number'] ?? '-',
                    'gender' => $row['gender'] ?? null,
                    'jersey_size' => $row['jersey_size'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'status' => ParticipantStatus::Unclaimed->value,
                    'import_batch_id' => $batch->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($toInsert !== []) {
                DB::transaction(function () use ($toInsert, &$successCount, $batch) {
                    try {
                        Participant::insert($toInsert);
                        $successCount += count($toInsert);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Fallback: insert satu-satu jika ada konflik tak terduga
                        foreach ($toInsert as $rowData) {
                            try {
                                Participant::create($rowData);
                                $successCount++;
                            } catch (\Illuminate\Database\QueryException) {
                                ImportError::create([
                                    'import_batch_id' => $batch->id,
                                    'row_number' => 0,
                                    'raw_data' => $rowData,
                                    'reason' => ImportErrorReason::DuplicatePin->value,
                                    'message' => 'Duplikat PIN pada baris data.',
                                ]);
                            }
                        }
                    }
                });
            }
        }

        $batch->update([
            'total_rows' => $totalRows,
            'success_count' => $successCount,
            'skipped_duplicate_count' => $skippedDuplicateCount,
            'failed_count' => $failedCount,
            'status' => 'completed',
        ]);

        return $batch->fresh();
    }

    /**
     * Resolusi kategori event secara cerdas berdasarkan nama / substring
     */
    public function resolveCategory(array $row, $categoriesByName, Event $event): ?Category
    {
        $categories = $event->categories()->get();
        if ($categories->isEmpty()) {
            return null;
        }

        $rowCat = mb_strtolower(trim((string) ($row['category'] ?? '')));

        if ($rowCat !== '' && $categoriesByName->has($rowCat)) {
            return $categoriesByName->get($rowCat);
        }

        if ($categories->count() === 1) {
            return $categories->first();
        }

        if ($rowCat !== '') {
            foreach ($categories as $c) {
                $cName = mb_strtolower($c->name);
                if (str_contains($rowCat, $cName)) {
                    return $c;
                }
            }
        }

        return $categories->first();
    }

    private function validateRow(array $row, $categoriesByName, Event $event): ?ImportErrorReason
    {
        foreach (['pin_code', 'full_name'] as $required) {
            if (empty($row[$required])) {
                return ImportErrorReason::MissingRequiredField;
            }
        }

        if (! $this->resolveCategory($row, $categoriesByName, $event)) {
            return ImportErrorReason::InvalidFormat;
        }

        return null;
    }

    private function updateExisting(int $participantId, array $row, $categoriesByName, Event $event): void
    {
        $category = $this->resolveCategory($row, $categoriesByName, $event);

        Participant::whereKey($participantId)->update([
            'full_name' => $row['full_name'],
            'bib_name' => $row['bib_name'] ?? $row['full_name'],
            'id_card_number' => $row['id_card_number'] ?? '-',
            'transaction_id' => $row['transaction_id'] ?? null,
            'gender' => $row['gender'] ?? null,
            'jersey_size' => $row['jersey_size'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'category_id' => $category ? $category->id : null,
        ]);
    }

    private function logError(ImportBatch $batch, int $rowNumber, array $row, ImportErrorReason $reason): void
    {
        ImportError::create([
            'import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'raw_data' => $row,
            'reason' => $reason->value,
            'message' => $reason->label(),
        ]);
    }
}
