<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\CardMoved;
use App\Models\BoardList;
use App\Models\Card;
use App\Services\ActivityLogger;
use App\Services\AutomationEngine;
use App\Services\WebhookDispatcher;
use App\Support\LexoRank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly AutomationEngine $automations,
        private readonly WebhookDispatcher $webhooks,
    ) {}

    public function store(Request $request, BoardList $list): RedirectResponse
    {
        $board = $list->board()->firstOrFail();
        $this->authorize('write', $board);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $last = $list->cards()->orderByDesc('position')->first();

        $card = $list->cards()->create([
            'board_id' => $board->id,
            'title' => $data['title'],
            'position' => LexoRank::between($last?->position, null),
            'created_by' => $request->user()?->id,
        ]);

        $this->activity->log('card.created', $card, ['title' => $card->title], $board);

        return back();
    }

    public function update(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'cover_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $original = $card->only(array_keys($data));
        $card->fill($data)->save();

        $this->activity->log('card.updated', $card, ['from' => $original, 'to' => $data], $card->board);

        return back();
    }

    /**
     * Verplaats een kaart naar een (andere) lijst tussen twee buren.
     */
    public function move(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate([
            'list_id' => ['required', 'integer', 'exists:lists,id'],
            'before_id' => ['nullable', 'integer', 'exists:cards,id'],
            'after_id' => ['nullable', 'integer', 'exists:cards,id'],
        ]);

        /** @var BoardList $target */
        $target = BoardList::query()->findOrFail($data['list_id']);
        abort_unless($target->board_id === $card->board_id, 422, 'Lijst hoort niet bij dit board.');

        $beforeRank = $this->cardRank($target, $data['before_id'] ?? null);
        $afterRank = $this->cardRank($target, $data['after_id'] ?? null);

        $fromList = $card->list_id;
        $card->update([
            'list_id' => $target->id,
            'position' => LexoRank::between($beforeRank, $afterRank),
        ]);

        $this->activity->log('card.moved', $card, [
            'from_list' => $fromList,
            'to_list' => $target->id,
        ], $card->board);

        // Live doorsturen naar iedereen die het board bekijkt (Reverb).
        broadcast(CardMoved::fromCard($card))->toOthers();

        // Automations en webhooks op de trigger "card_moved".
        $this->automations->dispatch('card_moved', $card);
        $board = $card->board()->firstOrFail();
        $this->webhooks->dispatch($board, 'card.moved', [
            'card_id' => $card->id,
            'list_id' => $target->id,
        ]);

        return back();
    }

    public function archive(Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $card->update(['archived_at' => now()]);
        $this->activity->log('card.archived', $card, [], $card->board);

        return back();
    }

    public function restore(Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $card->update(['archived_at' => null]);
        $this->activity->log('card.restored', $card, [], $card->board);

        return back();
    }

    public function destroy(Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $card->delete(); // soft delete → prullenbak
        $this->activity->log('card.trashed', $card, [], $card->board);

        return back();
    }

    private function cardRank(BoardList $list, ?int $cardId): ?string
    {
        if ($cardId === null) {
            return null;
        }

        return $list->cards()->whereKey($cardId)->value('position');
    }
}
