<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Board $board): Response
    {
        $this->authorize('view', $board);

        $cards = Card::onlyTrashed()
            ->where('board_id', $board->id)
            ->latest('deleted_at')
            ->get(['id', 'title', 'deleted_at'])
            ->map(fn (Card $card): array => [
                'id' => $card->id,
                'title' => $card->title,
                'deleted_at' => $card->deleted_at?->toDateTimeString(),
            ])->all();

        return Inertia::render('Board/Trash', [
            'board' => ['id' => $board->id, 'name' => $board->name, 'slug' => $board->slug],
            'cards' => $cards,
        ]);
    }

    public function restoreCard(Board $board, int $card): RedirectResponse
    {
        $this->authorize('write', $board);

        $model = Card::onlyTrashed()->where('board_id', $board->id)->findOrFail($card);
        $model->restore();

        $this->activity->log('card.untrashed', $model, [], $board);

        return back()->with('status', 'Kaart hersteld uit de prullenbak.');
    }

    public function forceDeleteCard(Board $board, int $card): RedirectResponse
    {
        $this->authorize('write', $board);

        $model = Card::onlyTrashed()->where('board_id', $board->id)->findOrFail($card);
        $model->forceDelete();

        $this->activity->log('card.deleted', null, ['card_id' => $card], $board);

        return back()->with('status', 'Kaart definitief verwijderd.');
    }
}
