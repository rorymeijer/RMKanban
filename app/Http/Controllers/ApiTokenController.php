<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    /**
     * Maak een persoonlijk API-token met scopes. Het token wordt éénmalig getoond.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
        ]);

        $token = $user->createToken($data['name'], $data['abilities'] ?? ['*']);

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $data['name'],
        ]);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->tokens()->whereKey($tokenId)->delete();

        return back()->with('status', 'API-token ingetrokken.');
    }
}
