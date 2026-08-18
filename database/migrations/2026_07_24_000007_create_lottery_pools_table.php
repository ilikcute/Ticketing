<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('criteria')->nullable(); // filter kategori/status
            $table->string('status')->default('open'); // open|drawing|closed
            $table->timestamps();
        });

        Schema::create('lottery_pool_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_pool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lottery_pool_id', 'participant_id']);
        });

        Schema::create('lottery_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_pool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('prize');
            $table->timestamp('drawn_at');
            $table->foreignId('drawn_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_winners');
        Schema::dropIfExists('lottery_pool_participants');
        Schema::dropIfExists('lottery_pools');
    }
};
