<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->string('pin_code')->unique(); // kode dari transaksi / import
            $table->string('transaction_id')->nullable();
            $table->string('full_name');
            $table->string('id_card_number');
            $table->string('gender', 1)->nullable(); // L|P
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('bib_number')->nullable()->unique();

            // unclaimed | claimed | checked_in
            $table->string('status')->default('unclaimed');

            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->string('claimed_device')->nullable();

            // Kolom import_batch_id ditambahkan lewat migration terpisah
            // (2026_07_24_000010) setelah tabel import_batches dibuat.

            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
