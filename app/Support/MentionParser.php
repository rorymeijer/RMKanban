<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class MentionParser
{
    /**
     * Haal @gebruikersnamen uit de tekst en resolve ze naar bestaande gebruikers.
     *
     * @return list<int> user-id's van genoemde gebruikers
     */
    public static function resolve(string $text): array
    {
        preg_match_all('/@([A-Za-z0-9_-]{3,40})/', $text, $matches);

        $usernames = array_values(array_unique($matches[1]));
        if ($usernames === []) {
            return [];
        }

        $ids = User::query()
            ->whereIn('username', $usernames)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return array_values($ids);
    }
}
