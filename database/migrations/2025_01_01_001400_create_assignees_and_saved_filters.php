<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_user', function (Blueprint $table): void {
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['card_id', 'user_id']);
        });

        Schema::create('saved_filters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // bord | tabel | kalender | tijdlijn | mijn-werk
            $table->string('view', 20)->default('board');
            $table->jsonb('criteria')->nullable();
            $table->boolean('shared')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
        Schema::dropIfExists('card_user');
    }
};
