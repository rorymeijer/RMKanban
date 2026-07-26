<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Limieten (null = onbeperkt): users, workspaces, boards, storage_gb.
            $table->json('limits')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('trial_days')->default(0);
            $table->timestamps();
        });

        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('holder_name');
            $table->string('holder_email');
            // active | revoked
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('grace_days')->default(14);
            // Het laatst uitgegeven, getekende token.
            $table->text('key')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('packages');
    }
};
