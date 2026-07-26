<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LicenseStatusController;
use Illuminate\Support\Facades\Route;

// Board's online refresh vraagt hier de status (upgrades/intrekking) op.
Route::get('/licenses/{uuid}/status', [LicenseStatusController::class, 'show'])->name('api.licenses.status');
