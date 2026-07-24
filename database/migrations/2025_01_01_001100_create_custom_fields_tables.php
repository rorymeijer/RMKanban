<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // text | number | choice | date | checkbox | user
            $table->string('type', 20);
            $table->jsonb('options')->nullable(); // keuze-opties
            $table->string('position', 64);
            $table->timestamps();
        });

        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->unique(['custom_field_id', 'card_id']);
        });

        // GIN-index op de jsonb-waarde voor snel filteren (alleen PostgreSQL).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX custom_field_values_value_gin ON custom_field_values USING gin (value)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
