<?php

declare(strict_types=1);

use App\Support\LexoRank;

it('genereert een eerste rank', function (): void {
    expect(LexoRank::first())->toBeString()->not->toBeEmpty();
});

it('plaatst een rank strikt tussen twee buren', function (): void {
    $a = LexoRank::first();
    $b = LexoRank::between($a, null);

    expect(strcmp($a, $b))->toBeLessThan(0);

    $mid = LexoRank::between($a, $b);
    expect(strcmp($a, $mid))->toBeLessThan(0);
    expect(strcmp($mid, $b))->toBeLessThan(0);
});

it('houdt de volgorde consistent bij herhaald invoegen in het midden', function (): void {
    $low = LexoRank::first();
    $high = LexoRank::between($low, null);

    $ranks = [$low, $high];
    for ($i = 0; $i < 50; $i++) {
        $mid = LexoRank::between($low, $high);
        expect(strcmp($low, $mid))->toBeLessThan(0);
        expect(strcmp($mid, $high))->toBeLessThan(0);
        $ranks[] = $mid;
        $high = $mid; // steeds dichter naar links invoegen
    }

    $sorted = $ranks;
    sort($sorted, SORT_STRING);
    // Alle ranks moeten uniek zijn.
    expect(array_unique($ranks))->toHaveCount(count($ranks));
});

it('geeft oplopende ranks voor initial()', function (): void {
    $ranks = LexoRank::initial(20);
    $sorted = $ranks;
    sort($sorted, SORT_STRING);

    expect($ranks)->toBe($sorted);
    expect(array_unique($ranks))->toHaveCount(20);
});
