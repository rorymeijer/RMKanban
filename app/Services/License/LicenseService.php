<?php

declare(strict_types=1);

namespace App\Services\License;

use App\Models\Board;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LicenseService
{
    private const SETTING_KEY = 'license.key';

    private ?License $cached = null;

    /**
     * De actieve licentie (geverifieerd), of de terugvaltier.
     */
    public function current(): License
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        // Handhaving uit → onbeperkt, alle features.
        if (! config('license.enforce')) {
            return $this->cached = License::unlicensed([], $this->stringList(config('license.features')));
        }

        $token = Setting::get(self::SETTING_KEY);
        if (is_string($token) && $token !== '') {
            $payload = LicenseToken::verify($token, (string) config('license.public_key'));
            if ($payload !== null && ($payload['product'] ?? null) === config('license.product')) {
                $license = License::fromPayload($payload);
                if ($license->isUsable()) {
                    return $this->cached = $license;
                }
            }
        }

        /** @var array<string, int|null> $limits */
        $limits = (array) config('license.unlicensed.limits');

        return $this->cached = License::unlicensed(
            $limits,
            $this->stringList(config('license.unlicensed.features')),
        );
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        return array_values(array_map(strval(...), is_array($value) ? $value : []));
    }

    /**
     * Registreer/activeer een licentiesleutel na lokale verificatie.
     */
    public function activate(string $token): License
    {
        $payload = LicenseToken::verify($token, (string) config('license.public_key'));

        if ($payload === null) {
            throw new RuntimeException('De licentiesleutel is ongeldig of niet ondertekend door de licentieserver.');
        }
        if (($payload['product'] ?? null) !== config('license.product')) {
            throw new RuntimeException('Deze licentie is niet voor dit product.');
        }

        Setting::put(self::SETTING_KEY, $token);
        $this->cached = null;

        return $this->current();
    }

    /**
     * Controleer online op upgrades/intrekking bij de licentieserver.
     */
    public function refresh(): void
    {
        $serverUrl = (string) config('license.server_url');
        $token = Setting::get(self::SETTING_KEY);
        if ($serverUrl === '' || ! is_string($token) || $token === '') {
            return;
        }

        $payload = LicenseToken::verify($token, (string) config('license.public_key'));
        $id = is_array($payload) ? (string) ($payload['id'] ?? '') : '';
        if ($id === '') {
            return;
        }

        $response = Http::timeout(5)->acceptJson()->get(rtrim($serverUrl, '/')."/api/licenses/{$id}/status");
        if (! $response->successful()) {
            return;
        }

        $data = $response->json();
        if (($data['status'] ?? null) === 'revoked') {
            Setting::put(self::SETTING_KEY, null);
            $this->cached = null;

            return;
        }

        // Server kan een bijgewerkte (upgrade) sleutel teruggeven.
        if (is_string($data['key'] ?? null) && $data['key'] !== $token) {
            $verified = LicenseToken::verify($data['key'], (string) config('license.public_key'));
            if ($verified !== null) {
                Setting::put(self::SETTING_KEY, $data['key']);
                $this->cached = null;
            }
        }
    }

    // --- Handhaving -------------------------------------------------------

    public function allowsFeature(string $feature): bool
    {
        return $this->current()->hasFeature($feature);
    }

    public function canAddUser(): bool
    {
        return $this->withinLimit('users', User::count());
    }

    public function canAddWorkspace(): bool
    {
        return $this->withinLimit('workspaces', Workspace::count());
    }

    public function canAddBoard(): bool
    {
        return $this->withinLimit('boards', Board::count());
    }

    public function storageLimitBytes(): ?int
    {
        $gb = $this->current()->limit('storage_gb');

        return $gb === null ? null : $gb * 1024 * 1024 * 1024;
    }

    private function withinLimit(string $key, int $currentCount): bool
    {
        $limit = $this->current()->limit($key);

        return $limit === null || $currentCount < $limit;
    }
}
