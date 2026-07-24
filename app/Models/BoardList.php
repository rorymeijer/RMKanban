<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BoardListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Een kolom op het board. "List" is een gereserveerd woord, vandaar BoardList;
 * de tabel heet gewoon `lists`.
 */
class BoardList extends Model
{
    /** @use HasFactory<BoardListFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'lists';

    protected $fillable = [
        'board_id', 'name', 'position', 'wip_limit', 'collapsed', 'archived_at',
    ];

    protected $casts = [
        'collapsed' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'list_id')->orderBy('position');
    }
}
