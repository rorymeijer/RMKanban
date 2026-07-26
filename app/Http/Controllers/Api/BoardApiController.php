<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 (token-geauthenticeerd via Sanctum).
 */
class BoardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workspaceIds = $user->workspaces()->pluck('workspaces.id');

        $boards = Board::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->whereNull('archived_at')
            ->get(['id', 'name', 'slug', 'workspace_id']);

        return response()->json(['data' => $boards]);
    }

    public function cards(Request $request, Board $board): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($board->roleFor($user) !== null, 403);

        $cards = $board->cards()
            ->whereNull('archived_at')
            ->get(['id', 'list_id', 'title', 'due_date', 'position']);

        return response()->json(['data' => $cards]);
    }
}
