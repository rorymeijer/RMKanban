<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardLink extends Model
{
    public const TYPES = ['blocks', 'related', 'duplicate'];

    protected $fillable = ['card_id', 'linked_card_id', 'type'];

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function linkedCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'linked_card_id');
    }
}
