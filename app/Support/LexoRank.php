<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Fractional-index ("LexoRank") string generation.
 *
 * Between any two neighbouring rank strings we can always compute a new rank
 * that sorts strictly between them, so reordering a single card never requires
 * renumbering the whole list. Ranks are compared lexicographically.
 *
 * Port of the well-known fractional indexing `midpoint` algorithm
 * (base-36, no trailing "zero" digit).
 */
final class LexoRank
{
    private const DIGITS = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * The first rank for an empty list.
     */
    public static function first(): string
    {
        return self::between(null, null);
    }

    /**
     * A rank strictly between $a and $b.
     *
     * Pass null for $a to insert before everything, null for $b to insert after
     * everything, or both null for the very first rank.
     */
    public static function between(?string $a, ?string $b): string
    {
        $a = $a ?? '';
        $b = ($b === null || $b === '') ? null : $b;

        if ($b !== null && strcmp($a, $b) >= 0) {
            throw new InvalidArgumentException("LexoRank: '{$a}' is niet kleiner dan '{$b}'.");
        }

        return self::midpoint($a, $b);
    }

    /**
     * Evenly spaced initial ranks for $count items.
     *
     * @return list<string>
     */
    public static function initial(int $count): array
    {
        $ranks = [];
        $prev = null;
        for ($i = 0; $i < $count; $i++) {
            $prev = self::between($prev, null);
            $ranks[] = $prev;
        }

        return $ranks;
    }

    private static function midpoint(string $a, ?string $b): string
    {
        $digits = self::DIGITS;
        $zero = $digits[0];

        if ($b !== null) {
            // Strip the longest common prefix, padding $a with zeros as needed.
            $n = 0;
            while (($a[$n] ?? $zero) === ($b[$n] ?? '')) {
                $n++;
            }
            if ($n > 0) {
                return substr($b, 0, $n).self::midpoint(substr($a, $n), substr($b, $n));
            }
        }

        $digitA = $a !== '' ? (int) strpos($digits, $a[0]) : 0;
        $digitB = $b !== null ? (int) strpos($digits, $b[0]) : strlen($digits);

        if (($digitB - $digitA) > 1) {
            $mid = (int) round(0.5 * ($digitA + $digitB));

            return $digits[$mid];
        }

        // First digits are consecutive.
        if ($b !== null && strlen($b) > 1) {
            return substr($b, 0, 1);
        }

        // $b is null or a single digit: recurse on the tail of $a.
        return $digits[$digitA].self::midpoint(substr($a, 1), null);
    }
}
