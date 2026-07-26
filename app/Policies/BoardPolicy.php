<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class BoardPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function view(User $user, Board $board): bool
    {
        return $board->roleFor($user) !== null;
    }

    /**
     * Aanmaken gebeurt binnen een workspace; vereist minstens schrijfrechten daar.
     */
    public function createInWorkspace(User $user, Workspace $workspace): bool
    {
        return $workspace->roleFor($user)?->canWrite() ?? false;
    }

    public function write(User $user, Board $board): bool
    {
        return $board->roleFor($user)?->canWrite() ?? false;
    }

    public function update(User $user, Board $board): bool
    {
        return $board->roleFor($user)?->canManage() ?? false;
    }

    public function delete(User $user, Board $board): bool
    {
        return $board->roleFor($user)?->canManage() ?? false;
    }
}
