<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFilter extends Model
{
    public const VIEWS = ['board', 'table', 'calendar', 'timeline', 'my-work'];

    protected $fillable = ['board_id', 'user_id', 'name', 'view', 'criteria', 'shared'];

    protected $casts = [
        'criteria' => 'array',
        'shared' => 'boolean',
    ];

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
