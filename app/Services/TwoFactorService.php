<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * TOTP (RFC 6238) in pure PHP — geen externe afhankelijkheid, niets verlaat de server.
 */
class TwoFactorService
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private int $digits = 6;

    private int $period = 30;

    /**
     * Genereer een nieuw base32-geheim.
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * De TOTP-code voor een specifiek tijdstip (default: nu).
     */
    public function codeAt(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = intdiv($timestamp, $this->period);

        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $this->base32Decode($secret), true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $code = $truncated % (10 ** $this->digits);

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verifieer een code met een klein tijdsvenster (±1 periode tegen klokdrift).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        $now = time();

        for ($i = -$window; $i <= $window; $i++) {
            $candidate = $this->codeAt($secret, $now + ($i * $this->period));
            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genereer herstelcodes (eenmalig te gebruiken).
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
        }

        return $codes;
    }

    /**
     * otpauth:// URI voor authenticator-apps (QR-code).
     */
    public function otpauthUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            $this->digits,
            $this->period,
        );
    }

    private function base32Decode(string $secret): string
    {
        $secret = rtrim(strtoupper($secret), '=');
        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $index = strpos(self::BASE32, $char);
            if ($index === false) {
                continue;
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int) bindec($byte));
            }
        }

        return $bytes;
    }
}
