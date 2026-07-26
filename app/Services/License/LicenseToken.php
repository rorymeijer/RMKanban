<?php

declare(strict_types=1);

namespace App\Services\License;

use JsonException;
use RuntimeException;

/**
 * Draagbaar, offline-verifieerbaar licentietoken.
 *
 * Formaat: base64url(payload_json) "." base64url(ed25519_signature)
 *
 * Board verifieert lokaal met de publieke sleutel van de licentieserver, dus
 * zonder internetverbinding. Dezelfde klasse wordt door de licentieserver
 * gebruikt om te tekenen.
 */
final class LicenseToken
{
    /**
     * Teken een payload met de Ed25519-privésleutel (base64).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function sign(array $payload, string $privateKeyBase64): string
    {
        $key = base64_decode($privateKeyBase64, true);
        if ($key === false || $key === '') {
            throw new RuntimeException('Ongeldige Ed25519-privésleutel.');
        }

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signature = sodium_crypto_sign_detached($json, $key);

        return self::b64url($json).'.'.self::b64url($signature);
    }

    /**
     * Verifieer een token met de publieke sleutel en geef de payload terug,
     * of null als de handtekening ongeldig is.
     *
     * @return array<string, mixed>|null
     */
    public static function verify(string $token, string $publicKeyBase64): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        $json = self::b64urlDecode($parts[0]);
        $signature = self::b64urlDecode($parts[1]);
        $publicKey = base64_decode($publicKeyBase64, true);

        if ($json === null || $json === '' || $signature === null || $signature === ''
            || $publicKey === false || $publicKey === '') {
            return null;
        }

        try {
            if (! sodium_crypto_sign_verify_detached($signature, $json, $publicKey)) {
                return null;
            }

            /** @var array<string, mixed> $payload */
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $payload;
        } catch (JsonException|\SodiumException) {
            return null;
        }
    }

    /**
     * Genereer een nieuw Ed25519-sleutelpaar als base64-strings.
     *
     * @return array{public: string, private: string}
     */
    public static function generateKeypair(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'private' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    private static function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $encoded): ?string
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
