<?php

declare(strict_types=1);

namespace App\Services\License;

use Illuminate\Support\Carbon;

/**
 * Waarde-object dat een geverifieerde licentie voorstelt en de status bepaalt
 * (geldig / respijt / verlopen).
 */
final class License
{
    /**
     * @param  array<string, int|null>  $limits  null = onbeperkt
     * @param  list<string>  $features
     */
    public function __construct(
        public readonly string $id,
        public readonly string $package,
        public readonly array $limits,
        public readonly array $features,
        public readonly ?Carbon $expiresAt,
        public readonly int $graceDays,
        public readonly bool $unlicensed = false,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        /** @var array<string, int|null> $limits */
        $limits = $payload['limits'] ?? [];
        /** @var list<string> $features */
        $features = $payload['features'] ?? [];

        return new self(
            id: (string) ($payload['id'] ?? ''),
            package: (string) ($payload['package'] ?? 'onbekend'),
            limits: $limits,
            features: $features,
            expiresAt: isset($payload['expires_at']) ? Carbon::parse((string) $payload['expires_at']) : null,
            graceDays: (int) ($payload['grace_days'] ?? 0),
        );
    }

    /**
     * @param  array<string, int|null>  $limits
     * @param  list<string>  $features
     */
    public static function unlicensed(array $limits, array $features): self
    {
        return new self('unlicensed', 'community', $limits, $features, null, 0, true);
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    /**
     * Zit de licentie in de respijtperiode na de vervaldatum?
     */
    public function inGrace(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt->isPast()
            && $this->expiresAt->copy()->addDays($this->graceDays)->isFuture();
    }

    /**
     * Is de licentie bruikbaar (geldig of binnen respijt)?
     */
    public function isUsable(): bool
    {
        if ($this->expiresAt === null) {
            return true;
        }

        return ! $this->expiresAt->isPast() || $this->inGrace();
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    /**
     * De limiet voor een dimensie, of null voor onbeperkt.
     */
    public function limit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }
}
