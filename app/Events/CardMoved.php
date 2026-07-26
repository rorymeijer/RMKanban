<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Card;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Kaart is verplaatst — live doorgestuurd naar iedereen die het board bekijkt.
 */
class CardMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $boardId,
        public int $cardId,
        public int $listId,
        public string $position,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel("board.{$this->boardId}")];
    }

    public function broadcastAs(): string
    {
        return 'card.moved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->cardId,
            'list_id' => $this->listId,
            'position' => $this->position,
        ];
    }

    public static function fromCard(Card $card): self
    {
        return new self($card->board_id, $card->id, $card->list_id, $card->position);
    }
}
