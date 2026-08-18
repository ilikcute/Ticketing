<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // "10K", "Half Marathon"
            $table->string('bib_prefix', 5)->nullable(); // "A", "HM-"
            $table->unsignedInteger('bib_start');
            $table->unsignedInteger('bib_end');
            $table->unsignedInteger('quota')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
