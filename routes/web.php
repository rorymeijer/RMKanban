<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureRegistrationOpen;
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

    Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallenge'])
        ->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);

    // Zelfregistratie (standaard dicht, via env te openen).
    Route::middleware(EnsureRegistrationOpen::class)->group(function (): void {
        Route::get('/register', [RegisterController::class, 'show'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
    });
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

    // Tweestapsverificatie.
    Route::post('/user/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/user/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Workspace-leden & uitnodigingen.
    Route::post('/workspaces/{workspace}/invitations', [WorkspaceMemberController::class, 'invite'])
        ->name('workspaces.invite');
    Route::get('/invitations/{token}/accept', [WorkspaceMemberController::class, 'accept'])
        ->name('invitations.accept');
    Route::delete('/workspaces/{workspace}/members/{member}', [WorkspaceMemberController::class, 'destroy'])
        ->name('workspaces.members.destroy');

    // Adminpaneel.
    Route::middleware(EnsureAdmin::class)->group(function (): void {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    });
});
