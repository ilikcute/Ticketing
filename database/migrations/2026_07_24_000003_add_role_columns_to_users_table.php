<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // enum('admin','loket','undian','viewer')
            $table->string('role')->default('viewer')->after('email');
            $table->string('counter_number')->nullable()->after('role'); // identitas loket, mis. "Loket-03"
            $table->boolean('is_active')->default(true)->after('counter_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'counter_number', 'is_active']);
        });
    }
};
