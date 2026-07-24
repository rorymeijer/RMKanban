<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Levert een uitgaande webhook af, HMAC-gesigneerd, met retries + backoff.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Webhook $webhook,
        public string $event,
        public array $payload,
    ) {}

    /**
     * Exponentiële backoff tussen pogingen.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(): void
    {
        $body = json_encode([
            'event' => $this->event,
            'data' => $this->payload,
        ], JSON_THROW_ON_ERROR);

        $signature = self::sign($body, $this->webhook->secret);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Board-Event' => $this->event,
            'X-Board-Signature' => $signature,
        ])->timeout(10)->throw()->send('POST', $this->webhook->url, ['body' => $body]);
    }

    /**
     * HMAC-SHA256 handtekening (prefix sha256=) van de payload.
     */
    public static function sign(string $body, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $body, $secret);
    }
}
