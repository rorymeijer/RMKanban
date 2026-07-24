<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\SavedFilter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavedFilterController extends Controller
{
    public function index(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        /** @var User $user */
        $user = $request->user();

        $filters = SavedFilter::query()
            ->where('board_id', $board->id)
            ->where(fn ($q) => $q->where('shared', true)->orWhere('user_id', $user->id))
            ->get(['id', 'name', 'view', 'criteria', 'shared', 'user_id']);

        return response()->json(['filters' => $filters]);
    }

    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('view', $board);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'view' => ['required', Rule::in(SavedFilter::VIEWS)],
            'criteria' => ['nullable', 'array'],
            'shared' => ['boolean'],
        ]);

        $board->savedFilters()->create([
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'view' => $data['view'],
            'criteria' => $data['criteria'] ?? null,
            'shared' => $data['shared'] ?? false,
        ]);

        return back();
    }

    public function destroy(Request $request, SavedFilter $filter): RedirectResponse
    {
        abort_unless($filter->user_id === $request->user()?->id, 403);

        $filter->delete();

        return back();
    }
}
