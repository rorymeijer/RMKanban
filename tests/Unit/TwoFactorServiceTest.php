<?php

declare(strict_types=1);

use App\Services\TwoFactorService;

beforeEach(function (): void {
    $this->service = new TwoFactorService;
    // RFC 6238-testgeheim ("12345678901234567890" in base32).
    $this->rfcSecret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
});

it('berekent een bekende RFC 6238-code', function (): void {
    // T=59 → counter 1 → code 287082 (SHA1, 6 cijfers).
    expect($this->service->codeAt($this->rfcSecret, 59))->toBe('287082');
});

it('verifieert de huidige code', function (): void {
    $secret = $this->service->generateSecret();
    $code = $this->service->codeAt($secret);

    expect($this->service->verify($secret, $code))->toBeTrue();
});

it('weigert een verkeerde code', function (): void {
    $secret = $this->service->generateSecret();

    expect($this->service->verify($secret, '000000'))->toBeFalse();
});

it('accepteert een code binnen het tijdsvenster', function (): void {
    $secret = $this->service->generateSecret();
    // Code van de vorige periode moet met window=1 nog geldig zijn.
    $previous = $this->service->codeAt($secret, time() - 30);

    expect($this->service->verify($secret, $previous, 1))->toBeTrue();
});

it('genereert unieke herstelcodes', function (): void {
    $codes = $this->service->generateRecoveryCodes(8);

    expect($codes)->toHaveCount(8);
    expect(array_unique($codes))->toHaveCount(8);
});

it('genereert een geldige otpauth-uri', function (): void {
    $uri = $this->service->otpauthUri('ABC234', 'user@example.com', 'Board');

    expect($uri)->toContain('otpauth://totp/Board:user%40example.com');
    expect($uri)->toContain('secret=ABC234');
});
