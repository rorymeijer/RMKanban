<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $boardIds = $this->accessibleBoardIds($user);

        $results = Card::search($query)
            ->take(50)
            ->get()
            ->filter(fn (Card $card): bool => in_array($card->board_id, $boardIds, true))
            ->map(fn (Card $card): array => [
                'id' => $card->id,
                'title' => $card->title,
                'board_id' => $card->board_id,
            ])
            ->values()
            ->all();

        return response()->json(['results' => $results]);
    }

    /**
     * @return list<int>
     */
    private function accessibleBoardIds(User $user): array
    {
        $workspaceIds = $user->workspaces()->pluck('workspaces.id');

        $ids = Card::query()->getConnection()->table('boards')
            ->whereIn('workspace_id', $workspaceIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return array_values($ids);
    }
}
