<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\Board;
use App\Models\Card;
use App\Services\AutomationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'trigger' => ['required', Rule::in(Automation::TRIGGERS)],
            'conditions' => ['nullable', 'array'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string'],
        ]);

        $board->automations()->create([
            'name' => $data['name'],
            'trigger' => $data['trigger'],
            'conditions' => $data['conditions'] ?? null,
            'actions' => $data['actions'],
            'active' => true,
        ]);

        return back();
    }

    /**
     * Draai een automation handmatig opnieuw op een kaart (herhaalbaar vanuit de UI).
     */
    public function run(Request $request, Automation $automation, AutomationEngine $engine): RedirectResponse
    {
        $board = $automation->board()->firstOrFail();
        $this->authorize('update', $board);

        $data = $request->validate(['card_id' => ['required', 'integer', 'exists:cards,id']]);
        $card = Card::query()->whereKey($data['card_id'])->where('board_id', $board->id)->firstOrFail();

        $engine->run($automation, $card);

        return back()->with('status', 'Automation uitgevoerd.');
    }
}
