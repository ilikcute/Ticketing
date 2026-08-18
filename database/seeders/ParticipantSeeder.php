<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::firstOrFail(); // hasil dari EventSeeder
        $categories = Category::where('event_id', $event->id)->get();
        $loketOfficer = User::where('role', 'loket')->first();

        if ($categories->isEmpty()) {
            $this->command->error('Belum ada Category. Jalankan EventSeeder dulu.');

            return;
        }

        foreach ($categories as $category) {
            // 1) Peserta BELUM tukar tiket — untuk testing alur Loket (scan → assign BIB)
            Participant::factory()
                ->count(60)
                ->for($event)
                ->for($category)
                ->create();

            // 2) Peserta SUDAH tukar tiket & punya BIB — untuk testing BIB Check & Undian
            Participant::factory()
                ->count(30)
                ->claimed()
                ->for($event)
                ->for($category)
                ->sequence(fn ($sequence) => [
                    'bib_number' => $category->bib_prefix.($category->bib_start + $sequence->index),
                    'claimed_by' => $loketOfficer?->id,
                    'claimed_device' => $loketOfficer?->counter_number ?? 'Loket-1',
                ])
                ->create();

            // 3) Peserta yang sudah CHECK-IN (sudah BIB Check sendiri) — untuk testing pool Undian
            Participant::factory()
                ->count(15)
                ->checkedIn()
                ->for($event)
                ->for($category)
                ->sequence(fn ($sequence) => [
                    'bib_number' => $category->bib_prefix.($category->bib_start + 30 + $sequence->index),
                    'claimed_by' => $loketOfficer?->id,
                    'claimed_device' => $loketOfficer?->counter_number ?? 'Loket-1',
                ])
                ->create();
        }

        $totalUnclaimed = Participant::where('status', 'unclaimed')->count();
        $totalClaimed = Participant::where('status', 'claimed')->count();
        $totalCheckedIn = Participant::where('status', 'checked_in')->count();

        $this->command->info("Dummy peserta dibuat: {$totalUnclaimed} unclaimed, {$totalClaimed} claimed, {$totalCheckedIn} checked-in.");

        // Cetak beberapa contoh PIN yang belum ditukar, biar gampang dites di halaman Loket.
        $samples = Participant::where('status', 'unclaimed')->inRandomOrder()->limit(5)->get(['full_name', 'pin_code']);
        $this->command->info('Contoh PIN untuk testing scan di /loket:');
        $this->command->table(['Nama', 'PIN Code'], $samples->map(fn ($p) => [$p->full_name, $p->pin_code])->toArray());

        // Cetak beberapa contoh BIB yang sudah claimed, buat testing /bib-check.
        $sampleBibs = Participant::where('status', 'claimed')->inRandomOrder()->limit(5)->get(['full_name', 'bib_number']);
        $this->command->info('Contoh Nomor BIB untuk testing di /bib-check:');
        $this->command->table(['Nama', 'No. BIB'], $sampleBibs->map(fn ($p) => [$p->full_name, $p->bib_number])->toArray());
    }
}
