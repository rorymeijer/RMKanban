<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\LicenseToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class License extends Model
{
    protected $fillable = [
        'uuid', 'package_id', 'holder_name', 'holder_email',
        'status', 'expires_at', 'grace_days', 'key', 'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Bouw de payload en teken een verse licentiesleutel.
     */
    public function issueKey(): string
    {
        $package = $this->package()->firstOrFail();

        $payload = [
            'id' => $this->uuid,
            'product' => (string) config('license.product'),
            'package' => $package->name,
            'holder' => $this->holder_email,
            'limits' => $package->limits ?? [],
            'features' => array_values($package->features ?? []),
            'issued_at' => Carbon::now()->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'grace_days' => (int) $this->grace_days,
        ];

        $key = LicenseToken::sign($payload, (string) config('license.private_key'));
        $this->forceFill(['key' => $key])->save();

        return $key;
    }
}
