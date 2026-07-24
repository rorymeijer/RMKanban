<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function show(Board $board): Response
    {
        $board->load([
            'lists' => fn ($q) => $q->whereNull('archived_at')->orderBy('position'),
            'lists.cards' => fn ($q) => $q->whereNull('archived_at')->orderBy('position'),
            'lists.cards.labels',
        ]);

        return Inertia::render('Board/Show', [
            'board' => [
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
                'color' => $board->color,
                'lists' => $board->lists->map($this->presentList(...))->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentList(BoardList $list): array
    {
        return [
            'id' => $list->id,
            'name' => $list->name,
            'position' => $list->position,
            'wip_limit' => $list->wip_limit,
            'collapsed' => $list->collapsed,
            'cards' => $list->cards->map($this->presentCard(...))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentCard(Card $card): array
    {
        return [
            'id' => $card->id,
            'title' => $card->title,
            'position' => $card->position,
            'cover_color' => $card->cover_color,
            'due_date' => $card->due_date?->toDateString(),
            'labels' => $card->labels->map(fn (Label $label): array => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ])->all(),
        ];
    }
}
