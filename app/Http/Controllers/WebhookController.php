<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Webhook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $data = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string'],
        ]);

        $board->webhooks()->create([
            'url' => $data['url'],
            'secret' => Str::random(48),
            'events' => $data['events'] ?? null,
            'active' => true,
        ]);

        return back()->with('status', 'Webhook aangemaakt.');
    }

    public function destroy(Board $board, Webhook $webhook): RedirectResponse
    {
        $this->authorize('update', $board);
        abort_unless($webhook->board_id === $board->id, 404);

        $webhook->delete();

        return back();
    }
}
