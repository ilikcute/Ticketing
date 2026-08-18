<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique();
            $table->json('permissions');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('custom_permissions')->nullable()->after('is_active');
        });

        // Seed default permissions per role
        $defaults = [
            'admin' => [
                'access-dashboard',
                'access-loket',
                'access-bib-check',
                'access-import',
                'access-users',
                'access-reset-claim',
            ],
            'loket' => [
                'access-dashboard',
                'access-loket',
                'access-bib-check',
            ],
            'undian' => [
                'access-dashboard',
                'access-bib-check',
            ],
            'viewer' => [
                'access-dashboard',
                'access-bib-check',
            ],
        ];

        foreach ($defaults as $role => $permissions) {
            DB::table('role_permissions')->insert([
                'role' => $role,
                'permissions' => json_encode($permissions),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('custom_permissions');
        });

        Schema::dropIfExists('role_permissions');
    }
};
