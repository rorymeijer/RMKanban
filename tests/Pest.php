<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/**
 * Markeer de applicatie als geïnstalleerd zodat EnsureInstalled niet naar de
 * installer redirect. Gedeeld tussen feature-tests.
 */
function installApp(): void
{
    Setting::put(Setting::INSTALLED_KEY, ['at' => now()->toIso8601String()]);
    @touch(storage_path('app/installed.lock'));
}

function clearInstallState(): void
{
    @unlink(storage_path('app/installed.lock'));
    Cache::flush();
}
