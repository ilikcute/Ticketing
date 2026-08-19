<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            // Nama yang dicetak / tampil pada BIB (Nama Pelari / Peserta)
            $table->string('bib_name')->nullable()->after('full_name');

            // Ukuran Jersey (XS, S, M, L, XL, XXL, 3XL, dll)
            $table->string('jersey_size', 20)->nullable()->after('gender');

            // Memastikan kolom gender memiliki panjang cukup untuk berbagai format
            $table->string('gender', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['bib_name', 'jersey_size']);
            $table->string('gender', 1)->nullable()->change();
        });
    }
};
