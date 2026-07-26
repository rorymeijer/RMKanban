<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CardLinkController extends Controller
{
    public function store(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate([
            'linked_card_id' => ['required', 'integer', 'exists:cards,id', 'different:card'],
            'type' => ['required', Rule::in(CardLink::TYPES)],
        ]);

        abort_if((int) $data['linked_card_id'] === $card->id, 422, 'Een kaart kan niet naar zichzelf verwijzen.');

        // Beide kaarten moeten in hetzelfde board zitten.
        $sameBoard = Card::query()
            ->whereKey($data['linked_card_id'])
            ->where('board_id', $card->board_id)
            ->exists();
        abort_unless($sameBoard, 422, 'Kaarten moeten in hetzelfde board staan.');

        $card->links()->firstOrCreate([
            'linked_card_id' => $data['linked_card_id'],
            'type' => $data['type'],
        ]);

        return back();
    }

    public function destroy(Card $card, CardLink $link): RedirectResponse
    {
        $this->authorize('write', $card->board);

        abort_unless($link->card_id === $card->id, 404);

        $link->delete();

        return back();
    }
}
