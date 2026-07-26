<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('admin')->prefix('admin')->group(function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

    Route::post('/licenses', [LicenseController::class, 'store'])->name('licenses.store');
    Route::post('/licenses/{license}/upgrade', [LicenseController::class, 'upgrade'])->name('licenses.upgrade');
    Route::post('/licenses/{license}/revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::post('/licenses/{license}/reactivate', [LicenseController::class, 'reactivate'])->name('licenses.reactivate');
    Route::post('/licenses/{license}/reveal', [LicenseController::class, 'show'])->name('licenses.reveal');
});
