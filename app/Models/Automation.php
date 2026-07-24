<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    public const TRIGGERS = ['card_moved', 'label_added', 'due_soon', 'checklist_completed', 'scheduled'];

    protected $fillable = ['board_id', 'name', 'active', 'trigger', 'conditions', 'actions'];

    protected $casts = ['active' => 'boolean', 'conditions' => 'array', 'actions' => 'array'];

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * @return HasMany<AutomationRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
