<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    protected $fillable = ['board_id', 'url', 'secret', 'events', 'active'];

    protected $casts = ['events' => 'array', 'active' => 'boolean'];

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function listensTo(string $event): bool
    {
        return $this->active && ($this->events === null || in_array($event, $this->events, true));
    }
}
