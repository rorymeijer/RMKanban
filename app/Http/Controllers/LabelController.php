<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('write', $board);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $board->labels()->create($data);

        return back();
    }

    public function attach(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate(['label_id' => ['required', 'integer', 'exists:labels,id']]);

        // De label moet bij hetzelfde board horen.
        $belongs = Label::query()
            ->whereKey($data['label_id'])
            ->where('board_id', $card->board_id)
            ->exists();
        abort_unless($belongs, 422, 'Label hoort niet bij dit board.');

        $card->labels()->syncWithoutDetaching([$data['label_id']]);

        return back();
    }

    public function detach(Card $card, Label $label): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $card->labels()->detach($label->id);

        return back();
    }
}
