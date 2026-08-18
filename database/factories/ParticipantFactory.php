<?php

namespace Database\Factories;

use App\Enums\ParticipantStatus;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ParticipantFactory extends Factory
{
    private const FIRST_NAMES = [
        'Ahmad', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Gita', 'Hendra',
        'Indah', 'Joko', 'Kartika', 'Lukman', 'Maya', 'Nur', 'Oki', 'Putri',
        'Rahmad', 'Sari', 'Tono', 'Umi', 'Vina', 'Wahyu', 'Yani', 'Zainal',
        'Agus', 'Bella', 'Dedi', 'Endang', 'Farhan', 'Gilang', 'Hana', 'Irfan',
    ];

    private const LAST_NAMES = [
        'Saputra', 'Wijaya', 'Kusuma', 'Pratama', 'Santoso', 'Hidayat', 'Purnama',
        'Setiawan', 'Nugroho', 'Wibowo', 'Permadi', 'Susanto', 'Handoko', 'Ramadhan',
        'Firmansyah', 'Gunawan', 'Halim', 'Iswanto', 'Junaedi', 'Kurniawan',
    ];

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['L', 'P']);

        return [
            'pin_code' => 'TIX-'.Str::upper(Str::random(8)),
            'transaction_id' => 'TRX-'.$this->faker->unique()->numerify('########'),
            'full_name' => $this->faker->randomElement(self::FIRST_NAMES).' '.$this->faker->randomElement(self::LAST_NAMES),
            'id_card_number' => $this->faker->unique()->numerify('################'), // 16 digit ala NIK
            'gender' => $gender,
            'phone' => '08'.$this->faker->numerify('##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => ParticipantStatus::Unclaimed,
        ];
    }

    /**
     * Peserta yang sudah menukar tiket & punya BIB (untuk testing BIB Check / Undian).
     */
    public function claimed(): static
    {
        return $this->state(fn () => [
            'status' => ParticipantStatus::Claimed,
            'claimed_at' => now()->subMinutes(rand(1, 600)),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => [
            'status' => ParticipantStatus::CheckedIn,
            'claimed_at' => now()->subMinutes(rand(1, 600)),
        ]);
    }
}
