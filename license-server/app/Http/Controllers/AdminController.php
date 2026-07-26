<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Package;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'packages' => Package::withCount('licenses')->get(),
            'licenses' => License::with('package')->latest()->get(),
            'publicKey' => (string) config('license.public_key'),
            'features' => (array) config('license.features'),
        ]);
    }
}
