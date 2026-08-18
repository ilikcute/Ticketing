<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Indomaret Fun Run 2026',
                'event_date' => '2026-08-30',
                'location' => 'Parkir Barat Stadion Mandala Krida, Yogyakarta',
                'status' => 'active',
            ]
        );

        $categories = [
            ['name' => '5K', 'bib_prefix' => '', 'bib_start' => 1, 'bib_end' => 5000, 'quota' => 5000],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['event_id' => $event->id, 'name' => $category['name']],
                $category + ['event_id' => $event->id]
            );
        }

        $this->command->info("Event '{$event->name}' dibuat dengan " . count($categories) . ' kategori.');
    }
}
