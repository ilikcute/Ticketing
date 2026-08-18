<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@event.test'],
            [
                'name' => 'Admin Panitia',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3 akun loket, sesuai jumlah loket fisik di venue (bisa ditambah sampai 15)
        foreach (range(1, 15) as $i) {
            User::updateOrCreate(
                ['email' => "loket{$i}@event.test"],
                [
                    'name' => "Petugas Loket {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'loket',
                    'counter_number' => "Loket-{$i}",
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        User::updateOrCreate(
            ['email' => 'undian@event.test'],
            [
                'name' => 'Panitia Undian',
                'password' => Hash::make('password'),
                'role' => 'undian',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'viewer@event.test'],
            [
                'name' => 'Viewer Sponsor',
                'password' => Hash::make('password'),
                'role' => 'viewer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Akun login untuk testing (password semua: "password"):');
        $this->command->table(['Role', 'Email'], [
            ['admin', 'admin@event.test'],
            ['loket', 'loket1@event.test s.d. loket3@event.test'],
            ['undian', 'undian@event.test'],
            ['viewer', 'viewer@event.test'],
        ]);
    }
}
