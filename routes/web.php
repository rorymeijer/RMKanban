<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

/*
 * Installer. Bereikbaar zolang de app niet geïnstalleerd is; daarna blokkeert
 * de EnsureInstalled-middleware deze routes.
 */
Route::get('/install', [InstallController::class, 'show'])->name('install.show');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');
Route::post('/install/test-mail', [InstallController::class, 'testMail'])->name('install.test-mail');

/*
 * Authenticatie.
 */
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
 * Applicatie (vereist authenticatie).
 */
Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/boards/{board:slug}', [BoardController::class, 'show'])->name('boards.show');
});
