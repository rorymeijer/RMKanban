<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Board;
use App\Models\Webhook;

class WebhookDispatcher
{
    /**
     * Verstuur een event naar alle webhooks van een board die erop luisteren.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Board $board, string $event, array $payload): void
    {
        $board->webhooks()
            ->get()
            ->filter(fn (Webhook $webhook): bool => $webhook->listensTo($event))
            ->each(fn (Webhook $webhook) => DeliverWebhook::dispatch($webhook, $event, $payload));
    }
}
