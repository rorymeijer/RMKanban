<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Aanwezigheid op een board: alleen leden mogen luisteren en verschijnen.
Broadcast::channel('board.{boardId}', function (User $user, int $boardId): array|false {
    $board = Board::find($boardId);
    if ($board === null || $board->roleFor($user) === null) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});
