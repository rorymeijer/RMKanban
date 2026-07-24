<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workspaces = $user->workspaces()
            ->with(['boards' => fn ($query) => $query->whereNull('archived_at')->orderBy('position')])
            ->get();

        return Inertia::render('Dashboard', [
            'workspaces' => $workspaces->map(fn (Workspace $workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'role' => $this->pivotRole($workspace),
                'boards' => $workspace->boards->map(fn (Board $board): array => [
                    'id' => $board->id,
                    'name' => $board->name,
                    'slug' => $board->slug,
                    'color' => $board->color,
                ])->all(),
            ])->all(),
        ]);
    }

    private function pivotRole(Workspace $workspace): string
    {
        $pivot = $workspace->getRelation('pivot');

        return $pivot instanceof Pivot ? (string) $pivot->getAttribute('role') : 'member';
    }
}
