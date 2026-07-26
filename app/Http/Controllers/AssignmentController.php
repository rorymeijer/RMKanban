<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function store(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        // De toegewezen gebruiker moet toegang hebben tot het board.
        $target = User::query()->whereKey($data['user_id'])->firstOrFail();
        $board = $card->board()->firstOrFail();
        abort_unless($board->roleFor($target) !== null, 422, 'Gebruiker heeft geen toegang tot dit board.');

        $card->assignees()->syncWithoutDetaching([$target->id]);

        return back();
    }

    public function destroy(Card $card, User $user): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $card->assignees()->detach($user->id);

        return back();
    }
}
