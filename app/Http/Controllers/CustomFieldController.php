<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\CustomField;
use App\Support\LexoRank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomFieldController extends Controller
{
    /**
     * Definieer een custom field op een board.
     */
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(CustomField::TYPES)],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:80'],
        ]);

        $last = $board->customFields()->orderByDesc('position')->first();
        $board->customFields()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'options' => $data['options'] ?? null,
            'position' => LexoRank::between($last?->position, null),
        ]);

        return back();
    }

    /**
     * Zet (of wis) de waarde van een custom field op een kaart.
     */
    public function setValue(Request $request, Card $card, CustomField $field): RedirectResponse
    {
        $this->authorize('write', $card->board);

        abort_unless($field->board_id === $card->board_id, 422, 'Veld hoort niet bij dit board.');

        $data = $request->validate(['value' => ['nullable']]);

        $card->customFieldValues()->updateOrCreate(
            ['custom_field_id' => $field->id],
            ['value' => $data['value'] ?? null],
        );

        return back();
    }
}
