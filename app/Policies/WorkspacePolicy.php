<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    /**
     * Globale beheerders mogen alles.
     */
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->roleFor($user) !== null;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->roleFor($user)?->canManage() ?? false;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->roleFor($user)?->canManage() ?? false;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->roleFor($user) === Role::Owner;
    }
}
