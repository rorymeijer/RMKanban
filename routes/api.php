<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BoardApiController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('api.health');

// Zelf-gehoste API-documentatie (OpenAPI 3.1).
Route::get('/docs', [ApiDocsController::class, 'page'])->name('api.docs');
Route::get('/openapi.json', [ApiDocsController::class, 'json'])->name('api.openapi');

// REST API v1 — token-geauthenticeerd, met rate limiting per token.
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/boards', [BoardApiController::class, 'index']);
    Route::get('/boards/{board}/cards', [BoardApiController::class, 'cards']);
});
