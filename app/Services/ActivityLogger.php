<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Schrijft audit-log records. Elke schrijfactie legt actor, diff en herkomst vast.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function log(string $action, ?Model $subject = null, array $changes = [], ?Board $board = null): Activity
    {
        $user = Auth::user();
        $board = $board ?? $this->resolveBoard($subject);

        return Activity::create([
            'user_id' => $user instanceof User ? $user->id : null,
            'board_id' => $board?->id,
            'workspace_id' => $board?->workspace_id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'changes' => $changes === [] ? null : $changes,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 1000),
        ]);
    }

    private function resolveBoard(?Model $subject): ?Board
    {
        if ($subject instanceof Board) {
            return $subject;
        }

        if ($subject instanceof Card) {
            return $subject->board;
        }

        return null;
    }
}
