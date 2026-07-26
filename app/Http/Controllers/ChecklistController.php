<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Support\LexoRank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function store(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate(['title' => ['required', 'string', 'max:100']]);

        $last = $card->checklists()->orderByDesc('position')->first();
        $card->checklists()->create([
            'title' => $data['title'],
            'position' => LexoRank::between($last?->position, null),
        ]);

        return back();
    }

    public function addItem(Request $request, Checklist $checklist): RedirectResponse
    {
        $card = $checklist->card()->firstOrFail();
        $this->authorize('write', $card->board);

        $data = $request->validate(['content' => ['required', 'string', 'max:255']]);

        $last = $checklist->items()->orderByDesc('position')->first();
        $checklist->items()->create([
            'content' => $data['content'],
            'position' => LexoRank::between($last?->position, null),
        ]);

        return back();
    }

    public function toggleItem(ChecklistItem $item): RedirectResponse
    {
        $card = $item->checklist()->firstOrFail()->card()->firstOrFail();
        $this->authorize('write', $card->board);

        $item->update(['completed' => ! $item->completed]);

        return back();
    }
}
