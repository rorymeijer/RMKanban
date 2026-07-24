<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret', 64);
            // Gebeurtenissen waarop deze webhook reageert.
            $table->jsonb('events')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('automations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true);
            // Butler-achtige regel: trigger + condities + acties.
            $table->string('trigger');            // card_moved | label_added | ...
            $table->jsonb('conditions')->nullable();
            $table->jsonb('actions');
            $table->timestamps();
        });

        Schema::create('automation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('success'); // success | failed
            $table->jsonb('result')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automations');
        Schema::dropIfExists('webhooks');
    }
};
