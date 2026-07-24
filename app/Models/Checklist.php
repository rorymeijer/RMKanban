<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    protected $fillable = ['card_id', 'title', 'position'];

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * @return HasMany<ChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    /**
     * Voortgang in procenten (0-100).
     */
    public function progress(): int
    {
        $total = $this->items()->count();
        if ($total === 0) {
            return 0;
        }

        $done = $this->items()->where('completed', true)->count();

        return (int) round(($done / $total) * 100);
    }
}
