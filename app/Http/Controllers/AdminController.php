<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Index', [
            'stats' => [
                'users' => User::count(),
                'workspaces' => Workspace::count(),
                'boards' => Board::count(),
                'registration_open' => (bool) config('board.registration_open'),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->take(10)
                ->get(['id', 'name', 'username', 'email', 'is_admin', 'created_at'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at?->toDateString(),
                ])->all(),
        ]);
    }
}
