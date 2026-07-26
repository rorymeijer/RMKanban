<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRun extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['automation_id', 'card_id', 'status', 'result'];

    protected $casts = ['result' => 'array', 'created_at' => 'datetime'];
}
