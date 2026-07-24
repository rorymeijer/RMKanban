<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Support\LexoRank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('createInWorkspace', [Board::class, $workspace]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $last = $workspace->boards()->orderByDesc('position')->first();

        $board = $workspace->boards()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($workspace, $data['name']),
            'color' => $data['color'] ?? '#6366f1',
            'visibility' => 'workspace',
            'position' => LexoRank::between($last?->position, null),
            'created_by' => $request->user()?->id,
        ]);

        $board->members()->attach($request->user()?->id, ['role' => 'admin']);

        // Standaard drie lijsten zodat het board meteen bruikbaar is.
        $rank = null;
        foreach (['Te doen', 'Bezig', 'Klaar'] as $name) {
            $rank = LexoRank::between($rank, null);
            $board->lists()->create(['name' => $name, 'position' => $rank]);
        }

        $this->activity->log('board.created', $board, ['name' => $board->name], $board);

        return redirect()->route('boards.show', $board->slug);
    }

    public function update(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'visibility' => ['sometimes', 'in:private,workspace'],
        ]);

        $board->fill($data)->save();
        $this->activity->log('board.updated', $board, $data, $board);

        return back();
    }

    public function archive(Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $board->update(['archived_at' => now()]);
        $this->activity->log('board.archived', $board, [], $board);

        return back();
    }

    public function restore(Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $board->update(['archived_at' => null]);
        $this->activity->log('board.restored', $board, [], $board);

        return back();
    }

    public function show(Board $board): Response
    {
        $this->authorize('view', $board);

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

    private function uniqueSlug(Workspace $workspace, string $name): string
    {
        $base = Str::slug($name) ?: 'board';
        $slug = $base;
        $i = 1;

        while ($workspace->boards()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
