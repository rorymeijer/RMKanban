<?php

declare(strict_types=1);

use App\Console\Commands\PruneTrash;
use App\Console\Commands\RefreshLicense;
use Illuminate\Support\Facades\Schedule;

// Ruim dagelijks de prullenbak op (retentie 30 dagen).
Schedule::command(PruneTrash::class)->daily();

// Controleer dagelijks online op licentie-upgrades/intrekking.
Schedule::command(RefreshLicense::class)->daily();
