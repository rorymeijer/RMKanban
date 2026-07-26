<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value applicatie-instellingen. Bevat o.a. de installatievlag.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public const INSTALLED_KEY = 'installed';

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        if ($row === null) {
            return $default;
        }

        return $row->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('board.installed');
    }

    /**
     * Is de applicatie geïnstalleerd? Cached, met een lock-file als snelle
     * offline-check zodat we niet bij elke request de database raken.
     */
    public static function isInstalled(): bool
    {
        if (file_exists(storage_path('app/installed.lock'))) {
            return true;
        }

        return (bool) Cache::remember('board.installed', now()->addMinutes(5), function (): bool {
            return static::query()
                ->where('key', self::INSTALLED_KEY)
                ->exists();
        });
    }
}
