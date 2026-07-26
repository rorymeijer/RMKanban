<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardList;
use App\Services\ActivityLogger;
use App\Support\LexoRank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoardListController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('write', $board);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $last = $board->lists()->orderByDesc('position')->first();

        $list = $board->lists()->create([
            'name' => $data['name'],
            'position' => LexoRank::between($last?->position, null),
        ]);

        $this->activity->log('list.created', $list, ['name' => $list->name], $board);

        return back();
    }

    public function update(Request $request, BoardList $list): RedirectResponse
    {
        $this->authorize('write', $list->board);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'wip_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:999'],
            'collapsed' => ['sometimes', 'boolean'],
        ]);

        $list->fill($data)->save();
        $this->activity->log('list.updated', $list, $data, $list->board);

        return back();
    }

    /**
     * Verplaats een lijst tussen twee buren (LexoRank).
     */
    public function move(Request $request, BoardList $list): RedirectResponse
    {
        $board = $list->board()->firstOrFail();
        $this->authorize('write', $board);

        $data = $request->validate([
            'before_id' => ['nullable', 'integer', 'exists:lists,id'],
            'after_id' => ['nullable', 'integer', 'exists:lists,id'],
        ]);

        $beforeRank = $this->rankOf($board, $data['before_id'] ?? null);
        $afterRank = $this->rankOf($board, $data['after_id'] ?? null);

        $list->update(['position' => LexoRank::between($beforeRank, $afterRank)]);
        $this->activity->log('list.moved', $list, [], $board);

        return back();
    }

    public function archive(BoardList $list): RedirectResponse
    {
        $this->authorize('write', $list->board);

        $list->update(['archived_at' => now()]);
        $this->activity->log('list.archived', $list, [], $list->board);

        return back();
    }

    public function restore(BoardList $list): RedirectResponse
    {
        $this->authorize('write', $list->board);

        $list->update(['archived_at' => null]);
        $this->activity->log('list.restored', $list, [], $list->board);

        return back();
    }

    private function rankOf(Board $board, ?int $listId): ?string
    {
        if ($listId === null) {
            return null;
        }

        return $board->lists()->whereKey($listId)->value('position');
    }
}
