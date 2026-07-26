<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\License\LicenseToken;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class InstallRequest extends FormRequest
{
    public function authorize(): bool
    {
        // De EnsureInstalled-middleware bewaakt de toegang tot deze route.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'locale' => ['required', 'string', 'in:'.implode(',', config('board.locales'))],
            'timezone' => ['required', 'string', 'timezone'],

            'admin_name' => ['required', 'string', 'max:100'],
            'admin_username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:users,username'],
            'admin_email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'admin_password' => [
                'required',
                'string',
                'confirmed',
                // Geen uncompromised()-check: privacy-by-default, niets verlaat de server.
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],

            // Optionele licentiesleutel: als hij is ingevuld, moet hij geldig
            // ondertekend zijn door de licentieserver (offline geverifieerd).
            'license_key' => ['nullable', 'string', $this->licenseKeyRule()],
        ];
    }

    private function licenseKeyRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $publicKey = (string) config('license.public_key');
            if ($publicKey === '') {
                $fail('Licentieverificatie is niet geconfigureerd op deze installatie.');

                return;
            }

            $payload = LicenseToken::verify(trim($value), $publicKey);
            if ($payload === null || ($payload['product'] ?? null) !== config('license.product')) {
                $fail('De licentiesleutel is ongeldig of niet voor dit product.');
            }
        };
    }
}
