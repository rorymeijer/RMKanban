<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\License;
use App\Models\Package;
use App\Support\LicenseToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicensingTest extends TestCase
{
    use RefreshDatabase;

    private string $public;

    protected function setUp(): void
    {
        parent::setUp();

        $keys = LicenseToken::generateKeypair();
        $this->public = $keys['public'];
        config([
            'license.private_key' => $keys['private'],
            'license.public_key' => $keys['public'],
            'license.admin_password' => 'geheim',
        ]);
    }

    private function makeLicense(string $status = 'active'): License
    {
        $package = Package::create([
            'name' => 'Pro',
            'slug' => 'pro-'.Str::lower(Str::random(4)),
            'limits' => ['users' => 25, 'workspaces' => 5, 'boards' => null, 'storage_gb' => 50],
            'features' => ['automations', 'webhooks', 'api'],
        ]);

        $license = License::create([
            'uuid' => (string) Str::uuid(),
            'package_id' => $package->id,
            'holder_name' => 'Klant BV',
            'holder_email' => 'klant@example.com',
            'status' => $status,
            'grace_days' => 14,
        ]);
        $license->issueKey();

        return $license;
    }

    public function test_it_signs_a_verifiable_license_key(): void
    {
        $license = $this->makeLicense();

        $payload = LicenseToken::verify((string) $license->key, $this->public);

        $this->assertNotNull($payload);
        $this->assertSame('board', $payload['product']);
        $this->assertSame('Pro', $payload['package']);
        $this->assertSame(25, $payload['limits']['users']);
        $this->assertContains('automations', $payload['features']);
    }

    public function test_status_endpoint_returns_active_key(): void
    {
        $license = $this->makeLicense();

        $this->getJson("/api/licenses/{$license->uuid}/status")
            ->assertOk()
            ->assertJson(['status' => 'active', 'key' => $license->key]);
    }

    public function test_status_endpoint_reports_revoked(): void
    {
        $license = $this->makeLicense('revoked');

        $this->getJson("/api/licenses/{$license->uuid}/status")
            ->assertOk()
            ->assertJson(['status' => 'revoked'])
            ->assertJsonMissing(['key' => $license->key]);
    }

    public function test_unknown_license_is_404(): void
    {
        $this->getJson('/api/licenses/does-not-exist/status')
            ->assertStatus(404)
            ->assertJson(['status' => 'unknown']);
    }

    public function test_upgrade_reissues_a_new_key(): void
    {
        $license = $this->makeLicense();
        $original = $license->key;

        $enterprise = Package::create([
            'name' => 'Enterprise',
            'slug' => 'ent-'.Str::lower(Str::random(4)),
            'limits' => ['users' => null, 'workspaces' => null, 'boards' => null, 'storage_gb' => null],
            'features' => ['automations', 'webhooks', 'api', 'sso'],
        ]);

        $this->withBasicAuth('admin', 'geheim')
            ->post("/admin/licenses/{$license->id}/upgrade", ['package_id' => $enterprise->id]);

        $license->refresh();
        $this->assertNotSame($original, $license->key);

        $payload = LicenseToken::verify((string) $license->key, $this->public);
        $this->assertSame('Enterprise', $payload['package']);
    }

    public function test_admin_requires_basic_auth(): void
    {
        config(['license.admin_password' => 'geheim']);
        $this->get('/admin')->assertStatus(401);
        $this->withBasicAuth('admin', 'geheim')->get('/admin')->assertOk();
    }
}
