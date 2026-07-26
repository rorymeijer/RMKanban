<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\License\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * SSO via OIDC (authorization-code flow), optioneel naast wachtwoordlogin.
 */
class OidcController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless((bool) config('board.oidc.enabled'), 404);

        $config = $this->discover();
        $state = Str::random(40);
        $request->session()->put('oidc.state', $state);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => (string) config('board.oidc.client_id'),
            'redirect_uri' => route('oidc.callback'),
            'scope' => (string) config('board.oidc.scopes'),
            'state' => $state,
        ]);

        return redirect()->away($config['authorization_endpoint'].'?'.$params);
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless((bool) config('board.oidc.enabled'), 404);

        abort_unless(
            $request->session()->pull('oidc.state') === $request->query('state'),
            403,
            'Ongeldige OIDC-state.',
        );

        $config = $this->discover();

        $tokenResponse = Http::asForm()->post($config['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'code' => (string) $request->query('code'),
            'redirect_uri' => route('oidc.callback'),
            'client_id' => (string) config('board.oidc.client_id'),
            'client_secret' => (string) config('board.oidc.client_secret'),
        ])->throw()->json();

        $accessToken = (string) ($tokenResponse['access_token'] ?? '');
        $claims = Http::withToken($accessToken)->get($config['userinfo_endpoint'])->throw()->json();

        $user = $this->findOrCreate($claims);
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function findOrCreate(array $claims): User
    {
        $email = (string) ($claims['email'] ?? '');
        abort_if($email === '', 422, 'OIDC leverde geen e-mailadres.');

        $existing = User::query()->where('email', $email)->first();
        if ($existing === null) {
            abort_unless(
                app(LicenseService::class)->canAddUser(),
                402,
                'Het maximale aantal gebruikers voor je licentiepakket is bereikt.',
            );
        }

        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) ($claims['name'] ?? $email),
                'username' => $this->uniqueUsername((string) ($claims['preferred_username'] ?? Str::before($email, '@'))),
                'password' => bcrypt(Str::random(40)),
                'locale' => config('board.default_locale'),
                'email_verified_at' => now(),
            ],
        );
    }

    private function uniqueUsername(string $base): string
    {
        $base = Str::slug($base, '_') ?: 'user';
        $username = $base;
        $i = 1;
        while (User::query()->where('username', $username)->exists()) {
            $username = $base.(++$i);
        }

        return $username;
    }

    /**
     * OIDC-discovery (well-known), 1 uur gecachet.
     *
     * @return array{authorization_endpoint: string, token_endpoint: string, userinfo_endpoint: string}
     */
    private function discover(): array
    {
        $issuer = rtrim((string) config('board.oidc.issuer'), '/');

        /** @var array{authorization_endpoint: string, token_endpoint: string, userinfo_endpoint: string} $config */
        $config = Cache::remember("oidc.discovery.{$issuer}", now()->addHour(), function () use ($issuer): array {
            /** @var array<string, mixed> $json */
            $json = Http::get($issuer.'/.well-known/openid-configuration')->throw()->json();

            return [
                'authorization_endpoint' => (string) $json['authorization_endpoint'],
                'token_endpoint' => (string) $json['token_endpoint'],
                'userinfo_endpoint' => (string) $json['userinfo_endpoint'],
            ];
        });

        return $config;
    }
}
