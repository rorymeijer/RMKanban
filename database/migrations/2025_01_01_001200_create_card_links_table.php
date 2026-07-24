<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('linked_card_id')->constrained('cards')->cascadeOnDelete();
            // blocks | related | duplicate
            $table->string('type', 20);
            $table->timestamps();

            $table->unique(['card_id', 'linked_card_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_links');
    }
};
