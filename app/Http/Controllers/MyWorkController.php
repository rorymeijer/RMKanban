<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mijn werk": alle kaarten van de gebruiker over boards heen.
 */
class MyWorkController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workspaceIds = $user->workspaces()->pluck('workspaces.id')->all();

        $cards = Card::query()
            ->whereNull('archived_at')
            ->whereHas('board', fn ($q) => $q->whereIn('workspace_id', $workspaceIds))
            ->where(function ($q) use ($user): void {
                $q->where('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id));
            })
            ->with('board:id,name,slug')
            ->orderBy('due_date')
            ->take(200)
            ->get();

        return Inertia::render('MyWork', [
            'cards' => $cards->map(fn (Card $card): array => [
                'id' => $card->id,
                'title' => $card->title,
                'due_date' => $card->due_date?->toDateString(),
                'board' => [
                    'id' => $card->board?->id,
                    'name' => $card->board?->name,
                    'slug' => $card->board?->slug,
                ],
            ])->all(),
        ]);
    }
}
